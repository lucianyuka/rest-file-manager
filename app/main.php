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
            $this->finalResponse(415, "You need to provide the File or Folder path in the url!");
        }

        $srcPathFirstCleaning = cleanInputPath($data);


        if (hasPathEndsWithFile($srcPathFirstCleaning)) {
            $srcPathLessFile = removeFilenameFromPath($srcPathFirstCleaning);
            $srcPathOnly = filter_path($srcPathLessFile);
            //$srcFilename = substr(strrchr($srcPathFirstCleaning, "/"), 1);
            $srcFilename = basename($srcPathFirstCleaning);
            $srcFilename = filter_filename($srcFilename);
            $srcPathInput = $srcPathOnly . DIRECTORY_SEPARATOR . $srcFilename;
        } else {
            $srcPathOnly = filter_path($srcPathFirstCleaning);
            $srcPathInput = $srcPathOnly;
        }

        $srcFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $srcPathInput;

        if (!$this->filesystem->exists($srcFullPath)) {
            $this->finalResponse(400, "File/Folder " . $srcPathInput . " does Not Exist");
        }

        if (is_dir($srcFullPath)) {
            $isDirEmpty = !(new \FilesystemIterator($srcFullPath))->valid();
            $pathInfo = array(
                "Path" => $srcPathInput,
                "Is Empty" => returnHumanReadableBoolean($isDirEmpty),
                "Created on" => @date("d M Y h:i:s A", filectime($srcFullPath)),
                "Last Accessed on" => @date("d M Y h:i:s A", fileatime($srcFullPath)),
                "Last Modified on" => @date("d M Y h:i:s A", filemtime($srcFullPath)),
                "Path Permissions" => getFilePerms($srcFullPath),
            );

            $this->finalResponse(200, "Folder Info", null, $pathInfo);

        } elseif (is_file($srcFullPath)) {

            $fileInfo = array(
                "Filename" => basename($srcFullPath),
                "Path" => pathinfo($srcPathInput, PATHINFO_DIRNAME),
                "File Type" => mime_content_type($srcFullPath),
                "FileSize" => FileSizeConvert($srcFullPath),
                "Last Accessed" => @date("d M Y h:i:s A", fileatime($srcFullPath)),
                "Last Modified on" => @date("d M Y h:i:s A", filemtime($srcFullPath)),
                "File Permissions" => getFilePerms($srcFullPath),
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
        $this->checkContentType("multipart/form-data", true);

        $this->checkUserAccess("create-file");

        if (empty($_FILES) || empty($_POST)) {
            $this->finalResponse(422, "Missing Informations");
        }

        if (!array_key_exists("file", $_FILES) || !array_key_exists("path", $_POST)) {
            $this->finalResponse(422, "Missing file Property");
        }

        if (count($_FILES) != 1) {
            $this->finalResponse(412, "Uploading Multiple Files is Not Allowed");
        }

        $dstPathFirstCleaning = cleanInputPath($_POST['path']);

        if ($dstPathFirstCleaning === "") {
            $this->finalResponse(422, "Empty Path Not Allowed");
        }

        if (hasPathEndsWithFile($dstPathFirstCleaning)) {
            $this->finalResponse(400, $dstPathFirstCleaning . " is not a Path!");
        }

        $srcFileInput = $_FILES['file'];

        if (!file_exists($srcFileInput['tmp_name']) || !is_uploaded_file($srcFileInput['tmp_name'])) {
            $this->finalResponse(412, "File Not uploaded");
        }

        $srcFileTmpInput = $srcFileInput['tmp_name'];
        $srcFileNameInput = filter_filename($srcFileInput['name']);
        $srcFileTypeInput = $srcFileInput['type'];

        $allowed_types = array("image/jpeg", "image/gif", "image/png", "image/svg", "application/pdf");

        if (!in_array($srcFileTypeInput, $allowed_types)) {
            $this->finalResponse(400, "Filetype Not Allowed");
        }

        $destPathOnly = filter_path($dstPathFirstCleaning);
        $tempFilePath = $this::$tempFolder . DIRECTORY_SEPARATOR . $srcFileNameInput;
        $destPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $destPathOnly;
        $destFullPath = $destPath . DIRECTORY_SEPARATOR . $srcFileNameInput;

        if ($this->filesystem->exists($destFullPath)) {
            $this->finalResponse(400, "File Already exsits");
        }

        deleteDirectory($this::$tempFolder, true);

        try {
            $this->filesystem->copy($srcFileTmpInput, $tempFilePath);
        } catch (IOExceptionInterface $exception) {
            //dd($exception);
            deleteDirectory($this::$tempFolder, true);
            $this->finalResponse(500, "An error occured while trying to load the given file!");
        }

        $mimeDetector = new MimeDetector();

        try {
            $mimeDetector->setFile($tempFilePath);
        } catch (MimeDetectorException $exception) {
            deleteDirectory($this::$tempFolder, true);
            $this->finalResponse(500, "An error occured!");
        }

        $realMimeType = $mimeDetector->getFileType();

        if ($realMimeType["mime"] != $srcFileTypeInput) {
            deleteDirectory($this::$tempFolder, true);
            $this->finalResponse(400, "Filetype Not Conform");
        }

        try {
            $this->filesystem->copy($tempFilePath, $destFullPath);
        } catch (IOExceptionInterface $exception) {
            deleteDirectory($this::$tempFolder, true);
            $this->finalResponse(500, "Error creating directory at" . $exception);
        }

        deleteDirectory($this::$tempFolder, true);

        $this->finalResponse(201, "File " . $srcFileNameInput . " uploaded successfully to " . $destPathOnly);

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
        $this->checkContentType();
        $this->checkUserAccess("create-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        $desiredKeys = array("path");
        $this->checkPathResponseData($object, $desiredKeys);

        $srcPathFirstCleaning = cleanInputPath($object['path']);

        if (hasPathEndsWithFile($srcPathFirstCleaning)) {
            $this->finalResponse(400, $srcPathFirstCleaning . " is not a Folder path!");
        }

        $srcPathInput = filter_path($srcPathFirstCleaning);
        $srcFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $srcPathInput;

        $srcInfo = explode("/", $srcPathInput);
        $srcFolderInfo = array_pop($srcInfo);
        $srcFolderPathInfo = implode("/", $srcInfo);

        if ($this->filesystem->exists($srcFullPath)) {
            if (empty($srcFolderPathInfo)) {
                $this->finalResponse(400, $srcFolderInfo . " already Exists in root folder!");
            }
            $this->finalResponse(400, $srcFolderInfo . " already Exists in " . $srcFolderPathInfo);
        }

        //make a new directory
        try {
            $this->filesystem->mkdir($srcFullPath, 0775);
        } catch (IOExceptionInterface $exception) {
            $this->finalResponse(400, "Error creating directory");
        }

        $this->finalResponse(201, "Path " . $srcPathInput . " was successfully created!");

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
        $this->checkContentType();
        $this->checkUserAccess("update-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);
        $desiredKeys = array("old_file_path", "new_file_path");

        $this->checkPathResponseData($object, $desiredKeys);

        $srcPathFirstCleaning = cleanInputPath($object['old_file_path']);

        if (hasPathEndsWithFile($srcPathFirstCleaning)) {
            $srcPathLessFile = removeFilenameFromPath($srcPathFirstCleaning);
            $srcPathOnly = filter_path($srcPathLessFile);
            //$srcFilename = substr(strrchr($srcPathFirstCleaning, "/"), 1);
            $srcFilename = basename($srcPathFirstCleaning);
            $srcFilename = filter_filename($srcFilename);
            $srcPathInput = $srcPathOnly . DIRECTORY_SEPARATOR . $srcFilename;
        } else {
            $srcPathOnly = filter_path($srcPathFirstCleaning);
            $srcPathInput = $srcPathOnly;
        }

        $srcFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $srcPathInput;

        if (!$this->filesystem->exists($srcFullPath)) {
            $this->finalResponse(400, "File/Folder " . $srcPathInput . " does Not Exist");
        }

        $dstPathFirstCleaning = cleanInputPath($object['new_file_path']);

        if (hasPathEndsWithFile($dstPathFirstCleaning)) {
            $destPathLessFile = removeFilenameFromPath($dstPathFirstCleaning);
            $destPathOnly = filter_path($destPathLessFile);
            //$srcFilename = substr(strrchr($dstPathFirstCleaning, "/"), 1);
            $destFilename = basename($dstPathFirstCleaning);
            $destFilename = filter_filename($srcFilename);
            $destPathInput = $destPathOnly . DIRECTORY_SEPARATOR . $destFilename;
        } else {
            $destPathOnly = filter_path($dstPathFirstCleaning);
            $destPathInput = $destPathOnly;
        }

        $destFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $destPathInput;

        if ($this->filesystem->exists($destFullPath)) {
            $this->finalResponse(400, "File/Folder " . $destPathInput . " already Exists!");
        }

        try {
            $this->filesystem->rename($srcFullPath, $destFullPath);
        } catch (IOExceptionInterface $exception) {
            $this->finalResponse(400, "Error renaming file or directory!");
        }

        $this->finalResponse(200, "Path " . $srcPathInput . " successfully renamed to " . $destPathInput);

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
        $this->checkContentType();
        $this->checkUserAccess("update-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        $desiredKeys = array("source", "dest");

        $this->checkPathResponseData($object, $desiredKeys);

        $srcPathFirstCleaning = cleanInputPath($object['source']);

        if (!hasPathEndsWithFile($srcPathFirstCleaning)) {
            $this->finalResponse(400, $srcPathFirstCleaning . " does not contain a filename!");
        }

        $dstPathFirstCleaning = cleanInputPath($object['dest']);

        if ($dstPathFirstCleaning === "") {
            $this->finalResponse(400, " Empty Path Not Allowed!");
        }

        if (hasPathEndsWithFile($dstPathFirstCleaning)) {
            $this->finalResponse(400, $dstPathFirstCleaning . " is not a Path!");
        }

        $srcPathLessFile = removeFilenameFromPath($srcPathFirstCleaning);
        //$srcPathLessFile = pathinfo($srcPathFirstCleaning, PATHINFO_DIRNAME);
        $srcPathOnly = filter_path($srcPathLessFile);
        //$srcFilename = substr(strrchr($srcPathFirstCleaning, "/"), 1);
        $srcFilename = basename($srcPathFirstCleaning);
        $srcFilename = filter_filename($srcFilename);
        $srcPathInput = $srcPathOnly . DIRECTORY_SEPARATOR . $srcFilename;
        $srcFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $srcPathInput;

        if (!$this->filesystem->exists($srcFullPath)) {
            $this->finalResponse(400, "File " . $srcPathInput . " does Not Exist");
        }

        $destPathInput = filter_path($dstPathFirstCleaning);
        $destFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $destPathInput;

        if (!$this->filesystem->exists($destFullPath)) {
            $this->finalResponse(400, "Path " . $destPathInput . " does Not Exist");
        }

        $destFullPathAndFile = $destFullPath . DIRECTORY_SEPARATOR . $srcFilename;

        if ($this->filesystem->exists($destFullPathAndFile)) {
            $this->finalResponse(400, "File " . $srcFilename . " already exists in folder " . $destPathInput);
        }

        try {

            $this->filesystem->copy($srcFullPath, $destFullPathAndFile);

        } catch (IOExceptionInterface $exception) {
            //dd($exception);
            $this->finalResponse(400, "Error copying file");
        }

        $this->finalResponse(200, "File " . $srcFilename . " copied successfully from " . $srcPathOnly . "  to " . $destPathInput);

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
        $this->checkContentType();
        $this->checkUserAccess("update-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        $desiredKeys = array("source", "dest");

        $this->checkPathResponseData($object, $desiredKeys);

        $srcPathFirstCleaning = cleanInputPath($object['source']);

        if (hasPathEndsWithFile($srcPathFirstCleaning)) {
            $this->finalResponse(400, $srcPathFirstCleaning . " is not a Path!");
        }

        $dstPathFirstCleaning = cleanInputPath($object['dest']);

        if (hasPathEndsWithFile($dstPathFirstCleaning)) {
            $this->finalResponse(400, $dstPathFirstCleaning . " is not a Path!");
        }

        $srcPathInput = filter_path($srcPathFirstCleaning);
        $srcFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $srcPathInput;

        if (!$this->filesystem->exists($srcFullPath)) {
            $this->finalResponse(400, "Path " . $srcPathInput . " does Not Exist");
        }

        $destPathInput = filter_path($dstPathFirstCleaning);
        $destFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $destPathInput;

        if (!$this->filesystem->exists($destFullPath)) {
            $this->finalResponse(400, "Path " . $destPathInput . " does Not Exist");
        }

        //We could add a routine here to check if any file ond src folder not
        //present on the destination folder. https://github.com/sureshdotariya/folder-compare

        try {
            $this->filesystem->mirror($srcFullPath, $destFullPath);
        } catch (IOExceptionInterface $exception) {
            //dd($exception);
            $this->finalResponse(400, "Error copying directory");
        }

        $this->finalResponse(200, "Content of Path " . $srcPathInput . " successfully copied to " . $destPathInput);

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
        $this->checkContentType();
        $this->checkUserAccess("delete-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);
        $desiredKeys = array("path");

        $this->checkPathResponseData($object, $desiredKeys);

        $srcPathFirstCleaning = cleanInputPath($object['path']);

        if (hasPathEndsWithFile($srcPathFirstCleaning)) {
            $srcPathLessFile = removeFilenameFromPath($srcPathFirstCleaning);
            $srcPathOnly = filter_path($srcPathLessFile);
            //$srcFilename = substr(strrchr($srcPathFirstCleaning, "/"), 1);
            $srcFilename = basename($srcPathFirstCleaning);
            $srcFilename = filter_filename($srcFilename);
            $srcPathInput = $srcPathOnly . DIRECTORY_SEPARATOR . $srcFilename;
        } else {
            $srcPathOnly = filter_path($srcPathFirstCleaning);
            $srcPathInput = $srcPathOnly;
        }

        $srcFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $srcPathInput;

        if (!$this->filesystem->exists($srcFullPath)) {
            $this->finalResponse(400, "File/Folder " . $srcPathInput . " does Not Exist");
        }

        if (is_dir($srcFullPath)) {
            $isDirEmpty = !(new \FilesystemIterator($srcFullPath))->valid();
            if (!$isDirEmpty) {
                $this->finalResponse(400, "Path " . $srcPathInput . " Is Not Empty");
            }
            try {
                $this->filesystem->remove($srcFullPath);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error deleting Folder");
            }
            $this->finalResponse(200, "Path " . $srcPathOnly . " was successfully deleted!");
        } elseif (is_file($srcFullPath)) {
            try {
                $this->filesystem->remove($srcFullPath);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error deleting file");
            }
            $this->finalResponse(200, "File " . $srcFilename . " was successfully deleted!");
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
        $this->checkContentType();
        $this->checkUserAccess("delete-file");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);
        $desiredKeys = array("path");

        $this->checkPathResponseData($object, $desiredKeys);

        $srcPathFirstCleaning = cleanInputPath($object['path']);

        if (hasPathEndsWithFile($srcPathFirstCleaning)) {
            $srcPathLessFile = removeFilenameFromPath($srcPathFirstCleaning);
            $srcPathOnly = filter_path($srcPathLessFile);
            //$srcFilename = substr(strrchr($srcPathFirstCleaning, "/"), 1);
            $srcFilename = basename($srcPathFirstCleaning);
            $srcFilename = filter_filename($srcFilename);
            $srcPathInput = $srcPathOnly . DIRECTORY_SEPARATOR . $srcFilename;
        } else {
            $srcPathOnly = filter_path($srcPathFirstCleaning);
            $srcPathInput = $srcPathOnly;
        }

        $srcFullPath = $this::$uploadFolder . DIRECTORY_SEPARATOR . $srcPathInput;

        if (!$this->filesystem->exists($srcFullPath)) {
            $this->finalResponse(400, "File/Folder " . $srcPathInput . " does Not Exist");
        }

        if (is_dir($srcFullPath)) {
            try {
                $this->filesystem->remove($srcFullPath);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error deleting Folder");
            }
            $this->finalResponse(200, "Path " . $srcPathOnly . " was successfully deleted!");
        } elseif (is_file($srcFullPath)) {
            try {
                $this->filesystem->remove($srcFullPath);
            } catch (IOExceptionInterface $exception) {
                ($exception);
                $this->finalResponse(400, "Error deleting file");
            }
            $this->finalResponse(200, "File " . $srcFilename . " was successfully deleted!");
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
        $this->checkContentType();
        $this->checkUserAccess("create-user");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);
        $desiredKeys = array("username", "permissions_string");

        $this->checkUserResponseData($object, $desiredKeys);

        $UserInput = cleanInputPath($object['username']);
        $UserInput = convertToLowerCase($UserInput);
        $PermInput = cleanInputPath($object['permissions_string']);

        if ($this->user->isRegistredUser($UserInput)) {
            $this->finalResponse(400, "Username Not Available");
        }
        $this->checkPermInput($object);

        $json_a = jsonToArray($this::$aclJSON);

        $output = array_merge($json_a, array($UserInput => $PermInput));
        file_put_contents($this::$aclJSON, json_encode($output, JSON_PRETTY_PRINT));

        $token_generated = $this->auth->generateToken($UserInput);

        $this->finalResponse(201, "User " . $UserInput . " with permissions " . $PermInput . " was added successfully", $token_generated);

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
        $UserInput = cleanInputPath($data);

        if (!$this->user->isRegistredUser($UserInput)) {
            $this->finalResponse(400, "Username Not Available");
        }

        $json_a = jsonToArray($this::$aclJSON);

        $perm_array = array();

        foreach ($json_a as $id => $value) {
            if ($id == convertToLowerCase($UserInput)) {
                $codes_arr = explode('-', $value);
                foreach ($codes_arr as $key => $val) {
                    foreach ($this->user::$permissions as $code => $permision_string) {
                        if ($val == $code) {
                            $perm_array[] = $permision_string;
                        }
                    }
                }
                $str = implode(", ", $perm_array);
                $UserInputPermlist = rtrim($str, ', ');
                $UserInputPermCout = count($perm_array);
                $this->finalResponse(200, "User " . $UserInput . " has the following " . $UserInputPermCout . " permissions : " . $UserInputPermlist);
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
        $this->checkContentType();
        $this->checkUserAccess("update-users-permissions");

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);
        $desiredKeys = array("username", "permissions_string");

        $this->checkUserResponseData($object, $desiredKeys);

        $UserInput = cleanInputPath($object['username']);
        $UserInput = convertToLowerCase($UserInput);
        $PermInput = cleanInputPath($object['permissions_string']);

        if (!$this->user->isRegistredUser($UserInput)) {
            $this->finalResponse(400, "Username Not Available");
        }

        $this->checkPermInput($object);

        $json_a = jsonToArray($this::$aclJSON);

        foreach ($json_a as $key => &$val) {
            if ($key == convertToLowerCase($UserInput)) {
                if ($val == $PermInput) {
                    $this->finalResponse(200, "Nothing to Update");
                } else {
                    $val = $PermInput;
                }
            }
        }

        file_put_contents($this::$aclJSON, json_encode($json_a, JSON_PRETTY_PRINT));

        $this->finalResponse(200, "User " . $UserInput . " was succefully updated with the following permissions " . $PermInput);

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
        $this->checkContentType();
        $this->checkUserAccess("delete-user");

        $input = file_get_contents('php://input');
        //parse_str(file_get_contents("php://input"), $input);
        $object = json_decode($input, true);
        $desiredKeys = array("username");

        $this->checkUserResponseData($object, $desiredKeys);

        $UserInput = cleanInputPath($object['username']);
        $UserInput = convertToLowerCase($UserInput);

        if (!$this->user->isRegistredUser($UserInput)) {
            $this->finalResponse(400, "Username Not Available");
        }

        $json_a = jsonToArray($this::$aclJSON);

        unset($json_a[$UserInput]);

        file_put_contents($this::$aclJSON, json_encode($json_a, JSON_PRETTY_PRINT));

        $this->finalResponse(200, "User " . $UserInput . " was succefully deleted!");

    }

    /**
     * checkUserResponseData.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, April 1st, 2019.
     * @access    private
     * @param    array    $object
     * @param    array    $desiredKeys
     * @return    void
     */
    private function checkUserResponseData(array $object, array $desiredKeys): void
    {

        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!isArrayOfKeysExists($desiredKeys, $object)) {
            $this->finalResponse(400, "Missing Property");
        }

    }

    /**
     * checkPathResponseData.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Sunday, March 31st, 2019.
     * @access    private
     * @param    array      $object
     * @param    boolean    $checkPath      Default: false
     * @param    boolean    $checkSrcDst    Default: false
     * @param    boolean    $checkOldNew    Default: false
     * @return    void
     */
    private function checkPathResponseData(array $object, array $desiredKeys): void
    {
        if (!isset($object)) {
            $this->finalResponse(415, "no data");
        }

        if (!isArrayOfKeysExists($desiredKeys, $object)) {
            $this->finalResponse(400, "Missing Property");
        }

    }

    /**
     * checkContentType.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Sunday, March 31st, 2019.
     * @access    private
     * @param    string     $contenType    Default: "application/json"
     * @param    bool    $upload        Default: false
     * @return    void
     */
    private function checkContentType(string $contenType = "application/json", bool $upload = false)
    {

        if (!isset(getAllHeaders()["Content-Type"])) {
            $this->finalResponse(401, "Content-Type Missing");

        } else {

            $desiredHeader = getAllHeaders()["Content-Type"];

            if ($upload) {
                $data = explode(";", $desiredHeader);
                if (strcasecmp($data[0], $contenType) !== 0) {
                    $this->finalResponse(415, "Only form-data Allowed for Upload");
                }
            } else {
                if ($desiredHeader !== $contenType) {
                    $this->finalResponse(401, "Only JSON33 Allowed");
                }

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
                    if ($val != $data && $val != 'xx') {
                        $this->finalResponse(400, "Permissions Not Accurate");
                    }
                }
            }
        }
    }

}
