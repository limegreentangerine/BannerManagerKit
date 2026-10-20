<?php
/** @noinspection PhpDeprecationInspection */
$attributeView->render();
?>

<?php if ($controller->getTask() == 'view') { ?>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo URL::to('/dashboard/banner_manager/slide_types/details', $slideTypeId); ?>" class="btn btn-secondary float-end">
                <?php echo t('Back to Slide Type'); ?>
            </a>
        </div>
    </div>
<?php } elseif ($controller->getTask() == 'select_type') { ?>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo URL::to('/dashboard/banner_manager/slide_types/attributes', $slideTypeId); ?>" class="btn btn-secondary float-start">
                <?php echo t('Back'); ?>
            </a>
            <?php echo $form->submit('save', t('Add'), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>

    <script>
        $(function() {
            $('input#save').on('click', function(e) {
                e.preventDefault();
                $('#ccm-attribute-key-form').submit();
            });
        });
    </script>
<?php } else { ?>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo URL::to('/dashboard/banner_manager/slide_types/attributes', $slideTypeId); ?>" class="btn btn-secondary float-start">
                <?php echo t('Back'); ?>
            </a>
            <?php echo $form->submit('save', t('Edit'), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>

    <script>
        $(function() {
            $('input#save').on('click', function(e) {
                e.preventDefault();
                $('#ccm-attribute-key-form').submit();
            });
        });
    </script>
<?php } ?>