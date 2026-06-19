<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Illuminate\Contracts\Queue\Queue;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZephyrIsle\AiAudit\Job\AuditJob;
use ZephyrIsle\AiAudit\Model\AuditLog;
use ZephyrIsle\AiAudit\Support\RequestActor;

class RetryAuditController implements RequestHandlerInterface
{
    public function __construct(private Queue $queue)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestActor::require($request, 'zephyrisle-ai-audit.retryAudit');

        $id = $request->getAttribute('id');
        $log = AuditLog::findOrFail($id);

        if (in_array($log->status, ['pending', 'retrying'], true)) {
            return new JsonResponse([
                'errors' => [[
                    'status' => '409',
                    'code' => 'audit_log_retry_conflict',
                    'detail' => 'Audit is already queued or running.',
                ]],
            ], 409);
        }

        $log->markRetrying();

        $changes = $log->analysis['job']['changes'] ?? [];
        if (!is_array($changes)) $changes = [];

        $this->queue->push(new AuditJob(
            $log->subject_type,
            $log->subject_id,
            $actor->id,
            $log->owner_id,
            $changes,
            $log->id
        ));

        return new JsonResponse([
            'data' => [
                'type' => 'ai-audit-logs',
                'id' => (string) $log->id,
                'attributes' => [
                    'status' => $log->status,
                    'retryCount' => (int) $log->retry_count,
                ],
            ],
        ]);
    }
}
