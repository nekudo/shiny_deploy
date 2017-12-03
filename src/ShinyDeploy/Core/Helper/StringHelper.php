<?php namespace ShinyDeploy\Core\Helper;

class StringHelper
{
     /**
     * Generates a random string of given length.
     *
     * @param int $length
     * @return string
     */
    public static function getRandomString(int $length = 6) : string
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }
}
