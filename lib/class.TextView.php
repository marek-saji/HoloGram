<?php
g()->load('View');

/**
 * @author m.augustynowicz
 */
class TextView extends View
{
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
        ob_start(NULL);
        echo g()->first_controller->displayingCtrl()->contents();
        $contents = ob_get_clean();

        $this->setEncoding();

        g('Functions')->exec('figlet',
                    escapeshellarg(trim('hologramCLI')), $out);
        printf("%s %s v%s",
                join("\n",$out),
                g()->conf['site_name'],
                g()->conf['version']
            );
        echo "\n";

        if (g()->prerender_echo)
        {
            if (g()->debug->allowed())
            {
                $this->_figlet('<pre display>');
                echo g()->prerender_echo;
                $this->_figlet('</pre display>');
            }
            g()->prerender_echo = '';
        }

        echo $contents;
        $this->_figlet('bye');
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


    /**
     */
    protected function _figlet($text)
    {
        static $f = null;
        $f || $f = g('Functions');
        $f->exec('figlet', escapeshellarg(trim($text)), $out);
        echo "\n";
        echo join("\n",$out);
        echo "\n";
    }
}

