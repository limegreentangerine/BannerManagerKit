<?php

declare(strict_types=1);

namespace BannerManagerKit\Tests\Entity;

use PHPUnit\Framework\TestCase;
use BannerManagerKit\Entity\Banner;
use BannerManagerKit\Entity\SlidePosition;

final class BannerTest extends TestCase
{
    public function testSettersAreFluentAndStoreValues(): void
    {
        $banner = (new Banner())->setHandle('home')->setName('Home');

        $this->assertSame('home', $banner->getHandle());
        $this->assertSame('Home', $banner->getName());
    }

    public function testDisplayOptionsDefaultToShownWhenUnset(): void
    {
        $banner = new Banner();

        $this->assertTrue($banner->getShowDots());
        $this->assertTrue($banner->getShowArrows());
        $this->assertTrue($banner->getShowDotsMobile());
        $this->assertTrue($banner->getShowArrowsMobile());
        $this->assertSame('slide', $banner->getSlideAnimation());
    }

    public function testDisplayOptionsCanBeDisabledIndependently(): void
    {
        $banner = (new Banner())->setShowDots(false)->setShowArrowsMobile(false);

        $this->assertFalse($banner->getShowDots());
        $this->assertTrue($banner->getShowArrows());
        $this->assertTrue($banner->getShowDotsMobile());
        $this->assertFalse($banner->getShowArrowsMobile());
    }

    public function testSlideAnimationAcceptsOnlyKnownValues(): void
    {
        $banner = new Banner();

        $banner->setSlideAnimation('fade');
        $this->assertSame('fade', $banner->getSlideAnimation());

        $banner->setSlideAnimation('spin');
        $this->assertSame('slide', $banner->getSlideAnimation());
    }

    public function testFallbackBannerCanBeSetAndCleared(): void
    {
        $banner = new Banner();
        $fallback = new Banner();

        $banner->setFallbackBanner($fallback);
        $this->assertSame($fallback, $banner->getFallbackBanner());

        $banner->setFallbackBanner();
        $this->assertNull($banner->getFallbackBanner());
    }

    public function testUnscheduledBannerIsAlwaysActive(): void
    {
        $this->assertTrue((new Banner())->isCurrentlyActive());
    }

    public function testScheduleWindowBoundaries(): void
    {
        $banner = (new Banner())
            ->setScheduleStart(new \DateTime('2026-01-10'))
            ->setScheduleEnd(new \DateTime('2026-01-20'));

        $this->assertFalse($banner->isCurrentlyActive(new \DateTime('2026-01-09')));
        $this->assertTrue($banner->isCurrentlyActive(new \DateTime('2026-01-10')));
        $this->assertTrue($banner->isCurrentlyActive(new \DateTime('2026-01-15')));
        $this->assertTrue($banner->isCurrentlyActive(new \DateTime('2026-01-20')));
        $this->assertFalse($banner->isCurrentlyActive(new \DateTime('2026-01-21')));
    }

    public function testOpenEndedSchedules(): void
    {
        $startOnly = (new Banner())->setScheduleStart(new \DateTime('2026-01-10'));
        $this->assertFalse($startOnly->isCurrentlyActive(new \DateTime('2026-01-01')));
        $this->assertTrue($startOnly->isCurrentlyActive(new \DateTime('2030-01-01')));

        $endOnly = (new Banner())->setScheduleEnd(new \DateTime('2026-01-10'));
        $this->assertTrue($endOnly->isCurrentlyActive(new \DateTime('2020-01-01')));
        $this->assertFalse($endOnly->isCurrentlyActive(new \DateTime('2026-01-11')));
    }

    public function testAddSlidePositionLinksBothSidesWithoutDuplicates(): void
    {
        $banner = new Banner();
        $position = new SlidePosition();

        $banner->addSlidePosition($position)->addSlidePosition($position);

        $this->assertSame(1, $banner->getSlidePositionsCount());
        $this->assertSame([$position], $banner->getSlidePositions());
        $this->assertSame($banner, $position->getBanner());
    }

    public function testRemoveSlidePositionUnlinksBothSides(): void
    {
        $banner = new Banner();
        $position = new SlidePosition();
        $banner->addSlidePosition($position);

        $banner->removeSlidePosition($position);

        $this->assertSame(0, $banner->getSlidePositionsCount());
        $this->assertNull($position->getBanner());
    }

    public function testRemovingUnknownSlidePositionIsANoOp(): void
    {
        $banner = new Banner();
        $position = (new SlidePosition())->setBanner($banner);

        $banner->removeSlidePosition($position);

        $this->assertSame($banner, $position->getBanner());
    }
}
