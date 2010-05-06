<?php
g()->load('DataSets', null);

/**
 * Model for uploaded files (except images, see: ImagesUploadModel)
 * @author m.jutkiewicz
 *
 */
class UploadModel extends Model
{
    private $__upload_dir;
    private $__max_size = 10; //(in megabytes)

    public function __construct()
    {
        $this->_table_name = 'upload';
        $this->__upload_dir = UPLOAD_DIR . 'files/';
        parent::__construct();

        $this->__addField(new FString('id', true, null, 32, 32));
        $this->__addField(new FInt('id_in_model', 4, true));
        $this->__addField(new FString('model', true, null, 0, 128));
        $this->__addField(new FString('mime', false, null, 0, 128));
        $this->__addField(new FString('original_name', false, null, 0, 256));
        $this->__addField(new FString('title', false, null, 0, 64));
        $this->__addField(new FString('description', false, null, 0, 512));

        $this->__pk('id');
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

    protected function __syncSingle(&$data, $action, &$error)
    {
        $folder = $this->__upload_dir . $data['model'] . '/';

        if(file_exists($folder))
            ;//g()->debug->addInfo(null, $this->trans('Directory %s exists', $name));
        elseif(mkdir($folder))
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
                $this->_file = $data['file'];
                unset($data['file']);

                if(!($hash = @$data['id']))
                    do
                    {
                        $hash = g('Functions')->generateKey();
                        $full_name = $this->getFullFileName($data['model'], $hash);
                    }
                    while($this->fileExists($full_name));

                $data['id'] = $hash;
                $data['mime'] = $this->_file['type'];
                $data['original_name'] = $this->_file['name'];

                if(!$this->__addFile($data['model'], $hash))
                    return false;

                if(is_uploaded_file($this->_file['tmp_name']))
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

        return parent::__syncSingle($data, $action, $error);
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

    protected function __addFile($model, $hash)
    {
        $file = $this->_file;
        $folder = $this->__upload_dir . $model . '/';

        if($file['size'] > $this->__max_size * 1024 * 1024)
        {
            g()->addInfo(null, 'error', $this->trans('Filesize is too big.'));
            return false;
        }

        if(file_exists($folder))
            ;//g()->debug->addInfo(null, $this->trans('Directory %s exists', $name));
        elseif(mkdir($folder))
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
                    $info = 'Błąd - %s - Nie udana próba wysłania pliku. Prosze spróbować jeszcze raz.';
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
        $ext = @$ext[count($ext) - 1];

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
            {
                if($ext)
                    $mime = $this->getMIMETypeBySuffix($ext);
                else
                    throw new HgException('This file cannot be uploaded without system `file` command.');
            }
        }
        else
            $mime = $file['type'];

        //upload file
        if(is_file($file['tmp_name']))
        {
            $path = $folder . $hash;
            if(g()->debug->allowed())
                printf('<p class="debug">creating <code>%s</code>', $path);
            if(!move_uploaded_file($file['tmp_name'], $path))
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
}
