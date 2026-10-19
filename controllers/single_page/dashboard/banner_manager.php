<?php

namespace Concrete\Package\BannerManagerKit\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;

class BannerManager extends DashboardPageController
{
    public function view()
    {
        $this->buildRedirect('/dashboard/banner_manager/banners')->send();
    }
}
