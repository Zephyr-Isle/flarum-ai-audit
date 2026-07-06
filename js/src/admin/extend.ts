import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';
import AiAuditLog from '../common/models/AiAuditLog';
import AiAuditLogListPage from './components/AiAuditLogListPage';
import AiAuditLogDetailPage from './components/AiAuditLogDetailPage';

const t = (key: string) => app.translator.trans(key, {}, true);

export default [
  ...commonExtend,
  new Extend.Routes('admin').add(
    'zephyrisle-ai-audit.logs',
    '/ai-audit',
    AiAuditLogListPage
  ),
  new Extend.Routes('admin').add(
    'zephyrisle-ai-audit.logs.detail',
    '/ai-audit/:id',
    AiAuditLogDetailPage
  ),
  new Extend.Navigation('admin').add(
    'zephyrisle-ai-audit-logs',
    () => ({
      icon: 'fas fa-shield-alt',
      children: t('zephyrisle-ai-audit.admin.nav.logs'),
      href: app.url('/admin/ai-audit'),
    }),
    99
  ),
  new Extend.Store().addModel('ai-audit-logs', AiAuditLog),
  new Extend.Admin()
    // === Settings ===
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_endpoint',
        label: t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
        type: 'text',
      }),
      1000
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_key',
        label: t('zephyrisle-ai-audit.admin.settings.api_key'),
        type: 'password',
      }),
      990
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.model',
        label: t('zephyrisle-ai-audit.admin.settings.model'),
        type: 'text',
      }),
      980
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.temperature',
        label: t('zephyrisle-ai-audit.admin.settings.temperature'),
        type: 'number',
        min: 0,
        max: 2,
        step: 0.1,
      }),
      970
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.max_tokens',
        label: t('zephyrisle-ai-audit.admin.settings.max_tokens'),
        type: 'number',
        min: 1,
        max: 4096,
      }),
      960
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.timeout',
        label: t('zephyrisle-ai-audit.admin.settings.timeout'),
        type: 'number',
        min: 1,
        max: 300,
      }),
      950
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.system_prompt',
        label: t('zephyrisle-ai-audit.admin.settings.system_prompt'),
        type: 'textarea',
      }),
      940
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.pre_approve_enabled',
        label: t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
        type: 'boolean',
      }),
      930
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.download_images',
        label: t('zephyrisle-ai-audit.admin.settings.download_images'),
        type: 'boolean',
      }),
      920
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.image_download_timeout',
        label: t('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
        type: 'number',
        min: 1,
        max: 30,
      }),
      910
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.review_threshold',
        label: t('zephyrisle-ai-audit.admin.settings.review_threshold'),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
      }),
      900
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.action_threshold',
        label: t('zephyrisle-ai-audit.admin.settings.action_threshold'),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
      }),
      890
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.suspend_days',
        label: t('zephyrisle-ai-audit.admin.settings.suspend_days'),
        type: 'number',
        min: 1,
        max: 365,
      }),
      880
    )
    // === Permissions ===
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
      'moderate',
      96
    ),
];
