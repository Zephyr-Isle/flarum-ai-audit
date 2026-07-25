import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

import commonExtend from '../common/extend';

import AiAuditLog from '../common/models/AiAuditLog';
import AiAuditLogListPage from './components/AiAuditLogListPage';
import AiAuditLogDetailPage from './components/AiAuditLogDetailPage';

const t = (key: string) =>
  app.translator.trans(key, {}, true);

export default [
  ...commonExtend,

  new Extend.Admin()
    .page(() => ({
      path: '/ai-audit',
      label: t('zephyrisle-ai-audit.admin.title'),
      icon: 'fas fa-shield-alt',
    }))

    // ============ API Configuration Section ============

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.api_endpoint',
      label: t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
      type: 'text',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.api_key',
      label: t('zephyrisle-ai-audit.admin.settings.api_key'),
      type: 'password',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.model',
      label: t('zephyrisle-ai-audit.admin.settings.model'),
      type: 'text',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.temperature',
      label: t('zephyrisle-ai-audit.admin.settings.temperature'),
      type: 'number',
      min: 0,
      max: 2,
      step: 0.1,
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.max_tokens',
      label: t('zephyrisle-ai-audit.admin.settings.max_tokens'),
      type: 'number',
      min: 1,
      max: 4096,
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.timeout',
      label: t('zephyrisle-ai-audit.admin.settings.timeout'),
      type: 'number',
      min: 1,
      max: 300,
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.system_prompt',
      label: t('zephyrisle-ai-audit.admin.settings.system_prompt'),
      type: 'textarea',
    }))

    // ============ Content Audit Toggles ============

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_username_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_username_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_avatar_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_avatar_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_nickname_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_nickname_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_bio_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_bio_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_cover_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_cover_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_post_content_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_post_content_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_post_image_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_post_image_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_discussion_title_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_discussion_title_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_upload_audit',
      label: t('zephyrisle-ai-audit.admin.settings.enable_upload_audit'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_notifications',
      label: t('zephyrisle-ai-audit.admin.settings.enable_notifications'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.use_json_schema',
      label: t('zephyrisle-ai-audit.admin.settings.use_json_schema'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.enable_context',
      label: t('zephyrisle-ai-audit.admin.settings.enable_context'),
      type: 'boolean',
    }))

    // ============ Behavior Settings ============

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.pre_approve_enabled',
      label: t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.download_images',
      label: t('zephyrisle-ai-audit.admin.settings.download_images'),
      type: 'boolean',
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.image_download_timeout',
      label: t('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
      type: 'number',
      min: 1,
      max: 30,
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.review_threshold',
      label: t('zephyrisle-ai-audit.admin.settings.review_threshold'),
      type: 'number',
      min: 0,
      max: 1,
      step: 0.05,
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.action_threshold',
      label: t('zephyrisle-ai-audit.admin.settings.action_threshold'),
      type: 'number',
      min: 0,
      max: 1,
      step: 0.05,
    }))

    .setting(() => ({
      setting: 'zephyrisle.ai-audit.suspend_days',
      label: t('zephyrisle-ai-audit.admin.settings.suspend_days'),
      type: 'number',
      min: 1,
      max: 365,
    }))

    // ============ Permissions ============

    .permission(() => ({
      icon: 'fas fa-shield-alt',
      label: t('zephyrisle-ai-audit.admin.permissions.view_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewAuditLogs',
    }), 'moderate', 110)

    .permission(() => ({
      icon: 'fas fa-shield-alt',
      label: t('zephyrisle-ai-audit.admin.permissions.view_full_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewFullAuditLogs',
    }), 'moderate', 109)

    .permission(() => ({
      icon: 'fas fa-redo',
      label: t('zephyrisle-ai-audit.admin.permissions.retry_audit'),
      permission: 'zephyrisle-ai-audit.retryAudit',
    }), 'moderate', 108)

    .permission(() => ({
      icon: 'fas fa-user-check',
      label: t('zephyrisle-ai-audit.admin.permissions.bypass_audit'),
      permission: 'zephyrisle-ai-audit.bypassAudit',
    }), 'moderate', 107)

    .permission(() => ({
      icon: 'fas fa-user-check',
      label: t('zephyrisle-ai-audit.admin.permissions.bypass_pre_approve'),
      permission: 'zephyrisle-ai-audit.bypassPreApprove',
    }), 'moderate', 106),

  // ============ Admin Routes ============

  new Extend.Routes('admin')
    .add(
      'zephyrisle-ai-audit.logs',
      '/ai-audit',
      AiAuditLogListPage
    )
    .add(
      'zephyrisle-ai-audit.logs.detail',
      '/ai-audit/:id',
      AiAuditLogDetailPage
    ),

  // ============ Admin Navigation ============

  new Extend.Navigation('admin')
    .add(
      'zephyrisle-ai-audit-logs',
      () => ({
        icon: 'fas fa-shield-alt',
        label: t('zephyrisle-ai-audit.admin.nav.logs'),
        href: app.url('/admin/ai-audit'),
      }),
      99
    ),

  // Store Models (registered in common/extend.ts via ...commonExtend)
];
