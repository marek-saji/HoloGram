<?php
/**
 * Renders two <input /> tags.
 * hidden field with value=0 and checkbox with value=1
 * This way you get all checkboxes in $_POST.
 *
 * @uses tpl/Forms/input look there for params info
 *
 * @author m.augustynowicz
 *
 * (params passed as local variables)
 * @param null|string $label will add this text after checkbox and wrap it in <label />
 */

extract(array_merge(
        array(
            'label'  => null
        ),
        (array) $____local_variables
    ));

@$____local_variables['class'] .= ' checkbox';

// $err_handling is set there to false
$t->inc('Forms/hidden', array_merge(
        $____local_variables,
        array(
            'id' => false,
            'data' => '0',

        )
    ));

$attrs = & $____local_variables['attrs'];
$attrs['type'] = 'checkbox';
if ($f->anyToBool($____local_variables['data']))
    $attrs['checked'] = 'checked';
$____local_variables['data'] = 1;

if ($label)
    echo '<label>';

$return_me = $t->inc('Forms/input', $____local_variables);

if ($label)
    printf('%s</label>', $label);

return $return_me;

