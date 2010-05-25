<?
    echo $t->l2a('back to the list', 'show', array($this->__params['ds']));
    $form = g('Forms',array('edit_in_ds',$this,$ds));
?>
<div>
    <?=$form->createAll();?>
</div>
