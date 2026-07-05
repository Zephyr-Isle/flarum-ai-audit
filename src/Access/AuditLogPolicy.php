<?php

namespace ZephyrIsle\AiAudit\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class AuditLogPolicy extends AbstractPolicy
{
    public function can(User $actor, string $ability): ?int
    {
        // Flarum's policy system automatically grants admins all permissions
        // We only need to check explicit permissions for non-admin users
        if ($actor->isAdmin()) {
            return $this->allow();
        }

        return match ($ability) {
            'zephyrisle-ai-audit.viewAuditLogs',
            'zephyrisle-ai-audit.viewFullAuditLogs',
            'zephyrisle-ai-audit.retryAudit' => $actor->hasPermission($ability) ? $this->allow() : null,
            default => null,
        };
    }
}
