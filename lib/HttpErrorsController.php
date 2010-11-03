<?php
g()->load('Pages', 'controller');

/**
 * Display common HTTP-like errors
 * @author m.augustynowicz
 */
class HttpErrorsController extends PagesController
{
    /**
     * Omit checking access in parent onAction()
     * @author m.augustynowicz
     */
    public function onAction($action, array & $params)
    {
        return true;
    }


    /**
     * Proxy to all errors
     * @author m.augustynowicz
     *
     * @params array $params from URL
     *         [0] int $error_code
     */
    public function actionDefault(array $params)
    {
        $error_code = @$params[0];
        switch ($error_code)
        {
            case 403 :
            case 404 :
                return $this->delegateAction('error'.$error_code, $params);
            default :
                return $this->delegateAction('error404', $params);
        }
    }


    /**
     * error 403: forbidden
     * @author m.augustynowicz
     */
    public function actionError403()
    {
    }


    /**
     * error 404: not found
     * @author p.piskorski
     */
    public function actionError404()
    {
    }

}

