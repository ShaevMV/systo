<?php

namespace Baza\Shared\Services;

class ShowSearchWordService
{
    public static function insertTag(string $str, ?string $q = null): string
    {
        if(is_null($q)) {
            return $str;
        }

        return str_replace($q, "<b>$q</b>", $str);
    }
}
