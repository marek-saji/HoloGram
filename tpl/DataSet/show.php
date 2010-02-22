<?if (isset($model_invalid)) { ?>
    <p>Tabela modelu nie istnieje w bazie danych, albo ma niewłaściwą definicję <?=$this->l2a('Porównaj','Comp',array($this->_dsName()));?>.</p>
<?  
}
else {
        if (isset($obsolete)) { ?>
    <p>W modelu brakuje definicji niektorych kolumn zadeklarowanych w bazie danych, <?=$this->l2a('Zaktualizuj','Comp',array($this->_dsName()));?>.</p>
<?      }?>    
    <div>
<?php
    echo $this->l2a('dodaj','Add',array($this->_dsName()));
    $this->inc('Page')
?>
    </div>
<?  echo $this->l2a('dodaj','Add',array($this->_dsName()));
}
?>
