<?php

namespace Utils\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {

    public static function ObtenerSemilla($headers)
    {

        if (isset($headers["Authorization"])) {
            $token = trim(str_replace("Bearer ", "", $headers["Authorization"]));
            $decode = JWT::decode($token, new Key("18dddd6d-bef4-44fe-9b92-f67030332b3f", "HS256"));

            return $decode->seed;
        }

        return "";
    }
}