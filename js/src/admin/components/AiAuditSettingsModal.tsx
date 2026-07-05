import SettingsModal from 'flarum/admin/components/SettingsModal';
import app from 'flarum/admin/app';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';

const t = (key: string) => app.translator.trans(key, {}, true);

export default class AiAuditSettingsModal extends SettingsModal {
  title() {
    return t('zephyrisle-ai-audit.admin.settings.title');
  }

  form() {
    const items = new ItemList();

    // API Settings Section
    items.add(
      'api-header',
      <div className="Form-group">
        <h3>{t('zephyrisle-ai-audit.admin.settings.api_section')}</h3>
      </div>,
      1000
    );

    items.add(
      'api-endpoint',
      this.setting(
        'zephyrisle.ai-audit.api_endpoint',
        t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
        'text'
      ),
      990
    );

    items.add(
      'api-key',
      this.setting(
        'zephyrisle.ai-audit.api_key',
        t('zephyrisle-ai-audit.admin.settings.api_key'),
        'password'
      ),
      980
    );

    items.add(
      'model',
      this.setting(
        'zephyrisle.ai-audit.model',
        t('zephyrisle-ai-audit.admin.settings.model'),
        'text'
      ),
      970
    );

    items.add(
      'temperature',
      this.numberSetting(
        'zephyrisle.ai-audit.temperature',
        t('zephyrisle-ai-audit.admin.settings.temperature'),
        { min: 0, max: 2, step: 0.1 }
      ),
      960
    );

    items.add(
      'max-tokens',
      this.numberSetting(
        'zephyrisle.ai-audit.max_tokens',
        t('zephyrisle-ai-audit.admin.settings.max_tokens'),
        { min: 1, max: 4096 }
      ),
      950
    );

    items.add(
      'timeout',
      this.numberSetting(
        'zephyrisle.ai-audit.timeout',
        t('zephyrisle-ai-audit.admin.settings.timeout'),
        { min: 1, max: 300 }
      ),
      940
    );

    items.add(
      'system-prompt',
      this.textareaSetting(
        'zephyrisle.ai-audit.system_prompt',
        t('zephyrisle-ai-audit.admin.settings.system_prompt')
      ),
      930
    );

    // Behavior Settings Section
    items.add(
      'behavior-header',
      <div className="Form-group">
        <h3>{t('zephyrisle-ai-audit.admin.settings.behavior_section')}</h3>
      </div>,
      500
    );

    items.add(
      'pre-approve',
      this.booleanSetting(
        'zephyrisle.ai-audit.pre_approve_enabled',
        t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled')
      ),
      490
    );

    items.add(
      'download-images',
      this.booleanSetting(
        'zephyrisle.ai-audit.download_images',
        t('zephyrisle-ai-audit.admin.settings.download_images')
      ),
      480
    );

    items.add(
      'image-download-timeout',
      this.numberSetting(
        'zephyrisle.ai-audit.image_download_timeout',
        t('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
        { min: 1, max: 30 }
      ),
      470
    );

    items.add(
      'review-threshold',
      this.numberSetting(
        'zephyrisle.ai-audit.review_threshold',
        t('zephyrisle-ai-audit.admin.settings.review_threshold'),
        { min: 0, max: 1, step: 0.05 }
      ),
      460
    );

    items.add(
      'action-threshold',
      this.numberSetting(
        'zephyrisle.ai-audit.action_threshold',
        t('zephyrisle-ai-audit.admin.settings.action_threshold'),
        { min: 0, max: 1, step: 0.05 }
      ),
      450
    );

    items.add(
      'suspend-days',
      this.numberSetting(
        'zephyrisle.ai-audit.suspend_days',
        t('zephyrisle-ai-audit.admin.settings.suspend_days'),
        { min: 1, max: 365 }
      ),
      440
    );

    return items.toArray();
  }

  setting(key: string, label: string, type: string = 'text') {
    const value = Stream(app.data.settings[key]);

    return (
      <div className="Form-group">
        <label>{label}</label>
        <input
          className="FormControl"
          type={type}
          value={value()}
          onchange={(e: Event) => {
            value((e.target as HTMLInputElement).value);
          }}
        />
      </div>
    );
  }

  numberSetting(
    key: string,
    label: string,
    options?: { min?: number; max?: number; step?: number }
  ) {
    const value = Stream(app.data.settings[key]);

    return (
      <div className="Form-group">
        <label>{label}</label>
        <input
          className="FormControl"
          type="number"
          min={options?.min}
          max={options?.max}
          step={options?.step}
          value={value()}
          onchange={(e: Event) => {
            value((e.target as HTMLInputElement).value);
          }}
        />
      </div>
    );
  }

  booleanSetting(key: string, label: string) {
    const value = Stream(app.data.settings[key] === '1' || app.data.settings[key] === true);

    return (
      <div className="Form-group">
        <label className="Checkbox">
          <input
            type="checkbox"
            checked={value()}
            onchange={(e: Event) => {
              value((e.target as HTMLInputElement).checked);
            }}
          />
          {label}
        </label>
      </div>
    );
  }

  textareaSetting(key: string, label: string) {
    const value = Stream(app.data.settings[key]);

    return (
      <div className="Form-group">
        <label>{label}</label>
        <textarea
          className="FormControl"
          value={value()}
          onchange={(e: Event) => {
            value((e.target as HTMLTextAreaElement).value);
          }}
          rows={5}
        />
      </div>
    );
  }
}
