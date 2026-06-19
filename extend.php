<?php

namespace ZephyrIsle\AiAudit;

use Flarum\Extend;
use ZephyrIsle\AiAudit\Access\AuditLogPolicy;
use ZephyrIsle\AiAudit\Api\Controller\ListAuditLogsController;
use ZephyrIsle\AiAudit\Api\Controller\RetryAuditController;
use ZephyrIsle\AiAudit\Api\Controller\ShowAuditLogController;
use ZephyrIsle\AiAudit\Listener\QueueAudit;
use ZephyrIsle\AiAudit\Model\AuditLog;
use ZephyrIsle\AiAudit\Provider\AiAuditServiceProvider;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ServiceProvider())
        ->register(AiAuditServiceProvider::class),

    (new Extend\Event())
        ->subscribe(QueueAudit::class),

    (new Extend\Policy())
        ->modelPolicy(AuditLog::class, AuditLogPolicy::class),

    (new Extend\Routes('api'))
        ->get('/ai-audit/logs', 'zephyrisle-ai-audit.logs.index', ListAuditLogsController::class)
        ->get('/ai-audit/logs/{id}', 'zephyrisle-ai-audit.logs.show', ShowAuditLogController::class)
        ->post('/ai-audit/logs/{id}/retry', 'zephyrisle-ai-audit.logs.retry', RetryAuditController::class),

    (new Extend\Settings())
        ->default('zephyrisle.ai-audit.api_endpoint', 'https://api.openai.com/v1')
        ->default('zephyrisle.ai-audit.api_key', '')
        ->default('zephyrisle.ai-audit.model', 'gpt-4o-mini')
        ->default('zephyrisle.ai-audit.temperature', 0.2)
        ->default('zephyrisle.ai-audit.max_tokens', 800)
        ->default('zephyrisle.ai-audit.timeout', 30)
        ->default('zephyrisle.ai-audit.system_prompt', '')
        ->default('zephyrisle.ai-audit.pre_approve_enabled', false)
        ->default('zephyrisle.ai-audit.download_images', true)
        ->default('zephyrisle.ai-audit.review_threshold', 0.55)
        ->default('zephyrisle.ai-audit.action_threshold', 0.75)
        ->default('zephyrisle.ai-audit.suspend_days', 7)
        ->serializeToForum('zephyrisle-ai-audit.preApproveEnabled', 'zephyrisle.ai-audit.pre_approve_enabled', 'boolval'),
];
