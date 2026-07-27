import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ExtensionPermissionGrid from 'flarum/admin/components/ExtensionPermissionGrid';
import Button from 'flarum/common/components/Button';
import m from 'mithril';
import type Mithril from 'mithril';
import AiAuditLogList from './AiAuditLogList';

export default class AiAuditExtensionPage extends ExtensionPage {
  activeTab = 'settings';

  view(vnode: Mithril.VnodeDOM<{ id: string }, this>) {
    if (!this.extension) return null;

    const settings = app.extensionData?.getSettings(this.extension.id);

    return (
      <div className="ExtensionPage">
        <div className="ExtensionPage-header">
          <div className="container">{this.headerItems()}</div>
        </div>
        <div className="ExtensionPage-body">
          <div className="container">
            <div className="AiAuditExtensionPage-layout">
              <div className="AiAuditExtensionPage-sidebar">{this.navItems()}</div>
              <div className="AiAuditExtensionPage-main">
                <div style={this.activeTab !== 'settings' ? { display: 'none' } : {}}>
                  <div className="ExtensionPage-settings">
                    {settings ? (
                      <div className="Form">
                        {settings.map(this.buildSettingComponent.bind(this))}
                        <div className="Form-group">{this.submitButton()}</div>
                      </div>
                    ) : (
                      <h3 className="ExtensionPage-subHeader">
                        {app.translator.trans('core.admin.extension.no_settings')}
                      </h3>
                    )}
                  </div>
                </div>
                <div style={this.activeTab !== 'logs' ? { display: 'none' } : {}}>
                  {m(AiAuditLogList)}
                </div>
              </div>
            </div>
            <div className="ExtensionPage-permissions">
              <h2 className="ExtensionTitle">
                {app.translator.trans('core.admin.extension.permissions_title')}
              </h2>
              {app.extensionData?.extensionHasPermissions(this.extension.id) ? (
                <ExtensionPermissionGrid extensionId={this.extension.id} />
              ) : (
                <h3 className="ExtensionPage-subHeader">
                  {app.translator.trans('core.admin.extension.no_permissions')}
                </h3>
              )}
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
