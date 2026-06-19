<?php

namespace ZephyrIsle\AiAudit\Support;

use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface;

class RequestActor
{
    public static function require(ServerRequestInterface $request, string $ability): User
    {
        $actor = $request->getAttribute('actor');

        if (!$actor instanceof User) {
            throw new NotAuthenticatedException();
        }

        $actor->assertCan($ability);

        return $actor;
    }
}
