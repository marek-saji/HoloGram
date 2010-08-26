<?php
if (!defined('FALLBACK_LANG'))
    define('FALLBACK_LANG', 'en-GB');

/**
 * For applications with only one language.
 *
 * "One lang to rule them all, one lang to find them,
 *  One lang to bring them all and in the darkness bind them.."
 *
 * @author m.augustynowicz
 */
class LangOne extends HgBase implements ILang
{
    protected $_current = FALLBACK_LANG;

    public function __construct(array $params)
    {
        if (isset($params['conf']))
            $conf = $params['conf'];
        else
        {
            global $kernel;
            if ($kernel)
                $conf = $kernel->conf['locale'];
            else
                throw new HgException('Lang called without `conf\' parameter before creation of global $kernel!');
        }

        if (isset($conf['fallback']))
            $this->_current = $conf['fallback'];

        if (isset($conf['time zone']))
            date_default_timezone_set($conf['time zone']);

        return parent::__construct($params);
    }

    public function get()
    {
        return $this->_current;
    }

    public function set($lang)
    {
        return $this->available($lang);
    }

    public function available($lang=null)
    {
        if (null===$lang)
            return array($this->get());
        else
            return strtolower($lang) == strtolower($this->get());
    }

    public function info($lang=null, $attr=null)
    {
        if (null===$lang)
        {
            return array($this->get());
        }
        else
            return strtolower($lang) == strtolower($this->get());
    }

    public function detect()
    {
        return $this->get();
    }

}

