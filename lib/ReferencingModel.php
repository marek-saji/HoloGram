<?php
g()->load('DataSets',null);
g()->load('Extendable', 'model');

abstract class ReferencingModel extends ExtendableModel
{
    protected $_inner_models=array();
    private $__join=null;

    public function __construct($inner_models=array())
    {
        parent::__construct();
        $this->alias('dsRf');
        $counter=0; 
        foreach($inner_models as $name=>$inner_model)
        {
            if (!$inner_model instanceof InnerModel)
                throw new HgException('Only InnerModels accepted');        
            if(is_int($name))
                $name = $inner_model->getName();
            $counter++;
            $inner_model->alias("dsIn{$this->getName()}$counter");
            $this->_inner_models[$name] = $inner_model;
            $name_id = strtolower($name);
            $this->__addField( new FForeignId("{$name_id}_id",true,$name,8));
            $this->relate('',$inner_model,'Nto1',"{$name_id}_id",'id');
        }
    }
    
    public function query($pages=true,$join=null)
    {
        $this->__join=$join;
        return parent::query();
    }
    
    public function alias($alias='')
    {
        if (!empty($alias))
        {
            $x = 0;
            foreach($this->_inner_models as $k => $inner)
            {
                $x++;
                $inner->alias("dsIn".$alias.$x);
            }
        }
        return(parent::alias($alias));
    }
    

    public function getFields()
    {
        $fields = $this->_fields;
        foreach($this->_inner_models as $name=>$inner_model)
            $fields = array_merge($fields,$inner_model->getFields());
        return($fields);
    }
    
    public function sync(array &$data,$execute=false,$action='update')
    {
        foreach($data as $key=> &$single)
        {
            if (g('Functions')->isInt($key))
                $this->__splitFields($single);
            else
                $this->__splitFields($data);
        }

        return parent::sync($data,$execute,$action);
    }
    
    public function generator()
    {
        // todo split fields
        $gen = parent::generator();
        if (empty($this->_inner_models))
            return($gen);

        if($this->__join !== 'left')
        {
            if($this->__join === null)
                foreach($this->_inner_models as $name=>$inner_model)
                {
                    $name_id = strtolower($name);
                    $gen .= "\nINNER JOIN ".$inner_model->generator()." ON ".$inner_model->alias().'.id = '.$this->alias().'.'.$name_id.'_id';
                }
            elseif(isset($this->_inner_models[$this->__join]))
                $gen = $inner_model->generator();
        }
        return $gen;
    }
    
    protected function __splitFields(&$data)
    {
        foreach($this->_inner_models as $name=>$inner_model)
        {
            $inner_fields = $inner_model->getFields();
            $name = ucfirst($name);
            foreach($inner_fields as $field_name=>$field)
            {
                if(isset($data[$field_name]))
                {
                    $data[$name][$field_name] = $data[$field_name];
                    unset($data[$field_name]);
                }
                elseif(!isset($data[$name]))
                    $data[$name] = array();
            }
        }
        return true;
    }
    
}

abstract class InnerModel extends ReferencingModel
{
    protected $_referenced_model;
    public function __construct($referenced_model)
    {        
        parent::__construct();
        $this->_referenced_model = $referenced_model;
        $this->__addField( new FId('id',8));
        $this->__addField( $f_type =  new FString('type',true,null,3,32));
        $f_type->auto(array($this->_referenced_model,'getName'),array(),true);
        
        $this->__pk('id');
        $this->whiteListAll();
    }
}
