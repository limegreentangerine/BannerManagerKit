<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if ($v['id']) { ?>
    <div class="ccm-dashboard-header-buttons">
        <button data-launch-dialog="delete-dialog" class="btn btn-danger"><?php echo t('Delete'); ?></button>
    </div>

    <div style="display: none" data-dialog="delete-dialog" class="ccm-ui">
        <form data-dialog-form="delete-form" method="POST" action="<?php echo $view->action('delete', $v['id']); ?>">
            <?php echo $token->output('delete-banner-id-' . $v['id']); ?>
            <p><?php echo t('Are you sure you want to permanently delete this feed?'); ?></p>
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
                window.location.href = <?php echo json_encode((string) URL::to('/dashboard/banner_manager/banners/')); ?>;
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
    </fieldset>

    <fieldset>
        <legend><?php echo t('Schedule'); ?></legend>

        <div class="form-group">
            <?php echo $form->label('scheduleStart', t('Schedule Start')); ?>
            <div class="help-block"><?php echo t('Leave unchecked to show this banner with no start date.'); ?></div>
            <?php echo $form_date_time->datetime('scheduleStart', $v['scheduleStart'], true); ?>
        </div>

        <div class="form-group">
            <?php echo $form->label('scheduleEnd', t('Schedule End')); ?>
            <div class="help-block"><?php echo t('Leave unchecked to show this banner with no end date.'); ?></div>
            <?php echo $form_date_time->datetime('scheduleEnd', $v['scheduleEnd'], true); ?>
        </div>

        <div class="form-group">
            <?php echo $form->label('fallbackBanner', t('Fallback Banner')); ?>
            <div class="help-block"><?php echo t('Shown instead of this banner whenever it is outside its scheduled window (before the start date or after the end date). Leave blank to show nothing during that time.'); ?></div>
            <?php echo $form->select('fallbackBanner', $bannerOptions, $v['fallbackBanner']); ?>
        </div>
    </fieldset>

    <fieldset data-slideshow-multi-slide-options data-slide-count="<?php echo (int) $v['slideCount']; ?>" style="display: none;">
        <legend><?php echo t('Slideshow'); ?></legend>
        <div class="help-block"><?php echo t('These options only apply once a banner has more than one slide.'); ?></div>

        <div class="form-group">
            <?php echo $form->label('slideAnimation', t('Slide Animation')); ?>
            <?php echo $form->select('slideAnimation', ['slide' => t('Slide'), 'fade' => t('Fade')], $v['slideAnimation']); ?>
        </div>

        <div class="form-group">
            <div class="form-check">
                <?php echo $form->checkbox('showDots', 1, $v['showDots'], ['class' => 'form-check-input']); ?>
                <?php echo $form->label('showDots', t('Show Dots'), ['class' => 'form-check-label']); ?>
            </div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <?php echo $form->checkbox('showArrows', 1, $v['showArrows'], ['class' => 'form-check-input']); ?>
                <?php echo $form->label('showArrows', t('Show Arrows'), ['class' => 'form-check-label']); ?>
            </div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <?php echo $form->checkbox('showDotsMobile', 1, $v['showDotsMobile'], ['class' => 'form-check-input']); ?>
                <?php echo $form->label('showDotsMobile', t('Show Dots on Mobile'), ['class' => 'form-check-label']); ?>
            </div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <?php echo $form->checkbox('showArrowsMobile', 1, $v['showArrowsMobile'], ['class' => 'form-check-input']); ?>
                <?php echo $form->label('showArrowsMobile', t('Show Arrows on Mobile'), ['class' => 'form-check-label']); ?>
            </div>
        </div>
    </fieldset>

    <script type="text/javascript">
        $(function() {
            var $options = $('[data-slideshow-multi-slide-options]');
            if (parseInt($options.attr('data-slide-count'), 10) > 1) {
                $options.show();
            }
        });
    </script>

    <?php if ($entity) { ?>
        <hr class="mb-4" />

        <section data-section="slides">
            <a href="<?php echo URL::to('/dashboard/banner_manager/slides/add?bannerId=' . $entity->getID()); ?>" class="btn-section btn btn-secondary"><?php echo t('Add Slide'); ?></a>

            <h3><?php echo t('Slides'); ?></h3>

            <div class="help-block mt-4"><?php echo t('To delete a slide, edit the slide you want to remove.'); ?></div>

            <table class="ccm-search-results-table compact-results">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php echo t('Name'); ?></th>
                        <th><?php echo t('Type'); ?></th>
                        <th><?php echo t('Added'); ?></th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody class="ccm-file-set-file-list" data-field-set="<?php echo $entity->getID(); ?>">
                    <?php
                    if ($entity && count($entity->getSlidePositions()) > 0) {
                        foreach ($entity->getSlidePositions() as $slidePosition) {
                            View::element('dashboard/banners/slide', [
                                'c' => $c,
                                'banner' => $entity,
                                'slidePosition' => $slidePosition,
                                'slide' => $slidePosition->getSlide(),
                                'position' => $slidePosition->getPosition(),
                                'token' => $token,
                            ], 'banner_manager_kit');
                        }
                    }
        ?>
                </tbody>
            </table>
        </section>


        <script type="text/javascript">
            $(function() {
                $('[data-dialog]').on('click', function() {
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

                $(".ccm-file-set-file-list").sortable({
                    cursor: 'move',
                    opacity: 0.5,
                    axis: 'y',
                    helper: function (evt, elem) {
                        let ret = $(elem).clone();
                        let i;

                        ret.width(elem.outerWidth());

                        let retChilds = $(ret.children());
                        let elemChilds = $(elem.children());

                        for (i = 0; i < elemChilds.length; i++)
                            $(retChilds[i]).width($(elemChilds[i]).outerWidth());

                        return ret;
                    },
                    placeholder: "ccm-file-set-file-placeholder",
                    stop: function (e,ui) {
                        var $set = $(ui.item).closest('[data-field-set]');
                        var formData = [{
                            'name': 'banner',
                            'value': $set.attr('data-field-set')
                        }];


                        $set.find('[data-field-set-control]').each(function() {
                            formData.push({'name': 'slidePositions[]', 'value': $(this).attr('data-field-set-control')});
                        });

                        formData.push({'name': 'ccm_token', 'value': <?php echo json_encode($token->generate('update-slide-order-' . $entity->getID())); ?>});

                        $.ajax({
                            type: 'post',
                            data: formData,
                            url: <?php echo json_encode((string) $view->action('updateSlideOrder', $entity->getID())); ?>
                        });
                    }
                });
            });
        </script>

        <style type="text/css">
            .ccm-file-set-file-list:hover {
                cursor: move
            }

            .ccm-file-set-file-placeholder {
                background-color: #ffd !important;
            }

            .ccm-file-set-file-placeholder td {
                background: transparent !important;
            }
        </style>

    <?php } ?>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo URL::to('/dashboard/banner_manager/banners/'); ?>" class="btn btn-secondary float-start"><?php echo t('Cancel'); ?></a>
            <?php echo $form->submit('save', ($entity) ? t('Update Banner') : t('Save Banner'), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>
</form>