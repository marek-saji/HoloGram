<?php

/**
 * Request enriched with reading language from URL.
 * 
 * @author m.augustynowicz
 * @author m.jutkiewicz (setting & getting cookies for language changes)
 */
class LangRequest extends Request
{
    protected function _diminishURL(&$url)
    {
        if (preg_match('/\.php(\?|$)/', $url))
            return;
        preg_match('!^/([a-z]{0,3}/?)(.*?)(\.html|)$!', $url, $matches);
        list(, $prefix, $url, $suffix) = $matches;
        $prefix = trim($prefix, '/');
        $url = '/' . ltrim($url, '/');

        if (!$prefix)
            $prefix = @$_COOKIE['language'];

        if ('/' != $url && (!$prefix || !$suffix))
        {
            if (!$prefix)
                $prefix = g()->lang->get();
            if (!$suffix)
                $suffix = '.html';
            setcookie('language', $prefix, time() + 60 * 60 * 24 * 30, '/');
            g()->redirect($this->getBaseUri(true) . $prefix . $url . $suffix);
        }
        else
        {
            if ($prefix)
                g()->lang->set($prefix);
            setcookie('language', $prefix, time() + 60 * 60 * 24 * 30, '/');
        }
    }

    public function enhanceURL(&$url)
    {
        if ($url)
            $url = sprintf('%s.html', $url);
        $url = g()->lang->get() . '/' . $url;
    }

}

