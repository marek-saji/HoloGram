<form method="post" action="<?=$this->url2a('Comp',array($this->_ds->getName(),'execute'));?>">
<pre>
<? if (isset($sql))
   {
       foreach($sql as $num => $option) {?>
    <label><input type="checkbox" name="part[<?=$num;?>]" selected="<?=@$_POST['part'][$num]?>" /><?=$option;?></label>
<?     } ?>
   <input type="submit" value="Wykonaj" />
<? }
   elseif(isset($match)) {?><p>Definicja modelu i tabeli jest jednakowa</p>
<? } ?>


</form>
</pre>
