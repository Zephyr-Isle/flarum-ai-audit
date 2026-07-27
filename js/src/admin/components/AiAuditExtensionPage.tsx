import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ExtensionPermissionGrid from 'flarum/admin/components/ExtensionPermissionGrid';
import m from 'mithril';
import type Mithril from 'mithril';
import AiAuditLogList from './AiAuditLogList';

export default class AiAuditExtensionPage extends ExtensionPage {
  activeTab = 'settings';

  content(vnode: Mithril.VnodeDOM<{ id: string }, this>) {
    if (!this.extension) return null;

    return (
      <div className="AiAuditExtensionPage-layout">
        <div className="AiAuditExtensionPage-sidebar">{this.navItems()}</div>
        <div className="AiAuditExtensionPage-main">
          {this.activeTab === 'logs'
            ? m(AiAuditLogList, { key: 'ai-audit-logs' })
            : this.settingsContent()}
        </div>
      </div>
    );
  }

  sections(vnode: Mithril.VnodeDOM<{ id: string }, this>) {
    const items = super.sections(vnode);

    items.add(
      'ai-audit-permissions',
      <div className="ExtensionPage-permissions">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">
              {app.translator.trans('core.admin.extension.permissions_title')}
            </h2>
          </div>
        </div>
        <div className="container">
          {app.extensionData.extensionHasPermissions(this.extension.id) ? (
            <ExtensionPermissionGrid extensionId={this.extension.id} />
          ) : (
            <h3 className="ExtensionPage-subHeader">
              {app.translator.trans('core.admin.extension.no_permissions')}
            </h3>
          )}
        </div>
      </div>,
      -100
    );

    return items;
  }

  settingsContent() {
    const settings = app.extensionData.getSettings(this.extension.id);
    return (
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
    );
  }

  navItems() {
    return [
      m(
        'button',
        {
          className: `Button Button--block ${this.activeTab === 'settings' ? 'Button--primary' : ''}`,
          onclick: () => {
            this.activeTab = 'settings';
          },
        },
        app.translator.trans('zephyrisle-ai-audit.admin.nav.settings')
      ),
      m(
        'button',
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
