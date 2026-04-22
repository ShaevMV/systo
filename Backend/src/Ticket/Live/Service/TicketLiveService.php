<?php

declare(strict_types=1);

namespace Tickets\Ticket\Live\Service;

class TicketLiveService
{
    private static string $cipher = 'aes-256-gcm';

    // Шифрование для URL
    public static function encrypt(int $kilter): string {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $tag = '';
        if(!$key =  env('KEY_LIVE_TICKET')) {
            throw new \DomainException('Not found key');
        }
        $encrypted = openssl_encrypt(
            self::addZero($kilter),
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        // Объединяем IV, tag и зашифрованные данные
        $combined = $iv . $tag . $encrypted;

        // Преобразуем в base64 и делаем URL-безопасным
        $base64 = base64_encode($combined);
        return self::base64UrlEncode($base64);
    }

    // Дешифрование из URL
    public static function decrypt(string $data): string {
        // Преобразуем обратно из URL-безопасного base64
        $base64 = self::base64UrlDecode($data);
        $combined = base64_decode($base64);

        $ivLength = openssl_cipher_iv_length(self::$cipher);

        $iv = substr($combined, 0, $ivLength);
        $tag = substr($combined, $ivLength, 16);
        $encrypted = substr($combined, $ivLength + 16);

        if(!$key =  env('KEY_LIVE_TICKET')) {
            throw new \DomainException('Not found key');
        }

        return openssl_decrypt(
            $encrypted,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }

    // Преобразование в URL-безопасный base64
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr($data, '+/', '-_'), '=');
    }

    // Преобразование из URL-безопасного base64
    private static function base64UrlDecode(string $data): string {
        return str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT);
    }


    public static function addZero(int $number): string
    {
        $zero = '';

        if ($number < 1000) {
            $zero.='0';
        }
        if ($number < 100) {
            $zero.='0';
        }
        if ($number < 10) {
            $zero.='0';
        }

        return $zero.$number;
    }
}
