import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import m from 'mithril';
import AiAuditLogList from './AiAuditLogList';

export default class AiAuditExtensionPage extends ExtensionPage {
  activeTab = 'settings';

  view(vnode: any) {
    if (!this.extension) return null;

    const icon = this.extension.icon;

    return (
      <div className="ExtensionPage">
        <div className="ExtensionPage-header">
          <div className="container">
            <div className="ExtensionPage-header-icon">
              {icon ? (
                <i className={icon.name} style={{ background: icon.backgroundColor, color: icon.color }} />
              ) : (
                <i className="fas fa-puzzle-piece" />
              )}
            </div>
            <div className="ExtensionPage-header-title">
              <h2 className="ExtensionTitle">{this.extension.extra?.title}</h2>
              <span className="ExtensionVersion">{this.extension.version}</span>
            </div>
          </div>
        </div>
        <div className="ExtensionPage-body">
          <div className="container">
            <div className="AiAuditExtensionPage-layout">
              <div className="AiAuditExtensionPage-sidebar">{this.navItems()}</div>
              <div className="AiAuditExtensionPage-main">
                <div style={this.activeTab !== 'settings' ? { display: 'none' } : {}}>
                  {this.content(vnode)}
                </div>
                <div style={this.activeTab !== 'logs' ? { display: 'none' } : {}}>
                  {m(AiAuditLogList)}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  navItems() {
    return [
      Button.component(
        {
          className: `Button Button--block ${this.activeTab === 'settings' ? 'Button--primary' : ''}`,
          onclick: () => {
            this.activeTab = 'settings';
          },
        },
        app.translator.trans('zephyrisle-ai-audit.admin.nav.settings')
      ),
      Button.component(
        {
          className: `Button Button--block ${this.activeTab === 'logs' ? 'Button--primary' : ''}`,
          onclick: () => {
            this.activeTab = 'logs';
          },
        },
        app.translator.trans('zephyrisle-ai-audit.admin.nav.logs')
      ),
    ];
  }
}
