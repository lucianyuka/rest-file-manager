<?php

if (!function_exists('getAllHeaders')) {
    /**
     * getAllHeaders.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @return    array
     */
    function getAllHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(convertToLowerCase(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        if (function_exists('apache_request_headers')) {
            return array_merge($headers, apache_request_headers());
        }
        return $headers;
    }
}

if (!function_exists('jsonToArray')) {

    /**
     * jsonToArray.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @param    string    $file
     * @return    mixed
     */
    function jsonToArray(string $file): array
    {

        $jsonFile = file_get_contents($file);
        $json_a = json_decode($jsonFile, true);
        return $json_a;
    }

}

if (!function_exists('in_array_r')) {
    /**
     * in_array_r.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @param    string     $needle
     * @param    array      $haystack
     * @param    boolean    $strict      Default: false
     * @return    boolean
     */
    function in_array_r(string $needle, array $haystack, bool $strict = false): bool
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
                return true;
            }
        }
        return false;
    }

}

if (!function_exists('filter_path')) {
    /**
     * filter_path.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @param    string    $path
     * @return    array
     */
    function filter_path(string $path): array
    {
        $pattern = '~
        [<>:"|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&%\'()+,;=.]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x';
        $path = preg_replace($pattern, '', $path);
        $path = stripslashes($path);
        // $path = trim($path, '/'); // remove space and backslashes from begining and ending

        $path_a = explode('/', $path);
        $path_a = array_map(function ($val) {return preg_replace('/\s+/', '', $val);}, $path_a);

        function remove_Single_Underscores_Single_Hyphens($str)
        {
            /* $aValid = array('-', '_');
            if (!ctype_alnum(str_replace($aValid, '', $str))) {
            $str = str_replace(array('-', '_'), '', $str);
            } */
            $keywords = preg_split("/[\s-]+/", $str);
            $filterd = array_filter($keywords, function ($value) {return $value !== '';});
            $str = implode("-", $filterd);

            $keywords = preg_split("/[\s_]+/", $str);
            $filterd = array_filter($keywords, function ($value) {return $value !== '';});
            $str = implode("-", $filterd);

            $str = trim($str, '-');

            return $str;
        }

        $path_a = array_map("remove_Single_Underscores_Single_Hyphens", $path_a);

        $path_a = array_filter($path_a, function ($value) {return $value !== '';});
        dd(implode("/", $path_a));

        //$output = array_map(function($val) { return preg_replace('/\s+/', ' ', $val); }, $path_a);
        /*  foreach ($path_a as $key => $val) {
        while(substr($val, 0, 1) === "-" or substr($val, 0, 1) === "_") {
        $val = trim($val,'-');
        $val = trim($val,'_');
        }
        } */

        //$path = preg_replace('/\s*\/\s*/', '/',$path); // remove space before and after evry backslashes within
        //$path = preg_replace('/\/\//', '/',$path); // remove space before and after evry backslashes within
        //$path = preg_replace('/\s*-\s*/', '-',$path); // remove space before and after evry backslashes within

        /*  while(substr($path, 0, 1) === "-" or substr($path, 0, 1) === "_") {
        $path = trim($path,'-');
        $path = trim($path,'_');
        } */

        // $path = trim($path,'/'); // remove space and backslashes from begining and ending

        return $path_a;
    }

}

if (!function_exists('filter_filename')) {
    /**
     * filter_filename.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @param    string     $filename
     * @param    boolean    $beautify    Default: true
     * @return    string
     */
    function filter_filename(string $filename, bool $beautify = true): string
    {
        // sanitize filename
        $filename = htmlspecialchars($filename); // best to be carefull
        $filename = preg_replace(
            '~
            [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
            [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
            [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
            [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
            [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
            ~x',
            '-', $filename);
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // optional beautification
        if ($beautify) {
            $filename = beautify_filename($filename);
        }
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
        return $filename;
    }
}

if (!function_exists('beautify_filename')) {
    /**
     * beautify_filename.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @param    string    $filename
     * @return    string
     */
    function beautify_filename(string $filename): string
    {
        // reduce consecutive characters
        $filename = preg_replace(array(
            // "file   name.zip" becomes "file-name.zip"
            '/ +/',
            // "file___name.zip" becomes "file-name.zip"
            '/_+/',
            // "file---name.zip" becomes "file-name.zip"
            '/-+/',
        ), '-', $filename);
        $filename = preg_replace(array(
            // "file--.--.-.--name.zip" becomes "file.name.zip"
            '/-*\.-*/',
            // "file...name..zip" becomes "file.name.zip"
            '/\.{2,}/',
        ), '.', $filename);
        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, '.-');
        return $filename;
    }
}

if (!function_exists('convertToLowerCase')) {
    /**
     * convertToLowerCase.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @param    string    $str
     * @return    string
     */
    function convertToLowerCase(string $str): string
    {
        // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        $str = mb_strtolower($str, mb_detect_encoding($str));
        return $str;
    }

}

if (!function_exists('delete')) {
    /**
     * Delete the file at a given path.
     *
     * @param  string|array  $paths
     * @return bool
     */
    function delete(string $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();
        $success = true;
        foreach ($paths as $path) {if (!@unlink($path)) {
            $success = false;
        }
        }
        return $success;
    }

}

if (!function_exists('deleteDirectory')) {
    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string  $directory
     * @param  bool    $preserve
     * @return bool
     */
    function deleteDirectory($directory, $preserve = false)
    {
        if (!is_dir($directory)) {
            return false;
        }

        $items = new \FilesystemIterator($directory);
        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir()) {
                deleteDirectory($item->getPathname());
            }
            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                delete($item->getPathname());
            }
        }
        /*  if (!$preserve) {
        @rmdir($directory);
        } */

        return true;
    }

}

if (!function_exists('getFilePerms')) {

    /**
     * getFilePerms.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @param    string    $file
     * @return    mixed
     */
    function getFilePerms(string $file): string
    {
        $perms = fileperms($file);

        switch ($perms & 0xF000) {
            case 0xC000: // socket
                $info = 's';
                break;
            case 0xA000: // symbolic link
                $info = 'l';
                break;
            case 0x8000: // regular
                $info = 'r';
                break;
            case 0x6000: // block special
                $info = 'b';
                break;
            case 0x4000: // directory
                $info = 'd';
                break;
            case 0x2000: // character special
                $info = 'c';
                break;
            case 0x1000: // FIFO pipe
                $info = 'p';
                break;
            default: // unknown
                $info = 'u';
        }

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x') :
            (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x') :
            (($perms & 0x0400) ? 'S' : '-'));

        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x') :
            (($perms & 0x0200) ? 'T' : '-'));

        return $info;

    }
}
if (!function_exists('FileSizeConvert')) {
    /**
     * Converts bytes into human readable file size.
     *
     * @param string $file
     * @return string human readable file size (2,87 Мб)
     * @author Mogilev Arseny
     */
    function FileSizeConvert(string $file): string
    {
        $bytes = filesize($file);
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4),
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3),
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2),
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024,
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1,
            ),
        );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
                break;
            }
        }

        return $result;
    }

}
