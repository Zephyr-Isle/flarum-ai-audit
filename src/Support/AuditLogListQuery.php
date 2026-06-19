<?php

namespace ZephyrIsle\AiAudit\Support;

use Illuminate\Support\Arr;

class AuditLogListQuery
{
    public function __construct(
        public readonly array $filters,
        public readonly string $sort,
        public readonly string $direction,
        public readonly int $limit,
        public readonly int $offset
    ) {
    }

    public static function fromArray(array $params): self
    {
        $filters = Arr::get($params, 'filter', []);

        $sort = (string) Arr::get($params, 'sort', '-createdAt');
        $sortField = ltrim($sort, '-+');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        return new self(
            [
                'subjectType' => self::stringOrNull($filters['subjectType'] ?? null),
                'status' => self::stringOrNull($filters['status'] ?? null),
                'ownerId' => self::positiveIntOrNull($filters['ownerId'] ?? null),
                'minRisk' => self::clampedFloatOrNull($filters['minRisk'] ?? null, 0.0, 1.0),
            ],
            $sortField,
            $direction,
            self::clampedInt(Arr::get($params, 'page.limit', 20), 1, 100),
            max(0, (int) Arr::get($params, 'page.offset', 0))
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private static function positiveIntOrNull(mixed $value): ?int
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private static function clampedFloatOrNull(mixed $value, float $min, float $max): ?float
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            return null;
        }

        return max($min, min($max, (float) $value));
    }

    private static function clampedInt(mixed $value, int $min, int $max): int
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            return $min;
        }

        return max($min, min($max, (int) $value));
    }
}
