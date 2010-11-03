<?php
g()->load('Pages', 'Controller');


/**
 * Displaying logs added via LogModel::log()
 * @author m.augustynowicz
 */
class LogController extends PagesController
{
    /**
     * FIXME Dirty, dirty hack for including paginator
     */
    public function process(Request $req)
    {
        $this->addChild('Paginator', 'p')->config(null, 20);
        parent::process($req);
        if(!$this->_launched_action)
            $this->launchDefaultAction();
    }


    /**
     * Display list of logs
     * @author m.augustynowicz
     *
     * @param array $params request params
     *
     * @return void
     */
    public function actionDefault(array $params)
    {
        $log_model = g('Log', 'model');
        $ds = $log_model->rel('Owner');
        $ds->whiteListAll();


        // TODO filter, sort etc
        $ds->order('log_id', 'DESC');
        //$this->_filterLog($ds); // overwrittable in applications
        $ds->filter(array(array('log_id','>',50)));
        $this->getChild('p')->setMargins($ds);



        $rows = $ds->exec();
        foreach ($rows as &$row)
        {
            $row['user_display_name'] =
                $row[ g()->conf['users']['display_name_field'] ];
            $row['user_ident'] =
                $row[ g()->conf['users']['ident_field'] ];
        }
        unset($row);
        $this->assignByRef('rows', $rows);
    }


    /**
     * Show details about a log entry
     * @author m.augustynowicz
     *
     * @param array $params request params
     *        - [0] log id
     * @return void
     */
    public function actionDetails(array $params)
    {
        $filter = array('log_id' => $params[0]);

        $log_model = g('Log', 'model');
        $ds = $log_model->rel('Owner');
        $ds->whiteListAll();

        $this->_filterLog($ds);
        $info = $ds->getRow($filter);
        $this->assignByRef('info', $info);

        $with_old_values = g('Functions')->anyToBool($info['with_old_values']);
        $this->assign('with_old_values', $with_old_values);


        $values_model = g('LogValue', 'model');
        $values_model->filter($filter);
        $rows = $values_model->exec();
        foreach ($rows as & $row)
        {
            $row['differs'] = $with_old_values
                && ($row['value'] !== $row['new_value']);
        }
        $this->assignByRef('rows', $rows);


    }


    /**
     * Generate title of a log entry
     * @author m.augustynowicz
     *
     * @param array $row row from Log model
     *
     * @return void
     */
    protected function _entryTitle(array $row)
    {
        $title = $this->trans('%s/%s on %s',
            $row['target_url'],
            $row['target_action'],
            (string) $row['title']
        );
        return $title;
    }


    /**
     * Generate link to a log entry
     * @author m.augustynowicz
     *
     * @param array $row row from User model
     *
     * @return void
     */
    protected function _l2owner(array $row)
    {
        $display_name = $row[ g()->conf['users']['display_name_field'] ];
        $ident = $row[ g()->conf['users']['ident_field'] ];
        return $this->l2c($display_name, 'User', '', array($ident));
    }

}

