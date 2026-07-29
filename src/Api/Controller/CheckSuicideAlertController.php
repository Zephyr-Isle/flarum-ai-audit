<?php

namespace ZephyrIsle\AiAudit\Api\Controller;

use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZephyrIsle\AiAudit\Support\RequestActor;

class CheckSuicideAlertController implements RequestHandlerInterface
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestActor::getActor($request);

        if (!$actor) {
            return RequestActor::notAuthenticatedResponse();
        }

        $hasAlert = $this->db->table('notifications')
            ->where('user_id', $actor->id)
            ->where('type', 'suicideSelfAlert')
            ->exists();

        return new JsonResponse(['hasAlert' => $hasAlert]);
    }
}
