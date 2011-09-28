<?php
@$____local_variables['class'] .= ' text';

$attrs =& $____local_variables['attrs'];
if (!isset($attrs['type']))
{
    $attrs['type'] = 'text';
}

if ($attrs['type'] === 'text')
{
    // convert data-{min,max}length rules to html5 attributes
    foreach (array('minlength', 'maxlength') as $rule)
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

