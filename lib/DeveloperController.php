<?php

g()->load('Pages', 'controller');

abstract class DeveloperController extends PagesController
{
    protected $_file = __FILE__;
    private $__file = __FILE__;
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
        $methods = get_class_methods($this);
        $source1 = file_get_contents($this->__file);
        $source2 = file_get_contents($this->_file);
        preg_match_all('/[\t ]*\/\*\*((?:\n[\t ]*\*[^\n]*)*)\n[\t ]*\*\/\s*(?:public )function\s+action([[:alpha:]]*)[^[:alpha:]].*[\r\n]/sUmi', $source1, $matches1);
        preg_match_all('/[\t ]*\/\*\*((?:\n[\t ]*\*[^\n]*)*)\n[\t ]*\*\/\s*(?:public )function\s+action([[:alpha:]]*)[^[:alpha:]].*[\r\n]/sUmi', $source2, $matches2);
        $actions = array_merge(
            (array)array_combine($matches1[2], $matches1[1]),
            (array)array_combine($matches2[2], $matches2[1])
        );
        $this->assign(compact('actions'));
    }

    /**
     * Launches all create*, and then add* actions.
     * @author m.augustynowicz
     *
     * @todo fix it. remove comment in tpl/Dev/default.php when fixed.
     *
     * @param array $params no params accepted
     */
    public function actionSetup(array $params)
    {
        $db = g()->db;
        $db->debugOn();
        echo '<hr /><h2>creating things..</h2>';
        $methods = preg_grep('/^actionCreate/', get_class_methods($this));
        foreach ($methods as $method)
        {
            printf ('<h3>%s</h3>', $method);
            call_user_func(array($this, $method), array());
        }
        echo '<hr /><h2>adding things..</h2>';
        $methods = preg_grep('/^actionAdd/', get_class_methods($this));
        foreach ($methods as $method)
        {
            printf ('<h3>%s</h3>', $method);
            call_user_func(array($this, $method), array());
        }
        $db->debugOff();

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
