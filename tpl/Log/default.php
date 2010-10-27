<?php
/**
 * Display table with logs
 * @author m.augustynowicz
 *
 * @param array $rows
 */
if (!isset($rows))
{
    trigger_error('Incorrect parameters passed to template', E_USER_WARNING);
    return;
}

$v->setTitle('Browse logs');

?>

<section class="log listing">

    <?php
    $this->getChild('p')->render();
    ?>

    <table>
        <thead>
            <tr>
                <th><?=$t->trans('id')?></th>
                <th><?=$t->trans('level')?></th>
                <th><?=$t->trans('when')?></th>
                <th><?=$t->trans('who did')?></th>
                <th><?=$t->trans('what happened')?></th>
                <th><?=$t->trans('view target')?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr class="level_<?=$row['level']?>">
                    <td>
                        <?=$this->l2a($row['log_id'], 'details', array($row['log_id']))?>
                    </td>
                    <td><?=$row['level']?></td>
                    <td><?=$f->formatDate($row['timestamp'], DATE_SORTABLE_FORMAT)?></td>
                    <td>
                        <?=$this->_l2owner($row)?>
                    </td>
                    <td>
                        <?=$this->l2a($this->_entryTitle($row), 'details', array($row['log_id']))?>
                    </td>
                    <td>
                        <?php if ($row['target_id']) : ?>
                            <?=$this->l2c($row['title'], $row['target_url'], '', array($row['target_id']))?>
                            <small>
                                <?=$this->trans('(may not work)')?>
                            </small>
                        <?php endif; ?>
                    </td>
                </tr>
                <!--
                <tr><td colspan="6"><small><?php var_dump($row); ?></small></td></tr>
                -->
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $this->getChild('p')->render();
    ?>

</section> <!-- .log_listing -->

