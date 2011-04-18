<?php
/**
 * Single <label><radio /></label> tag
 *
 * @see FRadio template renders whole set of them
 * @author m.augustynowicz
 *
 * @param mixed $value
 * @param string $label
 */
extract(
    array_merge(
        array(
            'value' => null,
            'label' => '',
        ),
        $____local_variables
    ),
    EXTR_REFS|EXTR_PREFIX_INVALID, 'param'
);

// select first radio by default
if (!isset($data))
{
    // template variables is the only scope we can really use to store this
    $val_name = "$ident $input FRadio selected";
    if (!$this->getAssigned($val_name))
    {
        $this->assign($val_name, true);
        $data = $value;
    }
}


$input_vars = array_merge($____local_variables, array(
    'data' => $value,
    'err_handling' => false,
));

$input_vars['attrs']['type'] = 'radio';

if (isset($data) && (string)$value === (string)$data)
{
    $input_vars['attrs']['checked'] = 'checked';
}


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

$return_me = $t->inc('Forms/input', $input_vars);

if ($label)
{
    printf("%s\n</label>\n", $label);
}

return $return_me;

