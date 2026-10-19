<?php

namespace Concrete\Package\BannerManagerKit\Controller\SinglePage\Dashboard\BannerManager;

use Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use BannerManagerKit\Search\ItemList\Banners as BannerList;
use BannerManagerKit\Entity\{Banner as BannerEntity, Slide as SlideEntity, SlidePosition as SlidePositionEntity};

class Banners extends DashboardPageController
{
    protected $helpers = [
        'form',
        'form/date_time',
        'concrete/ui',
    ];

    /**
     * Options for the "Fallback Banner" select, excluding the banner
     * currently being edited (a banner can't be its own fallback).
     */
    protected function getBannerOptions($excludeId = null): array
    {
        $options = [
            '' => t('None'),
        ];

        $bl = new BannerList();
        $banners = $bl->getResults();

        foreach ($banners as $banner) {
            if ($excludeId && $banner->getID() === (string) $excludeId) {
                continue;
            }
            $options[$banner->getID()] = $banner->getName();
        }

        return $options;
    }

    protected function getHeaderSearch()
    {
        if (!isset($this->headerSearch)) {
            $this->headerSearch = $this->app->make(ElementManager::class)->get('search/banners', [ 'headerSearchAction' => Page::getCurrentPage()->getCollectionLink() ], 'banner_manager_kit');
        }

        return $this->headerSearch;
    }

    /**
     * A banner isn't tied to a page by a direct relation - it's displayed
     * wherever a bm_banner block instance points at it (btBmBanner.bmBannersId),
     * and the same banner can be placed on any number of pages (or none).
     * This resolves that placement in a single query (avoiding an N+1 per
     * banner) by joining the block placement through to the live/approved
     * page version, and groups the given banners by the page(s) they're
     * used on for the dashboard listing.
     *
     * @param BannerEntity[] $banners
     *
     * @return array{byPage: array, unassigned: BannerEntity[]}
     */
    protected function groupBannersByPage(array $banners): array
    {
        $bannersById = [];
        foreach ($banners as $banner) {
            $bannersById[$banner->getID()] = $banner;
        }

        $unplacedIds = array_fill_keys(array_keys($bannersById), true);
        $pageGroups = [];

        if (!empty($bannersById)) {
            $db = $this->app->make('database')->connection();
            $bannerIds = array_keys($bannersById);
            $rows = $db->fetchAllAssociative(
                'SELECT DISTINCT bb.bmBannersId, p.cID
                 FROM btBmBanner bb
                 INNER JOIN CollectionVersionBlocks cvb ON cvb.bID = bb.bID
                 INNER JOIN CollectionVersions cv ON cv.cID = cvb.cID AND cv.cvID = cvb.cvID AND cv.cvIsApproved = 1
                 INNER JOIN Pages p ON p.cID = cvb.cID AND p.cIsActive = 1
                 WHERE bb.bmBannersId IN (' . implode(',', array_fill(0, count($bannerIds), '?')) . ')',
                $bannerIds,
            );

            // Several banners often share a page: resolve (and permission-check) each page once.
            $pages = [];
            foreach ($rows as $row) {
                $bannerId = (string) $row['bmBannersId'];
                if (!isset($bannersById[$bannerId])) {
                    continue;
                }

                $pageId = (int) $row['cID'];
                if (!array_key_exists($pageId, $pages)) {
                    $candidate = Page::getByID($pageId);
                    $pages[$pageId] = ($candidate && !$candidate->isError() && (new Checker($candidate))->canViewPage())
                        ? $candidate
                        : null;
                }

                $page = $pages[$pageId];
                if (!$page) {
                    continue;
                }

                $cID = $page->getCollectionID();
                if (!isset($pageGroups[$cID])) {
                    $pageGroups[$cID] = ['page' => $page, 'banners' => []];
                }
                $pageGroups[$cID]['banners'][] = $bannersById[$bannerId];
                unset($unplacedIds[$bannerId]);
            }
        }

        uasort($pageGroups, function ($a, $b) {
            return strcasecmp($a['page']->getCollectionName(), $b['page']->getCollectionName());
        });

        $nameSort = function (BannerEntity $a, BannerEntity $b) {
            return strcasecmp($a->getName(), $b->getName());
        };

        foreach ($pageGroups as &$group) {
            usort($group['banners'], $nameSort);
        }
        unset($group);

        $unassigned = array_values(array_intersect_key($bannersById, $unplacedIds));
        usort($unassigned, $nameSort);

        return [
            'byPage' => array_values($pageGroups),
            'unassigned' => $unassigned,
        ];
    }

    protected function validate($args)
    {
        $vstrings = $this->app->make('helper/validation/strings');

        if (!$vstrings->notempty($args['name'])) {
            $this->error->add(t('Please enter a name'));
        }

        if ($vstrings->notempty($args['name']) && !$vstrings->notempty($args['handle'])) {
            $this->error->add(t('Please enter a handle'));
        }

        $formDateTime = $this->app->make('helper/form/date_time');
        $scheduleStart = $formDateTime->translate('scheduleStart', $args, true);
        $scheduleEnd = $formDateTime->translate('scheduleEnd', $args, true);

        if ($scheduleStart && $scheduleEnd && $scheduleEnd <= $scheduleStart) {
            $this->error->add(t('The schedule end date must be after the start date.'));
        }

        if (!empty($args['fallbackBanner']) && !empty($args['id']) && (string) $args['fallbackBanner'] === (string) $args['id']) {
            $this->error->add(t('A banner cannot be its own fallback.'));
        } elseif (!empty($args['fallbackBanner']) && !empty($args['id'])) {
            // Reject A -> B -> A style loops, not just a direct self-reference.
            $fallback = BannerEntity::getByID((string) $args['fallbackBanner']);
            foreach ($fallback ? $fallback->getFallbackChain() : [] as $banner) {
                if ($banner->getID() === (string) $args['id']) {
                    $this->error->add(t('That fallback banner already falls back to this banner.'));
                    break;
                }
            }
        }
    }

    public function view()
    {
        $name = trim((string) $this->request->query->get('name', ''));
        $this->set('name', $name);

        $qb = $this->entityManager->getRepository(BannerEntity::class)->createQueryBuilder('b')
            ->orderBy('b.name', 'ASC');

        if ($name !== '') {
            $qb->andWhere('b.name LIKE :name')->setParameter('name', '%' . $name . '%');
        }

        $banners = $qb->getQuery()->getResult();

        $this->set('groups', $this->groupBannersByPage($banners));

        $headerSearch = $this->getHeaderSearch();
        $this->set('headerSearch', $headerSearch);
    }

    public function add()
    {
        $this->set('bannerOptions', $this->getBannerOptions());
    }

    public function details($id = false)
    {
        if (!$id) {
            $this->buildRedirect('/dashboard/banner_manager/banners/')->send();
        }

        $entity = BannerEntity::getByID($id);

        if (!$entity) {
            $this->buildRedirect('/dashboard/banner_manager/banners/')->send();
        }

        $this->set('entity', $entity);
        $this->set('bannerOptions', $this->getBannerOptions($id));
    }

    public function save()
    {
        if ($this->post()) {
            $post = $this->post();

            $th = $this->app->make('helper/text');
            // The handle is only ever derived server-side; a posted value is ignored.
            if (empty($post['id'])) {
                $post['handle'] = $th->urlify((string) ($post['name'] ?? ''));

                if (BannerEntity::getByHandle($post['handle'])) {
                    $this->error->add(t('Handle already in use.'));
                }
            } else {
                $existing = BannerEntity::getByID((string) $post['id']);
                $post['handle'] = $existing ? $existing->getHandle() : '';
            }

            if (!$this->token->validate('submit')) {
                $this->error->add($this->token->getErrorMessage());
            }

            $this->validate($post);

            if (!$this->error->has()) {
                if (isset($post['id'])) {
                    $entity = BannerEntity::getByID((string) $post['id']);
                }

                if (!isset($entity)) {
                    $entity = new BannerEntity();
                }

                $formDateTime = $this->app->make('helper/form/date_time');

                $entity->setHandle($post['handle']);
                $entity->setName($post['name']);
                $entity->setScheduleStart($formDateTime->translate('scheduleStart', $post, true));
                $entity->setScheduleEnd($formDateTime->translate('scheduleEnd', $post, true));

                $fallbackBanner = !empty($post['fallbackBanner']) ? BannerEntity::getByID((string) $post['fallbackBanner']) : null;
                $entity->setFallbackBanner($fallbackBanner ?: null);

                $entity->setShowDots(isset($post['showDots']));
                $entity->setShowArrows(isset($post['showArrows']));
                $entity->setShowDotsMobile(isset($post['showDotsMobile']));
                $entity->setShowArrowsMobile(isset($post['showArrowsMobile']));
                $entity->setSlideAnimation($post['slideAnimation'] ?? 'slide');

                $this->entityManager->persist($entity);
                $this->entityManager->flush();

                $entity->clearCache();

                $this->flash('success', t('Banner Saved.'));
                return $this->buildRedirect('/dashboard/banner_manager/banners/details/' . $entity->getID() . '#slides')->send();
            }
            $this->set('formContent', $post);
            $this->set('bannerOptions', $this->getBannerOptions($post['id'] ?? null));

        }
    }

    public function delete($id = false)
    {
        $rf = $this->app->make(ResponseFactoryInterface::class);

        if (!$this->token->validate('delete-banner-id-' . $id)) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $entity = BannerEntity::getByID($id);

            if ($entity) {
                // Set deleted message
                $this->flash('success', t('Banner Deleted.'));

                // run the delete.
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
                $entity->clearCache();

                return $rf->json(true);
            }
            $this->error->add(t('Unable to find the specified Banner Details.'));

        }

        return $rf->json($this->error->jsonSerialize());
    }

    public function duplicate($id = false)
    {
        $id = (string) $id;

        if ($id === '' || !$this->request->isPost() || !$this->token->validate('duplicate-banner-id-' . $id)) {
            $this->flash('error', $this->token->getErrorMessage());

            return $this->buildRedirect('/dashboard/banner_manager/banners/')->send();
        }

        $source = BannerEntity::getByID($id);
        if (!$source) {
            $this->flash('error', t('Unable to find the specified Banner Details.'));

            return $this->buildRedirect('/dashboard/banner_manager/banners/')->send();
        }

        $th = $this->app->make('helper/text');
        $name = t('Copy of %s', $source->getName());
        $baseHandle = $th->urlify($name);
        $handle = $baseHandle;
        for ($i = 2; BannerEntity::getByHandle($handle); ++$i) {
            $handle = $baseHandle . '-' . $i;
        }

        $copy = $source->createDuplicate($name, $handle);
        $this->entityManager->persist($copy);
        $this->entityManager->flush();
        $copy->clearCache();

        $this->flash('success', t('Banner Duplicated.'));

        return $this->buildRedirect('/dashboard/banner_manager/banners/details/' . $copy->getID())->send();
    }

    public function deleteSlide($id = false, $bannerId = false)
    {
        $id = (string) $id;
        $bannerId = (string) $bannerId;

        if ($id === '' || $bannerId === '' || !$this->request->isPost() || !$this->token->validate('delete-banner-slide-' . $id)) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            $entity = SlideEntity::getByID($id);

            if ($entity && in_array($bannerId, $entity->getBannerIds(), true)) {
                // Set deleted message
                $this->flash('success', t('Slide Deleted.'));

                // run the delete.
                $this->entityManager->remove($entity);
                $this->entityManager->flush();

                $banner = BannerEntity::getByID($bannerId);
                if ($banner) {
                    $banner->clearCache();
                }
            } else {
                $this->flash('error', t('Slide not found.'));
            }
        }

        return $this->buildRedirect('/dashboard/banner_manager/banners/details/' . $bannerId)->send();
    }

    public function updateSlideOrder($id = false)
    {
        $rf = $this->app->make(ResponseFactoryInterface::class);
        $id = (string) $id;

        if (!$this->request->isPost() || !$this->token->validate('update-slide-order-' . $id)) {
            return $rf->json(['error' => $this->token->getErrorMessage()], 400);
        }

        $banner = BannerEntity::getByID($id);
        $positions = $this->request->request->all('slidePositions');
        if (!is_object($banner) || !$positions) {
            return $rf->json(['error' => t('Invalid request.')], 400);
        }

        $displayOrder = 0;
        foreach ($positions as $sp) {
            $slidePosition = SlidePositionEntity::getByID((string) $sp);
            if (is_object($slidePosition) && $slidePosition->getBanner()->getID() === $id) {
                $slidePosition->setPosition($displayOrder);
                $this->entityManager->persist($slidePosition);
                ++$displayOrder;
            }
        }
        $this->entityManager->flush();
        $banner->clearCache();

        return $rf->json(true);
    }
}
