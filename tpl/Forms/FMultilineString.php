<?php
/**
 * <textarea></textarea>
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * @param string $id unique id
 * @param array $attrs
 * @param boolean $disabled
 * @param string $class
 * @param string $name_suffix e.g. "[en]" (note that name has to have
 *        arrayish value, use at your own risk)
 * @param string $name_suffix e.g. "[42][]" (note that name has to have
 *        arrayish value, use at your own risk)
 * @param string $validate_js_event js event to bind js validation to
 */
extract(array_merge(
        array(
            'id'          => null,
            'attrs'       => array(),
            'disabled'    => false,
            'class'       => '', // overrides attrs[class] !
            'name_prefix' => '',
            'name_suffix' => '',
            'validate_js_event' => 'blur',
        ),
        (array) $____local_variables
    ));
if ($disabled)
    $attrs['disabled'] = 'disabled';
@$attrs['class'] .= ' hg '.$class;

$attrs['id'] = $id;
$attrs['name'] = $ident.$name_prefix.'['.$input.']'.$name_suffix;
//$attrs['value'] = $data;

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

$data = html_entity_decode(@$data);

echo $f->tag('textarea', $attrs, $data);

$js_event = $validate_js_event;
$err_attrs = $t->inc('Forms/errors', compact('id', 'ajax', 'err_handling', 'js_event', 'errors'));
if (is_array($err_attrs))
{
    $attrs = array_merge($err_attrs, $attrs);
}

return $attrs;

