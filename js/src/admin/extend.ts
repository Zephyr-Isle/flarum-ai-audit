import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

const t = (key: string): string => app.translator.trans(key, {}, true);

export default [
  ...commonExtend,
  new (Extend as any).Admin()
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_endpoint',
        label: t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
        type: 'text',
        default: 'https://api.openai.com/v1',
      }),
      190
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_key',
        label: t('zephyrisle-ai-audit.admin.settings.api_key'),
        type: 'password',
      }),
      189
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.model',
        label: t('zephyrisle-ai-audit.admin.settings.model'),
        type: 'text',
        default: 'gpt-4o-mini',
      }),
      188
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.temperature',
        label: t('zephyrisle-ai-audit.admin.settings.temperature'),
        type: 'number',
        min: 0,
        max: 2,
        step: 0.1,
        default: 0.2,
      }),
      187
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.max_tokens',
        label: t('zephyrisle-ai-audit.admin.settings.max_tokens'),
        type: 'number',
        min: 1,
        max: 4096,
        default: 800,
      }),
      186
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.timeout',
        label: t('zephyrisle-ai-audit.admin.settings.timeout'),
        type: 'number',
        min: 1,
        max: 300,
        default: 30,
      }),
      185
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.system_prompt',
        label: t('zephyrisle-ai-audit.admin.settings.system_prompt'),
        type: 'textarea',
      }),
      184
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.pre_approve_enabled',
        label: t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
        type: 'boolean',
      }),
      119
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.download_images',
        label: t('zephyrisle-ai-audit.admin.settings.download_images'),
        type: 'boolean',
        default: true,
      }),
      118
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.image_download_timeout',
        label: t('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
        type: 'number',
        min: 1,
        max: 30,
        default: 8,
      }),
      117
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.review_threshold',
        label: t('zephyrisle-ai-audit.admin.settings.review_threshold'),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
        default: 0.55,
      }),
      116
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.action_threshold',
        label: t('zephyrisle-ai-audit.admin.settings.action_threshold'),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
        default: 0.75,
      }),
      115
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.suspend_days',
        label: t('zephyrisle-ai-audit.admin.settings.suspend_days'),
        type: 'number',
        min: 1,
        max: 365,
        default: 7,
      }),
      114
    )
    .permission(
      () => ({
        icon: 'fas fa-shield-alt',
        label: t('zephyrisle-ai-audit.admin.permissions.view_audit_logs'),
        permission: 'zephyrisle-ai-audit.viewAuditLogs',
      }),
      'moderate',
      100
    )
    .permission(
      () => ({
        icon: 'fas fa-shield-alt',
        label: t('zephyrisle-ai-audit.admin.permissions.view_full_audit_logs'),
        permission: 'zephyrisle-ai-audit.viewFullAuditLogs',
      }),
      'moderate',
      99
    )
    .permission(
      () => ({
        icon: 'fas fa-redo',
        label: t('zephyrisle-ai-audit.admin.permissions.retry_audit'),
        permission: 'zephyrisle-ai-audit.retryAudit',
      }),
      'moderate',
      98
    )
    .permission(
      () => ({
        icon: 'fas fa-user-check',
        label: t('zephyrisle-ai-audit.admin.permissions.bypass_audit'),
        permission: 'zephyrisle-ai-audit.bypassAudit',
      }),
      'moderate',
      97
    )
    .permission(
      () => ({
        icon: 'fas fa-user-check',
        label: t('zephyrisle-ai-audit.admin.permissions.bypass_pre_approve'),
        permission: 'zephyrisle-ai-audit.bypassPreApprove',
      }),
      'start',
      100
    ),
];
