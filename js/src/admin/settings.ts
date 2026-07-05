import app from 'flarum/admin/app';
import m from 'mithril';

const t = (key: string) => app.translator.trans(key, {}, true);
let registered = false;

export default function registerAdminSettings() {
  if (registered) return;

  const extensionId = 'zephyrisle-ai-audit';

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.api_endpoint',
      label: t('zephyrisle-ai-audit.admin.settings.api_endpoint'),
      type: 'text',
    },
    1000
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.api_key',
      label: t('zephyrisle-ai-audit.admin.settings.api_key'),
      type: 'password',
    },
    990
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.model',
      label: t('zephyrisle-ai-audit.admin.settings.model'),
      type: 'text',
    },
    980
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.temperature',
      label: t('zephyrisle-ai-audit.admin.settings.temperature'),
      type: 'number',
    },
    970
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.max_tokens',
      label: t('zephyrisle-ai-audit.admin.settings.max_tokens'),
      type: 'number',
    },
    960
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.timeout',
      label: t('zephyrisle-ai-audit.admin.settings.timeout'),
      type: 'number',
    },
    950
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.system_prompt',
      label: t('zephyrisle-ai-audit.admin.settings.system_prompt'),
      type: 'textarea',
    },
    940
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.pre_approve_enabled',
      label: t('zephyrisle-ai-audit.admin.settings.pre_approve_enabled'),
      type: 'boolean',
    },
    930
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.download_images',
      label: t('zephyrisle-ai-audit.admin.settings.download_images'),
      type: 'boolean',
    },
    920
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.image_download_timeout',
      label: t('zephyrisle-ai-audit.admin.settings.image_download_timeout'),
      type: 'number',
    },
    910
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.review_threshold',
      label: t('zephyrisle-ai-audit.admin.settings.review_threshold'),
      type: 'number',
    },
    900
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.action_threshold',
      label: t('zephyrisle-ai-audit.admin.settings.action_threshold'),
      type: 'number',
    },
    890
  );

  app.extensionData.for(extensionId).registerSetting(
    {
      setting: 'zephyrisle.ai-audit.suspend_days',
      label: t('zephyrisle-ai-audit.admin.settings.suspend_days'),
      type: 'number',
    },
    880
  );

  registered = true;
  m.redraw();
}
