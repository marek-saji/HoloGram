<?php
/**
 * Renders two <input type="password" /> tags.
 *
 * @uses tpl/Forms/input look there for params info
 *
 * @author m.augustynowicz
 */

extract(array_merge(
        array(
            'autocomplete'  => false,
            'validate_js_event' => 'blur',
        ),
        (array) $____local_variables
));

$____local_variables = array_merge($____local_variables, array(
    'data'  => '',
    'ajax'  => false
));

// first input
$____local_variables = array_merge($____local_variables, array(
    'id'            => ($id0 = $id . '_0'),
    'input'         => ($input . '][0'), // dirty
    'err_handling'  => false
));
$attr0 = $t->inc('Forms/FPassword_single', $____local_variables);

// second
$____local_variables = array_merge($____local_variables, array(
    'id'            => ($id1 = $id . '_1'),
    'input'         => ($input . '][1'), // dirty
    'class'         => @$____local_variables['class'] . ' repetition',
    'err_handling'  => true
));
$attr1 = $t->inc('Forms/FPassword_single', $____local_variables);

if($ajax)
{
    $err_id = $attr1['err_id'];
    $v->addOnLoad('$(\'#'.$id0.'\').'.$validate_js_event.'(function(){return hg("input_validate")(this,"#'.$err_id.'");})');
    $v->addOnLoad('$(\'#'.$id1.'\').'.$validate_js_event.'(function(){return hg("input_validate")([this,"#'.$id0.'"],"#'.$err_id.'");})');
}

return $attr0;

/* @example
 * validate in your Component with something like this:
 * <code>

    public function validateAddPasswd(&$value)
    {
        $errors = array();

        if (is_array($value))
        {
            $value0 = @array_shift($value);
            $value1 = @array_shift($value);
            if (null !== $value1) // two values given
            {
                if (! $ret = ($value0 == $value1))
                {
                    $errors['mismatch'] = $this->trans('Passwords do not match');
                    $errors['stop_validation'] = true;
                }
            }
            $value = (string) $value0;
        }

        return $errors;
    }

 * </code>
 */

