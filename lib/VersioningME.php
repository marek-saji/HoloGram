<?php
g()->load('DataSets',null);
g()->load('Extendable','model');


class LangDataSet extends ExtendableModel
{
    protected $_i_ds;
    protected $_id; // extension id
    
    public function __construct($i_ds, $id)
    {
        parent::__construct();
        $this->_i_ds = $i_ds;
        $this->_id = $id;
        var_dump("{$this->_id} is extension of ".get_class($i_ds));
    }

    public function ext($name)
    {
        return $this->_i_ds->ext($name);
    }

    public function query($pages=true,$join=null)
    {
        $i_ds_ext = $this->_i_ds->ext($this->_id);
        $version = $i_ds_ext->getVersion()->getName();
        $current_version = $i_ds_ext->currentVersion();
        if(!is_array($current_version))
            $current_version = array($current_version);
        $order = array();
        $pks = $this->_i_ds->getPrimaryKeys();
        foreach($pks as $pk)
        {
            if($pk != $version)
                $order[] = $pk;
        }
        $sql = "SELECT DISTINCT ON (\n";
        $sql .= implode(', ',$order);        
        $sql .= ") ".$this->_i_ds->__getWhitelistedFields()."FROM\n".$this->_i_ds->generator();
        $sql .= "\nWHERE\n ".$version." IN (".implode(", ",$current_version).") ";
        if ($this->_i_ds->_filter) 
            $sql .= "AND ".$this->_i_ds->_filter->generator();

        if($current_version)
            $sql .= "ORDER BY ".implode(', ',$order).", ";
        $order = array();
        $current_version = array_reverse($current_version); // FALSE < TRUE
        foreach($current_version as $c_version)
        {
            $order[] = $version." = ".$c_version;
        }
        $sql .= implode(', ',$order);

        if ($pages)
        {
            if (NULL != $this->_i_ds->_limit)
                $sql .= " LIMIT {$this->_i_ds->_limit} ";
            if (NULL != $this->_i_ds->_offset)
                $sql .= " OFFSET {$this->_i_ds->_offset} ";
        }

        return($sql);
        //SELECT DISTINCT ON (innerobjects_id) innerobjects_id,language_id FROM folders WHERE language_id IN (1,2,3) ORDER BY innerobjects_id, language_id = 2, language_id = 1, language_id = 3
    }
    
    public function generator()
    {
        return $this->_i_ds->generator();
    }

    public function getFields()
    {
        return $this->_i_ds->getFields();
    }    
    
}

class VersioningME extends ModelExtension
{    
    protected $_version;
    protected $_current_version;
    
    public function __construct($extended_model, IField $version, $extension_id)
    {
        parent::__construct($extended_model);
        $this->_version = $version;
        $i_ds = $extended_model->internalDS();
        $lds = new LangDataSet($i_ds, $extension_id);
        $extended_model->internalDS($lds);
    }
    
    public function currentVersion($current_version=null)
    {
        if($current_version==null)
            return $this->_current_version;
        else
            $this->_current_version = $current_version;
    }
    
    public function getVersion()
    {
        return $this->_version;
    }
    
}
