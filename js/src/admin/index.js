import app from 'flarum/admin/app';
import AdminNav from 'flarum/admin/components/AdminNav';
import { extend } from 'flarum/common/extend';
import LinkButton from 'flarum/common/components/LinkButton';
import AiAuditLog from '../common/models/AiAuditLog';
import AiAuditLogDetailPage from './components/AiAuditLogDetailPage';
import AiAuditLogListPage from './components/AiAuditLogListPage';

export { default as extend } from './extend';

app.initializers.add('zephyrisle-ai-audit', () => {
  app.store.models['ai-audit-logs'] = AiAuditLog;

  app.routes['zephyrisle-ai-audit.logs'] = { path: '/ai-audit', component: AiAuditLogListPage };
  app.routes['zephyrisle-ai-audit.log'] = { path: '/ai-audit/:id', component: AiAuditLogDetailPage };

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
