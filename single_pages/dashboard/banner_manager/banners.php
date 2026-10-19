<?php defined('C5_EXECUTE') or die('Access Denied.');

if (in_array($controller->getTask(), ['add', 'details', 'save'])) {
    View::element(
        'dashboard/banners/details',
        [
            'c' => $c,
            'token' => $token,
            'form' => $form,
            'form_date_time' => $form_date_time,
            'entity' => $entity ?? null,
            'bannerOptions' => $bannerOptions ?? [],
            'v' => [
                'id' => (isset($entity)) ? $entity->getID() : ((isset($formContent) && isset($formContent['id'])) ? $formContent['id'] : ''),
                'handle' => (isset($entity)) ? $entity->getHandle() : ((isset($formContent)) ? $formContent['handle'] : ''),
                'name' => (isset($entity)) ? $entity->getName() : ((isset($formContent)) ? $formContent['name'] : ''),
                'scheduleStart' => (isset($entity)) ? $entity->getScheduleStart() : null,
                'scheduleEnd' => (isset($entity)) ? $entity->getScheduleEnd() : null,
                'fallbackBanner' => (isset($entity) && $entity->getFallbackBanner()) ? $entity->getFallbackBanner()->getID() : ((isset($formContent)) ? ($formContent['fallbackBanner'] ?? '') : ''),
                'showDots' => (isset($entity)) ? $entity->getShowDots() : ((isset($formContent)) ? isset($formContent['showDots']) : true),
                'showArrows' => (isset($entity)) ? $entity->getShowArrows() : ((isset($formContent)) ? isset($formContent['showArrows']) : true),
                'showDotsMobile' => (isset($entity)) ? $entity->getShowDotsMobile() : ((isset($formContent)) ? isset($formContent['showDotsMobile']) : true),
                'showArrowsMobile' => (isset($entity)) ? $entity->getShowArrowsMobile() : ((isset($formContent)) ? isset($formContent['showArrowsMobile']) : true),
                'slideAnimation' => (isset($entity)) ? $entity->getSlideAnimation() : ((isset($formContent)) ? ($formContent['slideAnimation'] ?? 'slide') : 'slide'),
                'slideCount' => (isset($entity)) ? $entity->getSlidePositionsCount() : 0,
            ],
        ],
        'banner_manager_kit',
    );
} else {
    View::element(
        'dashboard/banners/all',
        [
            'token' => $token,
            'name' => $name,
            'groups' => $groups,
        ],
        'banner_manager_kit',
    );
}
