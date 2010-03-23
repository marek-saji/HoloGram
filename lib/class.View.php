<?php
class View extends HgBase implements IView
{
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

    /**
     * Prezentuje strone. Wysyla do przegladarki wszystkie informacje potrzebne do wyswietlenia
     * albo zaktualizwoania strony.
     */
    public function present()
    {
        ob_start(NULL);
        echo g()->first_controller->render();
        $contents = ob_get_clean();
        
        if (!isset($this->_metas['generator']))
            $this->setMeta('generator', 'Hologram');

        if (!isset($this->_headers['content-type']))
            $this->setEncoding();

        foreach ($this->_headers as $type => &$value)
            header($type.': '.$value);

        if (!$this->_lang)
            $this->setLang();
        $lang = htmlspecialchars($this->_lang);
        echo <<<DOC_END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$lang}" lang="{$lang}">

DOC_END;

        $NULL = NULL; // so it can be passed by reference

        # <head>
        if ($profiles = implode(' ', $this->_profiles))
            printf("<head profile=\"%s\">\n", $profiles);
        else
            print "<head>\n";

        # <title>..</title>
        if ($this->_title)
        {
            $title = htmlspecialchars(strip_tags($this->_title));
            echo $this->_tag('title', $NULL, $title, 2);
        }

        # <meta http-equiv="..
        foreach ($this->_headers as $type => &$value)
        {
            $attrs = array('http-equiv' => $type,
                           'content'    => $value);
            echo $this->_tag('meta', $attrs, $NULL, 2);
        }
        # <meta name="..
        foreach ($this->_metas as &$l)
            echo $this->_tag('meta',$l,$NULL,'  ');

        # literal html code
        foreach ($this->_head_code as &$l)
        {
            echo $l."\n";
        }

        # <link ..
        foreach ($this->_links as &$l)
        {
            echo $this->_tag('link',$l,$NULL,'  ');
        }

        # <style>..</style>
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

        # <script>..</script>
        if ($this->_inl_jses)
        {
            echo '  <script type="text/javascript">';
            echo "\n  // <![CDATA[\n";
            foreach ($this->_inl_jses as &$command)
                echo "$command;\n";
            echo "\n  // ]]>\n";
            echo '  </script>';
            echo "\n";
        }

        # <script src="..
        foreach ($this->_jses as &$l)
        {
            $attrs = array('type' => 'text/javascript',
                           'src'  => $l );
            echo $this->_tag('script', $attrs, $NULL, 2);
        }

        # <script>..</script>
        if ($this->_onloads)
        {
            echo '  <script type="text/javascript">';
            echo "\n  // <![CDATA[\n";
            if ($this->_onloads)
            {
                echo "  if (typeof $ != 'undefined')\n  {\$(function(){\n";
                foreach ($this->_onloads as &$command)
                    echo "    $command;\n";
                echo "  })} // onload end\n";
            }
            echo "\n  // ]]>\n";
            echo '  </script>';
            echo "\n";
        }
        
        $page = g()->first_controller->displayingCtrl();
        printf("</head>\n<body class=\"%s %1\$s__%s\">\n",
                $page->getName(), $page->getLaunchedAction() );

        if (g()->prerender_echo && g()->debug->allowed())
        {
            printf("<pre id=\"prerender\" style=\"color:black;background-color:white;text-align:left;float:none;margin:0;padding:0;\"><strong>prerender echoes:</strong>\n%s</pre>\n",
                   g()->prerender_echo);
            g()->prerender_echo = '';
        }

        if (g()->infos && g()->conf['allow_debug'])
        {
            echo "<div id=\"infos\"><h2>Unhandled informations:</h2>\n";
            foreach(g()->infos as $class => &$infos)
                echo "  <ul class=\"infos_$class\">\n    <li>".implode("</li>\n    <li>",$infos)."</li>\n  </ul>\n\n";
            echo "</div>\n";
            g()->infos = array();
        }

        echo "$contents\n";

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

        echo "</body>\n</html>";
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
     */
    private function _tag($name, &$attrs=array(), &$content='', $tab='')
    {
        $f = g('Functions');
        if ($f->isInt($tab))
            while ($tab--) echo ' ';
        else
            echo $tab;

        // <script /> _have to_ be rendered witch closing tag
        if (!empty($content) || $name == 'script')
            echo $f->tag($name, $attrs, $content);
        else
            echo $f->tag($name, $attrs);
    }

    /**
     * Very sophisticated method to prevent css and js files being cached.
     * @author m.augustynowicz
    *
     * @param string $url reference to the url
     * @return void given url is modified by reference
     */
    private function _serializeLink(&$url)
    {
        $stamp = g()->debug->on() ? time() : @g()->conf['version'];
        if ('/' !== $url[0])
            return; // don't touch remote files
        $glue = FALSE===strstr($url,'?') ? '?' : '&amp;';
        $url .= $glue . base_convert($stamp,10,36);
    }
    

}
