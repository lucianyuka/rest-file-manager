<?php
declare (strict_types = 1);

namespace App;

use App\Auth;
use App\Response;
use App\User;
use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Main.
 *
 * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
 * @since    v0.0.1
 * @version    v1.0.0    Friday, March 29th, 2019.
 * @global
 */
class Main
{
    private $username;
    private $response;
    private $user;
    private $auth;
    private $filesystem;

    private static $aclJSON;

    private static $current_dir_path;
    private static $uploadFolder;
    private static $tempFolder;

    public function __construct()
    {

        $this::$aclJSON = __DIR__ . DIRECTORY_SEPARATOR . getenv('JSON_FILE');
        $uploadsFolderName = getenv('UPLOADS_FOLDER');
        $tempFolderName = getenv('TEMP_FOLDER');

        $this::$current_dir_path = getcwd();
        $this::$uploadFolder = $this::$current_dir_path . DIRECTORY_SEPARATOR . $uploadsFolderName;
        $this::$tempFolder = $this::$uploadFolder . DIRECTORY_SEPARATOR . $tempFolderName;

        $this->filesystem = new Filesystem();
        $this->response = new Response();
        $this->user = new User;
        $this->auth = new Auth;
        $this->username = $this->auth->getUsernameFromToken();

    }

    /**
     * showInfo.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @param    string    $data
     * @return    void
     */
    public function showInfo(string $data)
    {
        $this->checkUserAccess("read-file");

        if (!isset($data)) {
            $this->finalResponse(415, "no data");
        }

        $data = trim($data, "/");
        $data = filter_path($data);
        $pathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $data;

        if (!$this->filesystem->exists($pathInput)) {
            $this->finalResponse(400, "File/Path " . $data . " does Not Exist");
        }

        if (is_dir($pathInput)) {
            $isDirEmpty = !(new \FilesystemIterator($pathInput))->valid();
            $pathInfo = array(
                "Path" => $data,
                "Is Empty" => $isDirEmpty,
                "Created on" => @date("d M Y h:i:s A", filectime($pathInput)),
                "Last Accessed on" => @date("d M Y h:i:s A", fileatime($pathInput)),
                "Last Modified on" => @date("d M Y h:i:s A", filemtime($pathInput)),
                "Path Permissions" => getFilePerms($pathInput),
            );

            $this->finalResponse(200, "File Info", null, $pathInfo);

        } elseif (is_file($pathInput)) {

            $fileInfo = array(
                "Filename" => basename($pathInput),
                "FileSize" => FileSizeConvert($pathInput),
                "File Type" => mime_content_type($pathInput),
                "Path" => pathinfo($data, PATHINFO_DIRNAME),
                "Last Accessed" => @date("d M Y h:i:s A", fileatime($pathInput)),
                "Last Modified on" => @date("d M Y h:i:s A", filemtime($pathInput)),
                "File Permissions" => getFilePerms($pathInput),
            );

            $this->finalResponse(200, "File Info", null, $fileInfo);
        }

    }

    /**
     * upload.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function upload()
    {

        if (!isset(getAllHeaders()["Content-Type"])) {
            $this->finalResponse(415, "Content-Type Missing");
        } else {
            $desiredHeader = getAllHeaders()["Content-Type"];
            $data = explode(";", $desiredHeader);

            if (strcasecmp($data[0], "multipart/form-data") !== 0) {
                $this->finalResponse(415, "Only form-data Allowed for Upload");
            }
        }

        $this->checkUserAccess("create-file");
dd(filter_path($_POST['path'], "/"));
        if (empty($_FILES)) {
            $this->finalResponse(422, "Missing File Key or Selected File");
        }

        if (empty($_POST)) {
            $this->finalResponse(422, "Missing Path Key");
        }

        if (!array_key_exists("file", $_FILES)) {
            $this->finalResponse(422, "Missing Property");
        }

        if (!array_key_exists("path", $_POST)) {
            $this->finalResponse(422, "Missing Property");
        }

        if (count($_FILES) != 1) {
            $this->finalResponse(412, "Uploading Multiple Files is Not Allowed");
        }

        if (trim($_POST['path'], "/") === "") {
            $this->finalResponse(422, "Empty Path Not Allowed");
        }

        if (!$_FILES['file']) {
            $this->finalResponse(412, "Missing Property");
        }

        $fileInput = $_FILES['file'];

        if (!file_exists($fileInput['tmp_name']) || !is_uploaded_file($fileInput['tmp_name'])) {
            $this->finalResponse(412, "Missing Property");
        }

        $tmpInput = $fileInput['tmp_name'];
        $filenameInput = filter_filename($fileInput['name']);
        $file_type = $fileInput['type'];

        $pathInput = filter_path($_POST['path']);

        $tempFilePath = $this::$tempFolder . DIRECTORY_SEPARATOR . $filenameInput;
        $targetFilePath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $pathInput . DIRECTORY_SEPARATOR . $filenameInput;

        $allowed_types = array("image/jpeg", "image/gif", "image/png", "image/svg", "application/pdf");

        if (!in_array($file_type, $allowed_types)) {
            $this->finalResponse(400, "Filetype Not Allowed");
        }

        if ($this->filesystem->exists($targetFilePath)) {
            $this->finalResponse(400, "File exsit");
        }

        deleteDirectory($this::$tempFolder, true);

        try {
            $this->filesystem->copy($tmpInput, $tempFilePath);
        } catch (IOExceptionInterface $exception) {
            //dd($exception);
            $this->finalResponse(500, "An error occured while trying to load the given file!" . $exception);
        }

        $mimeDetector = new MimeDetector();

        try {
            $mimeDetector->setFile($tempFilePath);
        } catch (MimeDetectorException $exception) {
            $this->finalResponse(500, "An error occured while trying to load the given file!" . $exception);
        }

        $realMimeType = $mimeDetector->getFileType();

        if ($realMimeType["mime"] != $file_type) {
            deleteDirectory($this::$tempFolder, true);
            $this->finalResponse(400, "Filetype Not Conform");
        }

        try {
            $this->filesystem->copy($tempFilePath, $targetFilePath);
        } catch (IOExceptionInterface $exception) {
            //dd($exception);
            $this->finalResponse(500, "Error creating directory at" . $exception);
        }

        deleteDirectory($this::$tempFolder, true);

        $this->finalResponse(201, "File " . $filenameInput . " uploaded successfully to " . $pathInput);

    }

    /**
     * addFolder.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function addFolder()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("create-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!array_key_exists("path", $object)) {
            $this->finalResponse(400, "Missing Property");
        }

        $pathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['path'];

        if ($this->filesystem->exists($pathInput)) {
            $this->finalResponse(400, "Path Exists");
        }

        //make a new directory
        try {
            $old = umask(0);
            $this->filesystem->mkdir($pathInput, 0775);
            $this->filesystem->chown($pathInput, "www-data");
            $this->filesystem->chgrp($pathInput, "www-data");
            umask($old);
        } catch (IOExceptionInterface $exception) {
            $this->finalResponse(400, "Error creating directory at" . $exception->getPath());
        }

        $this->finalResponse(201, "Path " . $pathInput . " was successfully created!");

    }

    /**
     * rename.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function rename()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("update-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!array_key_exists("old_file_path", $object) or !array_key_exists("new_file_path", $object)) {
            $this->finalResponse(400, "Missing Property");
        }

        $oldPathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['old_file_path'];
        $newPathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['new_file_path'];

        if (!$this->filesystem->exists($oldPathInput)) {
            $this->finalResponse(400, "Path " . $oldPathInput . " does Not Exist");
        }

        if ($this->filesystem->exists($newPathInput)) {
            $this->finalResponse(400, "Path " . $newPathInput . " Exists");
        }

        try {
            $this->filesystem->rename($oldPathInput, $newPathInput);
        } catch (IOExceptionInterface $exception) {
            $this->finalResponse(400, "Error creating directory at" . $exception->getPath());
        }

        $this->finalResponse(200, "Path " . $oldPathInput . " successfully renamed to " . $newPathInput);

    }

    /**
     * copy.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function copy()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("update-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!array_key_exists("source", $object) or !array_key_exists("dest", $object)) {
            $this->finalResponse(400, "Missing Property");
        }

        $object['source'] = trim($object['source'], "/");
        $sourcePathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['source'];
        $destPathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['dest'];
        $fileSource = basename($object['source']);
        $pathSource = pathinfo($object['source'], PATHINFO_DIRNAME);

        if (!is_file($destPathInput)) {
            $this->finalResponse(400, $object['source'] . " Is Not a File");
        }

        if (!is_dir($destPathInput)) {
            $this->finalResponse(400, $object['dest'] . " Is Not a Path");
        }

        if (!$this->filesystem->exists($sourcePathInput)) {
            $this->finalResponse(400, "File " . $object['source'] . " does Not Exist");
        }

        if (!$this->filesystem->exists($destPathInput)) {
            $this->finalResponse(400, "Path " . $object['dest'] . " does Not Exist");
        }

        if ($this->filesystem->exists($destPathInput . DIRECTORY_SEPARATOR . $fileSource)) {
            $this->finalResponse(400, "File " . $fileSource . " Exists in folder " . $object['dest']);
        }

        try {
            $this->filesystem->copy($sourcePathInput, $destPathInput . DIRECTORY_SEPARATOR . $fileSource);
        } catch (IOExceptionInterface $exception) {
            //dd($exception);
            $this->finalResponse(400, "Error creating directory at" . $exception);
        }

        $this->finalResponse(200, "File " . $fileSource . " copied successfully from " . $pathSource . "  to " . $destPathInput);

    }

    /**
     * copyFolder.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function copyFolder()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("update-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!array_key_exists("source", $object) or !array_key_exists("dest", $object)) {
            $this->finalResponse(400, "Missing Property");
        }

        $object['source'] = trim($object['source'], "/");
        $sourcePathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['source'];
        $destPathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['dest'];

        if (!is_dir($sourcePathInput)) {
            $this->finalResponse(400, $object['source'] . " Is Not a Path");
        }

        if (!is_dir($destPathInput)) {
            $this->finalResponse(400, $object['dest'] . " Is Not a Path");
        }

        if (!$this->filesystem->exists($sourcePathInput)) {
            $this->finalResponse(400, "File " . $object['source'] . " does Not Exist");
        }

        if (!$this->filesystem->exists($destPathInput)) {
            $this->finalResponse(400, "Path " . $object['dest'] . " does Not Exist");
        }

        try {
            $this->filesystem->mirror($sourcePathInput, $destPathInput);
        } catch (IOExceptionInterface $exception) {
            ($exception);
            $this->finalResponse(400, "Error creating directory at" . $exception);
        }

        $this->finalResponse(200, "Content of Path " . $object['source'] . " successfully copied to " . $object['dest']);

    }

    /**
     * delete.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function delete()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("delete-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!array_key_exists("path", $object)) {
            $this->finalResponse(400, "Missing Property");
        }

        $object['path'] = trim($object['path'], "/");
        $pathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['path'];

        if (!$this->filesystem->exists($pathInput)) {
            $this->finalResponse(400, "File " . $object['path'] . " does Not Exist");
        }

        if (is_dir($pathInput)) {
            //$this->finalResponse(400, $object['path'] . " Is Not a Path");
            $isDirEmpty = !(new \FilesystemIterator($pathInput))->valid();
            if (!$isDirEmpty) {
                $this->finalResponse(400, "Path " . $object['path'] . " Is Not Empty");
            }
            try {
                $this->filesystem->remove($pathInput);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error creating directory at" . $exception);
            }
            $this->finalResponse(200, "Path " . $object['path'] . " was successfully deleted!");
        } elseif (is_file($pathInput)) {
            try {
                $this->filesystem->remove($pathInput);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error creating directory at" . $exception);
            }
            $this->finalResponse(200, "File " . $object['path'] . " was successfully deleted!");
        }

    }

    /**
     * forceDelete.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function forceDelete()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("delete-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!array_key_exists("path", $object)) {
            $this->finalResponse(400, "Missing Property");
        }

        $object['path'] = trim($object['path'], "/");
        $pathInput = $this::$uploadFolder . DIRECTORY_SEPARATOR . $object['path'];

        if (!$this->filesystem->exists($pathInput)) {
            $this->finalResponse(400, "File " . $object['path'] . " does Not Exist");
        }

        if (is_dir($pathInput)) {
            try {
                $this->filesystem->remove($pathInput);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error creating directory at" . $exception);
            }
            $this->finalResponse(200, "Path " . $object['path'] . " was successfully deleted!");
        } elseif (is_file($pathInput)) {
            try {
                $this->filesystem->remove($pathInput);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error creating directory at" . $exception);
            }
            $this->finalResponse(200, "File " . $object['path'] . " was successfully deleted!");
        }

    }

    /**
     * addUser.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Thursday, March 28th, 2019.
     * @access    public
     * @return    void
     */
    public function addUser()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("create-user");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        $this->checkResponseData($object, true);
        $this->checkPermInput($object);

        $json_a = jsonToArray($this::$aclJSON);

        $output = array_merge($json_a, array(convertToLowerCase($object['username']) => $object['permissions_string']));
        file_put_contents($this::$aclJSON, json_encode($output, JSON_PRETTY_PRINT));

        $token_generated = $this->auth->generateToken($object['username']);

        $this->finalResponse(201, "User " . $object['username'] . " with permissions " . $object['permissions_string'] . " was added successfully", $token_generated);

    }

    /**
     * userInfo.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @param    string    $data
     * @return    void
     */
    public function userInfo(string $data)
    {
        $this->checkUserAccess("read-user");

        if (!isset($data)) {
            $this->finalResponse(415, "no data");
        }

        $data = trim($data, "/");
        $data = filter_path($data);

        if (!$this->user->isRegistredUser($data)) {
            $this->finalResponse(400, "Username Not Available");
        }

        $json_a = jsonToArray($this::$aclJSON);

        $perm_array = array();

        foreach ($json_a as $id => $value) {
            if ($id == convertToLowerCase($data)) {
                $codes_arr = explode('-', $value);
                foreach ($codes_arr as $key => $val) {
                    foreach ($this->user::$permissions as $code => $permision_string) {
                        if ($val == $code) {
                            $perm_array[] = $permision_string;
                        }
                    }
                }
                $str = implode(", ", $perm_array);
                $this->finalResponse(200, "User " . $key . " has the following permissions : " . rtrim($str, ', '));
            }
        }

    }

    /**
     * listUsers.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function listUsers()
    {
        $this->checkUserAccess("read-user");

        $json_a = jsonToArray($this::$aclJSON);
        $str = '';
        foreach ($json_a as $key => $val) {
            $str .= $key . ", ";
        }

        $this->finalResponse(200, "There are " . count($json_a) . " Users : " . rtrim($str, ', '));

    }

    /**
     * updateUser.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function updateUser()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("update-users-permissions");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        $this->checkResponseData($object, true);
        $this->checkPermInput($object);

        $json_a = jsonToArray($this::$aclJSON);

        foreach ($json_a as $key => &$val) {
            if ($key == convertToLowerCase($object['username'])) {
                if ($val == $object['permissions_string']) {
                    $this->finalResponse(200, "Nothing to Update");
                } else {
                    $val = $object['permissions_string'];
                }
            }
        }

        file_put_contents($this::$aclJSON, json_encode($json_a, JSON_PRETTY_PRINT));

        $this->finalResponse(200, "User " . $object['username'] . " was succefully updated with the following permissions " . $object['permissions_string']);

    }

    /**
     * deleteUser.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @return    void
     */
    public function deleteUser()
    {
        $this->checkContentTypeJson();
        $this->checkUserAccess("delete-user");

        $input = file_get_contents('php://input');
        //parse_str(file_get_contents("php://input"), $input);
        $object = json_decode($input, true);

        //dd(gettype($object));
        $this->checkResponseData($object);

        $json_a = jsonToArray($this::$aclJSON);

        unset($json_a[convertToLowerCase($object['username'])]);

        file_put_contents($this::$aclJSON, json_encode($json_a, JSON_PRETTY_PRINT));

        $this->finalResponse(200, "User " . $object['username'] . " was succefully deleted!");

    }

    /**
     * checkResponseData.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    private
     * @param    array      $object
     * @param    boolean    $checkPerm    Default: false
     * @return    void
     */
    private function checkResponseData(array $object, bool $checkPerm = false): void
    {

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if ($checkPerm) {
            if (!array_key_exists("username", $object) or !array_key_exists("permissions_string", $object)) {
                $this->finalResponse(400, "Missing3 Property");
            }
        }

        if (!array_key_exists("username", $object)) {
            $this->finalResponse(400, "Missing Property");
        }

        if (!$this->user->isRegistredUser($object['username'])) {
            $this->finalResponse(400, "Username Not Available");
        }

    }

    /**
     * checkContentTypeJson.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    private
     * @return    void
     */
    private function checkContentTypeJson()
    {

        if (!isset(getAllHeaders()["Content-Type"])) {
            $this->finalResponse(401, "Content-Type Missing");
        } else {
            $desiredHeader = getAllHeaders()["Content-Type"];
            if ($desiredHeader !== 'application/json') {
                $this->finalResponse(401, "Only JSON Allowed");
            }
        }
    }

    /**
     * checkUserAccess.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    private
     * @param    string    $perm
     * @return    void
     */
    private function checkUserAccess(string $perm)
    {
        if (!$this->user->hasThePerm($this->username, $perm)) {
            $this->finalResponse(401, "no authorization");
        }

    }

    /**
     * finalResponse.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    private
     * @param    int       $status
     * @param    string    $content
     * @param    string    $apiKey          Default: null
     * @param    array     $filePathInfo    Default: []
     * @return    void
     */
    private function finalResponse(int $status, string $content, string $apiKey = null, array $filePathInfo = [])
    {
        $this->response->setStatus($status);
        if ($apiKey) {
            $this->response->setUserCred($apiKey);
        }
        if ($filePathInfo) {
            $this->response->setfilePathInfo($filePathInfo);
        }
        $this->response->setContent($content);
        $this->response->finish();
    }

    /**
     * checkPermInput.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    private
     * @param    array    $object
     * @return    void
     */
    private function checkPermInput(array $object)
    {
        $perms_input = explode('-', $object['permissions_string']);

        if (count($perms_input) != 8) {
            $this->finalResponse(400, "Permissions too long or too short");
        }
        $target_arr = explode('-', 'cf-rf-uf-df-cu-ru-uu-du');
        //$target_arr2 = explode('-', 'xx-xx-xx-xx-xx-xx-xx-xx');
        //$this->response->setContent(count(array_intersect($target_arr1, $perms_input)). " - ". count(array_intersect($target_arr2, $perms_input)) .' --- '.count(array_diff($target_arr1, $perms_input)). " - ". count(array_diff($target_arr2, $perms_input)));
        foreach ($perms_input as $key => $val) {
            foreach ($target_arr as $prop => $data) {
                if ($key == $prop) {
                    if ($val != $data and $val != 'xx') {
                        $this->finalResponse(400, "Permissions Not Accurate");
                    }
                }
            }
        }
    }

}
