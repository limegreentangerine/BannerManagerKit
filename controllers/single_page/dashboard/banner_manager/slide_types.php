<?php

namespace Concrete\Package\BannerManagerKit\Controller\SinglePage\Dashboard\BannerManager;

use Express;
use Concrete\Core\Entity\Express\FieldSet;
use Concrete\Core\Http\ResponseFactoryInterface;
use BannerManagerKit\Entity\SlideType as SlideTypeEntity;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\BannerManagerKit\Controller\Search\SlideType\SlideTypes as SearchController;

class SlideTypes extends DashboardPageController
{
    protected $helpers = [
        'form',
        'concrete/ui',
    ];

    protected $repository;
    protected $formRepository;
    protected $fieldSetRepository;
    protected $controlRepository;

    protected function validate($args)
    {
        $vstrings = $this->app->make('helper/validation/strings');

        if (!$vstrings->notempty($args['handle']) || !preg_match('/^[a-z0-9_-]+$/', $args['handle'])) {
            $this->error->add(t('Please enter a valid handle'));
        }

        if (!$vstrings->notempty($args['name'])) {
            $this->error->add('Please enter a name');
        }
    }

    protected function buildExpressObject($post)
    {
        $handle = sprintf('slide_type_%s', $post['handle']);
        $name = sprintf('Slide Type %s', $post['name']);

        $expressObject = Express::getObjectByHandle($handle);
        if ($expressObject !== null) {
            return $expressObject;
        }

        $expressObject = Express::buildObject($handle, $this->pluralize($handle), $name);
        $expressEntity = $expressObject->save();

        $form = $expressObject->buildForm('Form');
        $form = $form->save();

        $expressEntity->setDefaultViewForm($form);
        $expressEntity->setDefaultEditForm($form);
        $this->entityManager->persist($expressEntity);

        return $expressObject;
    }

    /**
     * Express forms are shared with the rest of the CMS, so field-set and
     * control actions must only ever touch forms backing a slide type.
     */
    protected function isSlideTypeForm($form): bool
    {
        return is_object($form)
            && $this->entityManager->getRepository(SlideTypeEntity::class)
                ->findOneBy(['expressObject' => $form->getEntity()]) !== null;
    }

    protected function getSlideType($id)
    {
        $entity = SlideTypeEntity::getByID($id);

        if (!$entity) {
            $this->buildRedirect('/dashboard/banner_manager/slide_types/')->send();
        }

        return $entity;
    }

    protected function pluralize($singular, $quantity = 2)
    {
        if ($quantity == 1 || !strlen($singular)) {
            return $singular;
        }

        $last_letter = strtolower($singular[strlen($singular) - 1]);
        switch ($last_letter) {
            case 'y':
                return substr($singular, 0, -1) . 'ies';
            case 's':
                return $singular . 'es';
            default:
                return $singular . 's';
        }
    }

    public function on_start()
    {
        parent::on_start();

        $this->requireAsset('banner_manager_kit/slide_types');

        $this->repository = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\Entity');
        $this->formRepository = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\Form');
        $this->fieldSetRepository = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\FieldSet');
        $this->controlRepository = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\Control\Control');
    }

    public function view()
    {
        $reset = false;

        if ($this->request->isPost()) {
            if (!$this->token->validate('slide-type-search')) {
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
    }

    public function add() {}

    public function details($id = false)
    {
        if (!$id) {
            $this->buildRedirect('/dashboard/banner_manager/slide_types/')->send();
        }

        $entity = $this->getSlideType($id);
        $this->set('entity', $entity);
    }

    public function save()
    {
        if ($this->post()) {
            $post = $this->post();

            $th = $this->app->make('helper/text');
            // The handle is only ever derived server-side; a posted value is ignored.
            if (empty($post['id'])) {
                $post['handle'] = $th->urlify((string) ($post['name'] ?? ''));

                if (SlideTypeEntity::getByHandle($post['handle'])) {
                    $this->error->add(t('Handle already in use.'));
                }
            } else {
                $existing = SlideTypeEntity::getByID((string) $post['id']);
                $post['handle'] = $existing ? $existing->getHandle() : '';
            }

            if (!$this->token->validate('submit')) {
                $this->error->add($this->token->getErrorMessage());
            }

            $this->validate($post);

            if (!$this->error->has()) {
                if (isset($post['id'])) {
                    $entity = SlideTypeEntity::getByID((string) $post['id']);
                }

                if (!isset($entity)) {
                    $entity = new SlideTypeEntity();
                }

                $entity->setHandle($post['handle']);
                $entity->setName($post['name']);

                $object = $this->buildExpressObject($post);
                if ($object instanceof \Concrete\Core\Express\ObjectBuilder) {
                    $entity->setExpressObject($object->getEntity());
                } else {
                    $entity->setExpressObject($object);
                }

                $this->entityManager->persist($entity);
                $this->entityManager->flush();

                $this->flash('success', t('Slide Type Saved.'));
                return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $entity->getID())->send();
            }
            $this->set('formContent', $post);

        }
    }

    public function delete($id = false)
    {
        $rf = $this->app->make(ResponseFactoryInterface::class);

        if (!$this->token->validate('delete-slide-type-id-' . $id)) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $entity = SlideTypeEntity::getByID($id);

            if ($entity) {
                // Set deleted message
                $this->flash('success', t('Slide Type Deleted.'));

                $expressObject = $entity->getExpressObject();

                // run the delete.
                $this->entityManager->remove($expressObject);
                $this->entityManager->remove($entity);
                $this->entityManager->flush();

                return $rf->json(true);
            }
            $this->error->add(t('Unable to find the specified Slide Type Details.'));

        }

        return $rf->json($this->error->jsonSerialize());
    }

    public function add_fieldset($id = null, $slideTypeId = null)
    {
        $slideTypeId = (string) $slideTypeId;
        $form = $this->formRepository->findOneById((int) $id);
        if (!$this->isSlideTypeForm($form)) {
            $this->error->add(t('Invalid form object.'));
        }
        if (!$this->token->validate('add_set')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            $current = count($form->getFieldSets());
            $position = 0;
            if ($current > 0) {
                $position = $current;
            }

            $set = new FieldSet();
            $set->setTitle($this->request->request->get('name'));
            $set->setForm($form);
            $set->setPosition($position);
            $this->entityManager->persist($set);
            $this->entityManager->flush();
            $this->flash('success', t('Form field set added successfully.'));
            return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();
        }
        return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();

    }

    public function update_fieldset($slideTypeId)
    {
        $slideTypeId = (string) $slideTypeId;
        $set = $this->fieldSetRepository->findOneById((int) $this->request->request->get('field_set_id'));
        if (!is_object($set) || !$this->isSlideTypeForm($set->getForm())) {
            $this->error->add(t('Invalid field set object.'));
        }
        if (!$this->token->validate('update_set')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            $set->setTitle($this->request->request->get('name'));
            $this->entityManager->persist($set);
            $this->entityManager->flush();
            $this->flash('success', t('Field set updated successfully.'));
            return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();
        }
        return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();

    }

    public function delete_fieldset($slideTypeId)
    {
        $slideTypeId = (string) $slideTypeId;
        $set = $this->fieldSetRepository->findOneById((int) $this->request->request->get('field_set_id'));
        if (!is_object($set) || !$this->isSlideTypeForm($set->getForm())) {
            $this->error->add(t('Invalid field set object.'));
        }
        if (!$this->token->validate('delete_set')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            $this->entityManager->remove($set);
            $this->entityManager->flush();
            $this->flash('success', t('Field set deleted successfully.'));
            return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();
        }
        return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();

    }

    public function update_fieldset_display_order($id = null)
    {
        $form = $this->formRepository->findOneById((int) $id);
        if ($this->isSlideTypeForm($form)) {
            if ($this->token->validate('update_set_display_order', $this->request->request->get('token'))) {
                $position = 0;
                foreach ((array) $this->post('set') as $setID) {
                    $set = $this->fieldSetRepository->findOneById((int) $setID);
                    if (is_object($set) && $set->getForm()->getID() === $form->getID()) {
                        $set->setPosition($position);
                        $this->entityManager->persist($set);
                    }
                    ++$position;
                }
                $this->entityManager->flush();
            }
        }
        exit;
    }

    public function delete_set_control($id = null)
    {
        $slideTypeId = (string) $this->request->request->get('slideTypeId');
        $control = $this->controlRepository->findOneById((int) $this->request->request->get('field_set_control_id'));
        if (!is_object($control) || !$this->isSlideTypeForm($control->getFieldSet()->getForm())) {
            $this->error->add(t('Invalid field set control object.'));
        }
        if (!$this->token->validate('delete_set_control')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            $this->entityManager->remove($control);
            $this->entityManager->flush();
            $this->flash('success', t('Field set control deleted successfully.'));
            return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();
        }
        return $this->buildRedirect('/dashboard/banner_manager/slide_types/details/' . $slideTypeId)->send();

    }

    public function update_set_control_display_order($id = null)
    {
        $form = $this->formRepository->findOneById((int) $id);
        if ($this->isSlideTypeForm($form)) {
            $set = $this->fieldSetRepository->findOneById((int) $this->request->request->get('set'));
            if (is_object($set) && $set->getForm()->getID() === $form->getID()) {
                if ($this->token->validate('update_set_control_display_order', $this->request->request->get('token'))) {
                    $position = 0;
                    foreach ((array) $this->post('control') as $controlID) {
                        $control = $this->controlRepository->findOneById((int) $controlID);
                        if (is_object($control) && $control->getFieldSet()->getID() === $set->getID()) {
                            $control->setPosition($position);
                            $this->entityManager->persist($control);
                        }
                        ++$position;
                    }
                    $this->entityManager->flush();
                }
            }
        }
        exit;
    }
}
