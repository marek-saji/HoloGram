<?php
/**
 * <select> <option></option> </select>
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * @param string $id uniqe id
 * @param array $values REQUIRED!
 * @param string $empty_value_label if given, adds extra, empty value at the beginning
 * @param array $attrs
 * @param boolean $disabled
 * @param string $class
 * @param boolean $err_handling add div.field error, bind events (if $ajax)
 * @param string $name_suffix e.g. "[en]" (note that name has to have
 *        arrayish value, use at your own risk)
 * @param string $name_suffix e.g. "[42][]" (note that name has to have
 *        arrayish value, use at your own risk)
 */
if (!$____local_variables['values'])
{
    g()->debug->addInfo(null, 'No values passed to FSelect (%s, %s::%s)', $this->path(), $ident, $input);
}
extract(array_merge(
        array(
            'id'          => null,
            'values'      => array(),
            'empty_value_label' => null,
            'attrs'       => array(),
            'disabled'    => false,
            'class'       => '', // overrides attrs[class] !
            'err_handling'=> true,
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

$attrs_html = '';
foreach ($attrs as $name=>$value)
    $attrs_html .= sprintf(' %s="%s"', $name, htmlentities($value));

if (isset($select_array))
{
    g()->debug->addInfo('deprecated select_array', '$select_array variable has been assigned, but FSelect now accepts only local variable $values');
}
?>

<select <?=$attrs_html?>>
    <?php if ($empty_value_label) : ?>
    <option class="empty" value=""><?=$empty_value_label?></option>
    <?php endif; ?>
    <?php foreach($values as $value => $name) { ?>
    <option value="<?=$value?>" <? if(isset($data) && (string)$value === (string)$data) echo 'selected="selected"'; ?> ><?=$name?></option>
    <?php } ?>
</select>

<?php
$t->inc('Forms/errors', compact('id', 'ajax', 'err_handling'));

return $attrs;

