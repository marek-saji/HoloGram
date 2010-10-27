<?php
/**
 * Listing actions from developer controller
 * @author m.augustynowicz
 */

$title = 'DEV';
if ($this->_title)
    $title .= '->'.$this->_title;
$v->setTitle($title);

if (isset($output)) : // user action

    echo $output;

else : // default action (listing actions)

?>
<section>
    <?php foreach ($actions as $action => $doc) : ?>
    <section>
        <header>
            <h3><?=$action?></h3>
        </header>
        <fieldset>
            <pre><?= $doc?>

<label><?=$action?>(<input type="text" id="<?=$action?>_params" />)</label> <button onclick="window.location.href=('<?=$t->url2a($action,array('REPLACEME'))?>'.replace('REPLACEME',$('#<?=$action?>_params').val()));return false;">launch</button>
            </pre>
        </fieldset>
    </section>
    <?php endforeach; ?>
</section>
<?php

endif; // if $output else

