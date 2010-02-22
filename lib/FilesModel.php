<?php
g()->load('DataSets',null);

class FilesModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->__addField( new FId('id'));
        $this->__addField( new FForeignId('dir_id'));
        $this->__addField( new FString('description'));
        $this->__pk(array('id'));
        $this->whiteListAll();
    }
}