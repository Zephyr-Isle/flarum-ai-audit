import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';
import AiAuditLog from '../common/models/AiAuditLog';
import AiAuditLogListPage from './components/AiAuditLogListPage';
import AiAuditLogDetailPage from './components/AiAuditLogDetailPage';

const t = (key: string) => app.translator.trans(key, {}, true);

export default [
  ...commonExtend,
  new Extend.Admin()
    .section('zephyrisle-ai-audit', () => t('zephyrisle-ai-audit.admin.settings.api_section'), 100)
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_endpoint',
        label: t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
        type: 'text',
        default: 'https://api.openai.com/v1',
      }),
      'zephyrisle-ai-audit',
      190
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.api_key',
        label: t('zephyrisle-ai-audit.admin.settings.api_key'),
        type: 'password',
      }),
      'zephyrisle-ai-audit',
      189
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.model',
        label: t('zephyrisle-ai-audit.admin.settings.model'),
        type: 'text',
        default: 'gpt-4o-mini',
      }),
      'zephyrisle-ai-audit',
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
      'zephyrisle-ai-audit',
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
      'zephyrisle-ai-audit',
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
      'zephyrisle-ai-audit',
      185
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.system_prompt',
        label: t('zephyrisle-ai-audit.admin.settings.system_prompt'),
        type: 'textarea',
      }),
      'zephyrisle-ai-audit',
      184
    )
    .section('zephyrisle-ai-audit-behavior', () => t('zephyrisle-ai-audit.admin.settings.behavior_section'), 99)
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.pre_approve_enabled',
        label: t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
        type: 'boolean',
      }),
      'zephyrisle-ai-audit-behavior',
      119
    )
    .setting(
      () => ({
        setting: 'zephyrisle.ai-audit.download_images',
        label: t('zephyrisle-ai-audit.admin.settings.download_images'),
        type: 'boolean',
        default: true,
      }),
      'zephyrisle-ai-audit-behavior',
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
      'zephyrisle-ai-audit-behavior',
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
      'zephyrisle-ai-audit-behavior',
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
      'zephyrisle-ai-audit-behavior',
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
      'zephyrisle-ai-audit-behavior',
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
  new Extend.Routes()
    .add('admin', 'zephyrisle-ai-audit.logs', '/ai-audit', () => AiAuditLogListPage)
    .add('admin', 'zephyrisle-ai-audit.log', '/ai-audit/:id', () => AiAuditLogDetailPage),
  new Extend.Navigation('admin')
    .add(
      'zephyrisle-ai-audit',
      () => ({
        path: 'zephyrisle-ai-audit.logs',
        label: t('zephyrisle-ai-audit.admin.audit_logs.nav'),
        icon: 'fas fa-shield-alt',
        permission: 'zephyrisle-ai-audit.viewAuditLogs',
      }),
      80
    ),
  new Extend.Store()
    .addModel('ai-audit-logs', () => AiAuditLog),
];
