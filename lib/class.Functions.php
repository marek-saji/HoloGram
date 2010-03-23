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
     * @author m.augustynowicz
     *
     * @param array $a referencja do pierwszej tablicy
     * @param array $b referencja do tablicy, ktora dolaczamy
     * @return void
     */
    function arrayMergeRecursive(&$a,$b)
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
                if($b[$k]!=='' && $b[$k]!==NULL)
                    $this->arrayMergeRecursive($a[$k],$b[$k]);
                else
                    unset($a[$k]);
            }
        }
        foreach ($b as $k=>$v)
        {
            if (!array_key_exists($k,$a) && $v!=='' && $v!==NULL)
            {
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
        // if Tidy is not avaliable for us.
        // (let's just hope it's not production environment)
        if (!class_exists('tidy',false))
        {
            if (g()->debug->allowed())
                g()->debug->addInfo('messy HTML',
                             '<a href="http://pl.php.net/tidy">Tidy</a> is ' .
                             'avaliable, HTML may be messy.' );
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
     * If {@uses Tidy} is not avaliable it will use {@uses truncateHTMLUgly}.
     * @author m.augustynowicz
     *
     * @todo it would be really smashy if someone make it so $length would
     *       define length of the string stripped of html tags..
     *
     * @param string $string html code to truncate
     * @param integer $length max length after truncating
     * @param string $suffix optional suffix to add to truncated string
     */
    function truncateHTML($string, $length=null, $suffix='&hellip;')
    {
        if (!class_exists('tidy',false))
            return $this->truncateHTMLUgly($string, $length, $suffix);

        // any need to truncate?
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
                return( $ret.$addstring );
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
        // trully integer
        if (is_int($var))
            return TRUE;
        // integer in a string
        return ((string)(int)$var) === $var;
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
            if (!is_float($arg) && !preg_match('/[0-9]*\.?[0-9]*/', $arg))
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
     * @version 1.0
     */
    public function diacreticsToPlain($string)
    {
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
        if(strpos($_SERVER['SERVER_SOFTWARE'],'(Win32)'))
            $string = str_replace(array("'","\""),"",$string);

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

        if (!isset($auth)) // svn not avaliable or failed
        {
            $auth = $this->author($file, $line);
        }

        return($auth);
    }


    /**
     * Zwraca autora metody, w ktorej zawiera sie linia kodu w podanym pliku.
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
                        $format = 'Y-m-d';
                }
                else
                {
                    if (!$format = @$conf['sortable date+time'])
                        $format = 'Y-m-d H:i:s';
                }
                $string = date($format, $date);
                break;

            // sql format
            case DATE_SQL_FORMAT : // parts ignored
                if (!$format = @$conf['sql'])
                    $format = DATE_ATOM;
                $string = date($format, $date);
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
            $val = htmlspecialchars($a).'="'.htmlspecialchars($val).'"';
        return join(' ', $attr);
    }


    /**
     * Build xml tag
     * @author m.augustynowicz
     *
     * @param string $name
     * @param array $attr
     * @param null|string $value passing no value renders <tag />
     * @return string
     */
    public function tag($name, array $attr=array(), $value=null)
    {
        if (func_num_args() > 2)
            $fmt = '<%s %s>%s</%1$s>';
        else
            $fmt = '<%s %s />';

        return sprintf($fmt, $name, $this->xmlAttr($attr), $value);
    }


    /**
     * Generates unique id (for use in html)
     * @author m.augustynowicz
     *
     * @param string $id id prefix
     * @param null|integer $set_offset if given, sets starting offset for this
     *        and future ids (in general: do not use!)
     * @return string
     */
    public function uniqueId($id, $set_offset=null)
    {
        if (null !== $set_offset)
            self::$_unique_id_offset = $set_offset;
        if (!$id)
            $id = 'hgid';
        $id = sprintf('%s__%s', $this->ASCIIfyText($id),
                                ++self::$_unique_id_offset );
        return $id;
    }

}

