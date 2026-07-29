<?php

namespace ZephyrIsle\AiAudit\Notification;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use ZephyrIsle\AiAudit\Model\AuditLog;

class SuicideSelfAlertBlueprint implements BlueprintInterface, AlertableInterface
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
        return null;
    }

    public function getData(): array
    {
        return [
            'subjectType' => $this->auditLog->subject_type,
            'subjectId' => $this->auditLog->subject_id,
            'logId' => $this->auditLog->id,
        ];
    }

    public static function getType(): string
    {
        return 'suicideSelfAlert';
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
        return 'Suicide Self Alert';
    }
}
