<?php
/**
 * Core classes and interfaces of Hologram
 *
 * Be careful with putting things in here: they cannot be overwriten in apps!
 *
 * @package hologram2
 */



/**
 * HoloGram exceptions
 * @package hologram2
 */
require_once(HG_DIR.'lib/class.HgException.php');



/**
 * Autoloading's performance is not really great, so we'll throw exceptions
 * if anybody tries that
 * @author m.augustynowicz
 * @uses $kernel
 *
 * @param string $name class or interface name
 *
 * @return void
 */
function __autoload($name)
{
    throw new Exception("System Error #000, autoload called: no $name found! ZOMG!");
}



/**
 * Object Factory, wrapper for Kernel::get()
 * @author p.piskorski
 * @author m.augustynowicz
 * @uses $kernel
 *
 * @param string $name class name (without suffix)
 * @param string $type class name suffix
 * @param array $args
 *
 * @return HgBase
 */
function g($name='', $type='class', $args=array())
{
    global $kernel;
    if (empty($kernel))
        throw new HgException('g() called before creation of global $kernel');
    $argv = func_get_args();
    return call_user_func_array(array($kernel,'get'), $argv);
}



/**
 * Wrapper to allow chain calling with `new` operator in PHP
 *
 * example:
 *
 *     $bar = o(new Foo)->getBar();
 *
 * @param object $obj
 *
 * @return object the very same $obj
 */
function o($obj)
{
    return $obj;
}


/**
 * @rapper to allow chain usage of arrays
 *
 * example:
 *
 *     $bar = arr($foo->getList(), 0); // $foo->getList()[0]
 *
 * @param array $arr
 * @param mixed $key
 *
 * @return mixed
 */
function arr(array $arr, $key)
{
    return $arr[$key];
}


// lcfirst() has been added in php-5.3
if (!function_exists('lcfirst'))
{
    /**
     * Lower case first letter in a string
     * @author m.augustynowicz
     *
     * @param string $string
     *
     * @return string
     */
    function lcfirst($string)
    {
        if (is_string($string))
            $string[0] = strtolower($string[0]);
        return $string;
    }
}


////////////////////////////////////////////////////////////////////////
////////////////////////// interfaces //////////////////////////////////
////////////////////////////////////////////////////////////////////////


/**
 * Interface for classes that can be used to process user's requests.
 *
 *
 * Possible callbacks in classes implementing this interface:
 *
 * * action{ActionName}(array $params)
 *
 *   action that can be called with launchAction().
 *   launching an action sets $this->_template to lowercased action name,
 *   You can override this using _setTemplate().
 *
 *   - $params are request params
 *   - return value is ignored
 *
 * For (lot of) more callbacks see Component
 */
interface IController
{
    /**
     * Process request (create components, launch actions)
     */
    public function process(Request $req=null);

    /**
     * Default action is launched, when no other is specified.
     * @param array $params request params
     * @return bool|int
     *         ===false if action does not exist;
     *         ===true if exists and was launched;
     *         ===1 if exists, but was not launched
     */
    public function launchDefaultAction(array &$params=array());

    /**
     * Launch any action
     * @param null|string $action action to launch,
     *        null or empty string will launch default action
     * @param array $params request params
     * @return bool|int
     *         ===false if action does not exist;
     *         ===true if exists and was launched;
     *         ===1 if exists, but was not launched
     */
    public function launchAction($action, array &$params=array());

    /**
     * Getter for launched action name
     * @param boolean $collapse_default if true, will return empty string form default action
     * @return string|null
     */
    public function getLaunchedAction($collapse_default=false);

    /**
     * Render controller (without lauout and such)
     * @return string rendered code (html, xml, text etc)
     */
    public function render();

    /**
     * Getter for controller's name
     * @return string controller name
     */
    public function getName();

    /**
     * Getter for controller's parent
     * @return IController controller's parent
     */
    public function getParent();

    /**
     * Adds a child controller (a subcontroller)
     *
     * usages: addChild(string)
     *         addChild(string, array)
     *         addChild(IController)
     * @param IController|string $controller to add
     *        (object or name to pass to Kernel::get())
     * @param null|string|array $name_or_args
     *        null -- use lowercased $controller as name;
     *        string -- use as name for $controller;
     *        array -- pass to $controller's constructor;
     * @return the $controller itself (to allow chainig)
     */
    public function addChild($controller, $name_or_args=null);

    /**
     * Checks if given controller is a child of current one
     * @param IController|string $controller controller to look for,
     *        or controller name.
     * @return bool|string false if $controller is not subcontroller;
     *         otherwise name of the found controller is returned
     */
    public function isChild($controller);

    /**
     * Getter for all the children
     * @return array all of controller's children
     */
    public function getChildren();

    /**
     * Getter for one of the children
     * @param string $name name of requested child
     * @return IController|null child object, or null if none found
     */
    public function getChild($name);

    /**
     * Getter for controller that handles layout in current request
     * @return IController
     */
    public function displayingCtrl();

    /**
     * Checks whether component is request's main displaying controller.
     * @author m.augustynowicz
     *
     * @return boolean
     */
    public function isDisplayingCtrl();

    /**
     * Getter for permanent controllers
     * @param string $name permanent controller name
     *        as defined in conf[permanent_controllers]
     * @return PermanentController
     */
    public function getPermaCtrl($name);
}



/**
 * Interface of controller that handles authentication of users
 */
interface IUserController extends IController
{
    public function actionLogin(array $params);

    public function actionLogout(array $params);
}



/**
 * Interface for class that handles authorization and authentication.
 * It is available via g()->auth
 */
interface IAuth
{
    /**
     * Check whether we are logged in
     * @return boolean true if some user is logged in
     */
    public function loggedIn();

    /**
     * Return logged in user's id
     * @return bool|mixed logged-in user's id (type depends on UserModel)
     *         or false when no user is logged in
     */
    public function id();

    /**
     * Return logged in user's display name
     * @return bool|mixed logged-in users's display name
     *         or false when no user is logged in
     */
    public function displayName();


    /**
     * Get logged-in users's data.
     * @param string $field field name, available fields will differ
     *        accross implementations
     * @return mixed field's value, null when field is not available
     *         or user is not logged-in
     */
    public function get($field);


    /**
     * Authenticate against given credentials
     * @param array $auth_data authentication credentials
     * @return bool whether authentication was successfull
     */
    public function login(array $auth_data);

    /**
     * Get last operation's error
     * @return string error identifier (not user-friendly)
     */
    public function getLastError();

    /**
     * De-authenticate logged-in user
     * @return void
     */
    public function logout();

    /**
     * Check whether configuration grands access to requested action
     * @param string|IController|array $ctrl controller url or object,
     *        or array with [url] key and optionally others
     * @param string $action action name
     * @param null|string|array|Object $target target object actions it to
     *        be performed on. Object identifier, array with it's data;
     *        behaviour depends on implementation
     * @param null|string|array|Object $user user that tries to perform the
     *        action. Identifier, array with it's data or object;
     *        null for currently logged-in user
     * @return bool will return false if unsure (launching Component's
     *         callbacks is recommented
     */
    public function hasAccess($ctrl, $action=null, $target=null, $user=null);

}



/**
 * Implementations of ILang should conform BCF47
 * @url http://en.wikipedia.org/wiki/IETF_language_tag
 */
interface ILang
{
    /**
     * Gets currently set user's language
     * @return string language code
     */
    public function get();

    /**
     * Stores user's languages choice
     * @return bool|string false if language is invalid,
     *         previous value otherwise
     */
    public function set($lang);

    /**
     * Gets all available languages or checks if given is valid.
     * @param null|string $lang language code to check or null to get all
     * @return boolean|array returns array of available languages when none given
     */
    public function available($lang=null);

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
    public function info($lang=null, $attr=null);

    /**
     * Detects user's preferred language (browser's headers, IP..)
     */
    public function detect();
}


/**
* Interfejs widoku.
* Zadaniem widoku jest wygenerowanie dokumentu w odpowiedzi na zapytanie.
* Widok powinien zapewniac poszczegolnym komponentom systemu (kontrolerom) bezkonfliktowe
* wspoldzielenie zasobow definiowanych przez wybrane medium. W szczegolnosci, w
* przypadku strony xhtml zasobami mozna nazwac:
*  - naglowek
*    - podpiecie styli CSS z plikow zewnetrznych
*    - deklaracje styli inline'owych
*    - analogicznie skrypty js,
*    - meta tagi (glownie keywords i description)
*    - tytul (zasob? ustawiajacy najczesciej ma na to wylacznosc! trudno generycznie wspoldzielic)
*  - body
* W celu realizacji tego zadania widok ma kilka funkcji umozliwiajacych dodawanie poszczegolnych deklaracji i
* ogolnie - zarzadzanie ich zbiorem (np. dodawanie (i kasowanie?) plikow css i sprawdzanie, czy jakis
* css juz zostal podpiety).
* Widokowi odpowiada najbardziej podstawowy szablon strony, zawierajacy doctype, naglowek i puste body.
* W przypadku widoku fragmenty html moga byc zaszyte w kodzie i nie powinno to byc traktowane jako
* zlamanie zasad programowania metoda MVC.
*/
interface IView
{
    /**
    * Dodaje znacznik link.
    * @param def jest w postaci array ( $ident => $definition ), gdzie $definition jest tablica
    *        asocjacyjna z kluczami (opcjonalnymi)
                title, href, type, media, rel, rev, hreflang.
    */
    public function addLink($name,$def);

    /**
    * Dodaje link do pliku css. Efektem przypomina szczegolne wywolanie addLink.
    */
    public function addCss($file, $media='all');

    /**
    * Dodaje css wbudowany w html.
    * @param array( $key => $definition), gdzie $key jest selectorem CSS.
    */
    public function addInlineCss($css_code);

    /**
    * Dodaje zewnetrzny skrypt.
    */
    public function addJs($file);

    /**
    * Dodaje skrypt wbudowany w strone.
    */
    public function addInlineJs($js_code);

    /**
    * Dodaje instrukcje wywolywane po zaladowaniu DOM'u
    */
    public function addOnLoad($js_code);

    public function addKeyword($word);
    public function setDescription($desc);
    public function setTitle($title);
    public function getTitle();

    public function getMeta($tag);
    public function setMeta($name_or_attrs, $value=null, $meta_name = 'name');

    /**
     * Registers HTTP header.
     *
     * If $value is specified, then apart from sending "$header: $value" value,
     * adequate <meta http-equiv="".. will be added to <head />.
     * If $value is ommited, only "$header" header will be sent.
     * @author m.augustynowicz
     *
     * @param string $header header text
     * @param null|string $value header value, e.g. text/html; charset=utf-16
     * @return void
     */
    public function addHeader($header, $value=null);

    /**
     * Add code between <head></head> signs
     *
     * @param string $code - code to palce in head
     * @return void
     */
    public function addInHead($code);

    /**
     * Sets character encoding.
     * @uses addHeader()
     *
     * @param string $enc charset
     * @return void
     */
    public function setEncoding($enc=NULL);

    /**
     * Sets page language.
     *
     * @param string $lang Language code conforming BCF47
     *        {@url http://en.wikipedia.org/wiki/IETF_language_tag}
     * @return void
     */
    public function setLang($lang=null);

    /**
     * Adds HTML head's profile.
     * Used mainly for microformats' XMDPs.
     *
     * @param string $uri URI to profile document
     */
    public function addProfile($uri);

    /**
    * Prezentuje strone. Wysyla do przegladarki wszystkie informacje potrzebne do wyswietlenia
    * albo zaktualizwoania strony.
    */
    public function present();

    /**
    * Zalacza podkontroler. Ta metoda powinna byc uzywana przez kontrolery do wstawiania
    * w okreslonym miejscu szablonu zawartosci podkontrolera.
    */
    public function inc($controller);

}


/**
 * Template engine interface
 */
interface ITemplates
{
    public function inc($that, $file, &$variables=NULL);
    public function display($tpl, $tpl_is_string=false);
    public function fetch($tpl, $tpl_is_string=false);
    public function assign($a, $b=NULL);
    public function assignByRef(&$a);
}


////////////////////////////////////////////////////////////////////////
////////////////////////////core////////////////////////////////////////
////////////////////////////////////////////////////////////////////////

class NonConnectedDb
{
    private $__connection_params;
    private $__true_db;

    public function __construct($params)
    {
        list($connection_params) = $params;
        $this->__connection_params = $connection_params;
        $this->__true_db = NULL;
    }

    public function __call($name,$args)
    {
        if ('config'===$name)
            return;
        if (NULL !== $this->__true_db)
            throw new HgException("Do not store your own references to the DB.");

        global $kernel;
        $allowed_params = array('host'=>'','hostaddr'=>'',
                                'port'=>'','dbname'=>'',
                                'user'=>'','password'=>'' );
        $connection_params = array_intersect_key($this->__connection_params,
                                                 $allowed_params );
        foreach ($connection_params as $k=>&$v)
            $v = "$k='$v'";
        $connection_string = implode(' ', $connection_params);
        $db_resource = pg_pconnect($connection_string);
        if(!$db_resource)
            die("No DB connection.");
        $this->__true_db = $kernel->db = new DataBase($db_resource);
        $kernel->db->execute("SET CLIENT_ENCODING TO {$kernel->conf['db']['set_names']}");

        if (!method_exists($kernel->db, $name))
            throw new HgException("Method DataBase::$name() does not exist.");
        return call_user_func_array(array($kernel->db,$name), $args);
    }
}

/**
* Frontend bazy danych z leniwym laczeniem - dopiero wykonanie jakiejs operacji
* na bazie powoduje polaczenie z nia.
*
* @todo Moze nalezy zrobic do tego INTERFEJS!!
*/
class DataBase
{
    private $__db;
	private $__last_res;
    private $__trans_counter;
    private $__trans_failed = false;

    private $__debug = 0;
    const debug_css = 'font-size:11px; background-color: white; color: black; border: #888 solid; border-width: 1px 0; margin: .5ex 1em;';

    /**
     * @var int time, when query was launched, in microtime
     */
    protected $_query_start = null;

    public function __construct($db_resource)
    {
        $this->__db = $db_resource;
    }

    /**
     * @todo check if pg_query succeeded
     */
    public function startTrans()
    {
        $this->_printInDebugPre(__FUNCTION__, array());
        if ($this->__trans_counter<=0)
            pg_query('BEGIN;');
        $this->__trans_counter++;
        $this->_printInDebugPost(__FUNCTION__, array());

        return $this;
   }

    public function failTrans()
    {
        $this->_printInDebugPre(__FUNCTION__, array());
        $this->__trans_failed = true;
        $this->_printInDebugPost(__FUNCTION__, array());

        return $this;
    }

    /**
     * @todo check if pg_query succeeded
     * @todo how to deal with negative trans_counter?
     */
    public function completeTrans()
    {
        $this->_printInDebugPre(__FUNCTION__, array());
        if ($this->__trans_counter == 0)
            trigger_error('Used completeTrans() outside transaction!', E_USER_WARNING);
        if ($this->__trans_counter>0)
            $this->__trans_counter--;
        if ($this->__trans_failed)
        {
            pg_query('ROLLBACK;');
            $this->__trans_failed = false;
        }
        else
        {
            if ($this->__trans_counter<=0)
                pg_query('COMMIT;');
        }
        $this->_printInDebugPost(__FUNCTION__, array());

        return $this;
    }

    public function getOne($query, $row=0, $field=0)
    {
        $argv = func_get_args();
        $this->_printInDebugPre(__FUNCTION__, $argv);
        $result = false;
        if ($resource = $this->execute($query)) // sql error?
            $result = pg_fetch_result($resource, $row, $field);
        $this->_printInDebugPost(__FUNCTION__, $argv, $result);
        return $result;
    }

    public function getAll($query)
    {
        $argv = func_get_args();
        $this->_printInDebugPre(__FUNCTION__, $argv);
        $result = false;
        if ($resource = $this->execute($query)) // sql error?
            $result = pg_fetch_all($resource);
        $this->_printInDebugPost(__FUNCTION__, $argv, $result);
        return $result;
    }

    public function getRow($query, $number=0)
    {
        $argv = func_get_args();
        $this->_printInDebugPre(__FUNCTION__, $argv);
        $result = false;
        if ($resource = $this->execute($query)) // sql error?
            $result = pg_fetch_array($resource);
        $this->_printInDebugPost(__FUNCTION__, $argv, $result);
        return $result;
    }

    public function getCol($query, $number=0)
    {
        $argv = func_get_args();
        $this->_printInDebugPre(__FUNCTION__, $argv);
        $result = false;
        if ($resource = $this->execute($query)) // sql error?
            $result = pg_fetch_all_columns($resource, $number);
        $this->_printInDebugPost(__FUNCTION__, $argv, $result);
        return $result;
    }

    public function execute($query)
    {
        if ($print_in_debug = ('get' != substr(g('Functions')->getCaller('function'),0,3)))
        {
            $argv = func_get_args();
            $this->_printInDebugPre(__FUNCTION__, $argv);
        }
        $result = pg_query($this->__db, $query);
        if ($print_in_debug)
            $this->_printInDebugPost(__FUNCTION__, $argv, $result);
        return $result;
    }

    public function lastErrorMsg()
    {
        return pg_last_error();
    }

    public function debugOn($i=1)
    {
        if (!g()->conf['allow_debug'])
            return;
        $this->__debug += $i;
        if ($this->__debug)
            g()->debug->set(true);

        return $this;
    }

    public function debugOff($i=1)
    {
        if (!g()->conf['allow_debug'])
            return;
        $this->__debug -= $i;
        if ($this->__debug<0)
            $this->__debug = 0;
        if (!$this->__debug)
            g()->debug->set(false);

        return $this;
    }

    protected function _printInDebugPre($func, $args)
    {
        if (g()->debug->allowed() && g()->isRendering())
        {
            trigger_error('Querying database while rendering? You should be ashamed!', E_USER_WARNING);
        }
        if (!g()->debug->on())
        {
            if ((!g()->debug->allowed()) || (!$this->__debug))
                return;
        }

        printf('<div style="%s">', self::debug_css);
        print '<div style="margin: 0 2em;">';
        $this->_query_start = microtime(true);
    }

    protected function _printInDebugPost($func, $args, $result=null)
    {
        if (!g()->debug->on())
        {
            if ((!g()->debug->allowed()) || (!$this->__debug))
                return;
        }

        $query_time = microtime(true) - $this->_query_start;
        $this->_query_start = null;

        echo '</div>';
        $query = @$args[0];
        printf("<small style=\"color:gray\">(%s)</small>\n<code style=\"font-family: monospace\">\n%s</code>", $func, htmlspecialchars($query));

        static $counter = 0;

        $with_result = func_num_args()>2;

        if ($with_result)
        {
            if (is_array($result))
                $result_desc = ($s=sizeof($result)) . (($s>1) ? ' rows' : ' row');
            elseif (g('Functions')->isInt($result))
                $result_desc = "(int) $result";
            elseif (is_bool($result))
                $result_desc = $result ? 'true' : 'false';
            else
                $result_desc = 'something';

            static $js_printed = false;
            if (!$js_printed)
            {
                $js_printed = true;
                echo <<< JS
                    <script language="javascript">
                    // <![CDATA[
                    if (typeof hgDebugDbToggle == 'undefined')
                    function hgDebugDbToggle(t,tag,i,css,val1,val2)
                    {
                        if (typeof css  == 'undefined') css  = 'display';
                        if (typeof val1 == 'undefined') val1 = 'none';
                        if (typeof val2 == 'undefined') val2 = 'block';
                        o = t.parentNode.parentNode.getElementsByTagName(tag)[i];
                        o.style[css] = (o.style[css]==val1?val2:val1);
                        return false;
                    }
                    // ]]>
                    </script>
JS;
            }
            printf('<div>query took %fms and returned %s: <a href="#dbdebug%d" onclick="return hgDebugDbToggle(this,\'pre\',0)">what?</a>',
                    $query_time,
                    $result_desc,
                    ++$counter
                );
            printf(', <a href="#dbdebug%d" onclick="return hgDebugDbToggle(this,\'div\',2)">when?</a>', $counter);
            printf(', <a href="#dbdebug%d" onclick="return hgDebugDbToggle(this, \'code\', 0, \'whiteSpace\', \'pre\', \'normal\')">expand white spaces</a>', $counter);
            print '<pre style="display:none">';
            g()->debug->dump($result);
            if (is_resource($result))
            {
                echo pg_result_error($result);
            }
            print "</pre>";
            print '<div style="display:none">';
            g()->debug->trace(1, null);
            print "</div>";
            print "</div>";
            if ($msg = $this->lastErrorMsg())
            {
                do
                {
                    if (preg_match('/relation "(.*)" does not exist/', $msg, $match))
                    {
                        for ($i=0; $caller = g('Functions')->getCaller($i,'object') ; $i++)
                        {
                            // is_a() will stop throwing E_STRICT when updated to php-5.3
                            if (is_a($caller, 'DataSetController'))
                                break(2);
                        }
                        $rel = $match[1];
                        g()->debug->addInfo('non-existment relation '.$rel,
                                "Relation `$rel' does not exist or invalid. "
                                . "Use DataSet controller to fix it." );
                    }
                }
                while ('false' == false);
                printf('<p><strong>Error ocurred:</strong> %s</p>', $msg);
            }
        }
        print '</div>';
    }

}

/**
* zadania kernela:
* - obsluga polaczenia z baza
* - przydzielenie sesji komponentom, sesje przydzielane do obiektow
* - ladowanie klas
* - przeprowadzenie cyklu requestu :
*    -parsowanie urla
*    -wywolanie akcji
*    -renderowanie widokow
* - zarzadzanie widokiem
*/
class Kernel
{
    /**
     * @var int time Kernel was constructed, in microseconds
     */
    protected $_start_time = null;

    private $__instances;
    protected $_session; //
    protected $_log = null;
    public $db;
    public $conf = array();
    public $auth;
    public $view;
    public $lang;
    public $debug;
    public $first_controller;
    public $req;
    public $prerender_echo;
    public $infos = array();
    public $session;

    /**
     * Has processing action ended, and rendering began?
     * For internal use only, I guess.
     * @author m.augustynowicz
     * @var boolean
     */
    protected $_rendering = false;

    /**
    */
    public function __construct()
    {
        $this->_start_time = microtime(true);
        ob_start();

        if(!empty($_POST['PHPSESSID']))
            session_id($_POST['PHPSESSID']);

        session_start();

        $this->readConfigFiles();
        if (empty($this->conf))
            die('System Error #001');

        $this->_session = &$_SESSION[$this->conf['SID']]['KERNEL'];
        $this->session = &$_SESSION[$this->conf['SID']]['GLOBAL'];
        $this->infos = &$this->_session['infos'];

        $this->db = $this->get('NonConnectedDb', array($this->conf['db']));

        $this->lang = $this->get('Lang', array('conf' => $this->conf['locale']));
    }

    /**
    * Przeprowadza cykl requestu.
    * - Parsuje url
    * - Tworzy widok wynikajacy z rodzaju requestu    ? moze potem?
    * - Tworzy pierwszy kontroler
    * - Wywoluje jego metode process($req) (byc mozer wielokrotnie, jezeli dopuszczamy wywolanie kilku akcji na raz.
    * - Inicjuje renderowanie strony
    *
    * @return mixed whatever first controller returns.
    */
    public function run()
    {
        try
        {
            $this->debug = $this->get('Debug');
            $this->req = $this->get('Request');
            $this->auth = $this->get('Auth');

            // used in log() method
            $this->_log = $this->get('Log', 'model');

            $this->lang->available(); // fill the cache

            $this->_getTranslations($this->lang->get());

            $this->first_controller = $this->get(
                $this->conf['first_controller']['type'],'controller',
                array(
                    'name'=>$this->conf['first_controller']['name'],
                    'parent'=>null
                )
            );
            define('BASE', $this->req->getBaseUri());
            $this->first_controller->process($this->req);
            $this->_rendering = true;
            if (!$this->view) // could have been set in progress
            {
                switch (true)
                {
                    case $this->req->isAjax() :
                        $this->view = $this->get('AjaxView');
                        break;
                    case $this->req->isCli() :
                        $this->view = $this->get('TextView');
                        break;
                    default :
                        $this->view = $this->get('View');
                }
            }
            register_shutdown_function(array($this, 'possiblyDisplayPrerendererEcho'));
            $this->prerender_echo = ob_get_clean();
            $this->view->present();

            return $this->first_controller->getLaunchedActionReturn();
        }
        catch(Exception $e)
        {
            while (ob_get_level())
                ob_end_flush();
            echo "<h1>Unhandled exception</h1>";
            if ($this->conf['allow_debug'])
            {
                echo "<pre>$e</pre>";
            }
        }
    }


    /**
    * Laduje plik php. Jezeli plik jest w katalogu wdrozenia, to ma priorytet przed plikiem z biblioteki Hologramu.
    * Obsluga classes override.
    * @param assert When true, the function works in assert mode - it stops the script execution on fail.
    * @return mixed If the call succeedes, the name of the actually loaded class is returned, if not - the function
    *         either throws an error (assert=true), or returns boolean false (assert=false)
    */
    public function load($name, $type='class', $assert=true)
    {
        $type = (string) $type; // so you can use null
        switch ($type)
        {
        case 'class' :
            $class = $name;
            $file = 'class.'.basename($name);
            break;
        default :
            $file = $class = basename($name).ucfirst($type);
        }
        $dirname = dirname($name).'/';

        if (isset($this->conf['classes_override'][$class]))
            $overloading_class = $this->conf['classes_override'][$class];
        else
            $overloading_class = NULL;

        if (class_exists($class, false))
        {
            if ($overloading_class)
                return $this->load($overloading_class, $type);
            else
                return $class;
        }

        $fn = '';
        foreach ($GLOBALS['DIRS'] as $dir => $alias)
        {
            if (is_readable($potencial_fn = sprintf ('%slib/%s%s.php', $dir, $dirname, $file)))
            {
                $fn = $potencial_fn;
                break;
            }
        }

        if (!$fn)
        {
            if (!$assert)
                return(FALSE);
            $errmsg = "Reference to a non-existing class `$name', which seems to be a `$type'.";
            if ($overloading_class)
                $errmsg .=  " Oh, and it is overloaded by `$overloading_class'.";
            if (class_exists('HgException'))
                throw new HgException($errmsg);
            die('System Error #005');
        }

        require_once $fn;
        if ($overloading_class)
           $class = $this->load($overloading_class, $type);

        return $class;
    }


    /**
     * Objects factory
     *
     * If class has static variable 'singleton' set to 'true'.
     *
     * @param string $name name of class to load, if no given -- kernel ($this)
     * @param string $resource_type type of class ('class','model','controller' etc)
     * @param array $args arguments to be passed to classes' constructor / config()
     *
     * @return instance of requested class
     */
    public function get($name=null,$resource_type='class',$args=array())
    {
        $argc = func_num_args();

        if ($argc == 0)
            return $this;
        else if (empty($name))
        {
            trigger_error(
                'Tried to manufacture empty-named with '
                        .__CLASS__.'::'.__FUNCTION__,
                E_USER_ERROR
            );
        }

        if (is_array($resource_type))
        {
            $args = $resource_type;
            $resource_type = 'class';
            $argc++;
        }
        else
            $resource_type = strtolower($resource_type);

        $present = $this->load($name,$resource_type);
        $present_vars = get_class_vars($present);
        $singleton = @$present_vars['singleton'];

        if (!$present)
            throw new HgException('Load of '.$present.' failed somehow.');

        if (isset($this->__instances[$present]))
            $object = $this->__instances[$present]->config($args);
        else
        {
            if ($argc>2)
                $object = new $present($args);
            else
                $object = new $present();
            if ($singleton)
                $this->__instances[$present] = $object;
        }

        // check if created object implements correct interface
        switch ($resource_type)
        {
            case 'model' :
                if (!$object instanceof IModel)
                    throw new HgException($present.' does not implement IModel like it shoud.');
                break;
            case 'me' : // model extension
                if (!$object instanceof ModelExtension)
                    throw new HgException($present.' does not extend ModelExtension like it shoud.');
                break;
            case 'controller' :
                if (!$object instanceof IController)
                    throw new HgException($present.' does not implement IController like it shoud.');
                break;
        }

        return $object;
    }


    /**
    * Dodaje informacje do wyświetlenia użytkownikowi.
    * @author m.augustynowicz
    *
    * @param string $ident identyfikator komunikatu. dodanie komunikatu o
    *        idencie już istnijącym, powoduje jego nadpisanie
    * @param string $class klasa komunikatu. właściwie domślna, ale należy
    *        używać jednej z: error, info
    * @param string $message treść wiadomości. ten i kolejne parametry
    *        traktowane są tak jak parametry printf-a
    * @return void
    */
    public function addInfo($ident=NULL, $class, $message)
    {
        $args = func_get_args();
        $ident = array_shift($args);
        $class = array_shift($args);
        $message = array_shift($args);
        $message = vsprintf($message, $args);
        if ($ident)
            $this->infos[$class][$ident] = $message;
        else
            $this->infos[$class][] = $message;
        if (g()->debug->allowed())
        {
            printf('<div class="debug"><em>just added <strong>info</strong>[%s][%s]:</em> %s</div>',
                    $class, $ident?$ident:'', $message );
        }
    }


    /**
     * Log an event
     * @author m.augustynowicz
     *
     * @see LogModel::log() this is a wrapper to that method
     *
     * @param string $level {@see conf[enum][log_level]}
     * @param Controller|null $that controller event happened in
     * @param mixed $id id of object event regards
     * @param string $title title of object, or event if ($id===null)
     * @param array $values when no $values2 given, set of event properties,
     *        when $values2 given -- set of old properties
     * @param array $new_values set of new properties
     * @param int $user_id user id to use (when none supplied, signed-in
     *        user's id will be used)
     *
     * @return void
     */
    public function log($level, Controller $that, $title=null, $id=null,
                        array $values=null, array $new_values=null, $user_id=false )
    {
        $this->_log->log($level, $that, $title, $id, $values, $new_values, $user_id);
    }

    
    /**
    * Zwraca zawartosc bufora z rzeczami wyslanymi do przegladarki w trakcie przetwarzania
    * akcji. Najczesciej znajdowac sie tam beda debugi.
    * @return Zwraca zawartosc bufora z rzeczami wyslanymi przed nadaniem naglowkow strony.
    */
    public function getPrerenderEcho()
    {
        return $this->prerender_echo;
    }

    /**
     * Displays prerenderer echo.
     * Should be registered as script shutdown function.
     * @author m.augustynowicz
     *
     * @return void
     */
    public function possiblyDisplayPrerendererEcho()
    {
        if ($this->conf['allow_debug'])
        {
            if ($display_me = $this->getPrerenderEcho())
                printf("<pre><strong>prerender echoes (fallback display)</strong>\n%s</pre>", $display_me);
        }
    }

    /**
     * Reads configuration files (*conf*php) from config directories.
     *
     * Warning: uses include_once (once!)
     * @author m.augustynowicz
     *
     * @param string $prefix optionally add this before file names
     * @param string $glob globe used to match files
     * @return void
     */
    public function readConfigFiles($prefix = '', $glob='conf*')
    {
        // read from these directories
        $configDirs = array();
        foreach($GLOBALS['DIRS'] as $dir => $alias)
            $configDirs[] = $dir.'conf/';
        $configDirs = array_reverse($configDirs);
        $configDirs[] = LOCAL_DIR.'conf/';

        if ($prefix)
            $prefix = trim($prefix,'/') . '/';

        $this->load('Functions');

        foreach ($configDirs as $configDir)
        {
            $confDir = rtrim($configDir,'/').'/';
            $patterns = array($confDir.$prefix.$glob.'.php');

            $confAll = array();
            foreach ($patterns as $pattern)
            {
                foreach (glob($pattern) as $configFile)
                {
                    if (!is_file($configFile))
                        continue;
                    unset($conf);
                    include $configFile;
                    if (@is_array($conf))
                    {
                        Functions::arrayMergeRecursive(
                                $confAll, $conf, false );
                    }
                }
            }

            Functions::arrayMergeRecursive($this->conf, $confAll, false);
        }
    }


    /**
     * Fetch translations from database and merge them into config
     * @author m.augustynowicz
     *
     * @param string $lang language code
     */
    protected function _getTranslations($lang)
    {
        // get from config files

        $this->readConfigFiles('lang/'.$lang);


        // get from database
        if ($this->conf['use_db_trans'])
        {
            $f = g('Functions');
            $trans = & $this->conf['translations'];

            $db_trans = g('Trans', 'model')
                    ->filter(array(
                        'lang' => $lang
                    ))
                    ->exec();

            foreach ($db_trans as &$row)
            {
                if ($f->anyToBool($row['value_is_complex']))
                {
                    $row['value'] = json_decode($row['value'], true);
                }

                $trans[$row['context']][$row['key']] = $row['value'];
            }
        }
    }


    /**
     * Getter for {@see $_rendering}
     * @author m.augustynowicz
     */
    public function isRendering()
    {
        return $this->_rendering;
    }

    public function redirect($url, $with_base_uri=true)
    {
        if (!preg_match('!^[a-z]+://!', $url))
            // add host part if $url starts with a slash
            if($with_base_uri)
                $url = g()->req->getBaseUri(false).ltrim($url,'/');
        if ($this->conf['allow_debug'] && $_SESSION[g()->conf['SID']]['debug']['allow_debug'])
        {
            $this->debug->trace('redirect happens.');
            //printf("I'd like to redirect to %s, may I? <strong style=\"font-size: 2em\"><a href=\"%s\">yes</a></strong>, <a href=\"#\" onclick=\"alert('tought!');window.location.href='%s';return false;\">no</a>\n", $url, $url, $url);
            printf('<div style="position:fixed;bottom:0;right:0;left:0;text-align:center;padding:.5em;font-size:1.2em;background-color:white;color:black;"><a id="redirect-link" href="%s" style="display:block;width:100%%;"><big>redirecting to <code>%s</code></big></a></div>',
                   $url, $url );
            echo '<style type="text/css">body { padding-bottom: 3em; }</style>';
            echo '<script type="text/javascript">document.getElementById("redirect-link").focus();</script>';
        }
        else
            header('Location: '.$url, true, 301);
        exit;
    }


    /**
     * Get time elapsed from Kernel construction
     * @author m.augustynowicz
     *
     * @return int elapsed time, in microseconds
     */
    public function getTime()
    {
        return microtime(true) - $this->_start_time;
    }

}


/**
* Klasa bazowa wszystkich klas Hologramu (za wyjatkiem Kernel). Zapewnia ladowanie i pobieranie instancji
* innych klas za posrednictwem kernela w notacji $this->NazwaKlasy, albo $this->NazwaKlasy($args).
* Domyslnie, wszystkie klasy dziedziczace sa singletonami. Zadekladowanie w ktorejs klasie pochodnej
* statycznej zmiennej $singleton = false powoduje, ze klasa przestaje byc singletonem. Wtedy kazde odwolanie
* do metody get() zwraca nowa instancje.
*/
class HgBase
{
    static $singleton=true;

    protected $_trans_sources = array();

    public function __construct(array $params = array())
    {
        if (!in_array(get_class($this), $this->_trans_sources))
            array_unshift($this->_trans_sources, get_class($this));
        $this->_trans_sources[] = '_global';
    }

    /**
     * @todo remove this.
     * @fixme ?
     */
    public function __clone()
    {
        $present_vars = get_class_vars(get_class($this));
        $singleton = @$present_vars['singleton'];
        if ($singleton)
            throw new HgException("Shit, I'm being cloned!");
    }

    public function config()
    {
        //$args = func_get_args();
        return($this);
    }

    /**
     * sets lang to be used in translations
     * @author b.matuszewski
     */
    public function setTransLang($lang)
    {
        if(g()->lang->available($lang))
        {
            unset(g()->conf['translations']);
            g()->readConfigFiles('lang/'.$lang);
        }
    }

    /**
     * Translating strings.
     *
     * If first argument is array -- it will call itself for each element.
     * @author m.augustynowicz
     * @param string text to be translated
     * @param string rest of params taken in sprintf-like manner
     *
     * @return string translated string. or original if translation was not found
     */
    public function trans($text)
    {
        $argv = func_get_args();
        $text = array_shift($argv);


        // handle array of texts to translate

        if (is_array($text))
        {
            foreach ($text as &$txt)
            {
                $new_argv = array_merge(array(&$txt), $argv);
                $txt = call_user_func_array(array($this,__FUNCTION__),$new_argv);
            }
            return $text;
        }
        else
        {
            $text = (string) $text;
        }


        // get translations from config files

        foreach ($this->_trans_sources as $source)
        {
            $trans = & g()->conf['translations'][$source][$text];
            if (null === $trans) // not found
                continue;
            if (!is_array($trans)) // regular translation
                $msg = $trans;
            else // different translation for different arguments
            {
                $argv_key = $argv;
                do
                {
                    if (1 == sizeof($argv_key))
                        list($key) = $argv_key;
                    else
                        $key = join("\t", $argv_key);
                    $key = (string) $key;
                    if (isset($trans[$key]))
                    {
                        $msg = $trans[$key];
                        break;
                    }
                }
                while (null !== array_pop($argv_key)); // last key is ""
            }
            break;
        }


        if (!isset($msg))
        {
            $ret = vsprintf($text, $argv);
            $trans = array(); // "no trans"
        }
        else
        {
            $ret = vsprintf($msg, $argv);
            $trans = & $ret;
        }


        // do debug things

        $debug_all = g()->debug->on('trans');
        $debug_missing = g()->debug->on('trans','missing');

        if ($debug_all || (array() === $trans && $debug_missing))
        {
            $ret = sprintf('((%s ~ %s))',
                    vsprintf($text, $argv),
                    array() === $trans ? 'NO TRANS!' : $trans
                );
        }


        return $ret;

    }
}



////////////////////////////////////////////////////////////////////////
///////////////////////////kontrolery///////////////////////////////////
////////////////////////////////////////////////////////////////////////

/**
* Event sygnalizowany, gdy akcja jest przekazywana do innego kontrolera.
* Jezeli w klasie jest metoda onActionRouted2[$nazwa], gdzie $nazwa jest
* rowna
*/
abstract class Controller extends HgBase implements IController
{
    private $__parent;
    private $__name;
    private $__variables = array(); //!< do templejta
    protected $_session;
    protected $_conf;

    static $debug_inc_tree = array();
    static $debug_inc_level = 0;

    protected $_default_action = 'default';

    /**
     * @var Component component that should handle rendering.
     *      should be one of $__components
     */
    protected $_subrenderer = null;

    protected $_template = '';
    protected $_action_to_launch = null;
    protected $_launched_action = null;

    /**
     * @var mixed whatever this controller's action returns
     */
    protected $_launched_action_return = null;

    protected $_params = null;

    /**
    * Tworzy kontroler. Do $this->_session przypisuje referencje do klucza w $_SESSION.
    * Do rzeczy znajdujacych sie pod tym kluczem domyslnie ma dostep tylko jego wlasciciel.
    */
    public function __construct($args=array())
    {
        $parent_class = get_class($this);
        for ( ; $parent_class && $parent_class != 'Controller' ; $parent_class = get_parent_class($parent_class))
        {
            if (preg_match('/(.+)(?:Controller|Component|Model|View|ME)$/',
                           $parent_class, $matches ))
            {
                $this->_trans_sources[] = $matches[1];
            }
            $this->_trans_sources[] = $parent_class;
        }
        parent::__construct();

        extract($args);

        if(!isset($name))
        {
            throw new HgException("Bad args in ".get_class($this)." constructor.");
        }

        if (strpos('/',$name) !== false)
        {
            throw new HgException("You cannot create subcontrollers! Bad args in ".get_class($this)." constructor.");
        }

        // controller names have upper caseed first letter
        $name = ucfirst($name);

        if ($parent)
        {
            if (!$parent instanceof IController)
                throw new HgException("An non controller given as parent.");
            if ($parent->getChild($name))
                throw new HgException("Cannot use subcontroller name '$name' again.");
        }
        $this->__name = $name;
        $this->__parent = $parent;

        $this->_session = &$_SESSION[g()->conf['SID']][$this->path()];
        $this->_conf = &g()->conf['controllers'][$this->path()];

        array_unshift($this->_trans_sources, strtolower($name));
    }

    protected function _onAction($action, array &$params)
    {
        if (g()->debug->allowed() && g()->isRendering())
        {
            trigger_error('SIC! Launching action from a template?', E_USER_WARNING);
        }

        $has_access = $this->hasAccess($action, $params, false);
        if (g()->debug->allowed())
        {
            printf('<p class="debug">Permission to <em>%s::%s<small>(%s)</small></em> %s by <em>%s</em></p>',
                $this->path(), $action, print_r($params, true),
                $has_access ? 'granted' : 'denied',
                is_int($has_access) ? 'configuration' : 'callback'
            );
        }
        if (!$has_access)
        {
            if(!empty($params['backto']))
            {
                if (g()->debug->allowed())
                {
                    printf('<p class="debug"><em>$params[\'backto\']</em> found (%s)<br /> redirecting to the given page</p>',
                        $params['backto']
                    );
                }
                $this->redirect($params['backto']);
            }
            $this->redirect(); // main page
        }

        return true;
    }


    /**
     * Checking permissions.
     *
     * Uses Kernel's Auth and $this->hasAccessTo{ActionName}() callbacks
     * @author m.augustynowicz
     *
     * @param string $action name
     * @param array $params what will be passed to the action
     * @param boolean $just_checking pass false if you inteded to launch
     *        that action (it may cause displaying some "error denied"
     *        user messages and such)
     * @return boolean/int does logged-in user has access to this action?
     *         true when access granted by callback,
     *         1 when from config,
     *         false when access denied by callback
     *         0 when from config
     */
    public function hasAccess($action, array &$params = array(), $just_checking=true)
    {
        static $cache = array();

        $cache_key = json_encode($action)
                   . json_encode($params);
        $ret = & $cache[$cache_key];
        if (null !== $ret)
            return $ret;

        if (g()->auth->hasAccess($this, $action, $params))
        {
            $ret = 1;
        }
        else
        {
            // or maybe we have any callback for checking this?
            if($this->_default_action === $action)
                $callback = 'hasAccessToDefault';
            else
                $callback = 'hasAccessTo' . ucfirst($action);

            if (!method_exists($this, $callback))
                $ret = 0;
            else if ($this->$callback($params, $just_checking))
                $ret = true;
            else
                $ret = false;
        }

        return $ret;
    }


    /**
     * @todo DEPRECATED (defaultAction) marked for deletion 2010-11-03
     */
    public function defaultAction(array $params)
    {
        $this->redirect(array('HttpErrors','error404'));
    }

    public function assign($a, $value=null)
    {
        if (is_array($a))
            $this->__variables = array_merge($this->__variables, $a);
        else
            $this->__variables[$a] = $value;
    }

    public function assignByRef($a, &$v)
    {
        $this->__variables[$a] = & $v;
    }

    public function append($a, $v, $k=null)
    {
        $a = & $this->__variables[$a];
        if (isset($a) && !is_array($a))
            $a = array($a);
        if (null === $k)
            $a[] = $v;
        else
            $a[$k] = $v;
    }


    /**
     * Get assigned value
     * @author m.augustynowicz
     *
     * @param string|null $varname variable name,
     *        if null specified, gets array with all variables
     *
     * @return mixed
     */
    public function getAssigned($varname = null)
    {
        if ($varname === null)
        {
            return $this->__variables;
        }
        else if (array_key_exists($varname, $this->__variables))
        {
            return $this->__variables[$varname];
        }
        else
        {
            return null;
        }
    }

    /**
     * @param array $____tpl
     * @param array $____local_variables locally assigned variables
     *        (visible only in included file's scope).
     *        warning: do not change the name, it's used in several templates
     */
    public function inc($____tpl, array $____local_variables=NULL)
    {
        if (is_object($____tpl))
        {
            if (in_array('IController',class_implements($____tpl, false)))
                return g()->view->inc($____tpl);
            else
                throw new HgException('Weird.. trying to render a non-controller..');
        }

        $____file = $this->file($____tpl,'tpl');
        if (!$____file)
        {
            $____missing_tpl = $____tpl;
            $____file = $this->file('missing','tpl');
        }

        if (g()->debug->on('view'))
        {
            /** @todo check if caller is Component::render() and print it in bold */
            self::$debug_inc_level++;
            self::$debug_inc_tree[] = array(
                'tpl' => $____tpl,
                'file' => $____file,
                'type' => 'controller',
                'path' => $this->path(),
                'class' => get_class($this),
                'level' => self::$debug_inc_level,
            );
            printf('<!-- %s: %s::inc(%s => %s) -->',
                   $this->path(), get_class($this),
                   $____tpl, $____file );
        }

        if ('.php' !== strrchr($____file,'.'))
        {
            throw new HgException('Non-PHP template extension.');
        }

        extract($this->__variables, EXTR_OVERWRITE);
        if ($____local_variables)
            extract($____local_variables, EXTR_OVERWRITE);
        extract($this->getChildren(), EXTR_PREFIX_ALL, 'ch_');
        $f = g('Functions');
        $t = & $this;
        $v = & g()->view;
        $ret = include($____file);

        if (g()->debug->on('view'))
        {
            self::$debug_inc_level--;
            printf('<!-- (end) %s: %s::inc(%s => %s) -->',
                   $this->path(), get_class($this),
                   $____tpl, $____file );
        }
        return $ret;
    }

    /**
     * Specifies URI to given file owned $this class.
     * First it checks if ClassBaseName/$file exists (base i.e. without suffix)
     * then it tries with it's parent etc.
     *
     * All files except templates ($type=='tpl') are looked for in htdocs
     * directories.
     *
     * @author m.augustynowicz
     *
     * @param string $file file name to get without extension (excluding $type=='img')
     * @param string $type file type, can be js|css|img|tpl
     * @param bool $assert fail, when file does not exist
     * @return false|string when $type=='tpl' returns absolute path to the template
     *         or false, when it doesn't exist and $assert=false; for other types -- returns URI
     *         to the file, and false if it does not exist (and raises NOTICE
     *         when debug is allowed).
     */
    public function file($file, $type, $assert=true)
    {
        $argv = func_get_args();
        $return_real_path = false;
        switch($type)
        {
            case 'js' :
            case 'css':
            case 'swf':
                $file .= '.'.$type;
                $base_bases = array('htdocs/'.$type.'/%s');
                break;
            case 'less':
                $file .= '.'.$type;
                $type = 'css';
                $base_bases = array('htdocs/'.$type.'/%s');
                break;
            case 'gfx':
                // filename suffix is not added here.
                $base_bases = array('htdocs/gfx/%s');
                break;
            case 'tpl':
                $base_bases = array('tpl/%s.php');
                $return_real_path = true;
                break;
            default:
                throw new HgException("Invalid resource type");
        }
        $base = g()->req->getBaseUri();
        $basefs = array();
        foreach ($base_bases as $b)
        {
            foreach ($GLOBALS['DIRS'] as $gdir => $galias)
            {
                $basefs[$gdir.$b] = $galias;
            }
        }

        $c = get_class($this);
        do
        {
            $n = substr($c, 0, -10);
            foreach ($basefs as $basef => $uri)
            {
                // just in case. if we are requesting some js scripts w/ params
                list($fn) = explode('?',sprintf($basef, $n.'/'.$file));
                if (file_exists($fn))
                {
                    if ($n)
                        $n .= '/';
                    $base .= $uri;
                    break(2);
                }
            }
            $c = get_parent_class($c);
        }
        while ($n || $c=null);

        // file not found
        if (null === $c)
        {
            if (false == $assert)
                return false;
            else
                trigger_error("File $file not found!", E_USER_NOTICE);
        }

        if ($return_real_path)
            return $fn;
        else
            return "$base$type/$n$file";
    }

    public function path()
    {
        $parent = $this->getParent();
        $name = $this->getName();
        if ($parent)
            return($parent->path()."/$name");
        return($name);
    }

    public function url()
    {
        $name = '';
        if ($parent = $this->getParent())
            $name = $parent->url();
        $name .= ($name?'/':'').$this->getName();
		return $name;
    }

    public function getParent()
    {
        return($this->__parent);
    }


    public function getName()
    {
        return($this->__name);
    }


    /**
     * Get name ment for navigation (highlighting menu item etc)
     *
     * When overwritten, it can be used to interpersonate different controller
     * @author m.augustynowicz
     *
     * @return string
     */
    public function getNavName()
    {
        return $this->getName();
    }


    public function redirect($url='',$with_controllers=true, $with_base_uri=true)
    {
        if (is_array($url))
            $url = call_user_func_array(array($this,'url2c'), $url);
        g()->redirect($url, $with_base_uri);
    }


    /**
     * Let one of children handle rendering
     *
     * @param string|Controller $ctrl whether name or object of controller
     *        that should be used to render the page. Have to be a valid child
     *        of $this
     * @return boolean false, when $ctrl is not a valid child
     */
    protected function _passRenderingTo($ctrl)
    {
        if ($ctrl instanceof Controller)
        {
            if (!$this->isChild($ctrl))
                return false;
            else
                $child = $ctrl;
        }
        else
        {
            $child = $this->getChild($ctrl);
        }

        if ($child)
        {
            $this->_subrenderer = $child;
            return true;
        }
        else
        {
            $this->_subrenderer = null;
            return false;
        }
    }


    /**
     * Sets template that will be used to render the component
     * @author m.augustynowicz
     * @param null|string $tpl template name
     * @return boolean false when template does not exist
     *         (it is set nevertheless, so we can display
     *         "template %s lost in action")
     */
    protected function _setTemplate($tpl)
    {
        if (null === $tpl)
        {
            $this->_template = $tpl;
            return true;
        }
        $tpl = explode('/', $tpl);
        end($tpl);
        $last = key($tpl);
        $tpl[$last] = strtolower($tpl[$last]);
        $tpl = implode('/', $tpl);
        $this->_template = $tpl;
        $this->_action = $tpl; // legacy
        return false !== $this->file($tpl,'tpl');
    }


    /**
     * Get return value of this controller's launched action
     * @author m.augustynowicz
     *
     * @return mixed
     */
    public function getLaunchedActionReturn()
    {
        return (int) $this->_launched_action_return;
    }


    /**
     * Launch default action
     * @author m.augustynowicz
     *
     * @param array $params
     * @return boolean false if action does not exist (not really possible)
     */
    public function launchDefaultAction(array &$params=array())
    {
        return $this->launchAction($this->_default_action, $params);
    }


    /**
     * Launch any action
     * @author m.augustynowicz
     *
     * @param null|string $action action to launch,
     *        null or empty string will launch default action
     * @param array $params
     * @return bool|int
     *         ===false if action does not exist;
     *         ==true if exists;
     *         ===1 if exists, but was not launched
     *              (_onAction() returned ==false)
     */
    public function launchAction($action, array &$params=array())
    {
        // null or empty string means default action
        if (!$action)
        {
            $action = $this->_default_action;
        }

        $method = $this->_getActionMethod($action);

        $ret = true;

        // launching second action in one Component?
        if (@$this->_launched_action && g()->debug->allowed())
        {
            g()->addInfo(null, 'debug',
                    'Launching <em>%s</em> in <em>%s</em> that have already launched <em>%s</em>!',
                    $action, $this->path(), $this->_launched_action );
        }

        $this->_launched_action = $action;
        $this->_params = $params;

        // open debug output block
        if (g()->debug->allowed())
        {
            $displaying_ctrl_class = $this->isDisplayingCtrl() ? 'is-displaying-ctrl' : '';
            printf('<div class="debug-output-block %s extends-%s launching action"><h3>%s, action %s(%s)%s</h3>',
                $displaying_ctrl_class, get_parent_class($this),
                $this->path(), $action, print_r($params, true),
                $action === $this->_default_action ? ' <small>(default)</small>' : ''
            );
        }

        if (false === $method)
        {
            echo '<p><strong>method does not exist</strong></p>';
            $ret = false;
        }
        else if (method_exists($this,"_onAction")
                 && false === $this->_onAction($action,$params) )
        {
            echo '<p><strong>not launching -- _onAction() returned false</strong></p>';
            $ret = 1;
        }
        else
        {
            $this->_launched_action_return = $this->$method($params);
            if ( ! $this->_template )
            {
                $this->_setTemplate($action);
            }
        }

        // close debug output block
        if (g()->debug->allowed())
            printf('</div>');

        return $ret;
    }


    /**
     * For launching another action inside action method.
     * Use with caution. Should always break action code, when calling this:
     * <code>
     * if (!isset($params[0]))
     *     return $this->delegateAction('somethingElse', $params);
     * </code>
     * Keeps $_launched_action intact.
     * @author m.augustynowicz
     */
    public function delegateAction($action, array &$params)
    {
        $real_action = $this->_launched_action;
        $this->_launched_action = null;
        $this->_template = '';
        $ret = $this->launchAction($action, $params);
        $this->_launched_action = $real_action;
        return $ret;
    }


    /**
     * Gets launched action
     *
     * note: It returns action's name even, when permission has been denied.
     * @author m.augustynowicz
     *
     * @return string
     */
    public function getLaunchedAction($collapse_default=false)
    {
        if ($this->_launched_action == $this->_default_action)
            return '';
        else
            return $this->_launched_action;
    }


    /**
     * Gets params of launched action
     * @see getParam()
     * @see getLaunchedAction()
     * @author m.augustynowicz
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }


    /**
     * Gets a param of launched action
     * @see getParams()
     * @see getLaunchedAction()
     * @author m.augustynowicz
     *
     * @param string|int $key name or index of a param
     * @return string param value, null when it's not set
     */
    public function getParam($key)
    {
        return @$this->_params[$key];
    }


    /**
     * Link to action in current controller
     * @author m.augustynowicz
     *
     * @param string $contents text of link
     * @param string $act action name. use '' for default
     * @param array $params action's params
     * @param array $attr additional <a />'s attributes
     *        (not all are accepted -- view _l2sth() for details)
     *
     * @return string html code
     */
    public function l2a($contents, $act = '', array $params = array(), array $attrs = array())
    {
        $attrs['href'] = $this->url2a($act, $params);

        return $this->_l2sth($contents, $attrs);
    }

    /**
     * Prints link to a specified controler(s).
     *
     * You can build link to multiple controllers giving them in an arrays
     * <code>
     * $this->l2c('link', array('Debug','On'),array('User','show','42'));
     * </code>
     * Note: when selecting multiple controllers, $attr is taken from first one.
     * @author m.augustynowicz
     *
     * @param string $contents link label
     * @param string|Controller $ctrl path to controller
     * @param string $act action name
     * @param array $params action's parameters
     * @param array $attrs passed to _l2sth()
     *
     * @return string html code
     */
    public function l2c($contents, $ctrl = null, $act = '', array $params = array(), array $attrs = array())
    {
        $argv = func_get_args();
        $contents = array_shift($argv);
        if (!@is_array($argv[0]))
        {
            $argv = array($argv);
        }

        if (!array_key_exists(3, $argv[0]))
            $attrs = array();
        else
        {
            $attrs = & $argv[0][3];
            unset($argv[0][3]);
            $argv[0] = array_values($argv[0]); // re-index
        }

        $attrs['href'] = call_user_func_array(array($this,'url2c'), $argv);

        $contens = $this->trans($contents);

        return $this->_l2sth($contents, $attrs);
    }

    /**
     * Acts exacly like l2a, but uses url2aInside to generate URL
     * @author m.augustynowicz
     *
     * @param string $contents text of link
     * @param string $act action name. use '' for default
     * @param array $params action's params
     * @param array $attr additional <a />'s attributes
     *        (not all are accepted -- view _l2sth() for details)
     *
     * @return string html code
     */
    public function l2aInside($contents, $act = '', array $params = array(), array $attrs = array())
    {
        $attrs['href'] = $this->url2aInside($act, $params);

        return $this->_l2sth($contents, $attrs);
    }

    /**
     * Acts exacly like l2c, but uses url2cInside to generate URL
     *
     * @author m.augustynowicz
     *
     * @param string $contents link label
     * @param string|Controller $ctrl path to controller
     * @param string $act action name
     * @param array $params action's parameters
     * @param array $attrs passed to _l2sth()
     *
     * @return string html code
     */
    public function l2cInside($contents, $ctrl=null, $act='', array $params=array(), array $attrs=array())
    {
        $argv = func_get_args();
        $contents = array_shift($argv);
        if (!@is_array($argv[0]))
        {
            $argv = array($argv);
        }

        if (!array_key_exists(3, $argv[0]))
        {
            $attrs = array();
        }
        else
        {
            $attrs = $argv[0][3];
            unset($argv[0][3]);
        }

        $attrs['href'] = call_user_func_array(array($this,'url2cInside'), $argv);

        return $this->_l2sth($contents, $attrs);
    }

    /**
     * Common code for l2a() and l2c() for building <a /> tag.
     * @author m.augustynowicz
     *
     * @param string $contents content of <a /> tag
     * @param array $attrs additional attributes to <a /> tag.
     *        special values: 'anchor' is what will get added after '#' to URL
     *
     * @return string html code with <a /> tag
     */
    protected function _l2sth($contents, array $attrs=array())
    {
        static $allowed_attrs = array(
                'href'=>1,
                'rel'=>1, 'rev'=>1,
                'class'=>1, 'id'=>1,
                'title'=>1,'onclick'=>1,
                'target'=>1,
            );

        // special values in attrs
        if (isset($attrs['anchor']))
        {
            $attrs['href'] .= '#'.$attrs['anchor'];
            unset($attrs['anchor']);
        }

        if (empty($contents))
        {
            $contents = $attrs['href'];
        }

        return g('Functions')->tag('a', $attrs, $contents);
    }


    /**
     * Url to action in current controller
     * @author m.augustynowicz
     *
     * @param string $act action's name
     * @param array $params action's parameters
     * @param boolean $with_host should produced URL contain host name?
     *
     * @return string
     */
    public function url2a($act = '', array $params = array(), $with_host = false)
    {
        // null means current controller
        return $this->url2c(null, $act, $params, $with_host);
    }


    /**
     * Build a URL to specified controller(s)
     *
     * You can build link to multiple controllers giving each of them an arrays
     * <code>
     * $this->url2c(array('Debug','On'),
     *              array('User','show',array('42')),
     *              array('User/friends','', array('page'=>2) );
     * </code>
     * @author m.augustynowicz
     *
     * @param IController|string $ctrl path to controller or the controller itself,
     *        use null for $this and '' for default controller (main page)
     * @param string $act action name, use '' for default one
     * @param array $params action's parameters
     * @param boolean $with_host should produced URL contain host name?
     *
     * @return string URL
     */
    public function url2c($ctrl, $act = '', $params = array(), $with_host = false)
    {
        $url = $this->__buildArrayUrlFromArrayOfCtrls(func_get_args());

        $this->__buildStringUrlFromArray($url);

        g()->req->enhanceURL($url);
        return g()->req->getBaseUri($with_host).$url;
    }


    /**
     * Acts like url2a, but uses url2cInside instead
     *
     * @author m.augustynowicz
     *
     * @param string $act action's name
     * @param array $params action's parameters
     *
     * @return string
     */
    public function url2aInside($act = '', array $params = array())
    {
        // null means current controller
        return $this->url2cInside(null, $act, $params);
    }

    /**
     * Build a URL.
     * Function uses current request tree, modifies it using given parameters and
     * builds new URL based on it.
     *
     * You can build link to multiple controllers giving them in arrays
     * $this->url2cInside(array('UserProfile','showUsersItems',array('id'=>190,'p'=>3)),
     *                    array('Filters','',array('cat'=>3)) );
     * @author m.wierzba
     * @author m.augustynowicz
     *
     * @param string|IController $ctrl path to controller or the controller itself
     * @param string $act action name
     * @param array $params action's parameters
     *
     * @return string URL
     */
    public function url2cInside($ctrl, $act = '', array $params = array(), $with_host = false)
    {
        $arg_url = $this->__buildArrayUrlFromArrayOfCtrls(func_get_args());
        $url     = $this->__buildArrayUrlFromRequestArray();
        g('Functions')->arrayMergeRecursive($url, $arg_url);

        $this->__buildStringUrlFromArray($url);

        g()->req->enhanceURL($url);

        return g()->req->getBaseUri($with_host) . $url;
    }


    /**
     * Convert array used by Request class to array used by __buildStringUrlFromArray()
     * @author m.augustynowicz
     *
     * @param array|null $node if none passed, will use current request tree
     *
     * @return array
     */
    private function __buildArrayUrlFromRequestArray(array $node = null)
    {
        if ($node === null)
        {
            $node = g()->req->getWhole();
        }


        $arr = array();
        $act = '';

        if (isset($node['children']))
        {
            foreach ($node['children'] as $name => $child)
            {
                if (strtolower($name[0]) === $name[0])
                {
                    if (isset($child['children']))
                    {
                        $node['children'] += $child['children'];
                    }
                    if (isset($child['params']))
                    {
                        $node['params'] += $child['params'];
                    }
                    unset($node['children'][$name]);
                    $act = $name;
                }
            }

            foreach ($node['children'] as $name => $child)
            {
                $arr[$name] = $this->__buildArrayUrlFromRequestArray($child);
            }
        }

        if (isset($node['params']))
        {
            if ($act)
            {
                $act = "/{$act}";
            }

            $enc_params = g()->req->encodeVal($node['params']);
            if ($enc_params)
            {
                $enc_params = ":{$enc_params}";
            }

            if ($act || $enc_params)
            {
                $arr[null] = "{$act}{$enc_params}";
            }
        }

        return $arr;
    }


    /**
     * Convert array used as params for url2c()-alike methods to
     * form used internally (e.g. by __buildStringUrlFromArray())
     * @author m.augustynowicz
     *
     * @param array $controllers array with arguments given to url2c()
     *
     * @return array
     */
    private function __buildArrayUrlFromArrayOfCtrls(array $controllers)
    {
        if (!is_array($controllers[0]))
        {
            // only one controller given
            $controllers = array($controllers);
        }


        $url = array();
        foreach ($controllers as $c)
        {
            if (!is_array($c))
            {
                continue;
            }

            @list($ctrl, $act, $params, $with_host) = $c;

            // validate types of parameters

            if (null === $ctrl)
                $ctrl = $this->url();
            else if ($ctrl instanceof IController)
                $ctrl = $ctrl->url();

            if (!is_string($act) && null !== $act)
                throw new HgException('Action passed to url2c should be a string &#8212; '.gettype($act).' given.');

            if (null === $params)
                $params = (array) $params;
            else if (!is_array($params))
                throw new HgException('Params passed to url2c should be an array &#8212; '.gettype($params).' given.');


            // get controller's meta (action + params)

            $c_meta = g()->req->encodeVal($params);
            if ('' !== $c_meta)
                $c_meta = g()->conf['link_split'] . $c_meta;
            if ($act)
            {
                $act = lcfirst($act);
                $c_meta = '/' . $act . $c_meta;
            }


            // store meta in array under it's path

            $r_url = & $url;
            $path = explode('/', ltrim($ctrl, '/'));
            foreach ($path as $elem)
            {
                $elem = ucfirst($elem);
                $r_url = & $r_url[$elem];
            }
            $r_url[null] = $c_meta;
        }
        unset($r_url);

        return $url;
    }


    /**
     * Create string url from array format, used internally in {@see url2c()}
     * @author m.augustynowicz
     *
     * @param array $url current node in an array
     * @param string $curr_path path to current node
     *
     * @return void method works on reference to $url
     */
    private function __buildStringUrlFromArray(array &$url, $curr_path='')
    {
        foreach ($url as $name => & $node)
        {
            $node_meta = & $node[null];
            unset($node[null]);

            $node_path = $curr_path . $name . '/';
            $this->__buildStringUrlFromArray($node, $node_path);
            if ($node)
                $node = '/' . $node;

            $node = $name . $node_meta . $node;
        }

        $url = implode(';'.$curr_path, $url);
    }


    /**
     * Makes a controller.
     *
     * Same parameters as IController::addChild()
     * @author m.augustynowicz
     *
     * @param Controller|string $controller to add
     *        (object or name to pass to Kernel::get())
     * @param null|string|array $name_or_args if string passed as $controller,
     *        this will be used to initialize $controller,
     *        null -- use lowercased $controller as name
     *        string -- use as name
     *        array -- pass to $controller's constructor
     * @return Controller
     */
    protected function _makeController($controller, $name_or_args=null)
    {
        if (is_string($controller))
        {
            if (is_array($name_or_args))
                $args = & $name_or_args;
            else
            {
                if (null === $name_or_args)
                    $name_or_args = ucfirst($controller);
                $args = array(
                    'name'   => $name_or_args,
                    'parent' => $this
                );
            }
            $controller = g($controller, 'Controller', $args);
        }
        return $controller;
    }


    /**
     * Checks wheteher given name is a valid action of current component
     * @author m.augustynowicz
     *
     * @param string $action_name
     *
     * @return bool|string false, when not valid action,
     *         string with action's method name otherwise.
     */
    protected function _getActionMethod($action_name)
    {
        switch ($action_name)
        {
            case null :
            case 'default' :
                /**
                 * @todo DEPRECATED (defaultAction) marked for deletion 2010-11-03
                 */
                if (!method_exists($this, 'actionDefault'))
                {
                    g()->debug->addInfo(
                        "deprecated {$this->getName()}::defaultAction",
                        true,
                        "`defaultAction' name convention has been deprecated and will be removed in future. Rename it in `{$this->getName()}' to `actionDefault' instead"
                    );
                    $method = 'defaultAction';
                    break;
                }
            default :
                $method = 'action' . ucfirst($action_name);
        }

        if (method_exists($this, $method))
            return $method;
        else
            false;
    }

}



/**
* Kontroler pniowy. Wspolna cecha kontrolerow pniowych jest posiadanie
* jednego childa (kontrolera podrzednego). Domyslnie, przetwarzanie akcji
* w kontrolerze pniowym polega na transparentnym przekazaniu akcji do
* jego jedynego childa, podobnie domyslny render polega na przekazaniu
* wyniku renderowania childa.
*
*/
abstract class TrunkController extends Controller
{
    private $__child;
    private $__child_name;

    public function addChild($controller, $name_or_args=null)
    {
        $controller = $this->_makeController($controller, $name_or_args);
        $this->__child = $controller;
        $this->__child_name = $controller->getName();
        return $controller;
    }

    public function isChild($controller)
    {
        if ($controller instanceof IController)
        {
            if ($controller === $this->__child)
                return $controller;
            else
                return false;
        }

        if (is_string($controller))
        {
            $controller = ucfirst($controller);
            if ($controller === $this->__child_name)
                return $controller;
            else
                return false;
        }

        throw new HgException('$controller must be either a string or an IController');
    }

    public function getChildren()
    {
        return array($this->__child_name => $this->__child);
    }

    public function getChild($name)
    {
        $name = ucfirst($name);
        if ($this->isChild($name))
            return $this->__child;
        else
            return null;
    }

    /**
     * Getter for main displaying controller
     * @return Component
     */
    public function displayingCtrl()
    {
        if ($this->__child instanceof TrunkController)
            return $this->__child->displayingCtrl();
        else
            return $this->__child;
    }


    /**
     * Checks whether component is request's main displaying controller.
     * @author m.augustynowicz
     *
     * @return boolean
     */
    public function isDisplayingCtrl()
    {
        return false;
    }


    /**
     * Get's (children's) return value of launched action
     * @author m.augustynowicz
     *
     * @return mixed
     */
    public function getLaunchedActionReturn()
    {
        return $this->__child->getLaunchedActionReturn();
    }



    /**
     * Getter for permanent controllers
     * @param string $name permanent controller name
     *        as defined in conf[permanent_controllers]
     * @return PermanentController
     */
    public function getPermaCtrl($name)
    {
        $name = ucfirst($name);
        return $this->__child->getPermaCtrl($name);
    }


    public function process(Request $req=null)
    {
        $this->__child->process($req);
    }


    public function present()
    {
        $this->__child->present();
    }

    public function render()
    {
        $this->__child->render();
    }

    public function url()
    {
        if ($parent = $this->getParent())
            $name = $parent->url();
        else
            $name = '';
        return $name;
    }

}



/**
 * Visual controller that represents a part of the HTML body.
 *
 * process() has been split into two rounds: first we decide what action
 * should be launched for this component and all his children (preparing),
 * then we launch tem (launching).
 *
 *
 * Possible callbacks used during processing the request:
 *
 * * _prepareAction{ActionName}(array $params)
 *
 *   Called in prepare round for action that will be launched later.
 *
 *   - $params is a reference to request params
 *   - return value ignored.
 *
 * * _onRoutingTo{ComponentName}(bool $in_request);
 *
 *   Called in prepare round for each component.
 *
 *   - $in_request determines wheter component is present in
 *     the request (URL)
 *   - return false to prevent component from processing
 *
 * * _onAction(string $name, array &$params);
 *
 *   Called in launch round before launching action. It already exists in
 *   Controller, so be sure you call parent::_onAction(), when overloading.
 *
 *   - $name stores name of the action that is about to be launched
 *   - $params is a reference to request params
 *   - return false to prevent action from being launched
 *
 *
 *
 * Possible callbacks used in form validation:
 *
 * * validate{FormName}(array &$post)
 *
 *   Called to validate POST send to the form
 *
 *   - $post is this form's part of $_POST
 *   - returns array of errors. it can contain special key [stop_validation]
 *     if you don't want to launch build-in (model's) validation methods
 *     afterwards
 *
 * * validate{FormName}{FieldName}(mixed &$value)
 *
 *   Called to validate POST data for specified form field.
 *
 *   - $value is a value of the field, taken from POST (therefore it can
 *     be string or an array of strings)
 *   - returns the same as validate{FormName}
 */
abstract class Component extends Controller
{
    public static $singleton=false;
    public $forms = array();
    public $data = array();
    private $__components = array();

    /**
     * legacy. use of it is deprecated from 2010-01-04)
     * @todo remove this. and setting it in _setTemplate()
     * @author m.augustynowicz
     * @var string
     */
    protected $_action = '';

    protected $_validate = true;
    protected $_validated = array(); // list of validated forms
                                      // array(name => successfully_validated);

    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->_fixFormsFormat();
        $this->validate();
    }

    protected function _onAction($action, array &$params)
    {
        if (parent::_onAction($action, $params))
            return ! (g()->req->isAjax() && !@empty($_POST['validate_me']));
        else
            return false;
    }


    public function validate()
    {
        if (!$this->_validate)
            return true;
        if(isset($_POST['ds']) && isset($_POST['ident']) && isset($this->forms['models']))
        {
            $ds_name = pg_escape_string($_POST['ds']);
            if(g()->load($ds_name,'model',false))
            {
                $ident = pg_escape_string($_POST['ident']);
                $ds = g($ds_name,'model');
                $this->forms[$ident]['model'] = $ds->getName();
                $fields = $ds->getFields();
                foreach($fields as $fname => $field)
                    $this->forms[$ident]['inputs'][$fname] = array('models'=>array($ds_name=>array($fname)));
            }
            unset($this->forms['models']);
            unset($_POST['ds']);
            unset($_POST['ident']);
        }

        if (empty($this->forms))
            return true;
        if (!$_POST)
            return true;

        /**
         * @todo remove this, when sure it did not break anything.
         *       commented out 2010-01
         */
        /*
        if (g()->req->isAjax())
        {
            if (@empty($_POST['validate_me']))
                return true;
        }
         */

        $post_with_files = $_POST;

        // merge $_FILES into $post_with_files
        // will change structure of first level $_FILEs:
        // <code>
        // [form ident][name etc][field][0..1]
        // becomes
        // [form ident][field][name etc][0..1]
        // </code>
        foreach($_FILES as $ident => $files)
        {
            @list(,$form) = explode('_', $ident, 2);
            if (!isset($this->forms[$form]))
                continue;
            if ($this->getFormsIdent($form) !== $ident)
                continue;
            if (!is_array($files))
                continue;
            $hg_files = array();
            foreach($files as $idx => $input)
            {
                if (!is_array($input))
                    continue;
                foreach($input as $name => $msg)
                {
                    if (!is_array($msg))
                        $msg = array($msg);
                    foreach ($msg as $i => $m)
                        $hg_files[$name][$i][$idx] = $m;
                }
            }
            foreach ($hg_files as $name => & $f)
            {
                if (sizeof($f)==1)
                    $post_with_files[$ident][$name] = & $f[key($f)];
                else
                    $post_with_files[$ident][$name] = & $f;
            }
            unset($f);
        }

        foreach($post_with_files as $ident=>$post)
        {
            @list(,$form) = explode('_', $ident, 2);
            if (!isset($this->forms[$form]))
                continue;
            if ($this->getFormsIdent($form) !== $ident)
                continue;
            $data = $this->forms[$form]['inputs'];
            if (g()->req->isAjax())
                $data = array_intersect_key($data, $post);

            foreach($post as $input=>$value)
                if(!isset($data[$input]))
                    unset($post[$input]);

            $errors = array();
            $f = g('Functions');

            // validate_me please means that not whole form is being validated
            if ('please'!==@$_POST['validate_me'])
            {
                if (method_exists($this,$callback = 'validate'.$f->camelify($form)))
                {
                    $new_errors = $this->$callback($post);
                    if (!empty($new_errors))
                        $errors[$ident][0] = array_merge((array)@$errors[$ident][0], (array)$new_errors);
                }
                /**
                 * @todo remove this when all deployments got updated
                 *       added 2009-10-09
                 * @author m.augustynowicz
                 */
                else if (method_exists($this,$callback = 'validate'.ucfirst($form)))
                {
                    g()->debug->addInfo('non-camel form validation callback', true, "Non-camel callbacks are deprecated, due theirs non-camellness. Please update %s class &#8212; use `validate%s' instead of `%s'", get_class($this), $f->camelify($form), $callback);
                    $new_errors = $this->$callback($post);
                    if (!empty($new_errors))
                        $errors[$ident][0] = array_merge((array)@$errors[$ident][0], (array)$new_errors);
                }
            }

            if (@$errors['stop_validation'])
                unset($errors['stop_validation']);
            else
            {
                foreach($data as $input=>$input_def)
                {
                    $callback = 'validate' . $f->camelify($form)
                                . $f->camelify($input);
                    if (method_exists($this,$callback))
                    {
                        $new_errors = $this->$callback($post[$input]);
                        if (!empty($new_errors))
                        {
                            $errors[$ident][$input] = array_merge(
                                    (array)@$errors[$ident][$input],
                                    (array)$new_errors
                                );
                        }
                        if (@$errors[$ident][$input]['stop_validation'])
                        {
                            unset($errors[$ident][$input]['stop_validation']);
                            continue;
                        }
                    }
                    /**
                     * @todo remove this when all deployments got updated
                     *       added 2009-10-09
                     * @author m.augustynowicz
                     */
                    else if (method_exists($this,$callback = 'validate' . ucfirst($form) . $f->camelify($input)))
                    {
                        g()->debug->addInfo('non-camel form input validate callback', true, "Non-camel callbacks are deprecated, due theirs non-camellness. Please update class %s &#8212; use `validate%s%s' instead of `%s'", get_class($this), $f->camelify($form), $f->camelify($input), $callback);
                        $new_errors = $this->$callback($post[$input]);
                        if (!empty($new_errors))
                        {
                            $errors[$ident][$input] = array_merge(
                                    (array)@$errors[$ident][$input],
                                    (array)$new_errors
                                );
                        }
                        if (@$errors[$ident][$input]['stop_validation'])
                        {
                            unset($errors[$ident][$input]['stop_validation']);
                            continue;
                        }
                    }

                    if (@empty($input_def['models']))
                        continue;

                    foreach($input_def['models'] as $model => $fields)
                    {
                        if (! $model = g($model,'model'))
                            throw new HgException("Model declared in Component variable \$forms does not exist!");
                        foreach ($fields as $field)
                        {
                            $err = $model->validateField($field, $post[$input]);
                            if($err)
                            {
                                foreach ($err as &$err_text)
                                    $err_text = $this->trans($err_text);
                                $errors[$ident][$input] = array_merge(
                                    (array)@$errors[$ident][$input],
                                    $err
                                );
                            }
                        }
                    }
                } // foreach data as input field
            }
            $this->_validated[$form]= true; //will be changed to false below, if errors found
            $this->data[$form] = $post;
            if (!isset($this->data[$form]['_backlink']))
                $this->data[$form]['_backlink'] = g()->req->getReferer();
            if (g()->req->isAjax())
            {
                // assigning to first_controller as this method (validate()) is
                // called from constructor and $this->displayingCtrl() is
                // unreliable.. /:
                g()->first_controller->assign('json', compact('errors'));
            }
            else
            {
                foreach ($errors as $form_key => & $form_errors)
                {
                    foreach ($form_errors as $input => & $input_errors)
                    {
                        foreach ($input_errors as $error_id => $error_msg)
                        {
                            g()->addInfo("$form_key $input $error_id", 'forms', $error_msg);
                            $this->_validated[$form] = false;
                        }
                    }
                }
            }
        } // foreach post as form=>post
    }


    /**
     * Process request (create components, launch actions etc)
     *
     * note: not really used, as this happens in LibraryController
     * @author m.augustynowicz
     *
     * @param Request $req
     */
    public function process(Request $req=null)
    {
        $this->prepareActions($req);
        $this->launchActions($req);
    }


    /**
     * Decide whitch action will be launched, launch callbacks
     * @author m.augustynowicz
     * @param Request $req
     */
    public function prepareActions(Request $req=null)
    {
        // begin debug output block
        if (g()->debug->allowed())
        {
            $displaying_ctrl_class = $this->isDisplayingCtrl() ? 'is-displaying-ctrl' : '';
            printf('<div class="debug-output-block %s extends-%3$s preparing ctrl"><h3>preparing %s <small class="extends">extends %s</small></h3>',
                   $displaying_ctrl_class, $this->path(), get_parent_class($this) );
        }

        // find an action to launch

        $dived = null!==$req && $req->dive();

        // action already set
        if (null !== $this->_action_to_launch)
        {
            if ($this->_getActionMethod($this->_action_to_launch))
            {
                if ($dived && false !== $req->seekTo($this->_action_to_launch))
                {
                    $this->_params = $req->getParams();
                }
                else
                {
                    $this->_params = array();
                }
            }
            else
            {
                trigger_error('Incorrect action set to be launched: '
                            . $this->_action_to_launch,
                        E_USER_WARNING);
                $this->_action_to_launch = null;
            }
        }

        // try to get action from request
        if ($dived && null === $this->_action_to_launch)
        {
            while ($req_node = $req->next())
            {
                if ($this->_getActionMethod($req_node))
                {
                    $this->_action_to_launch = $req_node;
                    $this->_params = $req->getParams();
                    break;
                }
            }
        }

        if ($dived)
        {
            $req->emerge();
        }

        // fallback to default action
        if (null === $this->_action_to_launch)
        {
            $this->_action_to_launch = $this->_default_action;
            $this->_params = (null===$req) ? array() : $req->getParams();
        }

        if (g()->debug->allowed())
        {
            printf('<p>found action %s(%s)</p>',
                   $this->_action_to_launch, print_r($this->_params, true) );
        }


        // prepare this component's action

        $method = '_prepareAction' . ucfirst($this->_action_to_launch);
        if (method_exists($this, $method))
        {
            if (g()->debug->allowed())
            {
                $displaying_ctrl_class = $this->isDisplayingCtrl() ? 'is-displaying-ctrl' : '';
                printf('<div class="debug-output-block %s extends-%s preparing action"><h4>launching %s(%s)</h3>',
                       $displaying_ctrl_class, get_parent_class($this), $method, print_r($this->_params, true) );
            }

            $this->$method($this->_params);

            if (g()->debug->allowed())
            {
                printf('</div>');
            }
        }

        // prepare childrens' actions

        $dived = null!==$req && $req->dive();
        $f = g('Functions');
        foreach ($this->__components as & $child)
        {
            // whether component is present in request
            $in_request = $dived && false !== $req->seekTo($child->getName());

            // _onRoutingToChildName callback

            $child_name = $f->camelify($child->getName());
            if (method_exists($this, $callback = "_onRoutingTo$child_name"))
            {
                if (g()->debug->allowed())
                {
                    printf('<div class="debug-output-block routing"><h4>launching %s(%s)</h3>',
                           $callback, print_r($in_request, true) );
                }

                $route = $this->$callback($in_request);

                if (g()->debug->allowed())
                {
                    printf('</div>');
                }

                if (false === $route) // null still routes
                    continue;
            }

            $child->prepareActions($in_request ? $req : null);
        }
        unset($component);
        if ($dived)
        {
            $req->emerge();
        }

        // end debug output block
        if (g()->debug->allowed())
        {
            printf('</div>');
        }
    }


    /**
     * Launch actions selected in prepareActions()
     * @author m.augustynowicz
     * @param Request $req
     */
    public function launchActions(Request $req=null)
    {
        // begin debug output block
        if (g()->debug->allowed())
        {
            $displaying_ctrl_class = $this->isDisplayingCtrl() ? 'is-displaying-ctrl' : '';
            printf('<div class="debug-output-block %s extends-%3$s launching ctrl"><h3>launching things in %s <small class="extends">extends %s</small></h3>',
                   $displaying_ctrl_class, $this->path(), get_parent_class($this) );
        }

        if (!$this->_action_to_launch)
        {
            throw new HgException('Called launchActions(), but no action has been selected.');
            return;
        }

        // launch actions for all the components

        $dived = null!==$req && $req->dive();
        foreach ($this->__components as & $child)
        {
            if (!$child->_action_to_launch)
                continue;

            $in_request = $dived && false !== $req->seekTo($child->getName());
            $child->launchActions($in_request ? $req : null);
        }
        unset($component);
        if ($dived)
        {
            $req->emerge();
        }

        // and my own action
        $this->launchAction($this->_action_to_launch, $this->_params);


        // close debug output block
        if (g()->debug->allowed())
        {
            printf('</div>');
        }
    }


    /**
    * Adds a subcontroller.
    *
    * @param Controller|string $controller to add
    *        (object or name to pass to Kernel::get())
    * @param null|string|array $name_or_args if string passed as $controller,
    *        this will be used to initialize $controller,
    *        null -- use lowercased $controller as name
    *        string -- use as name
    *        array -- pass to $controller's constructor
    * @return The added controller.
    */
    public function addChild($controller, $name_or_args=null)
    {
        $controller = $this->_makeController($controller, $name_or_args);

        if ($this->isChild($controller->getName()))
            throw new HgException("This component already has a child named ".$controller->getName());
        $this->__components[$controller->getName()] = $controller;
        return $controller;
    }

    public function isChild($controller)
    {
        if ($controller instanceof IController)
        {
            return array_search($controller,$this->__components);
        }

        if (is_string($controller))
        {
            $controller = ucfirst($controller);
            if (array_key_exists($controller, $this->__components))
                return $controller;
            else
                return false;
        }

        throw new HgException('$controller must be either a string or an IController');
    }

    /**
    * Includes a template or a controller. If $____tpl is a name of a components child, then the child is included.
    * @param $____tpl either a child name, a controller, or a template name
    * @param $____local_variables Local variables assigned exclusively in this call.
    */
    public function inc($____tpl, array $____local_variables=NULL)
    {
        if (NULL !== ($child = $this->getChild($____tpl)))
            return parent::inc($child, $____local_variables);
        else
            return parent::inc($____tpl, $____local_variables);
    }

    /**
    * Retrieves children array
    */
    public function getChildren()
    {
        return($this->__components);
    }

    /**
    * Retrieves a child, if one is found.
    * @param $name name of the child to look for
    * @return A child controller if found, or NULL if thete is no child of given name.
    */
    public function getChild($name)
    {
        $name = ucfirst($name);
        if ($this->isChild($name))
            return ($this->__components[$name]);
        return(NULL);
    }

    /**
     * Geter of main displaying controller (first component)
     *
     * This may be unreliable if first_controller's process() has not ended!
     * You have been warned.
     *
     * @author m.augustynowicz
     * @return Component
     */
    public function displayingCtrl()
    {
        return g()->first_controller->displayingCtrl();
    }


    /**
     * Checks whether component is request's main displaying controller.
     * @author m.augustynowicz
     *
     * @return boolean
     */
    public function isDisplayingCtrl()
    {
        return $this == $this->displayingCtrl();
    }


    /**
     * Getter for permanent controllers
     * @param string $name permanent controller name
     *        as defined in conf[permanent_controllers]
     * @return PermanentController
     */
    public function getPermaCtrl($name)
    {
        $name = ucfirst($name);
        return g()->first_controller->getPermaCtrl($name);
    }


    /**
     * Get param from this component's parent, if it's of certain type
     * and certain action is to be launched.
     * @author m.augustynowicz
     *
     * @param string $parent_name name of the parent
     * @param string|null $parent_action name of action, null or empty string
     *        for default action
     * @param int|string $key param name or numeric key
     *
     * @return string|null|false
     *         - param value;
     *         - null if param is not set
     *         - false when parent is not of requested type;
     */
    public function getParentParam($parent_name, $parent_action=null, $key=0)
    {
        $parent = $this->getParent();

        // null and empty strings denotes default action
        if (!$parent_action)
        {
            $parent_action = $this->_default_action;
        }

        // check whether parent matches
        if ($parent_name != $parent->getName())
        {
            return false;
        }

        // check whether parent's action matches
        // we use _action_to_launch as at this point parent's action hasn't
        // been launched yet
        if ($parent_action != $parent->_action_to_launch)
        {
            return false;
        }


        // returns null when $key is not set
        return $parent->getParam($key);
    }


    /**
    * Renders a component. A template named after the controller name is included.
    */
    public function render()
    {
        if (g()->debug->on('view'))
        {
            printf('<!-- %s::render() BEGIN -->', $this->path() );
        }

        if (null === $this->_subrenderer)
        {
            $this->inc($this->_template);
        }
        else
        {
            $this->_subrenderer->render();
        }

        if (g()->debug->on('view'))
        {
            printf('<!-- %s::render() END -->', $this->path() );
        }
    }

    public function getFormsIdent($ident)
    {
        if (is_a($ident, 'Forms'))
        {
            $ident = $ident->getShortIdent();
        }
        return g('Functions')->camelify($this->url()) . '_' . $ident;
    }

    /**
     */
    protected function _fixFormsFormat()
    {
        if (@$this->_forms_are_ok)
            return;

        // add predefinied forms
        $this->forms += array(
            // use with tpl/confirm.php
            'confirm' => array(
                'ajax'   => false,
                'model'  => false,
                'inputs' => array(),
            ),
        );

        $fixed = array();

        foreach ($this->forms as $form_name => & $form_def)
        {
            if($form_name==='models')
            {
                $fixed['models']=true;
                continue;
            }
            $form = & $fixed[$form_name];
            $form = array('ident' => $form_name);

            // copy these if present
            foreach (array('model','ajax','upload') as $key)
                if (isset($form_def[$key]))
                    $form[$key] = $form_def[$key];

            $default_model = @$form_def['model'];
            foreach ($form_def['inputs'] as $input_name => $input_def)
            {
                do
                {
                    $tpl = null;

                    // inputs => array(field_name=>array(_tpl=>'xx',..))
                    // and/or
                    // inputs => array(field_name=>array()
                    if (is_array($input_def))
                    {
                        if (isset($input_def['_tpl']))
                        {
                            $tpl = $input_def['_tpl'];
                            unset($input_def['_tpl']);
                        }
                        if (empty($input_def))
                        {
                            $input_def = $input_name;
                            $input_name = -1;
                        }
                    }

                    // inputs => array(field_name)
                    if (is_int($input_name))
                    {
                        if (empty($default_model))
                        {
                            throw new HgException('No default model specified for '.
                                  $form_name."[$input_def] and no supplied in \$forms!");
                        }
                        $form['inputs'][$input_def]['models'][$default_model] =
                            array($input_def);
                        $input_name = $input_def; // so [tpl] got set properly
                        break;
                    }

                    // inputs => array(field_name=>model)
                    if (is_string($input_def))
                    {
                        $form['inputs'][$input_name]['models'][$input_def] =
                            array($input_name);
                        break;
                    }

                    //
                    if (is_array($input_def) && sizeof($input_def)===1)
                    {
                        // inputs => array(field_name=>array('fields'=>null))
                        if ((array_key_exists('fields',$input_def) && !$input_def['fields'])
                        // inputs => array(field_name=>array(null))
                         || (array_key_exists(0, $input_def) && !$input_def[0]) )
                        {
                            $form['inputs'][$input_name]['models'] = array();
                            break;
                        }

                        // inputs => array(field_name=>array('models'=>array('model name'=>'field name')))
                        if (array_key_exists('models', $input_def))
                        {
                            $form['inputs'][$input_name]['models'] = $input_def['models'];
                            break;
                        }
                    }

                    // inputs => array(field_name=>array())
                    if (is_array($input_def))
                    {
                        foreach ($input_def as $input_def_name => $input_def_def)
                        {
                            // model
                            if (is_int($input_def_name))
                            {
                                $form['inputs'][$input_name]['models'][$input_def_def][] = $input_name;
                                break;
                            }
                            // field => model
                            // or
                            // field => array(model1, model2)
                            if (!is_array($input_def_def))
                                $input_def_def = array($input_def_def);
                            foreach ($input_def_def as $input_def_def_def)
                                $form['inputs'][$input_name]['models'][$input_def_def_def][] = $input_def_name;
                            break;
                        }
                    }
                }
                while(true==='true');
                if ($tpl)
                    $form['inputs'][$input_name]['tpl'] = $tpl;
            }

            $form['inputs']['_backlink'] = array(
                    'models' => null,
                    'tpl'    => 'Forms/FBackLink',
                );
            $form['inputs']['_timestamp'] = array(
                    'models' => null,
                    'tpl'    => 'Forms/FRenderTimestamp',
                );
        }

        $this->forms = $fixed;
        $this->_forms_are_ok = true;
    }

}

/**
 * Components to be added in every request by LibraryController.
 *
 * Defined in conf[permanent_controllers].
 *
 * @author m.augustynowicz
 */
abstract class PermanentController extends Component
{
    /**
     * Generates part of URI to bo added to baseUri.
     * @author m.augustynowicz
     */
    public function getExtUri()
    {
        return '';
    }

}

