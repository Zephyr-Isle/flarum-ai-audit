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
                  { className: 'Button Button--small', onclick: () => app.history.back() },
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

              <table className="AiAuditLogDetailPage-table">
                <tbody>
                  {this.row('subjectType', this.log.attributes.subjectType)}
                  {this.row('subjectId', this.log.attributes.subjectId)}
                  {this.row('ownerId', this.log.attributes.ownerId)}
                  {this.row('actorId', this.log.attributes.actorId)}
                  {this.row('status', this.log.attributes.status)}
                  {this.row('risk', this.log.attributes.risk)}
                  {this.row('severity', this.log.attributes.severity)}
                  {this.row('actions', JSON.stringify(this.log.attributes.actions || []))}
                  {this.row('conclusion', this.log.attributes.conclusion)}
                  {this.row('createdAt', this.log.attributes.createdAt)}
                  {this.row('updatedAt', this.log.attributes.updatedAt)}
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
    return (
      <tr>
        <th>{label}</th>
        <td>{value === null || value === undefined ? '' : String(value)}</td>
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
