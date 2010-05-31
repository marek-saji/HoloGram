<?php
g()->load('DataSets', null);

/**
 * "Smart" join of base and lang models
 *
 *
 * USAGE:
 * // Foo has _pk('id')
 * // FooLang has _pk('id','lang')
 * $ds = g('MultiLang', 'view', array('Foo'));
 * // OR
 * $ds = g('MultiLang', 'view', array($foo_model));
 *
 * It acts like a Join, it basically is a join.
 *
 * NOTE: when using order() MultiLangView is forced to use subqueries,
 *       which are less efficient than not using them. Don't sort, if you
 *       don't need to.
 *
 * @author m.augustynowicz
 */
class MultiLangView extends Join
{
    protected $_guess_lang = true; // will we be guessing the best lang?

    protected $_in_subquery = false; // detemine whether we are generating subquery

    /**
     * //(IDataSet $base_ds=null, IDataSet $lang_ds=null, $on=null, $join_type= 'left')
     * @param array $params
     *         [0] IDataSet|string $base_ds model with non-lang fields
     *             (instance or name)
     *         [1] IDataSet|string|null $lang_ds model with lang fields
     *             (if null passed -- use base model + Lang suffix)
     *         [2] null|IBoolean $on ON condition
     *             (defaults to base_ds.id = lang_ds.id)
     *         [3] null|string $join_direction defaults to 'left'
     */
    public function __construct($params)
    {
        @list($base_ds, $lang_ds, $on, $join_direction) = $params;

        if (is_string($base_ds))
        {
            $base_ds = g($base_ds, 'model');
        }

        if (!is_a($base_ds, 'IDataSet'))
        {
            throw new HgException('Wrong base DS not passed to MultiLangView');
        }

        if (is_string($lang_ds))
        {
            $lang_ds = g($lang_ds, 'model');
        }
        else if (null === $lang_ds)
        {
            $lang_ds_name = $base_ds->getName().'Lang';
            if (false === g()->load($lang_ds_name, 'model', false))
            {
                throw new HgException('Lang DS `'.$lang_ds_name.'\' not found');
            }
            $lang_ds = g($lang_ds_name, 'model');
        }

        if (!is_a($lang_ds, 'IDataSet'))
        {
            throw new HgException('Wrong language DS not passed to MultiLangView');
        }

        if (!$lang_ds['id'] || !$lang_ds['lang_id'])
        {
            throw new HgException('Passed DS does not look like a valid language DS');
        }


        if (null === $on)
            $on = new FoBinary($base_ds['id'], '=', $lang_ds['id']);

        if (null === $join_direction)
            $join_direction = 'left';

        if (!is_a($on, 'IBoolean'))
        {
            if (false === $type = get_class($on))
                $type = gettype($on);
            throw new HgException('Incorrect $on parameter type: '.$type);
        }

        parent::__construct($base_ds, $lang_ds, $on, $join_direction);

        $this->whiteListAll();
    }


    /**
     * Detecting if lang_id and/or ver given.
     * And adding lang_id selection when guessing lang.
     */
    public function filter($condition)
    {
        if (!is_array($condition))
        {
            throw new HgException(__CLASS__.' supports only filtering with arrays');
        }

        $this->_guess_lang = true;
        foreach ($condition as $k => & $v)
        {
            if (is_string($k)) // field_name => value
                $field_name = $k;
            else if (is_array($v)) // array(field_name,operator,value)
                $field_name = $v[0];
            else
            {
                throw new HgException('Filter notation unsupported by '
                    . __CLASS__ . ': '
                    . print_r($k,true).'=&gt;'.print_r($v,true)
                );
            }
            if ('lang_id' == $field_name)
            {
                $this->_guess_lang = false;
                break;
            }
        }

        return parent::filter($condition);
    }


    /**
     * Injecting ordering needed by DISTINCT,
     */
    public function query($pages=true, $join=null)
    {
        $need_subquery = (bool) $this->_order;

        if ($need_subquery)
        {
            $this->_in_subquery = true;
        }

        $result = parent::query($pages, $join);

        if ($need_subquery)
        {
            $this->_in_subquery = false;
            $result = $this->_ident(trim($result));
            $result = sprintf("SELECT * FROM (\n  %s\n) AS ObjViewSubQuery%s",
                    $result, $this->_queryOrderBy() );
        }

        return $result;
    }

    protected function _queryOrderBy()
    {
        if ($this->_in_subquery || empty($this->_order))
        {
            $prev_order = $this->_order;
            $this->_order = array();

            // adding ordering needed by DISTINCT
            if ($this->_guess_lang)
            {
                // SELECT DISTINCT ON expressions must match initial ORDER BY expressions
                if (!$this->_order)
                    $this->_order = array();
                foreach ($this->_first->getPrimaryKeys() as $field)
                {
                    array_unshift($this->_order, array(
                            'field' => $this->_first[$field],
                            'dir' => ''
                        ));
                }

                if (!$this->_guess_lang)
                    $this->order('lang');
                else
                {
                    $second = $this->_joins[0]['ds'];
                    $lang_field = $second['lang_id'];
                    $lang_name = $lang_field->generator();

                    foreach ($this->_getLangOrder(false) as $lang)
                    {
                        $this->_order['lang_preference_'.$lang] = array(
                                'field' => new FoStatement(
                                    $lang_name . '=' .
                                    $lang_field->dbString($lang),'IBoolean'),
                                'dir' => 'DESC'
                            );
                    }
                    $this->_order['lang_preference'] = array(
                            'field' => new FoStatement($lang_name, 'IBoolean'),
                            'dir' => 'ASC',
                        );
                }
            }

            $ret = parent::_queryOrderBy();
            $this->_order = $prev_order;
        }
        else // not in subquery
        {
            $alias1 = $this->_first->_alias;
            $this->_first->_alias = '';
            $aliases = array();
            foreach ($this->_joins as $k=>&$j)
            {
                $aliases[$k] = $j['ds']->_alias;
                $j['ds']->_alias = '';
            }
            unset($j);

            $ret = parent::_queryOrderBy();

            $this->_first->_alias = $alias1;
            foreach ($aliases as $k=>$a)
                $this->_joins[$k]['ds']->_alias = $a;
        }

        return $ret;
    }

    /**
     * Distinction on primary keys (id)
     */
    protected function _distinctionSQL()
    {
        return sprintf("DISTINCT ON (%s)", $this->_getGeneratedPrimaryKeys());
    }

    /**
     * Caching generated primary keys
     */
    protected function _getGeneratedPrimaryKeys()
    {
        $pks = $this->_first->getPrimaryKeys();
        foreach ($pks as &$pk)
            $pk = $this->_first[$pk];
        $pks = join(', ', $pks);
        return $pks;
    }

    /**
     * Injecting DISTINCT.
     */
    public function getCount()
    {
	    if (NULL !== $this->_count)
		    return($this->_count);
        $gen = $this->_ident($this->generator());
        $sql = sprintf("SELECT\n%s\n%s\nFROM\n%s",
                $this->_ident($this->_distinctionSQL()),
                $this->_ident('COUNT(1)'),
                $this->_ident($gen) );
        if ($this->_filter) 
        {
            $gen = $this->_ident($this->_filter->generator());
            $sql .= "\nWHERE\n".$gen;
        }
        $sql .= "\nGROUP BY ".$this->_getGeneratedPrimaryKeys();
        $sql = sprintf("SELECT COUNT(1)\nFROM (\n%s\n) AS ObjViewSubQuery",
                $this->_ident($sql) );
		$this->_count = g()->db->getOne($sql);
		return($this->_count);
    }

    /**
     * Injecting DISTINCT.
     */
    protected function _getWhitelistedFields()
    {
        if (!$this->_guess_lang)
            $sql = '';
        else
            $sql = $this->_distinctionSQL();

        $sql .= "\n".parent::_getWhitelistedFields();
        return $sql;
    }

    /**
     */
    protected function _getLangOrder($normal_order=true)
    {
        $lang = g()->lang->info(true, 'id');
        $order = array($lang);
        if ($normal_order)
            return $order;
        else
            return array_reverse($order);
    }

}

