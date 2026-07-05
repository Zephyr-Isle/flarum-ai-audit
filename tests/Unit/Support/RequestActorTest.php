<?php

namespace ZephyrIsle\AiAudit\Tests\Unit\Support;

use Flarum\User\User;
use PHPUnit\Framework\TestCase;
use ZephyrIsle\AiAudit\Support\RequestActor;

class RequestActorTest extends TestCase
{
    public function testItResolvesOnlyRealUsers(): void
    {
        $user = new User();

        $this->assertSame($user, RequestActor::resolve($user));
        $this->assertNull(RequestActor::resolve(null));
        $this->assertNull(RequestActor::resolve(new \stdClass()));
    }

    public function testItBuildsStableApiErrors(): void
    {
        $notAuthenticated = RequestActor::notAuthenticatedResponse();
        $permissionDenied = RequestActor::permissionDeniedResponse();

        $this->assertSame(401, $notAuthenticated->getStatusCode());
        $this->assertStringContainsString('"code":"not_authenticated"', (string) $notAuthenticated->getBody());

        $this->assertSame(403, $permissionDenied->getStatusCode());
        $this->assertStringContainsString('"code":"permission_denied"', (string) $permissionDenied->getBody());
    }

    public function testItReadsActorFromActorReferenceForFlarum2(): void
    {
        $user = new User();
        $reference = new class($user) {
            private User $user;
            public function __construct(User $user)
            {
                $this->user = $user;
            }
            public function getActor(): User
            {
                return $this->user;
            }
        };

        $request = new \ZephyrIsle\AiAudit\Tests\Stub\ServerRequestStub();
        $request->attributes['actorReference'] = $reference;

        $this->assertSame($user, RequestActor::getActor($request));
    }

    public function testItFallsBackToLegacyActorAttribute(): void
    {
        $user = new User();
        $request = new \ZephyrIsle\AiAudit\Tests\Stub\ServerRequestStub();
        $request->attributes['actor'] = $user;

        $this->assertSame($user, RequestActor::getActor($request));
    }

    public function testItReturnsNullWhenNoActorIsAvailable(): void
    {
        $request = new \ZephyrIsle\AiAudit\Tests\Stub\ServerRequestStub();

        $this->assertNull(RequestActor::getActor($request));
    }
}
