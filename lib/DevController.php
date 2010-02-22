<?php

g()->load('Developer', 'controller');

class DevController extends DeveloperController
{

    protected $_file = __FILE__;

    /*
     * Template action.
     *
     * !! Copy and add the missig asterisk on the top of this comment. !!
     * @author someone
     *
     * @param array $params accepts "die" parameter
     *
     * @return void
     */
    public function actionTemplate(array $params)
    {
        $this->_devActionBegin($params, __FUNCTION__);

        // put your code here.

        $this->_devActionEnd($params, __FUNCTION__);
    }

    /**
     * Does a really bad thing.
     * @author m.augustynowicz
     *
     * @param array $params accepts "die" parameter
     *
     * @return void
     */
    public function actionPhpInfo(array $params)
    {
        $this->_devActionBegin($params,__FUNCTION__);

        phpinfo();

        $this->_devActionEnd($params,__FUNCTION__);
    }
    
}
