<?php

namespace Concrete\Package\BannerManagerKit\Controller\SinglePage\Dashboard\BannerManager\SlideTypes;

use URL;
use Concrete\Core\Attribute\TypeFactory;
use Concrete\Core\Attribute\CategoryObjectInterface;
use BannerManagerKit\Entity\SlideType as SlideTypeEntity;
use Concrete\Core\Page\Controller\DashboardAttributesPageController;

/**
 * Manages the attributes (the fields) of a slide type's Express entity.
 */
class Attributes extends DashboardAttributesPageController
{
    protected $helpers = [
        'form',
        'concrete/ui',
    ];

    /**
     * The Express entity behind the current slide type.
     *
     * @var \Concrete\Core\Entity\Express\Entity|null
     */
    protected $category;

    protected $slideTypeId;

    /**
     * Resolves the slide type and exposes it to the view. Unknown or
     * express-less slide types go back to the slide type list.
     */
    protected function loadSlideType($id): void
    {
        $slideType = $id ? SlideTypeEntity::getByID($id) : null;

        if (!$slideType || !$slideType->getExpressObject()) {
            $this->buildRedirect('/dashboard/banner_manager/slide_types/')->send();
            exit;
        }

        $this->slideTypeId = (string) $slideType->getID();
        $this->category = $slideType->getExpressObject();
        $this->set('slideTypeId', $this->slideTypeId);
        $this->set('entity', $this->category);
    }

    protected function getKey($akID)
    {
        return $this->app->make('Concrete\Core\Attribute\Category\ExpressCategory', ['entity' => $this->category])
            ->getAttributeKeyByID($akID);
    }

    protected function getBackURL()
    {
        return URL::to('/dashboard/banner_manager/slide_types/attributes', $this->slideTypeId);
    }

    protected function getCategoryObject()
    {
        return $this->category;
    }

    protected function getHeaderMenu(CategoryObjectInterface $category)
    {
        return false;
    }

    public function view($id = null)
    {
        $this->loadSlideType($id);
        $this->renderList();
    }

    public function edit($id = null, $akID = null)
    {
        $this->loadSlideType($id);
        $this->renderEdit($this->getKey($akID), $this->getBackURL());
    }

    public function update($id = null, $akID = null)
    {
        $this->edit($id, $akID);
        $this->executeUpdate($this->getKey($akID), $this->getBackURL());
    }

    public function select_type($id = null, $type = null)
    {
        $this->loadSlideType($id);
        $this->renderAdd($this->app->make(TypeFactory::class)->getByID($type), $this->getBackURL());
    }

    public function add($id = null, $type = null)
    {
        $this->select_type($id, $type);
        $this->executeAdd($this->app->make(TypeFactory::class)->getByID($type), $this->getBackURL());
    }

    public function delete($id = null, $akID = null)
    {
        $this->loadSlideType($id);
        $this->executeDelete($this->getKey($akID), $this->getBackURL());
    }
}
