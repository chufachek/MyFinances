<?php

namespace App\Services;

class Request
{
    public static function json()
    {
        $input = file_get_contents('php://input');
        if (!$input) {
            return [];
        }
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }

    public static function data()
    {
        $data = $_POST;
        if (empty($data)) {
            $data = self::json();
        }
        return $data;
    }
}
