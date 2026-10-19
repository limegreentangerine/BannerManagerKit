<?php

namespace BannerManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="bmSlideTypes")
 * options={
 *      "comment": "List of hero feed slide types"
 * }
 */
class SlideType extends UpdatedGuidEntity
{
    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=false, options={"comment": "Slide type handle"})
     */
    protected $handle;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"comment": "Slide type name"})
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="\BannerManagerKit\Entity\Slide", mappedBy="slideType", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="id", referencedColumnName="slideTypeId")
     */
    protected $slides;

    /**
     * @ORM\OneToOne(targetEntity="Concrete\Core\Entity\Express\Entity")
     * @ORM\JoinColumn(name="expressObjectId", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $expressObject;

    public function __construct()
    {
        parent::__construct();
        $this->slides = new ArrayCollection();
    }

    public function getSlidesCount()
    {
        return count($this->slides);
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
    public function setHandle($handle)
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
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of teams
     */
    public function getSlides()
    {
        return $this->slides;
    }

    /**
     * Add Slide
     *
     * @param Slide $slide
     */
    public function addSlide(Slide $slide)
    {
        if (!$this->slides->contains($slide)) {
            $this->slides->add($slide);
        }
    }

    /**
     * Remove Slide
     *
     * @param Slide $slide
     */
    public function removeSlide(Slide $slide)
    {
        if ($this->slides->contains($slide)) {
            $this->slides->removeElement($slide);
        }
    }

    /**
     * Get the value of expressObject
     */
    public function getExpressObject()
    {
        return $this->expressObject;
    }

    /**
     * Set the value of expressObject
     *
     * @return self
     */
    public function setExpressObject($expressObject)
    {
        $this->expressObject = $expressObject;

        return $this;
    }
}
