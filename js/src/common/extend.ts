import Extend from 'flarum/common/extenders';
import AiAuditLog from './models/AiAuditLog';

export default [
  new Extend.Store()
    .add('ai-audit-logs', AiAuditLog),
];

