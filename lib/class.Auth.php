<?php

if (!defined('INCORRECT_LOGIN_COUNT'))
    define('INCORRECT_LOGIN_COUNT',5);

class Auth extends HgBase implements IAuth
{
    static $singleton = true;

    private $__user = array();
    private $__error = '';
    protected $_session = null;

    public function __construct()
    {
        parent::__construct();
        $this->_session = &$_SESSION[g()->conf['SID']]['AUTH'];
    }

    public function loggedIn()
    {
        return false !== $this->id();
    }

    /**
     * DEPRECATED Getter for multiple keys of user's table.
     * Use id() instead.
     * @author m.augustynowicz
     *
     * @return array|boolean false, when user is not logged in,
     *         associative array with key values otherwise.
     */
    public function ids()
    {
        g()->debug->addInfo(null, 'Auth::ids() is deprecated. Don\'t use it.');
        $id = $this->id();
        return $id ? compact('id') : false;
    }

    /**
     * Returns value of loggen-in user's id in the database.
     * @author m.augustynowicz
     *
     * @return mixed false, when user is not logged-in,
     *         key's value otherwise (usually it will be integer)
     */
    public function id()
    {
        $id = @$this->_session['user']['id'];
        return $id ? $id : false;
    }

    public function login(array $auth_data)
    {
        extract($auth_data);
        $this->__user = array();
        $this->__error = '';

        $check_password = true;
        switch (true)
        {
            case isset($pass) :
                break;
            case isset($passwd) :
                $pass = $passwd;
                break;
            case isset($password) :
                $pass = $password;
                break;
            case isset($force_login) :
                if ($force_login)
                    $check_password = false;
                break;
            default :
                $this->__error = 'no_password_given';
                return false;
        }
        if (!isset($login) || ($check_password && !$pass))
        {
            $this->__error = 'empty_login_or_password';
            return false;
        }

        $sql_login = pg_escape_string($login);
        $sql_pass = md5($pass);

        $user = g('Getz')->getObject('Auth::auth', 'Users',
                                       array("login = '{$sql_login}'") );

        if (!$user)
        {
            $this->__error = 'no_such_user';
            return false;
        }

        if ($user['accepted']!='t')
        {
            $this->__error = 'not_active';
            return false;
        }

        if ($check_password && $user['password'] != $sql_pass)
        {
            $sql = "UPDATE users SET last_incorrect_login=now(), incorrect_login_count=incorrect_login_count+1 WHERE users_id='{$user['element_id']}'; ";
            g()->db->Execute($sql);
            $this->__error = 'bad_password';
            return false;
        }

        if (NULL!==INCORRECT_LOGIN_COUNT && $user['incorrect_login_count'] > INCORRECT_LOGIN_COUNT)
        {
            $this->__error = 'incorrect login count exceeded';
            return false;
        }
        $sql = "UPDATE users SET last_correct_login=now(), incorrect_login_count=0 WHERE users_id='{$user['element_id']}';";
        g()->db->Execute($sql);

        $this->_session['user'] = $user;

        return true;
    }

    public function getLastError()
    {
        return $this->__error;
    }

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

    public function get($field)
    {
        return @$this->_session['user'][$field];
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
