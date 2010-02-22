<?php

/**
 * Reporting errors in hologram-2.0 style.
 * For compatibility's sake.
 */
class Errors extends HgBase
{

    public function addErrorInternal($msg)
    {
        //throw new HgException("Someone called deprecated addErrorInternal with a message:\n$msg\n");
        if(g()->conf['allow_debug'])
        {
            trigger_error("Internal error: ".$msg);
        }
    }

    public function addError($id)
    {
        $value = g('Languages')->getTranslatedString($id);
        $argv = func_get_args();
        $value = $this->_parse($value, $argv);
        g()->addInfo(null, 'error', $value);
    }

    public function addNotice($id)
    {
        $value = g('Languages')->getTranslatedString($id);
        $argv = func_get_args();
        $value = $this->_parse($value, $argv);
        g()->addInfo(null, 'notice', $value);
    }

    public function _parse($value, $args)
    {
        if($value)
        {
            //sprawdzanie czy zostały podane kolejne parametry oprócz $id
            if (sizeof($args) > 1)
            {
                foreach ($args as $key => $arg)
                {
                    //podmienie wartość {1}, {2}, ... na podane parametry
                    $value = str_replace('{'.$key.'}', $arg, $value);
                }
                //usuwanie pozostałych znaczników typu {11}
                $notice_value = preg_replace('/\{[0-9]*\}/', '', $value);
            }
            //przypisywanie błędu do sessji
            return $value;

        }else return $value;
    }

}

