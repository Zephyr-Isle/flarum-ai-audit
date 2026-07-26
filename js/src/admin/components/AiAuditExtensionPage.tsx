import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';
import AiAuditLogList from './AiAuditLogList';
import m from 'mithril';

export default class AiAuditExtensionPage extends ExtensionPage {
  activeTab = 'settings';
  logList = new AiAuditLogList();

  sidebar(): ItemList<any> {
    const items = new ItemList<any>();

    items.add(
      'settings',
      m(
        'a',
        {
          className: `Button Button--block ${this.activeTab === 'settings' ? 'Button--primary' : ''}`,
          onclick: () => {
            this.activeTab = 'settings';
          },
        },
        app.translator.trans('zephyrisle-ai-audit.admin.nav.settings')
      ),
      100
    );

    items.add(
      'logs',
      m(
        'a',
        {
          className: `Button Button--block ${this.activeTab === 'logs' ? 'Button--primary' : ''}`,
          onclick: () => {
            this.activeTab = 'logs';
          },
        },
        app.translator.trans('zephyrisle-ai-audit.admin.nav.logs')
      ),
      90
    );

    return items;
  }

  content(): m.Children {
    if (this.activeTab === 'logs') {
      return m(AiAuditLogList);
    }

    return super.content();
  }
}
