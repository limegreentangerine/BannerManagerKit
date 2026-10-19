<?php

namespace Concrete\Package\BannerManagerKit;

use Concrete\Core\Asset\{Asset, AssetList};
use Concrete\Core\Entity\Package;
use ClassKit\Package\PackageController;
use BannerManagerKit\Traits\DefaultBanner;
use Concrete\Core\Page\{Page, Single as SinglePage};
use Concrete\Core\Database\Connection\Connection;
use ClassKit\Package\Traits\{AttributeTrait, BlockTrait, PageTrait};

class Controller extends PackageController
{
    use AttributeTrait;
    use BlockTrait;
    use DefaultBanner;
    use PageTrait;
    /**
     * The packages handle.
     * Note that this must be unique in the
     * entire concrete5 package ecosystem.
     *
     * @var string
     */
    protected $pkgHandle = 'banner_manager_kit';

    /**
     * The packages version.
     *
     * @var string
     */
    protected $pkgVersion = '0.0.4';

    /**
     * The minimum Concrete version compatible with the package.
     * Override this value according to the minimum required version for your package.
     *
     * @var string
     */
    protected $appVersionRequired = '9.5.0';

    /**
     * The minimum PHP version compatible with the package.
     * Override this value according to the minimum required version for your package.
     *
     * @var string
     */
    protected $phpVersionRequired = '8.4';

    /**
     * Package service providers to register.
     *
     * eg. 'Concrete\Package\PackageHandle\Src\Providers\PackageServiceProvider'
     *
     * @var array
     */
    protected $providers = [];

    /**
     * An array describing the package dependencies.
     * Keys are package handles.
     * Values may be:
     * - false: this package can't be installed if the other package is already installed.
     * - true: this package can't be installed of the other package is not installed
     * - a string: this package can't be installed of the other package is not installed or it's installed with an older version
     * - an array with two strings, representing the minimum and the maximum version of the other package to be installed.
     *
     * @var array
     *
     * @example [
     *     // This package can't be installed if a package with handle other_package_1 is already installed.
     *     'other_package_1' => false,
     *     // This package can't be installed if a package with handle other_package_2 is not installed.
     *     'other_package_2' => true,
     *     // This package can't be installed if a package with handle other_package_3 is not installed, or it has a version prior to 1.0
     *     'other_package_3' => '1.0',
     *     // This package can't be installed if a package with handle other_package_4 is not installed, or it has a version prior to 2.0, or it has a version after 2.9
     *     'other_package_4' => ['2.0', '2.9'],
     * ]
     */
    protected $packageDependencies = [
        'class_kit' => true,
    ];

    /**
     * Package class autoloader registrations
     * The package install helper class, included with this boilerplate,
     * is activated by default.
     *
     * @see https://goo.gl/4wyRtH
     * @var array
     */
    protected $pkgAutoloaderRegistries = [
        'src' => '\BannerManagerKit',
    ];

    /**
     * Package tasks to register.
     *
     * eg. 'task_handle' => \PackageHandle\Command\Task\Controller\TaskHandleController::class,
     *
     * @var array
     */
    protected $tasks = [];

    /**
     * Dashboard scripts, registered so core versions (and can combine) them
     * rather than controllers hand-writing script tags.
     */
    protected function registerAssets(): void
    {
        $al = AssetList::getInstance();
        $options = ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true];

        foreach (['slides', 'slide_types'] as $script) {
            $al->register('javascript', 'banner_manager_kit/' . $script, 'js/' . $script . '.js', $options, 'banner_manager_kit');
            $al->registerGroup('banner_manager_kit/' . $script, [
                ['javascript', 'jquery'],
                ['javascript', 'banner_manager_kit/' . $script],
            ]);
        }
    }

    public function getPackageName()
    {
        return t('Banner Manager');
    }

    public function getPackageDescription()
    {
        return t('Banner Manger');
    }

    public function on_start()
    {
        parent::on_start();
        $this->registerAssets();
    }

    public function installOrUpgrade(Package $pkg)
    {
        // Create Block Set
        $this->addBlockTypeSet('bm', 'Banner Manager', $pkg);

        // Add blocks
        $this->autoInstallBlocks($pkg);

        // Add Dashboard Pages
        $this->addSinglePage('/dashboard/banner_manager', $pkg, t('Banner Manager'), t('Banner Manager Dashboard'));
        $this->addSinglePage('/dashboard/banner_manager/banners', $pkg, t('Banners'), t('Banner Manager Banners'));
        $this->addSinglePage('/dashboard/banner_manager/slides', $pkg, t('Slides'), t('Banner Manager Slides'));
        $this->addSinglePage('/dashboard/banner_manager/slide_types', $pkg, t('Slide Types'), t('Banner Manager Slide Types'));
        $this->addSinglePage('/dashboard/banner_manager/slide_types/attributes', $pkg, t('Attributes'), t('Banner Manager Slide Type Attributes'));

        // Pages created while their view file couldn't be found keep an empty
        // cFilename (and render blank). Page::update() and SinglePage::refresh()
        // both skip such pages, so write the filename directly.
        $db = $this->app->make(Connection::class);
        foreach (['', '/banners', '/slides', '/slide_types', '/slide_types/attributes'] as $suffix) {
            $path = '/dashboard/banner_manager' . $suffix;
            $page = Page::getByPath($path);
            if (!$page->getCollectionFilename()) {
                $db->update('Pages', ['cFilename' => SinglePage::getPathToNode($path, $pkg)], ['cID' => $page->getCollectionID()]);
            }
        }

        // Seed the default "Standard" slide type
        $this->installStandardSlideType();
    }

    public function registerRoutes(): void {}

    public function registerEvents(): void {}
}
