<?php

namespace ZephyrIsle\AiAudit\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class AuditLogPolicy extends AbstractPolicy
{
    public function can(User $actor, string $ability): ?int
    {
        if ($ability === 'zephyrisle-ai-audit.viewAuditLogs') {
            if ($actor->isAdmin() || $actor->hasPermission('zephyrisle-ai-audit.viewAuditLogs')) {
                return $this->allow();
            }
            return null;
        }

        if ($ability === 'zephyrisle-ai-audit.viewFullAuditLogs') {
            if ($actor->isAdmin() || $actor->hasPermission('zephyrisle-ai-audit.viewFullAuditLogs')) {
                return $this->allow();
            }
            return null;
        }

        if ($ability === 'zephyrisle-ai-audit.retryAudit') {
            if ($actor->isAdmin() || $actor->hasPermission('zephyrisle-ai-audit.retryAudit')) {
                return $this->allow();
            }
            return null;
        }

        return null;
    }
}
