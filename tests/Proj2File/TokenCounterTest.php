<?php

declare(strict_types=1);

namespace Foreline\Tests\Proj2File;

use Foreline\Proj2File\TokenCounter;
use PHPUnit\Framework\TestCase;

class TokenCounterTest extends TestCase
{
    public function testEmptyString(): void
    {
        $this->assertSame(0, TokenCounter::getCount(''));
    }

    public function testSingleWord(): void
    {
        $this->assertGreaterThan(0, TokenCounter::getCount('hello'));
    }

    public function testMultipleWords(): void
    {
        $count = TokenCounter::getCount('hello world');
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testMultilineText(): void
    {
        $text = "line one\nline two\nline three";
        $count = TokenCounter::getCount($text);
        $this->assertGreaterThan(0, $count);
    }
}
