<?php

namespace BannerManagerKit\Search\Result\Item;

use Concrete\Core\Search\Result\Item;
use ClassKit\Search\Result\Item\ItemTrait;
use Concrete\Core\Support\Facade\Url as URL;

class Slides extends Item
{
    use ItemTrait;
    public function getViewUrl()
    {
        return URL::to('/dashboard/banner_manager/slides/details', $this->entity->getID());
    }
}
