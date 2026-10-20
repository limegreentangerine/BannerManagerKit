<?php

namespace BannerManagerKit\Traits;

use Doctrine\ORM\Id\UuidGenerator;
use BannerManagerKit\Entity\SlideType;
use Concrete\Core\Attribute\TypeFactory;
use Concrete\Core\Express\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Entity\Attribute\Key\ExpressKey;
use Concrete\Core\Entity\Express\Control\AttributeKeyControl;
use Concrete\Core\Entity\Attribute\Value\Value\{SelectValueOption, SelectValueOptionList};

trait DefaultBanner
{
    /**
     * Creates a "Standard" slide type (image, title, description, button link
     * type/page/url/text) if one doesn't already exist, so every site built
     * from this starter has a ready-to-use default set of slide fields.
     */
    protected function installStandardSlideType()
    {
        $existingSlideType = SlideType::getByHandle('standard');
        if ($existingSlideType) {
            // Slide type already installed - only top up newly added fields
            // (e.g. mobile_image) that weren't part of the original install,
            // and fix up labels that have since been renamed.
            $this->addMobileImageAttribute($existingSlideType);
            $this->renameMediaAttributes($existingSlideType);
            $this->addButtonNewTabAttribute($existingSlideType);
            $this->addSlideDurationAttribute($existingSlideType);
            $this->fixButtonAttributeSearchIndexColumns($existingSlideType);
            return;
        }

        $entityManager = $this->app->make(EntityManagerInterface::class);

        $handle = 'slide_type_standard';

        // Express::getObjectByHandle() returns a plain Entity, not an ObjectBuilder,
        // so an existing object can't have addAttribute() called on it. Since this
        // method only ever needs to run once (guarded by the SlideType check above),
        // any existing object at this point is leftover from a previous failed
        // attempt (no matching SlideType was ever created) - remove it and rebuild
        // cleanly via ObjectBuilder rather than trying to resume it.
        $express = $this->app->make(ObjectManager::class);
        $existingEntity = $express->getObjectByHandle($handle);
        if ($existingEntity !== null) {
            $entityManager->remove($existingEntity);
            $entityManager->flush();
        }

        $expressObject = $express->buildObject($handle, 'slide_type_standards', 'Slide Type Standard');
        $expressObject->save();

        // Handles for multi-word attributes must be snake_case, not camelCase.
        // Concrete\Core\Entity\Express\Entry::__call() resolves getButtonText()
        // by inserting an underscore before every uppercase letter and
        // lowercasing the result ("getButtonText" -> "button_text") to find the
        // attribute handle to look up - a camelCase handle like "buttonText"
        // never matches what that round-trip produces, so Slide::getAttribute()
        // would silently return null for it (single-word handles like "title"
        // are unaffected since there's no case change to reverse).
        $expressObject->addAttribute('image_file', t('Media'), 'image');
        $expressObject->addAttribute('image_file', t('Mobile Media'), 'mobile_image');
        $expressObject->addAttribute('text', t('Title'), 'title');
        $expressObject->addAttribute('textarea', t('Description'), 'description');
        $expressObject->addAttribute('select', t('Button Link Type'), 'button_link_type');
        $expressObject->addAttribute('page_selector', t('Button Page'), 'button_page');
        $expressObject->addAttribute('url', t('Button URL'), 'button_url');
        $expressObject->addAttribute('text', t('Button Text'), 'button_text');
        $expressObject->addAttribute('boolean', t('Open Link in New Tab'), 'button_new_tab');
        $expressObject->addAttribute('number', t('Slide Duration (seconds)'), 'slide_duration');
        $expressEntity = $expressObject->save();

        // Use the uncached lookup: ObjectBuilder::addAttribute() above already called
        // getAttributeKeyByHandle() for this handle *before* the key existed, which
        // caches a null result for the rest of this request in Concrete's per-request
        // cache pool. The cached (stale) lookup would still return null here.
        $linkTypeKey = $expressEntity->getAttributeKeyCategory()->getAttributeKeyByHandleUncached('button_link_type');

        // Controller::setOptions() naively assigns whatever it's given straight onto
        // a Doctrine-managed collection - it expects real SelectValueOption entities,
        // not strings, or flush() fails deep in Doctrine's UnitOfWork. Build the
        // option list by hand instead, following the same approach as core's own
        // Select\Controller::importKey().
        $optionList = new SelectValueOptionList();
        $displayOrder = 0;
        foreach ([t('Internal Page'), t('External URL')] as $optionValue) {
            $option = new SelectValueOption();
            $option->setSelectAttributeOptionValue($optionValue);
            $option->setDisplayOrder($displayOrder);
            $option->setOptionList($optionList);
            $optionList->getOptions()->add($option);
            $displayOrder++;
        }
        $linkTypeSettings = $linkTypeKey->getAttributeKeySettings();
        $linkTypeSettings->setOptionList($optionList);
        $entityManager->persist($linkTypeSettings);
        $entityManager->flush();

        $form = $expressObject->buildForm('Form');
        $set = $form->addFieldSet(t('Slide Content'));
        foreach (['image', 'mobile_image', 'title', 'description', 'button_link_type', 'button_page', 'button_url', 'button_text', 'button_new_tab', 'slide_duration'] as $attributeHandle) {
            $set->addAttributeKeyControl($attributeHandle);
        }
        $form = $form->save();

        $expressEntity->setDefaultViewForm($form);
        $expressEntity->setDefaultEditForm($form);
        $entityManager->persist($expressEntity);
        $entityManager->flush();

        $slideType = new SlideType();
        $slideType->setHandle('standard');
        $slideType->setName(t('Standard'));
        $slideType->setExpressObject($expressEntity);
        $entityManager->persist($slideType);
        $entityManager->flush();
    }

    /**
     * Adds the "Mobile Image" field to an already-installed Standard slide
     * type. Express::getObjectByHandle() returns a plain Entity, not an
     * ObjectBuilder, and ObjectBuilder only supports building brand new
     * objects - so a field added to an existing, already-installed entity
     * has to be constructed by hand here, mirroring what
     * ObjectBuilder::addAttribute() and FieldsetBuilder::build() do
     * internally.
     */
    protected function addMobileImageAttribute(SlideType $slideType)
    {
        $expressEntity = $slideType->getExpressObject();
        if (!$expressEntity) {
            return;
        }

        $category = $expressEntity->getAttributeKeyCategory();
        if ($category->getAttributeKeyByHandleUncached('mobile_image')) {
            return;
        }

        $entityManager = $this->app->make(EntityManagerInterface::class);
        $type = $this->app->make(TypeFactory::class)->getByHandle('image_file');

        $key = new ExpressKey();
        $key->setEntity($expressEntity);
        $settings = $type->getController()->getAttributeKeySettings();
        $settings->setAttributeKey($key);
        $key->setAttributeKeySettings($settings);
        $key->setAttributeKeyName(t('Mobile Media'));
        $key->setAttributeType($type);
        $key->setAttributeKeyHandle('mobile_image');
        $key->setIsAttributeKeySearchable(true);
        $key->setIsAttributeKeyContentIndexed(false);
        $expressEntity->getAttributes()->add($key);

        $entityManager->persist($expressEntity);
        $entityManager->flush();
        $entityManager->persist($settings);
        $entityManager->flush();
        $category->getSearchIndexer()->updateRepositoryColumns($category, $key);

        $form = $expressEntity->getDefaultEditForm();
        if ($form) {
            $fieldSet = $form->getFieldSets()->isEmpty() ? null : $form->getFieldSets()->first();
            if ($fieldSet) {
                $control = new AttributeKeyControl();
                $control->setAttributeKey($key);
                $control->setFieldSet($fieldSet);
                $control->setPosition($fieldSet->getControls()->count());
                $control->setId((new UuidGenerator())->generate($entityManager, $control));
                $fieldSet->getControls()->add($control);
                $entityManager->persist($control);
                $entityManager->flush();
            }
        }
    }

    /**
     * Adds the "Open Link in New Tab" field to an already-installed Standard
     * slide type. Same reasoning as addMobileImageAttribute() - the field has
     * to be constructed by hand since the express object can no longer take
     * addAttribute() calls once it's been installed.
     */
    protected function addButtonNewTabAttribute(SlideType $slideType)
    {
        $this->addStandardSlideAttribute($slideType, 'boolean', t('Open Link in New Tab'), 'button_new_tab');
    }

    /**
     * Adds the "Slide Duration (seconds)" field to an already-installed
     * Standard slide type. Same reasoning as addMobileImageAttribute().
     */
    protected function addSlideDurationAttribute(SlideType $slideType)
    {
        $this->addStandardSlideAttribute($slideType, 'number', t('Slide Duration (seconds)'), 'slide_duration');
    }

    /**
     * Shared top-up logic for addButtonNewTabAttribute()/addSlideDurationAttribute() -
     * mirrors what ObjectBuilder::addAttribute() and FieldsetBuilder::build()
     * do internally, since Express::getObjectByHandle() returns a plain
     * Entity that can't take addAttribute() calls after initial install.
     */
    protected function addStandardSlideAttribute(SlideType $slideType, string $typeHandle, string $name, string $handle)
    {
        $expressEntity = $slideType->getExpressObject();
        if (!$expressEntity) {
            return;
        }

        $category = $expressEntity->getAttributeKeyCategory();
        if ($category->getAttributeKeyByHandleUncached($handle)) {
            return;
        }

        $entityManager = $this->app->make(EntityManagerInterface::class);
        $type = $this->app->make(TypeFactory::class)->getByHandle($typeHandle);

        $key = new ExpressKey();
        $key->setEntity($expressEntity);
        $settings = $type->getController()->getAttributeKeySettings();
        $settings->setAttributeKey($key);
        $key->setAttributeKeySettings($settings);
        $key->setAttributeKeyName($name);
        $key->setAttributeType($type);
        $key->setAttributeKeyHandle($handle);
        $key->setIsAttributeKeySearchable(true);
        $key->setIsAttributeKeyContentIndexed(false);
        $expressEntity->getAttributes()->add($key);

        $entityManager->persist($expressEntity);
        $entityManager->flush();
        $entityManager->persist($settings);
        $entityManager->flush();
        $category->getSearchIndexer()->updateRepositoryColumns($category, $key);

        $form = $expressEntity->getDefaultEditForm();
        if ($form) {
            $fieldSet = $form->getFieldSets()->isEmpty() ? null : $form->getFieldSets()->first();
            if ($fieldSet) {
                $control = new AttributeKeyControl();
                $control->setAttributeKey($key);
                $control->setFieldSet($fieldSet);
                $control->setPosition($fieldSet->getControls()->count());
                $control->setId((new UuidGenerator())->generate($entityManager, $control));
                $fieldSet->getControls()->add($control);
                $entityManager->persist($control);
                $entityManager->flush();
            }
        }
    }

    /**
     * On sites installed before the multi-word attribute handles below were
     * fixed to be snake_case (see the note in installStandardSlideType()),
     * the AttributeKeys table has since been corrected (button_link_type,
     * button_page, button_url, button_text), but the Express search index
     * table's physical columns were never renamed to match - they're still
     * the old camelCase names (ak_buttonLinkType etc). Express's entry-save
     * flow always writes ak_<handle> for every searchable attribute, so
     * saving any slide fails with "Unknown column 'ak_button_page'" the
     * moment it tries to write to a column that doesn't exist. Renaming
     * (rather than dropping/recreating) keeps any already-indexed values.
     */
    protected function fixButtonAttributeSearchIndexColumns(SlideType $slideType)
    {
        $expressEntity = $slideType->getExpressObject();
        if (!$expressEntity) {
            return;
        }

        $table = $expressEntity->getAttributeKeyCategory()->getIndexedSearchTable();
        $db = $this->app->make('database')->connection();

        $columns = [];
        foreach ($db->fetchAllAssociative(sprintf('SHOW COLUMNS FROM %s', $table)) as $column) {
            $columns[strtolower($column['Field'])] = $column['Field'];
        }

        $renames = [
            'ak_buttonlinktype' => ['ak_button_link_type', 'longtext'],
            'ak_buttonpage' => ['ak_button_page', 'int(11)'],
            'ak_buttonurl' => ['ak_button_url', 'longtext'],
            'ak_buttontext' => ['ak_button_text', 'longtext'],
        ];

        foreach ($renames as $oldLower => [$new, $type]) {
            if (isset($columns[$oldLower]) && !isset($columns[strtolower($new)])) {
                $db->executeStatement(sprintf('ALTER TABLE %s CHANGE %s %s %s', $table, $columns[$oldLower], $new, $type));
            }
        }
    }

    /**
     * Renames the "Image"/"Mobile Image" fields to "Media"/"Mobile Media" on
     * an already-installed Standard slide type - "Image" undersold the field
     * once video files were supported there too.
     */
    protected function renameMediaAttributes(SlideType $slideType)
    {
        $expressEntity = $slideType->getExpressObject();
        if (!$expressEntity) {
            return;
        }

        $entityManager = $this->app->make(EntityManagerInterface::class);
        $category = $expressEntity->getAttributeKeyCategory();

        $renames = [
            'image' => t('Media'),
            'mobile_image' => t('Mobile Media'),
        ];

        $changed = false;
        foreach ($renames as $handle => $newName) {
            $key = $category->getAttributeKeyByHandleUncached($handle);
            if ($key && $key->getAttributeKeyName() !== $newName) {
                $key->setAttributeKeyName($newName);
                $entityManager->persist($key);
                $changed = true;
            }
        }

        if ($changed) {
            $entityManager->flush();
        }
    }
}
