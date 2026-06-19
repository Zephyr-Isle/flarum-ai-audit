import app from 'flarum/admin/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import LinkButton from 'flarum/common/components/LinkButton';
import Button from 'flarum/common/components/Button';
import m from 'mithril';
import type Mithril from 'mithril';

type ListResponse = {
  data: Array<{ id: string; attributes: Record<string, any> }>;
  meta?: { total?: number };
};

export default class AiAuditLogListPage extends Page {
  loading = false;
  logs: ListResponse['data'] = [];
  total = 0;
  limit = 20;
  offset = 0;
  status = '';

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
    this.load();
  }

  view() {
    return (
      <div className="AiAuditLogListPage">
        <div className="container">
          <h2>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.title')}</h2>

          <div className="Form-group">
            <label>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.filter_status')}</label>
            <select
              className="FormControl"
              value={this.status}
              onchange={(e: Event) => {
                this.status = (e.target as HTMLSelectElement).value;
                this.offset = 0;
                this.load();
              }}
            >
              <option value="">{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.filter_all')}</option>
              <option value="completed">completed</option>
              <option value="failed">failed</option>
              <option value="pending">pending</option>
              <option value="retrying">retrying</option>
            </select>
          </div>

          {this.loading ? (
            <LoadingIndicator />
          ) : (
            <div className="AiAuditLogListPage-tableWrap">
              <table className="AiAuditLogListPage-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.col_subject')}</th>
                    <th>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.col_owner')}</th>
                    <th>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.col_risk')}</th>
                    <th>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.col_actions')}</th>
                    <th>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.col_status')}</th>
                    <th>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.col_created')}</th>
                    <th>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.col_ops')}</th>
                  </tr>
                </thead>
                <tbody>
                  {this.logs.map((row) => {
                    const a = row.attributes || {};
                    const risk = typeof a.risk === 'number' ? `${(a.risk * 100).toFixed(1)}%` : '';
                    const actions = Array.isArray(a.actions) ? a.actions.join(', ') : '';
                    const createdAt = a.createdAt ? new Date(a.createdAt).toLocaleString() : '';
                    const subject = `${a.subjectType || ''}#${a.subjectId || ''}`;
                    const canRetry = a.status === 'failed';

                    return (
                      <tr key={row.id}>
                        <td>
                          {LinkButton.component(
                            { href: app.route('zephyrisle-ai-audit.log', { id: row.id }) },
                            row.id
                          )}
                        </td>
                        <td>{subject}</td>
                        <td>{a.ownerId || ''}</td>
                        <td>{risk}</td>
                        <td>{actions}</td>
                        <td>{a.status || ''}</td>
                        <td>{createdAt}</td>
                        <td>
                          {canRetry
                            ? Button.component(
                                { className: 'Button Button--small', onclick: () => this.retry(row.id) },
                                app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.retry')
                              )
                            : null}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>

              <div className="AiAuditLogListPage-pagination">
                {Button.component(
                  {
                    className: 'Button Button--small',
                    disabled: this.offset <= 0,
                    onclick: () => {
                      this.offset = Math.max(0, this.offset - this.limit);
                      this.load();
                    },
                  },
                  app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.prev')
                )}
                <span className="AiAuditLogListPage-pageInfo">
                  {app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.page', {
                    current: Math.floor(this.offset / this.limit) + 1,
                    total: Math.max(1, Math.ceil(this.total / this.limit)),
                  })}
                </span>
                {Button.component(
                  {
                    className: 'Button Button--small',
                    disabled: this.offset + this.limit >= this.total,
                    onclick: () => {
                      this.offset += this.limit;
                      this.load();
                    },
                  },
                  app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.next')
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }

  async load() {
    this.loading = true;
    m.redraw();

    const url = app.forum.attribute('apiUrl') + '/ai-audit/logs';
    const filter: Record<string, string> = {};
    if (this.status) filter.status = this.status;

    try {
      const resp = (await app.request({
        method: 'GET',
        url,
        params: { filter, page: { limit: this.limit, offset: this.offset }, sort: '-createdAt' },
      })) as ListResponse;

      this.logs = resp.data || [];
      this.total = resp.meta?.total || 0;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  async retry(id: string) {
    const url = app.forum.attribute('apiUrl') + `/ai-audit/logs/${id}/retry`;
    await app.request({ method: 'POST', url });
    await this.load();
  }
}

