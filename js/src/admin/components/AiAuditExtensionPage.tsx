import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ExtensionPermissionGrid from 'flarum/admin/components/ExtensionPermissionGrid';
import Button from 'flarum/common/components/Button';
import m from 'mithril';
import AiAuditLogList from './AiAuditLogList';

export default class AiAuditExtensionPage extends ExtensionPage {
  activeTab = 'settings';

  view(vnode: any) {
    if (!this.extension) return null;

    return (
      <div className={'ExtensionPage ' + this.className()}>
        {this.header()}
        <div className="ExtensionPage-body">
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
          <div style={this.activeTab !== 'settings' ? { display: 'none' } : {}}>
            <div className="ExtensionPage-permissions">
              <div className="ExtensionPage-permissions-header">
                <div className="container">
                  <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
                </div>
              </div>
              <div className="container">
                {app.registry.extensionHasPermissions(this.extension.id) ? (
                  <ExtensionPermissionGrid extensionId={this.extension.id} />
                ) : (
                  <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h3>
                )}
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
