<?php
g()->load('DataSets', null);

/**
 * General model for uploaded files
 *
 * @todo handle [accepted mime types] (validate in FFile)
 * @todo handle errors better
 *
 * @see FFile this field should be used to relate with this model
 *
 * THIS MODEL HAS NO SUPPORT FOR TRANSACTIONS!
 * You have been warned.
 *
 * NOTES for extending this model:
 *
 * 1.
 * File structure in UPLOAD_DIR should look like this:
 * for models keeping only original uploaded files:
 * UPLOAD_DIR/files/{model}/{id}
 * for models keeping more than one file, per uploaded file, e.g.:
 * UPLOAD_DIR/files/{model}/{id}/file
 * UPLOAD_DIR/files/{model}/{id}/file.flv
 * UPLOAD_DIR/files/{model}/{id}/file.mp4
 * or
 * UPLOAD_DIR/files/{model}/{id}/file
 * UPLOAD_DIR/files/{model}/{id}/file.100x100.png
 * UPLOAD_DIR/files/{model}/{id}/file.200x200.png
 * or similar
 *
 * 2.
 * Don't override the file, first delete it, then create new with the same name
 *
 * @author m.jutkiewicz
 * @author m.augustynowicz made it a little more extendable, reorganizing
 */
class UploadModel extends Model
{
    /**
     * @var string path to base upload dir for this model
     */
    protected $_upload_dir;

    /**
     * @var FFile field that refers to tis model
     *      can hold custom settings (see below)
     */
    protected $_field = null;


    // these can be overridden by field's config

    /**
     * @val string subdirectory of UPLOAD_DIR to keep files in
     *      field config "subdirectory"
     */
    protected $_subdirectory = 'files';
    /**
     * @val array|boolean list of allowed mime types, when true, allows all,
     *      array contains list of _regular expressions_
     *      field config "allowed mime types"
     */
    protected $_allowed_mime_types = true;
    /**
     * @val int maximul file size to upload [MB] (false for ini settings)
     *      field config "max size"
     */
    protected $_max_size = 10;



    /**
     *
     * @param conf $conf should contain [field] key
     */
    public function __construct(array $conf = array())
    {
        if (!isset($conf['field']))
        {
            trigger_error('You are using UploadModel without any master '
                    .'field specified! It is hightly discouraged to do so.',
                    E_USER_WARNING
                );
        }
        else
        {
            $this->_field = $conf['field'];
            if (null !== $val = $this->_field->getConf('subdirectory'))
                $this->_subdirectory = $val;
            if (null !== $val = $this->_field->getConf('allowed mime types'))
                $this->_allowed_mime_types = $val;
            if (null !== $val = $this->_field->getConf('max size'))
                $this->_max_size = $val;
        }

        $this->_table_name = 'upload'; // all upload models may share
        self::_getPath(null, null); // make sure the path exists
        $this->_upload_dir = UPLOAD_DIR . $this->_subdirectory
                             . DIRECTORY_SEPARATOR;

        parent::__construct();

        // (also a filename)
        $this->__addField(new FString('id', true, null, 32, 32));

        // relation to models
        $this->__addField(new FString('model', true, null, 0, 128));
        $this->__addField(new FInt('id_in_model', 4, true));

        // original uploaded file data
        $this->__addField(new FString('original_mime', false, null, 0, 128));
        $this->__addField(new FString('original_name', false, null, 0, 256));

        // additional meta data
        $this->__addField(new FString('title', false, null, 0, 64));
        $this->__addField(new FString('description', false, null, 0, 512));

        $this->__pk('id');
        $this->whiteListAll();
    }

    /**
     * Gets path to a given file
     * @author m.augustynowicz
     *
     * @param null|int|array $file @see _translateFileParam()
     *
     * @return false|string false on any error
     */
    public function getPath($file=null)
    {
        $file = $this->_translateFileParam($file);
        if (false === $file
            || !(isset($file['model']) && isset($file['id'])) )
            return false;

        return $this->_getPath($file['model'], $file['id']);
    }

    /**
     * Syncs single row.
     * When updating/deleting, $data must contain keys (filter() is not used)
     *
     * In addition to normal behaviour, gets data[file] (copied form $_FILES)
     * and stores uploaded file in UPLOAD_DIR/$file[model]/$file[id]
     *
     * @todo rename to _syncSingle(), when changed upstream
     * 
     * @param array $data reference to row data
     * @param string $action delete|update|insert action may be changed
     *        from update (which is default) to insert, when no PKs supplied
     *        (this will trigger warning)
     * @param array $error reference to errors
     */
    protected function __syncSingle(&$data, $action, &$error)
    {
        $f = g('Functions');

        // determining the action (copypasta from Model!)
        if (isset($data['_action']))
            $action = $data['_action'];
        if ($action == 'update')
        {
            foreach ($this->_primary_keys as $pk)
            {
                if(!isset($data[$pk]) || !$data[$pk])
                {
                    $action = 'insert';
                    trigger_error('Tried to update-sync, but no PK given, falling back to insert!', E_USER_WARNING);
                    break;
                }
            }
        }
        // do things on the filesystem
        switch ($action)
        {
            case 'delete':
                if (empty($data['model']) || empty($data['id']))
                {
                    trigger_error('Tried to delete a file, but no PKs supplied',
                                   E_USER_WARNING);
                }
                else
                {
                    $path = $this->_getPath($data['model'], $data['id']);
                    if (!$this->_deletePath($path))
                        return false;
                    $this->filter(array('id' => $data['id']));
                }
                break;

            case 'update':
            case 'insert':
                $file_data = & $data['file'];
                unset($data['file']);
                $mime = $this->getUploadedFileMIMEType($file_data);

                if (@$data['id'])
                {
                    $path = $this->_getPath($data['model'], $data['id']);
                }
                else
                {
                    // generate unique path
                    do
                    {
                        $hash = $f->generateKey();
                        $path = $this->_getPath($data['model'], $hash);
                    }
                    while (file_exists($path));
                    $data['id'] = $hash;
                }

                $mime = $this->getUploadedFileMIMEType($file_data);

                if (true !== $this->_allowed_mime_types)
                {
                    $do_match = false;
                    foreach ($this->_allowed_mime_types as $mime_type_regex)
                    {
                        $regex = '/'.addcslashes($mime_type_regex, '\/').'/';
                        if (preg_match($regex, $mime))
                        {
                            $do_match = true;
                            break;
                        }
                    }
                    if (!$do_match)
                    {
                        /** @todo ERROR */
                        return false;
                    }
                }

                $data['original_name'] = $file_data['name'];
                $data['original_mime'] = $mime;

                if (!$this->_storeUploadedFile($path, $file_data))
                    return false;

                if ('update' == $action && !empty($data['id']))
                {
                    $this->filter(array(
                        'id' => $data['id']
                    ));
                }
                break;

            default:
                throw new HgException("Invalid action {$action}");
                break;
        }

        return parent::__syncSingle($data, $action, $error);
    }

    /**
     * Deleting row(s).
     * in contrast to __syncSingle() makes use of filter() method
     *
     * @param boolean $execute launch the query or just build it?
     */
    public function delete($execute = false)
    {
        if ($execute)
        {
            $this->exec();

            $f = g('Functions');

            foreach ($this->_array as & $data)
            {
                if (!empty($data['model']) && !empty($data['id']))
                {
                    $path = $this->_getPath($data['model'], $data['id']);
                    if (!$this->_deletePath($path))
                        return false;
                }
            }
        }

        return parent::delete($execute);
    }

    /**
     * Store uploaded file in given location
     *
     * @todo ERRORS!
     *
     * @param string $path location to store at
     * @param array $file_data data from $_FILE
     *
     * @return boolean success
     */
    protected function _storeUploadedFile($path, array $file_data)
    {
        if(!$this->_beforeStoring($path, $file_data, $action))
            return false;

        if (false !== $this->_max_size)
        {
            if ($file_data['size'] > $this->_max_size * 1024 * 1024)
            {
                $file_data['error'] = 'UPLOAD_ERR_MODEL_SIZE';
            }
        }

        switch ($file_data['error'])
        {
            case UPLOAD_ERR_OK :
                $error = false;
                break;
            case UPLOAD_ERR_INI_SIZE :
            case UPLOAD_ERR_FORM_SIZE :
            case 'UPLOAD_ERR_MODEL_SIZE' :
                $error = 'Błąd - %s - Zbyt duży plik';
                break;
            case UPLOAD_ERR_PARTIAL :
                $error = 'Błąd - %s - Nie udana próba wysłania pliku. Prosze spróbować jeszcze raz.';
                break;
            case UPLOAD_ERR_NO_FILE :
                $error = 'Błąd - %s - Brak pliku.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR :
            case UPLOAD_ERR_CANT_WRITE :
            case UPLOAD_ERR_EXTENSION :
                $error = 'Błąd - %s - Błąd serwera.';
                break;
        }

        if ($error)
        {
            g()->addInfo(null, 'error', $this->trans($error, $file_data['name']));
            return false;
        }

        // upload file
        if (is_uploaded_file($file_data['tmp_name']))
        {
            if (g()->debug->allowed())
                printf('<p class="debug">creating <code>%s</code>', $path);
            if (file_exists($path) && !unlink($path))
            {
                /** @todo error */
                return false;
            }
            if (!move_uploaded_file($file_data['tmp_name'], $path))
            {
                g()->addInfo(null, 'error', $this->trans('File has not been sent.'));
                unlink($file_data['tmp_name']);
                return false;
            }
        }
        else
        {
            g()->debug->addInfo(null, $this->trans('File has not been saved as temporary file.'));
            return false;
        }

        if(!$this->_afterStoring($path, $file_data, $action))
            return false;

        return true;
    }


    /**
     * Delete path
     * @author m.augustynowicz
     *
     * @param string $path file location (generated by _getPath())
     *
     * @return boolean false on any error
     */
    protected function _deletePath($path)
    {
        if (!$this->_beforeDeleting($path))
            return false;

        g('Functions')->rmrf($path); // will echo "deleting $path"

        if (!$this->_afterDeleting($path))
            return false;
        return true;
    }

    /**
     * Returns the full path of given filename in the upload directory.
     *
     * @todo ERRORS!
     *
     * @param string $model
     * @param string $file_name
     * @return string
     */
    protected function _getPath($model, $file_name)
    {
        $directory = $this->_upload_dir . $model . DIRECTORY_SEPARATOR;

        if (!file_exists($directory) && !mkdir($directory, 0777, true))
            throw new HgException("Error while creating upload dir: `$directory'!");

        if (!is_dir($directory))
        {
            throw new HgException($this->trans('%s is not a directory!', $directory));
        }

        return $directory . $file_name;
    }

    /**
     * Translate $file param that can be passed from other classes
     * @author m.augustynowicz
     *
     * @param array|int|null $file
     *        for array: array have to have [model] and [id] keys;
     *        for int: n-th of fetched rows is used;
     *        for null: last inserted or fetched row used
     *
     * @return array|false
     */
    protected function _translateFileParam($file=null)
    {
        if (null === $file)
        {
            if (!empty($this->_data))
                $file = & $this->_data;
            else
                $file = 0;
        }

        if (is_int($file))
        {
            if (isset($this->_array[$file]))
                $file = & $this->_array[$file];
        }

        if (!is_array($file))
        {
            return false;
        }

        return $file;
    }


    /**
     * Callback launched before deleting file from UPLOAD_DIR
     *
     * feel free to override
     * @author m.augustynowicz
     *
     * @param string $path file location (generated by _getPath())
     *
     * @return boolean returning false will stop everything
     */
    protected function _beforeDeleting($path)
    {
        return true;
    }


    /**
     * Callback launched after deleting file from UPLOAD_DIR
     *
     * feel free to override
     * @author m.augustynowicz
     *
     * @param string $path file location (generated by _getPath())
     *
     * @return boolean returning false will stop everything
     */
    protected function _afterDeleting($path)
    {
        return true;
    }


    /**
     * Callback launched before storing file in UPLOAD_DIR
     *
     * feel free to override
     * @author m.augustynowicz
     * 
     * @param string $path file location (generated by _getPath())
     * @param array $data part of database row (will contain [original_mime]
     *        and [original_name])
     * @param string $action update|insert
     *
     * @return boolean returning false will stop everything
     */
    protected function _beforeStoring($path, array $data, $action)
    {
        return true;
    }


    /**
     * Callback launched after storing file in UPLOAD_DIR
     *
     * feel free to override
     * @author m.augustynowicz
     * 
     * @param string $path file location (generated by _getPath())
     * @param array $data part of database row (will contain [original_mime]
     *        and [original_name])
     * @param string $action update|insert
     *
     * @return boolean returning false will stop everything
     */
    protected function _afterStoring($path, array $data, $action)
    {
        return true;
    }


    /**
     * Determines uploaded file's MIME type
     *
     * @author m.augustynowicz
     *
     * @param array|string $file_data
     *        array -- part of $_FILES,
     *        string -- file name
     *
     * @return false on any error
     */
    public function getUploadedFileMIMEType($file_data)
    {
        /* trusting mime type sent by user is not a very good idea..
        if (null !== @$file_data['type'])
        {
            return $file_data['type'];
        }
        else
        */
        if (is_array($file_data)
                && array_key_exists('file', g()->conf['unix'])
                && g()->conf['unix']['file'] )
        {
            return $this->_getMIMETypeByFile($file_data['tmp_name']);
        }
        else
        {
            $file_name = is_array($file_data) ? $file_data['name'] : $file_data;
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            return $this->_getMIMETypeBySuffix($extension);
        }
    }


    /**
     * Uses file(1) do determine file's MIME type
     *
     * @see http://linux.die.net/man/1/file
     * 
     * @author p.piskorski
     * @author m.izewski
     * @author m.augustynowicz
     * @author m.jutkiewicz
     * WPISUJCIE MIASTA!
     * 
     * @param string $file file path (may be relative to $_upload_dir)
     *      
     * @return false|string false on any error
     */
    protected function _getMIMETypeByFile($path)
    {
        if (empty($path))
            return false;

        $oldpwd = getcwd();
        chdir($this->_upload_dir);

        $last_line = g('Functions')->exec(
                'file', '-bi '.escapeshellarg($path), $out, $ret);

        if (0 != $ret)
            throw new HgException("Cannot describe MIME type");

        // file may have added charset after the semicolon
        list($mime) = explode(';', trim($last_line));

        chdir($oldpwd);

        return $mime;
    }

    /**
     * Returns mime type based on the extension.
     * 
     * @author p.piskorski
     * @author m.augustynowicz added application/octet-stream as default
     *         (fallback) mime type to unify with _getMIMETypeByFile()
     * @param string $suffix file's extension
     * @return string mime type
     */
    protected function _getMIMETypeBySuffix($suffix)
    {
        switch(strtolower($suffix))
        {
            // office files
            case 'odt':
                return 'application/vnd.oasis.opendocument.text';
            case 'odp':
                return 'application/vnd.oasis.opendocument.presentation';
            case 'ods':
                return 'application/vnd.oasis.opendocument.spreadsheet';
            case 'odg':
                return 'application/vnd.oasis.opendocument.graphics';
            case 'doc':
            case 'docx':
                return 'application/msword';
            case 'xls':
            case 'xlt':
            case 'xlm':
            case 'xld':
            case 'xla':
            case 'xlc':
            case 'xlw':
            case 'xll':
                return 'application/vnd.ms-excel';
            case 'ppt':
            case 'pps':
                return 'application/vnd.ms-powerpoint';
            case 'rtf':
                return 'application/rtf';

            case 'pdf':
                return 'application/pdf';

            // plain text
            case 'html':
            case 'htm':
            case 'php':
                return 'text/html';
            case 'txt':
                return 'text/plain';

            // images
            case 'jpg':
            case 'jpeg':
            case 'jpe':
                return 'image/jpeg';
            case 'tif':
            case 'tiff':
                return 'image/tiff';
            case 'png':
            case 'gif':
            case 'bmp':
                return 'image/' . $suffix;

            // video
            case 'mpeg':
            case 'mpg':
            case 'mpe':
                return 'video/mpeg';
            case 'flv':
                return 'video/x-flv';
            case 'avi':
                return 'video/msvideo';
            case 'wmv':
                return 'video/x-ms-wmv';
            case 'mov':
                return 'video/quicktime';

            // audio
            case 'mp3':
                return 'audio/mpeg3';
            case 'wav':
                return 'audio/wav';
            case 'aiff':
            case 'aif':
                return 'audio/aiff';

            // other media
            case 'swf':
                return 'application/x-shockwave-flash';

            // archives
            case 'zip':
                return 'application/zip';
            case 'tar':
                return 'application/x-tar';

            // webdev files
            case "js":
                return 'application/x-javascript';
            case 'json':
                return 'application/json';
            case 'css':
                return 'text/css';
            case 'xml':
                return 'application/xml';


            default:
                return 'application/octet-stream';
        }
    }
}
