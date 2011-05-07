<?php
/**
 * Common page template for actions requiring yes-no decision
 * @author m.augustynowicz
 *
 * @param string $question
 * @param string $yes
 * @param string $no
 */

// allow confirm being used as component's main tempate
if (!isset($____local_variables))
    $____local_variables = & $this->__variables;

extract(
    array_merge(
        array(
            'question' => 'Are you sure?',
            'yes' => 'yes',
            'no'  => 'no'
        ),
        (array) $____local_variables
    ),
    EXTR_REFS|EXTR_PREFIX_INVALID, 'param'
);


$question = $this->trans($question);
$yes = $this->trans($yes);
$no  = $this->trans($no);

$form = g('Forms', array('confirm', $this));
?>

<section class="confirmation">

    <div class="holoform">
        <?php
        $form->create($this->url2c($this->url(),
                                   $this->getLaunchedAction(),
                                   $this->getParams() ));
        ?>
            <p>
                <?=$question?>
            </p>
            <?php
            $this->inc('Forms/buttons', array(
                'form'   => & $form,
                'submit' => $yes,
                'cancel' => $no
            ));
            ?>
        <?php
        $form->end();
        ?>
    </div>
</section>

