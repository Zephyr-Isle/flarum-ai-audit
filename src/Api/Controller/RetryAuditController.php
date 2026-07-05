<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Queue\Queue;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use ZephyrIsle\AiAudit\Job\AuditJob;
use ZephyrIsle\AiAudit\Model\AuditLog;

class RetryAuditController extends AbstractCreateController
{
    public $serializer = 'ZephyrIsle\AiAudit\Api\Serializer\AuditLogSerializer';

    public function __construct(private Queue $queue)
    {
    }

    protected function data(ServerRequestInterface $request, Document $document): AuditLog
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();
        $actor->assertCan('zephyrisle-ai-audit.retryAudit');

        $id = $request->getAttribute('id');
        $log = AuditLog::findOrFail($id);

        if (in_array($log->status, ['pending', 'retrying'], true)) {
            throw new \Flarum\User\Exception\PermissionDeniedException('Audit is already queued or running.');
        }

        $log->markRetrying();

        $changes = $this->extractChanges($log);

        $this->queue->push(new AuditJob(
            $log->subject_type,
            $log->subject_id,
            $actor->id,
            $log->owner_id,
            $changes,
            $log->id
        ));

        return $log;
    }

    private function extractChanges(AuditLog $log): array
    {
        if (is_array($log->analysis) && isset($log->analysis['job']['changes'])) {
            $changes = $log->analysis['job']['changes'];
            if (is_array($changes)) {
                return $changes;
            }
        }

        return match ($log->subject_type) {
            'post' => ['content' => 'retry'],
            'discussion' => ['title' => 'retry'],
            'user' => ['username' => 'retry'],
            default => [],
        };
    }
}
