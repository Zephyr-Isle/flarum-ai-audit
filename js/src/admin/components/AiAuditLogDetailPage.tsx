import app from 'flarum/admin/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import m from 'mithril';
import type Mithril from 'mithril';
import { apiUrl, showRequestError } from '../utils/api';

type ShowResponse = {
  data: { id: string; attributes: Record<string, any> };
};

const SUBJECT_ICONS: Record<string, string> = {
  user_username: 'fas fa-user',
  user_avatar: 'fas fa-user-circle',
  user_nickname: 'fas fa-tag',
  user_bio: 'fas fa-quote-right',
  user_cover: 'fas fa-image',
  discussion_title: 'fas fa-heading',
  post_content: 'fas fa-comment',
  post_image: 'fas fa-camera',
  upload_file: 'fas fa-file-upload',
};

export default class AiAuditLogDetailPage extends Page {
  loading = false;
  retrying = false;
  id = '';
  log: ShowResponse['data'] | null = null;

  oninit(vnode: Mithril.Vnode<{ id: string }>) {
    super.oninit(vnode);
    this.id = vnode.attrs.id;
    this.load();
  }

  view() {
    return (
      <div className="AiAuditLogDetailPage">
        <div className="container">
          <h2>{app.translator.trans('zephyrisle-ai-audit.admin.audit_log.title', { id: this.id })}</h2>

          {this.loading ? (
            <LoadingIndicator />
          ) : this.log ? (
            <div>
              <div className="AiAuditLogDetailPage-actions">
                {Button.component(
                  { className: 'Button Button--small', onclick: () => m.route.set(app.route('zephyrisle-ai-audit.logs')) },
                  app.translator.trans('zephyrisle-ai-audit.admin.audit_log.back')
                )}
                {this.log.attributes.status === 'failed'
                  ? Button.component(
                      {
                        className: 'Button Button--small Button--primary',
                        loading: this.retrying,
                        disabled: this.retrying,
                        onclick: () => this.retry(),
                      },
                      app.translator.trans('zephyrisle-ai-audit.admin.audit_log.retry')
                    )
                  : null}
              </div>

              <div className="AiAuditLogDetailPage-header">
                <div className="AiAuditLogDetailPage-headerIcon">
                  <i className={SUBJECT_ICONS[this.log.attributes.subjectType] || 'fas fa-shield-alt'} />
                </div>
                <div className="AiAuditLogDetailPage-headerInfo">
                  <span className="AiAuditLogDetailPage-subjectType">
                    {app.translator.trans(
                      `zephyrisle-ai-audit.notifications.subject_types.${this.log.attributes.subjectType || 'unknown'}`,
                      {},
                      true
                    ) || this.log.attributes.subjectType}
                  </span>
                  <span className={`AiAuditLogDetailPage-statusBadge AiAuditLogDetailPage-statusBadge--${this.log.attributes.status || 'unknown'}`}>
                    {this.log.attributes.status || ''}
                  </span>
                </div>
              </div>

              <table className="AiAuditLogDetailPage-table">
                <tbody>
                  {this.row('ID', this.log.id)}
                  {this.row('subjectType', this.log.attributes.subjectType)}
                  {this.row('subjectId', this.log.attributes.subjectId)}
                  {this.row('ownerId', this.log.attributes.ownerId)}
                  {this.row('actorId', this.log.attributes.actorId)}
                  {this.row('status', this.log.attributes.status)}
                  {this.riskRow('risk', this.log.attributes.risk)}
                  {this.row('severity', this.log.attributes.severity)}
                  {this.actionRow('actions', this.log.attributes.actions)}
                  {this.row('conclusion', this.log.attributes.conclusion)}
                  {this.row('retryCount', this.log.attributes.retryCount)}
                  {this.row('createdAt', this.log.attributes.createdAt)}
                  {this.row('updatedAt', this.log.attributes.updatedAt)}
                  {this.row('error', this.log.attributes.error)}
                </tbody>
              </table>

              {this.jsonBlock(app.translator.trans('zephyrisle-ai-audit.admin.audit_log.snapshot'), this.log.attributes.snapshot)}
              {this.jsonBlock(app.translator.trans('zephyrisle-ai-audit.admin.audit_log.analysis'), this.log.attributes.analysis)}
            </div>
          ) : (
            <div className="AiAuditLogDetailPage-empty">
              {app.translator.trans('zephyrisle-ai-audit.admin.audit_log.not_found')}
            </div>
          )}
        </div>
      </div>
    );
  }

  row(label: string, value: any) {
    if (value === null || value === undefined) return null;
    return (
      <tr>
        <th>{label}</th>
        <td>{String(value)}</td>
      </tr>
    );
  }

  riskRow(label: string, value: any) {
    if (value === null || value === undefined) return null;
    const riskPercent = typeof value === 'number' ? `${(value * 100).toFixed(1)}%` : String(value);
    const riskColor =
      value >= 0.75
        ? '#e74c3c'
        : value >= 0.55
        ? '#f39c12'
        : value >= 0.3
        ? '#3498db'
        : '#27ae60';

    return (
      <tr>
        <th>{label}</th>
        <td style={{ color: riskColor, fontWeight: 'bold' }}>{riskPercent}</td>
      </tr>
    );
  }

  actionRow(label: string, value: any) {
    if (!Array.isArray(value) || value.length === 0) return null;
    const actionLabels = value.map((a: string) => {
      return (
        app.translator.trans(
          `zephyrisle-ai-audit.notifications.action_labels.${a}`,
          {},
          true
        ) || a
      );
    });
    return (
      <tr>
        <th>{label}</th>
        <td>
          <div className="AiAuditLogDetailPage-actionTags">
            {actionLabels.map((al: string, i: number) => (
              <span className="AiAuditLogDetailPage-actionTag" key={i}>
                {al}
              </span>
            ))}
          </div>
        </td>
      </tr>
    );
  }

  jsonBlock(title: string, value: any) {
    if (value === null || value === undefined) return null;
    return (
      <div className="AiAuditLogDetailPage-json">
        <h3>{title}</h3>
        <pre>{JSON.stringify(value, null, 2)}</pre>
      </div>
    );
  }

  async load() {
    this.loading = true;
    m.redraw();

    const url = apiUrl(`/ai-audit/logs/${this.id}`);
    try {
      const resp = (await app.request({ method: 'GET', url })) as ShowResponse;
      this.log = resp.data || null;
    } catch (error) {
      this.log = null;
      showRequestError(error, 'zephyrisle-ai-audit.admin.audit_log.errors.load');
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  async retry() {
    const url = apiUrl(`/ai-audit/logs/${this.id}/retry`);
    this.retrying = true;
    m.redraw();

    try {
      await app.request({ method: 'POST', url });
      app.alerts.show({ type: 'success' }, app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.messages.retry_started'));
      await this.load();
    } catch (error) {
      showRequestError(error, 'zephyrisle-ai-audit.admin.audit_log.errors.retry');
    } finally {
      this.retrying = false;
      m.redraw();
    }
  }
}
