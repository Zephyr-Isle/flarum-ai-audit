<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;

class ShowAuditLogController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = $request->getAttribute('actor');
        $actor->assertCan('zephyrisle-ai-audit.viewAuditLogs');

        $id = $request->getAttribute('id');
        $log = AuditLog::findOrFail($id);

        $attrs = [
            'subjectType' => $log->subject_type,
            'subjectId' => $log->subject_id,
            'ownerId' => $log->owner_id,
            'actorId' => $log->actor_id,
            'status' => $log->status,
            'risk' => $log->risk ? (float) $log->risk : null,
            'severity' => (int) $log->severity,
            'actions' => $log->actions,
            'conclusion' => $log->conclusion,
            'retryCount' => (int) $log->retry_count,
            'createdAt' => $log->created_at?->toIso8601String(),
            'updatedAt' => $log->updated_at?->toIso8601String(),
        ];

        if ($actor->can('zephyrisle-ai-audit.viewFullAuditLogs')) {
            $attrs['snapshot'] = $log->snapshot;
            $attrs['analysis'] = $log->analysis;
            $attrs['error'] = $log->error;
        }

        return new JsonResponse([
            'data' => [
                'type' => 'ai-audit-logs',
                'id' => (string) $log->id,
                'attributes' => $attrs,
            ],
        ]);
    }
}

