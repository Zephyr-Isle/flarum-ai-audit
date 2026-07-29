import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

export default class SuicideWarmModal extends Modal {
  className() {
    return 'SuicideWarmModal Modal--fullScreen';
  }

  title() {
    return m('h2.SuicideWarmModal-title', app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.title'));
  }

  content() {
    return (
      <div className="SuicideWarmModal-body">
        <div className="SuicideWarmModal-flower">🌺</div>
        <p className="SuicideWarmModal-message">
          {app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.message')}
        </p>
        <p className="SuicideWarmModal-encouragement">
          {app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.encouragement')}
        </p>
        <div className="SuicideWarmModal-resources">
          <p>{app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.resources_title')}</p>
          <ul>
            <li>{app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.resource_line1')}</li>
            <li>{app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.resource_line2')}</li>
            <li>{app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.resource_line3')}</li>
          </ul>
        </div>
        <div className="SuicideWarmModal-actions">
          <Button className="Button Button--primary SuicideWarmModal-dismiss" onclick={this.close.bind(this)}>
            {app.translator.trans('zephyrisle-ai-audit.forum.suicide_warm_modal.dismiss')}
          </Button>
        </div>
      </div>
    );
  }

  onsubmit() {
    this.close();
  }
}
