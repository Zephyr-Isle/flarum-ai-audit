<?php

namespace ZephyrIsle\AiAudit\Job;

use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Psr\Log\LoggerInterface;

class SuicideEchoJob extends AbstractJob
{
    public $tries = 2;
    public $backoff = 30;

    public function __construct(
        public int $ownerId,
        public ?int $actorId,
        public string $conclusion
    ) {
    }

    public function handle(LoggerInterface $logger): void
    {
        if (!class_exists('Flarum\Messages\DialogMessage')) {
            $logger->info('[AI Audit] SuicideEchoJob skipped: flarum/messages not installed');
            return;
        }

        try {
            $owner = User::find($this->ownerId);
            if (!$owner) {
                $logger->warning('[AI Audit] SuicideEchoJob: owner not found', ['owner_id' => $this->ownerId]);
                return;
            }

            $systemUser = User::find(1);
            if (!$systemUser) {
                $logger->warning('[AI Audit] SuicideEchoJob: system user (ID 1) not found');
                return;
            }

            $dialog = new \Flarum\Messages\Dialog();
            $dialog->type = 'direct';
            $dialog->save();
            $dialog->users()->attach([$systemUser->id, $owner->id]);

            $message = new \Flarum\Messages\DialogMessage();
            $message->dialog_id = $dialog->id;
            $message->user_id = $systemUser->id;
            $message->content = $this->buildEchoMessage($owner->display_name ?? $owner->username);
            $message->save();

            $dialog->setFirstMessage($message);
            $dialog->setLastMessage($message);
            $dialog->save();

            $logger->info('[AI Audit] suicide echo sent', ['owner_id' => $owner->id, 'dialog_id' => $dialog->id]);
        } catch (\Exception $e) {
            $logger->error('[AI Audit] SuicideEchoJob failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function buildEchoMessage(string $username): string
    {
        return "送你一朵小红花 🌺\n\n嗨，{$username}。刚才看到你在论坛的发言。我不知道你经历了什么，但我想告诉你，你的感受值得被看见。如果今天很难熬，给自己一个拥抱。\n\n一切都会好起来的。";
    }
}
