<?php
g()->load('Pages', 'controller');

/**
 * Controller for serving uploaded files.
 * Must be used with UploadModel.
 * @author m.jutkiewicz
 *
 */
class UploadController extends PagesController
{
	/**
	 * Method serves a file with appropriate headers.
	 *
	 * @param array $params [0] - file hash, which is an ID in UploadModel
	 */
    public function defaultAction(array $params)
    {
    	if(!$id = @$params[0])
    		$this->redirect();

    	$model = g('Upload', 'model');
        $model->filter(array(
            'id' => $id,
        ));
        $model->setMargins(1);
        $data = $model->exec();

        if(empty($data))
    		$this->redirect();

   		$fullpath = $model->getPath();

        if(!is_file($fullpath))
    		$this->redirect();

        header("Content-Type: " . $data['original_mime']);
        header('Content-Disposition: attachment; filename="' . $data['original_name'] . '"');
        header("Content-Length: " . filesize($fullpath));

        while(ob_get_level())
            ob_end_clean();

        readfile($fullpath);
        die();
    }
}
