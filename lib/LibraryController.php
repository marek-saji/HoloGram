<?php
/**
* 
*/
class LibraryController extends TrunkController
{

    /**
    * Szuka w bibliotece kontroler o nazwie wynikajacej z biezacego elementu, laduje go 
    * i powierza mu dalsze wykonanie akcji. Jezeli odpowiedni kontroler nie istnieje - blad 404.
    */
    public function process(Request $req)
    {
        if(isset(g()->conf['permanent_controllers']))
        {
            foreach(g()->conf['permanent_controllers'] as $controller_name=>$controller_type)
            {
                $controller = g(
                $controller_type,'controller',
                array(
                    'name'=>$controller_name,
                    'parent'=>$this
                ));
                $req->addSubController($controller,false);
            }
        }
        if ($req->getChildrenCount() == count(@g()->conf['permanent_controllers']))
            $req->addAction($this->_conf['default']);

        while ($curr = $req->next())
        {
            try
            {
                $child = g($curr, 'controller', array(
                    'name' => $curr,
                    'parent' => $this,
                ));
            }
            catch (HgException $e)
            {
                if (g()->debug->allowed())
                    throw $e;
                $this->redirect('HttpErrors/Error404');
            }
            $this->addChild($child);
            $child->process($req);
        }
        $req->emerge();
    }
}
