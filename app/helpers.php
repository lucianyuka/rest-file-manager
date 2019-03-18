<?php

if (!function_exists('show_route')) {

    /**
     * in_array_r.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, March 18th, 2019.
     * @param    mixed      $needle
     * @param    mixed      $haystack
     * @param    boolean    $strict      Default: false
     * @return    boolean
     */
    function in_array_r($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }
        return false;
    }

}

if (!function_exists('show_route')) {

    /**
     * key_value_pair_exists.
     *
     * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
     * @since    v0.0.1
     * @version    v1.0.0    Monday, March 18th, 2019.
     * @param    array    $haystack
     * @param    mixed    $key
     * @param    mixed    $value
     * @return    boolean
     */
    function key_value_pair_exists(array $haystack, $key, $value)
    {
        return array_key_exists($key, $haystack) && $haystack[$key] == $value;
    }
}
