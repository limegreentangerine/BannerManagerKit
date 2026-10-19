<?php

namespace BannerManagerKit\Search\Result;

use ClassKit\Search\Result as SearchResult;

class Slides extends SearchResult
{
    public function getItemDetails($result)
    {
        return new Item\Slides($this, $this->listColumns, $result);
    }
}
