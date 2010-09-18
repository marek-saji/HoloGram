<?php
define('DEFAULT_PAGE_SIZE',20);
class TableController extends Component
{
    public $page_size = DEFAULT_PAGE_SIZE;
    public $page = 0;
    public $next = false;
    public $prev = false;
    public $pages_count = 0;
    public $records_count = 0;
    public $first_record = 0;
    public $last_record = 0;

    protected $_subject;
    protected $_default_action='Get';
    protected $_actions=array();

    private $__initialized=false;

    public function __construct($args)
    {
        parent::__construct($args);
        extract($args);
        $this->_subject = $subject;

        $this->init();

    }


    /**
     * Always allow access to this controller
     *
     * It's used only in DataSetController so far.
     * @author m.augustynowicz
     *
     * @param string $action action name
     * @param array $params request params 
     * 
     * @return boolean true. allow access
     */
    protected function _onAction($action, array &$params)
    {
        return true;
    }


    public function addRecordAction($url, $params, $contents)
    {
        $this->_actions[] = compact('url','params','contents');
    }

    public function actionGet($args=NULL)
    {
        if (isset($args[0]))
            $this->page = (int) $args[0];
        if (isset($args[1]))
        {
            $this->page_size = (int) $args[1];
            if ($this->page_size<1)
                $this->page_size = 1;
        }
    }


    public function url2a($act='', array $params=array())
	{
        if ($act)
            $act = "/$act";
        $p = ':';
        foreach($params as $name => $value)
        {
            if (is_string($name))
                $p .= "$name=$value,";
            else
                $p .= "$value,";
        }
        $p = substr($p,0,-1);


        return(trim($this->getParent()->url2a(NULL))."/".$this->getName()."$act{$p}");
    }

    public function render()
    {
        if (!$this->__initialized)
            $this->init();
        /*
        var_dump(
            array(
                'page'         =>$this->page,
                'prev'         =>$this->prev,
                'pages_count'  =>$this->pages_count,
                'next'         =>$this->next,
                'first_record' =>$this->first_record,
                'page_size'    =>$this->page_size,
                'last_record'  =>$this->last_record,
                'records_count'=>$this->records_count
            )
        );
        */
        if(!$this->_action)
            $tpl = $this->_default_action; //strtolower(substr(get_class($this),0,-10));
        else
            $tpl = $this->_action;
        //var_dump($tpl);
        $this->inc(strtolower($tpl));

    }

    public function init()
    {
        $this->__initialized = true;

        $this->records_count = (int) $this->_subject->getCount();
        $this->pages_count = (int) ceil($this->records_count/$this->page_size);

        if ($this->page<0)
            $this->page=0;
        if ($this->page>0)
            $this->prev = $this->page-1;

        if ($this->page >= $this->pages_count)
            $this->page = $this->pages_count;
        if ($this->page < $this->pages_count-1)
            $this->next = $this->page+1;

        $this->first_record = $this->page * $this->page_size;
        $this->last_record = ($this->page+1) * $this->page_size;
        if ($this->last_record > $this->records_count)
            $this->last_record = $this->records_count;

        foreach($this->_subject->getPrimaryKeys() as $field)
            $this->_subject->order($field);

        $this->_subject->setMargins($this->first_record, $this->last_record);
        $this->_subject->exec();
    }

    public function page($x)
    {
        if ($this->page_size!=DEFAULT_PAGE_SIZE)
            return(array($x,$this->page_size));
        else
            return(array($x));
    }

    protected function _actionParams(&$record, &$pk, &$params)
    {
        $res = g()->conf['link_split'];
        foreach($params as $n => &$p)
        {
            if (is_string($n))
            {
                if ($n[0]===' ')
                    $res .= "$p,";
                else
                    $res .= "$n=$p,";
                continue;
            }
            switch($p)
            {
                case '_ds':
                    $res .= "ds=".$this->_subject->getName().',';
                    break;
                case '_pk':
                    if (!$pk)
                        $pk = array_flip($this->_subject->getPrimaryKeys());
                    $vals = array_intersect_key($record,$pk);
                    foreach($vals as $name => $val)
                        $res .= "$name=$val,";
                    break;
                default:
                    if (isset($record[$p]))
                        $res .= "$p={$record[$p]}";
                    break;
            }
        }
        return(substr($res,0,-1));

    }

}
