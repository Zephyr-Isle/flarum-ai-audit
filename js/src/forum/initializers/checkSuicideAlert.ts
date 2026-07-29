import app from 'flarum/forum/app';
import SuicideWarmModal from '../components/SuicideWarmModal';
export default function () {
  Promise.resolve().then(() => {
    if (!app.session?.user) return;

    app.store.find('notifications').then((notifications: any[]) => {
      const suicideAlerts = notifications.filter((n: any) => {
        const type = n.attribute('type') || n.data?.attributes?.type || n.contentType?.();
        return type === 'suicideSelfAlert';
      });

      if (suicideAlerts.length > 0) {
        app.modal.show(SuicideWarmModal);
      }
    });
  });
}
