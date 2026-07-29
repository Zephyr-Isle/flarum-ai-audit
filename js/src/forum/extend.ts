import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import checkSuicideAlert from './initializers/checkSuicideAlert';

app.initializers.add('ai-audit-suicide-check', checkSuicideAlert);

export default [...commonExtend];

