<?php
/**
 * Displays "delete file" option
 * @param string $filename File's name
 * @author m.jutkiewicz
 */
extract(array_merge(
    array(
        'filename' => null,
    ),
    $____local_variables
), EXTR_REFS|EXTR_PREFIX_INVALID, 'param');

echo '<p>' . $filename . '</p>';
return $t->inc('Forms/FBool', $____local_variables);

