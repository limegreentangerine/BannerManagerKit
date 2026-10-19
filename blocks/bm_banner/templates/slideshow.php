<?php defined('C5_EXECUTE') or die('Access Denied.');
if ($slides === false) {
    echo '<div class="ccm-edit-mode-disabled-item">' . t('No Banner Set') . '</div>';
}
?>
<?php if (isset($slides) && count($slides) > 0) { ?>
    <div id="<?php echo $bID ?? null; ?>" class="bm-banner">
        <?php
            \View::element('slideshow', [
                'identifier' => $bID,
                'spacing' => 0,
                'offset' => 0,
                // Autoplays once there's more than one slide - a slide's own
                // "Slide Duration (seconds)" attribute (if set) then
                // overrides how long that particular slide is shown for.
                'autoplay' => count($slides) > 1,
                'autoplaySpeed' => 5,
                'itemDataAttributes' => function ($slide) {
                    $duration = (int) $slide->getAttribute('slide_duration');

                    return $duration > 0 ? ['duration' => $duration] : [];
                },
                'mobile' => 1,
                'tablet' => 1,
                'desktop' => 1,
                'buttonType' => 'standard',
                'buttonLocation' => 'bottom',
                // Banner display settings. 'dots' is read by the theme's slideshow
                // element; the others are passed through for it to pick up.
                'dots' => (bool) $banner->getShowDots(),
                'arrows' => (bool) $banner->getShowArrows(),
                'dotsMobile' => (bool) $banner->getShowDotsMobile(),
                'arrowsMobile' => (bool) $banner->getShowArrowsMobile(),
                'animation' => $banner->getSlideAnimation(),
                'viewTemplate' => 'view',
                'items' => $slides,
            ]);
    ?>
    </div>
<?php } ?>