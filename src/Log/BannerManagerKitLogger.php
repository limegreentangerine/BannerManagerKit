<?php

namespace BannerManagerKit\Log;

use ClassKit\Log\Logger;

class BannerManagerKitLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('banner_manager');
    }
}
