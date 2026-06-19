<?php

namespace ZephyrIsle\AiAudit\Job;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Log\LoggerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;
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
            $owner = $this->ownerId ? User::findOrFail($this->ownerId) : ($subject instanceof User ? $subject : null);
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

            $applier->apply($log, $owner, $subject);
        } catch (ModelNotFoundException $e) {
            $log->markFailed('subject_not_found');
            return;
        } catch (\Exception $e) {
            $logger->error('[AI Audit] job failed', ['error' => $e->getMessage()]);
            $log->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function loadSubject()
    {
        return match ($this->subjectType) {
            'post' => Post::findOrFail($this->subjectId),
            'discussion' => Discussion::findOrFail($this->subjectId),
            'user' => User::findOrFail($this->subjectId),
            default => throw new \RuntimeException('unknown_subject_type'),
        };
    }

    private function buildSnapshot($subject, SnapshotBuilder $snapshots): array
    {
        if ($subject instanceof Post) {
            return $snapshots->forPost($subject);
        }
        if ($subject instanceof Discussion) {
            return $snapshots->forDiscussion($subject);
        }
        if ($subject instanceof User) {
            return $snapshots->forUser($subject, $this->changes);
        }
        throw new \RuntimeException('unsupported_subject');
    }
}
