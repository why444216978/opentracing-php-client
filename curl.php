<?php
if (!function_exists('getCurlCh')) {
    function getCurlCh(&$url, $data = array(), $header = array(), $method = 'POST', $timeout = 2, $others = array(), $ch = null)
    {
        if (empty($ch)) {
            $ch = curl_init();
        }
 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        /**
         * 设置post信息
         */
        if ('POST' === strtoupper($method)) {
            if (is_array($data)) {
                curl_setopt($ch, CURLOPT_POST, count($data));
            } else {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if ('DELETE' === strtoupper($method)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        /**
         * 设置超时时间
         */
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, intval($timeout * 1000));
        /**
         * 设置header参数
         */
        if (!empty($header) && is_array($header)) {
            $tmp = array();
            foreach ($header as $key => $value) {
                $tmp[] = trim($key) . ': ' . trim($value);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $tmp);
        }

        /**
         * 设置其它信息
         */
        if (is_array($others) && !empty($others)) {
            foreach ($others as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }
        return $ch;
    }
}

if (!function_exists('curlSend')) {
    function curlSend($url, $data = array(), $header = array(), $method = 'POST', $timeout = 5, $others = array(), $error = true)
    {
        $ch    = getCurlCh($url, $data, $header, $method, $timeout, $others);
        $ret   = curl_exec($ch);
        $errno = curl_errno($ch);
        $res   = curl_getinfo($ch);
        curl_close($ch);
        $result  = array('ret' => $ret, 'errno' => $errno, 'res' => $res);
        return $result;
    }
}