<?php defined('C5_EXECUTE') or die('Access Denied.');
use Concrete\Package\BannerManagerKit\Controller\Element\Dashboard\SlideType\Control;

?>

<?php if ($v['id']) { ?>
    <div class="ccm-dashboard-header-buttons">
        <button data-launch-dialog="delete-dialog" class="btn btn-danger"><?php echo t('Delete'); ?></button>
    </div>

    <div style="display: none" data-dialog="delete-dialog" class="ccm-ui">
        <form data-dialog-form="delete-form" method="POST" action="<?php echo $view->action('delete', $v['id']); ?>">
            <?php echo $token->output('delete-slide-type-id-' . $v['id']); ?>
            <p><?php echo t('Are you sure you want to permanently delete this slide?'); ?></p>
            <p><strong><?php echo t('WARNING: this operation can not be undone!'); ?></strong></p>
        </form>
        <div class="dialog-buttons">
            <button class="btn btn-default pull-left" data-dialog-action="cancel"><?php echo t('Cancel'); ?></button>
            <button class="btn btn-danger pull-right" data-dialog-action="submit"><?php echo t('Delete'); ?></button>
        </div>
    </div>

    <script>
    $(function() {
        var $dialog = $('div[data-dialog="delete-dialog"]');
        $('[data-launch-dialog="delete-dialog"]').on('click', function(e) {
            e.preventDefault();
            jQuery.fn.dialog.open({
                element: $dialog,
                modal: true,
                width: 420,
                title: <?php echo json_encode(t('Confirm Delete')); ?>,
                height: 'auto'
            });
        });
        ConcreteEvent.subscribe('AjaxFormSubmitSuccess', function(e, data) {
            console.log('Delete SLide Type:', data);
            if (data.form === 'delete-form') {
                window.location.href = <?php echo json_encode((string) URL::to('/dashboard/banner_manager/slide_types')); ?>;
            }
        });
    });
    </script>
<?php } ?>

<form method="post" action="<?php echo $view->action('save'); ?>">
    <?php echo $token->output('submit') ?>
    <?php echo ($entity) ? $form->hidden('id', $entity->getID()) : ''; ?>

    <fieldset>
        <?php if ($entity) { ?>
            <div class="form-group">
                <?php echo $form->label('handle', t('Handle')); ?>
                <div class="float-end">
                    <span class="text-muted small"><?php echo t('Required'); ?></span>
                </div>
                <?php
                    $opts = [];
            if ($entity) {
                $opts['readonly'] = 'readonly';
            }
            echo $form->text('handle', $v['handle'], $opts);
            ?>
            </div>
        <?php } ?>

        <div class="form-group">
            <?php echo $form->label('name', t('Name')); ?>
            <div class="float-end">
                <span class="text-muted small"><?php echo t('Required'); ?></span>
            </div>
            <?php echo $form->text('name', $v['name']); ?>
        </div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo URL::to('/dashboard/banner_manager/slide_types'); ?>" class="btn btn-secondary float-start"><?php echo t('Cancel'); ?></a>
            <?php echo $form->submit('save', ($entity) ? t('Update Slide Type') : t('Save Slide Type'), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>
</form>

<?php if ($entity && $express = $entity->getExpressObject()) {
    $attributes = $express->getAttributes();
    $expressForm = $express->getDefaultEditForm();
    ?>
    <?php if (count($attributes)) { ?>
        <fieldset>
            <legend>
                <div class="d-flex justify-content-between align-items-center">
                    <span><?php echo t('Form'); ?></span>

                    <div class="flex-shrink-1">
                        <a href="<?php echo URL::to('/dashboard/banner_manager/slide_types/attributes', $entity->getID()); ?>" class="btn btn-secondary btn-sm">
                            <?php echo t('Edit Attributes'); ?>
                        </a>

                        <button type="button" class="btn btn-secondary btn-sm" data-dialog="add-set">
                            <?php echo t('Add FieldSet'); ?>
                        </button>
                    </div>
                </div>
            </legend>

            <hr />

            <?php if (count($expressForm->getFieldSets())) { ?>
                <?php foreach ($expressForm->getFieldSets() as $set) {?>
                    <div style="display: none">
                        <div id="ccm-dialog-delete-set-<?php echo $set->getID() ?>" class="ccm-ui">
                            <form method="post" action="<?php echo $view->action('delete_fieldset', $entity->getID()) ?>">
                                <?php echo $token->output('delete_set') ?>
                                <?php echo $form->hidden('field_set_id', $set->getID()); ?>

                                <p>
                                    <?php echo t('Are you sure you want to delete this field set? This cannot be undone.') ?>
                                </p>

                                <div class="dialog-buttons">
                                    <button class="btn btn-secondary float-start"
                                            onclick="jQuery.fn.dialog.closeTop()">
                                        <?php echo t('Cancel') ?>
                                    </button>

                                    <button class="btn btn-danger float-end"
                                            onclick="$('#ccm-dialog-delete-set-<?php echo $set->getId() ?> form').submit()">
                                        <?php echo t('Delete Control') ?>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div id="ccm-dialog-update-set-<?php echo $set->getID() ?>" class="ccm-ui">
                            <form method="post" action="<?php echo $view->action('update_fieldset', $entity->getID()) ?>">
                                <?php echo $token->output('update_set') ?>
                                <?php echo $form->hidden('field_set_id', $set->getID()); ?>

                                <div class="form-group">
                                    <?php echo $form->label('name', tc('Name of a set', 'Set Name')) ?>
                                    <?php echo $form->text('name', $set->getTitle()) ?>
                                </div>
                            </form>

                            <div class="dialog-buttons">
                                <button class="btn btn-secondary float-start" onclick="jQuery.fn.dialog.closeTop()">
                                    <?php echo t('Cancel') ?>
                                </button>

                                <button class="btn btn-primary float-end"
                                        onclick="$('#ccm-dialog-update-set-<?php echo $set->getID() ?> form').submit()">
                                    <?php echo t('Update Set') ?>
                                </button>
                            </div>
                        </div>

                    </div>


                    <div class="ccm-item-set panel panel-default" data-field-set="<?php echo $set->getID() ?>">
                        <div class="panel-heading">
                            <ul class="ccm-item-set-controls">
                                <li>
                                    <!--suppress HtmlUnknownAttribute -->
                                    <a href="<?php echo URL::to('/dashboard/system/express/entities/forms/add_control', $set->getID()) ?>"
                                    class="dialog-launch"
                                    dialog-title="<?php echo h(t('Add Form Control')) ?>"
                                    dialog-width="640"
                                    dialog-height="400"
                                    data-command="add-form-set-control">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="#" data-command="move-set" style="cursor: move">
                                        <i class="fas fa-arrows-alt"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="#"
                                    data-dialog="update-set-<?php echo $set->getId() ?>"
                                    data-dialog-title="<?php echo h(t('Update Set')) ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="#"
                                    data-dialog="delete-set-<?php echo $set->getId() ?>"
                                    data-dialog-title="<?php echo h(t('Delete Control')) ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </li>
                            </ul>

                            <div>
                                <?php echo $set->getTitle() ? h($set->getTitle()) : t('(No Title)') ?>
                            </div>
                        </div>

                        <table class="table table-hover" style="width: 100%;">
                            <tbody>
                            <?php
                                foreach ($set->getControls() as $control) {
                                    $element = new Control($control, $entity->getID());
                                    /** @noinspection PhpDeprecationInspection */
                                    echo $element->render();
                                }
                    ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="alert alert-info"><?php echo t('You have not created any field sets.') ?></div>
            <?php } ?>
        </fieldset>

        <div id="ccm-dialog-add-set" class="ccm-ui" style="display:none">
            <form method="post" action="<?php echo $view->action('add_fieldset', $expressForm->getID(), $entity->getID()) ?>">
                <?php echo $token->output('add_set') ?>

                <div class="form-group">
                    <?php echo $form->label('name', tc('Name of a set', 'Set Name')) ?>
                    <?php echo $form->text('name') ?>
                </div>
            </form>

            <div class="dialog-buttons">
                <button class="btn btn-secondary float-start" onclick="jQuery.fn.dialog.closeTop()">
                    <?php echo t('Cancel') ?>
                </button>

                <button class="btn btn-primary float-end" onclick="$('#ccm-dialog-add-set form').submit()">
                    <?php echo t('Add Set') ?>
                </button>
            </div>
        </div>

        <script>
            $(function() {
                $('[data-dialog]').on('click', function () {
                    var $element = $('#ccm-dialog-' + $(this).attr('data-dialog'));
                    if ($(this).attr('data-dialog-title')) {
                        var title = $(this).attr('data-dialog-title');
                    } else {
                        var title = $(this).text();
                    }
                    jQuery.fn.dialog.open({
                        element: $element,
                        modal: true,
                        width: 320,
                        title: title,
                        height: 'auto'
                    });
                });

                $('#ccm-dashboard-content').sortable({
                    handle: 'a[data-command=move-set]',
                    items: '.ccm-item-set',
                    cursor: 'move',
                    axis: 'y',
                    stop: function () {
                        var formData = [{
                            'name': 'token',
                            'value': '<?php echo $token->generate('update_set_display_order')?>'
                        }];
                        $('.ccm-item-set').each(function () {
                            formData.push({'name': 'set[]', 'value': $(this).attr('data-field-set')});
                        });
                        $.ajax({
                            type: 'post',
                            data: formData,
                            url: '<?php echo $view->action('update_fieldset_display_order', $expressForm->getID())?>',
                            success: function () {

                            }
                        });
                    }
                });


                $('div.ccm-item-set').sortable({
                    handle: 'a[data-command=move-control]',
                    items: '.ccm-item-set-item',
                    cursor: 'move',
                    axis: 'y',
                    helper: function (e, ui) { // prevent table columns from collapsing
                        ui.addClass('active');
                        ui.children().each(function () {
                            $(this).width($(this).width());
                        });
                        return ui;
                    },
                    stop: function (e, ui) {
                        var $set = $(ui.item).closest('div[data-field-set]');
                        var formData = [{
                            'name': 'token',
                            'value': '<?php echo $token->generate('update_set_control_display_order')?>'
                        }, {
                            'name': 'set',
                            'value': $set.attr('data-field-set')
                        }];

                        $set.find('.ccm-item-set-item').each(function () {
                            formData.push({'name': 'control[]', 'value': $(this).attr('data-field-set-control')});
                        });


                        $.ajax({
                            type: 'post',
                            data: formData,
                            url: '<?php echo $view->action('update_set_control_display_order', $expressForm->getID())?>',
                            success: function () {
                            }
                        });

                    }
                });
            });
        </script>
    <?php } else { ?>
        <a href="<?php echo URL::to('/dashboard/banner_manager/slide_types/attributes', $entity->getID()); ?>" class="btn btn-primary">
            <?php echo t('Add Attributes'); ?>
        </a>
    <?php } ?>
<?php } ?>