<?
    echo $t->l2a('back to the list', 'show', array($this->_params[0]));
    $form = g('Forms',array('add_to_ds',$this,$ds));
?>
<div>
    <?=$form->createAll();?>
</div>
