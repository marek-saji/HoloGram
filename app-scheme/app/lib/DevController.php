<?php
g()->load('Developer', 'controller');

/**
 * Developer controller
 * @author someone
 *
 */
class DevController extends DeveloperController
{
    /**
     * Used by parent
     */
    protected $_file = __FILE__;

    /**
     * Template action.
     * Copy and add the missig asterisk on the top of this comment.
     * @author someone
     * 
     * @param array $params accepts "die" parameter
     * @return void
     */
    public function actionTemplate(array $params)
    {
        $this->_devActionBegin($params, __FUNCTION__);
        // put your code here.
        $this->_devActionEnd($params, __FUNCTION__);
    }

    /**
     * Adds default stuff (like users etc)
     * @author m.augustynowicz
     *
     * @param array $params accepts "die" parameter
     */
    public function actionAddDefault(array $params)
    {
        $this->_devActionBegin($params, __FUNCTION__);

        /*
        $this->_insertSomething(
            'User',
            array(
                'id'     => -1,
                'login'  => 'admin',
                'passwd' => 'secret',
                ..
            )
        );
         */

        $this->_devActionEnd($params, __FUNCTION__);
    }

}

