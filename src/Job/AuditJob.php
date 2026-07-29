<?php

namespace ZephyrIsle\AiAudit\Job;

use Flarum\Discussion\Discussion;
use Flarum\Messages\DialogMessage;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Log\LoggerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;
use ZephyrIsle\AiAudit\Notification\AuditNotificationBlueprint;
use ZephyrIsle\AiAudit\Service\AuditClient;
use ZephyrIsle\AiAudit\Service\DecisionApplier;
use ZephyrIsle\AiAudit\Service\SnapshotBuilder;

class AuditJob extends AbstractJob
{
    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public string $subjectType,
        public ?int $subjectId,
        public ?int $actorId,
        public ?int $ownerId,
        public array $changes = [],
        public ?int $auditLogId = null
    ) {
    }

    public function handle(
        AuditClient $client,
        SnapshotBuilder $snapshots,
        DecisionApplier $applier,
        NotificationSyncer $notifications,
        LoggerInterface $logger
    ): void {
        $log = $this->auditLogId ? AuditLog::findOrFail($this->auditLogId) : new AuditLog();
        $log->subject_type = $this->subjectType;
        $log->subject_id = $this->subjectId;
        $log->actor_id = $this->actorId;
        $log->owner_id = $this->ownerId;
        $log->status = 'pending';
        $log->error = null;
        if (!$this->auditLogId) {
            $log->retry_count = 0;
        }
        $log->save();

        try {
            $subject = $this->loadSubject();
            $owner = $this->resolveOwner($subject);
            if (!$owner) {
                throw new \RuntimeException('owner_not_found');
            }

            $snapshot = $this->buildSnapshot($subject, $snapshots);
            $log->snapshot = $snapshot;
            $log->save();

            $analysis = $client->analyze($snapshot);
            $analysis['job'] = [
                'subjectType' => $this->subjectType,
                'subjectId' => $this->subjectId,
                'actorId' => $this->actorId,
                'ownerId' => $this->ownerId,
                'changes' => $this->changes,
                'auditLogId' => $log->id,
            ];
            $decision = $analysis['decision'] ?? [];

            $log->analysis = $analysis;
            $log->risk = (float) ($decision['risk'] ?? 0.0);
            $log->severity = (int) ($decision['severity'] ?? 0);
            $log->actions = $decision['actions'] ?? [];
            $log->conclusion = (string) ($decision['conclusion'] ?? '');
            $log->markCompleted();

            // Apply decisions
            $applier->apply($log, $owner, $subject);

            // Send notification to content owner if action was taken
            if (!in_array('none', $log->actions ?? [], true) && !empty($log->actions)) {
                try {
                    $blueprint = new AuditNotificationBlueprint($log, $owner);
                    $notifications->sync($blueprint, [$owner]);
                } catch (\Exception $e) {
                    $logger->warning('[AI Audit] notification failed', ['error' => $e->getMessage()]);
                }
            }
        } catch (ModelNotFoundException $e) {
            $this->markPermanentFailure($log, 'subject_not_found');
            return;
        } catch (\RuntimeException $e) {
            if ($this->isPermanentError($e->getMessage())) {
                $this->markPermanentFailure($log, $e->getMessage());
                return;
            }
            $logger->error('[AI Audit] job failed (temporary)', ['error' => $e->getMessage()]);
            $log->markFailed($e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $logger->error('[AI Audit] job failed (unknown)', ['error' => $e->getMessage()]);
            $log->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function isPermanentError(string $message): bool
    {
        $permanentErrors = [
            'owner_not_found',
            'unknown_subject_type',
            'unsupported_subject',
        ];

        foreach ($permanentErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }

        return false;
    }

    private function markPermanentFailure(AuditLog $log, string $message): void
    {
        $log->markFailed($message);
        $this->delete();
    }

    private function loadSubject()
    {
        return match (true) {
            in_array($this->subjectType, [
                'post_content', 'post_image', 'upload_file',
            ]) => Post::findOrFail($this->subjectId),
            in_array($this->subjectType, [
                'discussion_title',
            ]) => Discussion::findOrFail($this->subjectId),
            in_array($this->subjectType, [
                'user_username', 'user_avatar', 'user_nickname',
                'user_bio', 'user_cover',
            ]) => User::findOrFail($this->subjectId),
            $this->subjectType === 'dialog_message' => DialogMessage::findOrFail($this->subjectId),
            default => throw new \RuntimeException('unknown_subject_type'),
        };
    }

    private function resolveOwner($subject): ?User
    {
        if ($this->ownerId) {
            try {
                return User::findOrFail($this->ownerId);
            } catch (ModelNotFoundException) {
                return null;
            }
        }

        if ($subject instanceof User) {
            return $subject;
        }

        if ($subject instanceof Post) {
            return $subject->user;
        }

        if ($subject instanceof Discussion) {
            return $subject->user;
        }

        if ($subject instanceof DialogMessage) {
            return $subject->user;
        }

        return null;
    }

    private function buildSnapshot($subject, SnapshotBuilder $snapshots): array
    {
        return match (true) {
            $subject instanceof Post && $this->subjectType === 'post_image'
                => $snapshots->forPostImage($subject),
            $subject instanceof Post && $this->subjectType === 'upload_file'
                => $snapshots->forUploadedFile($subject),
            $subject instanceof Post
                => $snapshots->forPost($subject),
            $subject instanceof Discussion
                => $snapshots->forDiscussion($subject),
            $subject instanceof User => match ($this->subjectType) {
                'user_avatar' => $snapshots->forUserAvatar($subject, $this->changes),
                'user_nickname' => $snapshots->forUserNickname($subject, $this->changes),
                'user_bio' => $snapshots->forUserBio($subject, $this->changes),
                'user_cover' => $snapshots->forUserCover($subject, $this->changes),
                default => $snapshots->forUser($subject, $this->changes),
            },
            $subject instanceof DialogMessage => $snapshots->forDialogMessage($subject, $this->changes),
            default => throw new \RuntimeException('unsupported_subject'),
        };
    }
}
