<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;

class ListAuditLogsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = $request->getAttribute('actor');
        $actor->assertCan('zephyrisle-ai-audit.viewAuditLogs');

        $params = $request->getQueryParams();
        $filters = Arr::get($params, 'filter', []);
        $sort = Arr::get($params, 'sort', '-createdAt');
        $limit = (int) Arr::get($params, 'page.limit', 20);
        $offset = (int) Arr::get($params, 'page.offset', 0);

        $q = AuditLog::query();

        if (!empty($filters['subjectType'])) {
            $q->where('subject_type', $filters['subjectType']);
        }
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (!empty($filters['ownerId'])) {
            $q->where('owner_id', (int) $filters['ownerId']);
        }
        if (!empty($filters['minRisk'])) {
            $q->where('risk', '>=', (float) $filters['minRisk']);
        }

        $sortField = ltrim((string) $sort, '-+');
        $direction = str_starts_with((string) $sort, '-') ? 'desc' : 'asc';
        $map = [
            'createdAt' => 'created_at',
            'risk' => 'risk',
            'status' => 'status',
        ];
        if (isset($map[$sortField])) {
            $q->orderBy($map[$sortField], $direction);
        }

        $total = $q->count();
        $rows = $q->skip($offset)->take($limit)->get();

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

