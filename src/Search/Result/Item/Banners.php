<?php

namespace BannerManagerKit\Search\Result\Item;

use Concrete\Core\Search\Result\Item;
use ClassKit\Search\Result\Item\ItemTrait;
use Concrete\Core\Support\Facade\Url as URL;

class Banners extends Item
{
    use ItemTrait;
    public function getViewUrl()
    {
        return URL::to('/dashboard/banner_manager/banners/details', $this->entity->getID());
    }
}
