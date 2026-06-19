import app from 'flarum/admin/app';
import AdminNav from 'flarum/admin/components/AdminNav';
import { extend } from 'flarum/common/extend';
import LinkButton from 'flarum/common/components/LinkButton';
import AiAuditLog from '../common/models/AiAuditLog';
import AiAuditLogDetailPage from './components/AiAuditLogDetailPage';
import AiAuditLogListPage from './components/AiAuditLogListPage';

app.initializers.add('zephyrisle-ai-audit', () => {
  const extensionData = (app as typeof app & {
    extensionData?: {
      for: (extensionId: string) => {
        registerSetting: (setting: Record<string, unknown>, priority?: number) => unknown;
        registerPermission: (permission: Record<string, unknown>, type?: string, priority?: number) => unknown;
      };
    };
  }).extensionData;

  app.store.models['ai-audit-logs'] = AiAuditLog;

  app.routes['zephyrisle-ai-audit.logs'] = { path: '/ai-audit', component: AiAuditLogListPage };
  app.routes['zephyrisle-ai-audit.log'] = { path: '/ai-audit/:id', component: AiAuditLogDetailPage };

  const page = extensionData?.for('zephyrisle-ai-audit');

  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.api_endpoint',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.api_endpoint'),
      type: 'text',
      default: 'https://api.openai.com/v1',
    },
    100
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.api_key',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.api_key'),
      type: 'text',
    },
    99
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.model',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.model'),
      type: 'text',
      default: 'gpt-4o-mini',
    },
    98
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.temperature',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.temperature'),
      type: 'number',
      min: 0,
      max: 2,
      step: 0.1,
      default: 0.2,
    },
    97
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.max_tokens',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.max_tokens'),
      type: 'number',
      min: 1,
      max: 4096,
      default: 800,
    },
    96
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.timeout',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.timeout'),
      type: 'number',
      min: 1,
      max: 300,
      default: 30,
    },
    95
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.system_prompt',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.system_prompt'),
      type: 'textarea',
    },
    94
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.pre_approve_enabled',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
      type: 'boolean',
    },
    93
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.download_images',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.download_images'),
      type: 'boolean',
      default: true,
    },
    92
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.review_threshold',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.review_threshold'),
      type: 'number',
      min: 0,
      max: 1,
      step: 0.05,
      default: 0.55,
    },
    91
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.action_threshold',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.action_threshold'),
      type: 'number',
      min: 0,
      max: 1,
      step: 0.05,
      default: 0.75,
    },
    90
  );
  page?.registerSetting(
    {
      setting: 'zephyrisle.ai-audit.suspend_days',
      label: app.translator.trans('zephyrisle-ai-audit.admin.settings.suspend_days'),
      type: 'number',
      min: 1,
      max: 365,
      default: 7,
    },
    89
  );

  page?.registerPermission(
    {
      icon: 'fas fa-shield-alt',
      label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.view_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewAuditLogs',
    },
    'moderate',
    100
  );
  page?.registerPermission(
    {
      icon: 'fas fa-shield-alt',
      label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.view_full_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewFullAuditLogs',
    },
    'moderate',
    99
  );
  page?.registerPermission(
    {
      icon: 'fas fa-redo',
      label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.retry_audit'),
      permission: 'zephyrisle-ai-audit.retryAudit',
    },
    'moderate',
    98
  );
  page?.registerPermission(
    {
      icon: 'fas fa-user-check',
      label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.bypass_audit'),
      permission: 'zephyrisle-ai-audit.bypassAudit',
    },
    'moderate',
    97
  );
  page?.registerPermission(
    {
      icon: 'fas fa-user-check',
      label: app.translator.trans('zephyrisle-ai-audit.admin.permissions.bypass_pre_approve'),
      permission: 'zephyrisle-ai-audit.bypassPreApprove',
    },
    'start',
    100
  );

  extend(AdminNav.prototype, 'items', (items) => {
    items.add(
      'zephyrisle-ai-audit',
      LinkButton.component(
        { href: app.route('zephyrisle-ai-audit.logs'), icon: 'fas fa-shield-alt' },
        app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.nav')
      ),
      80
    );
  });
});
