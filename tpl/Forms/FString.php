<?php
if (!isset($____local_variables['attrs']['type']))
    $____local_variables['attrs']['type'] = 'text';
@$____local_variables['class'] .= ' text';

$____local_variables['data'] = html_entity_decode(@$____local_variables['data']);
return $t->inc('Forms/input', $____local_variables);

