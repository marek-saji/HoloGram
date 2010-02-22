<?php
g()->load('DataSets',null);

class DirectedGraphME extends ModelExtension
{
    protected $_connection_rules = array();
    protected $_connection_object;

    public function __construct($extended_model,$table_name,$connections_model=null)
    {
        parent::__construct($extended_model);
        $type = $this->getType();
        $name = $extended_model->getName();
        if($connections_model != null)
            $this->__connections_object = $connections_model;
        else
            $this->__connections_object = $this->createConnector($table_name);
        
        foreach($this->_connection_rules as $from=>$connection)
        {
            if($from==$name)
            {
                $this->_extended_model->relate($from.$type.'Connections',$this->__connections_object,'1toN',$connection['key'],'from_id');
                $this->__connections_object->relate('Connections'.$type.$from.'L',$from,'Nto1','from_id',$connection['key']);
                foreach($connection['values'] as $to)
                    $this->__connections_object->relate('Connections'.$type.$to['model'].'R',$to['model'],'Nto1','to_id',$to['key']);
            }
            else
            foreach($connection['values'] as $to)
            {
                if($name==$to['model'])
                {
                    $this->__connections_object->relate('Connections'.$type.$from.'L',$from,'Nto1','from_id',$connection['key']);
                    $this->__connections_object->relate('Connections'.$type.$to['model'].'R',$to['model'],'Nto1','to_id',$to['key']);
                }
            }
        }
    }
    
    public function createConnector($table_name)
    {
        return new DConnections($table_name);
    }


    public function addConnection($from,$f_key='',$to=array())
    {
        $this->_connection_rules[$from]['key'] = $f_key;
        foreach($to as $key=>$model)
        {
            $this->_connection_rules[$from]['values'][] = array('key'=>$key, 'model' => $model);
        }
    }
    
    public function getType()
    {
        return(substr(get_class($this),0,-2));
    }
    
    public function getConnector()
    {
        return $this->__connections_object;
    }
    
    /**
     * Returns children number of given element.
     * @author m.wierzba
     *
     * @param $id the id number of the element
     * @return integer number of children
     */
    public function getChildrenCount($id)
    {
        $id = pg_escape_string($id);
        $sql = "from_id".(($id=='')?" is NULL ":"={$id} ");
        $this->__connections_object->filter($sql);
        return $this->__connections_object->getCount();
    }
    
    /**
     * Returns parents number of given element.
     * @author m.wierzba
     *
     * @param $id the id number of the element
     * @return integer number of parents
     */
    public function getParentsCount($id)
    {
        $id = pg_escape_string($id);
        $sql = "to_id".(($id=='')?" is NULL ":"={$id} ");
        $this->__connections_object->filter($sql);
        return $this->__connections_object->getCount();
    }
    
    /**
     * Gets the given element's successors ids.
     *
     * @param $id the id number of the element
     * @return null|array of successors ids 
     */
    public function getSuccessors($id)
    {
        $id = pg_escape_string($id);
        $this->__connections_object->whiteList(array('to_id'));
        $sql = "from_id".(($id=='')?" is NULL ":"={$id} ");
        $this->__connections_object->filter($sql);
        return g()->db->getAll($this->__connections_object->query(false));
    }
    
    /**
     * Gets the given element's predecessors ids.
     *
     * @param $id the id number of the element
     * @return null|array of predecessors ids 
     */    
    public function getPredecessors($id)
    {
        $id = pg_escape_string($id);
        $sql = "to_id".(($id=='')?" is NULL ":"={$id} ");
        $this->__connections_object->filter($sql);
        return g()->db->getAll($this->__connections_object->query(false));
    }
    
    /**
     * Creates and returns Relation object, which represents connection to
     * the children model.
     * 
     * @param $model object we create a relation with
     * @return Relation
     */    
    public function getChildrenRelation($model)
    {
        return new Relation($this->getConnector(),'Connections'.$this->getType().$model->getName().'R');
    }
    
    /**
     * Checks if there is a connection beetwen two objects.
     * @param $from represents from_id in db
     * @param $to represents to_id in db          
     * @return true or false, depending on answer from db
     * @author d.wegner          
     */         
    public function isConnection($from,$to)
    {
        $from = pg_escape_string($from);
        $to = pg_escape_string($to);
        $sql = "from_id = {$from} AND to_id = {$to}";
        $this->__connections_object->whiteList(array('from_id','to_id'));
        $this->__connections_object->filter($sql);
        $count = $this->__connections_object->getCount();
        return (($count == 1)? true : false);
    }
    
}


class DConnections extends Model
{
    public function __construct($table_name)
    {
        $this->_table_name = $table_name;
        parent::__construct();
        $this->__addField( new FInt('from_id',8,true));
        $this->__addField( new FInt('to_id',8,true));
        $this->__pk('from_id','to_id');
        $this->whiteListAll();
    }
}