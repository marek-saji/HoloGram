<?php
$this->inc('main_common');
?>

<?// $this->view->addCss('style.css');?>
<div id="header">
    <h1>Hologram</h1>
</div>

<?php
    $this->inc('infos');
?>

<div id="content">
    <h2><?=g()->view->getTitle()?></h2>
    <div class="content">
        <?$this->contents();?>
    </div>
</div>

<div id="footer">
    <div class="copyright">
        <small>&copy; HolonGlobe</small>
    </div>
</div>
