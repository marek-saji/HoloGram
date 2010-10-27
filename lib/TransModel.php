<?php
/**
 * Translations made with translator UI.
 *
 * These are used by {@see HgBase::trans()}, only in non-producction
 * environments.
 * @author m.augustynowicz
 */
class TransModel extends Model
{
    public function __construct()
    {
        $this->_addField(new FString('lang', true, null, 2, 3));
        $this->_addField(new FString('context', true, null, 1, 128));
        $this->_addField(new FString('key', true, null, 1));
        $this->_addField(new FBool('value_is_complex', true, false));
        $this->_addField(new FString('value'));
        $this->_addField(new FString('comment'));
        $this->_addField(new FBool('added_automatically', true, false));

        $this->relate('Lang', 'Lang', 'Nto1', 'code', 'lang');

        $this->_pk('lang', 'context', 'key');

        $this->whiteListAll();

        parent::__construct();
    }
}

// vim:fenc=utf-8:ft=php:ai:si:ts=4:sw=4:et:nu:fdm=indent:fdn=1:

