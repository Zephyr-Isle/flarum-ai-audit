<?php

namespace ZephyrIsle\AiAudit\Tests\Stub;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Minimal PSR-7 ServerRequestInterface stub for unit tests.
 * Only the methods exercised by RequestActor are implemented.
 */
class ServerRequestStub implements ServerRequestInterface
{
    /** @var array<string, mixed> */
    public array $attributes = [];

    public function getAttribute($name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }

    // The remaining PSR-7 methods are unused by RequestActor; we provide
    // harmless throw-away implementations to satisfy the interface.

    public function getProtocolVersion(): string { return '1.1'; }
    public function withProtocolVersion($version): ServerRequestInterface { return $this; }
    public function getHeaders(): array { return []; }
    public function hasHeader($name): bool { return false; }
    public function getHeader($name): array { return []; }
    public function getHeaderLine($name): string { return ''; }
    public function withHeader($name, $value): ServerRequestInterface { return $this; }
    public function withAddedHeader($name, $value): ServerRequestInterface { return $this; }
    public function withoutHeader($name): ServerRequestInterface { return $this; }
    public function getBody(): StreamInterface { throw new \RuntimeException('not implemented'); }
    public function withBody(StreamInterface $body): ServerRequestInterface { return $this; }
    public function getRequestTarget(): string { return '/'; }
    public function withRequestTarget($requestTarget): ServerRequestInterface { return $this; }
    public function getMethod(): string { return 'GET'; }
    public function withMethod($method): ServerRequestInterface { return $this; }
    public function getUri(): UriInterface { throw new \RuntimeException('not implemented'); }
    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface { return $this; }
    public function getServerParams(): array { return []; }
    public function getCookieParams(): array { return []; }
    public function withCookieParams(array $cookies): ServerRequestInterface { return $this; }
    public function getQueryParams(): array { return []; }
    public function withQueryParams(array $query): ServerRequestInterface { return $this; }
    public function getUploadedFiles(): array { return []; }
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface { return $this; }
    public function getParsedBody() { return null; }
    public function withParsedBody($data): ServerRequestInterface { return $this; }
}
