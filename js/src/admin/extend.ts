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
  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-shield-alt',
      label: t('zephyrisle-ai-audit.admin.permissions.view_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewAuditLogs',
    }),
    'moderate',
    100
  ),
  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-shield-alt',
      label: t('zephyrisle-ai-audit.admin.permissions.view_full_audit_logs'),
      permission: 'zephyrisle-ai-audit.viewFullAuditLogs',
    }),
    'moderate',
    99
  ),
  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-redo',
      label: t('zephyrisle-ai-audit.admin.permissions.retry_audit'),
      permission: 'zephyrisle-ai-audit.retryAudit',
    }),
    'moderate',
    98
  ),
  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-user-check',
      label: t('zephyrisle-ai-audit.admin.permissions.bypass_audit'),
      permission: 'zephyrisle-ai-audit.bypassAudit',
    }),
    'moderate',
    97
  ),
  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-user-check',
      label: t('zephyrisle-ai-audit.admin.permissions.bypass_pre_approve'),
      permission: 'zephyrisle-ai-audit.bypassPreApprove',
    }),
    'moderate',
    96
  ),
];
