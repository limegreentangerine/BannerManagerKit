<?php

declare(strict_types=1);

namespace BannerManagerKit\Tests\Entity;

use PHPUnit\Framework\TestCase;
use BannerManagerKit\Entity\Slide;
use BannerManagerKit\Entity\Banner;
use BannerManagerKit\Entity\SlidePosition;
use BannerManagerKit\Doctrine\FlushListener;

final class BannerTest extends TestCase
{
    private function expiredBanner(): Banner
    {
        return (new Banner())->setScheduleEnd(new \DateTime('2026-01-01'));
    }
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

    public function testFallbackChainStartsWithTheBannerItself(): void
    {
        $banner = new Banner();

        $this->assertSame([$banner], $banner->getFallbackChain());
    }

    public function testFallbackChainFollowsFallbacksInOrder(): void
    {
        $c = new Banner();
        $b = (new Banner())->setFallbackBanner($c);
        $a = (new Banner())->setFallbackBanner($b);

        $this->assertSame([$a, $b, $c], $a->getFallbackChain());
    }

    public function testFallbackLoopTerminates(): void
    {
        $a = new Banner();
        $b = (new Banner())->setFallbackBanner($a);
        $a->setFallbackBanner($b);

        $this->assertSame([$a, $b], $a->getFallbackChain());
    }

    public function testResolveActiveReturnsTheBannerItselfWhenActive(): void
    {
        $banner = (new Banner())->setFallbackBanner(new Banner());

        $this->assertSame($banner, $banner->resolveActive(new \DateTime('2026-02-01')));
    }

    public function testResolveActiveSkipsInactiveBannersToTheFirstActiveFallback(): void
    {
        $live = new Banner();
        $second = $this->expiredBanner()->setFallbackBanner($live);
        $first = $this->expiredBanner()->setFallbackBanner($second);

        $this->assertSame($live, $first->resolveActive(new \DateTime('2026-02-01')));
    }

    public function testResolveActiveStopsAtTheFirstActiveBanner(): void
    {
        $later = new Banner();
        $active = (new Banner())->setFallbackBanner($later);
        $expired = $this->expiredBanner()->setFallbackBanner($active);

        $this->assertSame($active, $expired->resolveActive(new \DateTime('2026-02-01')));
    }

    public function testResolveActiveIsNullWhenNothingInTheChainIsActive(): void
    {
        $now = new \DateTime('2026-02-01');

        $this->assertNull($this->expiredBanner()->resolveActive($now));
        $this->assertNull($this->expiredBanner()->setFallbackBanner($this->expiredBanner())->resolveActive($now));
    }

    public function testResolveActiveTerminatesOnAnInactiveFallbackLoop(): void
    {
        $a = $this->expiredBanner();
        $b = $this->expiredBanner()->setFallbackBanner($a);
        $a->setFallbackBanner($b);

        $this->assertNull($a->resolveActive(new \DateTime('2026-02-01')));
    }

    public function testQueueCacheClearOnlyRecordsTheBannerWithoutTouchingTheCache(): void
    {
        $banner = new Banner();
        (new \ReflectionProperty($banner, 'id'))->setValue($banner, 'banner-1');

        // A cache clear would reach for Concrete's container, which isn't
        // booted here; queueing must defer that until the flush has committed.
        $banner->queueCacheClear();
        $banner->queueCacheClear();

        $pending = (new \ReflectionProperty(Banner::class, 'pendingCacheClears'))->getValue();
        $this->assertSame(['banner-1' => true], $pending);

        (new \ReflectionProperty(Banner::class, 'pendingCacheClears'))->setValue(null, []);
    }

    public function testClearPendingCachesWithNothingQueuedDoesNothing(): void
    {
        Banner::clearPendingCaches();
        (new FlushListener())->postFlush();

        $this->addToAssertionCount(1);
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

    public function testCreateDuplicateCopiesSettingsAndSharesSlides(): void
    {
        $fallback = new Banner();
        $source = (new Banner())
            ->setName('Home')
            ->setHandle('home')
            ->setScheduleStart(new \DateTime('2026-01-10'))
            ->setScheduleEnd(new \DateTime('2026-01-20'))
            ->setFallbackBanner($fallback)
            ->setShowDots(false)
            ->setSlideAnimation('fade');
        $slide = new Slide();
        $source->addSlidePosition((new SlidePosition())->setSlide($slide)->setPosition(3));

        $copy = $source->createDuplicate('Copy of Home', 'copy-of-home');

        $this->assertNotSame($source, $copy);
        $this->assertSame('Copy of Home', $copy->getName());
        $this->assertSame('copy-of-home', $copy->getHandle());
        $this->assertEquals($source->getScheduleStart(), $copy->getScheduleStart());
        $this->assertNotSame($source->getScheduleStart(), $copy->getScheduleStart());
        $this->assertSame($fallback, $copy->getFallbackBanner());
        $this->assertFalse($copy->getShowDots());
        $this->assertSame('fade', $copy->getSlideAnimation());

        $positions = $copy->getSlidePositions();
        $this->assertCount(1, $positions);
        $this->assertSame($slide, $positions[0]->getSlide());
        $this->assertSame($copy, $positions[0]->getBanner());
        $this->assertSame(3, $positions[0]->getPosition());
        $this->assertSame(1, $source->getSlidePositionsCount());
    }
}
