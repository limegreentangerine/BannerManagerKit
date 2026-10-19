<?php

namespace Concrete\Package\BannerManagerKit\Controller\Element\Dashboard\SlideType;

use Concrete\Core\Controller\ElementController;

class Control extends ElementController
{
    protected $control;
    protected $slideTypeId;

    public function __construct(\Concrete\Core\Entity\Express\Control\Control $control, $slideTypeId)
    {
        parent::__construct();
        $this->control = $control;
        $this->pkgHandle = 'banner_manager_kit';
        $this->slideTypeId = $slideTypeId;
    }

    public function getElement()
    {
        return 'dashboard/slide_types/control';
    }

    public function view()
    {
        $this->set('control', $this->control);
    }

    /**
     * Get the value of slideTypeId
     */
    public function getSlideTypeId()
    {
        return $this->slideTypeId;
    }

    /**
     * Set the value of slideTypeId
     *
     * @return self
     */
    public function setSlideTypeId($slideTypeId)
    {
        $this->slideTypeId = $slideTypeId;

        return $this;
    }
}
