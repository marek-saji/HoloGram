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
 */
extract(array_merge(
        array(
            'id'          => null,
            'attrs'       => array(),
            'disabled'    => false,
            'class'       => '', // overrides attrs[class] !
            'name_prefix' => '',
            'name_suffix' => '',
        ),
        (array) $____local_variables
    ));
if ($disabled)
    $attrs['disabled'] = 'disabled';
@$attrs['class'] .= ' hg '.$class;

$attrs['id'] = $id;
$attrs['name'] = $ident.$name_prefix.'['.$input.']'.$name_suffix;
//$attrs['value'] = $data;

$data = html_entity_decode(@$data);
$err_id = $attrs['id'] . '__err';
if ((@$errors) && is_array($errors))
    $errors = implode(', ', $errors);

echo $f->tag('textarea', $attrs, $data);
?>

<div class="field_error" id="<?=$err_id?>" style="display:<?=$errors?'block':'none'?>"><?=$errors?></div>
<?
if($ajax)
    $v->addOnLoad('$(\'#'.$attrs['id'].'\').blur(function(){return hg("input_validate")(this);})');

return $attrs;

