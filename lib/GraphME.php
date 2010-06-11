<?php
g()->load('DataSets',null);

class GraphME extends ModelExtension
{
    protected $_connection_rules = array();
    protected $_connection_object;

    public function __construct($extended_model,$table_name)
    {
        parent::__construct($extended_model);
        $type = $this->getType();
        $name = $extended_model->getName();
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
                    $this->_extended_model->relate($to['model'].$type.'Connections',$this->__connections_object,'1toN',$connection['key'],'from_id');
                }
            }
        }
    }
    
    public function createConnector($table_name)
    {
        return new Connections($table_name);
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
    
}


class Connections extends Model
{
    public function __construct($table_name)
    {
        $this->_table_name = $table_name;
        parent::__construct();
        $this->_addField( new FInt('from_id',8,true));
        $this->_addField( new FInt('to_id',8,true));
        $this->_pk('from_id','to_id');
        $this->whiteListAll();
    }
}