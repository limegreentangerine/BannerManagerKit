<?php

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Form\Service\Form;

/** @var string $headerSearchAction */
/** @var Form $form */

$form = \Core::make(Form::class);
?>

<div class="ccm-header-search-form ccm-ui" data-header="user-manager">
    <form method="get" class="row row-cols-auto g-0 align-items-center" action="<?php echo $headerSearchAction ?>">

        <div class="ccm-header-search-form-input input-group">
            <?php
                echo $form->search('name', [
                    'placeholder' => t('Search'),
                    'class' => 'form-control border-end-0',
                    'autocomplete' => 'off',
                ]);
?>

            <button type="submit" class="input-group-icon">
                <svg width="16" height="16">
                    <use xlink:href="#icon-search"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<script>
    (function ($) {
        $(function () {
            ConcreteEvent.subscribe('SavedSearchCreated', function () {
                window.location.reload();
            });

            ConcreteEvent.subscribe('SavedPresetSubmit', function (e, url) {
                window.location.href = url;
            });
        });
    })(jQuery);
</script>