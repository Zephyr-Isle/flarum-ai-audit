import app from 'flarum/forum/app';
import AiAuditLog from '../common/models/AiAuditLog';

export { default as extend } from './extend';

app.initializers.add('zephyrisle-ai-audit', () => {
  app.store.models['ai-audit-logs'] = AiAuditLog;
});

