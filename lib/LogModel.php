<?php
g()->load('DataSets', null);

/**
 * As elastic as possible model for holding and adding system logs
 *
 * USAGE:
 * To be used only in controllers.
 * Usual call looks like that:
 *
 * 1. when editing (including blocking etc)
 *
 *   $id = $params[0];
 *   $row = $model->getRow($id);
 *   $post_data = $this->data['form'];
 *   g()->log('info', $this, $id, $row['title'], $row, $post_data);
 *
 * 2. when adding
 *
 *   $id = $params[0];
 *   $post_data = $this->data['form'];
 *   g()->log('info', $this, $id, $row['title'], $post_data);
 *
 * 3. when performing some action (e.g. signing-in)
 *
 *   $id = $params[0];
 *   $row = $model->getRow($id);
 *   g()->log('info', $this, $id, $row['title']);
 *
 * 4. logging events not related with any particular object
 *
 *   g()->log('info', $this, null, 'main page displayed');
 *
 *
 * $this is used to obtain current controller url and launched actions,
 * when using log(), be sure that appropriate entry exists for
 * conf[translations][Log]['%s/%s on %s']. Parameters for translations are
 * controller url, action and logged title.
 *
 * Values with name starting with '_' are dropped.
 *
 *
 *
 * Possible callbacks for classes extending this class:
 *
 * * _log{LogLevel}{Url}{Action}({same params as log()})
 *
 *   {Url}{Action} in camelcase, so logging info in Foo/Bar/show would launch
 *   _logInfoFooBarShow()
 *
 *   return void
 *
 * @author m.augustynowicz
 */
class LogModel extends Model
{
    /**
     * Add fields, relations, set primary key
     * @author m.augustynowicz
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // fields

        $this->_addField(new FId('log_id'));
        $this->_addField(new FTimestamp('timestamp', true, 'NOW()'));
        $this->_addField(new FString('ip', false, null, 7, 15));
        $this->_addField(new FEnum('level', 'log_level', true, 'info'));
        $this->_addField(new FForeignId('user_id', false, 'User'));
        $this->_addField(new FString('title', false));
        $this->_addField(new FString('target_url', false));
        $this->_addField(new FString('target_action', false));
        $this->_addField(new FString('target_id', false));
        $this->_addField(new FBool('with_old_values', true, false));


        // set automatic values

        $this['ip']->auto(array($this, 'autoIP'));
        $this['user_id']->auto(array($this, 'autoUserID'));


        // relations

        $this->relate('Owner', 'User', 'Nto1', 'user_id');
        $this->relate('Values', 'LogValue', '1toN', 'log_id', 'log_id');


        $this->_pk('log_id');

        $this->whiteListAll();
    }


    /**
     * Log an event
     * @author m.augustynowicz
     *
     * @see Kernel::log() wrapper for this method
     *
     * @param string $level {@see conf[enum][log_level]}
     * @param Controller|null $that controller event happened in
     * @param mixed $id id of object event regards
     * @param string $title title of object, or event if ($id===null)
     * @param array $values when no $values2 given, set of event properties,
     *        when $values2 given -- set of old properties
     * @param array $new_values set of new properties
     * @param int $user_id user id to use (when none supplied, signed-in
     *        user's id will be used)
     *
     * @return void
     */
    public function log($level, Controller $that, $title=null, $id=null,
                        array $values=null, array $new_values=null, $user_id=false )
    {
        $log_row = array();
        $log_row['level'] = $level;
        $log_row['target_url'] = $that->url();
        $log_row['target_action'] = $that->getLaunchedAction();
        $log_row['target_id'] = $id;
        $log_row['title'] = $title;
        $log_row['with_old_values'] = (null !== $new_values);
        $log_row['user_id'] = $user_id;

        g()->db->startTrans();

        $result = $this->sync($log_row, true, 'insert');

        if (true !== $result)
        {
            g()->debug->dump($result);
            g()->db->failTrans();
        }
        else
        {
            $log_id = $this->getData('log_id');

            // merge $values and $new_values
            $values_rows = array();
            if (null !== $values)
            {
                foreach ($values as $k=>&$v)
                {
                    if ('_' == $k[0])
                        continue;
                    $values_rows[$k] = array(
                        'log_id' => $log_id,
                        'property' => $k,
                        'value' => $v
                    );
                }
                unset($v);
                if (null !== $new_values)
                {
                    foreach ($new_values as $k=>&$v)
                    {
                        if ('_' == $k[0])
                            continue;
                        $values_rows[$k]['log_id'] = $log_id;
                        $values_rows[$k]['property'] = $k;
                        $values_rows[$k]['new_value'] = $v;
                    }
                    unset($v);
                }
            }

            if (!empty($values_rows))
            {
                $values_rows = array_values($values_rows);
                $values_model = g('LogValue', 'model');
                $result = $values_model->sync($values_rows, true, 'insert');

                if (true !== $result)
                {
                    g()->debug->dump($result);
                    g()->db->failTrans();
                }
            }
        }

        g()->db->completeTrans();


        // launch callback

        $f = g('Functions');
        $callback = '_log'
                . $f->camelify($log_row['level'])
                . $f->camelify($log_row['target_url'])
                . $f->camelify($log_row['target_action']);
        if (method_exists($this, $callback))
        {
            $this->$callback($level, $that, $title, $id, $values, $new_values);
        }

    }


    /**
     * Automatic value for ip field
     * @author m.augustynowicz
     *
     * @return void
     */
    public function autoIP()
    {
        return $_SERVER['REMOTE_ADDR'];
    }


    /**
     * Automatic value for user_id field
     * @author m.augustynowicz
     *
     * @return void
     */
    public function autoUserID($action, $field, $value)
    {
        if ($value || !g()->auth->loggedIn())
            return null;
        else
            return g()->auth->id();
    }


    /**
     * Blocking updating as this is insert-and-select-only model
     * @author m.augustynowicz
     *
     * @param array $values ignored
     * @param bool $execute ignored
     *
     * @return bool|string no matter, as it always throws an exception
     */
    public function update(array $values, $execute=false)
    {
        throw new HgException('Tried to update logs.');
    }


    /**
     * Blocking deleting as this is insert-and-select-only model
     * @author m.augustynowicz
     *
     * @param bool $execute ignored
     *
     * @return mixed no matter, as it always throws an exception
     */
    public function delete($execute=false)
    {
        throw new HgException('Tried to delete logs.');
    }

}

