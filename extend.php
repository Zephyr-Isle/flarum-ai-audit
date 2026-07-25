<?php

namespace ZephyrIsle\AiAudit;

use Flarum\Extend;
use ZephyrIsle\AiAudit\Access\AuditLogPolicy;
use ZephyrIsle\AiAudit\Api\Controller\ListAuditLogsController;
use ZephyrIsle\AiAudit\Api\Controller\RetryAuditController;
use ZephyrIsle\AiAudit\Api\Controller\ShowAuditLogController;
use ZephyrIsle\AiAudit\Listener\QueueAudit;
use ZephyrIsle\AiAudit\Model\AuditLog;
use ZephyrIsle\AiAudit\Notification\AuditNotificationBlueprint;
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

    // Event listeners for all content types
    (new Extend\Event())
        ->subscribe(QueueAudit::class),

    // Permission policy
    (new Extend\Policy())
        ->modelPolicy(AuditLog::class, AuditLogPolicy::class),

    // Notification type registration
    // Flarum v2 signature: type(string $blueprintClass, array $driversEnabledByDefault = ['alert'])
    (new Extend\Notification())
        ->type(
            AuditNotificationBlueprint::class,
            ['alert', 'email']
        ),

    // API routes
    (new Extend\Routes('api'))
        ->get('/ai-audit/logs', 'zephyrisle-ai-audit.logs.index', ListAuditLogsController::class)
        ->get('/ai-audit/logs/{id}', 'zephyrisle-ai-audit.logs.show', ShowAuditLogController::class)
        ->post('/ai-audit/logs/{id}/retry', 'zephyrisle-ai-audit.logs.retry', RetryAuditController::class),

    // Default settings
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
        ->default('zephyrisle.ai-audit.image_download_timeout', 8)
        ->default('zephyrisle.ai-audit.enable_username_audit', true)
        ->default('zephyrisle.ai-audit.enable_avatar_audit', true)
        ->default('zephyrisle.ai-audit.enable_nickname_audit', true)
        ->default('zephyrisle.ai-audit.enable_bio_audit', true)
        ->default('zephyrisle.ai-audit.enable_cover_audit', true)
        ->default('zephyrisle.ai-audit.enable_post_content_audit', true)
        ->default('zephyrisle.ai-audit.enable_post_image_audit', true)
        ->default('zephyrisle.ai-audit.enable_discussion_title_audit', true)
        ->default('zephyrisle.ai-audit.enable_upload_audit', true)
        ->default('zephyrisle.ai-audit.enable_notifications', true)
        ->default('zephyrisle.ai-audit.enable_context', true)
        ->default('zephyrisle.ai-audit.use_json_schema', true)
        ->serializeToForum('zephyrisle-ai-audit.preApproveEnabled', 'zephyrisle.ai-audit.pre_approve_enabled', 'boolval'),
];
