<?php
/**
 * Show details about log entry (action template)
 * @author m.augustynowicz
 *
 * @param array $info row from Log model
 * @param array $rows rows from LogValue model
 * @param bool $with_old_values should we ignore second value in $rows
 */
if (!isset($info) || !isset($rows))
{
    trigger_error('Incorrect parameters passed to template', E_USER_WARNING);
    return;
}


$v->setTitle($this->trans('Details of log entry #%d: %s',
                          $info['log_id'], $this->_entryTitle($info) ));


?>

<section class="log details">

    <hgroup>
        <h2>
            <?=$t->trans('Log entry #%d', $info['log_id'])?>:
            <em><?=$this->_entryTitle($info)?></em>
        </h2>
        <h3>
            <?=
                $this->trans('by %s at %s',
                        $this->_l2owner($info),
                        $f->formatDate($info['timestamp'], DATE_SORTABLE_FORMAT)
                    );
            ?>
        </h3>
    </hgroup>

    <?php if (empty($rows)) : ?>

        <p>
            <?=$this->trans('No further informations about this event.')?>
        </p>

    <?php else : ?>

        <table class="diff <?=$with_old_values ? 'with_old_values' : ''?>">
            <thead>
                <tr>
                    <th class="property">
                        <?=$t->trans('property')?>
                    </th>
                    <th class="value">
                        <?=$t->trans('value')?>
                    </th>
                    <?php if ($with_old_values) : ?>
                        <th class="new_value">
                            <?=$t->trans('new value')?>
                        </th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr class="<?=$row['differs'] ? 'diff' : ''?>">
                        <th>
                            <?=$row['property']?>
                        </th>
                        <td>
                            <?=$row['value']?>
                        </td>
                        <?php if ($with_old_values) : ?>
                            <td class="new_value">
                                <?=$row['new_value']?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</section> <!-- .log_details -->

