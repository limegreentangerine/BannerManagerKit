<?php

namespace BannerManagerKit\Search\Result;

use ClassKit\Search\Result as SearchResult;

class Banners extends SearchResult
{
    public function getItemDetails($result)
    {
        return new Item\Banners($this, $this->listColumns, $result);
    }
}
