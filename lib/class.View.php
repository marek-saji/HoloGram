<?php
/**
 * @author m.augustynowicz
 */
class View extends HgBase implements IView
{
    /**
     * Page rendering component
     * @var Component
     */
    protected $_renderer = null;

    protected $_is_html5 = true;

    protected $_title = '';
    protected $_encoding = 'utf-8';
    protected $_lang = '';

    protected $_profiles = array();

    protected $_links = array(); // including css-es
    protected $_inl_csses = array();

    protected $_jses = array();
    protected $_inl_jses = array();
    protected $_onloads = array();

    protected $_metas = array();
    protected $_headers = array();
    protected $_head_code = array();

    public function __construct()
    {
        $this->_renderer = g()->first_controller->displayingCtrl();
        $this->_addDefaults();
    }

    /**
     * Set up default includes, links, meta, css etc.
     */
    protected function _addDefaults()
    {
        // hg.core settings

        $app_ver = @ g()->conf['version'];
        $this->_inl_jses['hg_base'] = sprintf("/**\n * hg settings\n */\nvar hg_base = '%s'",
                g()->req->getBaseUri());
        $this->_inl_jses['hg_include_path'] = "var hg_include_path = hg_base+'js/'";
        $this->_inl_jses['hg_app_ver'] = sprintf("var hg_app_ver = '%s'", @ g()->conf['version']);
        $this->_inl_jses['hg_id_offset'] = ''; // will be set in _renderHeadJSCode
        if (g()->debug->on('js'))
            $this->_inl_jses['hg_debug'] = "var hg_debug = true";

        // css style for things generated by javascript

        $this->addCss($this->_renderer->file('hg.js', 'css'));

        // include some javascript by default

        $js_debug = g()->debug->on('js');
        // jQuery itself
        $jquery_version = '1.4.1';
        $min = $js_debug ? '.min' : '';
        if (g()->debug->on('disable','externalcdn'))
            $this->addJs($this->_renderer->file('jquery','js'));
        else
        {
            $this->addJs('http://ajax.googleapis.com/ajax/libs/jquery/'.$jquery_version.'/jquery'.$min.'.js');
        }
        // make jquery more verbal about errors and warnings
        /*
        if ($js_debug)
            $this->addJs('http://github.com/jamespadolsey/jQuery-Lint/raw/master/jquery.lint.js');
         */

        // our core javascript code (lazy loading etc)
        $this->addJs($this->_renderer->file('hg.core','js'));
        // nasty way to add hg.definitions from each alias
        global $DIRS;
        foreach ($DIRS as &$dir)
        {
            $this->addJs(preg_replace('!^/!', '/'.$dir,
                    $this->_renderer->file('hg.definitions', 'js')));
        }

        if ($js_debug)
        {
            // Firefox Light, for those who can't do magic tricks for themselves
            switch (true)
            {
                case strpos($_SERVER['HTTP_USER_AGENT'],'Firefox') :
                case strpos($_SERVER['HTTP_USER_AGENT'],'Gecko') :
                case strpos($_SERVER['HTTP_USER_AGENT'],'Chrome') :
                case strpos($_SERVER['HTTP_USER_AGENT'],'Chromium') :
                case strpos($_SERVER['HTTP_USER_AGENT'],'Safari') :
                case strpos($_SERVER['HTTP_USER_AGENT'],'Presto') :
                    break;
                default :
                    $this->addJs('http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js');
            }
        }

        // this should make IE behave a litte better
        // IE8.js is for CSS in general
        // html5.js is for, well.. html5
        $attrs = array('type' => 'text/javascript');
        $attrs['src'] = 'http://ie7-js.googlecode.com/svn/version/2.1(beta3)/IE8.js';
        $this->addInHead(sprintf("<!--[if lt IE 9]>\n%s<![endif]-->",
            $this->_tag('script', $attrs)
        ));
        if ($this->_is_html5)
        {
            $attrs['src'] = 'http://html5shiv.googlecode.com/svn/trunk/html5.js';
            $this->addInHead(sprintf("<!--[if IE]>\n%s<![endif]-->",
                $this->_tag('script', $attrs)
            ));
        }
    }

    /**
     * Prezentuje strone. Wysyla do przegladarki wszystkie informacje potrzebne do wyswietlenia
     * albo zaktualizwoania strony.
     */
    public function present()
    {
        ob_start(NULL);
        echo $this->_renderer->render();
        $contents = ob_get_clean();
        
        if (!isset($this->_metas['generator']))
            $this->setMeta('generator', 'Hologram');

        if (!isset($this->_headers['content-type']))
            $this->setEncoding();

        $this->_sendHeaders(); // this adds something to $this->_metas

        if (!$this->_lang)
            $this->setLang();

        $this->_renderDocType();

        $this->_renderHtmlOpen();

        $this->_renderHeadOpen();

        $this->_renderHeadMeta();

        $this->_renderHeadJSCode();

        $this->_renderHeadJSInclusion(); // this adds something to $this->_head_code

        $this->_renderHeadLiterals();

        $this->_renderHeadLinks();

        $this->_renderCSSCode();

        $this->_renderHeadClose();

        $this->_renderBodyOpen();

        echo "$contents\n";

        $this->_renderBodyClose();
        $this->_renderHtmlClose();
    }    
    
    /**
    * Zalacza podkontroler. Ta metoda powinna byc uzywana przez kontrolery do wstawiania 
    * w okreslonym miejscu szablonu zawartosci podkontrolera.
    */   
    public function inc($controller)
    {
        echo $controller->render();
    }

    /**
    * Dodaje znacznik link.
    * @param $name
    * @param def jest w postaci array ( $ident => $definition ), gdzie $definition jest tablica
    *        asocjacyjna z kluczami (opcjonalnymi) 
                title, href, type, media, rel, rev, hreflang.
    */
    public function addLink($name='', $def)
    {
        $def = array_intersect_key(
            $def,
            array_flip(
                array('title', 'href', 'type', 'media', 'rel', 'rev', 'hreflang')
            )
        );
        if ($name)
            $this->_links[$name] = $def;
        else 
            $this->_links[] = $def;
    }
    
    /**
    * Dodaje link do pliku css. Efektem przypomina szczegolne wywolanie addLink.
    */
    public function addCss($file, $media="all")
    {
        $key = 'css '.$file;
        $this->_serializeLink($file);
        $this->addLink(
            $key,
            array(
                'type'=>'text/css', 
                'media' => $media, 
                'rel' =>"stylesheet", 
                'href' => $file
            )
        );
        return(true);
    }
    
    /**
    * Dodaje css wbudowany w html. 
    * @param array($key => $definition), gdzie $key jest selectorem CSS.
    */
    public function addInlineCss($css_code)
    {
        $this->_inl_csses[] = $css_code;
    }    
    
    /**
    * Dodaje zewnetrzny skrypt. 
    */
    public function addJs($file)
    {
        $key = $file;
        $this->_serializeLink($file);
        $this->_jses[$key] = $file;
    }
    
    /**
    * Dodaje skrypt wbudowany w strone. 
    */
    public function addInlineJs($js_code)
    {
        $this->_inl_jses[] = $js_code;
    }
    
    /**
    * Dodaje instrukcje wywolywane po zaladowaniu DOM'u
    */
    public function addOnLoad($js_code)
    {
        $this->_onloads[] = $js_code;
    }    
    
    public function addKeyword($word)
    {
        $keywords = $this->getMeta('keywords');
        $keywords = (array)@$keywords['content'];
        $keywords[] = $word;
        $this->setMeta('keywords', implode(', ', $keywords));

    }

    public function setDescription($desc)
    {
        $this->setMeta('description', $desc);
    }

    public function setTitle($title)
    {
        $this->_title = $title;
    }    
    
    public function getTitle()
    {
        return $this->_title;
    }
    
    public function getMeta($name)
    {
        return @$this->_metas[$name];
    }
    
    public function setMeta($name,$value)
    {
        $this->_metas[$name] = array(
                'name'          => $name,
                'content'       => $value,
            );
    }

    /**
     * Registers HTTP header.
     * It should be sent in {@see present()}
     * and adequate <meta http-equiv="".. will be displayed.
     *
     * @param string $type header type, e.g. 'Content-type'
     * @param string $value header value, e.g. text/html; charset=utf-16
     * @return void
     */
    public function addHeader($type, $value)
    {
        $type = strtolower($type);
        $this->_headers[$type] = $value;
    }
    
    /**
     * Add code between <head></head> signs
     * IMPORTANT!!! - Do not use this function too much; e.g. if you want add JS please do it function addJs()     
     *
     * @param string $code - code to palce in head
     * @return void
     */
    public function addInHead($code)
    {
        $this->_head_code[] = $code;
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
        $value  = 'text/html; charset='.$enc;
        $this->addHeader($header, $value);
    }


    /**
     * Sets page language.
     *
     * @url http://en.wikipedia.org/wiki/IETF_language_tag
     *
     * @param string $lang Language code conforming BCF47
     * @return void
     */
    public function setLang($lang=null)
    {
        if (null === $lang)
            $this->_lang = g()->lang->get();
        else
            $this->_lang = $lang;
    }

    /**
     * Gets language.
     *
     * @url http://en.wikipedia.org/wiki/IETF_language_tag
     *
     * @return string Language code conforming BCF47
     */
    public function getLang()
    {
        return $this->_lang;
    }


    /**
     * Adds HTML head's profile.
     * Used mainly for microformats' XMDPs.
     * 
     * @param string $uri URI to profile document
     */
    public function addProfile($uri)
    {
        $this->_profiles[$uri] = $uri;
    }

    
    /**
     * Generic tag code generator.
     *
     * @param string $name tag name
     * @param array $attrs reference to HTML attributes
     * @param string $content reference to tag's content
     * @param integer|string $tab line prefix, if string;
     *        or indent length, if integer
     *
     * @return html code
     */
    private function _tag($name, &$attrs=array(), &$content=null)
    {
        $f = g('Functions');

        $html = '  ';

        // <script /> _have to_ be rendered witch closing tag
        if (!empty($content) || $name == 'script')
            $html .= $f->tag($name, (array) $attrs, $content);
        else
            $html .= $f->tag($name, (array) $attrs);

        $html .= "\n";
        return $html;
    }

    /**
     * Very sophisticated method to prevent css and js files being cached.
     * @author m.augustynowicz
    *
     * @param string $url reference to the url
     * @return void given url is modified by reference
     */
    protected function _serializeLink(&$url)
    {
        $stamp = g()->debug->on() ? time() : @g()->conf['version'];
        if ('/' !== $url[0])
            return; // don't touch remote files
        $glue = FALSE===strstr($url,'?') ? '?' : '&amp;';
        $url .= $glue . base_convert($stamp,10,36);
    }
    

    /**
     * Renders document's DOCTYPE
     */
    protected function _renderDocType()
    {
        if ($this->_is_html5)
            echo '<!DOCTYPE html>';
        else
            echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        echo "\n";
    }

    /**
     * Renders opening <html> tag
     */
    protected function _renderHtmlOpen()
    {
        $lang = htmlspecialchars($this->_lang);
        if ($this->_is_html5)
            printf("<html xml:lang=\"%s\" lang=\"%1\$s\">\n", $lang);
        else
        {
            printf("<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"%s\" lang=\"%1\$s\">\n",
                    $lang);
        }
    }

    /**
     * Renders closing </html> tag
     */
    protected function _renderHtmlClose()
    {
        printf("</html>\n");
    }

    /**
     * Renders opening <head> tag
     */
    protected function _renderHeadOpen()
    {
        if ($profiles = implode(' ', $this->_profiles))
            printf("<head profile=\"%s\">\n", $profiles);
        else
            print "<head>\n";
    }

    /**
     * Renders closing </head> tag
     */
    protected function _renderHeadClose()
    {
        printf("</head>\n");
    }

    /**
     * Renders opening <body> tag
     *
     * also adds js class to it, if javascript is working,
     * in addition renders some cool stuff in debug mode as well:
     * prerender echoes and unhandled infoses
     */
    protected function _renderBodyOpen()
    {
        printf("<body class=\"%s %1\$s__%s\">\n",
                $this->_renderer->getName(),
                $this->_renderer->getLaunchedAction()
            );
        // add js class to body, if javascript is present
        echo '<script type="text/javascript">document.getElementsByTagName("body")[0].className += " js";</script>';

        if (g()->debug->allowed())
        {
            if (g()->prerender_echo)
            {
                printf("<pre id=\"prerender\" style=\"color:black;background-color:white;text-align:left;float:none;margin:0;padding:0;\"><strong>prerender echoes:</strong>\n%s</pre>\n",
                       g()->prerender_echo);
                g()->prerender_echo = '';
            }
            if (g()->infos)
            {
                echo "<div id=\"infos\"><h2>Unhandled informations:</h2>\n";
                foreach(g()->infos as $class => &$infos)
                    echo "  <ul class=\"infos_$class\">\n    <li>".implode("</li>\n    <li>",$infos)."</li>\n  </ul>\n\n";
                echo "</div>\n";
                g()->infos = array();
            }
        }
    }

    /**
     * Renders closing </body> tag
     *
     * and displays some useful stuff in debug mode
     */
    protected function _renderBodyClose()
    {
        if (g()->debug->allowed())
        {
            echo '<div id="foot_debug" style="text-align: left;">';
            // superglobals
            print '<pre id="debug_superglobals" style="margin: 1ex;">';
            foreach (array('SESSION','POST','FILES','COOKIE','SERVER') as $var)
            {
                if ($GLOBALS['_'.$var])
                {
                    print '<div>';
                    printf('<strong>%s</strong> <a href="javascript:void(0)" onclick="css=this.nextSibling.style; css.display=css.display==\'none\'?\'block\':\'none\'">toggle</a><pre style="display:none">', $var);
                    var_dump($GLOBALS['_'.$var]);
                    print '</pre></div>';
                }
            }
            print '</pre> <!-- #debug_superglobals -->';
            // debug stats
            g()->debug->printStats();
            // templates inclusion tree
            if (g()->debug->on('view'))
            {
                print '<pre id="debug_inc_tree" style="margin: 1ex">';
                printf("<strong>templates inclusion tree</strong>\n");
                $prev_path = '';
                foreach (Controller::$debug_inc_tree as $inc_data)
                {
                    $ident = str_repeat('  ', $inc_data['level']);
                    if ($prev_path != $inc_data['path'])
                        printf("%s<strong><small>%s</small></strong>, <small>(%s)</small>\n",
                               $ident, $inc_data['path'], $inc_data['class'] );
                    $prev_path = $inc_data['path'];
                    printf("%s<span title=\"%s\">%s</span>\n",
                           $ident, $inc_data['file'], $inc_data['tpl'] );
                }
                print '</pre>';
            }
            echo '</div> <!-- .foot_debug -->';
        }

        $this->_renderHeadJSOnLoadCode();

        printf("</body>\n");
    }

    /**
     * Sends HTTP headers
     */
    protected function _sendHeaders()
    {
        $meta = array();
        foreach ($this->_headers as $type => &$value)
        {
            header($type.': '.$value);
            $meta['http-equiv='.$type] = array(
                    'http-equiv' => & $type,
                    'content'    => & $value,
                );
        }
        $this->_metas = array_merge($meta, $this->_metas);
    }

    /**
     * Renders <meta /> tags as well as <title /> in <head />
     */
    protected function _renderHeadMeta()
    {
        $NULL = NULL; // so it can be passed by reference

        # <title>..</title>
        if ($this->_title)
        {
            $title = htmlspecialchars(strip_tags($this->_title));
            echo $this->_tag('title', $NULL, $title);
        }

        # <meta name="..
        foreach ($this->_metas as &$attrs)
        {
            echo $this->_tag('meta', $attrs);
        }
    }

    /**
     * Render literal HTML code in <head />
     *
     * should be used as rarely as possible..
     */
    protected function _renderHeadLiterals()
    {
        foreach ($this->_head_code as &$html)
        {
            echo $html."\n";
        }
    }

    /**
     * Renders <link /> tags in <head />
     */
    protected function _renderHeadLinks()
    {
        foreach ($this->_links as &$attrs)
        {
            echo $this->_tag('link', $attrs);
        }
    }

    /**
     * Renders javascript code that get executed immediately
     */
    protected function _renderHeadJSCode()
    {
        $this->_inl_jses['hg_id_offset'] = "var hg_id_offset = "
                . (100+g('Functions')->uniqueId(null));

        // display
        if ($this->_inl_jses)
        {
            echo '  <script type="text/javascript">';
            echo "\n  // <![CDATA[\n";
            foreach ($this->_inl_jses as &$command)
                echo "$command;\n";
            echo "  // ]]>\n";
            echo '  </script>';
            echo "\n";
        }
    }

    /**
     * Renders inclusion of javascript files in <head />
     */
    protected function _renderHeadJSInclusion()
    {
        // display
        foreach ($this->_jses as &$l)
        {
            $attrs = array('type' => 'text/javascript',
                           'src'  => $l );
            echo $this->_tag('script', $attrs);
        }

    }

    /**
     * Renders javascript code that get executed after page loads
     */
    protected function _renderHeadJSOnLoadCode()
    {
        if ($this->_onloads)
        {
            echo '  <script type="text/javascript">';
            echo "\n// <![CDATA[\n";
            echo "  if (typeof $ != 'undefined') {\$(function(){\n";
            foreach ($this->_onloads as &$command)
                echo "    $command;\n";
            echo "  })} else console.error('jQuery not loaded!'); // onload end";
            echo "\n// ]]>\n";
            echo '  </script>';
            echo "\n";
        }
    }

    /**
     * Renders <style /> with CSS rules
     */
    protected function _renderCSSCode()
    {
        if (!empty($this->_inl_csses))
        {
            echo '  <style type="text/css">';
            echo "\n  /* <![CDATA[ */";
            foreach ($this->_inl_csses as $selector => &$def)
                echo "$selector {\n$def\n}\n";
            echo "\n  /* ]]> */";
            echo ' </style>';
            echo "\n";
        }
    }

}

