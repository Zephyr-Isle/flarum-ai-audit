<?php

namespace ZephyrIsle\AiAudit\Support;

use Flarum\User\User;
use Laminas\Diactoros\Response\JsonResponse;

class RequestActor
{
    public static function resolve(mixed $actor): ?User
    {
        return $actor instanceof User ? $actor : null;
    }

    public static function notAuthenticatedResponse(): JsonResponse
    {
        return self::errorResponse(401, 'not_authenticated', 'Authentication required.');
    }

    public static function permissionDeniedResponse(): JsonResponse
    {
        return self::errorResponse(403, 'permission_denied', 'You do not have permission to perform this action.');
    }

    private static function errorResponse(int $status, string $code, string $detail): JsonResponse
    {
        return new JsonResponse([
            'errors' => [[
                'status' => (string) $status,
                'code' => $code,
                'detail' => $detail,
            ]],
        ], $status);
    }
}
