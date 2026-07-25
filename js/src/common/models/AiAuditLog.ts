import Model from 'flarum/common/Model';

export default class AiAuditLog extends Model {
  subjectType = Model.attribute<string>('subjectType');
  subjectId = Model.attribute<number>('subjectId');
  ownerId = Model.attribute<number>('ownerId');
  actorId = Model.attribute<number>('actorId');
  status = Model.attribute<string>('status');
  risk = Model.attribute<number>('risk');
  severity = Model.attribute<number>('severity');
  actions = Model.attribute<string[]>('actions');
  conclusion = Model.attribute<string>('conclusion');
  snapshot = Model.attribute<Record<string, unknown>>('snapshot');
  analysis = Model.attribute<Record<string, unknown>>('analysis');
  error = Model.attribute<string>('error');
  retryCount = Model.attribute<number>('retryCount');
  createdAt = Model.attribute<string>('createdAt');
  updatedAt = Model.attribute<string>('updatedAt');
}

