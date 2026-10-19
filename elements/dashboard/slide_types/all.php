<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="ccm-dashboard-content-full">

    <?php if (empty($items)) { ?>
        <div class="alert alert-warning">
            <?php echo t('No slide types found.') ?>
        </div>
    <?php } else { ?>

        <div class="table-responsive">
            <table class="ccm-search-results-table">
                <thead>
                    <tr>
                        <?php foreach ($result->getColumns() as $column) { ?>
                            <?php if ($column->isColumnSortable()) { ?>
                                <th class="<?php echo $column->getColumnStyleClass(); ?>">
                                    <a href="<?php echo $column->getColumnSortURL(); ?>"><?php echo h($column->getColumnTitle()); ?></a>
                                </th>
                            <?php } else { ?>
                                <th><span><?php echo h($column->getColumnTitle()); ?></span></th>
                            <?php } ?>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) { ?>
                        <tr data-details-url="<?php echo h($item->getViewUrl()); ?>">
                            <?php foreach ($item->getColumns() as $column) { ?>
                                <td><?php echo nl2br(h($column->getColumnValue())); ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination) { ?>
            <div class="ccm-search-results-pagination">
                <?php echo $pagination ?>
            </div>
        <?php } ?>

    <?php } ?>

</div>

<div class="ccm-dashboard-header-buttons">
    <a href="<?php echo View::url('/dashboard/banner_manager/slide_types/add/'); ?>" class="btn btn-primary"><?php echo t('Add Slide Type'); ?></a>
</div>