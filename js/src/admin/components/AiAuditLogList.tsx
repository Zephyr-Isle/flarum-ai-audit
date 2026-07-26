import app from 'flarum/admin/app';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import LinkButton from 'flarum/common/components/LinkButton';
import Button from 'flarum/common/components/Button';
import m from 'mithril';
import type Mithril from 'mithril';
import { apiUrl, showRequestError } from '../utils/api';

type ListResponse = {
  data: Array<{ id: string; attributes: Record<string, any> }>;
  meta?: { total?: number };
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

const SUBJECT_LABELS: Record<string, string> = {
  user_username: 'zephyrisle-ai-audit.notifications.subject_types.user_username',
  user_avatar: 'zephyrisle-ai-audit.notifications.subject_types.user_avatar',
  user_nickname: 'zephyrisle-ai-audit.notifications.subject_types.user_nickname',
  user_bio: 'zephyrisle-ai-audit.notifications.subject_types.user_bio',
  user_cover: 'zephyrisle-ai-audit.notifications.subject_types.user_cover',
  discussion_title: 'zephyrisle-ai-audit.notifications.subject_types.discussion_title',
  post_content: 'zephyrisle-ai-audit.notifications.subject_types.post_content',
  post_image: 'zephyrisle-ai-audit.notifications.subject_types.post_image',
  upload_file: 'zephyrisle-ai-audit.notifications.subject_types.upload_file',
};

export default class AiAuditLogList {
  loading = false;
  retryingId: string | null = null;
  logs: ListResponse['data'] = [];
  total = 0;
  limit = 20;
  offset = 0;
  status = '';
  subjectType = '';

  oninit() {
    this.load();
  }

  view() {
    return (
      <div className="AiAuditLogListPage">
        <h2>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.title')}</h2>

        <div className="Form-group">
          <div className="AiAuditLogListPage-filters">
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

            <div className="Form-group">
              <label>{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.filter_subject_type')}</label>
              <select
                className="FormControl"
                value={this.subjectType}
                onchange={(e: Event) => {
                  this.subjectType = (e.target as HTMLSelectElement).value;
                  this.offset = 0;
                  this.load();
                }}
              >
                <option value="">{app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.filter_all')}</option>
                {Object.entries(SUBJECT_LABELS).map(([key, labelKey]) => (
                  <option value={key} key={key}>
                    {app.translator.trans(labelKey)}
                  </option>
                ))}
              </select>
            </div>
          </div>
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
                  const st = a.subjectType || '';
                  const risk = typeof a.risk === 'number' ? `${(a.risk * 100).toFixed(1)}%` : '';
                  const actions = Array.isArray(a.actions) ? a.actions.join(', ') : '';
                  const createdAt = a.createdAt ? new Date(a.createdAt).toLocaleString() : '';
                  const subjectIcon = SUBJECT_ICONS[st] || 'fas fa-question-circle';
                  const subjectLabel = SUBJECT_LABELS[st]
                    ? app.translator.trans(SUBJECT_LABELS[st])
                    : st;
                  const subject = `${subjectLabel}#${a.subjectId || ''}`;
                  const canRetry = a.status === 'failed';

                  const riskColor =
                    a.risk >= 0.75
                      ? '#e74c3c'
                      : a.risk >= 0.55
                      ? '#f39c12'
                      : a.risk >= 0.3
                      ? '#3498db'
                      : '#27ae60';

                  return (
                    <tr key={row.id}>
                      <td>
                        <LinkButton
                          href={app.route('zephyrisle-ai-audit.logs.detail', { id: row.id })}
                        >
                          {row.id}
                        </LinkButton>
                      </td>
                      <td>
                        <span className="AiAuditLogListPage-subjectType">
                          <i className={subjectIcon} />
                          {' '}
                          {subject}
                        </span>
                      </td>
                      <td>{a.ownerId || ''}</td>
                      <td>
                        <span className="AiAuditLogListPage-riskBadge" style={{ color: riskColor }}>
                          {risk}
                        </span>
                      </td>
                      <td>{actions}</td>
                      <td>
                        <span className={`AiAuditLogListPage-statusBadge AiAuditLogListPage-statusBadge--${a.status || 'unknown'}`}>
                          {a.status || ''}
                        </span>
                      </td>
                      <td>{createdAt}</td>
                      <td>
                        {canRetry
                          ? Button.component(
                              {
                                className: 'Button Button--small',
                                disabled: this.retryingId === row.id,
                                loading: this.retryingId === row.id,
                                onclick: () => this.retry(row.id),
                              },
                              app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.retry')
                            )
                          : null}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>

            {this.logs.length === 0 && !this.loading && (
              <div className="AiAuditLogListPage-empty">
                {app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.no_logs')}
              </div>
            )}

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
    );
  }

  async load() {
    this.loading = true;
    m.redraw();

    const url = apiUrl('/ai-audit/logs');
    const filter: Record<string, string> = {};
    if (this.status) filter.status = this.status;
    if (this.subjectType) filter.subjectType = this.subjectType;

    try {
      const resp = (await app.request({
        method: 'GET',
        url,
        params: { filter, page: { limit: this.limit, offset: this.offset }, sort: '-createdAt' },
      })) as ListResponse;

      this.logs = resp.data || [];
      this.total = resp.meta?.total || 0;
    } catch (error) {
      this.logs = [];
      this.total = 0;
      showRequestError(error, 'zephyrisle-ai-audit.admin.audit_logs.errors.load');
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  async retry(id: string) {
    const url = apiUrl(`/ai-audit/logs/${id}/retry`);
    this.retryingId = id;
    m.redraw();

    try {
      await app.request({ method: 'POST', url });
      app.alerts.show({ type: 'success' }, app.translator.trans('zephyrisle-ai-audit.admin.audit_logs.messages.retry_started'));
      await this.load();
    } catch (error) {
      showRequestError(error, 'zephyrisle-ai-audit.admin.audit_logs.errors.retry');
    } finally {
      this.retryingId = null;
      m.redraw();
    }
  }
}
