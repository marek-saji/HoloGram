<?php
g()->load('DataSets',null);
//g()->load('class.ModelExtension.php');
/**
* Simple tree model extension. Combines records of a specific model into a tree
* Modifies a model add operation, as one has to point the parent node whenever 
* a new node is added.
*/
class SimpleTreeME extends ModelExtension
{

    class ChildNodes extends DataSet
    {
	    protected __source_model;
		
        public function __construct(SimpleTreeExtension $parent)
        {
            parent::__construct();
        }
        
        public function parents($parents=array())
        {
        }
        
        public function minLevel($level=-1)
        {
        }
        
        public function maxLevel($level=-1)
        {
        }
        
        public function generator()
        {
            return("simple_tree_children(ARRAY[".implode(',',$this->__parents)."], {$this->__start_level}, {$this->__stop_level} {$this->_alias})");
        }
    }
    
    class NodesPaths extends DataSet
    {
        public function __create(SimpleTreeExtension $parent)
        {
            parent::__construct();
        }
        
        public function nodes($nodes=array())
        {
        }
        
        public function depth($depth=-1)
        {
        }
        
        public function generator()
        {
            return("simple_tree_path(ARRAY[".implode(',',$this->__parents)."],{$this->__depth})");
        }    
    }
    
    
    protected $_desc;
    protected $_name;
    
    
    
    public function __construct(ExtendableModel $model, $basename, FId $node_id)
    {
        //$this->_fields = $descendant->_fields;
        $this->_desc = $descendant;
        $this->_desc->__addField(new FId('parent'));
        $this->_desc->__addField(new FInt('order'));
        $this->_desc->__addField(new FInt('depth'));
        $this->_name = $basename
    }
    
    /**
    * Selects the path to the node
    */
    public function paths($nodes)
    {
        return new NodesPaths($this);
    }
    
    
    
    public function moveBranch($src_node, $dest_node, $order)
    {
    }
    
    public function siblings($node)
    {
        $ds = new ChildNodes($this);
        $ds->children($node['parent_id']);
        $ds->minLevel(1);
        $ds->maxLevel(1);
        return $ds;
    }
    
    
    public function children($node, $start_level =-1, $stop_level=-1)
    {
         return new ChildNodes($this);
    }
    
    /**
    * Selects the contents of a node (subnodes)
    */
    public function contents($node)
    {
    }
    
    
}