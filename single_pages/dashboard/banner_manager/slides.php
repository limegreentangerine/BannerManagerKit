<?php defined('C5_EXECUTE') or die('Access Denied.');

if (in_array($controller->getTask(), ['add', 'details', 'save'])) {
    $values = [
        'id' => (isset($entity)) ? $entity->getID() : ((isset($formContent) && isset($formContent['id'])) ? $formContent['id'] : false),
        'handle' => (isset($entity)) ? $entity->getHandle() : ((isset($formContent)) ? $formContent['handle'] : false),
        'slideType' => (isset($entity) && $entity->getSlideType()) ? $entity->getSlideType()->getID() : ((isset($formContent)) ? $formContent['slideType'] : false),
        'name' => (isset($entity)) ? $entity->getName() : ((isset($formContent)) ? $formContent['name'] : false),
        'expressEntry' => ((isset($entity) && $entity->getExpressEntry()) ? $entity->getExpressEntry()->getID() : false),
    ];

    if ($error->has()) {
        foreach ($formContent as $key => $value) {
            $values[$key] = $value;
        }
    }

    View::element(
        'dashboard/slides/details',
        [
            'token' => $token,
            'form' => $form,
            'entity' => $entity ?? null,
            'banners' => $banners,
            'types' => $types,
            'h' => [
                'al' => $concrete_asset_library,
                'ps' => $form_page_selector,
            ],
            'v' => $values,
        ],
        'banner_manager_kit',
    );
} else {
    View::element(
        'dashboard/slides/all',
        [
            'token' => $token,
            'params' => $params,
            'items' => $items,
            'result' => $result,
            'pagination' => $pagination,
        ],
        'banner_manager_kit',
    );
}
