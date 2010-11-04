<?php

g()->load('DataSets',null);
g()->load('Pages','controller');

class DataSetController extends PagesController
{
    protected $_datasets=array();
    protected $_model_form;
	protected $_ds;
	protected $_relations=array();
	public $forms = array('models' => true);


    protected function _onAction($action, array & $params)
    {
        if (!g()->debug->allowed())
        {
            $this->redirect(array('HttpErrors', 'error404'));
        }
        return true;
    }


    public function process(Request $req=null)
    {
        parent::process($req);
        if($table=$this->getChild('Page'))
            $table->init();
    }

    public function actionDefault(array $params)
    {
        $sql  = "SELECT rels.relname, dsc.description\n";
        $sql .= "FROM pg_class rels\n";        
        $sql .= "LEFT JOIN pg_namespace nss ON rels.relnamespace = nss.oid\n";
        $sql .= "LEFT JOIN pg_description dsc ON dsc.objoid = rels.oid\n";
        $sql .= "WHERE nss.nspname='public' AND rels.relkind='r' AND (dsc.objsubid=0 OR dsc.objsubid IS NULL)\n ORDER BY rels.relname";
        $res = g()->db->getAll($sql);

        foreach ($res as &$relation)
        {
            if (!empty($relation['description']) && preg_match('/model:([a-zA-Z0-9]+)/',$relation['description'],$regs))
                $relation = $regs[1];
            else
            {
                $relation = explode('_',$relation['relname']);
                foreach ($relation as &$name_part)
                    $name_part= ucfirst($name_part);
                $relation = implode('',$relation);
            }
            
            $relation = array($relation);
            if(!$name = g()->load($relation[0],'model',false))
                $relation['class'] = 'missing';
            else
            {
                $class = new ReflectionClass($name);
                $abstract = $class->isAbstract();
                if($abstract)
                {
                    $relation['class'] = 'abstract';
                }
                else
                {
                    $dd = g($relation[0],'model');
                    $diff = $dd->checkModelInDb();
                    if (true === $diff)
                        $class = 'correct';
                    elseif (false === $diff)
                        $class = 'missing';
                    else
                        $class = 'incorrect';
                }
                $relation['class'] = $class;

            }
           
            
            $this->_datasets[] = $relation;
        }
    }
    
    
    public function actionList($args)
    {
        $libs = array();
        $dirs = $GLOBALS['DIRS'];
        foreach ($dirs as $dir => $alias)
        {
            $paths = glob($dir.'lib/*Model.php');
            $short_dir = preg_replace('!'.preg_quote(APP_DIR).'!', '', $dir);
            $libs[$short_dir] = compact('alias', 'dir', 'short_dir');
            $libs[$short_dir]['models'] = array();
            foreach ($paths as $path)
            {
                $short_name = preg_replace('!^'.preg_quote($dir).'lib/|Model\.php$!', '', $path);
                $name = g()->load($short_name, 'model', false);
                $reflection = new ReflectionClass($name);
                if ($abstract = $reflection->isAbstract())
                    $class = 'abstract';
                else
                {
                    $diff = g($short_name, 'model')->checkModelInDb();
                    if (true === $diff)
                        $class = 'correct';
                    elseif (false === $diff)
                        $class = 'missing';
                    else
                        $class = 'incorrect';
                }
                $libs[$short_dir]['models'][$short_name] = compact('name','short_name','class');
            }
        }
        $this->assign(compact('libs'));
    }
    
    public function modelsOnly(&$file_name)
    {
        if (0 === preg_match('/(.*)Model\.php$/',$file_name,$regs))
            return(false);
        $file_name = $regs[1];
        return(true);
    }
    
    public function _prepareActionShow(array &$args)
    {
        $this->_isSupportedDs($args);
        $this->addChild($tab = g('Table','controller',array('name'=>'Page','parent'=>$this, 'subject'=>$this->_ds)));
        $tab->addRecordAction($this->url2a('Edit'),array('_ds','_pk'),'edytuj');
    }
    
    public function actionShow($args)
    {
    /*
    'Books'             //'id', 'publishers_id', 'authors_id', 'title', 'description'
    'Publishers',       //'id', 'name', 'description'
    'Authors',          //'id', 'name', 'description'
    'BooksCategories'
    'AuthorsCategories'
    */
        /*$arr = array (
            '0' => array (
                '',
                
            ),
            '1' =>*/

        $diff = $this->_ds->checkModelInDb();
		if (true !== $diff && (false === $diff || !empty($diff['not_in_base']) || !empty($diff['def_diff'])))
		    //$this->assign('model_invalid',true);
            $this->redirect($this->url2a('comp', array($this->_dsName())),false,false);
        else
        {
            if (!empty($diff['not_in_model']))
                //$this->assign('obsolete',true);
                $this->redirect($this->url2a('comp', array($this->_dsName())));
            
            //$rel = $this->_ds->rel('Books>Publishers');
            //$res = $rel->getArray();
            //echo "<pre>";print_r($res); echo"</pre>";
		    //$this->_ds->exec();
        }
        
        
    }

    /**
     * @author p.piskorski - first version
     * @author m.izewski - indexes and triggers support
     */	
    public function actionComp($args)
    {
        $this->_isSupportedDs($args);
        $diff = $this->_ds->checkModelInDb();
        $sql = '';
        $idx_sql = array();
        $trg_sql = array();

        //if table was not found in db
        if (false === $diff)
        {
            $sql = $this->_ds->tableDefinition();
        }
        //if table was found in db
        else
        {
            ############
            ## FIELDS ##
            ############
            if (!empty($diff['not_in_base']['fields']) || !empty($diff['not_in_model']['fields']) || !empty($diff['def_diff']['fields']))
                $sql .= "ALTER TABLE \"".$this->_ds->getTableName()."\"\n";
            elseif (
                empty($diff['not_in_base']['indexes']) && empty($diff['not_in_model']['indexes']) && empty($diff['def_diff']['indexes'])
                && empty($diff['not_in_base']['triggers']) && empty($diff['not_in_model']['triggers']) && empty($diff['def_diff']['triggers'])
            )
            {
                $this->assign('match',true);
                return;
            }
            
            //if filed was added in model
            if (!empty($diff['not_in_base']['fields']))
            {
                $sql .= "-- adding non-existing columns\n";
                foreach($diff['not_in_base']['fields'] as $name => $new_field)
                {
                    $sql .= "    ADD COLUMN ".$new_field->columnDefinition().",\n";
                }
            }

            //if field was deleted in model
            if (!empty($diff['not_in_model']['fields']))
            {
                $sql .= "-- deleteing obsolete columns\n";
                foreach($diff['not_in_model']['fields'] as $name => $new_field)
                {
                    $sql .= "    DROP COLUMN \"{$name}\",\n";
                }   
            }

            //if field was changed in model 
            if (!empty($diff['def_diff']['fields']))
            {
                $sql .= "-- altering changed columns\n";
                foreach($diff['def_diff']['fields'] as $name => $fdiff)
                {
                    foreach ($fdiff as $prop => $val)
                    {
                        switch($prop)
                        {
                            case 'defval':
                                if ($val)
                                    $sql .= "    ALTER COLUMN \"{$name}\" SET DEFAULT $val,\n";
                                else
                                    $sql .= "    ALTER COLUMN \"{$name}\" DROP DEFAULT,\n";
                                break;
                            case 'notnull':
                                //if ($val)
                                //    $sql .= "    UPDATE ".$this->_ds->getTableName()." SET $name = DEFAULT WHERE $name IS NULL\n/*option*/";
                                $sql .= "    ALTER COLUMN \"{$name}\" ".(g('Functions')->anyToBool($val) ? 'SET' : 'DROP')." NOT NULL,\n";
                                break;
                            case 'typename':
                                $sql .= "    ALTER COLUMN \"{$name}\" TYPE $val,\n";
                                break;
                            case 'type_specific':
                                $sql .= "    -- YOU WILL HAVE TO MANUALLY change type specific (ex. length) in column `$name`:\n";
                                $sql .= "/*\n" . var_export(array($prop=>$val), true) . "\n*/\n";
                                break;
                        }
                    }
                }
            }
            if(!empty($sql))
                $sql[strrpos($sql,',')]=' '; //substitute the last comma
            
            ##############
            ## TRIGGERS ##
            ##############
            //if trigger is added in model
            if(!empty($diff['not_in_base']['triggers']))
            {
                foreach($diff['not_in_base']['triggers'] as $name => $def)
                {
                    $trg_sql[$name]  = "CREATE TRIGGER {$name} {$def['when']} " . implode(' OR ', $def['events']) . " ON \"{$this->_ds->getTableName()}\"\n      ";
                    if($def['row'])
                        $trg_sql[$name] .= ' FOR EACH ROW ';
                    $trg_sql[$name] .= "EXECUTE PROCEDURE {$def['exec']}";
                }
            }
           
            //if trigger is removed in model 
            if(!empty($diff['not_in_model']['triggers']))
            {
                foreach($diff['not_in_model']['triggers'] as $name => $def)
                    $trg_sql[$name] = "DROP TRIGGER {$name} ON {$this->_ds->getTableName()}";
            }

            //@todo add suport for trigger change
            
            #############
            ## INDEXES ##
            #############

            //if index is added in model
            if(!empty($diff['not_in_base']['indexes']))
            {
                foreach($diff['not_in_base']['indexes'] as $name => $def)
                    $idx_sql[$name]  = "CREATE INDEX {$name} ON \"{$this->_ds->getTableName()}\" USING {$def['type']}({$def['expr']})";
            }

            if(!empty($diff['not_in_model']['indexes']))
            {
                foreach($diff['not_in_model']['indexes'] as $name => $def)
                    $idx_sql[$name] = "DROP INDEX {$name}";
            }

            //@todo add suport for index change
        }


        if(empty($sql))
            unset($sql);

        //assign sql's
        $sql = array_merge(compact('sql'),$trg_sql,$idx_sql);
        $this->assign(compact('sql'));

        if (isset($args[1]) && $args[1]==='execute')
        {
            if(!empty($_POST['part']))
                foreach($_POST['part'] as $name => $opt)
                    g()->db->execute($sql[$name]);
            else
                foreach($sql as $name => $cmd)
                    g()->db->execute($cmd);

            $this->redirect('DataSet/Show'.g()->conf['link_split'].$this->_ds->getName());
        }
    }
    
    public function actionAdd($args)
    {
        $this->_isSupportedDs($args);
        if(isset($this->data['add_to_ds']) && $this->_validated['add_to_ds'])
        {
            $sql = $this->_ds->sync($this->data['add_to_ds'],true,'insert');
            if(is_array($sql) && $sql)
            {
                $errors = $sql;
                g()->addInfo(null, 'error', $this->trans('Adding failed, sorry! Please try again.'));
            }else{
                g()->addInfo(null, 'info', $this->trans('Adding completed!'));
                unset($this->data['add_to_ds']);
            }
        }
        $this->assign('ds',$this->_ds);
    }
    
    public function actionEdit($args)
    {
        $this->_isSupportedDs(array($args['ds']));
        if(isset($this->data['edit_in_ds']) && $this->_validated['edit_in_ds'])
        {
            $sql = $this->_ds->sync($this->data['edit_in_ds'],true,'update');
            if(is_array($sql) && $sql)
            {
                $errors = $sql;
                g()->addInfo(null, 'error', $this->trans('Editing failed, sorry! Please try again.'));
            }else{
                g()->addInfo(null, 'info', $this->trans('Editing completed!'));
            }
        }

        $pks = $this->_ds->getPrimaryKeys();
        $this->_ds->whiteListAll();
        $filter = array();
        foreach($pks as $pk)
        {
            $filter[] = "$pk = '".pg_escape_string($args[$pk])."'";
        }
        $this->_ds->filter(implode(' AND ',$filter));
        $this->data['edit_in_ds'] = g()->db->getRow($this->_ds->query(false));

        $this->assign('ds',$this->_ds);
    }
    
    
	public function actionCreate($args)
	{
		$this->_isSupportedDs($args);
		g()->db->execute($this->_ds->tableDefinition());	
		g()->req->addAction($this,'Show',$args);
	}
	
	
	public function contents()
	{
	    $this->inc('header');
		parent::contents();
	}
	
	
	protected function _isSupportedDs($args)
	{	    
        $valid =false;
        do
        {
            if (!isset($args[0]))
            {
                g()->addInfo(NULL,'error',"No model given");
                break;
            }
            if (!preg_match('/^([a-zA-Z]+)$/',$args[0]))
            {
                g()->addInfo(NULL,'error',"Invalid model name: {$args[0]}");
                break;
            }
            if (!g()->load($args[0],'model',false))
            {
                g()->addInfo(NULL,'error',"No such model: {$args[0]}");
                break;
            }
            $valid=true;
        }
        while(0);
	    if (!$valid)
			$this->redirect('HttpErrors/Error404');

        $this->_ds = g($args[0], 'model');
		return(true);
	}
	
	public function _dsName()
	{
	    return(substr(get_class($this->_ds),0,-5));
	}
	
}
