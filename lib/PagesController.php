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
            if ($this->_routeAction($current, $req))
                continue;
        }

        $req->reset();

        while ($current = $req->next())
        {
            /** @todo shouldn't part of this code be shared
             *        with Controller::_handle()?
             */

            if ($this->_routeAction($current, $req, false))
                continue;
            //var_dump("did not route with $current");
            $action = ('default'===$current)?'defaultAction':
                                             ('action'.ucfirst($current));

            $this->_params = (array) $req->getParams();
            $this->_action = $current;

            $callback = "prepareAction".ucfirst($current);
            if (method_exists($this,$callback))
                $this->$callback($this->_params);

            # For URLs like Controller/Controller'sAction/Controller'sChild..
            if ($req->dive(true)) // nie schodzi poziom nizej
                $this->process($req);

            if (false === $this->_handle($req, $current, false))
            {
                trigger_error('Method '.get_class($this).'::'.$action.' not found.', E_USER_NOTICE);
                $this->redirect('HttpErrors/Error404');
            }
        }
        $req->emerge();
    }

    public function present()
    {
        $layout = & $this->_views_layouts[get_class(g()->view)];
        if (null === $layout)
            $layout = $this->_layout;
        $arr = explode('/',$layout);
        $arr[count($arr)-1] = strtolower($arr[count($arr)-1]);
        $this->_layout = implode('/',$arr);
        $this->inc($this->_layout);
    }

}

