<?php
/**
 * @todo Doszczegółowić wyświetlanie włączonych debugów w toolbarze
 */


class DebugController extends TrunkController
{

    public function process(Request $req)
    {
        if (g()->conf['allow_debug'] = $this->_session['allow_debug'])
            error_reporting(E_ALL|E_STRICT);
        else
            // @todo it sure looks like potential "wtf? white screen" situation"
            error_reporting(0);
        g()->debug->config();

        if (!isset($this->_conf['sub']['type']))
            throw new HgException("sub controller type not defined for debug controller");
        $this->addChild(
            g($this->_conf['sub']['type'],
                'controller',
                array(
                    'name' => $this->_conf['sub']['name'],
                    'parent' => $this,
                )
            )
        ); 

        $name = ucfirst($this->getName());


        $redirect_me = false;
        while ($current = $req->next())
        {
            if (ucfirst($current)==$name)
            {
                $redirect_me = true;
                if ($req->dive())
                {
                    while ($current = $req->next())
                    {
                        if (!$this->_handle($req,$current))
                            $this->redirect('HttpErrors/Error404');
                        $this->_launched_action = null; // to avoid warnings
                    }
                    $req->emerge();
                }
            }        
        }
        // pretty mutch the same as while($req->prev()) but faster:
        $req->emerge();

        // redirect to path without Debug
        
        if ($redirect_me)
        {
            $new_url = explode(';',$req->getUrlPath());
            $regex = '!(^|/)'.preg_quote(ucfirst($this->getName())).'[:/]!';
            foreach ($new_url as $k => &$new_part)
            {
                if (preg_match($regex, $new_part))
                    unset($new_url[$k]);
            }
            $new_url = join(';',$new_url);
            if (!$new_url)
                $new_url = $req->getReferer();

            $this->redirect($new_url,false);
        }

        parent::process($req);
    }
    
    
    public function render()
    {
        $this->inc('toolbar');

        if (g()->debug->allowed() && !empty(g()->prerender_echo))
        {
            switch ($view_class_name = get_class(g()->view))
            {
            case 'View' :
                # NOTE to future us:
                # if you modify this, please also take care of AjaxView::present().
                echo '<div id="pre-display_stuff">';
                $lines_count = count(explode("\n", g()->prerender_echo));
                printf('<div id="pre-display_stuff_switcher" title="switch pre-display stuff visibility (~%d lines)" onclick="var o=this.parentNode;o.className = o.className ? \'\' : \'foo\'">x</div>', $lines_count);
                echo '<div id="pre-display_stuff_content">'.g()->prerender_echo.'</div></div>';
                break;
            case 'FeedView' :
            case 'XmlView' :
                printf("<!-- <![CDATA[ PRERENDER ECHO BEGIN:\n\n%s\n\n PRERENDER ECHO END. ]]> -->", g()->prerender_echo);
                break;
            }
            g()->prerender_echo='';
        }
        parent::render();
    }    
    
    
    public function defaultAction(array $params)
    {
        
    }
    
    public function actionOn(array $params)
    {
        g()->conf['allow_debug'] = $this->_session['allow_debug'] = true;
        error_reporting(E_ALL|E_STRICT);
        g()->debug->config();
        if (isset($params['global']))
        {
            $switch = g('Functions')->anyToBool($params['global'], true);
            g()->debug->set($switch);
            printf('<pre> * global debug went %s</pre>', $switch?'on':'off');
        }
    }

    
    public function actionOff(array $params)
    {
        g()->conf['allow_debug'] = $this->_session['allow_debug'] = false;
        error_reporting(0);
        g()->debug->config();
    }

    public function actionSet(array $params)
    {
        echo '<pre>';
        foreach ($params as $k => $v)
        {
            if ('global' === $k)
            {
                $item = '';
                $switch = g('Functions')->anyToBool($v, true);
            }
            else if (g('Functions')->isInt($k))
            {
                $item = $v;
                $switch = true;
            }
            else
            {
                $item = $k;
                $switch = g('Functions')->anyToBool($v, true);
            }

            if ('fav' === $item)
            {
                $items = @g()->conf['favorite debugs'];
                if (!is_array($items))
                    $items = preg_split('/[, ]+/', (string)$items);
            }
            else
                $items = array($item);

            foreach ($items as $item)
            {
                g()->debug->set($switch, explode('.',$item));
                printf(" * %s debug went %s\n", $item, $switch?'on':'off');
            }
        }
        echo '</pre>';
    }

    public function url2a($act='', array $params=array())
	{
        return parent::url2c(ucfirst($this->getName()), $act, $params);
	}

}

