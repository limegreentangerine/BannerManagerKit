<?php defined('C5_EXECUTE') or die('Access Denied.');
$editor = Core::make('editor');
$editor->setAllowFileManager(false);
$editorJavascript = $editor->outputStandardEditorInitJSFunction();
$returnBannerId = (string) ($_GET['bannerId'] ?? '');
?>

<?php if ($v['id']) { ?>
    <div class="ccm-dashboard-header-buttons">
        <form method="POST" action="<?php echo h($view->action('duplicate', $v['id'])); ?>" class="d-inline">
            <?php echo $token->output('duplicate-slide-id-' . $v['id']); ?>
            <button type="submit" class="btn btn-secondary"><?php echo t('Duplicate'); ?></button>
        </form>
        <button data-launch-dialog="delete-dialog" class="btn btn-danger"><?php echo t('Delete'); ?></button>
    </div>

    <div style="display: none" data-dialog="delete-dialog" class="ccm-ui">
        <form data-dialog-form="delete-form" method="POST" action="<?php echo $view->action('delete', $v['id']); ?>">
            <?php echo $token->output('delete-slide-id-' . $v['id']); ?>
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
            if (data.form === 'delete-form') {
                window.location.href = <?php echo json_encode((string) URL::to('/dashboard/banner_manager/slides')); ?>;
            }
        });
    });
    </script>
<?php } ?>

<form method="post" action="<?php echo h($view->action('save')); ?><?php echo $returnBannerId ? '?bannerId=' . urlencode($returnBannerId) : ''; ?>">
    <?php // Not ccm_token: the injected Express form posts its own ccm_token ('express_form') and would overwrite this one.?>
    <input type="hidden" name="submit_token" value="<?php echo h($token->generate('submit')); ?>" />
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
                $opts['readonly'] = ' readonly';
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

        <div class="form-group">
            <?php echo $form->label('slideType', t('Slide Type')); ?>
            <div class="float-end">
                <span class="text-muted small"><?php echo t('Required'); ?></span>
            </div>
            <?php echo $form->select('slideType', $types, $v['slideType']); ?>
            <div class="help-block"><?php echo t('You can create new Slide Types <a href="%s" target="_blank">here</a>, then come back and refresh this page.', URL::to('/dashboard/banner_manager/slide_types')); ?></div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?php echo t('Banners'); ?></legend>

        <div class="form-group">
            <?php foreach ($banners as $banner) {
                $checked = ($returnBannerId && $returnBannerId === $banner->getID()) ? 'checked' : (($entity && in_array($banner->getID(), $entity->getBannerIDs())) ? 'checked' : '');
                ?>

                <div class="input-group">
                    <div class="checkbox">
                        <label for="<?php echo h($banner->getHandle()); ?>" >
                            <input type="checkbox" id="<?php echo h($banner->getHandle()); ?>" name="banners[]" class="ccm-input-checkbox" value="<?php echo h($banner->getID()); ?>" <?php echo $checked; ?> />
                            <span><?php echo h($banner->getName()); ?></span>
                        </label>
                    </div>
                </div>

            <?php } ?>
        </div>
    </fieldset>

    <div id="form-fields" data-values="<?php echo h($v['expressEntry']); ?>"></div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <?php if ($returnBannerId) { ?>
                <a href="<?php echo h(URL::to('/dashboard/banner_manager/banners/details', $returnBannerId)); ?>" class="btn btn-secondary float-start"><?php echo t('Go Back'); ?></a>
            <?php } else { ?>
                <a href="<?php echo URL::to('/dashboard/banner_manager/slides'); ?>" class="btn btn-secondary float-start"><?php echo t('Cancel'); ?></a>
            <?php } ?>
            <?php echo $form->submit('save', ($entity) ? t('Update Slide') : t('Save Slide'), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>
</form>