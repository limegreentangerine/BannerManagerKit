<?php defined('C5_EXECUTE') or die('Access Denied.');

if (in_array($controller->getTask(), ['add', 'details', 'save'])) {
    View::element(
        'dashboard/slide_types/details',
        [
            'token' => $token,
            'form' => $form,
            'controller' => $controller,
            'entity' => $entity ?? null,
            'attributeView' => $attributeView ?? null,
            'v' => [
                'id' => (isset($entity)) ? $entity->getID() : ((isset($formContent) && isset($formContent['id'])) ? $formContent['id'] : ''),
                'handle' => (isset($entity)) ? $entity->getHandle() : ((isset($formContent)) ? $formContent['handle'] : ''),
                'name' => (isset($entity)) ? $entity->getName() : ((isset($formContent)) ? $formContent['name'] : ''),
            ],
        ],
        'banner_manager_kit',
    );
} else {
    View::element(
        'dashboard/slide_types/all',
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
