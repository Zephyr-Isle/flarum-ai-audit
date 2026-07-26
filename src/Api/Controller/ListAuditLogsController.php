<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;
use ZephyrIsle\AiAudit\Support\AuditLogListQuery;
use ZephyrIsle\AiAudit\Support\RequestActor;

class ListAuditLogsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestActor::getActor($request);

        if (!$actor) {
            return RequestActor::notAuthenticatedResponse();
        }

        if (!$actor->can('zephyrisle-ai-audit.viewAuditLogs')) {
            return RequestActor::permissionDeniedResponse();
        }

        $query = AuditLogListQuery::fromArray($request->getQueryParams());

        $q = AuditLog::query();

        if (is_array($query->filters) && isset($query->filters['subjectType'])) {
            $q->where('subject_type', $query->filters['subjectType']);
        }
        if (is_array($query->filters) && isset($query->filters['status'])) {
            $q->where('status', $query->filters['status']);
        }
        if (is_array($query->filters) && isset($query->filters['ownerId'])) {
            $q->where('owner_id', $query->filters['ownerId']);
        }
        if (is_array($query->filters) && isset($query->filters['minRisk'])) {
            $q->where('risk', '>=', $query->filters['minRisk']);
        }

        $map = [
            'createdAt' => 'created_at',
            'risk' => 'risk',
            'status' => 'status',
        ];
        if (isset($map[$query->sort])) {
            $q->orderBy($map[$query->sort], $query->direction);
        }

        $total = (clone $q)->count();
        $rows = $q->skip($query->offset)->take($query->limit)->get();

        $data = $rows->map(function (AuditLog $log) use ($actor) {
            return $this->serialize($log, $actor);
        })->toArray();

        return new JsonResponse(['data' => $data, 'meta' => ['total' => $total]]);
    }

    private function serialize(AuditLog $log, $actor): array
    {
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

        return [
            'type' => 'ai-audit-logs',
            'id' => (string) $log->id,
            'attributes' => $attrs,
        ];
    }
}
