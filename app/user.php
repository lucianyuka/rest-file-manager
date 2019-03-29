<?php
declare (strict_types = 1);

namespace App;

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

    private static $aclJSON;

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

        $this::$aclJSON = __DIR__ . DIRECTORY_SEPARATOR . getenv('JSON_FILE');

    }

    /**
     * createUsersACL.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, March 18th, 2019.
     * @access    private
     * @return    array
     */
    private function createUsersACL(): array
    {

        $json_a = jsonToArray($this::$aclJSON);

        foreach ($json_a as $key => $val) {
            $arr[] = array($key => $val);
        }

        for ($i = 0; $i < count($arr); ++$i) {
            reset($arr[$i]);
            $indice = key($arr[$i]);
            $value = $arr[$i][$indice];
            $acl_a[$i]["username"] = $indice;
            $acl_a[$i]["permissions-code"] = $value;
            $codes_arr = explode('-', $value);
            foreach ($codes_arr as $key => $val) {
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
     * isRegistredUser.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @param    string    $user
     * @return    bool
     */
    public function isRegistredUser(string $user): bool
    {
        $array = $this->createUsersACL();

        if (array_search(strtolower($user), array_column($array, 'username')) !== false) {
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
     * @version    v1.0.0    Friday, March 29th, 2019.
     * @access    public
     * @param    string    $user
     * @param    string    $perm
     * @return    bool
     */
    public function hasThePerm(string $user, string $perm): bool
    {
        $array = $this->createUsersACL();
        foreach ($array as $userperm) {
            if ($userperm['username'] != strtolower($user)) {
                continue;
            } else {
                if ($userperm['username'] == strtolower($user) and in_array_r($perm, $userperm['permissions'])) {
                    return true;
                } else {
                    return false;
                }

            }

        }

    }

}
