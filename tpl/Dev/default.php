<?php
if (isset($output)) :
    echo $output;
else :
?>

<ol>
    <?php foreach ($actions as $action => $doc) : ?>
    <li>
        <h4><?=$action?></h4>
        <pre><?=$doc?></pre>
            <label>
                params:
                <input type="text" id="<?=$action?>_params" />
            </label>
            <button onclick="window.location.href=('<?=g()->req->getBaseUri(true)?>Dev/<?=$action.g()->conf['link_split'];?>'+$('#<?=$action?>_params').val());return false;">run</button>
    </li>
    <?php endforeach; ?>
</ol>

<?php
endif; // if $output else
?>

