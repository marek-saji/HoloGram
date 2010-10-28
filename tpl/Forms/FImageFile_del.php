<?php
/**
 * Displays "delete image" option
 *
 * when image passed, will render it above the checkbox
 * @author m.augustynowicz
 *
 * @param array $image {@see tpl/uploaded_image} for details
 * @param string $image_size {@see tpl/uploaded_image} for details
 */
extract(array_merge(
    array(
        'image' => null,
        'image_size' => 'original',
    ),
    $____local_variables
), EXTR_REFS|EXTR_PREFIX_INVALID, 'param');

if (!@$image['id'])
    return false;

@$____local_variables['class'] .= ' image';
?>

<p class="current-value <?=$____local_variables['class']?>">
    <?php
    $this->inc('uploaded_image', array(
        $image,
        'size' => $image_size
    ));
    ?>
</p>

<?php

return $t->inc('Forms/FBool', $____local_variables);

