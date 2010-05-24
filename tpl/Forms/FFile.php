<?php
/**
 * FFile template: choosing file
 * 
 * @see FFile_del which shows currently uploaded file with option to delete it
 */
if (!isset($____local_variables['attrs']['type']))
    $____local_variables['attrs']['type'] = 'file';
@$____local_variables['class'] .= ' file';
if (!isset($____local_variables['validate_js_event']))
    $____local_variables['validate_js_event'] = 'change';

$____local_variables['data'] = null;

return $t->inc('Forms/input', $____local_variables);
