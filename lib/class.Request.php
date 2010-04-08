<?php

/**
 * Obsluga adresu określającego komponenty, akcje i parametry.
 *
 * Przechowuje informacje o adresie biezacego wywolania. Obiekt tworzony jest
 * przez kernel i przekazywany do kolejnych zagniezdzonych kontrolerow. Kazdy 
 * kontroler musi zakladac, ze ma do wykonania kilka akcji (wlasnych albo swoich
 * podkomponentow). Dlatego na zastanym poziomie powinien iterowac tak dlugo,
 * az Url::next() zwraci false. Przed wywolaniem akcji komponentu podrzednego nalezy
 * wykonac Url::dive(), ktore zaglebia sie o jeden poziom. Po zakonczeniu 
 * podwywolania nalezy wykonac Url::emerge(), aby wrocic na wlasny poziom. 
 * Funkcje next(), dive() i emerge() umozliwiaja przeskanowanie drzewiastej struktury
 * jaka moze miec zapytanie.
 *
 * Klasa trzyma zparsowane drzewo oraz dwa "wskaźniki": jeden wskazuje na aktualnie
 * obsługiwany element (wywoływanie {@see next()} zwraca kolejne jego podwęzły), oraz
 * element ostatnio zwrócony przez next()/current() (false zaraz po wejściu do węzła)
 * 
 * example:
 * <code>
 * http://example.com/users/edit:42,p=5,xx=7
 * parses into
 * array(
 *   children => array(
 *     users => array(
 *       children => array(
 *         edit => array(
 *           children => array(),
 *           params => array(
 *             0  => 42,
 *             p  => '5',
 *             xx => '7',
 *           ),
 *         ),
 *       ),
 *       params => array(),
 *     )
 *   )
 *   params => array(),
 * )
 *
 * http://example.com/users/save;users/edit:42;debug:on;foo/view:table,7;
 * array(
 *   children => array(
 *     users => array(
 *       children => array(
 *         save => array(
 *           children => array(),
 *           params   => array(),
 *         ),
 *         edit => array(
 *           children => array(),
 *           params => array(
 *             0 => '42'
 *           )
 *         ),
 *       ),
 *       params => array(),
 *     ),
 *     foo => array(
 *       children => array(
 *         view => (
 *           children => arary(),
 *           params => array(
 *             0 => 'table',
 *             1 => '7'
 *           )
 *         ),
 *       ),
 *       params => array(),
 *     ),
 *     debug => array(
 *       children => array(),
 *       params => array(
 *         0 => 'on',
 *       ),
 *     ),
 *   ),
 * )
 * </code>
 *
 * @author m.augustynowicz
 * @package hologram2
 */
class Request extends HgBase
{
    static $singleton = false;

    /**
     * Protokół podany w adresie URL
     * @var string
     */
    protected $_protocol = '';

    /**
     * Określa czy zapytanie było AJAXem
     * @var boolean
     */
    protected $_is_ajax = false;

    /**
     * Is request got from $_SERVER?
     * @var boolean
     */
    protected $_from_server = true;

    /**
     * URL from address bar
     * @var string
     */
    protected $_given_url = '';

    /**
     * Host podany w adresie URL
     * @var string
     */
    protected $_host = '';

    /**
     * Port podany w adresie URL
     * @var integer
     */
    protected $_port = 80;

    /**
     * Base Uri. First part of request uri not parsed to {@uses $_tree}.
     * @var string
     */
    protected $_base_uri = '';

    /**
     * stuff after base_uri
     */
    protected $_url_path = '';

    /**
     * Część z adresu URL, która właściwie i tak jest w $_GET
     * @var string
     */
    protected $_query = '';
    
    /**
     * Sparsowane drzewo komponentów, akcji etc.
     * @var null|array
     */
    protected $_tree = null;

    /**
     * Referencja do aktualnie obsługiwanego węzła w $_tree
     * @var mixed referencja
     */
    protected $_rtree = null; // referencja

    /**
     * Klucz w $_rtree w skazujący na aktualnie obsługiwany element w węźle
     * @var false|string
     */
    protected $_current = NULL; // klucz w $_rtree, z którego skorzysta next/dive/emerge

    /**
     * Ścieżka prowadząca do $_rtree w $_tree
     *
     * np.
     * <code>
     * array('view','users','add')
     * </code>
     * @var array
     */
    protected $_path_to_rtree = null; // ścieżka w postaci kolejnych kluczy

    /**
     * If conf[link_split] is not being encoded by urlencode,
     * it's encoded form should be stored here (in constructor)
     * @var null|string
     */
    protected $_link_split_encoded;


    /**
     * Konstruktor parsujący adres URL przy pomocy php-owego parse_url()
     * oraz {@uses _parse()}.
     * @author m.augustynowicz
     *
     * @param string $text_url adres URL, domyślnie brany jest aktualny adres
     *                         złożony ze zmiennej $_SERVER
     * @param string $base_uri początek adresu URL, który ma być ignorowany,
     *                         zwykle (i domyślnie) pusty.
     * @return void
     */
    public function __construct($text_url = NULL, $base_uri = '')
    {
        if (!$text_url)
        {
            $text_url = (@$_SERVER['HTTPS'] ? 'https://' : 'http://' ).
                         $_SERVER['HTTP_HOST'] .
                         $_SERVER['REQUEST_URI']; // request_uri starts with slash

            $this->_is_ajax = @'xmlhttprequest' === strtolower(@$_SERVER['HTTP_X_REQUESTED_WITH']);

            if (!$base_uri)
                $base_uri = dirname($_SERVER['SCRIPT_NAME']);
        }
        else
        {
            $this->_is_ajax = false;
            $this->_from_server = false; // was not generated from $_SERVER
        }

        $this->_given_url = $text_url;
        $this->_base_uri = rtrim($base_uri, '/\/').'/';

        if (g()->conf['link_split'] == urlencode(g()->conf['link_split']))
            $this->_link_split_encoded =
                            '%'.strtoupper(dechex(ord(g()->conf['link_split'])));

        $url = parse_url($this->_given_url);
        $this->_protocol = @$url['scheme'];
        $this->_host = @$url['host'];
        if (@$url['port'])
            $this->_port = $url['port'];
        $this->_query = @$url['query']; // w sumie to jest w $_GET

        $this->_url_path = '/'.trim($url['path'], '/');
        if ($this->_base_uri)
        {
            $this->_url_path = preg_replace('/^'.preg_quote($this->_base_uri,'/').'/',
                                     '', $this->_url_path);
        }

        $this->_diminishURL($this->_url_path);
        $this->__buildTree($this->_url_path);
    }


    /**
     * Adds action to the request.
     *
     * @param mixed $ctrl Either a slash separated string with the controller path, or a targer controller object
     * @param string $action name of the action
     * @param mixed $params action's parameters
     * @param bool should params be overriden, if they already exist?
     * @return void
     */
    public function addAction($ctrl, $action='', $params=array(),$override_params=true)
    {
        //var_dump(array('adding action:'=>compact('ctrl','action','params')));
	    if (is_string($ctrl))
		    $path = explode('/', $ctrl);
	    elseif ($ctrl instanceof IController)
            $path = explode('/', $ctrl->url('',''));
	    else 
		    throw new HgException("Invalid argument passed as parameter ctrl");
        $rtree = & $this->_tree;
        while ($k = array_shift($path))
        {
            if ($k)
                $rtree = & $rtree['children'][$k];
        }

        if ($action)
            $curr_params = & $rtree['children'][$action]['params'];
        else
            $curr_params = & $rtree['params'];

        if ($override_params)
        {
            $a = (array)$curr_params;
            $b = $params;
        }
        else
        {
            $a = $params;
            $b = (array)$curr_params;
        }
        // almost like array_merge()
        foreach ($b as $k => $v)
            $a[$k] = $v;
        $curr_params = $a;

        if ($this->_current === FALSE && current($this->_rtree['children']) !== FALSE)
        {
            $this->_current = key($this->_rtree['children']);
            $this->prev();
        }
    }
    
    public function removeCurrentAction()
    {
        $rem = $this->_current;
        $this->next();
        if (FALSE !== $rem)
            unset($this->_rtree['children'][$rem]);
    }


    /**
     * Adds action to the request.
     *
     * @param Controller $ctrl

     * @return void
     */
    public function addSubController(Controller $ctrl,$at_end = true)
    {
        $path = explode('/', $ctrl->url('',''));
        $rtree = &$this->_tree;
        while (null !== ($k = array_shift($path)))
        {
            if(!$at_end)
            {
                if(!isset($rtree['children'][$k]))
                {
                    $rtree['children'] = array_reverse($rtree['children']);
                    $rtree['children'][$k] = array();
                    $rtree['children'] = array_reverse($rtree['children']);
                }
                else
                {
                    $tmp = $rtree['children'][$k];
                    unset($rtree['children'][$k]);
                    $rtree['children'] = array_reverse($rtree['children']);
                    $rtree['children'][$k] = $tmp;
                    $rtree['children'] = array_reverse($rtree['children']);
                } 
            }
            $rtree = &$rtree['children'][$k];
        }
        if(!isset($rtree['params']))
            $rtree['params'] = array();        
    }


    /**
     * Pobiera całe drzewo URLa.
     * @return array głęboko zagnieżdzona tablica asocjacyjna w postaci
     *               omówionej przed klasą.
     */
    public function getWhole()
    {
        return $this->_tree;
    }
    
    public function getChildrenCount()
    {
        return count($this->_rtree['children']);
    }


    /**
     * Zwraca nazwę aktualnie obsługiwanego elementu w aktualnym węźle.
     * Zwraca to samo, co ostatnie wywołanie {@see next()}.
     * Po wejściu w węzeł, zwraca FALSE.
     *
     * @return false|string
     */
    public function getCurrent()
    {
        return $this->_current;
    }


    /**
     * Getter for {@uses $_base_uri}.
     *
     * @param boolean $with_host prefix with protocol and host name
     * @return string
     */
    public function getBaseUri($with_host=false,$with_controllers=false)
    {
        $ctrl = '';
        if($with_controllers)
        {
            if(isset(g()->conf['permanent_controllers']))
            {                
                foreach(g()->conf['permanent_controllers'] as $controller_name=>$controller_type)
                {
                    $ct=g($controller_type,'controller');
                    $ctrl .= $ct->getExtUri();
                }
            }
        }
        if (!$with_host)
            return $this->_base_uri.$ctrl;
        else
        {
            $host = $this->_host;
            if ('http' == $this->_protocol && 80 != $this->_port)
                $host .= ':' . $this->_port;
            return sprintf('%s://%s%s%s', $this->_protocol, $host, $this->_base_uri, $ctrl);
        }
    }
    
    public function getUrlPath()
    {
        return($this->_url_path);
    }


    /**
     * Getter for {@uses $_is_ajax}.
     * @return boolean
     */
    public function isAjax()
    {
        return $this->_is_ajax;
    }


    /**
     * Gets referer URL.
     * @return string|boolean false when request was not generated from $_SERVER
     */
    public function getReferer()
    {
        if ($this->_from_server)
            return @$_SERVER['HTTP_REFERER'];
        else
            return false;
    }


    /**
     * Checks for children in active node.
     * @returns boolean
     */
    public function hasChildren()
    {
        return !empty($this->_rtree['children']);
    }


    /**
     * Gets first element's key in current node.
     * Returns false if there're no elements.
     *
     * @return false|string first node's key
     */
    public function first()
    {
        if (!is_array(@$this->_rtree['children']))
            return false;
        reset($this->_rtree['children']);
        return key($this->_rtree['children']);
    }


    /**
     * Resets positions to the first element in current node.
     *
     * @return void
     */
    public function reset()
    {
        $this->first();
        $this->_current = null;
    }
    

    /**
     * Gets next element's key in current node.
     * Returns false if there's no more elements left.
     *
     * @return false|string
     */
    public function next()
    {
        //var_dump(array ('next before current'=>$this->_current));
        if ($this->_current === FALSE)
            return(FALSE);        
    
        if ($this->_current === NULL)
            reset($this->_rtree['children']);
        else
        {
            if (FALSE === next($this->_rtree['children']))
                $this->_current = FALSE;
        }
        if ($this->_current !== FALSE)
            $this->_current = key($this->_rtree['children']);
        //var_dump(array ('next after current'=>$this->_current));
        return $this->_current;
    }


    /**
     * Gets previous element's key in current node.
     * @return false|string false if no previous elements
     */
    public function prev()
    {
        //var_dump(array ('prev before current'=>$this->_current));
        if ($this->_current === NULL)
            return(FALSE);
        if ($this->_current === FALSE)
            end($this->_rtree['children']);
        else
        {
            if (FALSE === prev($this->_rtree['children']))
                $this->_current = NULL;
        }
        if ($this->_current !== NULL)
            $this->_current = key($this->_rtree['children']);
        //var_dump(array ('prev after current'=>$this->_current));
        
        return $this->_current;
    }

    
    /**
     * Dives into active element in active node.
     * @param test If true, the function never actually dives.
     * @return boolean false if active element can't be diven into.
     */
    public function dive($test=false)
    {
        $key = $this->getCurrent();
        if (@!is_array($this->_rtree['children'][$key])
            || @!is_array($this->_rtree['children'][$key]['children']))
            return false;
        if ($test)
            return(true);
        $this->_path_to_rtree[] = $key;
        $this->_rtree = & $this->_rtree['children'][$key];
        $this->_current = NULL;
        return true;
    }
    
    /**
     * Caution! This function might work unpredictably!    
     * Gets current node's child name.
     * 
     * @return string/null Child name or null if child doesn't exist
     */
    public function getActiveChildName()
    {
        $key = $this->getCurrent();
        if(isset($this->_rtree['children'][$key]['children']))
            return key($this->_rtree['children'][$key]['children']);
        else
            return null;
    }
    
    /**
     * Emerges from node previously diven into.
     *
     * @return zwraca biezacy element po przejciu, albo null, jezeli wyzszego elementu juz nie ma.
     */
    public function emerge()
    {
        $this->_current = array_pop($this->_path_to_rtree);
        $this->_updateRtree();
        return true;
    }
    
    /**
     * Returns parameters of a node.
     * @uses {$_rtree}
     *
     * @param $path path to the node, active node is used when no path given.
     * @return array|null associative array of got parameters or null if node does not exist
     */
    public function getParams($path=null)
    {
        if (null === $path)
            return  @ $this->_rtree['children'][$this->_current]['params'];

        if (!is_array($path))
            $path = explode('/', $path);
        $rtree = & $this->_tree;
        while ($node = array_shift($path))
        {
            if (!isset($rtree['children'][$node]))
                return null;
            else
                $rtree = & $rtree['children'][$node];
        }
        return $rtree['params'];
    }


    /**
     * Encodes value in url, for building links.
     * @param string $val
     * @return string encoded value
     */
    public function encodeVal($val)
    {
        // triple encod is a must, apache tends to freak out otherwise
        $val = @urlencode(urlencode(urlencode($val)));
        if ($this->_link_split_encoded)
            $val = str_replace(g()->conf['link_split'],
                               $this->_link_split_encoded, $val);
        return $val;
    }

    /**
     * Decodes value in url, for building links.
     * @param string $val
     * @return string decoded value
     */
    public function decodeVal($val)
    {
        $val = urldecode(urldecode(urldecode($val)));
        if ($this->_link_split_encoded)
            $val = str_replace($this->_link_split_encoded,
                               g()->conf['link_split'], $val);
        return $val;
    }

    /**
     * Parses params string (e.g. foo=bar,foo,xx=2)
     * @param string $params
     * @return array with parsed parameters
     */
    public function decodeParams($params_raw)
    {
        if ('' === $params_raw)
            return array();

        $params_raw = explode(',', $params_raw);
        $params = array();

        foreach ($params_raw as & $param)
        {
            if (FALSE !== ($pos = strpos($param,'=')))
                $params[$this->decodeVal(substr($param,0,$pos))] = $this->decodeVal(substr($param,$pos+1));
            else
                $params[] = $this->decodeVal($param);
        }
        return $params;
    }

    /**
     */
    public function encodeParams($params_array)
    {
        $params = array();
        foreach ($params_array as $k => $v)
        {
            $k = $this->encodeVal($k);
            $v = $this->encodeVal($v);
            if (g('Functions')->isInt($k))
                $params[] = $v;
            else
                $params[] = "$k=$v";
        }
        return implode(',', $params);
    }
    
    /**          
     * @version: 0.5 
     * @todo:
     *  - some testing needed to check if all possible situations are handled
     *  - created link might looks nicer
     *     
     * Builds an URL based on the given request tree.     
     * @author m.wierzba
     *
     * @param array $tree Parsed tree of components, actions, etc. 
     * This param must have $this->_tree structure !!!          
     * @param string $temp_url auxiliary parameter. Keeps function's current result. 
     * @param string $parent auxiliary parameter. Keeps parent's controller,action... name.
     *
     * @return string URL
     */
    public function getTreeBasedUrl(array $tree, $temp_url='', $parent_name='')
    {
        $tree = $tree['children'];
        foreach($tree as $ctrl => $ctrl_val)  // $ctrl - controller/action name
        {
            $counter = 0; // counter!=0 means that 'brother' is being processed 
            foreach($ctrl_val as $key => $val) // $key - 'params'/'children'
            {       
                if($key == 'params')
                {
                    // permanent controllers are ignored
                    if(!array_key_exists($ctrl, g()->conf['permanent_controllers']))
                    {
                        // setting controller's/action's parameteres
                        $params = $this->encodeParams($val);

                        //if (!empty($params)) wb 04.08.09 - w dsCtrl przy stronnicowaniu tablecrtl'a brakuje link splittera
                        if($params!=='')
                            $params = g()->conf['link_split'].$params;

                        // adding parent_name to url if needed
                        if(!empty($parent_name) && !key_exists('children',$ctrl_val))
                            $ready_url = $parent_name.'/'.$ctrl.$params;
                        else
                            $ready_url = $ctrl.$params;
                        
                        // sticking urls together 
                        if(!empty($temp_url))
                        {
                            if($counter == 0 && !key_exists('children',$ctrl_val)) // avoiding adding 'brothers' to url
                                $temp_url .= ';'.$ready_url;
                        }
                        else
                            $temp_url .= $ready_url;
                    }
                }
                
                if($key == 'children')
                {
                    // recursive children processing
                    $ctrl = $parent_name.(!empty($parent_name)?'/':'').$ctrl;
                    $temp_url = $this->getTreeBasedUrl($ctrl_val, $temp_url, $ctrl);
                }
                $counter++;
            }
        }
        return $temp_url;
    }

    /**
     * Seeks to given key in given array.
     *
     * @param mixed $key key to find
     * @param array $arr array to seek in.
     *                   {@uses $_rtree} if none given
     * @return mixed found element's value
     *               or false, it it does not exist
     */
    private function _seekTo($key, array &$arr = null)
    {
        if (null === $arr)
            $arr = & $this->_rtree['children'];
        for (reset($arr) ; key($arr)!=$key && false!==key($arr) ; next($arr) );
        return current($arr);
    }


    /**
     * Sets {@uses $_rtree} to path stored in {@uses $_path_to_rtree}.
     *
     * @return void
     */
    private function _updateRtree()
    {
        // ustawia rtree
        $this->_rtree = & $this->_tree;
        $path = $this->_path_to_rtree;
        while ($k = array_shift($path))
        {
            $this->_rtree = & $this->_rtree['children'][$k];
        }
        $this->_seekTo($this->_current);
    }


    /**
     * Common code for {@see prev()} and {@see next()}.
     *
     * @param string $fn function name to use (next or prev)
     * @return false|string false if no prev|next, key name on success
     */
    private function _prevOrNext($fn)
    {
        if ('prev'!==$fn && 'next'!==$fn)
            throw new HgException('Incorrect method call.');

        if (false === $this->_current)
        {
            reset($this->_rtree['children']);
            $this->_current = key($this->_rtree['children']);
        }
        else
        {
            $this->_seekTo($this->_current);
            $fn($this->_rtree['children']);
            $this->_current = key($this->_rtree['children']);
        }

        return $this->getCurrent();
    }


    /**
     * Initializes class variables.
     * @uses $_tree
     * @uses $_rtree
     * @uses $_path_to_rtree
     * @uses $_current
     * 
     * @param string $path path to parse into the tree
     * @return void
     */
    protected function __buildTree($path='')
    {
        $tree = array('children'=>array());
        $paths = array_filter(explode(';', $path));
        foreach ($paths as & $p)
        {
            $rtree = & $tree;
            $words = array_filter(explode('/', @trim($p,'/')));
            //var_dump($words);
            foreach($words as &$word)
            {
                $command = urldecode($word);
                $params = '';
            
                if (FALSE !== ($pos = strpos($word,g()->conf['link_split']))) // pod Win nie moze byc ':'
                {
                    $command = substr($word,0,$pos);
                    $params = substr($word,$pos+1);
                }
                $rtree = & $rtree['children'][$command];
                //$xtree = & $rtree['children'][$command];
                //unset($rtree['children'][$command]);    //move this branch to the 
                //$rtree['children'][$command] = $xtree;
                
                if (!isset($rtree['params']))
                    $rtree['params'] = array();
                
                $rtree['params'] = array_merge(
                        $rtree['params'],
                        $this->decodeParams($params)
                    );
            }
        }
        $this->_tree = $tree;
        $this->_rtree = & $this->_tree;
        $this->_path_to_rtree = array();
        $this->_current = NULL;
        //echo "<pre>".print_r($tree,true).'</pre>';
    }

    /**
     * Callback to diminish local text URLs, called before __buildTree()
     * e.g. remove some prefix or suffix that is being added by enhanceURL()
     * @author m.augustynowicz
     *
     * @param string $url passed by reference
     * @return string
     */
    protected function _diminishURL(&$url)
    {
    }


    /**
     * Callback to enhance local text URLs.
     * e.g. add some prefix or suffix that is being handled by _parseTextURL()
     * @author m.augustynowicz
     *
     * @param string $url passed by reference
     * @return string
     */
    public function enhanceURL(&$url)
    {
    }
}
