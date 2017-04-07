<?php

abstract class cripto_tipos
{
	const criptografar = 1;
	const descriptografar = 2;
}


class FDBCripto
{
	protected function __construct(){}
	public function __destruct(){}
	public function __clone(){}

	private static function encrypt_decrypt(
		$string,
		$action = cripto_tipos::criptografar,
		$secret_key = flatdbase_config::encryption_key,
		$secret_iv = flatdbase_config::encryption_iv,
		$encrypt_method = flatdbase_config::encrypt_method
	){
	    $output = false;

	    // hash
	    $key = hash('sha256', $secret_key);

	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);

	    if( $action == cripto_tipos::criptografar ) {
	        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	        $output = base64_encode($output);
	    }
	    else if( $action == cripto_tipos::descriptografar ){
	        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	    }

	    return $output;
	}

	static function cripto($string, $key = flatdbase_config::encryption_key )
	{return self::encrypt_decrypt($string,cripto_tipos::criptografar,$key);}

	static function decripto($string, $key = flatdbase_config::encryption_key )
	{return self::encrypt_decrypt($string,cripto_tipos::descriptografar,$key);}
}

