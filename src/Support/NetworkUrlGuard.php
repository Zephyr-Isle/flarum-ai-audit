<?php

namespace ZephyrIsle\AiAudit\Support;

class NetworkUrlGuard
{
    public static function isSafeExternalHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (self::isBlockedHostname($host)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        foreach (self::resolveIps($host) as $ip) {
            if (!self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private static function isBlockedHostname(string $host): bool
    {
        if ($host === 'localhost' || $host === 'localhost.localdomain') {
            return true;
        }

        return preg_match('/(?:^|\.)((?:local|internal|localhost|test|home|arpa))$/', $host) === 1;
    }

    private static function resolveIps(string $host): array
    {
        $ips = gethostbynamel($host);

        if (!is_array($ips)) {
            return [];
        }

        return array_values(array_unique(array_filter($ips, fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP))));
    }

    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
