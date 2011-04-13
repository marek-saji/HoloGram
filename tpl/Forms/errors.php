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
 * @param int $timeout while-editing validation delay
 */
extract(array_merge(
        array(
            'id'        => null,
            'errors'    => array(),
            'err_handling' => true,
            'ajax'      => true,
            'js_event'  => 'blur',
            'timeout'   => 1500
        ),
        (array) $____local_variables
    ));

if (!$err_handling)
    return;

if (!$id)
    trigger_error('No ID passed to tpl/Forms/errors', E_USER_NOTICE);

$err_id = $id . '__err';
if ((@$errors) && is_array($errors))
    $errors = implode('</li><li>', $errors);
else
    $errors = '';

$timeout = json_encode((int) $timeout);

printf(
    '<ol class="field_error" id="%s" style="%s">%s</ol>',
    $err_id,
    $errors ? '' : 'display:none',
    $errors ? "<li>{$errors}</li>" : ''
);
if($ajax)
{
    //$v->addOnLoad('$(\'#'.$id.'\').bind("validate", function(){return hg("input_validate")(this);}).bind("'.$js_event.'", function(){$(this).trigger("validate")})');
    $v->addOnLoad( <<< JS_VALIDATE
    \$('#$id')
        .bind('validate.validation', function(e, timeout){
            var \$this = \$(this),
                timeout = arguments[1] || 0
            ;
            window.clearTimeout(\$this.data('validationTimeout'));
            if (timeout)
            {
                \$this.data('validationTimeout', window.setTimeout(function(){
                    \$this.trigger('validate');
                }, timeout));
            }
            else
            {
                hg("input_validate")(\$this[0]);
            }
        })
        .bind('$js_event.validation', function(){\$(this).trigger('validate')})
        .bind('keyup.validation', function(){\$(this).trigger('validate', [$timeout])})
    ;
JS_VALIDATE
);
}

return array('err_id' => $err_id);

