<?php
/**
 * Plik zawiera klase Forms do tworzenia i obslugi formularzy 
 *
 * @author w.bojanowski
 * @version 1
 * @package hologram2.1
 */

if (!defined('USE_AJAX_BY_DEFAULT'))
    define('USE_AJAX_BY_DEFAULT', true);


/**
 * Klasa do tworzenia i obslugi formularzy
 * @author w.bojanowski
 * @author a.augustynowicz simplification of inputs declaration and use of Kernel::$infos
 * @version 1
 * @package hologram2.1
 *
 */
class Forms extends HgBase
{

/**
 *  @example
 *  property $forms that should be placed in component that renders form
 *  <code>
 *  public $forms = array(
 *              'register_user' => array( // form name.
 *                                        // try not to choose common name for this
 *                                        // as <form /> gets this name as id
 *                          'ajax' => false, // use fancy ajax validation? (default: true)
 *                          'upload' => true, // files will get uploaded by this form
 *                          'model' => 'User', // default model for fields if none supplied
 *                          // declarations of available input fields
 *                          'inputs' => array(
 *                              // login from default model
 *                              'login',
 *                              // email from other model
 *                              'email' => 'UsersMails',
 *                              // email validated by several models' email field
 *                              'email22' => array('UsersMails', 'UsersSomethings'),
 *                              // input called address validated by addr field in UsersAddressessModel
 *                              'address' => array('addr' => 'UsersAddresses'),
 *                              // field from several models, with different names
 *                              'surname' => array(
 *                                  'last_name' => 'Users',
 *                                  'surname' => array('UsersAddresses', 'UsersUsers'),
 *                              ),
 *                              // in any array form you can additionally specify alternative template
 *                              'description' => array(
 *                                  '_tpl' => 'FString_area',
 *                              ),
 *                              // field not related to any fields in any models
 *                              'non-model-input' => array(
 *                                  '_tpl' => 'sth',
 *                                  'fields' => false, // input is not related to any model
 *                              )
 *                              // most elastic form (warning: got literally copied!)
 *                              'input_name' => array('models'=>array(
 *                                  'model1' => array('field1','field2'),
 *                                  'model2' => array('field3')
 *                              )),
 *                          )
 *              )
 *  );
 *  </code>
 *
 *  NOTE: all this mess got parsed in Component::_fixFormsFormat()
 *
 */

    public static $singleton=false;
    protected static $_used_ids = array();
    private $__ident;
    private $__model;
    private $__ctrl;
    private $__form;

    protected static $_models_cache = array();
    
    
    /**
     * @param array $params accepted params:
     *        [0] form id
     *        [1] object of Component that is using this form
     */
    public function __construct(array $params=array())
    {
        if(!$params[1] instanceof Component)
            throw new HgException("Second form constructor parameter must bee instance of Component!");
        $this->__ctrl = $params[1];
        $this->__short_ident = $params[0];
        $this->__ident = $this->__ctrl->getFormsIdent($this->__short_ident);

        if(isset($params[2]) && $params[2] instanceof Model)
        {
            $ds = $params[2];
            $this->__form['model'] = $ds->getName();
            $fields = $ds->getFields();
            foreach($fields as $fname => $field)
            {
                $this->__form['inputs'][$fname]['models'][$this->__form['model']][] = $fname;
            }
                
            $this->__form['ajax'] = false;
            $this->__ctrl->forms[$this->__short_ident] = $this->__form;
        }
        elseif(!isset($this->__ctrl->forms[$this->__short_ident]))
            throw new HgException("Form not defined in controller!");
        else
            $this->__form = & $this->__ctrl->forms[$this->__short_ident];
        
        if ($model = @$this->__form['model'])
            $this->__model = g($model,'model');
        parent::__construct($params);
    }

    
    /**
     * Renders begining of the form.
     *
     * @todo update when we will use file fields
     *
     * @return void
     */
    public function create($action='', array $additional_params=array())
    {
        $f = g('Functions');

        if(!empty($additional_params['ident']))
            $this->__ident = $additional_params['ident'];

        $params = array_merge(array(
            'id' => $f->uniqueId(),
            'ident'=>$f->ASCIIfyText($this->__ident),
            'ajax'=>isset($this->__form['ajax'])?$this->__form['ajax']:USE_AJAX_BY_DEFAULT,
            'errors' => $this->getErrors(),
            'action'=>$action,
            'has_files' => @$this->__form['upload']
        ), $additional_params);
        return $this->__ctrl->inc('Forms/form_create',$params);
    }
    
    /**
     * Renders end of the form.
     * @return void
     */
    public function end()
    {
        return $this->__ctrl->inc('Forms/form_end', array(
            'ident' => $this->__ident,
            'form'  => $this,
        ));
    }

    
    /**
     * Renders form field using definition from Component::$forms.
     *
     * @param string $input key in $forms[inputs]
     * @param array $additional_params additional params to pass to the template
     * @return whatever template returns
     */
    public function input($input, array $additional_params = array())
    {
        $input_def = $this->_getInput($input, false);

        $data = @ $this->__ctrl->data[$this->__short_ident][$input];
        $errors = $this->_getErrors($input);

        $additional_attrs = (array) @$additional_params['attrs'];
        $hg_attrs = (array) @$input_def['data-attrs'];
        $additional_params['attrs'] = array_merge($hg_attrs, $additional_attrs);

        $hg_params = array(
            'input_def' => & $input_def,
            'ident'     => $input_def['form_ident'],
            'input'     => $input_def['input_name'],
            'id'        => $input_def['id'],
            'ajax'      => $input_def['ajax'],
            'data'      => $data,
            'errors'    => $errors,
            'model_objects' => $input_def['model_objects'],
            'field_objects' => $input_def['field_objects'],
        );
        $params = array_merge($hg_params, $additional_params);

        return $this->__ctrl->inc($input_def['tpl'], $params);
    }


    /**
     * Render label for form input
     * @author m.augustynowicz
     *
     * @param string $input key in $forms[inputs]
     * @param string $label text label. will be translated
     * @param array $additional_params additional params to pass to the template
     *
     * @return void
     */
    public function label($input, $label, array $additional_params = array())
    {
        $input_def = $this->_getInput($input, true);

        $required = false;
        foreach ($input_def['models'] as $model_name => &$fields)
        {
            $model = g($model_name, 'model');
            foreach ($fields as $field_name)
            {
                if ($model[$field_name]->notNull())
                {
                    $required = true;
                    break 2;
                }
            }
        }
        unset($fields);

        $hg_params = array(
            'label' => $label,
            'input_id' => $input_def['id'],
            'required' => $required
        );
        $params = array_merge($hg_params, $additional_params);

        return $this->__ctrl->inc('Forms/label', $params);
    }


    /**
     * Wrapper for deprecated method name.
     * Created 2009-09-10
     * @author m.augustynowicz
     */
    public function create_all($action='')
    {
        g()->debug->addInfo('deprecated Forms::create_all()', true,
                'Method '.__CLASS__.__FUNCTION__
                . '() is deprecated due it\' incorrect name and will be removed &#8212; update your code' );
        return $this->createAll($action);
    }
    
    public function createAll($action='')
    {
        echo "<div>";
        $this->create($action);
        foreach($this->__form['inputs'] as $input => $data)
        {
            echo "<div style='width:500px;text-align:right;'><span>$input: </span>";
            $type = $this->__model->getField($input)->type().'?';
            if (isset($data['tpl']))
                $type .= " ({$data['tpl']})";
            printf('<small>(%s)</small>', $type);
            $this->input($input);
            echo "</div>";
        }
        echo "<input type='hidden' name='ds' value='".$this->__form['model']."' >";
        echo "<input type='hidden' name='ident' value='".$this->__short_ident."' >";
        echo "<input type='submit' value='send' >";
        echo "</div>";
        $this->end();
    }

    /**
     * Gets form's id.
     *          
     * @return string $this__ident value
     */         
    public function getIdent()
    {
        return $this->__ident;
    }

    public function getShortIdent()
    {
        return $this->__short_ident;
    }

    /**
     * Retrieves form's errors.
     * @author m.augustynowicz
     * @param boolean $flush delete after retrieving
     * @return array
     */
    public function getErrors($flush=true)
    {
        return $this->_getErrors('0', $flush);
    }

    /**
     * Retrieves error messages for given input from Kernel's infos
     * @author m.augustynowicz
     *
     * @param string $input input name, "0" is special value for the whole form
     * @param boolean $flush delete after retrieving
     * @return array
     */
    protected function _getErrors($input="0", $flush=true)
    {
        if (@empty(g()->infos['forms']))
            return array();

        $errors = array();

        $err_prefix = "{$this->__ident} {$input} ";
        $err_prefix_len = strlen($err_prefix);
        foreach (g()->infos['forms'] as $info_id => $info)
        {
            // I guess that's faster that preg_match()
            if (substr($info_id,0,$err_prefix_len) !== $err_prefix)
                continue;

            $errors[substr($info_id,$err_prefix_len)] = $info;
            if ($flush)
                unset(g()->infos['forms'][$info_id]);
        }

        if ($flush && empty(g()->infos['forms']))
            unset(g()->infos['forms']);

        return $errors;
    }


    /**
     * Get a complete input field definition
     *
     * Here we take care of duplicate ids.
     * @author m.augustynowicz
     *
     * @param string $name valid input field name
     * @param bool $for_label determine wheter we are getting input data for rendering
     *        <label /> and not actual <input />.
     *
     * @return array input field definition containing:
     *         - [form_ident]
     *         - [input_name]
     *         - [models]
     *         - [tpl]
     *         - [id]
     *         - [ajax]
     */
    protected function _getInput($name, $for_label)
    {
        if (null === $this->__form['inputs'][$name])
        {
            throw new HgException("Input `$name' not defined in controller variable \$forms!");
        }

        $input_def = & $this->__form['inputs'][$name];

        static $rendered_cache = array();
        $rendered = & $rendered_cache[$name];

        // determine whether we should (re-)generate id
        if (!@$input_def['_generated'])
        {
            $generate_id = true; // we should for new inputs
            if (!$for_label)
                $rendered++;
        }
        elseif ($for_label)
            $generate_id = true; // we should, when rendering <label />
        else
        {
            // we should, when rendering <input /> again.

            $rendered++;
            $generate_id = (1 < $rendered);
        }

        if ($generate_id)
        {
            $input_def['input_name'] = $name;
            $input_def['id'] = g('Functions')->uniqueId();
        }


        if (true === @$input_def['_generated'])
        {
            return $input_def;
        }


        $models = & $input_def['models'];

        static $html5data_rules = array(
            'min_length' => -1,   // take lower value
            'max_length' => +1,   // take higher value
            'notnull'    => +1,   // take higher value (true>false)
            'defval'     => true, // combine all values
        );
        $input_def['data-attrs'] = array();

        $input_def['model_objects'] = array();
        $input_def['field_objects'] = array();
        if ($models)
        {
            foreach ($models as $model_name => $fields)
            {
                if (!isset(self::$_models_cache[$model_name]))
                     self::$_models_cache[$model_name] = g($model_name, 'model');
                $model = & self::$_models_cache[$model_name];
                $input_def['model_objects'][$model_name] = & $model;
                if ($fields)
                {
                    foreach ($fields as $field_name)
                    {
                        $field = & $model[$field_name];
                        $input_def['field_objects'][] = & $field;

                        // export some of the rules to the client using html5 data-* attributes

                        $rules = $field->rules();
                        foreach ($html5data_rules as $rule_name => $cmp_multiplier)
                        {
                            if (!isset($rules[$rule_name]))
                                continue;

                            $attr = & $input_def['data-attrs']['data-'.$rule_name];
                            $rule_html = $rules[$rule_name];
                            if (is_string($rule_html) || !is_scalar($rule_html))
                                $rule_html = htmlspecialchars($rule_html);

                            if (true === $cmp_multiplier)
                            {
                                if (!isset($attr))
                                    $attr = array($rule_html);
                                else
                                    $attr[] = $rule_html;
                            }
                            else if (!isset($attr) ||
                                     $attr > $rules[$rule_name] * $cmp_multiplier
                                    )
                            {
                                $attr = $rule_html;
                            }

                            unset($attr);
                        }
                    }
                    unset($field);
                }
            }
            unset($model);
            foreach ($input_def['data-attrs'] as &$attr)
                $attr = json_encode($attr);
        }

        $tpl = & $input_def['tpl'];
        if (null === $tpl)
        {
            $ctrl_class = get_class($this->__ctrl);
            $form_def = "{$ctrl_class}::\$forms[{$this->__short_ident}][{$name}]";

            $error_prefix = "Template is not defined in {$form_def} definition and ";
            if (empty($models))
            {
                throw new HgException($error_prefix . 'no models given.');
            }
            else if (sizeof($models) > 1)
            {
                throw new HgException($error_prefix . 'more than one models given.');
            }
            $fields = reset($models);
            if (sizeof($fields) > 1)
            {
                throw new HgException($error_prefix . 'more than one field of a model given.');
            }
            unset($err_prefix);

            $model_name = key($models);
            $field_name = reset($fields);
            $model = & $input_def['model_objects'][$model_name];
            $field = $model[$field_name];
            if (!$field)
            {
                throw new HgException("{$form_def} supposed to render {$model_name}'s {$field_name} field, but it does not exist.");
            }
            $tpl = 'Forms/' . $field->type();
        }

        $input_def['form_ident'] = $this->getIdent();

        if (isset($this->__form['ajax']))
            $input_def['ajax'] = $this->__form['ajax'];
        else
            $input_def['ajax'] = USE_AJAX_BY_DEFAULT;


        $input_def['_generated'] = true;

        unset($tpl);
        unset($models);

        return $input_def;
    }

}

