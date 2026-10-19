<?php

declare(strict_types=1);

namespace BannerManagerKit\Tests\Search;

use PHPUnit\Framework\TestCase;
use BannerManagerKit\Search\Column\Set\Slides;
use PHPUnit\Framework\Attributes\DataProvider;
use BannerManagerKit\Search\Column\Set\Banners;
use BannerManagerKit\Search\Column\Set\SlideTypes;

final class ColumnSetTest extends TestCase
{
    public static function sets(): array
    {
        return [
            'banners' => [Banners::class],
            'slides' => [Slides::class],
            'slide types' => [SlideTypes::class],
        ];
    }

    #[DataProvider('sets')]
    public function testDefaultSortIsNameAscending(string $setClass): void
    {
        $default = (new $setClass())->getDefaultSortColumn();

        $this->assertNotNull($default);
        $this->assertSame('asc', $default->getColumnDefaultSortDirection());
        $this->assertSame('getName', $default->getColumnCallback());
    }

    public static function expectedColumns(): array
    {
        return [
            'banners' => [Banners::class, ['getName', 'getDateCreatedString']],
            'slides' => [Slides::class, ['getName', 'getDateCreatedString']],
            'slide types' => [SlideTypes::class, ['getName', 'getSlidesCount']],
        ];
    }

    #[DataProvider('expectedColumns')]
    public function testSetExposesExpectedColumns(string $setClass, array $expected): void
    {
        $callbacks = array_map(static fn($c) => $c->getColumnCallback(), (new $setClass())->getColumns());

        $this->assertSame($expected, $callbacks);
    }
}
