<?php
/**
 * Layout
 *
 * @author m.augustynowicz
 */

// render all the components first
// (they will set page (sub)title etc)
ob_start();
$this->render();
$contents = ob_get_clean();

//$v->addCss($this->file('common', 'css'));
// include JSes, CSS, set page title etc.
$t->inc('main_common');

// show page infos. will render <aside id="infos" /> at least
$t->inc('infos');
?>

<header id="head">
    <h1>
        <?= $t->l2c(g()->conf['site_name'], ''); ?>
    </h1>
    <nav>
        <ul>
            <?php
            if (g()->auth->loggedIn()) :
                $username = g()->auth->get('login');
            ?>
                <li class="welcome">
                    <?= $t->l2c($username, 'User', '', array($username)) ?>
                </li>
                <li class="signout">
                    <?= $t->l2c($t->trans('Sign Out'), 'User', 'logout'); ?>
                </li>
            <?php
            else :
            ?>
                <li class="signin">
                    <?= $t->l2c($t->trans('Sign In'), 'User', 'login', array(), array('class' => 'btn modal', 'anchor' => 'login')); ?>
                </li>
                <li class="create_account">
                    <?= $this->l2c($t->trans('Create an Account'), 'User', 'add'); ?>
                </li>
            <?php
            endif;
            ?>
        </ul>
    </nav>
</header> <!-- #head -->

<nav id="menu">
    <?php
        echo 'menu goes here';
        // $this->inc('menu');
    ?>
</nav> <!-- #menu -->

<div id="content">
        <?= $contents ?>
</div> <!-- #content -->

<footer id="foot">
    <nav>
        <?php
            //$this->inc('menu', array('name'=>'footer', 'menu' => g()->conf['menu']['foot']));
        ?>
    </nav>
    <section class="tech">
        <span class="powered"><?=$t->trans('powered by %s', '<a class="hg">Hologram</a>')?></span>
        <span class="ver"><abbr title="<?=$t->trans('application version')?>">app v.</abbr> <?= g()->conf['version']?></span>
    </section> <!-- .powered -->
</footer> <!-- #foot -->

