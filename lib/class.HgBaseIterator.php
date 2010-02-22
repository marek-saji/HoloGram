<?php
/**
 * Base of iteratable classes.
 *
 * This classes' constructor have to be called after setting $this->_iterator
 * to not-null (possibly reference to some other property).
 * You can foreach this class.
 *
 * @author m.augustynowicz
 */
abstract class HgBaseIterator extends HgBase implements Iterator
{
    protected $_iterator = null;

    public function __construct(array $params = array())
    {
        if (null === $this->_iterator)
        {
            throw new HgException('No iterator property set for '.__CLASS__);
        }

        return parent::__construct($params);
    }

    public function rewind()
    {
        reset($this->_iterator);
    }

    public function current()
    {
        return current($this->_iterator);
    }

    public function key()
    {
        return key($this->_iterator);
    }

    public function next()
    {
        return next($this->_iterator);
    }

    public function valid()
    {
        return false !== $this->current();
    }

}

