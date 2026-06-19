<?php

namespace ZephyrIsle\AiAudit\Model;

use Flarum\Database\AbstractModel;

/**
 * @property int $id
 * @property string $subject_type
 * @property int|null $subject_id
 * @property int|null $actor_id
 * @property int|null $owner_id
 * @property string $status
 * @property float|null $risk
 * @property int $severity
 * @property array|null $actions
 * @property string|null $conclusion
 * @property array|null $snapshot
 * @property array|null $analysis
 * @property string|null $error
 * @property int $retry_count
 */
class AuditLog extends AbstractModel
{
    protected $table = 'zia_ai_audit_logs';

    protected $casts = [
        'risk' => 'decimal:4',
        'severity' => 'integer',
        'actions' => 'array',
        'snapshot' => 'array',
        'analysis' => 'array',
        'retry_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'subject_type',
        'subject_id',
        'actor_id',
        'owner_id',
        'status',
        'risk',
        'severity',
        'actions',
        'conclusion',
        'snapshot',
        'analysis',
        'error',
        'retry_count',
    ];

    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    public function markFailed(string $message): void
    {
        $this->status = 'failed';
        $this->error = $message;
        $this->save();
    }

    public function markRetrying(): void
    {
        $this->status = 'retrying';
        $this->retry_count = (int) $this->retry_count + 1;
        $this->save();
    }
}
