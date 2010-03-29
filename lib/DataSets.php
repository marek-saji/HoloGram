<?php
g()->load('Fields', null);
g()->load('HgBaseIterator');

define('ORDER_RANDOM', 'RANDOM()');


/**
* Interface of the data set.  A dataset is an abstraction of any sql tabular data retrieved with a query.
* A query is considered to consist of several parts. First of all from a field set, that define the values
* to retrieve. The second part, the one following the FROM keyword, is called a data set generator (@see generator()).
*
* ArrayAccess's method have to provide *read* access to DataSet's fields
*/
interface IDataSet extends ArrayAccess
{
    /**
    * Returns the string with the data set generator.
    */
    public function generator();
    
    /**
    * Sets the range of results to fetch
    *
    * @param null|integer $from
    * @param null|integer $to if only $from is given it should fetch first $from results.
    * @return $this
    */
    public function setMargins($from = null, $to = null);
    
    
    /**
    * Calculates the count of the quiery results.
    */
    public function getCount();
    
    /**
    * Sets or retrieves the list of fields that should be retrieved.
    */
    public function whiteList(array $field_keys=NULL, $merge=false);
    
    /**
    * Sets query filtering. 
    * @param $condition may be of type FBool or array
    * @return $this
    */
    public function filter($condition);

    /**
     * Gets filtering.
     */
    public function getFilter();
    
    /**
    * Retrieves a field.
    * @param name Name of the field to be returned
    * @result mixed Either the wanted field or NULL if such field doens not exits
    */
    public function getField($name);
    
    
    /**
    * Retrieves fields array
    */
    public function getFields();
    
    /**
    * Retrieves array with names of fields
    */
    //public function getFieldsNames();
    
    
    /**
    * Generates DataSet retrieving query according to current configuration of the data set.
    * @param $pages when true, the query has limit and offset statements.
    * @return string with the query.
    */
    public function query($pages=true,$join=null);
    
    /**
    * Gets or sets model alias.
    * @param $alias string with a single-word table alias
    * $return If $alias is not given, current alias is returned
    */
    public function alias($alias='');    
    
    /**
    * Sets or retrieves the list of fields that will be used in GROUP BY clause.
    */
    public function groupBy($field_keys=NULL);    
    
    
}


/**
 * An abstract base class of data sets.
 */
abstract class DataSet extends HgBaseIterator implements IDataSet
{
    static $singleton = false;

    protected $_whitelist = array(); //!< tablica z kluczami okreslajacymi wyciagane fieldy
    protected $_groupby = array();
    protected $_limit = null;
    protected $_offset = null;
    protected $_filter = NULL;
    protected $_array = array();
    protected $_alias = '';
	protected $_count = NULL;
    protected $_order = NULL;

    public function __construct()
    {
        $this->_iterator = & $this->_array;
        parent::__construct();
    }
        
    public function setMargins($from = null, $to = null)
    {
        if($from === null)
        {
            $this->_limit = null;    
            $this->_offset = null;
        }
        else
        {
            if($to === null)
            {
                $to = 0;
            }

            if($from < $to)
            {
                $this->_limit = $to-$from;    
                $this->_offset = $from;
            }
            else
            {
                $this->_limit = $from-$to;
                $this->_offset = $to;
            }
        }

        return $this;
    }
    
    public function getCount()
    {
	    if (NULL !== $this->_count)
		    return $this->_count;
        $sql = "SELECT\n  COUNT(1)";
        $sql .= $this->_queryFrom();
        $sql .= $this->_queryWhere();
		$this->_count = g()->db->getOne($sql);
		return($this->_count);
    }
	
	public function exec($return=true)
	{
        if(!$this->_array = g()->db->getAll($this->query()))
	       $this->_array = array();
	    
        if ($return)
        {
            if ($this->_limit==1 && !empty($this->_array))
                return ($this->_array[0]);
            return ($this->_array);
        }
	}
    

    public function alias($alias='')
    {
        if ($alias)
            $this->_alias = $alias;
        else
            return($this->_alias);
    }
    
    public function whiteListAll()
    {
        $this->_whitelist = $this->getFields();
        return $this;
    }
    
    /**
    * Sets the whitelist
     * @todo fix this (will break compatibility..
     */
    public function whiteList(array $field_keys=NULL, $merge=false)
    {
        if (NULL===$field_keys)
            return($this->_whitelist);

        $fields = $this->getFields();
        
        $new_whitelist = array();
        
        foreach($field_keys as $column)
        {
            if(is_array($column))
            {
                $field = $column[0];
                $aggregate = $column[1];
                $alias = null;
            }
            else
            {
                $field = $column;
                $aggregate = false;
                $alias = null;
            }
            if($aggregate && !in_array(strtolower($aggregate),array('max','min','count','avg','sum')))
                throw new HgException('Unknown aggregate function: '.$aggregate.' !');
            if(isset($fields[$field]))
            {
                if($aggregate)
                    $new_whitelist[$aggregate.' '.str_replace('"','',$field)] = new FoFunc($aggregate,$fields[$field]);                
                else
                    $new_whitelist[$field] = $fields[$field];
            }
        }
        
        if ($merge)
            $this->_whitelist = array_merge($this->_whitelist,$new_whitelist);
        else
            $this->_whitelist = $new_whitelist;
        return $this;
    }
    
    /**
    * Sets and returns GROUP BY fields
    */
    public function groupBy($field_keys=NULL, $merge=false)
    {
        if (NULL===$field_keys)
            return($this->_groupby);
        if ($merge)
            $this->_groupby = array_merge($this->_groupby,array_intersect_key(array_flip($field_keys),$this->getFields()));
        else
            $this->_groupby = array_intersect_key(array_flip($field_keys),$this->getFields());
        return $this;
    }    
    
    /**
    * Generates selecting query according to current settings of a data set.
    * @param $pages determines wether to include limit and offset statements in the query, or not.
    * @return string selecting query.
    */
    public function query($pages=true,$join=null)
    {
        $sql  = $this->_querySelect();

        $sql .= $this->_queryFrom();

        $sql .= $this->_queryWhere();
            
        $sql .= $this->_queryGroupBy();
        
        $sql .= $this->_queryOrderBy();
            
        if ($pages) 
            $sql .= $this->_queryLimitOffset();

        return $sql;
    }

    /**
     * Generates SELECT part of query
     * @uses __getGroupByFields()
     * @author m.augustynowicz moved from query()
     * @return string
     */
    protected function _querySelect()
    {
        return "SELECT\n".$this->_ident($this->__getWhitelistedFields());
    }

    /**
     * Generates WHERE part of query
     * @uses generator()
     * @author m.augustynowicz moved from query()
     * @return string
     */
    protected function _queryFrom()
    {
        return "\nFROM\n".$this->_ident($this->generator());
    }

    /**
     * Generates WHERE part of query
     * @uses $_filter
     * @author m.augustynowicz moved from query()
     * @return string
     */
    protected function _queryWhere()
    {
        if (!$this->_filter)
            return '';
        return "\nWHERE\n".$this->_ident($this->_filter->generator());
    }

    /**
     * Generates GROUP BY part of query
     * @uses $_groupby
     * @author m.augustynowicz moved from query()
     * @return string
     */
    protected function _queryGroupBy()
    {
        if (!$this->_groupby)
            return '';
        return "\nGROUP BY\n".$this->_ident($this->__getGroupByFields());
    }

    /**
     * Generates ORDER BY part of query
     * @uses $_order
     * @author m.augustynowicz moved from query()
     * @return string
     */
    protected function _queryOrderBy()
    {
        if (!$this->_order)
            return '';
        $sql = array();
        foreach ($this->_order as $name => & $def)
        {
            if ($def['field'] instanceof IField)
                $field = $def['field']->generator();
            else
                $field = $def['field'];
            $sql[] = "$field {$def['dir']}";
        }
        unset($def);
        $sql = "\nORDER BY\n".$this->_ident(join(",\n", $sql));
        return $sql;
    }

    /**
     * Generates LIMIT OFFSET part of query
     * @uses $_limit
     * @uses $_offset
     * @author m.augustynowicz moved from query()
     * @return string
     */
    protected function _queryLimitOffset()
    {
        $sql = '';
        if (null !== $this->_limit)
            $sql .= "\nLIMIT {$this->_limit}";
        if (null !== $this->_offset)
            $sql .= "\nOFFSET {$this->_offset}";
        return $sql;
    }
        
    /**
     * Applies certain filtering to a DataSet.
     * @author p.piskorski
     * @author m.augustynowicz accepting arrays as $condition
     * @author m.jutkiewicz when array is empty - then do nothing
     *
     * @param string|array|IField $condition can be:
     *        a string that will get converted to a FoStatement,
     *        IBoolean generating IField,
     *        array with such elements:
     *          1. 'field_name' => 'value'
     *          2. 'literal query part'
     *          3' array('field_name','operator','value')
     *             e.g. array('date','>','2009')
     *                  array('date','> NOW()')
     *                  array('','MAX(date) >',$date) -- only semi-cool
     *             first element gets quoted as table field,
     *             third one as field value (only if first one was given)
     * @return $this
     */
    public function filter($condition)
    {
        if(is_array($condition) && empty($condition))
            $condition = false;
        elseif(is_array($condition))
        {
            $cond = array();
            foreach ($condition as $field_name => $value)
            {
                $value_given = true;
                // format 'field'=>'value'
                if (!is_int($field_name))
                    $operator = '=';
                else
                {
                    // format: array('field','literal','value')
                    if (is_array($value))
                    {
                        $field_name  = @$value[0];
                        $operator    = @" {$value[1]} ";
                        $value_given = array_key_exists(2,$value);
                        $value       = @$value[2];
                    }
                    // format: 'literal query part'
                    else
                    {
                        $cond[] = $value;
                        continue;
                    }
                }

                if ($field_name)
                {
                    $field = $this[$field_name];
                    if (!$field)
                        throw new HgException("Trying to filter ".get_class($this)." with unknown field $field_name");
                    if ($value_given)
                        $value = $field->dbString($value);
                }
                $cond[] = $field . $operator . $value;
            }
            $condition = join("\nAND ", $cond);
        }

        if(false === $condition)
            $this->_filter = NULL;
        else
        {
            $this->__iBoolean($condition);
            $this->_filter = $condition;
        }
		$this->_count = NULL;

        return $this;
    }

    public function getFilter()
    {
        return $this->_filter;
    }
    
    
    /**
    * Sets or retrieves current records ordering.
    * @author p.piskorski
    * @author m.augustynowicz
    *
    * @param $field IField|string|boolean
    *     when instance of IField given: it's generator will be used;
    *     when string: it is expected to be this DataSet's field name
    *     when true: method returns associative array with sorting information
    *     when false: method returns null d:
    * @param $action string|boolean
    *     string sets ordering: either ASC or DESC, unknown values got changed to ASC;
    *     boolean sets action: true returns ordering information for specified field,
    *                          false unsets ordering for it.
    *
    * @return string|int
    *   when setting: id associated to newly set ordering. can be used to unset it.
    *                 or false if error occured (e.g. non existment field given)
    *   when resetting: previous value
    *   when getting: ordering you want to get
    * 
    * @example
    *   // $ds has fields: type and owner
    *
    *   // ORDER BY 'type' ASC
    *   $id_type = $ds->order('type')
    *
    *   // ORDER BY owner=42 DESC, type DESC, owner ASC
    *   $id_owner42 = $ds->order(new FConst('owner=42','FBool'), 'DESC'); // watch out for SQL-injections!
    *   $id_type = $ds->order('type','desc'); // will return 'type'
    *   $id_owner = $ds->order('owner', 'asc'); // will return 'owner'
    *   $id_non_existment = $ds->order('non existment field'); // will return false
    *   // warning: this will overwrite desceding sorting by type:
    *   $ds->order('type');
    *
    *   // reset sorting by type
    *   $prev_ordering_by_type = $ds->order('type',false);
    *
    *   // reset some custom sorting
    *   $ds->order($id_owner42, false);
    *
    *   // reset all sorting
    *   $ds->order(false);
    *
    *   // get sorting by type
    *   $ds->order('type', true);
    *
    *   // get all sorting
    *   $ds->order();
    *   // or
    *   $ds->order(true);
    */
    public function order($field=true, $dir_or_action='ASC')
    {
        // work on all fields
        if (is_bool($field))
        {
            // get all
            if ($field)
            {
                return $this->_order;
            }
            // reset all
            else
            {
                $prev_value = $this->_order;
                $this->_order = null;
                return $prev_value;
            }
        }
        // work on one field
        else
        {
            $dir = $action = $dir_or_action;
            if (strtoupper($dir)==='DESC')
                $dir = 'DESC';
            else // all non-DESC values
                $dir = 'ASC';
            // what has been passed in $field?
            $suff = false;
            if(is_array($field))
            {
                $suff = $field[1];
                $field = $field[0];
            }
            if ($field == ORDER_RANDOM)
            {
                $key = $field = 'RANDOM()';
            }
            else
            {
                if (is_string($field))
                {
                    $fields = $this->getFields();
                    if (!isset($fields[$field]))
                    {
                        $return = true;
                        foreach ($this->__whitelist as $f=>$c)
                        {
                            if ($f == $field)
                            {
                                $field = $c;
                                $return = false;
                                break;
                            }
                        }
                        if ($return)
                            return false;
                    }
                    else
                        $field = $fields[$field];
                }
                // "generate" key
                $key = $field->generator();
                if($suff)
                    $field = $field->generator().$suff;
            }
            // get/reset
            if (is_bool($action))
            {
                // get
                if ($action)
                    return $this->_order[$key];
                // reset
                else
                {
                    $prev_value = $this->_order[$key];
                    unset($this->_order[$key]);
                    return $prev_value;
                }
            }
            // set
            else
            {
                $this->_order[$key] = compact('field', 'dir');
                return $key;
            }
        }
    }
    
    
    /**
    * Retrieves a field
    * @param $name Name of the searched field.
    * @return Returns a field with a given name, or NULL if such field doens't exists
    */
    public function getField($name)
    {
        $f = $this->getFields();
        return($f[$name]);
    }     

    /**
     * Field getter via ArrayAccess interface
     * @author m.augustynowicz
     */
    public function offsetGet($offset)
    {
        return $this->getField($offset);
    }

    /**
     * Satysfying ArrayAccess interface
     * @author m.augustynowicz
     */
    public function offsetExists($offset)
    {
        return null !== $this->getField($offset);
    }

    /**
     * Satysfying ArrayAccess interface
     * @author m.augustynowicz
     */
    public function offsetSet($offset, $value)
    {
        throw new HgException('You can\'t set fields via ArrayAccess interface.');
    }

    /**
     * Satysfying ArrayAccess interface
     * @author m.augustynowicz
     */
    public function offsetUnset($offset)
    {
        throw new HgException('You can\'t unset fields via ArrayAccess interface.');
    }
    /**
    * Checks if a given variable is, or may be converted to an IField with IBoolean generator type.
    * This function is ment to be used for parameter validation wherever a joining condition or filtering condition is required.
    * This is an assertion-level function. It throws when something is wrong.
    * @param $field A field do be converted. Is a string is given, it's converted to an IBoolean generating FoStatement, 
    *    The other possibility is that it's an IBoolean generating IField.
    */
    protected function __iBoolean(&$field)
    {
        if (is_string($field))
            $field = new FoStatement($field,'IBoolean');
        elseif (!$field instanceof IField)
            throw new HgException('Joining condition may only be a string or an IField');
        elseif (!$field->type('IBoolean'))
            throw new HgException('Joining IField must have IBoolean generator type');    
    }
    
    protected function __getWhitelistedFields()
    {
        $res = array();
        foreach ($this->_whitelist as $f=>$c)
        {
            if($c instanceOf FoFunc)
                $res[] = $c->generator()." AS \"$f\"";
            else
                $res[] = $c->generator();
        }
        if (!$res)
            return "NULL";
        return join(",\n", $res);
    }
    
    protected function __getGroupByFields()
    {
        $current = array_intersect_key($this->getFields(),$this->_groupby);
        $res = '';
        foreach ($current as $c)
            $res .= "  ".$c->generator().",\n";
        if (!empty($res))
            $res[strlen($res)-2]=' ';
        else 
            $res = "  NULL\n";
        return($res);
    }    

    /**
     * Useful when creating subqueries
     * @author m.augustynowicz
     */
    protected function _ident($code)
    {
        if (!g()->debug->on('db'))
            return $code;
        return preg_replace("/^|\n/", "$0  ", $code);
    }
    
}

/**
* A dataset that represents joins between other datasets.
*/
class Join extends DataSet
{
    protected $_first=NULL;
    protected $_joins=array();

    public function __construct(IDataSet $data_set_1=null, IDataSet $data_set_2=null, $on=null, $join_type= 'left')
    {
             
        if (FALSE === array_search($join_type,array('left','right','inner','outer')))
            throw new HgException("Invalid join type '$join_type'");
        if (!$data_set_1)
            throw new HgException('No data set to join given!');

        $this->_first = $data_set_1;
        if ($data_set_2)
            $this->_joins[] = array('ds'=>$data_set_2,'type'=>$join_type,'on'=>$on);

        $this->alias('dsJ');
        $data_set_1->alias($this->alias().'0');
        $data_set_2->alias($this->alias().'1');
        //
    }

    /**
    * Sets the filtration condition.
    * If no filtration was present, then by default a chain of and operations on all joioned datasets filters is made.
    * @param $condition Either a filtration condition, or empty (NULL), to retrieve (possibly recreate) existing dataset.
    * @result $this
    */
    public function filter($condition)
    {
        if (NULL === $condition && NULL === $this->_filter)
        { 
            $join = reset($this->_joins);
            $ops = array();
            if (NULL !== ($op = $this->_first->getFilter()))
               $ops[]=$op;
            foreach ($this->_joins as &$join)
                if (NULL !== ($op = $join['ds']->getFilter()))
                    $ops[] = $op;
            switch(count($ops))
            {
                case 0 : 
                    $this->_filter = NULL;
                    break;
                case 1 :
                    $this->_filter = $ops[0];
                    break;
                default :
                    $this->_filter = new FoChain($ops[0],'AND',$ops[1]);
                    reset($ops); next($ops); 
                    while (FALSE !== ($op = next($ops)))
                        $this->_filter->also($op);
                    break;
            }
            return $this;
        }
        return parent::filter($condition);
    }    
    
    public function query($pages=true,$join=null)
    {
        $this->getFilter();
        return(parent::query($pages));
    }
    
    public function addJoin(IDataSet $data_set, IBoolean $on, $join_type = 'left' )
    {
        if (FALSE === array_search($join_type,array('left','right','inner','outer')))
            throw new HgException("Invalid join type '$join_type'");   
        $data_set->alias($this->alias().(count($this->_joins)+1));
        $this->_joins[] = array('ds'=>$data_set, 'type'=>$join_type, 'on'=>$on);
    }
    
    public function getFields()
    {
        $fields = array_values($this->_first->getFields());
        foreach($this->_joins as $join)
        {
            $fields = array_merge(
                $fields,
                array_values($join['ds']->getFields())
            );
        }
        $f_array = array();
        $f_exs = array();
        foreach($fields as $field)
        {
            if(isset($f_array[$field->getName()]))
            {
                $f = $f_array[$field->getName()];
                unset($f_array[$field->getName()]);
                $f_exs[$field->getName()] = $f;
                $f_array[$f->generator()] = $f;
                $f_array[$field->generator()] = $field;
            }
            elseif(isset($f_exs[$field->getName()]))
            {
                $f_array[$field->generator()]=  $field;
            }
            else
                $f_array[$field->getName()]=$field;
        }
        return $f_array;

        //FIXME: to trzeba zaliasowac zeby merge nie nadpisal pol o takiej samej nazwie
    }
    
    public function generator()
    {
        $gen = $this->_first->generator();
        foreach ($this->_joins as $join)
            $gen .= strtoupper("\n".$join['type']).' JOIN '.$join['ds']->generator()." ON\n".$this->_ident($join['on']->generator());
           
        return $gen;
    }
    
    public function alias($alias='')
    {
        if (!empty($alias))
        {
            $this->_first->alias($alias.'0');
            foreach($this->_joins as $k => $join)
            {
                $join['ds']->alias($alias.($k+1));
            }
        }
        return(parent::alias($alias));
    }
}


/*
class Aggregate extends DataSet
{
}
*/

/**
* Interface of a Model data set. A Model is a kind of database, that works on a single,
* specific database table. 
*/
interface IModel extends IDataSet
{
    public function rel($name);
    
    /**
    * Retrieves model relation definition
    * @param $name Name of the relation to retrieve.
    * @return array Relation definition
    */
    public function getRelation($name,$makeModel=true);    
}


/**
* Base class of data Models
* FIXME : specify the IModel and use it!
*/
class Model extends DataSet implements IModel
{
    protected $_data=array();
    protected $_fields=array(); //!< field set (columns) array of Fields
    protected $_primary_keys=array(); //!< names of fields accounting for the primary key
    protected $_table_name=''; //!< table name
    protected $_force_action = null;
    private $__relations=array(); //!< array of relations, each specified by type, foreign keys and so on.

    /**
     * Array of indexes
     * @var array
     */
    protected $_indexes = array();

    /**
     * Array of triggers
     * @var array
     */
    protected $_triggers = array();
    
    /**
    * Constructor.
    * If table name isn't assigned, then default table name is used (lower case of class name without the 'Model' suffix).
    */
    public function __construct()
    {
        parent::__construct();
        if (empty($this->_table_name))
            $this->_table_name = strtolower(substr(get_class($this),0,-5));
        //$this->alias($this->_table_name);
    }
    
    public function __toString()
    {
        $c = $this->current();
        if (!$c)
            return($this->getName().' model');
    
        if (!isset($this->__name_string))
        {
            $f = $this->getFields();
            $nf = array_intersect(
                array_keys($f),
                array('title','name','surname','label','caption')
            );
            if (empty($nf))
                $nf = $this->__primary_key;
            
            if (empty($nf))
                $nf = key($f);
            
            $this->__name_string = '$'.implode(' $',$nf);
        }
                
        extract($c);
        return( eval("return({$this->__name_string})"));
    }

    /**
    * Returns table generator.
    * @return table generator - table name followed by alias
    */
    public function generator()
    {
        $sql = sprintf('"%s"', $this->_table_name);
        if ($this->_alias)
            $sql .= sprintf(' "%s"', $this->_alias);
        return $sql;
    }
        
    /**
    * Retrieves names of primary key fields
    * @return array of names.
    */
    public function getPrimaryKeys()
    {
        return $this->_primary_keys;
    }
    
    /**
    * Retrieves fields definition
    */
    public function getFields()
    {
        return($this->_fields);
    }
    
    public function fieldsCount()
    {
        return(count($this->_fields));
    }
    
    /**
    * Retrieves a field with a given name.
    * @param $name The name of retrieved field.
    * @return Field or NULL if no such field is defined
    */
    public function getField($name)
    {
        if (array_key_exists($name,$this->_fields))
            return($this->_fields[$name]);
        else
        {
            $fields = $this->getFields();
            return (isset($fields[$name]))?$fields[$name]:NULL;
        }
    }

    /**
    * Prepares a Relation object.
    * You don't have to define all the branches at once, as You may actually add additional 
    * branches with additional calls to a Relation::rel() method.
    *     objects rel()     
    * @param $path mixed Can be either an array with subsequent relations, or nested arrays for branching, or
    *     a string with comma separated relation names that defines a single chain of relation to follow. 
    * FIXME : Yeah, make it happen.
    */
    public function rel($path)
    {
        return(new Relation($this,$path));
    }
    
    /**
    * Retrieves a relation definition.
    */
    public function getRelation($name,$makeModel=true)
    {
        if (FALSE === array_key_exists($name, $this->__relations))
        {
            throw new HgException("No such relation '$name'.");
        }
        if($makeModel && !isset($this->__relations[$name]['model']))
            $this->__relations[$name]['model'] = g($this->__relations[$name]['model_name'],'model');

        return($this->__relations[$name]);
    }
    
    public function getRelations($kind='all',$makeModel=true)
    {
	    $res = array();
		if (!is_array($kind))
		    $kind = array($kind);
		foreach($this->__relations as $key => $rel)
		    if ($kind === array('all') || in_array($rel['type'],$kind))
			    $res[$key] = $this->getRelation($key,$makeModel);
		return($res);
    }
    
    
    /**
    * Retrieves table name.
    */
    public function getTableName()
    {
        return($this->_table_name);
    }
    
    public function getName()
    {
        return(substr(get_class($this),0,-5));
    }
    
    /**
    * Compares model definition with the database table.
    * @return If definitions match, boolean true is returned. If the table doesn't exist, booleanf false is returned.
    *     Otherwise an array with the following structure is given:
    *     array(
    *         'not_in_base'  => array (
    *             'fields' => array($name => Field, ...),
    *             'indexes' => array($name => Index, ...),
    *             'triggers' => array($name => Trigger, ...),
    *         ),
    *         'not_in_model' => array (
    *             'fields' => array(
    *                 $name => array (
    *                     fieldname, //same as $name 
    *                     typename, 
    *                     type_specific, //i.e. length of a varchar
    *                     notnull, 
    *                     defval         //default value
    *                 ), ...
    *             ),
    *             'indexes => array( $name => array(...), ...),
    *             'triggers => array( $name => array(...), ...),
    *         ),
    *         'def_diff' => array(
    *             'fields' => array ( $name => diff, ...), //fields/columns being present in both places, but with a different definition
    *             'indexes' => array ( $name => diff, ...),
    *             'triggers' => array ( $name => diff, ...),
    *         ),
    *         'matching' => array(
    *             'fields' => array ( $name, ...),
    *             'indexes' => array ( $name, ...),
    *             'triggers' => array ( $name, ...),
    *         )
    *
    * @author p.piskorski - first version
    * @author m.izewski - support for triggers and indexes
    */
    public function checkModelInDb()
    {
        //if field,trigger or indes is not in database (was added in model)
        $not_in_base = array();
        //if field,trigger or index is not in model (was removed from model)
        $not_in_model = array();
        //if field, trigger or index deffinition differs between model and postgres db
        $def_diff = array();

        $db_n_model = array();
        $n_db_model = array();
        $db_model = array();
        $pg_db = g()->db->getAll(
            "select fd.attname as fieldname, tp.typname as typename, fd.atttypmod as type_specific, fd.attnotnull as notnull, def.adsrc as defval
            from pg_class tb
            left join pg_attribute fd on fd.attrelid=tb.oid
            left join pg_type tp on fd.atttypid =tp.oid
            left join pg_attrdef def on def.adrelid=tb.oid and def.adnum=fd.attnum
            where tb.relname = '{$this->_table_name}' and fd.attnum>0 and fd.attisdropped = false order by  fd.attnum"
        );
		if (empty($pg_db))
		    return false;
        g('Functions')->changeKeys($pg_db,'fieldname');
        $fields = $this->_fields;
        $not_in_base['fields'] = array_diff_key($fields,$pg_db);
        $not_in_model['fields'] = array_diff_key($pg_db,$fields);
        $def_diff['fields'] = array_intersect_key($fields,$pg_db);
        $matching['fields'] = array();
        foreach ($def_diff['fields'] as $name=>&$field)
        {
            $db_def = &$pg_db[$name];
            $comp = $field->checkType($db_def);
            if (!$comp)
            {
                unset($def_diff['fields'][$name]);
                $matching['fields'][] = $name;
            }
            else
                $field = $comp;
        }
        
        ###################
        ## index support ##
        ###################
        $indexes = g()->db->getAll("
            /*obtain index definitions for table*/
            select idxc.relname as name, am.amname as type, ARRAY (select attname from pg_attribute where attrelid = idxc.oid) as fields
            from pg_class tb
            inner join pg_index idx on idx.indrelid = tb.oid
            left join pg_class idxc on idxc.oid = idx.indexrelid
            /*inner join pg_class idx on idx.relnamespace = tb.relnamespace and idx.reltype = 0 and idx.relam != 0*/
            left join pg_am am on idxc.relam = am.oid
            where tb.relname = '{$this->_table_name}'
            "
        );

        if($indexes === FALSE)
            $indexes = array();

        if(empty($indexes) && !empty($this->_indexes))
            $not_in_base['indexes'] = $this->_indexes;
        else
        {
            g('Functions')->changeKeys($indexes,'name');
            $not_in_base['indexes'] = array_diff_key($this->_indexes,$indexes);
            $not_in_model['indexes'] = array_diff_key($indexes,$this->_indexes);
            //@todo add checking of differences
            //foreach($indexes as &$index)
            //{
            //}
        }
        ###################

        #####################
        ## trigger support ##
        #####################
        //for debug purpose we can get definition query generated by postgres >> select pg_get_triggerdef(tg.oid) 
        $triggers =  g()->db->getAll("
            /*obtain trigger definitions for table*/
            select tg.tgname as name, tg.tgtype as type, tg.tgnargs as nargs, tg.tgargs as args, pr.proname as proc
            from pg_class tb
            inner join pg_trigger tg on tg.tgrelid = tb.oid
            left join pg_proc pr on pr.oid = tg.tgfoid
            where tb.relname ='{$this->_table_name}'
            "
        );

        if($triggers === FALSE)
            $triggers = array();
        
        if(empty($triggers) && !empty($this->_triggers))
        {
            $not_in_base['triggers'] = $this->_triggers;
        }
        else
        {
            g('Functions')->changeKeys($triggers,'name');
            $not_in_base['triggers'] = array_diff_key($this->_triggers,$triggers);
            $not_in_model['triggers'] = array_diff_key($triggers,$this->_triggers);

            foreach($triggers as &$trigger)
            {
                //transform arguments to an array
                if($trigger['nargs']>0)
                {
                    $trigger['args'] = explode('\000', $trigger['args']);
                    //remove last - empty - element
                    array_pop($trigger['args']);
                }

                //transform bit-field type to an "user friendly" array
                $bit_type = $trigger['type'];
                $trigger['type'] = $arr_type = array();
                for($i = 5; $i >= 0; $i--)
                {
                    $arr_type[$i] = $bit_type >> $i;
                    $bit_type -= $arr_type[$i] << $i;
                    switch($i)
                    {
                        case 0 : $key = 'row';
                                break;
                        case 1 : $key = 'before';
                                break;
                        case 2 : $key = 'insert';
                                break;
                        case 3 : $key = 'delete';
                                break;
                        case 4 : $key = 'update';
                                break;
                        case 5 : $key = 'truncate';
                                break;
                    }
                    $trigger['type'][$key] = $arr_type[$i];
                }
            }
        }

        #####################

        if(
            empty($not_in_base['fields']) && empty($not_in_model['fields']) && empty($def_diff['fields'])
            && empty($not_in_base['indexes']) && empty($not_in_model['indexes']) && empty($def_diff['indexes'])
            && empty($not_in_base['triggers']) && empty($not_in_model['triggers']) && empty($def_diff['triggers'])
        )
            return true;
        return compact('not_in_base','not_in_model','def_diff','matching');
    }
    
    /**
    * Generates table creation query.
    */
    public function tableDefinition()
    {
        $sql = "DROP TABLE IF EXISTS \"{$this->_table_name}\";\nCREATE TABLE \"{$this->_table_name}\" (\n";
        foreach($this->_fields as $f)
            $sql .= "    ".$f->columnDefinition().",\n";
        if (!empty($this->_primary_keys))
            $sql .= "    CONSTRAINT \"{$this->_table_name}_pk\" PRIMARY KEY (".implode(',',$this->_primary_keys)."),\n";
        $sql[strlen($sql)-2]=' ';
        $sql .= ");\n";
        $sql .= "COMMENT ON TABLE \"{$this->_table_name}\" IS 'model:".$this->getName()."';\n";
        return($sql);
    }
    
    public function delete($execute=false)
    {
        $sql = "DELETE FROM {$this->_table_name} ";
        if ($this->_filter) 
            $sql .= "\nWHERE\n  ".$this->_filter->generator();
        return $execute?g()->db->execute($sql):$sql;
    }
    
    
    /**
    * Updates recodrs matched by the current filter with the given values.
    * @param $values array of $name=>$field, where $name contain names of the fields to be 
    *     updated, and $fields IFields with new values.
    * @param $execute set true to automatically execute prepared query
    * @return When $execute is false (default) the generated query is returned. 
    */
    public function update(array $values, $execute=false)
    {
        $sql = '';
        foreach ($values as $name => $val)
        {
            $sql .= "    ".$this[$name]->getName()." = ". $val->generator() .",\n";
        }
        if (!empty($values))
            $sql[strlen($sql)-2]=' ';
        if ($sql)
            $sql = $this->_ident($sql);
        $sql = " UPDATE {$this->_table_name} SET\n".$sql;
        if ($this->_filter) 
            $sql .= "\nWHERE\n".$this->_ident($this->_filter->generator());
        return $execute?g()->db->execute($sql):$sql;        
    }
    
    
    /**
    * Generates a filter that matches the same rows as a data array. The structure of $data array is same 
    * as in case of the sync() method. The function scanes the first array level for primary key values and constructs
    * a (pk) IN (row1, row2, row3, ... ) FoStatement afterwards passed to the filter() method. 
    * @param $data 
    */
    public function filterFromData(array &$data)
    {
        $pk = $this->_primary_keys;
        $field ='('.implode(', ',$pk).") IN ( (";
        $pk = array_flip($pk);
        reset($data);
        if (g('Functions')->isInt(key($data)))
        {
            foreach ($data as $num => $row)
                $field .= implode(',',array_intersect_keys($row,$pk)).') , (';
        }
        else
            $field .= implode(',',array_intersect_keys($data,$pk)).') , (';
        $field = substr($field,0,-2).')';
        $this->filter(new FoStatement($field,'IBoolean'));
    }
    
    
    /**
    * Synchronizes an array with the data base. This function recursively processes $data array and performs
    * multiple relation-aware insertions, updates or deletions. The DATA array is itself recursive and is either
    * a row or an array of rows:
    * <pre>
    * DATA = array(NUMERIC_KEY => ROW,...) | ROW
    * ROW = array ( 
    *     FIELD => VALUE, ..., //possibly for each field, aspecially including primary keys
    *     NTO1_RELATION => ROW, ..., 
    *     1TON_RELATION => DATA,
    *     ['_action'=> 'update' | 'insert' | 'delete'] //optional
    * )
    * </pre>
    * Operation to perform is typically passed with $action parameter, but can also be supplied in per-record 
    * basis with an optional '_action' key. The action determined for every record is passed as default action
    * to the nested sync() calls. Therefore if you decide to delete a record, by default you will also delete each 
    * of its nested records (stuff under any relation key). With specific '_action' setting you can mix insertions
    * and deletions in a single call, which may lead to undesired behavior like breaking the foreign key constraints.
    *
    * @param $data Data array
    * @param $execute execution flag, when true - the generated queries are performed, otherwise they are returned
    * @param $action the default action. See description.
    * @return Either a string with the SQL code, or the error array. If $execute flag is set and no errors are 
    *     found, result of the db->execute() call is returned
    *     when execute fails (lastErrorMsg is non-empty), returns FALSE
    */
    public function sync(array &$data,$execute=false,$action='update')
    {
        if($this->_force_action!==null)
            $action = $this->_force_action;
        //$execute=false;
        if(!in_array($action,array('insert','update','delete')))
            return null;
        $sql = '';
        $error = array();
        reset($data);
        if (g('Functions')->isInt(key($data)))
        {
            foreach($data as $key=> &$single)
            {
                $tmp_error=array();
                if (!$tmp = $this->__syncSingle($single,$action,$tmp_error))
                    $error[$key] = $tmp_error;
                else
                    $sql .= $tmp;
            }
        }
        else
        {
            $tmp_error=array();
            if (!$tmp = $this->__syncSingle($data,$action,$tmp_error))
                $error = $tmp_error;
            else
                $sql .= $tmp;
        }
        
        //var_dump($error);
        if(!empty($error))
            return $error;
        else if (!$execute)
            return $sql;
        else
            return g()->db->execute($sql) && !g()->db->lastErrorMsg();
    }
    
    
    /**
    * Registeres a new model relation. 
    * @param $rel_name Name of the relation.
    * @param $model_name Name of the related model.
    * @param $type Type of the relation, currently either one of  '1toN', 'Nto1', '1to1'. Calling model is considered left-hand in this notation.
    * @param $foreign_key The key in calling model, that points other side of the relation. 
    *     If not provided, a default value is used (depending on the relation type).
    * @param $target_key The key in related model, that points the calling model.
    *     If not provided, a default value is used (depending on the relation type). Default value 
    *     in case of the '1toN' relation is strtolower($rel_name).'_id', which assumes, that the 
    *     mirror relation has the same name!
    */
    public function relate($rel_name='', $model_name, $type, $foreign_key='', $target_key='')
    {
        if (false === in_array($type,array('1toN','Nto1','1to1')))
            throw new HgException("Unsupported relation type: $type");            
        if(!is_string($model_name))
        {
            $model = $model_name;
            $model_name = $model->getName();
        }
        else
            $model = null;
        if (empty($rel_name))
            $rel_name = ucfirst($model_name);
        if (FALSE !== array_key_exists($rel_name, $this->__relations))        
            throw new HgException("Relation '$rel_name' already exists");
            //var_dump($this->_propose_keys($rel_name,$type));
        extract($this->_propose_keys($rel_name,$type));
        if (empty($foreign_key))
            $foreign_key = $pfk;
        if (empty($target_key))
            $target_key = $ptk;
        /*if (in_array($type,array('Nto1','1to1')))   moze byc relacja 0/1 to N 
            $this[$foreign_key]->notNull(true);*/
        //var_dump($this[$foreign_key]->notNull());
        $this->__relations[$rel_name] = compact('rel_name','model_name','model','type','foreign_key','target_key');
    }
    
    public function getData($name)
    {
        if(!is_array($name))
            $name = array($name);
        $data = $this->_data;
        foreach($name as $p)
            if(isset($data[$p]))
                $data = $data[$p];
            else
                return null;
        return $data;
    }
    
    
    /**
    * Performs a single record synchronization for the sync() function. Possibly calls sync() or __syncSingle() of related 
    * models
    * @param $data single record data
    * @param $action current default action. The action propagates down into the data array, so if any record is to 
    *     be deleted, then, by default, each record it relates to will also be deleted. This behavior is required to perform
    *     cascading deletions of 1toN related records.
    */
    protected function __syncSingle(&$data, $action, &$error)
    {
        // determining the action
        
        if(isset($data['_action']))
            $action = $data['_action'];
            
        if($action == 'update')
        {
            foreach($this->_primary_keys as $pk)
                if(!isset($data[$pk]) || !$data[$pk])
                {
                    $action = 'insert';
                    trigger_error(E_USER_WARNING, 'Tried to update-sync, but no PK given, falling back to insert!');
                    break;
                }
        }
        if( !in_array($action,array('insert','update','delete')))
            throw new HgException("Invalid action $action");
        //var_dump("action: $action");
        
        //processing Nto1 relations
        
        $relations = $this->getRelations('Nto1');
        $command = '';
        foreach($relations as $rel_name => $relation)
        {
            if(!isset($data[$rel_name]))
                continue;
            $rel_model = $relation['model'];
            $data[$relation['foreign_key']] = NULL; //may be overwriten later
            $tmp_error = array();
            if($tmp = $rel_model->__syncSingle($data[$rel_name],$action,$tmp_error))
            {
                $command .= $tmp;
                $data[$relation['foreign_key']] = $data[$rel_name][$relation['target_key']];
            }
            else
                $error[$rel_name] = $tmp_error;
        }
        $sql = $command;
        
        //begin local query
        
        if($action == 'insert')
            $sql .= "INSERT INTO \"{$this->_table_name}\" (";
        elseif($action =='update')
            $sql .= "UPDATE \"{$this->_table_name}\"\nSET ";
        elseif($action =='delete')
        {
            $sql .= "DELETE FROM \"{$this->_table_name}\" ";
            $non_pk = array_diff_key($this->_fields, array_flip($this->_primary_keys)); 
            $data = array_diff_key($data,$non_pk); //ignore all data except the primary key components.
        }
        
        //process the data
        
        $values = '';
        foreach($this->_fields as $name=>$field)
        {
            /** @todo wielki syf w tej if-ownicy. naprawić. uporządkować. przepisać od nowa. */
            if ($action == 'delete')
            {
                if (!in_array($name,$this->_primary_keys)) //only interested in primary keys when deleting
                    continue;
                elseif (!isset($data[$name]))
                    $data[$name]= NULL; //these will be properly caught during validation
            }            
            if (get_class($field) === 'FId' && $action == 'insert' && (!isset($data[$name]) || $data[$name]==='' || $data[$name]===null))
            {
                $data[$name] = $field->seqValue('next');
            }            
            elseif ($action == 'insert') // key not exist or null value
            {
                if (!array_key_exists($name, $data))
                {
                    $null = null;
                    if($tmp = $field->invalid($null)) //reference to $data[$name] is NULL, 
                        $error[$name]= $tmp;                     //but the call may fill it with
                    if (null !== $null)
                        $data[$name] = $null; 
                }
                else
                {
                    if($tmp = $field->invalid($data[$name])) //reference to $data[$name] is NULL, 
                        $error[$name]= $tmp;                     //but the call may fill it with some automatic value. @TODO Really?!?.\
                }
                
            }
            elseif(array_key_exists($name,$data) && !isset($data[$name]) && $action=='update') // key exists and value is null
            {
                if($tmp = $field->invalid($data[$name]))
                    $error[$name]= $tmp;
            }
            elseif(isset($data[$name]) && $tmp = $field->invalid($data[$name]))
            {
                //var_dump(array("$name: '{$data[$name]}' invalid"=>$tmp));
                $error[$name]= $tmp;
                continue;
            }
			$this->_data = $data;
            if (!isset($error[$name]) && array_key_exists($name,$data)) //so far so good
            {
                $value = $field->dbString($data[$name]);
                if($action == 'insert')
                {
                    $sql.= $this->_ident("\n\"$name\",");
                    $values .= "\n$value,";
                }
                elseif($action == 'update')
                    $sql.= $this->_ident("\n\"$name\"=$value,");
            }
        }

        //end local query
        
        if(empty($error))
        {
            if($action != 'delete') $sql = substr($sql,0,-1);
            else $sql = substr($sql,0,-1);
            $sql .= "\n";
            if($action == 'insert')
            {
                $values = substr($values,0,-1);
                $sql.= ") VALUES (".$this->_ident($values)."\n); ";
            }
            else
            {
                $where = '';
                foreach($this->_primary_keys as $pk)
                    $where .= "$pk={$this[$pk]->dbString($data[$pk])} AND ";
                $where = "WHERE\n".$this->_ident($where);
                $sql.= substr($where,0,-5)."; ";
            }
        }
        $sql .= "\n";
        
        //process the 1toN relations
        
        $relations = $this->getRelations('1toN');
        $command = '';
        foreach($relations as $relation)
        {
            $rel_model = $relation['model'];
            $rel_name = $relation['rel_name'];
            if(!isset($data[$rel_name]) || !isset($data[$relation['foreign_key']]))
                continue;
    
            $xdata = &$data[$rel_name]; //possibly work on multiple records
            reset($xdata);
            if (!g('Functions')->isInt(key($xdata)))
                $xdata = array($data[$rel_name]);
                
            //fill up the foreign keys
            foreach ($xdata as &$single)
                $single[$relation['target_key']] = $data[$relation['foreign_key']];
            
                
            if (is_string($tmp = $rel_model->sync($data[$rel_name],false,$action)))
                $command .= $tmp;
            else
                $error[$rel_name] = $tmp;                
            //$data[$rel_name] = array_filter($data[$rel_name]); //filter out deleted records

            $this->_data[$rel_name] = $rel_model->_data;
        }    
        $sql.=$command;
        
        if (empty($error))
            return($sql);
        return(NULL);
        //if ($action == 'delete')
        //    $data=NULL; //unset will only destroy the reference. This NULL will be filtered out by the caller.
        
    }

    /**
    * Inserts a new field into a model.
    * @param  IModelField $field A field to insert.
    */
    protected function __addField(IModelField $field)
    {
        $this->_fields[$field->getName()] = $field;
        $field->SourceModel($this);
        return($field);
    }

    /**
    * Removes field from a model.
    * @author m.jutkiewicz
    * @param string $field A field to remove.
    * @return bool True if removed successfully.
    */
    protected function __removeField($field)
    {
        if(@$this->_fields[$field])
        {
            unset($this->_fields[$field]);
            return true;
        }
        return false;
    }

    /**
     * Registers index for model
     *
     * @param string $name
     * @param string $expr
     * @param string $type
     *
     * @author m.izewski
     */
    protected function __addIndex($name, $expr, $type = null)
    {
        if(empty($name) || empty($expr))
            throw new HgException("Given name and expression can not be empty.");

        //Check if there is no index of this name already declared in this model
        if(!empty($this->_indexes[$name]))
            throw new HgException("Selected index name({$name}) is already declared in this model.");
            
        //Available index types (from PostgreSQL 8.4 documentation)
        //Gin type is available since PostgreSQL 8.2
        $available_types = array('btree', 'hash', 'gist', 'gin');
        if(!empty($type) && !in_array(strtolower($type),$available_types))
            throw new HgException("Selected index type({$type}) is not supported.");

        $this->_indexes[$name]['expr'] = $expr;
        $this->_indexes[$name]['type'] = empty($type)?'btree':$type;
    }

    /**
     * Registers trigger for model
     *
     * @param string $name triggers name
     * @param array $fields list of fields that will fire up this trigger
     * @param array $events list of events that will fire up this trigger (must be at least one of insert, update or delete)
     * @param string $exec procedure and arguments (as in sql) that will be fired up by trigger
     * @param string $when when trigger should be triggered up (before[default] or after),
     * @param string $row FOR EACH ROW if true or FOR EACH STATEMENT if false (by default). For further information see PostgreSQL Docs
     *
     * @author m.izewski
     */
    protected function __addTrigger($name, array $fields,  array $events, $exec, $when = 'before', $row = false )  
    {
        //check if there is no trigger of given name already declared in this model
        if(!empty($this->_triggers[$name]))
            throw new HgException("Selected trigger name({$name}) us already declared in this model.");

        if(empty($fields))
            throw new HgException("Empty field set given.");

        //Available fireing up events
        foreach($events as &$event)
            $event = strtolower($event);
        $allowed_events = array('update', 'insert', 'delete');
        if(empty($events))
            throw new HgException("You must choose at least one event");
        elseif(sizeof(array_intersect($events,$allowed_events)) != sizeof($events))
            throw new HgException('One or more of selected events('.implode(',',$events).') are not supported.');

        if(!in_array(strtolower($when),array('before','after')))
            throw new HgException("Invalid 'when' argument({$when}). Must be 'before' or 'after'");

        $this->_triggers[$name] = compact('fields','events','exec','when','row');
    }
    
    /**
    * Registeres a primary key.
    * @param $primary_keys mixed Either a name of the field accounting for the primary key, 
    *     or an array of those.
    */
    protected function __pk($primary_keys)
    {
        $args = func_get_args();
        foreach($args as $arg)
        {
            if (!is_array($arg))
                $arg = array($arg);
            foreach($arg as $pkey)
                if ($f=$this[$pkey])
                {
                    $this->_primary_keys[]=$pkey;
                    $f->notNull(true);
                }
                else
                    throw new HgException("No such field '$pkey'");
        }
        //register index for primary keys...
        $this->__addIndex($this->_table_name.'_pk', implode(',',$args));
    }
    
    /**
    * Creates proposition for related tables foreign and local keys 
    * @param $rel_name name of the relation
    * @param $type Relation type.
    */
    private function _propose_keys($rel_name,$type)
    {
        $pfk='id';
        $ptk ='id';
        switch ($type)
        {
            case '1toN' : 
                $ptk = strtolower(substr(get_class($this),0,-5)).'_id';
                break;
            case 'Nto1' :
                $pfk = strtolower($rel_name).'_id';
                break;
        }
        return(compact('pfk','ptk'));
    }
} 


/**
* Data set used to walk through models relations.
*/
class Relation extends Join
{   
	protected $_relations=array();
    
    /**
    * Constructor.
    * @param Model $start The starting model.
    * @param $relation Name of the relation to follow.
    */
    public function __construct(Model $start, $path)
    {
        $this->_first = $start;
        $this->_relations = array(
            '_model' => $start,
        );
        $this->_first->alias("dsJ");
        $this->alias("dsJ");
		$this->rel($path);
		
        //extract($this->__prepare($this->_first, $relation));
        //parent::__construct($this->_first, $target, $on, $join_type);
    }
    
    /**
    * Attaches additional models to the relaton cluster. Allways begining with the 
    * starting model defined during construction.
    * @param $path The relation path to include.
	*     Either a string - i.e. [Books>] 'Authors>Categories', which is good to specify a
    *     single path,
	*     Or an array - i.e. [Books>] array('Authors'=>array('Categories'),
    *          'Transactions>Clients')
    */
    public function rel($path)
    {
        //$base = new Dummy();
        //$base->b=$this->_first;
        //var_dump($base);
	    $this->__addRel($path, $this->_relations);
        //$this->dumpRelations();
    }
    
    
   	public function getArray()
	{
        $counter=0;
        $this->alias('ds');
        $res = $this->__getArraySql($this->_relations, $counter);
        echo "<pre>"; echo($res); echo "</pre>";
        $res = unserialize(g()->db->getOne($res));
        return($res);
	}
    
    
    public function dumpRelations()
    {
        echo "<pre>";
        $level='';
        echo $this->_first->getName()."\n";
        $this->__walkAndDumpRelations($this->_relations,$level);
        //var_dump($this->_relations);
        echo "</pre>";
    }

     
    protected function __walkAndDumpRelations(&$el, $level)
    {
        if (isset($el['_def']))
            echo "$level{$el['_def']['type']} {$el['_def']['model_name']} (<small>{$el['_def']['rel_name']}</small>)\n";
        echo "$level{\n";
        $level .= '  ';
        foreach($el as $key => &$val)
        {
            if ($key[0] === '_')
            {
                echo "{$level}$key [".gettype($val).($val instanceof Model ? ':'.$val->getName() : '')."]\n";
                continue;
            }
            $dive = &$el[$key];
            $this->__walkAndDumpRelations($dive,$level);
        }
        $level = substr($level,0,-2);
        echo "$level}\n";
    }
	
	protected function __addRel($path, &$rel)
	{
		if (!is_array($path))
		    $path = array($path);
		
        foreach($path as $key => $way)
		{
		    $n_rel = &$rel;
		    if (is_string($key))
			{
			    $this->__addSinglePath($key,$n_rel);
				$this->__addRel($way,$n_rel);
			}
			else
			    $this->__addSinglePath($way,$n_rel);
		}
	}
	
    
	protected function __addSinglePath($path, &$rel)
	{
        $path = explode('>',$path);	
		foreach($path as $way)
		{
            if (!isset($rel[$way]))
            {
                //echo $rel['_model']->getName()."-$way *\n<br />"; 
                $relation=$rel['_model']->getRelation($way);
                $target = $relation['model'];
                $rel[$way]=array(
                    '_def'=>$relation,
                    '_model'=>$target
                );
                extract($this->__prepare($rel['_model'], $relation));
                $this->addJoin($target, $on, $join_type);
            }
            //else
            //    echo $rel['_model']->getName()."-$way was there\n<br />";
            //$base->b = $rel[$way]['_model'];
            $rel = &$rel[$way];
            //echo "new base:".$rel['_model']->getName();
			
		}
	}
	
    
    /**
    * Prepares a join for the relation
    * @param $relation defninition of the relation to follow.
    */
    protected function __prepare($base, $relation)
    {
        $join_type = 'left';
        switch($relation['type'])
        {
            case '1to1' : 
                $join_type='inner';
                break;
        }
        $target = g($relation['model_name'],'model');
        $on = new FoBinary($base[$relation['foreign_key']],'=',$target[$relation['target_key']]);
        return(compact('join_type','target','on'));
    }
	
    
    /**
    * Recursively generates SQL statement to fetch relation data in form of a serialized php array.
    * Conceptually, the sql has the following form:
    * SELECT 
    *     [model0.target_key], --possibly multiple fields
    *     [serialized/whitelisted model0.fields, model0.relations as embedded serializations] 
    *          as serialization 
    *                          -- single text field with embedded serializations of subqueries, 
    *                          -- i.e. Model1Relation => tab1.serialization
    * FROM model0
    * LEFT JOIN ( 
    *     SELECT 
    *         [model1.target_key],
    *         [serialized/whitelisted model1.fields, model1.relaitions as embedded serializations] 
    *             as serialization
    *     FROM model1
    *     LEFT JOIN ([recursion for subrelation 1]) ON ...
    *     LEFT JOIN ([recursion for subrelation 2]) ON ...
    *     GROUP BY model1.target_key
    * ) tab1 ON (model0.foreign_key) = (tab1.target_key)
    * LEFT JOIN ([recursion]) ON ...
    *    
    * 1. target_key is selected to supply one side of the upper level joining condition 
    *    (therefor it's not selected in the top-most query level.
    * 2. topmost query is grouped by everything - it only returns a single text cell,
    *    subqueries are grouped by models target_key, so they provide a single row for
    *    each upper level row
    * 3. serializations use a text aggregate function, that concatenates multiple text rows
    * 4. in case of 1toN relation a 2nd degree table of rows is used
    * 
    * 
    */
	protected function __getArraySql( &$el, &$counter, $ident='')
    {
        $model = &$el['_model'];
        $foreign_key = array();
        $target_key = array();
        $type = '';
        if (isset($el['_def']))
            extract($el['_def']);//$type and $target_key
        if (!is_array($target_key))
            $target_key = array($target_key);//FIXME this should always be an array, fix in model::relate
        $alias = $model->alias().'.';
        $rels = array();
        foreach ($el as $name => $val)
            if ($name[0]!=='_')
                $rels[$name] = $val;
                
        $sql =  "\n{$ident}SELECT";
       
        if (!empty($target_key))
            $sql .= "\n$ident    $alias".implode(",\n$ident    $alias",$target_key).',';

        $end ='';
        
        if ($type === 'Nto1')
        {
            $sql .= "\n$ident     str_concat('a:".(count($model->whiteList()) + count($rels)).":{' ||";
            $end = "\n$ident    '}') as ser";
        }
        else
        //if ($type === '1toN')
        {
            $sql .= "\n$ident    'a:' || count(1) || ':{' ||\n$ident      str_concat('i:' || {$alias}id || ';' || 'a:".(count($model->whiteList()) + count($rels)).":{' ||";
            $end = "\n$ident        '}'\n$ident      ) ||\n$ident    '}' as ser";
        }
        
		$sql .= $this->_modelArray($model, $ident);

        $from_sql = "\n{$ident}FROM ".$model->generator();
        foreach($rels as $name => $val)
        {
            $cnt = ++$counter;
            $from_sql .= "\n{$ident}LEFT JOIN (".$this->__getArraySql($val,$counter,$ident.'    ')."\n$ident) tab$cnt ";
            //var_dump($val['_def']);
            $from_sql .= "ON (".
                "$alias".implode(", $alias",array($val['_def']['foreign_key'])).
                ") = (".
                "tab{$cnt}.".implode(", tab{$cnt}.",array($val['_def']['target_key'])).")\n$ident";
            
            $sql .= "\n$ident        's:".strlen($name).":\"$name\";' || COALESCE(tab{$cnt}.ser,'a:0:{}') ||";
        }
        $sql .= $end;
        if (NULL !== ($filter = $model->getFilter()))
            $from_sql .= "\n{$ident}WHERE ".$filter->generator()."\n$ident";
        //$from_sql .= "";
        if (!empty($foreign_key))
            $from_sql .= "\n{$ident}GROUP BY $alias".implode(", $alias",$target_key);
        //else
        //    $from_sql .= "1";
        return($sql.$from_sql);
    }
    
    private function _modelArray(Model $model, $ident)
    {
        //$fields = $model->getFields();
        $whitelist = $model->whiteList();
        //var_dump($whitelist);
        $alias = $model->alias().'.';
        $sql = '';
        foreach($whitelist as $name=>$nr)
		{
            //var_dump($name);
            $field = $model[$name];           
            $sql .= "\n$ident        's:".strlen($name).":\"$name\";";
            $sql .= "s:' || octet_length({$field}::text) || ':\"' || {$field} || '\";' ||";
		}
        return($sql);
    }
    
    
}

class ModelExtension
{
    protected $_extended_model;
    
    public function __construct($extended_model)
    {
        $this->_extended_model = $extended_model;
    }        
}

interface IPresentableModel extends IModel
{
    
}


/**
class AbstractModel extends Model
{
}
*/

//// DATASETY 

/**
* @param fields array of Field.
*/
/*
function aggregate(DataSet $ds, $fields, $group_by)
{
    return(new Aggregate($ds, $fields, $group_by));
}

function addJoin(DataSet $ds1, DataSet $ds2, $joinType, $on)
{
    return(new Join($ds1,$ds2,$joinType));
}

function filter(DataSet $ds, $where)
{
    $ds->addWhere($where);
}

function page(DataSet $ds, $page,$perpage)
{
// yeah!
}

$ds = aggregate(
        addJoin(
            addJoin($objects,$messages,'t0.element_id=t1.message_id AND t0.type=2'), 
            $connections,
            't1.conn_o_from = t00.object_id' 
        ),
        't001.message_reciever'
    );
    
$ds->setMargins(0,20);    
echo $ds -> query();
$res = $ds->execute();
*/
/** aliasowanie:
* aliasy nadawane s z uwzgldnieniem drzewa operacji prowadzcego do skonstruowania 
* dataseta zgodnie z powyzszym zapisem. 
* Kady kombinowany dataset kae przealiasowa fieldy i generatory w swoich skladowych 
* datasetach poprzedzajc numer aliasu numerem argumentu. 
* 
*/



