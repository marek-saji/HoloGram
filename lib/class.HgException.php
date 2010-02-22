<?php
/**
 * Plik zawiera implementacje klasy obslugujaca wyjatki w Hologramie
 *
 * @author m.augustynowicz
 *
 * @package hologram
 * @version 1.0
 */

/**
 * Klasa do obslugi wyjatkow
 *
 * @author m.augustynowicz
 *
 * @package hologram2
 * @version 1.0
 */
class HgException extends Exception
{
    protected $_displayed = false;

    /**
     * Konstruktor wyjatku. Jezeli w configu zadeklarowano stala NOXDEBUG, to na stronie wyswietlana jest informacja
     * o sladzie wywolania w miejscu rzucenia wyjatku. Metoda wywoluje trigger_error, ktory przekazuyje na strone
     * informacje o bledzie w randze notice'a.
     * @param string $mess Komunikat bledu
     * @param integer $code kod bledu
     * @author p.piskorski
     */
    function __construct($mess='', $code = 0)
    {
        // make sure everything is assigned properly
        parent::__construct($mess, $code);

        //$error_added = $this->db->Execute("INSERT INTO {$this->conf['tables']['internal_errors']} (description,session_id) VALUES('".pg_escape_string($description)."',{$session_id})");
        //jeśli się nie powiedzie zapis do bazy danych zapisuje treść błędu do pliku
        //if(!$error_added)
          //  $this->_logError($description);
    }

    public function __toString()
    {
        global $kernel;
        if ($kernel && $kernel->debug && $kernel->debug->allowed())
        {
            ob_start();
            $message = '<strong style="color:red">[EXCEPTION]</strong> ' . $this->getMessage();
            $trace = $this->getTrace();
            array_unshift($trace, array(
                    'file' => $this->getFile(),
                    'line' => $this->getLine(),
                    'function' => '<small>('.__CLASS__.' has been thrown)</small>',
                    'args' => array(),
                ));
            g()->debug->trace($message, $trace);
            return ob_get_clean();
        }
        else
            return parent::__toString();
    }

}

