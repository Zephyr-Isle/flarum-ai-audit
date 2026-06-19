<?php

namespace ZephyrIsle\AiAudit\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use ZephyrIsle\AiAudit\Support\AuditLogListQuery;

class AuditLogListQueryTest extends TestCase
{
    public function testItNormalizesFiltersAndPaging(): void
    {
        $query = AuditLogListQuery::fromArray([
            'filter' => [
                'subjectType' => ' post ',
                'status' => 'failed',
                'ownerId' => '42',
                'minRisk' => '2.5',
            ],
            'sort' => '-risk',
            'page' => [
                'limit' => '999',
                'offset' => '-12',
            ],
        ]);

        $this->assertSame('post', $query->filters['subjectType']);
        $this->assertSame('failed', $query->filters['status']);
        $this->assertSame(42, $query->filters['ownerId']);
        $this->assertSame(1.0, $query->filters['minRisk']);
        $this->assertSame('risk', $query->sort);
        $this->assertSame('desc', $query->direction);
        $this->assertSame(100, $query->limit);
        $this->assertSame(0, $query->offset);
    }

    public function testItFallsBackForInvalidInput(): void
    {
        $query = AuditLogListQuery::fromArray([
            'filter' => [
                'subjectType' => [],
                'status' => '   ',
                'ownerId' => 'abc',
                'minRisk' => null,
            ],
            'sort' => '+createdAt',
            'page' => [
                'limit' => 'oops',
                'offset' => '5',
            ],
        ]);

        $this->assertNull($query->filters['subjectType']);
        $this->assertNull($query->filters['status']);
        $this->assertNull($query->filters['ownerId']);
        $this->assertNull($query->filters['minRisk']);
        $this->assertSame('createdAt', $query->sort);
        $this->assertSame('asc', $query->direction);
        $this->assertSame(1, $query->limit);
        $this->assertSame(5, $query->offset);
    }
}
