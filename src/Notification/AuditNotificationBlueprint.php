<?php

namespace ZephyrIsle\AiAudit\Notification;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use ZephyrIsle\AiAudit\Model\AuditLog;

class AuditNotificationBlueprint implements BlueprintInterface
{
    public function __construct(
        public AuditLog $auditLog,
        public User $recipient
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->auditLog;
    }

    public function getFromUser(): ?User
    {
        return null; // System notification
    }

    public function getData(): array
    {
        return [
            'subjectType' => $this->auditLog->subject_type,
            'subjectId' => $this->auditLog->subject_id,
            'conclusion' => $this->auditLog->conclusion,
            'actions' => $this->auditLog->actions,
            'risk' => $this->auditLog->risk,
            'severity' => $this->auditLog->severity,
            'logId' => $this->auditLog->id,
        ];
    }

    public static function getType(): string
    {
        return 'aiAuditNotification';
    }

    public static function getSubjectModel(): string
    {
        return AuditLog::class;
    }

    public function getRecipients(): array
    {
        return [$this->recipient];
    }

    public static function getDescription(): string
    {
        return 'AI Audit Notification';
    }
}
