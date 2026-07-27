import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import ExtensionPermissionGrid from 'flarum/admin/components/ExtensionPermissionGrid';
import m from 'mithril';
import AiAuditLogList from './AiAuditLogList';

export default class AiAuditExtensionPage extends ExtensionPage {
  activeTab = 'settings';

  view(vnode: any) {
    if (!this.extension) return null;

    return (
      <div className="ExtensionPage">
        <div className="ExtensionPage-header">
          <div className="container">
            <div className="ExtensionPage-header-icon">
              {this.extension.icon ? (
                <i className={this.extension.icon.name} style={{ background: this.extension.icon.backgroundColor, color: this.extension.icon.color }} />
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
                  {app.registry.extensionHasPermissions(this.extension.id) ? (
                    <div className="ExtensionPage-permissions">
                      <div className="ExtensionPage-permissions-header">
                        <div className="container">
                          <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
                        </div>
                      </div>
                      <div className="container">
                        <ExtensionPermissionGrid extensionId={this.extension.id} />
                      </div>
                    </div>
                  ) : null}
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
