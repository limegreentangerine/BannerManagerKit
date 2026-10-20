<?php defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;

$app = Application::getFacadeApplication();
$date = $app->make('date');

/**
 * One banner row: name (links to details), active/inactive badge, schedule.
 */
$renderBannerRow = function ($banner) use ($date) {
    $isActive = $banner->isCurrentlyActive();
    $scheduleStart = $banner->getScheduleStart();
    $scheduleEnd = $banner->getScheduleEnd();

    if (!$scheduleStart && !$scheduleEnd) {
        $schedule = t('No schedule');
    } else {
        $schedule = sprintf(
            '%s &ndash; %s',
            $scheduleStart ? h($date->formatDateTime($scheduleStart, true)) : t('No start date'),
            $scheduleEnd ? h($date->formatDateTime($scheduleEnd, true)) : t('No end date'),
        );
    }
    ?>
    <tr data-details-url="<?php echo h(View::url('/dashboard/banner_manager/banners/details/' . $banner->getID())); ?>">
        <td><?php echo h($banner->getName()); ?></td>
        <td>
            <span class="badge <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
                <?php echo $isActive ? t('Active') : t('Inactive'); ?>
            </span>
        </td>
        <td><?php echo $schedule; ?></td>
    </tr>
    <?php
};
?>

<div class="ccm-dashboard-content-full">

    <?php if (empty($groups['byPage']) && empty($groups['unassigned'])) { ?>
        <div class="alert alert-warning">
            <?php echo t('No banners found.') ?>
        </div>
    <?php } else { ?>

        <?php foreach ($groups['byPage'] as $group) { ?>
            <h3>
                <a href="<?php echo h($group['page']->getCollectionLink()); ?>" target="_blank">
                    <?php echo h($group['page']->getCollectionName()); ?>
                </a>
            </h3>
            <div class="table-responsive mb-4">
                <table class="ccm-search-results-table">
                    <thead>
                        <tr>
                            <th><?php echo t('Name'); ?></th>
                            <th><?php echo t('Status'); ?></th>
                            <th><?php echo t('Schedule'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group['banners'] as $banner) {
                            $renderBannerRow($banner);
                        } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <?php if (!empty($groups['unassigned'])) { ?>
            <h3><?php echo t('Unassigned'); ?></h3>
            <div class="help-block mb-2"><?php echo t('Not currently placed on any page.'); ?></div>
            <div class="table-responsive mb-4">
                <table class="ccm-search-results-table">
                    <thead>
                        <tr>
                            <th><?php echo t('Name'); ?></th>
                            <th><?php echo t('Status'); ?></th>
                            <th><?php echo t('Schedule'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups['unassigned'] as $banner) {
                            $renderBannerRow($banner);
                        } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

    <?php } ?>

</div>

<div class="ccm-dashboard-header-buttons">
    <a href="<?php echo View::url('/dashboard/banner_manager/banners/add/'); ?>" class="btn btn-primary"><?php echo t('Add Banner'); ?></a>
</div>