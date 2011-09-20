<?php

/**
 * Paginating for DataSet
 *
 * USAGE:
 * add as a child and then (e.g. named 'p'):
 * <code>
 * // set margins on a model
 * $this->getChild('p')->setMargin($model);
 * // or, to change default results per page setting:
 * $this->getChild('p')->setMargin($model, 42);
 * // or just get pagination data (won't touch $model)
 * $this->getChild('p')->config($model);
 * $margins = $this->getChild('p')->getMargins();
 * // or get pagination settings for 1000 objects (42 per page)
 * $this->getChild('p')->config(1000, 42)->getMargins();
 * </code>
 *
 * @author m.augustynowicz
 */
class PaginatorController extends Component
{
    /**
     * Number of items
     */
    protected $_count = null;
    /**
     * Number of items per page
     */
    protected $_perpage = 7;
    /**
     * Page we are on.
     */
    protected $_current_page = 1;
    /**
     * Total pages
     */
    protected $_total_pages = null;

    /**
     * Count things.
     * 
     * @param null|DataSet|integer $count_source when DataSet, will call it's
     *        getCount() to obtain item count
     * @param null|integer $perpage number of items per page
     *
     * @return PaginatorController $this
     */
    public function config($count_source = null, $perpage = null)
    {
        $this->_setTemplate($this->_default_action);
        if(null !== $count_source)
        {
            if(is_a($count_source, 'DataSet'))
                $this->_count = $count_source->getCount();
            else
                $this->_count = $count_source;
        }
        if(null !== $perpage)
            $this->_perpage = $perpage;
        /** @todo maybe implement obtaining $perpage from parent */
        /*
        else if (method_exists($this->getParent(), 'getPerpage'))
            $this->_perpage = $this->getParent()->getPerpage();
         */
        $this->_total_pages = ceil($this->_count / $this->_perpage);
        return $this;
    }

    /**
     * Set margins on given DataSet
     *
     * @param DataSet $ds
     * @param null|integer $perpage passed to {@see config()}
     *
     * @return DataSet $ds, to allow chaining
     */
    public function setMargins(DataSet $ds, $perpage = null)
    {
        if(null === $this->_count)
            $this->config($ds, $perpage);
        $margins = $this->getMargins();
        return $ds->setMargins($margins[0], $margins[1]);
    }

    /**
     * Get margin boundaries
     *
     * @return array
     */
    public function getMargins()
    {
        $from = ($this->_current_page - 1) * $this->_perpage;
        $to = $from + $this->_perpage;
        return array(
            $from,
            $to,
            $to - $from
        );
    }

    /**
     * Nothing much to explain
     *
     * @param array $params URL params
     */
    public function actionDefault(array $params)
    {
        if(!$this->_current_page = (int)@$params[0])
            $this->_current_page = 1;
    }

    /**
     * Render enhanced with caching.
     * @return void
     */
    public function render()
    {
        static $cache = null;
        if(null != $cache)
        {
            echo $cache[1];
            return $cache[0];
        }
        ob_start();
        $cache = array(
            parent::render(),
            ob_get_flush()
        );
    }


    /**
     * Total items count
     * @author m.augustynowicz
     *
     * @return int
     */
    public function getCount()
    {
        return $this->_count;
    }


    /**
     * Number of items per page
     * @author m.augustynowicz
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->_perpage;
    }


    /**
     * Page number we are on.
     * @author m.augustynowicz
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->_current_page;
    }


    /**
     * Total pages count
     * @author m.augustynowicz
     *
     * @return int
     */
    public function getPagesCount()
    {
        return $this->_total_pages;
    }


    /**
     * Current's page first item index in global scope
     * @author m.augustynowicz
     *
     * @return int
     */
    public function getFirstItemIndex()
    {
        return 1 + ($this->_current_page-1)*$this->_perpage;
    }

}
