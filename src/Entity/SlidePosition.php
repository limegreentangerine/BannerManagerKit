<?php

namespace BannerManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use ClassKit\Entity\Core\UpdatedGuidEntity;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 * @ORM\Table(
 *      name="bmSlidePositions",
 *      indexes={
 *          @ORM\Index(name="idx_banner_position", columns={"bannerId", "position"}),
 *          @ORM\Index(name="idx_slide", columns={"slideId"})
 *      }
 * )
 */
class SlidePosition extends UpdatedGuidEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Banner", inversedBy="slidePositions")
     * @ORM\JoinColumn(name="bannerId", referencedColumnName="id", nullable=FALSE)
     */
    protected $banner;

    /**
     * @ORM\ManyToOne(targetEntity="Slide", inversedBy="slidePositions")
     * @ORM\JoinColumn(name="slideId", referencedColumnName="id", nullable=FALSE)
     */
    protected $slide;

    /**
     * @ORM\Column(type="integer", length=11, nullable=false, options={"comment": "Slide Position", "default" : 0})
     */
    protected $position;

    public static function getBySlideId($id)
    {
        $em = app(EntityManagerInterface::class);
        $repository = $em->getRepository(get_called_class());

        $entity = $repository->findOneBy(['slide' => $id]);

        if ($entity) {
            return $entity;
        }

        return false;
    }

    public static function getBySlideAndBanner($slideId, $bannerId)
    {
        $em = app(EntityManagerInterface::class);
        $repository = $em->getRepository(get_called_class());

        $entity = $repository->findOneBy(['slide' => $slideId, 'banner' => $bannerId]);

        if ($entity) {
            return $entity;
        }

        return false;
    }

    /**
     * Get the value of banner
     */
    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * Set the value of banner
     *
     * @return self
     */
    public function setBanner(?Banner $banner = null)
    {
        $this->banner = $banner;

        return $this;
    }

    /**
     * Get the value of slide
     */
    public function getSlide()
    {
        return $this->slide;
    }

    /**
     * Set the value of slide
     *
     * @return self
     */
    public function setSlide(?Slide $slide = null)
    {
        $this->slide = $slide;

        return $this;
    }

    /**
     * Get the value of position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set the value of position
     *
     * @return self
     */
    public function setPosition(int $position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * A banner's cached slide list depends on its positions, so any change
     * to one (added, reordered or removed) must drop that cache.
     *
     * @ORM\PostPersist
     * @ORM\PostUpdate
     * @ORM\PreRemove
     */
    public function clearBannerCache()
    {
        if ($this->banner) {
            $this->banner->queueCacheClear();
        }
    }
}
