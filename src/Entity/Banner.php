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


    public static function getByHandle($handle)
    {
        $em = app(EntityManagerInterface::class);
        $repository = $em->getRepository(get_called_class());

        $entity = $repository->findOneBy(['handle' => $handle]);

        if ($entity && $entity->getID() > 0) {
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
