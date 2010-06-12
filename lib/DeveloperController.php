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


    public function render()
    {
        if (g()->conf['site_name'])
            $title = g()->conf['site_name'].' ';
        else
            $title = '';
        $title .= 'Dev';
        if ($this->_title)
            $title .= ' : ' . $this->_title;
        g()->view->setTitle($title);

        return parent::render();
    }

    public function defaultAction(array $params)
    {
        $actions = array();
        foreach ($this->_files as & $file)
        {
            $source = file_get_contents($file);
            preg_match_all('/[\t ]*\/\*\*((?:\n[\t ]*\*[^\n]*)*)\n[\t ]*\*\/\s*(?:public )function\s+action([[:alpha:]]*)[^[:alpha:]].*[\r\n]/sUmi', $source, $matches);
            $actions = array_merge(
                $actions,
                (array) array_combine($matches[2], $matches[1])
            );
        }

        unset($actions['Template']);
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
     * @param array $params no params accepted
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


}
