<?php 

function getCookies($headers)
{
    $cookies = [];
    try {
        $cookie = $headers['Set-Cookie'];
        foreach ($cookie as $c) {
            $c = explode(';', $c);
            $cookies[] = $c[0];
            //$c=explode('=',$c[0]);
            //$cookies[$c[0]]=$c[1];
        }
        //while sending back cookie use headers named Cookie
    } catch (\Throwable $th) {
    }
    return implode('; ',   $cookies);
}