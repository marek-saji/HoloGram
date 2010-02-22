<?php

g()->load('Pages', 'controller');

class HelloController extends PagesController
{
    public function defaultAction(array $params)
    {
        $this->assign('message','Hello World');
        $this->assign('comment','New style Hg2.1 application.');
        //$this->db->getAll('SELECT * FROM object where object_id<10;');
        //$this->db->getAll('SELECT * FROM object where object_id<10;');

        //$tpl = g('Templates');

    }
}

