<?php

echo 'PHP_BINARY: ';
var_dump(PHP_BINARY);

echo 'PHP_VERSION: ';
var_dump(PHP_VERSION);

echo 'PHP_SAPI: ';
var_dump(PHP_SAPI);

echo 'Loaded php.ini: ';
var_dump(php_ini_loaded_file());

echo 'OpenSSL extension: ';
var_dump(extension_loaded('openssl'));

echo 'Function: ';
var_dump(function_exists('openssl_cipher_iv_length'));