<?php

namespace ZephyrIsle\AiAudit\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use ZephyrIsle\AiAudit\Support\NetworkUrlGuard;

class NetworkUrlGuardTest extends TestCase
{
    public function testItAllowsPublicHttpAndHttpsUrls(): void
    {
        $this->assertTrue(NetworkUrlGuard::isSafeExternalHttpUrl('https://8.8.8.8/example.png'));
        $this->assertTrue(NetworkUrlGuard::isSafeExternalHttpUrl('http://1.1.1.1/image.jpg'));
    }

    public function testItBlocksLocalPrivateOrUnsupportedTargets(): void
    {
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('http://127.0.0.1/image.png'));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('http://localhost/image.png'));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('http://192.168.1.10/image.png'));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('ftp://8.8.8.8/image.png'));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('https://user:pass@8.8.8.8/image.png'));
    }

    public function testItBlocksInternalHostnames(): void
    {
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('http://example.local/image.png'));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('http://test.internal/image.png'));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('http://my.home/image.png'));
    }

    public function testItHandlesInvalidUrls(): void
    {
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('not-a-url'));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl(''));
        $this->assertFalse(NetworkUrlGuard::isSafeExternalHttpUrl('http://'));
    }
}
