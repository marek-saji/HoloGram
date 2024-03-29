<?php
g()->load('DataSets', null);

class LangModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_addField(new FId('id'));
        $this->_addField(new FString('name', true, null, null, 100));
        $this->_addField(new FString('code', true, null, null, 5));

        $this->_pk('id');
        $this->whiteListAll();
    }

}
