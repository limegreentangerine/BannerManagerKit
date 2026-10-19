<?php

namespace Concrete\Package\BannerManagerKit\Controller\SinglePage\Dashboard\BannerManager;

use Page;
use View;
use Concrete\Core\Express\Form\Renderer;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Context\ContextFactory;
use Concrete\Core\Http\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concrete\Core\Express\Form\Context\DashboardFormContext;
use Concrete\Core\Express\Form\Processor\ProcessorInterface;
use Concrete\Core\Page\Controller\DashboardSitePageController;
use Concrete\Core\Controller\Traits\DashboardExpressEntryDetailsTrait;
use BannerManagerKit\Entity\{Banner, Slide as SlideEntity, SlidePosition, SlideType};
use Concrete\Package\BannerManagerKit\Controller\Search\Slide\Slides as SearchController;
use BannerManagerKit\Search\ItemList\{Banners as BannerList, SlideTypes as SlideTypeList};

class Slides extends DashboardSitePageController
{
    use DashboardExpressEntryDetailsTrait;

    protected $helpers = [
        'form',
        'concrete/ui',
        'concrete/asset_library',
        'form/page_selector',
    ];

    protected function getHeaderSearch()
    {
        if (!isset($this->headerSearch)) {
            $this->headerSearch = $this->app->make(ElementManager::class)->get('search/slides/search', [ 'headerSearchAction' => Page::getCurrentPage()->getCollectionLink() ], 'banner_manager_kit');
        }

        return $this->headerSearch;
    }

    protected function getBanners()
    {
        $bl = new BannerList();
        $banners = $bl->getResults();
        return $banners;
    }

    protected function getSlideTypes()
    {
        $types = [
            '' => 'Choose type...',
        ];
        $stl = new SlideTypeList();
        $sts = $stl->getResults();

        foreach ($sts as $st) {
            $types[$st->getID()] = $st->getName();
        }

        return $types;
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

        if (!$vstrings->notempty($args['slideType'])) {
            $this->error->add(t('Please choose a slide type'));
        }

        if (is_array($args['banners']) && count($args['banners']) < 1) {
            $this->error->add(t('Please choose a banner'));
        }
    }

    /**
     * The Express form / entry IDs arrive in the request, so make sure they
     * belong to the slide type's own entity before touching them.
     */
    protected function assertExpressOwnership($express, $form, $expressEntry): void
    {
        if ($form !== null && $form->getEntity()->getID() !== $express->getID()) {
            throw new UserMessageException(t('Invalid Express Form.'));
        }

        if ($expressEntry !== null && $expressEntry->getEntity()->getID() !== $express->getID()) {
            throw new UserMessageException(t('Invalid Express Entry.'));
        }
    }

    protected function saveExpressDetails()
    {
        $slideType = SlideType::getByID($this->request->request->get('slideType'));
        $express = $slideType->getExpressObject();

        $r = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\Form');
        $form = $r->findOneById($this->request->request->get('express_form_id'));

        $expressEntry = null;
        if ($this->request->request->has('entry_id')) {
            $expressEntry = $this->entityManager->getRepository('Concrete\Core\Entity\Express\Entry')
                ->findOneById($this->request->request->get('entry_id'));
        }

        $this->assertExpressOwnership($express, $form, $expressEntry);

        if ($form !== null) {
            $eh = $this->app->make('express');
            $controller = $eh->getEntityController($express);

            $notifier = $controller->getNotifier();
            $notifications = $notifier->getNotificationList();

            $manager = $controller->getEntryManager($this->request);

            if ($expressEntry === null) {
                // create
                $expressEntry = $manager->addEntry($express, $this->getSite());
                $expressEntry = $manager->saveEntryAttributesForm($form, $expressEntry);
                $notifier->sendNotifications($notifications, $expressEntry, ProcessorInterface::REQUEST_TYPE_ADD);

                return $expressEntry;
            }
            // update
            $manager->saveEntryAttributesForm($form, $expressEntry);
            $notifier->sendNotifications($notifications, $expressEntry, ProcessorInterface::REQUEST_TYPE_UPDATE);

            return $expressEntry;

        }
        throw new UserMessageException(t('Invalid Express Form.'));

    }

    /**
     * Everything only the add/edit form needs - kept out of on_start() so the
     * list view and the ajax/delete actions don't pay for it.
     */
    protected function prepareForm()
    {
        $this->requireAsset('banner_manager_kit/slides');

        $this->set('banners', $this->getBanners());
        $this->set('types', $this->getSlideTypes());
    }

    protected function validateExpress()
    {
        $slideType = SlideType::getByID($this->request->request->get('slideType'));
        $express = $slideType->getExpressObject();

        $r = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\Form');
        $form = $r->findOneById($this->request->request->get('express_form_id'));

        $expressEntry = null;
        if ($this->request->request->has('entry_id')) {
            $expressEntry = $this->entityManager->getRepository('Concrete\Core\Entity\Express\Entry')
                ->findOneById($this->request->request->get('entry_id'));
        }

        $this->assertExpressOwnership($express, $form, $expressEntry);

        if ($form !== null) {
            $eh = $this->app->make('express');
            $controller = $eh->getEntityController($express);
            $processor = $controller->getFormProcessor();
            $validator = $processor->getValidator($this->request);

            if ($expressEntry === null) {
                $validator->validate($form);
            } else {
                $validator->validate($form, $expressEntry);
            }

            foreach ($validator->getErrorList()->getList() as $error) {
                $this->error->addError($error);
            }
        } else {
            throw new UserMessageException(t('Invalid Express Form.'));
        }
    }

    protected function getSlide($id = false)
    {
        return SlideEntity::getByID($id);
    }

    public function view()
    {
        $reset = false;

        if ($this->request->isPost()) {
            if (!$this->token->validate('slide-search')) {
                $this->error->add($this->token->getErrorMessage());
            } else {
                $reset = true;
            }
        }

        $search = $this->app->make(SearchController::class);
        $search->search($reset);

        $result = $search->getSearchResultObject();

        $this->set('result', $result);
        $this->set('items', $result->getItems());
        $this->set('pagination', $result->getPaginationHTML());

        $allowed_num_results = array_combine(
            $search->getAllowedPaginationSizes(),
            $search->getAllowedPaginationSizes(),
        );

        $params = $search->getStickyRequest()->getSearchRequest();
        $this->set('name', isset($params['name']) ? $params['name'] : '');

        if (isset($params['num_results']) && isset($allowed_num_results[$params['num_results']])) {
            $num_results = (int) $params['num_results'];
        } else {
            $num_results = $search->getDefaultPaginationSize();
        }

        $this->set('num_results', $num_results);
        $this->set('params', $params);
        $this->set('allowed_num_results', $allowed_num_results);

        $headerSearch = $this->getHeaderSearch();
        $this->set('headerSearch', $headerSearch);
    }

    public function add()
    {
        $this->requireAsset('core/sitemap');
        $this->prepareForm();
    }

    public function addSlideModal()
    {
        $this->requireAsset('core/sitemap');
        $this->prepareForm();
    }

    public function details($id = false)
    {
        if (!$id) {
            $this->buildRedirect('/dashboard/banner_manager/slides/')->send();
        }

        $entity = SlideEntity::getByID($id);

        if (!$entity) {
            $this->buildRedirect('/dashboard/banner_manager/slides/')->send();
        }

        $this->set('entity', $entity);
        $this->prepareForm();
    }

    public function save()
    {
        if ($this->post()) {
            $post = $this->post();

            if (!$this->token->validate('submit')) {
                $this->error->add($this->token->getErrorMessage());
            }

            $th = $this->app->make('helper/text');
            // The handle is only ever derived server-side; a posted value is ignored.
            if (empty($post['id'])) {
                $post['handle'] = $th->urlify((string) ($post['name'] ?? ''));

                if (SlideEntity::getByHandle($post['handle'])) {
                    $this->error->add(t('Handle already in use.'));
                }
            } else {
                $existing = SlideEntity::getByID((int) $post['id']);
                $post['handle'] = $existing ? $existing->getHandle() : '';
            }

            $returnBannerId = (int) $this->request->query->get('bannerId');
            if ($returnBannerId > 0) {
                $post['banners'][] = $returnBannerId;
            }

            if ((int) ($post['slideType'] ?? 0) > 0) {
                $type = SlideType::getByID((int) $post['slideType']);
            } else {
                throw new UserMessageException(t('Slide Type (ID: %s) not found', (string) ($post['slideType'] ?? '')), 404);
            }

            $this->validate($post);
            $this->validateExpress();

            if (!$this->error->has()) {
                $newSlide = false;

                if (isset($post['id'])) {
                    $entity = SlideEntity::getByID((int) $post['id']);
                    if (!$entity) {
                        throw new UserMessageException(t('Unable to find the specified Slide.'), 404);
                    }
                    $bannerIds = $entity->getBannerIds();
                }

                if (!isset($entity)) {
                    $newSlide = true;
                    $entity = new SlideEntity();
                }

                $entity->setHandle($post['handle']);
                $entity->setName($post['name']);
                $entity->setSlideType($type);

                $expressEntry = $this->saveExpressDetails($this->request);
                $entity->setExpressEntry($expressEntry);

                $this->entityManager->persist($entity);

                if (isset($bannerIds) && is_array($bannerIds)) {
                    $diff = array_diff($bannerIds, $post['banners']);
                }

                if (isset($post['banners'])) {
                    foreach ($post['banners'] as $bannerId) {
                        if ((isset($bannerIds) && is_array($bannerIds)) && in_array($bannerId, $bannerIds)) {
                            continue;
                        }

                        $banner = Banner::getByID($bannerId);
                        if ($banner) {
                            $slidePosition = SlidePosition::getBySlideAndBanner($entity->getID(), $bannerId);

                            if (!$slidePosition) {
                                $slidePosition = new SlidePosition();
                            }

                            $slidePosition->setBanner($banner);
                            $slidePosition->setSlide($entity);
                            $slidePosition->setPosition(0);
                            $this->entityManager->persist($slidePosition);
                            $this->entityManager->flush();

                            if ($newSlide) {
                                $slidePosition->setPosition($slidePosition->getID());
                            } else {
                                $slidePosition->setPosition($slidePosition->getPosition());
                            }
                            $this->entityManager->persist($slidePosition);
                            $banner->clearCache();
                        }
                    }
                }

                if (isset($diff) && is_array($diff) && $diff) {
                    foreach ($entity->getSlidePositions() as $position) {
                        if (in_array($position->getBanner()->getID(), $diff)) {
                            $this->entityManager->remove($position);
                            $position->getBanner()->clearCache();
                        }
                    }
                }

                $this->entityManager->flush();

                // The slide's own content may have changed, not just its placement.
                foreach ($entity->getBannerIds() as $bannerId) {
                    $banner = Banner::getByID($bannerId);
                    if ($banner) {
                        $banner->clearCache();
                    }
                }

                $this->flash('success', t('Slide Saved.'));

                if ($returnBannerId > 0) {
                    return $this->buildRedirect('/dashboard/banner_manager/banners/details/' . $returnBannerId)->send();
                }
                return $this->buildRedirect('/dashboard/banner_manager/slides/')->send();

            }
            $this->set('formContent', $post);
            $this->prepareForm();

        }
    }

    public function delete($id = false)
    {
        $rf = $this->app->make(ResponseFactoryInterface::class);

        if (!$this->token->validate('delete-slide-id-' . $id)) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $entity = SlideEntity::getByID($id);

            if ($entity && $entity->getID() > 0) {
                $expressEntry = $entity->getExpressEntry();
                // Set deleted message
                $this->flash('success', t('Slide Deleted.'));

                // Banner caches must be dropped before the positions go away.
                foreach ($entity->getBannerIds() as $bannerId) {
                    $banner = Banner::getByID($bannerId);
                    if ($banner) {
                        $banner->clearCache();
                    }
                }

                // run the delete.
                if ($expressEntry) {
                    $this->entityManager->remove($expressEntry);
                }
                $this->entityManager->remove($entity);
                $this->entityManager->flush();

                return $rf->json(true);
            }
            $this->error->add(t('Unable to find the specified Slide Details.'));

        }

        return $rf->json($this->error->jsonSerialize());
    }

    public function get_type_controls()
    {
        $slideTypeId = (int) $this->request->query->get('slideTypeId');
        $expressEntryId = (int) $this->request->query->get('expressEntry') ?: false;

        $slideType = SlideType::getByID($slideTypeId);
        if (!is_object($slideType)) {
            throw new UserMessageException(t('Slide type not found'), 404);
        }

        $express = $slideType->getExpressObject();
        if (!is_object($express)) {
            throw new UserMessageException(t('Express Object not found'), 404);
        }

        $form = $express->getDefaultEditForm();
        if (!is_object($form)) {
            throw new UserMessageException(t('Express Form not found'), 404);
        }

        if ($expressEntryId) {
            $expressEntry = $this->entityManager->getRepository('Concrete\Core\Entity\Express\Entry')
                ->findOneById($expressEntryId);

            // Only entries of this slide type's own entity may be rendered.
            if (!is_object($expressEntry) || $expressEntry->getEntity()->getID() !== $express->getID()) {
                throw new UserMessageException(t('Express Entry not found'), 404);
            }
        }

        $eh = \Core::make('express');
        $controller = $eh->getEntityController($express);
        $factory = new ContextFactory($controller);
        $context = $factory->getContext(new DashboardFormContext());

        $renderer = new Renderer($context, $form);
        if (!is_object($renderer)) {
            throw new UserMessageException(t('Form Renderer Error'), 400);
        }

        ob_start();
        $view = new View();
        if (isset($expressEntry)) {
            echo '<input type="hidden" name="entry_id" value="' . $expressEntry->getID() . '">';
            echo $renderer->render($expressEntry);
        } else {
            echo $renderer->render();
        }
        $html = ob_get_contents();
        ob_end_clean();

        return new JsonResponse([ 'html' => $html ]);
    }
}
