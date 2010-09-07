<?php
/**
 * <select> <option></option> </select>
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * @param string $id unique id
 * @param array $values REQUIRED!
 * @param string $empty_value_label if given, adds extra, empty value at the beginning
 * @param array $attrs
 * @param boolean $disabled
 * @param string $class
 * @param boolean $err_handling add div.field error, bind events (if $ajax)
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
        ),
        (array) $____local_variables
    ));
if ($disabled)
    $attrs['disabled'] = 'disabled';
@$attrs['class'] .= ' hg '.$class;

$attrs['id'] = $id;

$attrs_html = $f->xmlAttr($attrs);

unset($attrs['id']); // we don't want <input />s to get the same id as fieldset

if (isset($select_array))
{
    g()->debug->addInfo('deprecated select_array', '$select_array variable has been assigned, but FSelect now accepts only local variable $values');
}
?>

<fieldset <?=$attrs_html?>>

    <?php if (null !== $empty_value_label) : ?>
        <label>
            <?php
            $input_vars = array_merge($____local_variables, array(
                'data' => '',
                'err_handling' => false,
            ));
            $input_vars['attrs'] = array_merge((array)@$input_vars['attrs'], array(
                'type' => 'radio',
                'class' => 'radio empty',
            ));
            if (!isset($data) || ''===$data || false===$data) // '0' is different value
                $input_vars['attrs']['checked'] = 'checked';
            $t->inc('Forms/input', $input_vars);
            echo $empty_value_label;
            ?>
        </label>
    <?php endif; /* if empty_value_label */ ?>

    <?php
    foreach ($values as $value => $label)
    {
        $this->inc('Forms/FRadio-single', array(
            'data' => $data,
            'value' => $value,
            'label' => $label,
            'ident' => $ident,
            'input' => $input,
            'err_handling' => false
        ));
    }
    ?>

</fieldset>

<?php
$t->inc('Forms/errors', compact('id', 'ajax', 'err_handling'));

return $attrs;

