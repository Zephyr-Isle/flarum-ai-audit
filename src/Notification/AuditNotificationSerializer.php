<?php

namespace ZephyrIsle\AiAudit\Notification;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Notification\Blueprint\BlueprintInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;

class AuditNotificationSerializer extends AbstractSerializer
{
    protected string $type = 'ai-audit-notifications';

    protected function getDefaultAttributes(BlueprintInterface|AuditLog $model): array
    {
        if ($model instanceof AuditLog) {
            return [
                'subjectType' => $model->subject_type,
                'subjectId' => $model->subject_id,
                'conclusion' => $model->conclusion,
                'actions' => $model->actions,
                'risk' => $model->risk,
                'severity' => $model->severity,
                'logId' => $model->id,
                'createdAt' => $model->created_at?->toIso8601String(),
            ];
        }

        $data = $model->getData();
        return [
            'subjectType' => $data['subjectType'] ?? '',
            'subjectId' => $data['subjectId'] ?? null,
            'conclusion' => $data['conclusion'] ?? '',
            'actions' => $data['actions'] ?? [],
            'risk' => $data['risk'] ?? null,
            'severity' => $data['severity'] ?? 0,
            'logId' => $data['logId'] ?? null,
            'createdAt' => null,
        ];
    }
}
