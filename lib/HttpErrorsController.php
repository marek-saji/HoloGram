<?php

global $kernel;
$kernel->load('Pages', 'controller');

class HttpErrorsController extends PagesController
{

    public function actionError404()
    {
        $this->assign('error404_message','Error 404');
    }

}

?>
