<?php

namespace BannerManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 * @ORM\Table(
 *      name="bmBanners"
 * )
 * options={
 *      "comment": "List of banners available for display"
 * }
 */
class Banner extends UpdatedGuidEntity
{
    //
    //  STANDARD FIELDS
    //

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=false, options={"comment": "Feed handle"})
     */
    protected $handle;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"comment": "Feed name"})
     */
    protected $name;

    //
    //  MAPPED FIELDS
    //

    /**
     * @ORM\OneToMany(targetEntity="SlidePosition", mappedBy="banner", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $slidePositions;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"comment": "Schedule start date/time"})
     */
    protected $scheduleStart;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"comment": "Schedule end date/time"})
     */
    protected $scheduleEnd;

    /**
     * @ORM\ManyToOne(targetEntity="Banner")
     * @ORM\JoinColumn(name="fallbackBannerId", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $fallbackBanner;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"comment": "Show slideshow dot navigation"})
     */
    protected $showDots;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"comment": "Show slideshow prev/next arrows"})
     */
    protected $showArrows;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"comment": "Show slideshow dot navigation on mobile"})
     */
    protected $showDotsMobile;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"comment": "Show slideshow prev/next arrows on mobile"})
     */
    protected $showArrowsMobile;

    /**
     * @ORM\Column(type="string", length=20, nullable=true, options={"comment": "Slideshow transition: slide or fade"})
     */
    protected $slideAnimation;

    public function __construct()
    {
        parent::__construct();
        $this->slidePositions = new ArrayCollection();
    }

    /**
     * Expire the cached output of every bm_banner block that renders this
     * banner, directly or by falling back to it, so guests see a change
     * straight away instead of when the block's cache lifetime runs out.
     * Concrete keeps block output in CollectionVersionBlocksOutputCache,
     * keyed by block, so one UPDATE covers every page the blocks sit on.
     */
    protected function expireBlockOutputCache(): void
    {
        static $expired = [];
        if (isset($expired[$this->getID()])) {
            return;
        }
        $expired[$this->getID()] = true;

        $db = \Core::make('database')->connection();

        // Banners whose fallback chain reaches this one render it too.
        $ids = [$this->getID() => true];
        $pending = [$this->getID()];
        while ($pending) {
            $rows = $db->fetchFirstColumn(
                'SELECT id FROM bmBanners WHERE fallbackBannerId IN (' . implode(',', array_fill(0, count($pending), '?')) . ')',
                $pending,
            );
            $pending = [];
            foreach ($rows as $id) {
                if (!isset($ids[$id])) {
                    $ids[$id] = true;
                    $pending[] = $id;
                }
            }
        }

        $db->executeStatement(
            'UPDATE CollectionVersionBlocksOutputCache SET btCachedBlockOutputExpires = 0
             WHERE bID IN (SELECT bID FROM btBmBanner WHERE bmBannersId IN (' . implode(',', array_fill(0, count($ids), '?')) . '))',
            array_map('strval', array_keys($ids)),
        );
    }


    public static function getByHandle($handle)
    {
        $em = app(EntityManagerInterface::class);
        $repository = $em->getRepository(get_called_class());

        $entity = $repository->findOneBy(['handle' => $handle]);

        if ($entity) {
            return $entity;
        }

        return false;
    }

    /**
     * @ORM\PostUpdate
     * @ORM\PostPersist
     * @ORM\PreRemove
     */
    public function clearCache()
    {
        $cache = \Core::make('cache/expensive');
        $cache->delete(sprintf('bm_banner_slides_%s', $this->getID()));

        $this->expireBlockOutputCache();
    }

    /**
     * This banner followed by its fallback, that banner's fallback and so on.
     * Stops at the first repeat, so a fallback loop can't run forever.
     *
     * @return Banner[]
     */
    public function getFallbackChain(): array
    {
        $chain = [];
        $banner = $this;
        // Keyed by object identity: Doctrine's identity map gives one instance
        // per row, and unsaved banners have no ID yet.
        while ($banner && !isset($chain[spl_object_id($banner)])) {
            $chain[spl_object_id($banner)] = $banner;
            $banner = $banner->getFallbackBanner();
        }

        return array_values($chain);
    }

    /**
     * The first banner in the fallback chain that is within its schedule, or
     * null when none of them are.
     */
    public function resolveActive(?\DateTime $now = null): ?self
    {
        foreach ($this->getFallbackChain() as $banner) {
            if ($banner->isCurrentlyActive($now)) {
                return $banner;
            }
        }

        return null;
    }

    /**
     * Get the value of handle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Set the value of handle
     *
     * @return self
     */
    public function setHandle(string $handle)
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of scheduleStart
     */
    public function getScheduleStart()
    {
        return $this->scheduleStart;
    }

    /**
     * Set the value of scheduleStart
     *
     * @return self
     */
    public function setScheduleStart(?\DateTime $scheduleStart)
    {
        $this->scheduleStart = $scheduleStart;

        return $this;
    }

    /**
     * Get the value of scheduleEnd
     */
    public function getScheduleEnd()
    {
        return $this->scheduleEnd;
    }

    /**
     * Set the value of scheduleEnd
     *
     * @return self
     */
    public function setScheduleEnd(?\DateTime $scheduleEnd)
    {
        $this->scheduleEnd = $scheduleEnd;

        return $this;
    }

    /**
     * Get the value of fallbackBanner
     */
    public function getFallbackBanner()
    {
        return $this->fallbackBanner;
    }

    /**
     * Set the value of fallbackBanner
     *
     * @return self
     */
    public function setFallbackBanner(?Banner $fallbackBanner = null)
    {
        $this->fallbackBanner = $fallbackBanner;

        return $this;
    }

    /**
     * Get the value of showDots. Nullable in the DB (existing banners predate
     * this field), so treat unset as the previous default behaviour: shown.
     */
    public function getShowDots()
    {
        return $this->showDots ?? true;
    }

    /**
     * Set the value of showDots
     *
     * @return self
     */
    public function setShowDots(bool $showDots)
    {
        $this->showDots = $showDots;

        return $this;
    }

    /**
     * Get the value of showArrows. Nullable in the DB (existing banners
     * predate this field), so treat unset as the previous default: shown.
     */
    public function getShowArrows()
    {
        return $this->showArrows ?? true;
    }

    /**
     * Set the value of showArrows
     *
     * @return self
     */
    public function setShowArrows(bool $showArrows)
    {
        $this->showArrows = $showArrows;

        return $this;
    }

    /**
     * Get the value of showDotsMobile. Independent of showDots - lets dots
     * be shown/hidden on mobile separately from desktop/tablet.
     */
    public function getShowDotsMobile()
    {
        return $this->showDotsMobile ?? true;
    }

    /**
     * Set the value of showDotsMobile
     *
     * @return self
     */
    public function setShowDotsMobile(bool $showDotsMobile)
    {
        $this->showDotsMobile = $showDotsMobile;

        return $this;
    }

    /**
     * Get the value of showArrowsMobile. Independent of showArrows - lets
     * arrows be shown/hidden on mobile separately from desktop/tablet.
     */
    public function getShowArrowsMobile()
    {
        return $this->showArrowsMobile ?? true;
    }

    /**
     * Set the value of showArrowsMobile
     *
     * @return self
     */
    public function setShowArrowsMobile(bool $showArrowsMobile)
    {
        $this->showArrowsMobile = $showArrowsMobile;

        return $this;
    }

    /**
     * Get the value of slideAnimation ("slide" or "fade")
     */
    public function getSlideAnimation()
    {
        return $this->slideAnimation ?? 'slide';
    }

    /**
     * Set the value of slideAnimation
     *
     * @return self
     */
    public function setSlideAnimation(string $slideAnimation)
    {
        $this->slideAnimation = in_array($slideAnimation, ['slide', 'fade'], true) ? $slideAnimation : 'slide';

        return $this;
    }

    /**
     * Whether this banner is within its scheduled window right now. A banner
     * with no start/end set on a given side has no boundary on that side, so
     * an unscheduled banner is always active.
     */
    public function isCurrentlyActive(?\DateTime $now = null)
    {
        $now = $now ?: new \DateTime();

        if ($this->scheduleStart && $now < $this->scheduleStart) {
            return false;
        }

        if ($this->scheduleEnd && $now > $this->scheduleEnd) {
            return false;
        }

        return true;
    }

    /**
     * Build an unpersisted copy of this banner: same schedule, fallback and
     * display options, and the same slides in the same order. Slides are
     * shared with the original rather than cloned, since a slide can belong
     * to any number of banners.
     */
    public function createDuplicate(string $name, string $handle): self
    {
        $copy = new self();
        $copy->setName($name)->setHandle($handle);
        $copy->setScheduleStart($this->scheduleStart ? clone $this->scheduleStart : null);
        $copy->setScheduleEnd($this->scheduleEnd ? clone $this->scheduleEnd : null);
        $copy->setFallbackBanner($this->fallbackBanner);
        $copy->setShowDots($this->getShowDots());
        $copy->setShowArrows($this->getShowArrows());
        $copy->setShowDotsMobile($this->getShowDotsMobile());
        $copy->setShowArrowsMobile($this->getShowArrowsMobile());
        $copy->setSlideAnimation($this->getSlideAnimation());

        foreach ($this->getSlidePositions() as $slidePosition) {
            $position = (new SlidePosition())->setPosition((int) $slidePosition->getPosition());
            $slidePosition->getSlide()->addSlidePosition($position);
            $copy->addSlidePosition($position);
        }

        return $copy;
    }

    /**
     * Get Slide Positions
     *
     * @return array
     */
    public function getSlidePositions()
    {
        return $this->slidePositions->toArray();
    }

    /**
     * Number of slide positions, counted in SQL without loading them.
     */
    public function getSlidePositionsCount(): int
    {
        return $this->slidePositions->count();
    }

    /**
     * Add Slide Position
     *
     * @param SlidePosition $slidePosition
     */
    public function addSlidePosition(SlidePosition $slidePosition)
    {
        if (!$this->slidePositions->contains($slidePosition)) {
            $this->slidePositions->add($slidePosition);
            $slidePosition->setBanner($this);
        }

        return $this;
    }

    /**
     * Remove Slide Position
     *
     * @param SlidePosition $slidePosition
     */
    public function removeSlidePosition(SlidePosition $slidePosition)
    {
        if ($this->slidePositions->contains($slidePosition)) {
            $this->slidePositions->removeElement($slidePosition);
            $slidePosition->setBanner(null);
        }

        return $this;
    }
}
