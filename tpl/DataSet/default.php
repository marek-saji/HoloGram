<!--<div style="float:left; min-width:300px;">-->
<ul>
<?foreach($this->_datasets as &$ds) {?>
    <li class="<?=$ds['class']?>"><span class="name"><?=$this->l2a($ds[0],'Show',array($ds[0]))?></span>&nbsp;&nbsp;&nbsp;<?=$this->l2a('[+]','Add',array($ds[0]), array('title'=>'insert row'));?></li>
<?}?>
    <li>
        <small>
            <h4>legend:</h4>
            <ul>
                <li class="abstract "><span class="name">abstract </span></li>
                <li class="correct  "><span class="name">correct  </span></li>
                <li class="missing  "><span class="name">missing  </span></li>
                <li class="incorrect"><span class="name">incorrect</span></li>
            </ul>
        </small>
    </li>
</ul>
<form method="post">
    <label>Other: <input id="ds" name="ds" /></label><input type="submit" value="Go" onclick="window.location.href='<?=g()->req->getBaseUri().$this->url();?>/Show<?=g()->conf['link_split']?>' + document.getElementById('ds').value; return false;" />
</form>
<!-- </div> -->
