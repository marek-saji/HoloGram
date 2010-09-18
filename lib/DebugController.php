<?php
/**
 * Debug flavour.
 *
 * Enabling and disabling debug modes,
 * debug toolbar,
 * pre-render output debug.
 * Pretty much everything's that debug.
 * @author m.augustynowicz
 *
 * @todo provide more details when displaying enabled debugs in toolbar
 */
class DebugController extends TrunkController
{
    /**
     * Parent onAction checks permissions. We don't really want that.
     * @author m.augustynowicz
     * @return true allowing access
     */
    protected function _onAction($action, array & $params)
    {
        return true;
    }


    /**
     * Processing request
     *
     * Normally trunk controllers don't take part in processing user request.
     * So we make some dirty hacks to do that.
     * @author p.piskoski
     * @author m.augustynowicz
     *
     * @param Request $req set to currently handled node (should be root)
     */
    public function process(Request $req=null)
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

        // launch action of this controller

        $name = ucfirst($this->getName());
        $anything_launched = false;
        while ($current = $req->next())
        {
            if (ucfirst($current)==$name)
            {
                $anything_launched = true;
                if ($req->dive())
                {
                    while ($current = $req->next())
                    {
                        $params = $req->getParams();
                        if (false === $this->launchAction($current, $params))
                        {
                            $this->redirect('HttpErrors/Error404');
                        }
                        // debug controller can launch more than one action,
                        // so to avoid warnings:
                        $this->_launched_action = null;
                    }
                    $req->emerge();
                }
            }
        }
        // HACK pretty mutch the same as while($req->prev()) but faster
        $req->emerge();


        // redirect to path without Debug

        if ($anything_launched)
        {
            $new_url = preg_replace(
                    '!;?\b'.preg_quote($name).'([^;]*);?!',
                    '',
                    trim($req->getUrlPath(),'/')
                );
            if ($new_url)
                $new_url = '/' . $new_url;
            else
                $new_url = $req->getReferer();

            $this->redirect($new_url,false);
        }

        parent::process($req);
    }
    
    
    /**
     * Display toolbar, then prerender echoe, then real content
     * @author m.augustynowicz
     */
    public function present()
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

        parent::present();
    }    
    
    
    /**
     * Don't do anything by default. Just in case.
     * @author m.augustynowicz
     */
    public function defaultAction(array $params)
    {
        
    }
    
    /**
     * Enable debug mode
     * @author p.piskorski
     * @author m.augustynowicz
     */
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

    
    /**
     * Disable debug mode
     * @author p.piskorski
     * @author m.augustynowicz
     */
    public function actionOff(array $params)
    {
        g()->conf['allow_debug'] = $this->_session['allow_debug'] = false;
        error_reporting(0);
        g()->debug->config();
    }


    /**
     * Enable/disable debug sub-mode
     *
     * Later on, when calling debug->on() from, for example FooController,
     * we check if debug is enabled for "foo", then for "foo.controller".
     * You can also use custom sub-modes like "disable.captcha" (and check
     * it by debug->on('disable','captcha');
     *
     * @author m.augustynowicz
     */
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


    /**
     * Produce URLs that can be handled by process()
     * @param string $act action name
     * @param array $params action's params
     * @return string
     */
    public function url2a($act='', array $params=array())
    {
        return parent::url2c(ucfirst($this->getName()), $act, $params);
    }

}

