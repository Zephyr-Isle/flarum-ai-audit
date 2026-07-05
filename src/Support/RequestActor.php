<?php

namespace ZephyrIsle\AiAudit\Support;

use Flarum\User\User;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class RequestActor
{
    /**
     * Resolve a user from the given (possibly partial) actor value.
     */
    public static function resolve(mixed $actor): ?User
    {
        return $actor instanceof User ? $actor : null;
    }

    /**
     * Resolve the current actor from a server request in a way that works
     * across Flarum 1.x and 2.0.
     *
     * Flarum 2.0 stores the actor on the `actorReference` request attribute,
     * while older 1.x versions primarily expose the legacy `actor` attribute.
     * The auth middleware is expected to populate the actor before the
     * controller runs, but reading `actor` directly can yield `null` on 2.0
     * and cause spurious 401 responses.
     */
    public static function getActor(ServerRequestInterface $request): ?User
    {
        $reference = $request->getAttribute('actorReference');
        if ($reference !== null && method_exists($reference, 'getActor')) {
            $actor = $reference->getActor();
            if ($actor instanceof User) {
                return $actor;
            }
        }

        return self::resolve($request->getAttribute('actor'));
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
