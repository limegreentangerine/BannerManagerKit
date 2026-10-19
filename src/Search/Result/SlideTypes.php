<?php

namespace BannerManagerKit\Search\Result;

use ClassKit\Search\Result as SearchResult;

class SlideTypes extends SearchResult
{
    public function getItemDetails($result)
    {
        return new Item\SlideTypes($this, $this->listColumns, $result);
    }
}
