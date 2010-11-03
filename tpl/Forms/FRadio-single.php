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

$attrs = $t->inc('Forms/input', $input_vars);
echo $label;
?>
</label>


// render

if ($label)
{
    printf("<label for=\"%s\">\n", $id);
}

$return_me = $t->inc('Forms/input', $input_vars);

if ($label)
{
    printf("%s\n</label>\n", $label);
}

return $return_me;

