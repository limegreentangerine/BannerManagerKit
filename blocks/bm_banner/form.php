<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <div class="form-group">
        <?php echo $form->label('bmBannersId', t('Choose Banner')); ?>
        <?php echo $form->select('bmBannersId', $banners, $bmBannersId ?? null); ?>
    </div>
</fieldset>