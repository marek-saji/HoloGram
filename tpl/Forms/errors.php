<?php
/**
 * Display errors under the field
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * @param string $id form element id REQUIRED
 * @param array $errors with error messages as values
 * @param boolean $err_handling add div.field error, bind events (if $ajax)
 * @param boolean $ajax use ajax validation
 * @param string $js_event javascript event to launch validation on
 */
extract(array_merge(
        array(
            'id'        => null,
            'errors'    => array(),
            'err_handling' => true,
            'ajax'      => true,
            'js_event'  => 'blur'
        ),
        (array) $____local_variables
    ));

if (!$err_handling)
    return;

if (!$id)
    trigger_error('No ID passed to tpl/Forms/errors', E_USER_NOTICE);

$err_id = $id . '__err';
if ((@$errors) && is_array($errors))
    $errors = implode(', ', $errors);
else
    $errors = '';

printf('<span class="field_error" id="%s" style="%s">%s</span>',
         $err_id, ($errors?'':'display:none'), $errors );
if($ajax)
    $v->addOnLoad('$(\'#'.$id.'\').'.$js_event.'(function(){return hg("input_validate")(this);})');

return array('err_id' => $err_id);

