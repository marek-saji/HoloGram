<?php

g()->load('Pages', 'controller');

abstract class DeveloperController extends PagesController
{
    protected $_files = array(__FILE__);
    protected $_title = null;


    public function onAction($action, array & $params)
    {
        if (!g()->debug->allowed())
        {
            $this->redirect(array('HttpErrors', 'error404'));
        }

        g()->db->lastErrorMsg(); // will initialize db

        return true;
    }


    /**
     * Display list of all actions available.
     *
     * @param array $params none used
     */
    public function defaultAction(array $params)
    {
        $actions = array();
        foreach ($this->_files as & $file)
        {
            $source = file_get_contents($file);
            preg_match_all('!
                (
                ^\s*/\*\*\s*$\s*    # /**
                (?:^\s*\*.*$\s*)*   #  *
                ^\s*\*/\s*$         #  */
                )\s*
                ^\s*public\s+function\s+action([[:alpha:]]*)[^[:alpha:]].*$!mUx',
                    $source, $matches
            );
            $actions = array_merge(
                $actions,
                (array) array_combine($matches[2], $matches[1])
            );
        }

        unset($actions['Template']); // don't include actionTemplate
        ksort($actions);

        $this->assign(compact('actions'));
    }


    /**
     * Will launch actions:
     *   - create*()
     *   - populate*()
     *   - add*()
     * @author m.augustynowicz
     *
     * @param array $params accepts "die" parameter
     */
    public function actionSetup(array $params)
    {
        $this->_devActionBegin($params, __FUNCTION__);

        $all_methods = get_class_methods($this);
        foreach (array('create', 'populate', 'add') as $suffix)
        {
            printf('<hr /><h2>%s* actions</h2>', $suffix);
            $regex = '/^action'.ucfirst($suffix).'/';
            $methods = preg_grep($regex, $all_methods);
            foreach ($methods as $method)
            {
                printf ('<h3>%s</h3>', $method);
                call_user_func(array($this, $method), array());
                echo $this->getAssigned('output');
                $this->assign('output', null);
            }
        }
        echo '<hr />';

        $this->_devActionEnd($params, __FUNCTION__);
        $this->_title = 'setup';
    }


    /**
     * Set of instructions to be called on the begining of each dev action
     * @author m.augustynowicz
     *
     * @todo get all arguments from debug_backtrace()
     *
     * @param array $params action's parameters
     * @param string $func actions's function name (__FUNCTION__)
     */
    protected function _devActionBegin(array & $params, $func)
    {
        $this->_setTemplate('default');
        $this->_title = $func;

        if (isset($params['die']))
        {
            $this->_die_after_action = true;
            unset($params['die']);
        }
        else if ('die'===@$params[0])
        {
            $this->_die_after_action = true;
            unset($params[0]);
        }

        ob_start();
        g()->db->debugOn();
    }

    
    /**
     * Set of instructions to be called on the end of each dev action
     * @author m.augustynowicz
     *
     * @param array $params action's parameters
     * @param string $func actions's function name (__FUNCTION__)
     */
    protected function _devActionEnd(array & $params, $func)
    {
        g()->db->debugOff();
        $this->assign('output', ob_get_contents());
        ob_end_clean();
        if (@$this->_die_after_action)
            die();
    }


    /**
     * Insert some rows to some model
     * @author m.augustynowicz
     *
     * @param string $model_name
     * @param array $rows No data validation. You have been warned.
     */
    protected function _insertSomething($model_name, array $rows)
    {
        $model = g($model_name, 'model');
        foreach($data as &$row)
        {
            $model->filter($row);
            if (!$model->getCount())
            {
                if(true !== $err = $model->sync($row, true, 'insert'))
                {
                    g()->addInfo(null, 'error', 'Error while adding ' . $model_name);
                    g()->debug->dump($err);
                    return;
                }
            }
        }
    }

}

