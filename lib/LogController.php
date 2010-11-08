<?php
g()->load('Pages', 'Controller');


/**
 * Displaying logs added via LogModel::log()
 * @author m.augustynowicz
 */
class LogController extends PagesController
{
    public $forms = array(
        'filters' => array(
            'model' => 'Log',
            'inputs' => array(
                'user_login' => array(
                    '_tpl'=>'Forms/FString',
                    'login' => 'User',
                ),
                'from' => array('timestamp' => 'Log'),
                'to'   => array('timestamp' => 'Log'),
                'level',

                // unused in the form

                'target_url' => array(
                    '_tpl'=>'Forms/input',
                ),
                'target_action' => array(
                    '_tpl'=>'Forms/input',
                ),
                'target_id' => array(
                    '_tpl'=>'Forms/input',
                ),
            ),
        ), // filters
    );

    protected function _prepareActionDefault(array &$params)
    {
        $this->addChild('Paginator', 'p')->config(null, 20);
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
        if (@$this->_validated['filters'])
        {
            $post_data = & $this->data['filters'];
            foreach ($post_data as $k=>&$val)
            {
                if ($k[0] === '_' || !$val)
                    unset($post_data[$k]);
            }
            $this->redirect($this->url2a(
                $this->getLaunchedAction(),
                $post_data
            ));
        }

        // id without url doesn't make too much sense
        if (@$params['target_id'] && !@$params['target_url'])
        {
            unset($params['target_id']);
            $this->redirect($this->url2a(
                $this->getLaunchedAction(),
                $params
            ));
        }

        $f = g('Functions');


        // fill the form with GET data
        $this->data['filters'] = $params;


        $level_values = array_flip(g()->conf['enum']['log_level']);
        foreach ($level_values as $k => &$val)
        {
            $val = $this->trans('((log level:%s))', $k);
        }
        $this->assignByRef('level_values', $level_values);

        $log_model = g('Log', 'model');
        $ds = $log_model->rel('Owner');
        $ds->whiteListAll();


        // filter

        $filter = array();

        static $basic_filters = array(
            'level',
            'target_url',
            'target_action',
            'target_id',
        );
        foreach ($basic_filters as $field_name)
        {
            if (@$params[$field_name])
                $filter[$field_name] = $params[$field_name];
        }

        if (@$params['user_login'])
        {
            $filter_user = g('User', 'model')
                    ->whiteList(array('id'))
                    ->getRow(array('login'=>$params['user_login']));
            $filter['user_id'] = $filter_user['id'];
        }

        if (@$params['from'])
            $filter[] = array('timestamp', '>=', $params['from']);

        if (@$params['to'])
            $filter[] = array('timestamp', '<=', $params['to']);


        // here be dragons.
        // we want to use LogModel::filter() to allow some special filtering in app overloads.
        // e.g. one can filter only info-level logs for non-superusers

        $log_model->filter($filter);
        $ds->filter(null);


        $ds->order('timestamp', 'DESC');
        $this->getChild('p')->setMargins($ds);

        $rows = $ds->exec();
        foreach ($rows as &$row)
        {
            $row['user_display_name'] =
                $row[ g()->conf['users']['display_name_field'] ];
            $row['user_ident'] =
                $row[ g()->conf['users']['ident_field'] ];
            $timestamp = strtotime($row['timestamp']);
            $row['AroundDateParams'] = array(
                'from' => $f->formatDate(strtotime('-12 hours', $timestamp), DATE_SORTABLE_FORMAT),
                'to'   => $f->formatDate(strtotime('+12 hours', $timestamp), DATE_SORTABLE_FORMAT),
            );
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
     * Allow filters[level] to be empty
     * @author m.augustynowicz
     *
     * @param array $value referene to user-supplied value
     *
     * @return array errors.
     *         See description in Component comment-block for more details.
     */
    public function validateFiltersLevel(&$value)
    {
        return $value ? array() : array('stop_validation' => true);
    }



    /**
     * Allow filters[user_login] to be empty
     * @author m.augustynowicz
     *
     * @param array $value referene to user-supplied value
     *
     * @return array errors.
     *         See description in Component comment-block for more details.
     */
    public function validateFiltersUserLogin(&$value)
    {
        return $value ? array() : array('stop_validation' => true);
    }


    /**
     * Allow filters[from] to be empty
     * @author m.augustynowicz
     *
     * @param array $value referene to user-supplied value
     *
     * @return array errors.
     *         See description in Component comment-block for more details.
     */
    public function validateFiltersFrom(&$value)
    {
        return $value ? array() : array('stop_validation' => true);
    }


    /**
     * Allow filters[to] to be empty
     *
     * Gotta love these methods with comment block 3x longer than themselves. d;
     * @author m.augustynowicz
     *
     * @param array $value referene to user-supplied value
     *
     * @return array errors.
     *         See description in Component comment-block for more details.
     */
    public function validateFiltersTo(&$value)
    {
        return $value ? array() : array('stop_validation' => true);
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
        $title = $this->trans('((event: %s/%s on %s))',
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

