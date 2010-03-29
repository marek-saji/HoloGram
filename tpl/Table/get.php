<div>
<?  $fields = $this->_subject->getFields();?>
	<table style="border:4px solid #d0d0d0">
	    <tr><?
    foreach ($fields as $field) { 
        ?><th><?=$field->getName();?></th><?
    } ?></tr>
       
<?  
    $pkeys = NULL;
    foreach($this->_subject as $row) {?>
	    <tr><?
        foreach($row as $val) {
            ?><td><?=$val;?></td><?
        }?><td><? 
        foreach($this->_actions as &$act){
            ?><a href="<?=$act['url'].$this->_actionParams($row,$pkeys,$act['params'])?>"><?=$act['contents']?></a> <?
        } ?></td></tr>
<?  }?>
    </table>    
<?
    if ($this->prev !== false)
        echo "<a href='".$this->url2cInside($this->url(),'',array($this->prev))."'>prev</a>";
    echo ' ';
    if ($this->next !== false)
        echo "<a href='".$this->url2cInside($this->url(),'',array($this->next))."'>next</a>"; 
?>
</div>
