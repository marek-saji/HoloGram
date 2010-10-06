<?php
g()->load('DataSets', null);

/**
 * Values for a log information row.
 *
 * Used to store informations about just key=>val pair or with additional
 * old_val value. Depends on log.with_old_values.
 * @author m.augustynowicz
 */
class LogValueModel extends Model
{
    /**
     * Adding fields, relations and primary keys
     * @author m.augustynowicz
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // fields

        $this->_addField(new FId('log_id'));
        $this->_addField(new FString('property', true));
        $this->_addField(new FString('value', false));
        $this->_addField(new FString('new_value', false));


        // relations

        $this->relate('Log', 'Log', 'Nto1', 'log_id', 'log_id');


        $this->_pk('log_id', 'property');

        $this->whiteListAll();
    }
}

