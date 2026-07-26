<?php

namespace ZephyrIsle\AiAudit\Service;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Locale\Translator;
use Flarum\Post\Post;
use Psr\Log\LoggerInterface;
use ZephyrIsle\AiAudit\Model\AuditLog;

class Flagger
{
    public function __construct(
        private Translator $translator,
        private LoggerInterface $logger
    ) {
    }

    public function flagForReview($content, ?AuditLog $log): ?object
    {
        $flagClass = 'Flarum\\Flags\\Flag';
        if (!class_exists($flagClass)) {
            return null;
        }

        $post = $this->resolvePost($content);
        if (!$post) return null;

        $existing = $flagClass::where('post_id', $post->id)->where('type', 'ai-audit')->first();
        if ($existing) return $existing;

        try {
            $flag = new $flagClass();
            $flag->setAttribute('post_id', $post->id);
            $flag->setAttribute('type', 'ai-audit');
            $flag->setAttribute('user_id', null);
            $flag->setAttribute('created_at', Carbon::now());

            $flag->setAttribute('reason', $this->translator->trans('zephyrisle-ai-audit.flags.reason'));
            $detail = $this->translator->trans('zephyrisle-ai-audit.flags.detail_review');
            if ($log) {
                $detail = $detail . ' #' . $log->id;
            }
            $flag->setAttribute('reason_detail', $detail);
            $flag->save();
            return $flag;
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] failed to create flag', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function resolvePost($content): ?Post
    {
        if ($content instanceof Post) return $content;
        if ($content instanceof Discussion) return $content->firstPost ?? $content->posts()->where('number', 1)->first();
        return null;
    }
}
