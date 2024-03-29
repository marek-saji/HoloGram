<?php
/**
 * Defines to be passed as $format parameter of Functions::formatDate().
 * Lowest four bits are reserved to "what to show".
 * Next four -- for defining type of format.
 * m.augustynowicz
 */
define('DATE_SORTABLE_FORMAT', 16);
define('DATE_HUMAN_FORMAT', 32);
define('DATE_SQL_FORMAT', 48);
define('DATE_SHOW_DATE', 1);
define('DATE_SHOW_TIME', 2);
define('DATE_SHOW_ALL', 3);

/**
 * Klasa jest potrzeba do wykorzystywania funkcji nie zależnych od innych klas np.: do sprawdzania e-mila, stronicowania itp...
 * @package hologram2
 * @author j.juszkiewicz
 * @version 1.0
 */
class Functions extends HgBase
{
    /**
     * Numeric suffix used to generate unique IDs for use in HTML
     * @author m.augustynowicz
     * @see uniqueId()
     * @var integer
     */
    static protected $_unique_id_offset = 0;

    protected $_email_reg_exp = "![a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}!";
    protected $_email_obfuscate_key = '';


    /**
     * Filling in some fields
     */
    public function __construct()
    {
        // initiate email obfuscating key
        $this->_email_obfuscate_key = g()->conf['site_name']
                . ' '
                . g()->conf['version'];
    }


    /**
     * Find links in given text and create anchors for them.
     *
     * Warning: find only links that start with www or http.
     *          If you want to improve it -- please don't be too agressive with matching.
     * @author m.augustynowicz
     *
     * @param string $text text to linkify
     * @return string linkified
     */
    function linkify($text)
    {
        return preg_replace('/
                             (?<=^|[\s<]) # start of the word
                             (
                               (?:http:\/\/|www\.) # starts with..
                               [^\s\/]+\.[^\s\/]+[^\s]* # and has one more dot.
                             )
                             (?=$|[\s,;>]) # end of the word
                            /x',
                            '<a href="$1">$1</a>', $text );
    }

    /**
     * Funkcja tworzaca losowy klucz alfanumeryczny o zadanej dlugosci
     *
     * @param integer $len
     * @return string
     * @author j.juszkiewicz
     * @author m.izewski - zlikwidowanie wystepowania notice
     * @version 1.0
     */
    function generateSimpleKey($len)
    {
        if($len>36 || !$len) $len = 36;
        $chars = "0123456789abcdefghijklmnopqrstuvwxyz";

        $str = '';

        for($i=1; $i<=$len; $i++)
        {
            $str .= $chars[rand(0,35)];
        }

        return $str;
    }

    /**
     * Metoda tworzy klucz na podstawie daty w formacie UNIX, wygenerowanego losowo klucza i ewentualnie podanego jako argument ciągu znaków (opcjonalne). W założeniu wygenerowany ciąg ma być niepowtarzalny.
     *
     * @param string $arg
     * @return string
     * @author j.juszkiewicz
     * @version 1.0
     */
    function generateKey($arg='')
    {
        $time = time();
        $simple_key = $this->generateSimpleKey(36);
        return md5($time.$simple_key.$arg);
    }

    /**
     * Funkcja sprawdza format adresu email i sprawdza czy istnieje taka domena
     *
     * @param string $email
     * @return bool
     * @author j.juszkiewicz
     * @version 1.0
     */
    function checkEmail($email)
    {
        if(!$email)
            return false;

        if((preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $email)) ||
            (preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/',$email)))
        {
            if (function_exists('checkdnsrr'))
            {
                $host = explode('@', $email);
                if(checkdnsrr($host[1].'.', 'MX') ) return true;
                if(checkdnsrr($host[1].'.', 'A') ) return true;
                if(checkdnsrr($host[1].'.', 'CNAME') ) return true;
            }
            else return true;
        }
        return false;
    }

    /**
     * Funkcja rozbijajaca ciag znakow w postaci - x.x1.x2 na tablice ['x']['x1']['x2']
     * kropka na poczatku warunkuje ustawienie $global na true,
     * kropka na koncu warunkuje ustawienie $incremental na true.
     *
     * @param string $name
     * @param boolean $global - wartosc zwracana przez referencje
     * @param boolean $incremental - wartosc zwracana przez referencje
     * @return array
     *
     * @author m.izewski
     * @version 1
     */
    function explodeStringifiedArray($name, &$global, &$incremental)
    {
        //rozbicie nazwy z kropkami na poszczegolne elementy
        $name = explode('.',$name);

        //ustawienie flagi - zmienna sesyjna globalna - na false
        $global = false;
        //ustawienie flagi - zmienna sesyjna dodawana jako kolejny element tablicy - na false
        $incremental = false;

        //jezeli pierwszy element jest pusty (nazwa poprzedzona kropka) ustawienie flagi global
        if($name[0]==='')
        {
            $global = true;
            //usuniecie pierwszego elementu z tablicy
            array_shift($name);
        }
        //jezeli ostatni element jest pusty (nazwa zakonczona kropka) wartosc ma byc dodana jako kolejny element tablicy
        if($name[sizeof($name)-1]==='')
        {
            $incremental = true;
            //usuniecie ostatniego elementu z tablicy
            array_pop($name);
        }

        return $name;
    }

    /**
     * Funkcja budujaca galaz tablicy z podanej listy kluczy, jezeli $incremental ustawiony na true to wartosc jest dodawana jako kolejna wartosc tablicy
     *
     * @param array $name - tablica zawierajaca liste kluczy
     * @param boolean $incremental
     * @param mixed $value - opcjonalny parametr uzywany tylko przy ustawianiu nowej wartosci
     * @return array
     *
     * @author m.izewski
     * @version 1
     */
    function buildArrayBranch($name, $incremental, $value=null)
    {
        $array_size = sizeof($name);
        //utworzenie tablicy wartosci i referencji
        for($i=0; $i<$array_size;$i++)
        {
            //tworzenie prostej tablicy asocjacyjnej i zapisanie jej w tablicy $arrays
            $arrays[$i] = array($name[$i]=>null);
            //zapisanie referencji - do wartosci powyzej utworzonej prostej tabeli asocjacyjenj - do tablicy
            $refs[$i] = &$arrays[$i][$name[$i]];
        }

        //budowanie drzewa
        for($i=0; $i<$array_size-1; $i++)
        {
            //przepisanie wartosci tabeli i+1 jako wartosc tabeli asocjacyjnej (budowanie drzewa)
            $refs[$i] = $arrays[$i+1];
        }
        //jezeli ustawiona flaga "incremental" - dopisujemy wartosc jako kolejna wartosc
        if($incremental)
            $refs[$array_size-1][] = $value;
        else
            $refs[$array_size-1] = $value;

        //przepisanie wyniku (pierwsza tabela zawiera pelne drzewo)
        $result = $arrays[0];

        return $result;
    }


    /**
     * Funkcja zwracajaca tablice bedaca roznica pomiedzy kluczami dwoch podanych tablic (nie sa porownywane wartosci).
     *
     * @param array $array1 - pierwsza tablica
     * @param array $array2 - druga tablica
     * @return array - roznica
     *
     * @author m.izewski
     * @version 1
     */
    function arrayDiffKeyRecursive($array1, $array2)
    {
        $result = array();

        foreach($array1 as $key => $value)
        {
            //jezeli wartosc jest tablica
            if (is_array($value) && isset($array2[$key]))
            {
                $result[$key] = $this->arrayDiffKeyRecursive($array1[$key], $array2[$key]);
            }
            //roznica w danym poziomie
            elseif(is_array($array2))
            {
                $res = array_diff_key($array1, $array2);

                if(!is_array($res))
                    $res=array();

                $result = array_merge($result,$res);
            }

            //unsetowanie gdy pusta tablica
            if (is_array(@$result[$key]) && count(@$result[$key])==0)
            {
                unset($result[$key]);
            }
        }

        return $result;
    }


    /**
     * Funkcja laczaca dwie tablice rekursywnie. Duzo fajniejsza, niz array_merge()
     *  jezeli w b sa puste galezie to usetuje je w a
     * Wynik laduje w pierwszej tablicy
     *
     * It is static because Kernel uses it in it's constructor.
     * @author m.augustynowicz
     *
     * @param array $a referencja do pierwszej tablicy
     * @param array $b referencja do tablicy, ktora dolaczamy
     * @param bool $ignore_empty_strings if set, will ignore keys that have
     *             empty strings as values (NULL values will always be ignored)
     * @return void
     */
    public static function arrayMergeRecursive(&$a,$b, $ignore_empty_strings=true)
    {
        if (!is_array($a) || !is_array($b))
        {
            $a = $b;
            return;
        }
        foreach ($a as $k=>$v)
        {
            if(array_key_exists($k,$b))
            {
                if(((!$ignore_empty_strings) || $b[$k]!=='') && $b[$k]!==NULL)
                    self::arrayMergeRecursive($a[$k],$b[$k], $ignore_empty_strings);
                else
                    unset($a[$k]);
            }
        }
        foreach ($b as $k=>$v)
        {
            if (!array_key_exists($k,$a) && $v!==NULL)
            {
                if ((!$ignore_empty_strings) || ($v!==''))
                    $a[$k]=$v;
            }
        }

    }


    /**
     * Zwraca tekst ze znakami tylko z przedzialu [A-z0-9-_]
     * @author m.augustynowicz
     * @author m.izewski - zmienienie metody podmiany znakow diakretycznych oraz dodanie kropki do znakow dozwolonych
     * @param string $text
     * @return string
     */
    public function ASCIIfyText($text)
    {
        /*
        $text = iconv('UTF-8','ASCII//TRANSLIT',$text); // ain't cannons.
         */
        // polskie znaki diakrytyczne
        /*$text = strtr($text,array('ę'=>'e','ó'=>'o','ą'=>'a','ś'=>'s','ł'=>'l',
                                  'ż'=>'z','ź'=>'z','ć'=>'c','ń'=>'n','Ę'=>'E',
                                  'Ó'=>'O','Ą'=>'A','Ś'=>'S','Ł'=>'L','Ż'=>'Z',
                                  'Ź'=>'Z','Ć'=>'C','Ń'=>'N'));*/
        $text = $this->diacreticsToPlain($text);
        return preg_replace('/[^a-zA-Z0-9-_\.]/','_',$text);
    }

    /**
     * Makes HTML code tidy and pretty.
     * @uses tidy
     * @author m.augustynowicz
     *
     * @param string $string html code
     * @param boolean|array $config when boolean: specifies if function should
     *        return whole document (doctype, <html /> and such) or only body
     *        conents (tidy's show-body-only option,
     *        when array: tidy's configuration, reference:
     *        {@url http://tidy.sf.net/docs/quickref.html}
     * @return string pretty, pretty code
     */
    function tidyHTML($string, $config=false)
    {
        // if Tidy is not available for us.
        // (let's just hope it's not production environment)
        if (!class_exists('tidy',false))
        {
            if (g()->debug->allowed())
                g()->debug->addInfo('messy HTML',
                             '<a href="http://pl.php.net/tidy">Tidy</a> is ' .
                             'available, HTML may be messy.' );
            return $string;
        }

        static $tidy = null;
        if (is_bool($config))
        {
            $config = array(
                    'show-body-only' => ! $config,
                    'output-xhtml' => true
                );
        }

        if (!isset($config['input-encoding']))
            $config['input-encoding'] = 'utf-8';
        if (!isset($config['output-encoding']))
            $config['output-encoding'] = 'utf-8';

        if (null === $tidy)
            $tidy = new tidy();

        $tidy->parseString($string, $config);
        $tidy->cleanRepair();

        return (string) $tidy;
    }

    /**
     * Obcina tekst do podanej dlgosci, o ile ma wiecej znakow niz okreslono.
     * @author p.piskorski
     * @param string $text Tekst wejsciowy
     * @param integer $max_len Maksymalna dlugosc
     * @param string $concat Tekst do dopisania
     * @return string Obciety lancuch.
     */
    public function truncate($text, $max_len, $concat='…')
    {
        mb_internal_encoding('utf-8');
        if ($max_len && mb_strlen($text)>$max_len)
            return(mb_substr($text,0,$max_len-mb_strlen($concat)).$concat);
        else
            return($text);
    }

    /**
     * Wrapper for deprecated function name.
     *
     * Created 2009-07-20
     * @author m.augustynowicz
     * @todo remove this function when some time passes.
     */
    function truncate_html()
    {
        g()->debug->addInfo(null, 'Function %s() is deprecated ' .
                     'due it\'s incorrect name and will be deleted. ' .
                     'Use truncateHTML() instead.', __FUNCTION__ );
        $argv = func_get_args();
        return call_user_func_array(array($this,'truncateHTML'), $argv);
    }

    /**
     * Truncates HTML to requested length.
     *
     * If {@uses Tidy} is not available it will use {@uses truncateHTMLUgly}.
     * @author m.augustynowicz
     *
     * @todo it would be really smashy if someone make it so $length would
     *       define length of the string stripped of html tags..
     *
     * @param string $string html code to truncate
     * @param integer $length max length after truncating
     * @param string $suffix optional suffix to add to truncated string
     */
    function truncateHTML($string, $length, $suffix='&hellip;')
    {
        if (!class_exists('tidy',false) || !g()->conf['use_tidy'])
        {
            static $error_displayed = false;
            if (!$error_displayed)
            {
                $error_displayed = true;
                trigger_error('Tidy class is not present! We are very unpappy'
                        . ' about that, as we have to use less efficient method'
                        . ' method (this warning appears only once).',
                        E_USER_WARNING );
            }
            return $this->truncateHTMLUgly($string, $length, $suffix);
        }

        // any need to truncate?
        if (!$length)
            return $string;
        if ($length && strlen($string) <= $length)
            return $string;

        // count suffixes length in a intelligent way.
        $suffix_len = strlen(html_entity_decode($suffix));
        $length -= $suffix;

        // brutally truncate html
        $string = $this->truncate($string, $length, '');
        // .. and fix it.
        $string = $this->tidyHTML($string);

        if ($suffix)
            // insert before all closing tags
            return preg_replace('!((?:<[^>]+>\s*)*)$!', "$suffix\\1", $string, 1);
        else
            return $string;
    }

    /**
     * obcina tekst do poządanej długosci oraz zamyka znaczniki html
     *
     * The ugly implementation.
     * @author m.augustynowicz (googled up public domain code)
     *
     * @params string $string - tekst
     * @params int $length - do ilu skrocic  
     * 
     * @return string            
     */             
    function truncateHTMLUgly($string, $length=null, $addstring="")
    {
        if($length===null)
            $length = strlen($string);
        if (strlen($addstring))
            $addstring = " " . $addstring;
        
        if (strlen($string) > $length)
        {
            if( !empty( $string ) && $length>0 )
            {
                $isText = true;
                $ret = "";
                $i = 0;
                
                $currentChar = "";
                $lastSpacePosition = -1;
                $lastChar = "";
                
                $tagsArray = array();
                $currentTag = "";
                $tagLevel = 0;
                
                $noTagLength = strlen( strip_tags( $string ) );
                
                // Parser loop
                for( $j=0; $j<strlen( $string ); $j++ ) 
                {
                
                    $currentChar = substr( $string, $j, 1 );
                    $ret .= $currentChar;
                    
                    // Lesser than event
                    if( $currentChar == "<") $isText = false;
                    
                    // Character handler
                    if( $isText )
                    {
                    
                        // Memorize last space position
                        if( $currentChar == " " ) { $lastSpacePosition = $j; }
                        else { $lastChar = $currentChar; }
                    
                        $i++;
                    } 
                    else
                    {
                        $currentTag .= $currentChar;
                    }
                    
                    // Greater than event
                    if( $currentChar == ">" ) 
                    {
                        $isText = true;
                        
                        // Opening tag handler
                        if( ( strpos( $currentTag, "<" ) !== FALSE ) &&
                            ( strpos( $currentTag, "/>" ) === FALSE ) &&
                            ( strpos( $currentTag, "</") === FALSE ) ) 
                        {
                        
                            // Tag has attribute(s)
                            if( strpos( $currentTag, " " ) !== FALSE ) 
                            {
                                $currentTag = substr( $currentTag, 1, strpos( $currentTag, " " ) - 1 );
                            }
                            else
                            {
                                // Tag doesn't have attribute(s)
                                $currentTag = substr( $currentTag, 1, -1 );
                            }
                            
                            array_push( $tagsArray, $currentTag );
                        
                        } 
                        else if( strpos( $currentTag, "</" ) !== FALSE )
                        {
                            array_pop( $tagsArray );
                        }
                        
                        $currentTag = "";
                    }
                    
                    if( $i >= $length) 
                    {
                        break;
                    }
                }
                
                // Cut HTML string at last space position
                if( $length < $noTagLength ) 
                {
                    if( $lastSpacePosition != -1 ) 
                    {
                        $ret = substr( $string, 0, $lastSpacePosition );
                    } 
                    else 
                    {
                        $ret = substr( $string, $j );
                    }
                }
                
                // Close broken XHTML elements
                while( sizeof( $tagsArray ) != 0 ) 
                {
                    $aTag = array_pop( $tagsArray );
                    $ret .= "</" . $aTag . ">\n";
                }
                
            } 
            else
            {
                $ret = "";
            }
            
            // only add string if text was cut
            if ( strlen($string) > $length )
            {
                return preg_replace('!((?:<[^>]+>\s*)*)$!', "$addstring\\1", $ret, 1);
            }
            else
            {
                return ( $res );
            }
        }
        else
        {
            return ( $string );
        }
    }


    /**
     * Sprawdza czy zmienna jest typu calkowitego, badz jest stringiem
     * zawierajacym int
     *
     * @author m.augustynowicz
     * @param mixed zmienna
     * @return boolean wynik sprawdzenia
     */
    public function isInt($var)
    {
        // all /[0-9]+/
        return is_numeric($var) && ((string)(int)$var) === (string)$var;
    }

    /**
     * Checks if all given arguments are integers (or integer strings)
     * @author m.augustynowicz
     *
     * @param mixed ... values to be checked
     * @return boolean
     */
    public function areInts()
    {
        $argv = func_get_args();
        foreach ($argv as $arg)
        {
            if (!$this->isInt($arg))
                return false;
        }
        return true;
    }

    /**
     * Checks if all given arguments are floats (or float strings)
     * @author m.augustynowicz
     *
     * @param mixed ... values to be checked
     * @return boolean
     */
    public function areFloats()
    {
        $argv = func_get_args();
        foreach ($argv as $arg)
        {
            if (!is_float($arg) && !preg_match('/^[+-]?[0-9]*\.?[0-9]*$/', $arg))
                return false;
        }
        return true;
    }


    /**
     * Funkcja konwertujaca znaki diakretyczne UTF8 do zwyklych liter z alfabetu lacinskiego
     * (znaki ktorych niepotrafi przekonwertowac zostawia bez zmian)
     *
     * @param string $string - ciag znakow w UTF-8 do przeanalizowania
     * @return string - ciag znakow w UTF-8 z podmienionymi znakami
     *
     * @author m.izewski
     * @author m.augustynowicz
     * @version 1.0
     */
    public function diacreticsToPlain($string)
    {
        static $win = null;
        if (null === $win)
        {
            $win = DIRECTORY_SEPARATOR == '\\';
        }

        //locale musza byc obslugiwane przez serwer!!
        setlocale(LC_CTYPE, 'pl_PL.UTF8');

        $string1 = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        //wyzerowanie ciagu
        $string = '';
        //zapisanie nowego ciagu
        for ($i = 0; $i < strlen($string1); $i++)
        {
            $char1 = $string1[$i];
            $char2 = mb_substr($string, $i, 1);
            $string .= $char1=='?'?$char2:$char1;//jezeli znak nie zostal rozpoznany uzywamy oryginalnego znaku utf-8
        }

        //win32 hack - usuniecie "zmiekczen"...
        if ($win)
        {
            $string = str_replace(array("'","\""),"",$string);
        }

        return $string;
    }


    /**
     * Change keys of array to values of.. it's easier to explain by example:
     * array(0=>array('id'=>42), 1=>array('id'=>7), 2=>array('id'=>7, 'data'))
     * becomes
     * array(42=>array('id'=>42), 7=>array('id'=>7, 'data'))
     * (yes. duplicates get overwritten)
     * @m.augustynowicz
     *
     * @param array $arr reference to array to "re-keyify"
     * @param mixed $key what key to use
     * @param mixed $store_index_in save old index value in new array under
              this key. null to disable
     * @return array|boolean array with keys, or false on error
     */
    public function changeKeys(&$arr, $key='id', $store_index_in=null)
    {
        if (!is_array($arr))
            return false;

        $keys = array();
        $new_arr = array();
        foreach ($arr as $i => $v)
        {
            $new_arr[$v[$key]] = $v;
            if ($store_index_in)
            {
                $new_arr[$v[$key]][$store_index_in] = $i;
            }
            $keys[] = $v[$key];
        }
        $arr = $new_arr;
        return $keys;
    }


    /**
     * Zwraca autora kodu wywolujacego te funkcje. Znajduje pierwsza dokumentacyjna deklaracje
     * autora przed linia wywolujaca blame(), o ile nie podano tablicy ze sladem wywolan.
     * @param array $backtrace Tablica ze sladem wywolan, domyslnie null, wtedy funkcja sama pobiera slad.
     * @param integer $level Indeks z tablicy sladu, z ktorego ma korzystac funkcja.
     * @return mixed string z nazwa autora albo false jezeli nie zostanie okreslony.
     * @author p.piskorski
     * @author m.augustynowicz opcjonalna obsługa svn'owego blame'a
     */
    public function blame($backtrace=null,$level=2)
    {
        if ($backtrace===null)
            $backtrace=debug_backtrace();
        $file = $backtrace[$level]['file'];
        $line = $backtrace[$level]['line'];
        if (is_dir('./.svn') && is_readable('./.svn') && (!isset(g()->conf['svn_blame_disable']) || !g()->conf['svn_blame_disable'] ))
        {
            $cmd = sprintf('svn blame --non-interactive -x "--ignore-space-change --ignore-all-space --ignore-eol-style" "%s" | head -n%d | tail -n1 2>&1', $file, $line );
            $auth = exec($cmd, $out, $ret);
            $auth = array($auth);
        }

        if (!isset($auth)) // svn not available or failed
        {
            $auth = $this->author($file, $line);
        }

        return($auth);
    }


    /**
     * Zwraca autora metody, w ktorej zawiera sie linia kodu w podanym pliku.
     *
     * @todo unused (marked 2010-05-20), use or remove
     *
     * @param string $file Plik zrodlowy.
     * @param integer $line Numer linii.
     * @return array tablica z autorami metody
     * @author p.piskorski
     */
    public function author($file, $line)
    {
        $regexp = '/\s*\*\s*@author\s*(.*)$/';
        $authors = array();
        $code = file($file);
        $found = false; // seeking for '@author'
        for ( ; $line>0 ; $line-- )
        {
            if (FALSE==$found)
            {
                if (preg_match($regexp,$code[$line],$matches))
                {
                    $found = TRUE;
                    $authors[] = $matches[1];
                    continue;
                }
            }
            else
            {
                if (!preg_match($regexp,$code[$line],$matches))
                    break;
                $authors[] = $matches[1];
            }
        }
        return array_reverse($authors);
    }

    /**
     * Metoda do budowania stronicaowania
     *
     * @todo remove this, deprecated since 2010-05-20
     *
     * @param integer $count - ile wszystkich rekordów
     * @param integer $on_page - po ile na stronie
     * @param integer $current_page - aktualna strona
     * @params integer (nieparzysta) $show_page - ilość stron do pokazania + jeśli liczba będzie parzysta metoda podniesie ją o jeden (domyślnie 3) np.: ... 5 6 7 ...
     *                              aby wyświetlić wszytkie trony parametr ustawiś na null
     * @param additional_params - opcjonalny array z :
     *                  go_to - czy wyświetlić pole "idź do"? (leży w gestii szablonu)
     *                  page_key - nazwa klucza z numerem strony, domyślnie: "page"
     *
     * @return array(
                'pages' => array ( "numer", "numer", ... ),
                'pages_count' => sizeof(pages),
                'current_page' => "numer aktualnie wybranej strony",
                'offset' => "od której pozycji ma brać rekordy z bazy",
                'additional_params' => array ( "przekazane z parametru funkcji" )
    * @version 1.0
    * @author j.juszkiewicz
    */
    public function selectPage($count,$on_page,$current_page,$show_page=3,$additional_params=array('go_to'=>false,'page_key'=>'page'))
    {
        if($current_page=='') $current_page = 1;
        $return = array();
        $return['current_page'] = $current_page;
        $return['additional_params'] = $additional_params;
        $return['offset'] = ($current_page-1)*$on_page;
        $pages_count = ceil($count/$on_page);
        $return['pages_count'] = $pages_count;

        if($show_page==null)
        {
            for($i=1; $i<=$pages_count; $i++)
            {
                $return['pages'][] = $i;
            }
        }
        else
        {
            if($show_page%2==0) ++$show_page;
            //ile stron po bokach
            $left_right_pages = floor($show_page/2);
            //sytuacja kiedy aktualna strona jest mniejsza od ilość stron po lewej np [1]2 3...
            if($current_page<=$left_right_pages)
            {
                $stop = min($show_page,$pages_count);
                for($i=1; $i<=$stop; $i++)
                {
                    $return['pages'][] = $i;
                }
            }
            // ... 56 57 [58]
            elseif($current_page>($pages_count-$left_right_pages))
            {
                $start = (($pages_count-$show_page+1)<=0)? 1 : $pages_count-$show_page+1;
                for($i=$start; $i<=$pages_count; $i++)
                {
                    $return['pages'][] = $i;
                }
            }
            // ...7[8]9...
            else
            {
                $start = $current_page-$left_right_pages;
                $stop = $current_page+$left_right_pages;
                for($i=$start; $i<=$stop; $i++)
                {
                    $return['pages'][] = $i;
                }
            }
        }
        return $return;
    }

    /**
     * Funkcja zamieniajaca timestamp z bazy danych na unixtime
     *
     * @param string $timestamp - timestamp w formacie SQL
     * @return integer - unixtime
     *
     *  @version 1.0
     *  @author m.izewski
     */
    public function timestampToUnixtime($timestamp)
    {
        //przypozadkowanie do odpowiedniego formatu
        //timestamp format
        if (!preg_match('/^(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2}).*$/', $timestamp, $matches))
            //funkcja zwraca null jezeli podany format daty nie jest obslugiwany
            return null;
        $unixtime = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
        return $unixtime;
    }

    /**
     * Funkcja zmieniajaca date z bazy danych na unixtime
     *
     * @param string $date - data w formacie SQL
     * @return integer - unixtime
     *
     * @author m.izewski
     * @version 1.0
     */
    public function dateToUnixtime($date)
    {
        //przypozadkowanie do odpowiedniego formatu
        //date format
        if(!preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $date, $matches))
            //funkcja zwraca null jezeli podany format daty nie jest obslugiwany
            return null;
        $unixtime = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
        return $unixtime;
    }


    /**
     * Format date in variety of ways.
     *
     * @uses conf[locale][date format] for obtaining date formats, used keys:
     *      for default value of $format argument:
     *          default
     *      for custom formats:
     *          human date, human time, human date+time
     *          sortable date, sortable date+time
     *          sql
     *      for defining day offsets (like 'today', 'tomorrow' etc.)
     *          day offsets
     *      see Hologram's conf.locale.php for some examples
     *
     * @todo allow array(">7"=>"more than week ago") in [day offsets]
     *
     * @author m.augustynowicz
     *
     * @param integer|string|null $date integer (or integer-ish string) with
     *        timestamp or string parseable by strtotime() or ==null for now.
     * @param integer|string $format date format (binary OR of DATE_*_FORMAT
     *        and DATE_SHOW_*), you can also specify strftime() accepted string
     *        but it's not really preffered (maybe you shoud add DATE_*_FORMAT?)
     *        default: DATE_HUMAN_FORMAT|DATE_SHOW_ALL.
     * @param Component $that component used to translate custom dates.
     *        If none given -- Kernel::first_controller->displayingCtrl() is used
     * @return string empty string is returned on non-parseable $date
     */
    public function formatDate($date=null, $format=null, $that=null)
    {
        $conf = & g()->conf['locale']['date format'];

        // don't forget to change these, when chaning method's defaults!!
        $default_values = array(
                'date'=>null,
                'that'=>null
            );
        if (!$default_values['format'] = @$conf['default'])
            $default_values['format'] = 35;
        // $that can be passed as any parameter.
        foreach ($default_values as $arg => $default_value)
        {
            if ('that' !== $arg && is_object($$arg))
            {
                $that = $$arg;
                $$arg = $default_value;
            }
        }

        //windows servers do not support '%e' in data formats, so it is switched to '%d'
        if(strpos($_SERVER['SERVER_SOFTWARE'],'Win32'))
            $windows = true;
		else
			$windows = false;
        
        // first four bits
        if (!$parts   =  ((1<<4) - 1)     & $format)
            $parts = DATE_SHOW_ALL;
        // next four bits
        if (!$variant = (((1<<4) - 1)<<4) & $format)
            $variant = DATE_HUMAN_FORMAT;

        if (!$that)
            $that = g()->first_controller->displayingCtrl();

        // only Component has trans() method
        if (!($that instanceof Component))
            throw new HgException('Non-component object passed as translator!');

        //var_dump($date,$format,$parts,$variant,$that->path());

        if (null == $date)
            $date = time();

        // if $date is string-ish, then it have to be a timestamp
        if (!$this->isInt($date))
            if (false === ($date = strtotime($date)))
                return '';

        //var_dump($date, strftime('%c', $date));

        $string = ''; // to be returned

        switch ($variant)
        {
            // sortable date (all or date only)
            case DATE_SORTABLE_FORMAT :
                if (DATE_SHOW_DATE === $parts)
                {
                    if (!$format = @$conf['sortable date'])
                        $format = '%Y-%m-%d';
                }
                else
                {
                    if (!$format = @$conf['sortable date+time'])
                        $format = '%Y-%m-%d %H:%i:%s';
                }
                $string = strftime($format, $date);
                break;

            // sql format
            case DATE_SQL_FORMAT : // parts ignored
                if (!$format = @$conf['sql'])
                    $format = DATE_ATOM;
                $string = strftime($format, $date);
                break;

            // human-readable format
            // warning: it uses locale! (LC_TIME)
            case DATE_HUMAN_FORMAT :
            default :
                // human readable date with time format
                static $datetime_f = '%s at %s'; 

                // in some cases we know better, than LC_TIME:
                // language => array(full format, date, time)
                static $human_formats = array(
                    'pl'    => array(
                            'human date+time' => '%A, %e %B %Y %X',
                            'human date' => '%A, %e %B %Y',
                            'human time' => '%X'
                        ),
                    'from locale' => array(
                            'human date+time' => '%c',
                            'human date' => '%x',
                            'human time' => '%X'
                        ),
                );
                if($windows)
                    $human_formats['pl']['human date+time'] = '%A, %d %B %Y %X';

                $lang = g()->lang->get();
                $format = array();
                foreach ($human_formats['from locale'] as $key => &$v)
                {
                    if (!$format[$key] = @$conf[$key])
                    {
                        if (!$format[$key] = @$human_formats[$lang][$key])
                            $format[$key] = $v;
                    }
                }
                unset($v);

                // special days.
                // if you add something here -- don't forget to translate!
                if (null === $days = @$conf['days offsets'])
                {
                    $days = array(
                            -2 => 'two days ago',
                            -1 => 'yesterday',
                             0 => 'today',
                             1 => 'tomorrow',
                             2 => 'day after tomorrow'
                         );
                }
                
                switch ($parts)
                {
                    case DATE_SHOW_TIME :
                        $string = strftime($format['human time'], $date);
                        break;

                    // full-date and date-only are similar
                    // both of them print 'today', 'yesterday' etc.
                    case DATE_SHOW_ALL :
                    case DATE_SHOW_DATE :
                    default :
                        // detect 'today', 'yesterday' etc.
                        $date_date = strftime('%Y-%m-%d', $date);
                        foreach ($days as $i => $day)
                        {
                            $time = strtotime($i.' day');
                            if (strftime('%Y-%m-%d', $time) === $date_date)
                            {
                                $string = $that->trans($day);
                                break;
                            }
                        }

                        // only date
                        if (DATE_SHOW_DATE === $parts)
                        {
                            if (!$string) // did not detect 'today' etc.
                                $string = strftime($format['human date'], $date);
                        }
                        // date with time
                        else if ($string) // we already have the date
                            $string = $that->trans($datetime_f,
                                             $string, strftime($format['human time'], $date) );
                        else // whole date in locale-preffered format
                            $string = strftime($format['human date+time'], $date);
                        break;
                }
                break;
        }

        return $string;
    }

    
    /**
     * Gets caller object.
     *
     * Works only if debug is allowed. Params can be passed in any order.
     * @author m.augustynowicz
     *
     * @todo sometimes it does not work (e.g. when there's no caller object)
     *
     * @param int $backtrace_offset if $caller is not given and is taken from
     *        debug_backtrace, you can specify additional offset (default: 0)
     * @param string $key if you want to return only one key from backtrace
     * @return mixed row from debug_back
     */
    public function getCaller($backtrace_offset=0, $key=null)
    {
        if (!g()->debug->allowed())
            return false; // GO AWAY!

        if (!is_int($backtrace_offset)) // swap!
        {
            $foo = $backtrace_offset;
            $backtrace_offset = $key;
            $key = $foo;
        }

        $backtrace = debug_backtrace();
        $calling_bt = $backtrace[2 + $backtrace_offset];
        if (empty($key))
            return $calling_bt;
        elseif (isset($calling_bt[$key]))
            return $calling_bt[$key];
        else
            return null; // no value
    }

    /**
     * Translates passed argument from human to boolean.
     *
     * New cases (that make sense) are welcome.
     * @author m.augustynowicz
     *
     * @param boolean|int|string $val
     * @param mixed $def default value when I can't decide
     * @return mixed true/false or $def
     */
    public function anyToBool($val, $def=null)
    {
        if (is_bool($val) || is_int($val))
            return (bool) $val;

        switch (strtolower($val))
        {
            case 'on' : case 'true' : case 'yes' :
            case 'enable' : case 'enabled' :
            case '1' : case 't' : case 'y' :
            case 'tak' : // polish?
                return true;
                break;
            case 'off' : case 'false' : case 'no' :
            case 'disable' : case 'disabled' :
            case '0' : case 'f' : case 'n' : case '' :
            case 'nie' : // polish?
                return false;
                break;
            default:
                return $def;
                break;
        }
    }

    /**
     * Makes string CamelCase.
     *
     * Read the code, it's self-explanatory. [;
     * @author m.augustynowicz
     * 
     * @param string $string to camelify.
     * @param string $word_splitters characters used as non-word
     * @return string Camel
     */
    public function camelify($string, $word_splitters='A-Za-z0-9')
    {
        $string = preg_replace("/[^$word_splitters]/", ' ', $string);
        $string = ucwords($string);
        return str_replace(' ', '', $string);
    }


    /**
     * Converts given value to float and is locale-aware.
     * @author m.augustynowicz
     *
     * @param mixed $value value to convert to float
     * @param boolean $return_locale_unaware_string should I return float
     *        number or locale-unaware string? (with "." as decimal point)
     * @return float|string|boolean float or string depending on
     *         second parameter, or false if invalid value provided
     */
    public function floatVal($value, $return_locale_unaware_string=false)
    {
        if (is_string($value))
        {
            $locale_info = localeconv();
            $point = $locale_info['decimal_point'];

            $value = str_replace($point, '.', $value);
        }

        if (!$this->areFloats($value))
            return false;

        if ($return_locale_unaware_string)
            return sprintf('%F', $value);
        else
            return (float) $value;
    }

    /**
     * Deletes given file or directory with all directories and files inside its.
     * @author m.jutkiewicz (in fact - taken from php.net comments to 'rmdir' function ;))
     *
     * @param string $path path to directory which will be recursive deleted
     */
    public function rmrf($path)
    {
        if (g()->debug->allowed())
            printf('<p class="debug">deleting <code>%s</code>', $path);
        if(is_file($path))
            unlink($path);
        elseif(is_dir($path))
        {
            $files = scandir($path);

            foreach($files as $file)
            {
                if($file !== '.' && $file !== '..')
                {
                    if(is_dir($path.'/'.$file))
                    {
                        if(count(glob($path . '/' . $file . '/*')) > 0)
                            $this->rmrf($path . '/' . $file);
                        else
                            rmdir($path . '/' . $file);
                    }
                    else
                        unlink($path . '/' . $file);
                }
            }

            rmdir($path);
        }
    }


    /**
     * Build xml attrute list from associative array.
     * @author m.augustynowicz
     *
     * @param array $attr
     * @return string
     */
    public function xmlAttr(array $attr)
    {
        foreach ($attr as $a => &$val)
        {
            if ($val === false)
            {
                // skip
                continue;
            }
            else if ($val === true)
            {
                // boolean true attribute
                $val = htmlspecialchars($a);
            }
            else
            {
                if (is_array($val))
                {
                    $val = json_encode($val);
                }

                if (false !== strpos($val, '"'))
                {
                    $val = htmlspecialchars($val, ENT_QUOTES); // escape both quotes
                    $val = htmlspecialchars($a) . "='" . $val . "'";
                }
                else
                {
                    // surround with double quotes
                    $val = htmlspecialchars($val, ENT_COMPAT); // escape only double quotes
                    $val = htmlspecialchars($a) . '="' . $val . '"';
                }
            }
        }
        return join(' ', $attr);
    }


    /**
     * Build xml tag
     * @author m.augustynowicz
     *
     * @param string $name
     * @param array $attr
     * @param null|string $value passing no value renders <tag />
     * @param string $type when rendering non-self-closing tag, can specify
     *        "open", "close" or "both"
     * @return string
     */
    public function tag($name, $attr=array(), $value=null, $type='both')
    {
        if (is_array($attr))
        {
            $attrs    = $this->xmlAttr($attr);
            $no_value = (func_num_args() <= 2);
        }
        else
        {
            $type  = $value;
            $value = $attr;
            $attrs = '';
            $no_value = (func_num_args() <= 1);
        }


        switch (strtolower($type))
        {
            case 'open' :
                if ($no_value)
                {
                    $fmt = '<%s ';
                }
                else
                {
                    $fmt = '<%s %s>';
                }
                break;
            case 'close' :
                if ($no_value)
                {
                    $fmt = '/>';
                }
                else
                {
                    $fmt = '</%s>';
                }
                break;
            default :
                if ($no_value)
                {
                    $fmt = '<%s %s />';
                }
                else
                {
                    $fmt = '<%s %s>%s</%1$s>';
                }
        }

        return sprintf($fmt, $name, $attrs, $value);
    }


    /**
     * Set offset for generating unique ids
     * @author m.augustynowicz
     *
     * @param int $offset
     * @return int old offset
     */
    public function setUniqueIdOffset($offset)
    {
        $old = self::$_unique_id_offset;
        self::$_unique_id_offset = $offset;
        return $old;
    }


    /**
     * Generates unique (sequencial) id
     * @author m.augustynowicz
     *
     * @param bool $increment increment interlal id counter after returning
     * @return string
     */
    public function uniqueId($increment=true)
    {
        $id = sprintf('hg%s', self::$_unique_id_offset);
        if ($increment)
        {
            self::$_unique_id_offset++;
        }
        return $id;
    }


    /**
     * Unified way for execucmdting UNIX commands
     * @author m.augustynowicz
     *
     * @uses conf[unix]
     *
     * @param string $cmd see PHPs exec() for reference,
     *        can also be key in conf[unix], then cmd path may be modified
     * @param string $args list of argument
     * @param string $output see PHPs exec() for reference
     * @param string $return_code see PHPs exec() for reference
     *
     * @return false|string last line of the output;
     *         false on any error
     */
    public function exec($cmd, $args, & $output=null, & $return_code=null)
    {
        $all_conf = & g()->conf['unix'];
        $hg_args = '';
        if (array_key_exists($cmd, $all_conf))
        {
            $conf = & $all_conf[$cmd];
            if (false === $conf)
                return false;
            if (isset($conf['path']))
                $cmd = $conf['path'];
            if (isset($conf['args']))
                $hg_args = $conf['args'];
            if (isset($conf['args_args']))
                $args = vsprintf($args, $conf['args_args']);
        }


        $cmd = sprintf('%s %s %s 2>&1', $cmd, $hg_args, $args);

        if (g()->debug->allowed())
        {
            echo '<pre class="shell">';
            printf("<span class=\"cmd\"><span class=\"PS1\">%s $</span> %s</span>\n", getcwd(), $cmd);
        }
        $last_line = exec($cmd, $output, $return_code);
        if (g()->debug->allowed())
        {
            printf("<span class=\"output\">%s</span>\n", join("\n", $output));
            printf('<small class="return_code">(returned %s)</small>', $return_code);
            echo '</pre>';
        }
        return $last_line;
    }

    /**
     * obfuscates all e-mails in given text
     * @author b.matuszewski
     * @param string $html text to obfuscate e-mails in
     *
     * USEGE
     *   to obfuscate e-mails in html string use this method
     *
     *   if you want to automagically decode obfuscated e-mails in javascript,
     *   make sute this is eval'd at every page load:
     *     $('.obfuscated').mouseover(function()
     *     {
     *         var me = $(this);
     *         hg('ajax')({
     *             url: '{$t->url2c('Obfuscate', 'ajaxDecode')}',
     *             data: {0: me.attr('id'), 1: me.attr('name')}
     *         });
     *     });
     *
     */
    public function obfuscateEmails(& $html)
    {
        preg_match_all($this->_email_reg_exp, $html, $matches);
        foreach($matches[0] as $email)
        {
            $coded = $this->rc4Encrypt($this->_email_obfuscate_key, $email);
            $coded2 = '';
            for($i = 0; $i < strlen($coded); $i++)
            {
                $tmp_str = dechex(ord($coded[$i]));
                while(strlen($tmp_str) < 2)
                    $tmp_str = '0'.$tmp_str;
                $coded2 .= $tmp_str;
            }

            list($user_name) = explode('@', $email);
            $html = str_replace($email, sprintf('<span id="%s" class="obfuscated" name="%s">%s@...</span><noscript>%s</noscript>', $this->uniqueId(), $coded2, $user_name, $this->trans('To see this e-mail enable JavaScript!')), $html);
        }
    }
    
    public function getEmailObfuscateKey()
    {
        return $this->_email_obfuscate_key;
    }

    /**
     * codes/decodes given string using rc4 algorithm (symetrical encrypion)
     * @author b.matuszewski
     *
     * @param string $key - encrypion key
     * @param string $pt - string to encrypt/decrypt
     *
     * @returns string - encrypted/decrypted string
     */
    public function rc4Encrypt($key, $pt)
    {
        $s = array();
        for ($i=0; $i<256; $i++)
            $s[$i] = $i;
        $j = 0;
        $x;
        for ($i=0; $i<256; $i++)
        {
            $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
        }
        $i = 0;
        $j = 0;
        $ct = '';
        $y;
        for ($y=0; $y<strlen($pt); $y++)
        {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
            $ct .= $pt[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
        }
        return $ct;
    }
    
    /**
     * generates <a target="_blank"></a> tag for given url
     * if the url does not start with 'http://' or 'https://'
     * the 'http://' prefix is added to href="" attr
     *
     * @author b.matuszewski
     *
     * @param string $url - url
     * @param null|string - value of <a></a> tag, if null $url will be taken instead
     *
     * @returns string - generated <a></a> tag
     */
    public function externalUrl($url, $label=null)
    {
        if(!preg_match('!(^http://)|(^https://)!', $url))
            $href = 'http://' . $url;
        else
            $href = $url;
        $target = '_blank';
        $attr = compact('href', 'target');
        $value = $label ? $label : $url;
        return $this->tag('a', $attr, $value);
    }


    /**
     * Format numeric bytes into suffix form.
     * @author m.augustynowicz
     * @url http://stackoverflow.com/questions/2510434/php-format-bytes-to-kilobytes-megabytes-gigabytes/2510459#2510459
     *
     * @param float $bytes
     * @param int $precision
     *
     * @return string
     */
    public function formatBytes($bytes, $precision = 2)
    {
        static $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }


    /**
     * Parsee human-readable format of filesize/diskspace.
     * @author m.augustynowicz
     *
     * @param string $string
     *
     * @return bool|float false on errors
     */
    public function parseBytes($string)
    {
        $string = trim(strtoupper($string));
        if (!preg_match('/^(.*)([A-Z])B?$/', $string, $matches))
        {
            return false;
        }

        static $units = array('K'=>1, 'M'=>2, 'G'=>3, 'T'=>4);

        $num = (float) $matches[1];
        $unit = $matches[2];

        if (!isset($units[$unit]))
        {
            return false;
        }
        else
        {
            return $num * (1 << (10 * $units[$unit]));
        }
    }


    /**
     * Method selects correct Polish grammatic form of words.
     * Example:
     * 1 pozycja, 2 pozycje, 5 pozycji, 22 pozycje, 32 pozycje, but: 12 pozycji
     * @author m.jutkiewicz
     *
     * @param integer $number
     * @param array $forms Array should contain three elements with exactly these keys: 1, 2, 5, e.g.:
     * array(
     *     1 => 'pozycja',
     *     2 => 'pozycje',
     *     5 => 'pozycji',
     * )
     */
    public function correctForm($number, array $forms)
    {
    	if(!array_key_exists(1, $forms) || !array_key_exists(2, $forms) || !array_key_exists(5, $forms))
    		throw new HgException('Incorrect using of Functions::correctForm()');

		if($number == 0)
			return $forms[5];

		if($number == 1)
			return $forms[1];

		$last_digits = (string)$number;
		$last_digits_arr = array(
			0 => (int)$last_digits[strlen($last_digits) - 1],
		);

		if(strlen($last_digits) < 2)
		    $last_digits_arr[1] = 0;
	    else
		    $last_digits_arr[1] = (int)$last_digits[strlen($last_digits) - 2];

	    $last_digits = &$last_digits_arr;

		switch($last_digits[1])
		{
			case 1:
				return $forms[5];
			default:
				switch($last_digits[0])
				{
					case 2: case 3: case 4:
						return $forms[2];
					default:
						return $forms[5];
				}
		}
    }
}
