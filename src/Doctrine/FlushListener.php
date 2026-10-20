<?php

namespace BannerManagerKit\Doctrine;

use BannerManagerKit\Entity\Banner;

/**
 * Runs the banner cache clears queued by the entities' lifecycle callbacks
 * once the flush that triggered them has committed.
 */
class FlushListener
{
    public function postFlush(): void
    {
        Banner::clearPendingCaches();
    }
}
