import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';
import AiAuditLogListPage from './components/AiAuditLogListPage';
import AiAuditLogDetailPage from './components/AiAuditLogDetailPage';

export default [
  ...commonExtend,

  // ============ Custom Routes ============
  new Extend.Routes('admin')
    .add('zephyrisle-ai-audit.logs', '/ai-audit', AiAuditLogListPage)
    .add('zephyrisle-ai-audit.logs.detail', '/ai-audit/:id', AiAuditLogDetailPage),
];

// ============ Settings, Pages & Permissions (registered at runtime via app.registry) ============

const EXT_ID = 'zephyrisle-ai-audit';
const t = (key: string) => app.translator.trans(key, {}, true);

app.initializers.add(EXT_ID, () => {
  const registry = app.registry.for(EXT_ID);

  // ============ Sidebar: Audit Logs Page ============
  registry.registerPage(AiAuditLogListPage);

  // ============ Settings Page (auto-generated) ============

  // API Configuration
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.api_endpoint',
    label: t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
    type: 'text',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.api_key',
    label: t('zephyrisle-ai-audit.admin.settings.api_key'),
    type: 'password',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.model',
    label: t('zephyrisle-ai-audit.admin.settings.model'),
    type: 'text',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.temperature',
    label: t('zephyrisle-ai-audit.admin.settings.temperature'),
    type: 'number',
    min: 0,
    max: 2,
    step: 0.1,
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.max_tokens',
    label: t('zephyrisle-ai-audit.admin.settings.max_tokens'),
    type: 'number',
    min: 1,
    max: 4096,
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.timeout',
    label: t('zephyrisle-ai-audit.admin.settings.timeout'),
    type: 'number',
    min: 1,
    max: 300,
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.system_prompt',
    label: t('zephyrisle-ai-audit.admin.settings.system_prompt'),
    type: 'textarea',
  });

  // Content Audit Toggles
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_username_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_username_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_avatar_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_avatar_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_nickname_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_nickname_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_bio_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_bio_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_cover_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_cover_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_post_content_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_post_content_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_post_image_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_post_image_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_discussion_title_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_discussion_title_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_upload_audit',
    label: t('zephyrisle-ai-audit.admin.settings.enable_upload_audit'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_notifications',
    label: t('zephyrisle-ai-audit.admin.settings.enable_notifications'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.use_json_schema',
    label: t('zephyrisle-ai-audit.admin.settings.use_json_schema'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.enable_context',
    label: t('zephyrisle-ai-audit.admin.settings.enable_context'),
    type: 'switch',
  });

  // Behavior Settings
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.pre_approve_enabled',
    label: t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.download_images',
    label: t('zephyrisle-ai-audit.admin.settings.download_images'),
    type: 'switch',
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.image_download_timeout',
    label: t('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
    type: 'number',
    min: 1,
    max: 30,
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.review_threshold',
    label: t('zephyrisle-ai-audit.admin.settings.review_threshold'),
    type: 'number',
    min: 0,
    max: 1,
    step: 0.05,
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.action_threshold',
    label: t('zephyrisle-ai-audit.admin.settings.action_threshold'),
    type: 'number',
    min: 0,
    max: 1,
    step: 0.05,
  });
  registry.registerSetting({
    setting: 'zephyrisle.ai-audit.suspend_days',
    label: t('zephyrisle-ai-audit.admin.settings.suspend_days'),
    type: 'number',
    min: 1,
    max: 365,
  });

  // ============ Permissions ============
  registry.registerPermission(
    {
      icon: 'fas fa-shield-alt',
      label: t('zephyrisle-ai-audit.admin.permissions.view_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewAuditLogs',
    },
    'moderate'
  );
  registry.registerPermission(
    {
      icon: 'fas fa-shield-alt',
      label: t('zephyrisle-ai-audit.admin.permissions.view_full_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewFullAuditLogs',
    },
    'moderate'
  );
  registry.registerPermission(
    {
      icon: 'fas fa-redo',
      label: t('zephyrisle-ai-audit.admin.permissions.retry_audit'),
      permission: 'zephyrisle-ai-audit.retryAudit',
    },
    'moderate'
  );
  registry.registerPermission(
    {
      icon: 'fas fa-user-check',
      label: t('zephyrisle-ai-audit.admin.permissions.bypass_audit'),
      permission: 'zephyrisle-ai-audit.bypassAudit',
    },
    'moderate'
  );
  registry.registerPermission(
    {
      icon: 'fas fa-user-check',
      label: t('zephyrisle-ai-audit.admin.permissions.bypass_pre_approve'),
      permission: 'zephyrisle-ai-audit.bypassPreApprove',
    },
    'moderate'
  );
});
