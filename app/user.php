<?php
declare (strict_types = 1);

namespace App;

use Dotenv\Dotenv;

/**
 * User.
 *
 * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
 * @since    v0.0.1
 * @version    v1.0.0    Monday, March 18th, 2019.
 * @global
 */
class User
{

    /**
     * @var        static    $aclJSON
     */
    private static $aclJSON;

    /**
     * @var        static    $permissions
     */
    private static $permissions = array(
        "cf" => "create-file",
        "rf" => "read-file",
        "uf" => "update-file",
        "df" => "delete-file",
        "cu" => "create-user",
        "ru" => "read-user",
        "uu" => "update-users-permissions",
        "du" => "delete-user",
    );
    /**
     * __construct.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, March 18th, 2019.
     * @access    public
     * @return    void
     */
    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__));
        $dotenv->load();
        $this::$aclJSON = dirname(__DIR__) . $_ENV['JSON_PATH'];

    }

    /**
     * Json2Array.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, March 18th, 2019.
     * @access    private
     * @return    array
     */
    private function Json2Array()
    {
        $aclJSON = $this::$aclJSON;

        $jsonFile = file_get_contents($aclJSON);
        $json_a = json_decode($jsonFile, true);

        foreach ($json_a as $key => $val) {
            $arr[] = array($key => $val);
        }

        for ($i = 0; $i < count($arr); ++$i) {
            reset($arr[$i]);
            $indice = key($arr[$i]);
            $value = $arr[$i][$indice];
            $acl_a[$i]["username"] = $indice;
            $acl_a[$i]["permissions-code"] = $value;
            $codes_a = explode('-', $value);
            foreach ($codes_a as $key => $val) {
                foreach ($this::$permissions as $code => $permision_string) {
                    if ($val == $code) {
                        $acl_a[$i]["permissions"][] = array($key => $permision_string);
                    }
                }
            }
        }
        return $acl_a;
    }

    /**
     * is_permitted_user.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, March 18th, 2019.
     * @access    public
     * @param    mixed    $user
     * @return    boolean
     */
    public function isRegistredUser($user)
    {
        $array = $this->Json2Array();
        if (array_search($user, array_column($array, 'username')) !== false) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * hasThePerm.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, March 18th, 2019.
     * @access    public
     * @param    mixed    $user
     * @param    mixed    $perm
     * @return    boolean
     */
    public function hasThePerm($user, $perm)
    {
        $array = $this->Json2Array();
        foreach ($array as $userperm) {
            if ($userperm['username'] != $user) {
                continue;
            } else {
                if ($userperm['username'] == $user and $this->in_array_r($perm, $userperm['permissions'])) {
                    return true;
                } else {
                    return false;
                }

            }

        }

    }

    private function in_array_r($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }
        return false;
    }

}
