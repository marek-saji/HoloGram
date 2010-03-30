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
 */
extract(array_merge(
        array(
            'id'          => null,
            'attrs'       => array(),
            'disabled'    => false,
            'class'       => '', // overrides attrs[class] !
        ),
        (array) $____local_variables
    ));
if ($disabled)
    $attrs['disabled'] = 'disabled';
@$attrs['class'] .= ' hg '.$class;

$attrs['id'] = $id;
$attrs['name'] = $ident.'['.$input.']';
//$attrs['value'] = $data;

$attrs_html = '';
foreach ($attrs as $name=>$value)
    $attrs_html .= sprintf(' %s="%s"', $name, htmlentities($value));

$err_id = $attrs['id'] . '__err';
if ((@$errors) && is_array($errors))
    $errors = implode(', ', $errors);
?>

<textarea <?=$attrs_html?>><?=$data?></textarea>
<div class="field_error" id="<?=$err_id?>" style="display:<?=$errors?'block':'none'?>"><?=$errors?></div>
<?
if($ajax)
    $v->addOnLoad('$(\'#'.$attrs['id'].'\').blur(function(){return hg("input_validate")(this);})');

return $attrs;

