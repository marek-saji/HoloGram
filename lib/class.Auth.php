<?php
if (!defined('INCORRECT_LOGIN_COUNT'))
    define('INCORRECT_LOGIN_COUNT', NULL);

/**
 * Handling authorization
 *
 * @author m.augustynowicz
 */
class Auth extends HgBase implements IAuth
{
    static $singleton = true;

    /**
     * @var string last error
     */
    protected $_error = '';

    /**
     * @var array reference to session data
     */
    protected $_session = null;

    /**
     * @var Model user model
     */
    protected $_model = null;


    /**
     */
    public function __construct()
    {
        $this->_model = g('User','Model');
        // check if UserModel is well constructed
        if (!$this->_model['id'])
        {
            throw new HgException('User Model does not have `id\' field');
        }



        parent::__construct();
        $this->_session = &$_SESSION[g()->conf['SID']]['AUTH'];
    }


    /**
     * @return boolean whether we are authorized
     */
    public function loggedIn()
    {
        return false !== $this->id();
    }


    /**
     * Returns value of loggen-in user's id in the database.
     * @author m.augustynowicz
     *
     * @return boolean|integer value of user's [id] field
     *          false, when user is not logged-in,
     */
    public function id()
    {
        $id = @$this->_session['user']['id'];
        return $id ? (int)$id : false;
    }


    /**
     * Returns loggen-in user's display name
     *
     * @return boolean|integer user's display name
     *          false, when user is not logged-in,
     */
    public function displayName()
    {
        $display_name_field = g()->conf['users']['display_name_field'];
        $display_name = @$this->_session['user'][$display_name_field];
        return $display_name ? $display_name : false;
    }


    /**
     * Authorize user
     *
     * @param array $auth_data
     *        [login] string
     *        [passwd] string
     *        [force_login] boolean if true, [passwd] is not verified
     * @return boolean success of authorizing against given data
     */
    public function login(array $auth_data)
    {
        $login = & $auth_data['login'];
        $passwd = & $auth_data['passwd'];
        $force_login = & $auth_data['force_login'];

        $user_data = null;
        $this->__user = array();
        $this->_error = '';
        $user_data_update = array();

        do // just so we can break on error
        {

            if (empty($passwd))
            {
                $this->_error = 'no_password_given';
                break;
            }

            if (!isset($login) || (!$force_login && !$passwd))
            {
                $this->_error = 'empty_login_or_password';
                break;
            }

            $login_fields = g()->conf['users']['login_fields'];
            $sql_fields = array();
            foreach ($login_fields as $login_field)
            {
                $sql_fields[] = "{$login_field} = "
                        . $this->_model[$login_field]->dbString($login);
            }

            $this->_model
                    ->filter(join(' OR ', $sql_fields))
                    ->order('id', 'DESC');

            $user_data = $this->_model
                    ->setMargins(1)
                    ->exec();

            if (empty($user_data))
            {
                $this->_error = 'no_such_user';
                break;
            }

            if (STATUS_ACTIVE != $user_data['status'])
            {
                $this->_error = 'not_active';
                break;
            }

            if (!$force_login && $user_data['passwd'] != md5($passwd))
            {
                $user_data_update = array (
                    'last_incorrect_login' => time(),
                    'incorrect_login_count' =>
                            1 + @$user_data['incorrect_login_count']
                );
                $this->_error = 'bad_password';
                break;
            }

            if (NULL!==INCORRECT_LOGIN_COUNT)
            {
                if ($user_data['incorrect_login_count'] > INCORRECT_LOGIN_COUNT)
                {
                    $this->_error = 'incorrect login count exceeded';
                    break;
                }
            }

            $user_data_update = array(
                'last_correct_login'    => time(),
                'incorrect_login_count' => 0
            );
        }
        while(false); // just so we can break on error

        if ($user_data && !empty($user_data_update))
        {
            $user_data_update['id'] = $user_data['id'];
            // this will ignore all non-existing fields
            $this->_model->sync($user_data_update, true, 'update');
        }

        if (!$this->_error)
        {
            $this->_session['user'] = & $user_data;
            return true;
        }
        else
        {
            return false;
        }
    }


    /**
     * @return string last error code
     */
    public function getLastError()
    {
        return $this->_error;
    }


    /**
     * De-authorize user
     */
    public function logout()
    {
        $this->_session['user'] = array();
    }

    public function hasAccess($ctrl, $action=null, $target=null, $user=null)
    {
        if (!is_object($ctrl))
            $ctrl_name = $ctrl;
        else
        {
            if (!$ctrl instanceof Controller)
                throw new HgException('Trying to check permissions to non-controller object');
            $ctrl_name = get_class($ctrl);
        }
        if (!$user)
            $user = & $this->__user;
        else
            throw new HgException('Checking for non-logged-in user permissions not supported in this implementation.');

        switch ($ctrl_name)
        {
            case 'UserController' :
            case 'ArticlesController' :
            case 'AdvertsController' :
            // case 'SthElse' :
                if ('actionEdit'===$action)
                {
                    $target = $this->__parseTarget($target);
                    return $this->__isHg20Owner($target, $user);
                }
                return true;
        }

        return false;
    }


    /**
     * @param string field name in user model
     * @return mixed value of requested field for authorized user
     */
    public function get($field)
    {
        return @$this->_session['user'][$field];
    }

    /**
     * @param null|integer $user_id when null, use authorized user
     * @return array keys are [type] ids (USER_TYPE_*), and values
     *         are acl.xml-friendly names
     */
    public function getUserGroups($user_id = null)
    {
        static $cache = array();

        $ret = array();

        if (null === $user_id)
        {
            if (!$this->loggedIn())
                return array(USER_TYPE_UNAUTHORIZED => 'unauthorized');
            $user_id = $this->id();
        }
        elseif (!g('Functions')->isInt($user_id))
        {
            throw new HgException('Invalid parameter passed as user_id: '
                    . print_r($user_id,true) );
        }

        if (isset($cache[$user_id]))
            return $cache[$user_id];

        $ret = & $cache[$user_id];

        $user_data = g('User', 'model')
            ->filter(array('id' => $user_id))
            ->whiteList(array('type', 'id'))
            ->setMargins(1)
            ->exec();

        if (empty($user_data))
            return array();

        $user_id = $user_data['id'];

        switch($user_data['type'])
        {
            case USER_TYPE_ADMIN :
                $ret[USER_TYPE_ADMIN] = 'admins';
            case USER_TYPE_MOD :
                $ret[USER_TYPE_MOD] = 'mods';
            case USER_TYPE_AUTHORIZED :
                $ret[USER_TYPE_AUTHORIZED] = 'authorized';
                break;
            default:
                throw new HgException('User with unknown [type]: '
                        . print_r($user_data, true) );
                break;
        }

        return $ret;
    }

    /**
     *
     */
    public function isUserInGroup($group, $user=null)
    {
        if(is_int($group))
            return array_key_exists($group, $this->getUserGroups($user));
        else
            return in_array($group, $this->getUserGroups($user));
    }


    protected function __isHg20Owner($target, $user)
    {
        if ('user' === $target->model)
            return $target->fields['object_id'] == @$this->get('object_id');
        else
        {
            return $target->fields['add_o_id'] == @$this->get('object_id');
            // TODO implement me. d;
            // check if $target is owned by $user
        }
    }

    /**
     * @todo fix it.
     */
    protected function __parseTarget($t)
    {
        $target = new stdClass();
        switch (true)
        {
            case is_object($t) :
                return $t;

            case is_array($t) :
                $target->fields = $t;
                // $target->model = ??;
                return $target;

            case is_string($t) :
                $matches = array();
                if (!preg_match('/^([^\s]*)\s+?(.*)\s+?(?:\((.*)\)(?:\s+?(.*))?)$/U', $t, $matches))
                    return null;
                // target type
                if ('model' !== $matches[1])
                    throw new HgException('Non-model target types not implemented.');
                // target model
                $target->model = $matches[2];
                // target keys
                if (!isset($matches[3]))
                    return null;
                $fields = explode(',',$matches[3]);
                $this->fields = array();
                foreach ($fields as $field)
                {
                    list($name,$val) = explode('=',$field,2);
                    $target->fields[$name] = $val;
                }
                // target's fields
                // ?

                return $target;
        }
        return null;
    }
}
