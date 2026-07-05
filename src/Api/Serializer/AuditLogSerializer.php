<?php

namespace ZephyrIsle\AiAudit\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use InvalidArgumentException;
use ZephyrIsle\AiAudit\Model\AuditLog;

class AuditLogSerializer extends AbstractSerializer
{
    protected $type = 'ai-audit-logs';

    protected function getDefaultAttributes($log): array
    {
        if (!($log instanceof AuditLog)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . AuditLog::class
            );
        }

        $actor = $this->actor;

        $attributes = [
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
            $attributes['snapshot'] = $log->snapshot;
            $attributes['analysis'] = $log->analysis;
            $attributes['error'] = $log->error;
        }

        return $attributes;
    }
}
