import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin()
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_endpoint',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.api_endpoint', {}, true),
        type: 'text',
        default: 'https://api.openai.com/v1',
      }),
      100
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_key',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.api_key', {}, true),
        type: 'text',
      }),
      99
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.model',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.model', {}, true),
        type: 'text',
        default: 'gpt-4o-mini',
      }),
      98
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.temperature',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.temperature', {}, true),
        type: 'number',
        min: 0,
        max: 2,
        step: 0.1,
        default: 0.2,
      }),
      97
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.max_tokens',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.max_tokens', {}, true),
        type: 'number',
        min: 1,
        max: 4096,
        default: 800,
      }),
      96
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.timeout',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.timeout', {}, true),
        type: 'number',
        min: 1,
        max: 300,
        default: 30,
      }),
      95
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.system_prompt',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.system_prompt', {}, true),
        type: 'textarea',
      }),
      94
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.pre_approve_enabled',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.pre_approve_enabled', {}, true),
        type: 'boolean',
      }),
      93
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.download_images',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.download_images', {}, true),
        type: 'boolean',
        default: true,
      }),
      92
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.image_download_timeout',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.image_download_timeout', {}, true),
        type: 'number',
        min: 1,
        max: 30,
        default: 8,
      }),
      91
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.review_threshold',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.review_threshold', {}, true),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
        default: 0.55,
      }),
      91
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.action_threshold',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.action_threshold', {}, true),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
        default: 0.75,
      }),
      90
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.suspend_days',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.suspend_days', {}, true),
        type: 'number',
        min: 1,
        max: 365,
        default: 7,
      }),
      89
    )
    .permission(
      () => ({
        icon: 'fas fa-shield-alt',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.view_audit_logs', {}, true),
        permission: 'zephyrisle-ai-audit.viewAuditLogs',
      }),
      'moderate',
      100
    )
    .permission(
      () => ({
        icon: 'fas fa-shield-alt',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.view_full_audit_logs', {}, true),
        permission: 'zephyrisle-ai-audit.viewFullAuditLogs',
      }),
      'moderate',
      99
    )
    .permission(
      () => ({
        icon: 'fas fa-redo',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.retry_audit', {}, true),
        permission: 'zephyrisle-ai-audit.retryAudit',
      }),
      'moderate',
      98
    )
    .permission(
      () => ({
        icon: 'fas fa-user-check',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.bypass_audit', {}, true),
        permission: 'zephyrisle-ai-audit.bypassAudit',
      }),
      'moderate',
      97
    )
    .permission(
      () => ({
        icon: 'fas fa-user-check',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.bypass_pre_approve', {}, true),
        permission: 'zephyrisle-ai-audit.bypassPreApprove',
      }),
      'start',
      100
    ),
];
