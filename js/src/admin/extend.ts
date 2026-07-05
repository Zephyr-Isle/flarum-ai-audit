import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import m from 'mithril';
import commonExtend from '../common/extend';

const extensionId = 'zephyrisle-ai-audit';
const hasExtensionData = typeof (app as any).extensionData?.for === 'function';

function sectionTitle(labelKey: string) {
  return () =>
    m('div', { className: 'Form-group' }, [
      m('h3', { className: 'App-titleControl' }, app.translator.trans(labelKey)),
    ]);
}

export function registerAdminExtensionData(): void {
  if (!hasExtensionData) return;

  const extensionData = app.extensionData.for(extensionId);

  extensionData
    .registerSetting(sectionTitle('zephyrisle-ai-audit.admin.settings.api_section'), 200)
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.api_endpoint',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.api_endpoint'),
        type: 'text',
        default: 'https://api.openai.com/v1',
      },
      190
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.api_key',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.api_key'),
        type: 'text',
      },
      189
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.model',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.model'),
        type: 'text',
        default: 'gpt-4o-mini',
      },
      188
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.temperature',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.temperature'),
        type: 'number',
        min: 0,
        max: 2,
        step: 0.1,
        default: 0.2,
      },
      187
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.max_tokens',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.max_tokens'),
        type: 'number',
        min: 1,
        max: 4096,
        default: 800,
      },
      186
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.timeout',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.timeout'),
        type: 'number',
        min: 1,
        max: 300,
        default: 30,
      },
      185
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.system_prompt',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.system_prompt'),
        type: 'textarea',
      },
      184
    )
    .registerSetting(sectionTitle('zephyrisle-ai-audit.admin.settings.behavior_section'), 120)
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.pre_approve_enabled',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
        type: 'boolean',
      },
      119
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.download_images',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.download_images'),
        type: 'boolean',
        default: true,
      },
      118
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.image_download_timeout',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
        type: 'number',
        min: 1,
        max: 30,
        default: 8,
      },
      117
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.review_threshold',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.review_threshold'),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
        default: 0.55,
      },
      116
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.action_threshold',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.action_threshold'),
        type: 'number',
        min: 0,
        max: 1,
        step: 0.05,
        default: 0.75,
      },
      115
    )
    .registerSetting(
      {
        setting: 'zephyrisle.ai-audit.suspend_days',
        label: app.translator.trans('zephyrisle-ai-audit.admin.settings.suspend_days'),
        type: 'number',
        min: 1,
        max: 365,
        default: 7,
      },
      114
    )
    .registerPermission(
      {
        icon: 'fas fa-shield-alt',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.view_audit_logs'),
        permission: 'zephyrisle-ai-audit.viewAuditLogs',
      },
      'moderate',
      100
    )
    .registerPermission(
      {
        icon: 'fas fa-shield-alt',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.view_full_audit_logs'),
        permission: 'zephyrisle-ai-audit.viewFullAuditLogs',
      },
      'moderate',
      99
    )
    .registerPermission(
      {
        icon: 'fas fa-redo',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.retry_audit'),
        permission: 'zephyrisle-ai-audit.retryAudit',
      },
      'moderate',
      98
    )
    .registerPermission(
      {
        icon: 'fas fa-user-check',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.bypass_audit'),
        permission: 'zephyrisle-ai-audit.bypassAudit',
      },
      'moderate',
      97
    )
    .registerPermission(
      {
        icon: 'fas fa-user-check',
        label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.bypass_pre_approve'),
        permission: 'zephyrisle-ai-audit.bypassPreApprove',
      },
      'start',
      100
    );
}

const legacyAdminExtenders = hasExtensionData
  ? []
  : [
      new (Extend as any).Admin()
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
          90
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
          89
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
          88
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

export default [...commonExtend, ...legacyAdminExtenders];
