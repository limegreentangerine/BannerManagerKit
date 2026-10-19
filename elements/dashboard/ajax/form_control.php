<div class="form-group">
    <?php echo $form->label('', h($control->getCustomLabel())); ?>
    <?php if ($control->getIsRequired()) { ?>
        <div class="float-end">
            <span class="text-muted small"><?php echo t('Required'); ?></span>
        </div>
    <?php } ?>

    <?php  echo $control->render($value); ?>
</div>