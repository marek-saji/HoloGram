<?php
/**
 * Field FDate template
 *
 * For parameters refer to FString
 */
@$____local_variables['class'] .= ' date';
@$____local_variables['validate_js_event'] = 'change';
if(!isset($____local_variables['attrs']['title']))
{
    $____local_variables['attrs']['title'] = $this->trans('yyyy-mm-dd');
    $____local_variables['class'] .= ' helpval';
}

return $this->inc('Forms/FString', $____local_variables);

