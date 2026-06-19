<?php

namespace ZephyrIsle\AiAudit\Service;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Psr\Log\LoggerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;

class DecisionApplier
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private LoggerInterface $logger,
        private Flagger $flagger
    ) {
    }

    public function apply(AuditLog $log, User $owner, $subject): void
    {
        $actions = is_array($log->actions) ? $log->actions : [];

        if (in_array('review', $actions, true) && count(array_intersect($actions, ['hide', 'suspend'])) === 0) {
            $this->flagger->flagForReview($subject, $log);
            return;
        }

        foreach ($actions as $action) {
            if ($action === 'hide') {
                $this->hideSubject($subject);
                $this->flagger->flagForReview($subject, $log);
            }

            if ($action === 'suspend') {
                $this->suspendOwner($owner, $log);
            }
        }
    }

    private function hideSubject($subject): void
    {
        if (($subject instanceof Post || $subject instanceof Discussion) && $this->supportsApproval($subject)) {
            try {
                $subject->setAttribute('is_approved', false);
                $subject->save();
            } catch (\Exception $e) {
                $this->logger->warning('[AI Audit] failed to hide subject', ['error' => $e->getMessage()]);
            }
        }
    }

    private function suspendOwner(User $owner, AuditLog $log): void
    {
        $days = (int) $this->settings->get('zephyrisle.ai-audit.suspend_days', 7);
        try {
            $owner->suspended_until = Carbon::now()->addDays(max(1, $days));
            $owner->suspend_reason = $log->conclusion ?? '违反社区规范';
            $owner->suspend_message = $log->conclusion ?? '违反社区规范';
            $owner->save();
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to suspend user', ['error' => $e->getMessage()]);
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
}
