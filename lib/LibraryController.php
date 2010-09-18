<?php
/**
 * @author m.augustynowicz
 */
class LibraryController extends TrunkController
{
    /**
     * @var array of PermanentControllers
     */
    protected $_perma_children = array();

    /**
     * Pass the controll to first child in request
     * and permanent controllers
     * @author m.augustynowicz
     * @param Request $req
     */
    public function process(Request $req=null)
    {
        // add *only first* controller from request

        $child = $req->next();
        if (!$child)
        {
            // not really an action, we are adding component
            $req->addAction($this->_conf['default']);
            $child = $req->next();
        }
        if (!$child || !g()->load($child, 'controller', false))
        {
            // don't panic
            $this->redirect(array('HttpErrors', 'error404'));
        }
        $ctrl = g($child, 'controller', array(
                'name'   => $child,
                'parent' => $this
            ));
        $this->addChild($ctrl);


        // add permanent controllers

        $perma_ctrls = g()->conf['permanent_controllers'];
        foreach ($perma_ctrls as $name => $type)
        {
            $perma_ctrl = g($type, 'controller',
                array(
                    'name'   => $name,
                    'parent' => $this
                )
            );
            $this->_addPermaCtrl($perma_ctrl);
        }
        unset($perma_ctrl);


        // prepare actions

        $ctrl->prepareActions($req);
        foreach ($this->_perma_children as $name => $perma_ctrl)
        {
            $perma_ctrl->prepareActions(null);
        }
        unset($perma_ctrl);

        // launch actions

        $ctrl->launchActions($req);
        foreach ($this->_perma_children as $name => $perma_ctrl)
        {
            $perma_ctrl->launchActions(null);
        }
        unset($perma_ctrl);

    }


    /**
     * Add permanent controller child
     * @author m.augustynowicz
     *
     * @param PermanentController $child controller to add
     * @return PermanentController added controller, for chaining
     */
    protected function _addPermaCtrl(PermanentController $child)
    {
        $this->_perma_children[$child->getName()] = $child;
        return $child;
    }


    /**
     * Get permanent controller child
     * @author m.augustynowicz
     *
     * @param string $child_name name of controller
     * @return PermanentController|null
     */
    public function getPermaCtrl($child_name)
    {
        return @$this->_perma_children[$child_name];
    }

}

