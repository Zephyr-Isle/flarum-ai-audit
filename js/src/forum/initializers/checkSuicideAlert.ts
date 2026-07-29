import app from 'flarum/forum/app';
import SuicideWarmModal from '../components/SuicideWarmModal';
export default function () {
  Promise.resolve().then(() => {
    if (!app.session?.user) return;

    const apiUrl = app.forum.attribute<string>('apiUrl');
    app.request({
      method: 'GET',
      url: `${apiUrl}/ai-audit/suicide-check`,
    }).then((resp: any) => {
      if (resp?.hasAlert) {
        app.modal.show(SuicideWarmModal);
      }
    }).catch(() => {});
  });
}
