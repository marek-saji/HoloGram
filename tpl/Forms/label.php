<?php
/**
 * Form input label
 * @author m.augustynowicz
 *
 * @param string $input_id id of field 
 * @param string $label label text
 * @param bool $required whether field is required
 */
extract(
    array_merge(
        array(
            'input_id' => null,
            'label'    => null,
            'required' => false,
        ),
        (array) $____local_variables
    ),
    EXTR_REFS|EXTR_PREFIX_INVALID, 'param'
);
?>

<label for="<?=$input_id?>">
    <?=$this->trans($label)?>
    <?php
    echo $f->tag('strong',
        array(
            'class' => 'required',
            'title' => $this->trans('field required')
        ),
        '*'
    );
    ?>
</label>

