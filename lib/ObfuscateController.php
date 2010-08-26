<?php
/**
 * @author b.matuszewski
 */
 
g()->load('POPPages', 'controller');

class ObfuscateController extends POPPagesController
{
    public function actionAjaxDecode(array $params)
    {
        $f = g('Functions');
        $this->assign('id', $_POST[0]);
        $str = $_POST[1];
        $coded = '';
        for($i = 0; $i < strlen($str); $i += 2)
        {
            $tmp_str = $str[$i] . $str[$i+1];
            $coded .= chr(hexdec($tmp_str));
        }
        $decoded = $f->rc4Encrypt($f->getEmailObfuscateKey(), $coded);
        $user_name = explode('@', $decoded);
        $user_name = $user_name[0];
        $this->assign(compact('decoded', 'user_name'));
    }
}
