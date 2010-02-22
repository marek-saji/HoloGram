<?php
	
	abstract class ExtendableModel extends Model
	{
	    protected $_extensions=array(); //!< model extensions 
	    protected $_internal_ds;
	    
	    public function __construct()
	    {
	       parent::__construct();
	       $this->_internal_ds = $this;
	    }
	    
        public function query($pages=true,$join=null)
        {
            if($this->_internal_ds === $this)
                return parent::query($pages);
            else
                return $this->_internal_ds->query($pages);    
        }
        
        
        public function internalDS(DataSet $internal_ds=null)
        {
            if($internal_ds==null)
                return $this->_internal_ds;
            else
                $this->_internal_ds = $internal_ds;
        }

	    /**
	    public function __call($name, $args)
	    {
	        foreach($this->__inherits as &$inh)
	        {
	            if (method_exists($inh['class'],$name))
	                return(call_user_func_array(array($inh['class'],$name),$args);
	        }
	    }
	    */		
		/////////////////////////////////
	    public function save(&$data)
		{
		    parent::save($data);
			
		}
		/////////////////////////////////
        
        
		public function extendedBy($class)
		{
		    foreach ($this->_extensions as $rel_name => $ext)
			    if(is_a($ext,$class))
				    return($rel_name);
		    return false;
		}

		
		
		public function ext($name)
		{
            if(isset($this->_extensions[$name]))
                return $this->_extensions[$name];
            else
                return null;
		}
		
		/**
	    * Registeres a new model extension.
	    * @param $name the name of extension
	    * @param $model ModelExtension
	    */
	    protected function __extend($name, $model)
	    {
	        $this->_extensions[$name]=$model;
	    }
	}
	
