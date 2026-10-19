<tr id="slide__<?php echo $slidePosition->getID(); ?>" class="ui-sortable-handle" data-field-set-control="<?php echo $slidePosition->getID(); ?>">
    <td><i class="fas fa-arrows-alt-v"></i></td>
    <td data-type="name"><?php echo h($slide->getName()); ?></td>
    <td data-type="type"><?php echo h($slide->getSlideTypeName()); ?></td>
    <td data-type="added"><?php echo h($slide->getDateCreatedString()); ?></td>
    <td>
        <ul class="ccm-item-set-controls float-end">
            <li class="h5"><a href="<?php echo h(URL::to('/dashboard/banner_manager/slides', 'details', $slide->getID()) . '?bannerId=' . $banner->getID()); ?>"><i class="fas fa-edit"></i></a></li>
        </ul>
    </td>
</tr>