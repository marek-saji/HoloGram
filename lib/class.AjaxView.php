<?php
g()->load('View');

/**
 * @author m.augustynowicz
 */
class AjaxView extends View
{
    public function __construct()
    {
        $id_offset = (int) $_POST['hg+id_offset'];
        g('Functions')->setUniqueIdOffset($id_offset);

        parent::__construct();
    }

    /**
     * Set up default includes, links, meta, css etc.
     *
     * Everything should be already on page, so not much to do here
     */
    protected function _addDefaults()
    {
        $this->_inl_jses['hg_id_offset'] = ''; // will be set in _renderHeadJSCode
    }

    /**
     * Prezentuje strone. Wysyla do przegladarki wszystkie informacje potrzebne do wyswietlenia
     * albo zaktualizowania strony.
     */
    public function present()
    {
        $first = g()->first_controller;
        ob_start(NULL);

        if (g()->prerender_echo)
        {
            if (g()->debug->allowed())
            {
                # NOTE to future us:
                # if you modify this, please also take care of DebugController::render().
                echo '<div id="pre-display_stuff" class="ajax" style="left: 1.5em">';
                $lines_count = count(explode("\n", g()->prerender_echo));
                printf('<div id="pre-display_stuff_switcher" style="left:1.5em" title="switch pre-display stuff visibility (~%d lines)" onclick="var o=this.parentNode;o.className = o.className ? \'\' : \'foo\'">x</div>', $lines_count);
                echo '<div id="pre-display_stuff_content">'.g()->prerender_echo.'</div></div>';
            }
            g()->prerender_echo = '';
        }

        if(@empty($_POST['validate_me']))
            $first->render();
        $ret = array_merge(
                (array) $first->getAssigned('json'),
                (array) $first->displayingCtrl()->getAssigned('json')
            );

        $ret['html'] = ob_get_clean();

        $ret['class'] = sprintf("%s %1\$s__%s",
                $this->_renderer->getName(),
                $this->_renderer->getLaunchedAction()
            );

        if (preg_match('/([0-9]*)$/', g('Functions')->uniqueId(), $matches))
        {
            $numeric_id = $matches[1];
            $this->_inl_jses['hg_id_offset'] = "window.hg_id_offset += "
                    . (100+$numeric_id);
        }

        if ($this->_inl_jses)
            $ret['js'] = $this->_inl_jses;

        if ($this->_onloads)
            $ret['onload'] = $this->_onloads;

        if ($this->_links)
            $ret['links'] = $this->_links;

        if ($this->_inl_csses)
            $ret['css'] = $this->_inl_csses;

        if ($this->_title)
            $ret['title'] = htmlspecialchars(strip_tags($this->_title));

        $this->setEncoding(); // will also set content-type

        foreach ($this->_headers as $type => &$value)
            header($type.': '.$value);

        echo json_encode($ret);
    }    
    
    /**
     * Sets character encoding.
     * @uses addHeader()
     *
     * @param string $enc charset
     * @return void
     */
    public function setEncoding($enc=NULL)
    {
        if (NULL === $enc) // so it conforms the interface
            $enc = 'utf-8';

        $this->_encoding = $enc;
        $header = 'Content-type';
        $value  = 'application/json; charset='.$enc;
        $this->addHeader($header, $value);
    }
}

