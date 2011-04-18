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
            'label'  => null,
            'err_handling' => false,
            'validate_js_event' => 'change',
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


// render

if ($label)
{
    $label_attrs = array(
        'for'   => $id,
        'class' => ''
    );
    if (array_key_exists('disabled', $____local_variables))
    {
        $label_attrs['class'] .= ' disabled';
    }
    else if (array_key_exists('disabled', @$attrs))
    {
        $label_attrs['class'] .= ' disabled';
    }
    printf("<label %s>\n", $f->xmlAttr($label_attrs));
}

$attrs = $t->inc('Forms/input', array('err_handling'=>false) + $____local_variables);

if ($label)
{
    printf("%s\n</label>\n", $label);
}

$id = $attrs['id'];
$js_event = $validate_js_event;
$err_attrs = $t->inc('Forms/errors', compact('id', 'ajax', 'err_handling', 'js_event', 'errors'));
if (is_array($err_attrs))
    $attrs = array_merge($err_attrs, $attrs);

return $attrs;

