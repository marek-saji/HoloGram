<?php
/**
 * Common code for all plain <input /> form fields
 * @author m.augustynowicz
 *
 * (params passed as variables) 
 * @param boolean $ajax use ajax validation
 * @param string $ident form ident
 * @param string $input input ident
 *
 * (params passed as local variables) 
 * @param string|false $id uniqe id. pass false to skip id attr
 * @param array $attrs
 * @param boolean $disabled
 * @param string $class
 * @param boolean $err_handling add div.field error, bind events (if $ajax)
 * @param string $validate_js_event js event to bind js validation to
 */
extract(array_merge(
        array(
            'attrs'       => array(),
            'id'          => null,
            'disabled'    => false,
            'class'       => '',
            'autocomplete'=> true,
            'err_handling'=> true,
            'validate_js_event' => 'blur',
        ),
        (array) $____local_variables
    ));
if ($disabled)
    $attrs['disabled'] = 'disabled';
@$attrs['class'] .= ' hg '.$class;

if (false !== $id)
    $attrs['id'] = $id;
$attrs['name'] = $ident.'['.$input.']';
$attrs['value'] = $data;
if (false === $autocomplete)
    $attrs['autocomplete'] = 'off';

echo $f->tag('input', $attrs);

$js_event = $validate_js_event;
$err_attrs = $t->inc('Forms/errors', compact('id', 'ajax', 'err_handling', 'js_event'));
if (is_array($err_attrs))
    $attrs = array_merge($err_attrs, $attrs);

return $attrs;

