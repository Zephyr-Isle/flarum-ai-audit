<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use ZephyrIsle\AiAudit\Model\AuditLog;

class ShowAuditLogController extends AbstractShowController
{
    public $serializer = 'ZephyrIsle\AiAudit\Api\Serializer\AuditLogSerializer';

    protected function data(ServerRequestInterface $request, Document $document): AuditLog
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();
        $actor->assertCan('zephyrisle-ai-audit.viewAuditLogs');

        $id = $request->getAttribute('id');

        return AuditLog::findOrFail($id);
    }
}
