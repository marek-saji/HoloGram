<?php
/**
 * Template consists of two inputs for two postal code parts.
 * @author j.rozanski
 */

$data = $____local_variables['data'];

// first input
$____local_variables = array_merge($____local_variables, array(
    'name_suffix'   => '[0]',
));
$____local_variables['data'] = $data[0];
$attr0 = $t->inc('Forms/FString', $____local_variables);
echo '-';
// second
$____local_variables = array_merge($____local_variables, array(
    'name_suffix'   => '[1]',
));
$____local_variables['data'] = $data[1];
$attr1 = $t->inc('Forms/FString', $____local_variables);
