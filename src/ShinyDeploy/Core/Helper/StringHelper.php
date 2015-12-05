<?php namespace ShinyDeploy\Core\Helper;

class StringHelper
{
     /**
     * Genrates a random string of given length.
     *
     * @param int $length
     * @return string
     */
     public static function getRandomString($length = 6)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }
}
