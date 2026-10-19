<?php

declare(strict_types=1);

namespace BannerManagerKit\Tests\Entity;

use PHPUnit\Framework\TestCase;
use BannerManagerKit\Entity\Slide;
use BannerManagerKit\Entity\Banner;
use BannerManagerKit\Entity\SlidePosition;

final class SlidePositionTest extends TestCase
{
    public function testAccessors(): void
    {
        $banner = new Banner();
        $slide = new Slide();

        $position = (new SlidePosition())
            ->setBanner($banner)
            ->setSlide($slide)
            ->setPosition(3);

        $this->assertSame($banner, $position->getBanner());
        $this->assertSame($slide, $position->getSlide());
        $this->assertSame(3, $position->getPosition());
    }

    public function testBannerAndSlideCanBeCleared(): void
    {
        $position = (new SlidePosition())->setBanner(new Banner())->setSlide(new Slide());

        $position->setBanner()->setSlide();

        $this->assertNull($position->getBanner());
        $this->assertNull($position->getSlide());
    }

    public function testClearBannerCacheWithoutBannerDoesNothing(): void
    {
        (new SlidePosition())->clearBannerCache();

        $this->addToAssertionCount(1);
    }

    public function testClearBannerCacheQueuesTheBannerForClearing(): void
    {
        $banner = $this->createMock(Banner::class);
        $banner->expects($this->once())->method('queueCacheClear');

        (new SlidePosition())->setBanner($banner)->clearBannerCache();
    }
}
