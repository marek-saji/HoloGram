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
    <h2><?=$v->getTitle()?></h2>
    <div class="content">
        <?php
        $this->render();
        ?>
    </div>
</div>

<div id="footer">
    <div class="copyright">
        <small>&copy; HolonGlobe</small>
    </div>
</div>

