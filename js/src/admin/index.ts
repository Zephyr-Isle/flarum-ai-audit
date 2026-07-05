import app from 'flarum/admin/app';
import registerAdminSettings from './settings';

app.initializers.add('zephyrisle-ai-audit', () => {
  registerAdminSettings();
});

export { default as extend } from './extend';