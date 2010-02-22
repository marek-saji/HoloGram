<?php
g()->load('DataSets', null);

class LangModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->__addField(new FId('id'));
        $this->__addField(new FString('name', true, null, null, 100));
        $this->__addField(new FString('code', true, null, null, 5));

        $this->__pk('id');
        $this->whiteListAll();
    }

}
