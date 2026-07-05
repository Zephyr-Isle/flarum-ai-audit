<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use ZephyrIsle\AiAudit\Model\AuditLog;
use ZephyrIsle\AiAudit\Support\AuditLogListQuery;

class ListAuditLogsController extends AbstractListController
{
    public $serializer = 'ZephyrIsle\AiAudit\Api\Serializer\AuditLogSerializer';

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();
        $actor->assertCan('zephyrisle-ai-audit.viewAuditLogs');

        $query = AuditLogListQuery::fromArray($request->getQueryParams());

        $q = AuditLog::query();

        if ($query->filters['subjectType'] !== null) {
            $q->where('subject_type', $query->filters['subjectType']);
        }
        if ($query->filters['status'] !== null) {
            $q->where('status', $query->filters['status']);
        }
        if ($query->filters['ownerId'] !== null) {
            $q->where('owner_id', $query->filters['ownerId']);
        }
        if ($query->filters['minRisk'] !== null) {
            $q->where('risk', '>=', $query->filters['minRisk']);
        }

        $map = [
            'createdAt' => 'created_at',
            'risk' => 'risk',
            'status' => 'status',
        ];
        if (isset($map[$query->sort])) {
            $q->orderBy($map[$query->sort], $query->direction);
        }

        $document->addMeta('total', (clone $q)->count());

        return $q->skip($query->offset)->take($query->limit)->get();
    }
}
