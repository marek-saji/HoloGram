<?php
g()->load('DataSets', null);

/**
 * Model for uploaded images.
 * @author m.jutkiewicz
 *
 * WARNING: if you are using transaction's rollback, you have to
 * delete files from a hard drive by yourself.
 *
 * To use this model with its destiny it is necessary to
 * define in source model's constructor a property called 'image_files' e.g.:
 * $this->image_files = array(
 *     'sizes' => array('XXxYY', 'AAxBB', ...),
 *     'store_original' => false,
 *     'stripes' => '00A8FF',
 *     'format' => 'png',
 * );
 * where XX, YY, AA, BB are dimensions in pixels (connected with resizing image),
 * 'store_original' - it is clear - true or false,
 * 'stripes' - a color of stripes, false if transparent stripes, null if no stripes,
 * 			   also you can define this parameter for each image size separately in array, e.g.:
 * 			   array('00A8FF', false, null, '000000');
 * 'format' - target format, allowed values: 'png', 'gif', 'jpg'.
 *
 * All above-mentioned properties are mandatory and must be declared, even if they are empty.
 */
class ImagesUploadModel extends Model
{
    private $__upload_dir;
    private $__allowed_extensions = array(
    	'jpg',
    	'png',
    	'gif',
    );

    public function __construct()
    {
        $this->__upload_dir = UPLOAD_DIR . 'images/';
        parent::__construct();

        $this->_addField(new FString('id', true, null, 32, 32));
        $this->_addField(new FInt('id_in_model', 4, true));
        $this->_addField(new FString('model', true, null, 0, 128));
        $this->_addField(new FString('extension', false, null, 0, 4));
        $this->_addField(new FString('original_name', false, null, 0, 256));
        $this->_addField(new FString('original_mime', false, null, 0, 16));
        $this->_addField(new FString('title', false, null, 0, 64));
        $this->_addField(new FString('description', false, null, 0, 512));
        $this->_addField(new FInt('original_width', 4, true));
        $this->_addField(new FInt('original_height', 4, true));

        $this->_addField(new FString('maker', false));
        $this->_addField(new FString('camera_model', false));
        $this->_addField(new FString('aperturefnumber', false));
        $this->_addField(new FString('isospeedratings', false));
        $this->_addField(new FString('focallength', false));
        $this->_addField(new FTimestamp('filedatatime'));
        $this->_addField(new FInt('height', 4, false));
        $this->_addField(new FInt('width', 4, false));

        $this->_pk('id');
        $this->whiteListAll();
    }

    /**
     * @author m.jutkiewicz
     * Sets the upload directory.
     *
     * @param string $dir
     */
    public function setUploadDir($dir)
    {
        $this->__upload_dir = $dir;
    }

    /**
     * @author m.jutkiewicz
     * Gets the upload directory
     *
     * @return string
     */
    public function getUploadDir()
    {
        return $this->__upload_dir;
    }

    protected function _syncSingle(&$data, $action, &$error)
    {
        $folder = $this->__upload_dir . $data['model'] . '/';

        if(file_exists($folder))
            ;//g()->debug->addInfo(null, $this->trans('Directory %s exists', $name));
        elseif(mkdir($folder, 0700, true))
            ;//g()->debug->addInfo(null, $this->trans('Directory %s created', $name));
        else
            throw new HgException($this->trans('%s is not created!', $folder));

        switch($action)
        {
            case 'delete':
                if(!empty($data['model']) && !empty($data['id']))
                {
                    $path = $this->__upload_dir . $data['model'] . '/' . $data['id'];
                    g('Functions')->rmrf($path); // will echo "deleting $path"
                    $this->filter(array('id' => $data['id']));
                }
            break;

            case 'update':
            case 'insert':
                $image_files = g($data['model'], 'model')->image_files;

                if(empty($image_files) || !array_key_exists('sizes', $image_files) || !array_key_exists('store_original', $image_files) || !array_key_exists('stripes', $image_files) || !array_key_exists('format', $image_files))
                    throw new HgException($this->trans('$image_sizes defined incorrectly.'));

				if(!in_array($image_files['format'], $this->__allowed_extensions))
                    throw new HgException($this->trans('Destination format defined incorrectly: %s.', $image_files['format']));

                $this->_file = $data['file'];
                $size = getimagesize($this->_file['tmp_name']);
                $data['original_width'] = $size[0];
                $data['original_height'] = $size[1];

                //EXIF
                $exif = exif_read_data($data['file']['tmp_name']);
                $data['filedatatime'] = $exif['DateTimeOriginal'];
                $data['maker'] = $exif['Make'];
                $data['camera_model'] = $exif['Model'];
                $data['focallength'] = $exif['FocalLength'];
                $exif['COMPUTED']['ApertureFNumber'] = str_replace(',', '.', $exif['COMPUTED']['ApertureFNumber']);
                $data['aperturefnumber'] = $exif['COMPUTED']['ApertureFNumber'];
                $data['isospeedratings'] = $exif['ISOSpeedRatings'];
                $data['height'] = $exif['COMPUTED']['Height'];
                $data['width'] = $exif['COMPUTED']['Width'];

                if(!($hash = @$data['id']))
                    do
                    {
                        $hash = g('Functions')->generateKey();
                        $full_name = $this->getFullFileName($data['model'], $hash);
                    }
                    while($this->fileExists($full_name));

                $data['id'] = $hash;
                $data['extension'] = $image_files['format'];
                $data['original_name'] = $this->_file['name'];

                if (!$this->_file['type'] || $this->_file['type'] === 'application/octet-stream')
                {
                    $ext = explode('.', $data['file']['name']);
                    $ext = $ext[count($ext) - 1];
                    if(strpos($_SERVER['SERVER_SOFTWARE'], '(Win32)'))
                        $pos = strrpos($data['file']['tmp_name'], '\\') + 1;
                    else
                        $pos = strrpos($data['file']['tmp_name'], '/') + 1;

                    $dir = substr($data['file']['tmp_name'], 0, $pos);
                    $file_name = substr($data['file']['tmp_name'], $pos);

                    if(!@g()->conf['get_mime_type_by_suffix'])
                        $mime = $this->getMIMETypeByFile($file_name, $dir);
                    else
                        $mime = $this->getMIMETypeBySuffix($ext);
                    $this->_file['type'] = $mime;
                }

                $data['original_mime'] = $this->_file['type'];

                switch($this->_file['type'])
                {
                    case 'image/jpeg':
                    case 'image/pjpeg':
                        $func = 'imagecreatefromjpeg';
                    break;
                    case 'image/gif':
                        $func = 'imagecreatefromgif';
                    break;
                    case 'image/png':
                    case 'image/x-png':
                        $func = 'imagecreatefrompng';
                    break;
                    default:
                        $func = false;
                    break;
                }

                switch($data['extension'])
                {
                    case 'jpg':
                        $f = 'imagejpeg';
                    break;
                    case 'gif':
                        $f = 'imagegif';
                    break;
                    case 'png':
                        $f = 'imagepng';
                    break;
                    default:
                        $f = 'imagepng';
                    break;
                }

                foreach($image_files['sizes'] as $i => $s)
                {
                    list($w, $h) = sscanf($s, "%dx%d");
                    if($func)
                    {
                        //photo uploaded by user is resizing and converting to PNG format
                        $size = $this->_calculateNewDimensions($w, $h);
                        $rgb = array();

                        if(is_array($image_files['stripes']))
                            $stripes = @$image_files['stripes'][$i];
                        else
                            $stripes = &$image_files['stripes'];

                        if($stripes === null)
                        {
                            $rgb['r'] = 0xFF;
                            $rgb['g'] = 0xFF;
                            $rgb['b'] = 0xFF;
                            $new_w = $size['width'];
                            $new_h = $size['height'];
                        }
                        elseif($stripes)
                        {
                            $rgb['r'] = hexdec(substr($stripes, 0, 2));
                            $rgb['g'] = hexdec(substr($stripes, 2, 2));
                            $rgb['b'] = hexdec(substr($stripes, 4, 2));
                            $new_w = $w;
                            $new_h = $h;
                        }
                        else
                        {
                            $rgb['r'] = 0xFF;
                            $rgb['g'] = 0xFF;
                            $rgb['b'] = 0xFF;
                            $new_w = $w;
                            $new_h = $h;
                        }

                        $im = $func($this->_file['tmp_name']);
                        $resized = @imagecreatetruecolor($new_w, $new_h);
                        $color = imagecolorallocate($resized, $rgb['r'], $rgb['g'], $rgb['b']);

                        if($stripes === false)
                        	imagecolortransparent($resized, $color);

                        imagefill($resized, 0, 0, $color);
                        imagecopyresampled($resized, $im, ($new_w - $size['width'])/2, ($new_h - $size['height'])/2, 0, 0, $size['width'], $size['height'], $size['orig_width'], $size['orig_height']);

                        //unlink($this->_file['tmp_name']);
                        $f($resized, $this->__upload_dir . 'tmp' . $hash);
                        imagedestroy($im);
                        imagedestroy($resized);
                    }

                    if(!$this->_addFile($data['model'], $hash, $data['extension'], $w, $h))
                        return false;

                    unlink($this->__upload_dir . 'tmp' . $hash);
                }

                $really_is_uploaded = is_uploaded_file($this->_file['tmp_name']);
                $is_uploaded = (@$this->_file['impersonator']) || $really_is_uploaded;
                if($image_files['store_original'] && $is_uploaded)
                {
                    $im = $func($this->_file['tmp_name']);
                    $f($im, $this->_file['tmp_name']);
                    imagedestroy($im);
                    $path = $this->__upload_dir . $data['model'] . '/' . $hash . '/original' . '.' . $data['extension'];

                    if(g()->debug->allowed())
                        printf('<p class="debug">creating <code>%s</code>', $path);

                    mkdir($this->__upload_dir . $data['model'] . '/' . $hash, 0700, true);
                    if ($really_is_uploaded)
                        move_uploaded_file($this->_file['tmp_name'], $path);
                    else
                        rename($this->_file['tmp_name'], $path);
                }
                elseif(is_uploaded_file($this->_file['tmp_name']))
                    unlink($this->_file['tmp_name']);

                if($action == 'update')
                {
                    if(!empty($data['id']))
                    {
                        $this->filter(array('id' => $data['id']));
                        //$this->delete(true);
                    }
                }
            break;

            default:
                throw new HgException("Invalid action {$action}");
            break;
        }

        return parent::_syncSingle($data, $action, $error);
    }

    /**
     * copies row of ImagesUploadModel and related model
     * also copies images to new directory
     * @author b.matuszewski
     *
     * @param array $params containig key
     *    'model_name' - name of related model
     *    'id_in_model'
     *    'hash_name' - name of FImageFile field in related model
     *    'overwrites' - array of values you want to overwrite
     *          for example
     *          'model_name' => 'Photo'
     *          'id_in_model' => $photo_id
     *          'has_name' => 'image_hash'
     *          'overwrites' => array('owner_id' => g()->auth->id())
     *          will copy row of PhotoModel where id = {$photo_id}
     *          but the row will be modified. owne_id of new photo would be
     *          actually logged in user's id
     *
     * @return int|bool - id of compied row in related model or false on failure
     */
    public function hardCopy($params)
    {
        extract($params);
        if(empty($model_name))
            throw new HgException("Wrong params passed to ImagesUploadModel::hardCopy(). Mising field 'model_name'");
        if(empty($id_in_model))
            throw new HgException("Wrong params passed to ImagesUploadModel::hardCopy(). Mising field 'id_in_model'");
        if(empty($hash_name))
            throw new HgException("Wrong params passed to ImagesUploadModel::hardCopy(). Mising field 'hash_name'");
        $overwrites = array_merge(
            array(),
            (array) @$overwrites
        );

        $this->filter(array(
            'model' => $model_name,
            'id_in_model' => $id_in_model
        ));
        if(!$this->getCount())
            return false;
            
        $image_db_data = $this->getRow();
        $surce_hash = $image_db_data['id'];
        $f = g('Functions');
        do
        {
            $target_hash = $f->generateKey();
            $full_name = $this->getFullFileName($model_name, $target_hash);
        }
        while($this->fileExists($full_name));

        $model = g($model_name, 'model');
        $model->whiteListAll();
        $db_data = $model->getRow($id_in_model);
        if(!$db_data)
            return false;

        foreach($overwrites as $key => $val)
            $db_data[$key] = $val;
            
        unset($db_data['id']);
            
        $db_data[$hash_name] = $target_hash;

        $result = $model->sync($db_data, true, 'insert');
        if(true !== $result)
            return false;

        $inserted_id = $model->getData('id');
            
        $image_db_data['id'] = $target_hash;
        $image_db_data['id_in_model'] = $inserted_id;
        $errors = array();
        $sql = '';
        if(!$sql = parent::_syncSingle($image_db_data, 'insert', $errors))
        {
            g()->debug->dump($errors);
            return false;
        }
            
        if(!g()->db->execute($sql))
            return false;
            
        if(g()->db->lastErrorMsg())
            return false;

        $image_files = $model->image_files;
        $format = $image_files['format'];
        $source_path = $this->getFullFileName($model_name, $surce_hash);
        $target_path = $this->getFullFileName($model_name, $target_hash);
        $debug_allowed = g()->debug->allowed();
        $mkdir = mkdir($target_path, 0700, true);
        if($debug_allowed)
        {
            printf('<p class="debug">Creating directory <code>%s</code></p>', $source_path);
            if(!$mkdir)
            {
                echo '<p class="debug"><strong>failed</strong></p>';
                return false;
            }
        }
        foreach($image_files['sizes'] as $size)
        {
            $source_file = $source_path . '/' . $size . '.' . $image_files['format'];
            $target_file = $target_path . '/' . $size . '.' . $image_files['format'];
            if($debug_allowed)
                printf('<p class="debug">copying file <code>%s</code><br />to <code>%s</code>', $source_file, $target_file);
            $copy = copy($source_file, $target_file);
            if($debug_allowed && !$copy)
            {
                echo '<p class="debug"><strong>failed</strong></p>';
                return false;
            }
                
        }
        if($image_files['store_original'])
        {
            $size = 'original';
            $source_file = $source_path . '/' . $size . '.' . $image_files['format'];
            $target_file = $target_path . '/' . $size . '.' . $image_files['format'];
            if($debug_allowed)
                printf('<p class="debug">copying file <code>%s</code><br />to <code>%s</code>', $source_file, $target_file);
            $copy = copy($source_file, $target_file);
            if($debug_allowed && !$copy)
            {
                echo '<p class="debug"><strong>failed</strong></p>';
                return false;
            }
        }
            
        return $inserted_id;
    }

    public function delete($execute = false)
    {
        //throw new HgException("Invalid action {$action}");
        if($execute)
        {
            $all_data = $this->exec();

            if($this->_limit == 1)
                $all_data = array($all_data);

            foreach($all_data as &$data)
            {
                if(!empty($data['model']) && !empty($data['id']))
                {
                    $path = $this->__upload_dir . $data['model'] . '/' . $data['id'];
                    g('Functions')->rmrf($path); // will echo "deleting $path"
                }
            }
        }

        return parent::delete($execute);
    }

    protected function _addFile($model, $hash, $extension, $width = null, $height = null)
    {
        $file = $this->_file;
        $folder = $this->__upload_dir . $model . '/' . $hash . '/';

        if(file_exists($folder))
            ;//g()->debug->addInfo(null, $this->trans('Directory %s exists', $name));
        elseif(mkdir($folder, 0700, true))
            ;//g()->debug->addInfo(null, $this->trans('Directory %s created', $name));
        else
            throw new HgException($this->trans('%s is not created!', $folder));

        //get neccesary information about the file
        if($file['error'] > 0)
        {
            switch($file['error'])
            {
               //now more human readable
                case 1:
                case 2:
                    $info = 'Błąd - %s - Zbyt duży plik';
                    break;
                case 3:
                    $info = 'Błąd - %s - Nieudana próba wysłania pliku. Prosze spróbować jeszcze raz.';
                    break;
                case 4:
                    $info = 'Błąd - %s - Brak pliku.';
                    break;
            }
            g()->addInfo(null, 'error', $this->trans($info, $file['name']));
            return false;
        }

        //file's extension
        $ext = explode('.', $file['name']);
        $ext = $ext[count($ext) - 1];
        //getting mime
        if($file['type'] === null)
        {
            if(strpos($_SERVER['SERVER_SOFTWARE'], '(Win32)'))
                $pos = strrpos($file['tmp_name'], '\\') + 1;
            else
                $pos = strrpos($file['tmp_name'], '/') + 1;

            $dir = substr($file['tmp_name'], 0, $pos);
            $file_name = substr($file['tmp_name'], $pos);

            if(!g()->conf['get_mime_type_by_suffix'])
                $mime = $this->getMIMETypeByFile($file_name, $dir);
            else
                $mime = $this->getMIMETypeBySuffix($ext);
        }
        else
            $mime = $file['type'];

        //upload file
        if(is_file($this->__upload_dir . 'tmp' . $hash))
        {
            $path = $folder . $width . 'x' . $height . '.' . $extension;

            if(g()->debug->allowed())
                printf('<p class="debug">creating <code>%s</code>', $path);

            if(!copy($this->__upload_dir . 'tmp' . $hash, $path))
            {
                g()->addInfo(null, 'error', $this->trans('File has not been sent.'));
                return false;
            }
        }
        else
        {
            g()->debug->addInfo(null, $this->trans('File has not been saved as temporary file.'));
            return false;
        }

        return array(
            'mime' => $mime,
            'orig' => $file['name'],
        );
    }

    /**
     * Returns true when the file exists and is not a directory.
     *
     * @param string $file_name - contains file's name
     *
     * @author D. Wegner
     * @author m.jutkiewicz
     */
    public function fileExists($filename)
    {
        return file_exists($filename);// && is_file($filename) && !is_dir($filename);
    }

    /**
     * Returns the full path of given filename in the upload directory.
     *
     * @author m.jutkiewicz
     * @param string $model
     * @param string $file_name
     * @return string
     */
    public function getFullFileName($model, $file_name)
    {
        $folder = $this->__upload_dir . $model . '/';

        if(!is_dir($folder))
        {
            throw new HgException($this->trans('%s is not created!', $folder));
            return false;
        }

        return $folder . $file_name;
    }

    /**
     * Zwraca typ mime pliku na podstawie polecenia 'file'.
     *
     * @author p.piskorski
     * @author m.izewski
     * @author m.augustynowicz
     * @author m.jutkiewicz
     * WPISUJCIE MIASTA!
     *
     * @param string $file - nazwa pliku
     * @param string $path - katalog z plikiem, domyslnie UPLOAD_DIR
     *
     * @return string typ mime, null jezeli operacja sie nie powiedzie
     */
    public function getMIMETypeByFile($file, $path = UPLOAD_DIR)
    {
        $path = ($path) ? $path . $file : $file;
        $mime_lib = HG_DIR . 'lib/mime/magic.mime';
        $cmd = "file -bi '" . $path . "'";

        //if((@g()->conf['use_hg_magic_file']) && $this->fileExists($mime_lib))
        //    $cmd .= ' -m ' . $mime_lib;

        $res = exec($cmd, $out, $ret);
        //var_dump(g()->conf['use_hg_magic_file'], $cmd, $out, $ret);

        if($ret)
            throw new HgException("Cannot describe MIME type: <code>{$cmd}</code> returned {$ret} printing <pre>\n" . join("\n", $out) . "</pre>");

        $res = trim($res);
        if(preg_match('/^([^\s]+); .*/', $res, $matches))
            $res = $matches[1];

        return $res;
    }

    /**
     * Returns mime type based on the extension.
     *
     * @author p.piskorski
     * @param string $suffix - identyfikator typu pliku/rozszerzenie
     * @return string mime type of 'false' if type is unknown
     */
    public function getMIMETypeBySuffix($suffix)
    {
        switch(strtolower($suffix))
        {
            case "js":
                return "application/x-javascript";
            case "json":
                return "application/json";
            case "jpg":
            case "jpeg":
            case "jpe":
                return "image/jpeg";
            case "png":
            case "gif":
            case "bmp":
            case "tiff":
                return "image/" . $suffix;
            case "css":
                return "text/css";
            case "xml":
                return "application/xml";
            case "doc":
            case "docx":
                return "application/msword";
            case "xls":
            case "xlt":
            case "xlm":
            case "xld":
            case "xla":
            case "xlc":
            case "xlw":
            case "xll":
                return "application/vnd.ms-excel";
            case "ppt":
            case "pps":
                return "application/vnd.ms-powerpoint";
            case "rtf":
                return "application/rtf";
            case "pdf":
                return "application/pdf";
            case "html":
            case "htm":
            case "php":
                return "text/html";
            case "txt":
                return "text/plain";
            case "mpeg":
            case "mpg":
            case "mpe":
                return "video/mpeg";
            case "flv":
                return "video/x-flv";
            case "mp3":
                return "audio/mpeg3";
            case "wav":
                return "audio/wav";
            case "aiff":
            case "aif":
                return "audio/aiff";
            case "avi":
                return "video/msvideo";
            case "wmv":
                return "video/x-ms-wmv";
            case "mov":
                return "video/quicktime";
            case "zip":
                return "application/zip";
            case "tar":
                return "application/x-tar";
            case "swf":
                return "application/x-shockwave-flash";
            default:
                return false;
        }
    }

    /**
     * Calculates the new dimensions of an image with keeping the scale.
     * @author m.jutkiewicz
     *
     * @return array The array of new and old dimensions.
     */
    protected function _calculateNewDimensions($w, $h)
    {
        $size = getimagesize($this->_file['tmp_name']);
        $width = $size[0];
        $height = $size[1];

    	if($w > $width && $h > $height)
    	{
    		$new_w = $width;
    		$new_h = $height;
    	}
    	else
    	{
    		$fct = $width / $w;

    		if($height / $fct > $h)
    		{
    			$fct = $height / $h;
    			$new_w = round($width / $fct);
    			$new_h = $h;
    		}
    		else
    		{
    			$new_w = $w;
    			$new_h = round($height / $fct);
    		}
    	}

        return array(
        	'orig_width' => $width,
            'orig_height' => $height,
        	'width' => $new_w,
            'height' => $new_h,
        );
    }
}
