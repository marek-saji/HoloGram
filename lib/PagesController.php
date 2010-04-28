<?php
/**
 * Pages controller.
 * Controller will present one of multiple pages. Each is accessed with a specific action. 
 */
class PagesController extends Component
{
    public static $singleton=true;
    protected $_action='default';
    protected $_layout='main';
    protected $_params=array();

    protected $_views_layouts = array('View'=>'main', 'AjaxView'=>'main_ajax');

    public function process(Request $req)
    {
        if (!$req->dive())
        {
            $this->launchDefaultAction();
            return;
        }

        while ($current = $req->next())
        {
            if ($this->__routeAction($current, $req))
                continue;
        }

        $req->reset();

        while ($current = $req->next())
        {
            /** @todo shouldn't part of this code be shared
             *        with Controller::__handle()?
             */

            if ($this->__routeAction($current, $req, false))
                continue;
            //var_dump("did not route with $current");
            $action = ('default'===$current)?'defaultAction':
                                             ('action'.ucfirst($current));

            $this->_params = (array) $req->getParams();
            $this->_action = $current;

            if (method_exists($this,$callback = "prepare".ucfirst($current)))
                $this->$callback($this->_params);

            # For URLs like Controller/Controller'sAction/Controller'sChild..
            if ($req->dive(true)) // nie schodzi poziom nizej
                $this->process($req);

            if (false === $this->__handle($req, $current, false))
            {
                trigger_error('Method '.get_class($this).'::'.$action.' not found.', E_USER_NOTICE);
                $this->redirect('HttpErrors/Error404');
            }
        }
        $req->emerge();
    }

    public function render()
    {
        $layout = & $this->_views_layouts[get_class(g()->view)];
        if (null === $layout)
            $layout = $this->_layout;
        $arr = explode('/',$layout);
        $arr[count($arr)-1] = strtolower($arr[count($arr)-1]);
        $this->_layout = implode('/',$arr);
        $this->inc($this->_layout);

        /**
         * @todo remove this, when sure the above works (created 2010-01)
         */
        /*
        if (g()->req->isAjax())
            return $this->contents();
        else
        {
            $arr = explode('/',$this->_layout);
            $arr[count($arr)-1] = strtolower($arr[count($arr)-1]);
            $this->_layout = implode('/',$arr);
            $this->inc($this->_layout);
        }
         */
    }    

    public function contents()
    {
        if (is_array($this->_action))
            @list($that,$action) = $this->_action;
        else
        {
            $that = $this;
            $action = $this->_action;
        }

        if ($action)
        {
            // lowercase last in path
            $arr = explode('/',$action);
            $arr[count($arr)-1] = strtolower($arr[count($arr)-1]);
            $action = implode('/',$arr);
        }

        $this->_action = array($that, $action);

        return parent::render();
    }


    /**
     * removed by m.augustynowicz, 2009-10-23
     */
    /*
    public function url2a($act='', array $params=array())
	{
        if (empty($act))
            return(parent::url2a($this->_action,$this->_params));
        return(parent::url2a($act,$params));
    }
     */
}
