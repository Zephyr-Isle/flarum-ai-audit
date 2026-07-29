<?php

namespace ZephyrIsle\AiAudit\Service;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Messages\DialogMessage;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Psr\Log\LoggerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;
use ZephyrIsle\AiAudit\Notification\AuditNotificationBlueprint;
use ZephyrIsle\AiAudit\Notification\SuicideSelfAlertBlueprint;

class DecisionApplier
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private LoggerInterface $logger,
        private Flagger $flagger,
        private NotificationSyncer $notifications
    ) {
    }

    public function apply(AuditLog $log, User $owner, $subject, ?User $actor = null): void
    {
        $actions = is_array($log->actions) ? $log->actions : [];

        // If only 'review' or 'suicide_alert' - flag for manual review, no auto-action
        $hasAutoAction = false;
        foreach (['hide', 'delete', 'suspend', 'rename', 'delete_avatar', 'reset_nickname', 'reset_bio', 'delete_cover'] as $a) {
            if (in_array($a, $actions, true)) {
                $hasAutoAction = true;
                break;
            }
        }

        if (!$hasAutoAction && (in_array('review', $actions, true) || in_array('suicide_alert', $actions, true))) {
            if (in_array('suicide_alert', $actions, true)) {
                $this->handleSuicideAlert($subject, $log);
            }
            if (in_array('review', $actions, true)) {
                $this->flagger->flagForReview($subject, $log);
            }
            return;
        }

        // Apply actions in order
        foreach ($actions as $action) {
            match ($action) {
                'hide' => $this->hideSubject($subject),
                'delete' => $this->deleteSubject($subject),
                'suspend' => $this->suspendOwner($owner, $log),
                'rename' => $this->renameUser($owner),
                'delete_avatar' => $this->deleteUserAvatar($owner),
                'reset_nickname' => $this->resetUserNickname($owner),
                'reset_bio' => $this->resetUserBio($owner),
                'delete_cover' => $this->deleteUserCover($owner),
                'flag' => $this->flagger->flagForReview($subject, $log),
                'suicide_alert' => $this->handleSuicideAlert($subject, $log),
                default => null,
            };
        }

        // Always flag for review if hide was applied
        if (in_array('hide', $actions, true)) {
            $this->flagger->flagForReview($subject, $log);
        }
    }

    /**
     * Hide a post, discussion, or dialog message.
     */
    private function hideSubject($subject): void
    {
        if ($subject instanceof DialogMessage) {
            try {
                $subject->setAttribute('content', '');
                $subject->setAttribute('user_id', null);
                $subject->save();
                $this->logger->info('[AI Audit] hidden dialog message', ['id' => $subject->id]);
            } catch (\Exception $e) {
                $this->logger->warning('[AI Audit] failed to hide dialog message', ['error' => $e->getMessage()]);
            }
            return;
        }

        if (($subject instanceof Post || $subject instanceof Discussion) && $this->supportsApproval($subject)) {
            try {
                $subject->setAttribute('is_approved', false);
                $subject->save();
                $this->logger->info('[AI Audit] hidden subject', [
                    'type' => $subject instanceof Post ? 'post' : 'discussion',
                    'id' => $subject->id,
                ]);
            } catch (\Exception $e) {
                $this->logger->warning('[AI Audit] failed to hide subject', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Delete a subject entirely (used for dialog messages).
     */
    private function deleteSubject($subject): void
    {
        if ($subject instanceof DialogMessage) {
            try {
                $subject->delete();
                $this->logger->info('[AI Audit] deleted dialog message', ['id' => $subject->id]);
            } catch (\Exception $e) {
                $this->logger->warning('[AI Audit] failed to delete dialog message', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Suspend a user for a configured number of days.
     */
    private function suspendOwner(User $owner, AuditLog $log): void
    {
        if (!method_exists($owner, 'getAttribute') || !$owner->getConnection()->getSchemaBuilder()->hasColumn($owner->getTable(), 'suspended_until')) {
            $this->logger->warning('[AI Audit] suspend not available - flarum/suspend not installed');
            return;
        }

        $days = (int) $this->settings->get('zephyrisle.ai-audit.suspend_days', 7);
        try {
            $owner->setAttribute('suspended_until', Carbon::now()->addDays(max(1, $days)));
            $owner->setAttribute('suspend_reason', $log->conclusion ?? '违反社区规范');
            $owner->setAttribute('suspend_message', $log->conclusion ?? '您的账号因违反社区规范已被暂时封禁。');

            if (method_exists($owner, 'save')) {
                $owner->save();
                $this->logger->info('[AI Audit] suspended user', [
                    'user_id' => $owner->id,
                    'days' => $days,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to suspend user', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Rename a user to "user{id}_random" format for severe violations.
     */
    private function renameUser(User $user): void
    {
        try {
            $suffix = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6);
            $newUsername = 'user' . $user->id . '_' . $suffix;

            // Check if username is available
            $existing = User::where('username', $newUsername)->where('id', '!=', $user->id)->first();
            if ($existing) {
                $newUsername = 'user' . $user->id . '_' . substr(md5(uniqid()), 0, 8);
            }

            $user->setAttribute('username', $newUsername);
            $user->save();

            $this->logger->info('[AI Audit] renamed user', [
                'user_id' => $user->id,
                'new_username' => $newUsername,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to rename user', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a user's avatar.
     */
    private function deleteUserAvatar(User $user): void
    {
        try {
            if (method_exists($user, 'deleteAvatar')) {
                $user->deleteAvatar();
            } elseif (method_exists($user, 'setAttribute')) {
                $user->setAttribute('avatar_url', null);
                $user->save();
            }

            $this->logger->info('[AI Audit] deleted avatar for user', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to delete avatar', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reset user's nickname to empty string (flarum/nicknames).
     */
    private function resetUserNickname(User $user): void
    {
        try {
            if ($this->hasColumn($user, 'nickname')) {
                $user->setAttribute('nickname', '');
                $user->save();
                $this->logger->info('[AI Audit] reset nickname for user', ['user_id' => $user->id]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to reset nickname', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reset user's bio to a default message (fof/user-bio).
     */
    private function resetUserBio(User $user): void
    {
        try {
            if ($this->hasColumn($user, 'bio')) {
                $user->setAttribute('bio', '');
                $user->save();
                $this->logger->info('[AI Audit] reset bio for user', ['user_id' => $user->id]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to reset bio', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete user's profile cover (forumaker/profile-cover).
     */
    private function deleteUserCover(User $user): void
    {
        try {
            // Try common cover column names
            foreach (['cover', 'cover_url', 'profile_cover', 'cover_image'] as $col) {
                if ($this->hasColumn($user, $col)) {
                    $user->setAttribute($col, null);
                }
            }
            $user->save();
            $this->logger->info('[AI Audit] deleted cover for user', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to delete cover', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle a suicide/self-harm alert - flag for admin intervention without punishing the user.
     */
    private function handleSuicideAlert($subject, AuditLog $log): void
    {
        $flag = $this->flagger->flagForReview($subject, $log);
        if ($flag) {
            try {
                $flag->setAttribute('type', 'suicide_alert');
                $flag->setAttribute('reason', '检测到自杀/自残倾向 - 请管理员及时介入');
                $flag->save();
            } catch (\Exception $e) {
                $this->logger->warning('[AI Audit] failed to update suicide alert flag', ['error' => $e->getMessage()]);
            }
        }

        $this->logger->critical('[AI Audit] SUICIDE ALERT detected', [
            'log_id' => $log->id,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'owner_id' => $log->owner_id,
            'conclusion' => $log->conclusion,
        ]);

        // 1. Send notification to the affected user (triggers frontend modal)
        try {
            $owner = $log->owner_id ? User::find($log->owner_id) : null;
            if ($owner) {
                $selfBlueprint = new SuicideSelfAlertBlueprint($log, $owner);
                $this->notifications->sync($selfBlueprint, [$owner]);
                $this->logger->info('[AI Audit] suicide self-alert notification sent', ['user_id' => $owner->id]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to send self-alert notification', ['error' => $e->getMessage()]);
        }

        // 2. Notify all users in admin group or with view_audit_logs permission
        try {
            $adminGroupId = defined(Group::class . '::ADMINISTRATOR_ID') ? Group::ADMINISTRATOR_ID : 1;
            $admins = User::whereHas('groups', function ($q) use ($adminGroupId) {
                $q->where('group_id', $adminGroupId)
                  ->orWhereIn('group_id', function ($sub) {
                      $sub->select('group_id')
                          ->from('group_permission')
                          ->where('permission', 'zephyrisle-ai-audit.viewAuditLogs');
                  });
            })->get();

            if ($admins->isNotEmpty()) {
                $adminBlueprint = new AuditNotificationBlueprint($log, $admins->first());
                $this->notifications->sync($adminBlueprint, $admins->all());
                $this->logger->info('[AI Audit] admin suicide alert sent', ['admin_count' => $admins->count()]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to send admin notification', ['error' => $e->getMessage()]);
        }

    }

    private function supportsApproval($model): bool
    {
        try {
            if (!is_object($model) || !method_exists($model, 'getConnection') || !method_exists($model, 'getTable')) {
                return false;
            }
            return $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'is_approved');
        } catch (\Exception) {
            return false;
        }
    }

    private function hasColumn($model, string $column): bool
    {
        try {
            return $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $column);
        } catch (\Exception) {
            return false;
        }
    }
}
