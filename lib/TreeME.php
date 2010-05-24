<?php
g()->load('DataSets',null);

class TreeME extends ModelExtension
{
    protected $_parent;
    protected $_order;
    
    public function __construct($extended_model,$parent,$order)
    {
        parent::__construct($extended_model);
        $this->_parent = $parent;
        $this->_order = $order;
    }
    
    public function getChildrenCount($id)
    {
        $id = pg_escape_string($id);
        $sql = " {$this->_parent->getName()}";
        $sql .= ($id=='')?" is NULL ":"={$id} ";
        $this->_extended_model->filter($sql);
        return $this->_extended_model->getCount();
    }
    
    public function getChildren($id)
    {
        $id = pg_escape_string($id);
        $sql = " {$this->_parent->getName()}";
        $sql .= ($id=='')?" is NULL ":"={$id} ";
        $this->_extended_model->filter($sql);
        $this->_extended_model->whiteListAll();
        return $this->_extended_model->exec();
    }
    
    public function getParent($id=null)
    {
        if(!$id)
            return null;
        $id = pg_escape_string($id);
        $pk = $this->_extended_model->getPrimaryKeys();
        $sql = " {$pk[0]}={$id} ";
        $this->_extended_model->filter($sql);
        $this->_extended_model->whiteList(array('parent_id'));
        return g()->db->getOne($this->_extended_model->query(false));
    }
    
}

