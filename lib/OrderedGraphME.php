<?php
g()->load('Graph','ME');

class OrderedGraphME extends GraphME
{
    public function createConnector($table_name)
    {
        return new OrderedConnections($table_name);
    }
    
    public function getChildrenCount($id)
    {
        $id = pg_escape_string($id);
        $sql = "to_id".(($id=='')?" is NULL ":"={$id} ");
        $this->__connections_object->filter($sql);
        return $this->__connections_object->getCount();
    }
    
}

class OrderedConnections extends Model
{
    public function __construct($table_name)
    {
        $this->_table_name = $table_name;
        parent::__construct();
        $this->__addField( new FInt('from_id',8,true));
        $this->__addField( new FInt('to_id',8,true));
        $this->__addField( new FInt('order_num',4,true));
        $this->__pk('from_id','to_id');
        $this->whiteListAll();
    }
}
