<?php

namespace BannerManagerKit\Entity;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="bmSlides"
 * )
 * options={
 *      "comment": "List of slides available for banners"
 * }
 */
class Slide extends UpdatedGuidEntity
{
    //
    // STANDARD FIELDS
    //

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=false, options={"comment": "Slide handle"})
     */
    protected $handle;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"comment": "Slide name"})
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="\BannerManagerKit\Entity\SlidePosition", mappedBy="slide", cascade={"persist", "remove"}, orphanRemoval=TRUE, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $slidePositions;

    /**
     * @ORM\ManyToOne(targetEntity="\BannerManagerKit\Entity\SlideType", inversedBy="slides")
     * @ORM\JoinColumn(name="slideTypeId", referencedColumnName="id")
     */
    protected $slideType;

    /**
     * @ORM\OneToOne(targetEntity="\Concrete\Core\Entity\Express\Entry")
     * @ORM\JoinColumn(name="expressEntryId", referencedColumnName="exEntryID")
     */
    protected $expressEntry;

    public function __construct()
    {
        parent::__construct();
        $this->slidePositions = new ArrayCollection();
    }

    /**
     * Get By Handle
     *
     * @param  string      $handle
     * @return Slide|false
     */
    public static function getByHandle(string $handle)
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
     * Get the value of banners
     */
    public function getSlidePositions()
    {
        return $this->slidePositions->toArray();
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
            $slidePosition->setSlide($this);
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
            $slidePosition->setSlide(null);
        }

        return $this;

    }

    public function getBanners()
    {
        try {
            return array_map(
                function ($slidePosition) {
                    return $slidePosition->getBanner();
                },
                $this->slidePositions->toArray(),
            );
        } catch (\Exception $e) {
            // Log the error in production
            app(LoggerInterface::class)->error('Failed to retrieve banners for slide ' . $this->getID(), ['exception' => $e]);
            return [];
        }
    }

    /**
     * Get the value of slideType
     */
    public function getSlideType()
    {
        return $this->slideType;
    }

    /**
     * Get Slide Type Handle
     *
     * @return string
     */
    public function getSlideTypeHandle()
    {
        return ($this->slideType) ? $this->slideType->getHandle() : '';
    }

    /**
     * Get Slide Type Name
     *
     * @return string
     */
    public function getSlideTypeName()
    {
        return ($this->slideType) ? $this->slideType->getName() : '--';
    }

    public function getSlideTypeValues()
    {
        $fields = $this->getSlideType()->getForm()->getFields();
        foreach ($fields as $field) {
            $field->publicRender($this, $this->getSlideType());
        }
    }

    /**
     * Set the value of slideType
     *
     * @return self
     */
    public function setSlideType(SlideType $slideType)
    {
        $this->slideType = $slideType;

        return $this;
    }

    public function getBannerIds(): array
    {
        $ids = [];
        foreach ($this->slidePositions as $sp) {
            $banner = $sp->getBanner();
            if ($banner instanceof Banner) {
                $ids[] = $banner->getID();
            }
        }
        return $ids;
    }

    /**
     * Get the value of expressEntry
     */
    public function getExpressEntry()
    {
        return $this->expressEntry;
    }

    /**
     * Set the value of expressEntry
     *
     * @return self
     */
    public function setExpressEntry($expressEntry)
    {
        $this->expressEntry = $expressEntry;

        return $this;
    }

    public function debugSlideAttributes()
    {
        ob_start();
        $slideType = $this->getSlideType();
        $express = $slideType->getExpressObject();
        echo '<script>';
        echo sprintf(
            'console.group(%s, "color:red;font-size:14px");',
            json_encode('%c' . t('Available Attributes on Slide Type: %s', $slideType->getName()), JSON_HEX_TAG | JSON_HEX_AMP),
        );
        foreach ($express->getAttributes() as $attribute) {
            echo sprintf(
                'console.log(%s, "color:blue");',
                json_encode('%c' . t('%s: can be access using getAttribute("%s") on the Slide object. AttributeType: %s', $attribute->getAttributeKeyName(), $attribute->getAttributeKeyHandle(), $attribute->getAttributeTypeHandle()), JSON_HEX_TAG | JSON_HEX_AMP),
            );
        }
        echo 'console.groupEnd();';
        echo '</script>';
        $html = ob_get_contents();
        ob_end_clean();

        echo $html;
    }

    public function getAttribute($handle)
    {
        $expressEntry = $this->getExpressEntry();
        if (!$expressEntry || !preg_match('/^[A-Za-z0-9_]+$/', (string) $handle)) {
            return null;
        }

        return $expressEntry->__call(sprintf('get%s', ucfirst($handle)), null);
    }
}
