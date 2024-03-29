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
    private $__available = null;
    private $__available_by_id = null;
    protected $_current = FALLBACK_LANG;
    protected $_locale = null;

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
        if (!$this->available($lang))
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
    public function available($lang=null, $getinfo=false)
    {
        if (null === $this->__available)
        {
            $this->__available = (array) g('Lang','model')->exec();
            g('Functions')->changeKeys($this->__available, 'code');
        }

        if (null === $lang)
        {
            if ($getinfo)
                return $this->__available;
            else
                return array_keys($this->__available);
        }
        else
        {
            $available = isset($this->__available[strtolower($lang)]);
            if (!$getinfo)
                return $available;
            else
            {
                if ($available)
                    return $this->__available[strtolower($lang)];
                else
                    return false;
            }
        }
    }

    /**
     * Gets language code
     * 
     * @author b.matuszewski
     * @param int $lang_id language id
     * @return string language code
     */
    public function getLangCodeById($lang_id)
    {
        if($this->__available_by_id === null)
        {
            $this->available();
            foreach($this->__available as $code => & $lang)
                $this->__available_by_id[$lang['id']] = $lang;
        }
        return $this->__available_by_id[$lang_id]['code'];
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
     *         false if $lang or $attr is not available.
     */
    public function info($lang=null, $attr=null)
    {
        if (true === $lang)
            $lang = $this->get();

        $available = $this->available($lang, true);

        if (null === $attr)
            return $available;
        else
            return @$available[$attr];
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
