<?php
/**
 * (params passed as local variables)
 * @see Forms/input.php
 */
$attrs =& $____local_variables['attrs'];

if (!array_key_exists('type', $attrs))
{
    $attrs['type'] = 'number';
}

if ($attrs['type'] === 'number')
{
    // convert data-{min,max} rules to html5 attributes
    foreach (array('min', 'max') as $rule)
    {
        $data_attr = 'data-' . $rule;
        if (array_key_exists($data_attr, $attrs))
        {
            if (!array_key_exists($rule, $attrs))
            {
                $attrs[$rule] =& $attrs[$data_attr];
                unset($attrs[$data_attr]);
            }
        }
    }
}

return $t->inc('Forms/input', $____local_variables);

