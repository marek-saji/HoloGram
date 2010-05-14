<?php

if (!defined('FALLBACK_LANG'))
    define('FALLBACK_LANG', 'en-GB');

if (!defined('FALLBACK_LOCALE'))
    define('FALLBACK_LOCALE', 'C');

/**
 * Hologram's default language support class.
 *
 * Implementations of ILang should conform BCF47
 * {@url http://en.wikipedia.org/wiki/IETF_language_tag}
 *
 * If you don't want to use more than one language -- class_override to LangOne.
 *
 * @todo
 *       osobne metody do ustawiania poszczególnych locale'i i timezone'a?
 *
 *       powiązanie time zone z językiem?
 *
 *       niemało kodu będzie można zapożyczyć z klasy Languages z hg-2.0
 *       (ale przy okazji zrewidować, poprawić etc.)
 *
 *       przy rozwoju uaktualniać interfejs -- i nie zapomnieć o dbaniu,
 *       żeby klasa LangOne też była z nim zgodna
 *
 * @author m.augustynowicz
 */
class Lang extends HgBase implements ILang
{
    private $__avaliable = null;
    protected $_session = null;
    protected $_current = FALLBACK_LANG;
    protected $_locale = null;

    public function __construct(array $params)
    {
        $this->_session = & g()->session['lang'];
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
        
        $this->_current = & $this->_session['lang'];

        if ((!$this->_current) && isset($conf['fallback']))
            $this->_current = $conf['fallback'];

        $this->_setLocale();

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
        if (!$this->avaliable($lang))
            return false;
        else
        {
            $prev = $this->_current;
            $this->_current = $lang;
            $this->_setLocale();
            return $prev;
        }
    }

    /**
     * Check Avaliability of a language
     *
     * Shared code with {@see info}
     *
     * @author m.augustynowicz
     *
     * @param null|string $lang lang to check avaliablility of or null to get all
     * @param boolean $getinfo if false: returned array looks like (0=>'pl',1=>'en')
     *                         if true: array('pl'=>array('name'=>,'code'=> etc.)
     * @return boolean|array
     */
    public function avaliable($lang=null, $getinfo=false)
    {
        static $cache = array();
        if (null === $this->__avaliable)
        {
            $lang_model = g('Lang','model');
            $lang_model->order('id');
            $this->__avaliable = (array) $lang_model->exec();
            g('Functions')->changeKeys($this->__avaliable, 'code');
        }

        if (null === $lang)
        {
            if ($getinfo)
                return $this->__avaliable;
            else
                return array_keys($this->__avaliable);
        }
        else
        {
            $avaliable = isset($this->__avaliable[strtolower($lang)]);
            if (!$getinfo)
                return $avaliable;
            else
            {
                if ($avaliable)
                    return $this->__avaliable[strtolower($lang)];
                else
                    return false;
            }
        }
    }

    /**
     * Gets array with info about languages
     * @param null|string|true $lang language code or null to get all
     *        or true to get current language's info
     * @param null|string $attr null to get array with all attributes
     *        or attribute name
     * @return array|string array must contain 'code' and 'name' keys at least.
     *         under special circumstances name can have the same value as code.
     *         Some classes implementing this interface can provide other keys
     *         (e.g. 'id'), but you have to remember that they can be absent.
     *         or string with specific attribute.
     *         false if $lang or $attr is not avaliable.
     */
    public function info($lang=null, $attr=null)
    {
        if (true === $lang)
            $lang = $this->get();

        $avaliable = $this->avaliable($lang, true);

        if (null === $attr)
            return $avaliable;
        else
            return @$avaliable[$attr];
    }

    public function detect()
    {
        return $this->get();
    }

    /**
     * Sets LC_ALL (may change in future) based on
     * current language.
     *
     * Warning: doesn't work for all correct BCF47 formats.
     *          as the matter of fact -- works only with pl and en
     * @author m.augustynowicz
     * @return boolean|string set locale (false on error)
     */
    protected function _setLocale()
    {
        @list($lang,$variang) = explode('-', $this->_current, 2);
        switch (strtolower($lang))
        {
            case 'pl' :
                $locale = array('pl_PL.utf8', 'pl_PL', 'pl', 'polish');
                break;
            case 'en' :
                $locale = array('en_GB.utf8', 'en_US.utf8', 'en.utf8', 'en', 'en_GB', 'en_US');
                break;
            default :
                $locale = array();
        }
        $locale[] = FALLBACK_LOCALE;

        return $this->_locale = setlocale(LC_ALL, $locale);
    }

}

