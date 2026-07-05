import app from 'flarum/admin/app';
import AiAuditLog from '../common/models/AiAuditLog';

const t = (key: string) => app.translator.trans(key, {}, true);

function registerSettings() {
  ['zephyrisle-ai-audit', 'zephyrisle-flarum-ai-audit'].forEach((extensionId) => {
    const extension = app.extensionData.for(extensionId);

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.api_endpoint',
        label: t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
        type: 'text',
      },
      1000
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.api_key',
        label: t('zephyrisle-ai-audit.admin.settings.api_key'),
        type: 'password',
      },
      990
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.model',
        label: t('zephyrisle-ai-audit.admin.settings.model'),
        type: 'text',
      },
      980
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.temperature',
        label: t('zephyrisle-ai-audit.admin.settings.temperature'),
        type: 'number',
      },
      970
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.max_tokens',
        label: t('zephyrisle-ai-audit.admin.settings.max_tokens'),
        type: 'number',
      },
      960
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.timeout',
        label: t('zephyrisle-ai-audit.admin.settings.timeout'),
        type: 'number',
      },
      950
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.system_prompt',
        label: t('zephyrisle-ai-audit.admin.settings.system_prompt'),
        type: 'textarea',
      },
      940
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.pre_approve_enabled',
        label: t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
        type: 'boolean',
      },
      930
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.download_images',
        label: t('zephyrisle-ai-audit.admin.settings.download_images'),
        type: 'boolean',
      },
      920
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.image_download_timeout',
        label: t('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
        type: 'number',
      },
      910
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.review_threshold',
        label: t('zephyrisle-ai-audit.admin.settings.review_threshold'),
        type: 'number',
      },
      900
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.action_threshold',
        label: t('zephyrisle-ai-audit.admin.settings.action_threshold'),
        type: 'number',
      },
      890
    );

    extension.registerSetting(
      {
        setting: 'zephyrisle.ai-audit.suspend_days',
        label: t('zephyrisle-ai-audit.admin.settings.suspend_days'),
        type: 'number',
      },
      880
    );
  });
}

app.initializers.add('zephyrisle-ai-audit', () => {
  registerSettings();
});

export { default as extend } from './extend';