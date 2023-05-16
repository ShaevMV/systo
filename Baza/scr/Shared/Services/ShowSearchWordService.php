<?php

namespace Baza\Shared\Services;

class ShowSearchWordService
{
    public static function insertTag(?string $str = null, ?string $q = null): ?string
    {
        if(is_null($q) || is_null($str)) {
            return $str;
        }

        return str_replace($q, "<b>$q</b>", $str);
    }
}
