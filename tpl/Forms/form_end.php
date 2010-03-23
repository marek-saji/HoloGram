<?php
/**
 * End of a form.
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * @param string $idet form ident
 * @param Form $form
 */
extract(array_merge(
        array(
            'ident' => null,
            'form'  => null,
        ),
        (array) $____local_variables
    ), EXTR_REFS|EXTR_PREFIX_INVALID, 'param');
?>

    <fieldset class="hidden backlink">
        <?php $form->input('_backlink'); ?>
    </fieldset>
</form> <!-- #<?=$ident?> -->

