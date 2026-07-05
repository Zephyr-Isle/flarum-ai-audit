import Extend from 'flarum/common/extenders';
import AiAuditLog from './models/AiAuditLog';

export default [
  new Extend.Store()
    .addModel('ai-audit-logs', () => AiAuditLog),
] as Extend[];

