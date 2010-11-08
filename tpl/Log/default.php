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

$v->setTitle('Logs');

$v->addCss($this->file('log', 'css'));
?>

<section class="log listing">

    <?php
    $form = g('Forms', array('filters', $this));
    ?>
    <div class="holoform">

        <?php
        $form->create();
        ?>

        <fieldset>
            <ul>
                <li class="field">
                    <?php
                    $form->label('user_login', 'Caused by');
                    ?>
                    <?php
                    $form->input('user_login');
                    ?>
                </li>

                <li class="field">
                    <?php
                    $form->label('level', 'Level');
                    ?>
                    <?php
                    $form->input('level', array(
                        'values' => $level_values,
                        'empty_value_label' => $this->trans('all')
                    ));
                    ?>
                </li>

                <li class="field">
                    <?=$this->trans('date and time')?>
                    <ul>
                        <li class="field">
                            <?php
                            $form->label('from', 'from');
                            ?>
                            <?php
                            $form->input('from');
                            ?>
                        </li>
                        <li class="field">
                            <?php
                            $form->label('to', 'to');
                            ?>
                            <?php
                            $form->input('to');
                            ?>
                        </li>
                    </ul>
                </li>

                <?php
                $post_data = & $this->data['filters'];
                ?>
                <?php if (@$post_data['target_url'] || @$post_data['target_action'] || @$post_data['target_id']) : ?>

                    <li class="field">

                        <ul class="subform">

                            <?=$this->trans('Set filters')?>

                            <?php if ($data = @$post_data['target_url']) : ?>
                            <li class="field">
                                <label>
                                    <?php
                                    $form->input(
                                        'target_url',
                                        array(
                                            'value' => $data,
                                            'attrs' => array(
                                                'type' => 'checkbox',
                                                'checked' => 'checked'
                                            )
                                        )
                                    );
                                    ?>
                                    <?=$this->trans('Context')?>
                                </label>
                            </li>
                            <?php endif; ?>

                            <?php if ($data = @$post_data['target_action']) : ?>
                            <li class="field">
                                <label>
                                    <?php
                                    $form->input(
                                        'target_action',
                                        array(
                                            'value' => $data,
                                            'attrs' => array(
                                                'type' => 'checkbox',
                                                'checked' => 'checked'
                                            )
                                        )
                                    );
                                    ?>
                                    <?=$this->trans('Event name')?>
                                </label>
                            </li>
                            <?php endif; ?>

                            <?php if ($data = @$post_data['target_id']) : ?>
                            <li class="field">
                                <label>
                                    <?php
                                    $form->input(
                                        'target_id',
                                        array(
                                            'value' => $data,
                                            'attrs' => array(
                                                'type' => 'checkbox',
                                                'checked' => 'checked'
                                            )
                                        )
                                    );
                                    ?>
                                    <?=$this->trans('Target')?>
                                </label>
                            </li>
                            <?php endif; ?>

                        </ul> <!-- .subform -->

                    </li>

                <? endif; /* if post_data[target_*] */ ?>

            </ul>
        </fieldset>

        <?php
        $this->inc(
            'Forms/buttons',
            array(
                'form' => $form,
                'submit' => 'Filter',
                'cancel' => false
            )
        );
        ?>

        <?php
        $form->end();
        ?>

    </div>

    <?php
    $this->getChild('p')->render();
    ?>

    <table>
        <thead>
            <tr>
                <th><?=$t->trans('id')?></th>
                <th><?=$t->trans('Level')?></th>
                <th><?=$t->trans('When')?></th>
                <th><?=$t->trans('Caused by')?></th>
                <th><?=$t->trans('Context')?></th>
                <th><?=$t->trans('Event name')?></th>
                <th><?=$t->trans('Target <p><small>note that links may not work</small></p>')?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <?php
                $params = $this->getParams();
                ?>
                <tr class="level_<?=$row['level']?>">

                    <?php /* log_id */ ?>
                    <td>
                        <?=$this->l2a($row['log_id'], 'details', array($row['log_id']))?>
                    </td>

                    <?php /* level */ ?>
                    <td>
                        <?=$this->trans('((log level:%s))', $row['level'])?>
                    </td>

                    <?php /* timestamp */ ?>
                    <td>
                        <?=$f->formatDate($row['timestamp'], DATE_SORTABLE_FORMAT)?>
                        <span class="action more-like-this">
                            <?=$this->l2a(
                                $title = $this->trans('see all logs around that date'),
                                $this->getLaunchedAction(),
                                $row['AroundDateParams'],
                                array('title' => $title)
                            )?>
                        </span>
                    </td>

                    <?php /* user_id */ ?>
                    <td>
                        <?=$this->_l2owner($row)?>
                        <?php if ($row['user_ident'] && !@$params['user_login']) : ?>
                            <span class="action more-like-this">
                                <?php
                                $owner_params = $params;
                                $owner_params['user_login'] = $row['user_ident'];
                                echo $this->l2a(
                                    $title = $this->trans('see only this user\'s actions'),
                                    $this->getLaunchedAction(),
                                    $owner_params,
                                    array('title' => $title)
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </td>

                    <?php /* target_url */ ?>
                    <td>
                        <?=$this->l2a($this->trans('((context: %s))', $row['target_url']))?>
                        <?php if (!@$params['target_url']) : ?>
                            <span class="action more-like-this">
                                <?php
                                $params['target_url'] = $row['target_url'];
                                echo $this->l2a(
                                    $title = $this->trans('see only this context'),
                                    $this->getLaunchedAction(),
                                    $params,
                                    array('title' => $title)
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </td>

                    <?php /* target_action */ ?>
                    <td>
                        <?=$this->l2a($this->_entryTitle($row), 'details', array($row['log_id']))?>
                        <?php if (!@$params['target_action']) : ?>
                            <span class="action more-like-this">
                                <?php
                                $params['target_action'] = $row['target_action'];
                                echo $this->l2a(
                                    $title = $this->trans('see only this actions'),
                                    $this->getLaunchedAction(),
                                    $params,
                                    array('title' => $title)
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </td>

                    <?php /* target_id */ ?>
                    <td>
                        <?php if ($row['target_id']) : ?>
                            <?=$this->l2c(
                                $row['title'],
                                $row['target_url'],
                                '',
                                array($row['target_id'])
                            )?>
                            <?php if (!@$params['target_id']) : ?>
                                <span class="action more-like-this">
                                    <?php
                                    unset($params['target_action']);
                                    $params['target_id'] = $row['target_id'];
                                    echo $this->l2a(
                                        $title = $this->trans('see only actions on this target'),
                                        $this->getLaunchedAction(),
                                        $params,
                                        array('title'=>$title)
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $this->getChild('p')->render();
    ?>

</section> <!-- .log_listing -->

