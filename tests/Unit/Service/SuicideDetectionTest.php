<?php

namespace ZephyrIsle\AiAudit\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

class SuicideDetectionTest extends TestCase
{
    private string $chinesePattern;
    private string $englishPattern;

    protected function setUp(): void
    {
        $this->chinesePattern = '/(?:自杀|轻生|自残|割腕|上吊|跳楼|厌世|遗书|不想活了|活不下去|不想活啦|结束生命|告别这个世界|最后(?:的)?(?:时光|旅程|一程)|没有(?:活下去|生存)的(?:勇气|希望|意义)|一死了之|想不开想死|想结束自己的生命|对(?:生活|人生|世界)失去了(?:希望|信心))/iu';
        $this->englishPattern = '/\b(kill\s+myself|committed?\s+suicide|suicide|suicidal|self[\-\s]harm\b|end\s+my\s+life|want\s+to\s+die|better\s+off\s+dead|no\s+reason\s+to\s+live)\b/i';
    }

    /** @dataProvider positiveChineseProvider */
    public function testChinesePositive(string $text, string $description): void
    {
        $this->assertSame(1, preg_match($this->chinesePattern, $text), "Should match: $description");
    }

    /** @dataProvider negativeChineseProvider */
    public function testChineseNegative(string $text, string $description): void
    {
        $this->assertSame(0, preg_match($this->chinesePattern, $text), "Should NOT match: $description");
    }

    /** @dataProvider positiveEnglishProvider */
    public function testEnglishPositive(string $text, string $description): void
    {
        $this->assertSame(1, preg_match($this->englishPattern, $text), "Should match: $description");
    }

    public static function positiveChineseProvider(): array
    {
        return [
            ['我想自杀', '自杀 direct'],
            ['她有轻生念头', '轻生'],
            ['他在自残', '自残'],
            ['她割腕了', '割腕'],
            ['他要上吊', '上吊'],
            ['他想跳楼', '跳楼'],
            ['他最近很厌世', '厌世'],
            ['写了遗书', '遗书'],
            ['我真的不想活了', '不想活了完整短语'],
            ['我活不下去了', '活不下去'],
            ['我不想活啦', '不想活啦'],
            ['我想结束生命', '结束生命'],
            ['准备告别这个世界', '告别这个世界'],
            ['最后的时光', '最后的时光'],
            ['最后一程', '最后一程'],
            ['没有活下去的勇气', '没有活下去的勇气'],
            ['没有生存的希望', '没有生存的希望'],
            ['一死了之', '一死了之'],
            ['想不开想死', '想不开想死完整短语'],
            ['想结束自己的生命', '想结束自己的生命完整表达'],
            ['对生活失去了希望', '对生活失去了希望'],
            ['对人生失去了信心', '对人生失去了信心'],
            ['对世界失去了希望', '对世界失去了希望'],
        ];
    }

    public static function negativeChineseProvider(): array
    {
        return [
            ['我想死你了', '想念的我想死你了，常见误报'],
            ['想死你啊', '方言表达想念'],
            ['累死了', '程度补语'],
            ['笑死了', '程度补语'],
            ['饿死了', '程度补语'],
            ['热死了', '程度补语'],
            ['烦死了', '程度补语'],
            ['高兴死了', '程度补语'],
            ['困死了', '程度补语'],
            ['要死了', '感叹'],
            ['去死吧', '咒骂但非自伤'],
            ['死不了', '否定表达'],
            ['你死定了', '威胁他人'],
            ['跟你拼了', '愤怒表达非自伤'],
            ['我绝望了这游戏', '日常绝望表达'],
            ['了结这件事', '了结事务'],
            ['不想活了其实也没那么严重', '上下文否定'],
        ];
    }

    public static function positiveEnglishProvider(): array
    {
        return [
            ['I want to kill myself', 'kill myself'],
            ['commit suicide', 'commit suicide'],
            ['committed suicide', 'committed suicide past tense'],
            ['suicidal thoughts', 'suicidal'],
            ['suicide', 'suicide standalone'],
            ['self harm', 'self harm spaced'],
            ['self-harm', 'self-harm hyphenated'],
            ['end my life', 'end my life'],
            ['I want to die', 'want to die'],
            ['better off dead', 'better off dead'],
            ['no reason to live', 'no reason to live'],
        ];
    }
}
