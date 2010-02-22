<?php

g()->load('View');

/**
 * @author m.augustynowicz
 */
class FeedView extends View
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

    /**
     * Prezentuje strone. Wysyla do przegladarki wszystkie informacje potrzebne do wyswietlenia
     * albo zaktualizwoania strony.
     */
    public function present()
    {
        g()->conf['allow_debug'] = false;
        
        ob_start(NULL);
        echo g()->first_controller->render();
        $contents = ob_get_contents();
        ob_end_clean();
        
        if (!isset($this->_headers['content-type']))
            $this->setEncoding();

        foreach ($this->_headers as $type => &$value)
            header($type.': '.$value);

        $lang = htmlspecialchars($this->_lang);
        $enc = $this->_encoding;
        echo <<<DOC_END
<?xml version="1.0" encoding="$enc"?>

DOC_END;

        $NULL = NULL; // so it can be passed by reference

        # <feed>
        echo <<< FEED
<feed xmlns="http://www.w3.org/2005/Atom"
      xmlns:xh="http://www.w3.org/1999/xhtml"
      xml:lang="$lang">

FEED;

        $metatags = array_intersect_key($this->_metas, array(
                'subtitle' => true,
                'id' => true
            ));
        $metatags = array_merge($metatags, array(
                'title' => $this->_title
            ));
        foreach ($metatags as $tagname => $value)
        {
            $value = htmlspecialchars(strip_tags($value));
            echo "  <$tagname>$value</$tagname>\n";
        }
        
        $authortags = array_intersect_key($this->_metas, array(
                'author' => true,
                'email' => true,
                'uri' => true
            ));
        if (@$authortags['author'])
        {
            $authortags['author'] = $authortags['name'];
            unset($authortags['author']);
            echo "  <author>\n";
            foreach ($authortags as $tagname => $value)
            {
                $value = htmlspecialchars(strip_tags($value));
                echo "    <$tagname>$value</$tagname>\n";
            }
            echo "  </author>\n";
        }


        if (g()->prerender_echo && g()->conf['allow_debug'])
        {
            printf("<!-- %s -->\n", htmlspecialchars(g()->prerender_echo));
            g()->prerender_echo = '';
        }

        echo $contents;
        
        echo '</feed>';
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
        $value  = 'application/atom+xml; charset='.$enc;
        $this->addHeader($header, $value);
    }
    

}
