<?php

namespace Concrete\Package\BannerManagerKit\Block\BmBanner;

defined('C5_EXECUTE') or die('Access Denied.');

use Page;
use Permissions;
use BannerManagerKit\Entity\Banner;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Block\BlockController;
use BannerManagerKit\Entity\SlidePosition;
use BannerManagerKit\Search\ItemList\Banners as BannerList;

class Controller extends BlockController
{
    protected $btTable = 'btBmBanner';
    protected $btDefaultSet = 'bm';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;

    protected function getBanners(): array
    {
        $options = [
            '' => 'Choose a banner...',
        ];

        $bl = new BannerList();
        $banners = $bl->getResults();

        foreach ($banners as $banner) {
            $options[$banner->getID()] = $banner->getName();
        }

        return $options;
    }

    protected function getBanner($bmBannersId)
    {
        $banner = Banner::getByID($bmBannersId);

        if (!$banner) {
            return false;
        }

        if (!$banner->isCurrentlyActive()) {
            $fallback = $banner->getFallbackBanner();

            return $fallback ?: false;
        }

        return $banner;
    }

    protected function loadHelpers()
    {
        $this->set('banners', $this->getBanners());
    }

    /**
     * The permission-independent part of a banner's slide list: every position
     * ID, plus the pages its page-selector attributes point at. This is what
     * gets cached; the per-viewer read-permission filtering happens on every
     * request in getSlides() so one viewer's access never leaks to another.
     */
    protected function getSlideEntries($banner): array
    {
        $cache = $this->app->make(\Concrete\Core\Cache\Level\ExpensiveCache::class);
        $key = sprintf('bm_banner_slides_%s', $banner->getID());
        $c = Page::getCurrentPage();
        $fresh = $c && ($c->isEditMode() || (new Permissions($c))->canViewToolbar());

        $data = $cache->getItem($key);
        if (!$fresh && $cache->exists($key) && is_array($data->get())) {
            return $data->get();
        }

        $entries = [];
        $pageSelectorHandles = [];
        foreach ($banner->getSlidePositions() as $position) {
            $entry = $position->getSlide()->getExpressEntry();
            if (!$entry) {
                continue;
            }

            $typeId = $entry->getEntity()->getID();
            if (!isset($pageSelectorHandles[$typeId])) {
                $pageSelectorHandles[$typeId] = [];
                foreach ($entry->getEntity()->getAttributes() as $attr) {
                    if ($attr->getAttributeTypeHandle() === 'page_selector') {
                        $pageSelectorHandles[$typeId][] = $attr->getAttributeKeyHandle();
                    }
                }
            }

            $pages = [];
            foreach ($pageSelectorHandles[$typeId] as $handle) {
                $value = $entry->getAttribute($handle);
                $cID = $value instanceof Page ? $value->getCollectionID() : (int) $value;
                if ($cID > 0) {
                    $pages[] = $cID;
                }
            }

            $entries[] = ['id' => $position->getID(), 'pages' => $pages];
        }

        $data->set($entries)->expiresAfter(3600);
        $cache->save($data);

        return $entries;
    }

    /**
     * Slide position IDs the current viewer may see, or false when none.
     */
    protected function getSlides($banner)
    {
        if (!$banner) {
            return false;
        }

        $ids = [];
        foreach ($this->getSlideEntries($banner) as $entry) {
            $allowed = true;
            foreach ($entry['pages'] as $cID) {
                $page = Page::getByID($cID);
                if ($page && !$page->isError() && !(new Checker($page))->canRead()) {
                    $allowed = false;
                    break;
                }
            }
            if ($allowed) {
                $ids[] = $entry['id'];
            }
        }

        return $ids ?: false;
    }

    /**
     * Load the slides for the given position IDs in one query, in order.
     */
    protected function hydrateSlides(array $positionIds): array
    {
        $em = $this->app->make(\Doctrine\ORM\EntityManagerInterface::class);
        $positions = $em->createQueryBuilder()
            ->select('sp', 's')
            ->from(SlidePosition::class, 'sp')
            ->join('sp.slide', 's')
            ->where('sp.id IN (:ids)')
            ->setParameter('ids', $positionIds)
            ->getQuery()
            ->getResult();

        $order = array_flip($positionIds);
        usort($positions, fn($a, $b) => $order[$a->getID()] <=> $order[$b->getID()]);

        return array_map(fn($position) => $position->getSlide(), $positions);
    }

    /**
     * Cache the rendered block (guests only - see $btCacheBlockOutputForRegisteredUsers)
     * until the banner's next schedule boundary, capped so edits show up promptly.
     */
    public function getBlockTypeCacheOutputLifetime()
    {
        $lifetime = 600;
        $banner = Banner::getByID($this->bmBannersId);
        $now = time();

        while ($banner) {
            foreach ([$banner->getScheduleStart(), $banner->getScheduleEnd()] as $boundary) {
                if ($boundary && $boundary->getTimestamp() > $now) {
                    $lifetime = min($lifetime, $boundary->getTimestamp() - $now);
                }
            }
            $banner = $banner->getFallbackBanner();
            if ($banner && $banner->getID() == $this->bmBannersId) {
                break;
            }
        }

        return max(1, $lifetime);
    }

    public function validate($args)
    {
        $e = $this->app->make('error');
        if (empty($args['bmBannersId']) || !Banner::getByID((int) $args['bmBannersId'])) {
            $e->add(t('Please choose a banner.'));
        }

        return $e;
    }

    public function save($args)
    {
        $args['bmBannersId'] = (int) ($args['bmBannersId'] ?? 0);
        parent::save($args);
    }

    public function getBlockTypeName()
    {
        return t('Banner');
    }

    public function getBlockTypeDescription()
    {
        return t('Add a Banner Manager Banner block.');
    }

    public function add()
    {
        $this->loadHelpers();
    }

    public function edit()
    {
        $this->loadHelpers();
    }

    public function view()
    {
        $banner = $this->getBanner($this->bmBannersId);
        $positions = $this->getSlides($banner);
        $this->set('banner', $banner);
        $this->set('positions', $positions);
        $this->set('slides', $positions === false ? false : $this->hydrateSlides($positions));
    }
}
