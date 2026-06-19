<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Illuminate\Contracts\Queue\Queue;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZephyrIsle\AiAudit\Job\AuditJob;
use ZephyrIsle\AiAudit\Model\AuditLog;

class RetryAuditController implements RequestHandlerInterface
{
    public function __construct(private Queue $queue)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = $request->getAttribute('actor');
        $actor->assertCan('zephyrisle-ai-audit.retryAudit');

        $id = $request->getAttribute('id');
        $log = AuditLog::findOrFail($id);
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
