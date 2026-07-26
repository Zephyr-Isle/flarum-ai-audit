<?php

namespace ZephyrIsle\AiAudit\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving as PostSaving;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\AvatarSaving;
use Flarum\User\Event\Saving as UserSaving;
use Flarum\User\User;
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
        $events->listen(AvatarSaving::class, [$this, 'onAvatarSaving']);

        // fof/upload event
        if (class_exists('FoF\Upload\Event\FileWasUploaded')) {
            $events->listen('FoF\Upload\Event\FileWasUploaded', [$this, 'onFileUploaded']);
        }
        if (class_exists('FoF\Upload\Event\FileWillBeUploaded')) {
            $events->listen('FoF\Upload\Event\FileWillBeUploaded', [$this, 'onFileWillBeUploaded']);
        }
    }

    // ================================================================
    //  POST CONTENT (core)
    // ================================================================

    public function onPostSaving(PostSaving $event): void
    {
        if (!$this->isEnabled('post_content')) return;

        $post = $event->post;
        $actor = $event->actor;

        if (!$post instanceof CommentPost) return;
        if ($this->canBypass($actor)) return;

        $isNew = !$post->exists;
        $edited = $post->exists && isset($event->data['attributes']['content']);
        if (!$isNew && !$edited) return;

        if ($isNew && $this->preApproveEnabled() && !$this->canBypassPreApprove($actor)) {
            $this->setUnapproved($post);
        }

        $post->afterSave(function ($post) use ($actor) {
            $this->queueAudit('post_content', $post->id, $actor?->id, $post->user_id, [
                'content' => $post->content,
            ]);

            // Check for images in content
            if ($this->isEnabled('post_image') && $this->hasImageUrls((string) $post->content)) {
                $this->queueAudit('post_image', $post->id, $actor?->id, $post->user_id, [
                    'content' => $post->content,
                ]);
            }
        });
    }

    // ================================================================
    //  DISCUSSION TITLE (core)
    // ================================================================

    public function onDiscussionSaving(DiscussionSaving $event): void
    {
        if (!$this->isEnabled('discussion_title')) return;

        $discussion = $event->discussion;
        $actor = $event->actor;

        if ($this->canBypass($actor)) return;

        $titleChanged = $discussion->exists && isset($event->data['attributes']['title']);
        if (!$titleChanged) return;

        $discussion->afterSave(function ($discussion) use ($actor) {
            $this->queueAudit('discussion_title', $discussion->id, $actor?->id, $discussion->user_id, [
                'title' => $discussion->title,
            ]);
        });
    }

    // ================================================================
    //  USER PROFILE CHANGES (core + flarum/nicknames + fof/user-bio)
    // ================================================================

    public function onUserSaving(UserSaving $event): void
    {
        $user = $event->user;
        $actor = $event->actor;

        if ($this->canBypass($actor)) return;

        $isNew = !$user->exists;
        $changes = [];

        // Username changes (core)
        if ($this->isEnabled('username') && isset($event->data['attributes']['username'])) {
            $changes['username'] = $event->data['attributes']['username'];
            $changes['oldUsername'] = $user->getOriginal('username') ?? $user->username;

            // Queue username audit
            $user->afterSave(function ($user) use ($actor, $changes) {
                $this->queueAudit('user_username', $user->id, $actor?->id, $user->id, $changes);
            });
        }

        // Nickname changes (flarum/nicknames)
        if ($this->isEnabled('nickname') && isset($event->data['attributes']['nickname'])) {
            $oldNickname = $this->getUserAttribute($user, 'nickname') ?? '';

            $user->afterSave(function ($user) use ($actor, $oldNickname) {
                $this->queueAudit('user_nickname', $user->id, $actor?->id, $user->id, [
                    'nickname' => $user->nickname ?? '',
                    'oldNickname' => $oldNickname,
                ]);
            });
        }

        // Bio changes (fof/user-bio)
        if ($this->isEnabled('bio') && isset($event->data['attributes']['bio'])) {
            $oldBio = $this->getUserAttribute($user, 'bio') ?? '';

            $user->afterSave(function ($user) use ($actor, $oldBio) {
                $this->queueAudit('user_bio', $user->id, $actor?->id, $user->id, [
                    'bio' => $user->bio ?? '',
                    'oldBio' => $oldBio,
                ]);
            });
        }

        // Profile cover changes (forumaker/profile-cover)
        if ($this->isEnabled('cover')) {
            foreach (['cover', 'cover_url', 'profile_cover'] as $coverKey) {
                if (isset($event->data['attributes'][$coverKey])) {
                    $coverChanges = ['cover' => $event->data['attributes'][$coverKey]];

                    $user->afterSave(function ($user) use ($actor, $coverChanges) {
                        $this->queueAudit('user_cover', $user->id, $actor?->id, $user->id, $coverChanges);
                    });
                    break;
                }
            }
        }

        // Avatar changes fallback (if AvatarSaving event doesn't fire)
        if ($this->isEnabled('avatar') && isset($event->data['attributes']['avatarUrl'])) {
            $oldAvatarUrl = $this->getUserAttribute($user, 'avatar_url');

            $user->afterSave(function ($user) use ($actor, $oldAvatarUrl) {
                $newAvatarUrl = $this->getUserAttribute($user, 'avatar_url');
                if ($oldAvatarUrl === $newAvatarUrl) return;

                $this->queueAudit('user_avatar', $user->id, $actor?->id, $user->id, [
                    'oldAvatarUrl' => $oldAvatarUrl,
                    'newAvatarUrl' => $newAvatarUrl,
                ]);
            });
        }

        if ($isNew) {
            // For new users, the username audit is already queued above
            return;
        }
    }

    // ================================================================
    //  USER AVATAR (core)
    // ================================================================

    public function onAvatarSaving(AvatarSaving $event): void
    {
        if (!$this->isEnabled('avatar')) return;

        $user = $event->user;
        $actor = $event->actor;

        if ($this->canBypass($actor)) return;

        $oldAvatarUrl = $this->getUserAttribute($user, 'avatar_url');

        $user->afterSave(function ($user) use ($actor, $oldAvatarUrl) {
            $newAvatarUrl = $this->getUserAttribute($user, 'avatar_url');

            if ($oldAvatarUrl === $newAvatarUrl) return;

            $this->queueAudit('user_avatar', $user->id, $actor?->id, $user->id, [
                'oldAvatarUrl' => $oldAvatarUrl,
                'newAvatarUrl' => $newAvatarUrl,
            ]);
        });
    }

    // ================================================================
    //  FILE UPLOADS (fof/upload)
    // ================================================================

    public function onFileWillBeUploaded(object $event): void
    {
        if (!$this->isEnabled('upload')) return;

        // fof/upload FileWillBeUploaded event - queue audit before file is saved
        // The event has: $event->actor, $event->file (instance of FoF\Upload\File)
        $actor = $event->actor ?? null;
        $file = $event->file ?? null;

        if (!$file || !method_exists($file, 'post') || !$file->post) return;
        if ($this->canBypass($actor)) return;

        $post = $file->post;

        $this->queueAudit('upload_file', $post->id, $actor?->id, $post->user_id, [
            'file_name' => method_exists($file, 'getDisplayName') ? $file->getDisplayName() : 'unknown',
        ]);
    }

    public function onFileUploaded(object $event): void
    {
        if (!$this->isEnabled('upload')) return;

        // Alternative: fof/upload FileWasUploaded event
        $actor = $event->actor ?? null;
        $file = $event->file ?? null;

        if (!$file || !method_exists($file, 'post') || !$file->post) return;
        if ($this->canBypass($actor)) return;

        $post = $file->post;

        $this->queueAudit('upload_file', $post->id, $actor?->id, $post->user_id, [
            'file_name' => method_exists($file, 'getDisplayName') ? $file->getDisplayName() : 'unknown',
        ]);
    }

    // ================================================================
    //  HELPERS
    // ================================================================

    private function queueAudit(string $subjectType, ?int $subjectId, ?int $actorId, ?int $ownerId, array $changes): void
    {
        $log = new AuditLog([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'actor_id' => $actorId,
            'owner_id' => $ownerId,
            'status' => 'pending',
            'retry_count' => 0,
        ]);
        $log->save();

        $this->queue->push(new AuditJob(
            $subjectType,
            $subjectId,
            $actorId,
            $ownerId,
            $changes,
            $log->id
        ));
    }

    private function isEnabled(string $type): bool
    {
        $keyMap = [
            'username' => 'zephyrisle.ai-audit.enable_username_audit',
            'avatar' => 'zephyrisle.ai-audit.enable_avatar_audit',
            'nickname' => 'zephyrisle.ai-audit.enable_nickname_audit',
            'bio' => 'zephyrisle.ai-audit.enable_bio_audit',
            'cover' => 'zephyrisle.ai-audit.enable_cover_audit',
            'post_content' => 'zephyrisle.ai-audit.enable_post_content_audit',
            'post_image' => 'zephyrisle.ai-audit.enable_post_image_audit',
            'discussion_title' => 'zephyrisle.ai-audit.enable_discussion_title_audit',
            'upload' => 'zephyrisle.ai-audit.enable_upload_audit',
        ];

        $key = $keyMap[$type] ?? null;
        if ($key === null) return true; // unknown types default to enabled

        return (bool) $this->settings->get($key, true);
    }

    private function hasImageUrls(string $content): bool
    {
        if (preg_match('/<img\s+[^>]*src=["\']([^"\']+)["\']/i', $content)) return true;
        if (preg_match('/!\[[^\]]*\]\(([^)]+)\)/', $content)) return true;
        return false;
    }

    private function setUnapproved($model): void
    {
        try {
            if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'is_approved')) {
                $model->setAttribute('is_approved', false);
            }
        } catch (\Exception) {
            // ignore
        }
    }

    private function getUserAttribute(User $user, string $key): mixed
    {
        try {
            if (method_exists($user, 'getRawOriginal')) {
                return $user->getRawOriginal($key);
            }
            if (method_exists($user, 'getOriginal') && $user->getOriginal($key) !== null) {
                return $user->getOriginal($key);
            }
            return $user->$key ?? null;
        } catch (\Exception) {
            return null;
        }
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
}
