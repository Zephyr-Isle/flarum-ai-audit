<?php

namespace ZephyrIsle\AiAudit\Listener;

use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving as PostSaving;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\Saving as UserSaving;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Psr\Log\LoggerInterface;
use ZephyrIsle\AiAudit\Job\AuditJob;
use ZephyrIsle\AiAudit\Model\AuditLog;

class QueueAudit
{
    public function __construct(
        private Queue $queue,
        private SettingsRepositoryInterface $settings,
        private LoggerInterface $logger
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PostSaving::class, [$this, 'onPostSaving']);
        $events->listen(DiscussionSaving::class, [$this, 'onDiscussionSaving']);
        $events->listen(UserSaving::class, [$this, 'onUserSaving']);
    }

    public function onPostSaving(PostSaving $event): void
    {
        $post = $event->post;
        $actor = $event->actor;

        if (!$post instanceof CommentPost) return;
        if ($this->canBypass($actor)) return;

        $isNew = !$post->exists;
        $edited = $post->exists && isset($event->data['attributes']['content']);
        if (!$isNew && !$edited) return;

        if ($isNew && $this->preApproveEnabled() && !$this->canBypassPreApprove($actor) && $this->supportsApproval($post)) {
            $post->setAttribute('is_approved', false);
        }

        $post->afterSave(function ($post) use ($actor) {
            $log = new AuditLog([
                'subject_type' => 'post',
                'subject_id' => $post->id,
                'actor_id' => $actor?->id,
                'owner_id' => $post->user_id,
                'status' => 'pending',
                'retry_count' => 0,
            ]);
            $log->save();

            $this->queue->push(new AuditJob(
                'post',
                $post->id,
                $actor?->id,
                $post->user_id,
                ['content' => $post->content],
                $log->id
            ));
        });
    }

    public function onDiscussionSaving(DiscussionSaving $event): void
    {
        $discussion = $event->discussion;
        $actor = $event->actor;

        if ($this->canBypass($actor)) return;

        $isNew = !$discussion->exists;
        $titleChanged = $discussion->exists && isset($event->data['attributes']['title']);
        if (!$isNew && !$titleChanged) return;

        if ($isNew && $this->preApproveEnabled() && !$this->canBypassPreApprove($actor) && $this->supportsApproval($discussion)) {
            $discussion->setAttribute('is_approved', false);
        }

        $discussion->afterSave(function ($discussion) use ($actor) {
            $log = new AuditLog([
                'subject_type' => 'discussion',
                'subject_id' => $discussion->id,
                'actor_id' => $actor?->id,
                'owner_id' => $discussion->user_id,
                'status' => 'pending',
                'retry_count' => 0,
            ]);
            $log->save();

            $this->queue->push(new AuditJob(
                'discussion',
                $discussion->id,
                $actor?->id,
                $discussion->user_id,
                ['title' => $discussion->title],
                $log->id
            ));
        });
    }

    public function onUserSaving(UserSaving $event): void
    {
        $user = $event->user;
        $actor = $event->actor;

        if ($this->canBypass($actor)) return;

        $changes = [];
        foreach (['username', 'display_name', 'bio'] as $k) {
            if (isset($event->data['attributes'][$k])) {
                $changes[$k] = $event->data['attributes'][$k];
            }
        }
        if ($changes === []) return;

        $user->afterSave(function ($user) use ($actor, $changes) {
            $log = new AuditLog([
                'subject_type' => 'user',
                'subject_id' => $user->id,
                'actor_id' => $actor?->id,
                'owner_id' => $user->id,
                'status' => 'pending',
                'retry_count' => 0,
            ]);
            $log->save();

            $this->queue->push(new AuditJob(
                'user',
                $user->id,
                $actor?->id,
                $user->id,
                $changes,
                $log->id
            ));
        });
    }

    private function canBypass($user): bool
    {
        return $user && ($user->isAdmin() || $user->hasPermission('zephyrisle-ai-audit.bypassAudit'));
    }

    private function canBypassPreApprove($user): bool
    {
        return $user && ($user->isAdmin() || $user->hasPermission('zephyrisle-ai-audit.bypassPreApprove'));
    }

    private function preApproveEnabled(): bool
    {
        return (bool) $this->settings->get('zephyrisle.ai-audit.pre_approve_enabled', false);
    }

    private function supportsApproval($model): bool
    {
        try {
            if (!is_object($model) || !method_exists($model, 'getConnection') || !method_exists($model, 'getTable')) {
                return false;
            }
            return $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'is_approved');
        } catch (\Exception) {
            return false;
        }
    }
}
