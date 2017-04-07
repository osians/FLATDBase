<?php

require_once 'fdbcripto.class.php' ;

class FDBUtils
{
	/**
	 * Make exclusive database safe names
	 * @param  string $value
	 * @return string
	 */
    public static function make_safe( $value = null, $__maxlenght = 0 )
    {
        $value = trim( $value );
        $value = strip_tags( $value );
        $value = self::mres( $value );
        $value = self::remove_spaces( $value );
        $value = self::replace_accents( $value );
        $value = self::remove_numbers( $value );
		$value = self::azOnly( $value );
		$value = strtolower($value);
		if($__maxlenght > 0)
			return (strlen($value) > $__maxlenght)?substr($value,0,$__maxlenght):$value;
        return $value;
    }

    public static function replace_accents( $__param__ )
    {
        return strtr(utf8_decode($__param__),
            utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
            'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
    }

    public static function mres($value)
    {
        $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");
        return str_replace($search, $replace, $value);
    }

    public static function textOnly( $value ){
        $value = trim( $value );
        $value = strip_tags( $value );
        $value = self::mres( $value );
        return $value;
    }

    public static function textOnlyNoSpaces($value){
        $value = self::textOnly( $value );
        $value = self::remove_spaces( $value );
        return $value;
    }

    static function remove_spaces( $param ){
        return str_replace(' ','',$param );
    }

	static function numbersOnly( $param ){
        return preg_replace( "/[^0-9]/","",trim($param) );
    }

	/**
	 * Removes special chars
	 **/
	static function azOnly( $__str ){
		return preg_replace('/[^A-Za-z0-9\-\_]/', '', $__str);
	}

    static function remove_numbers( $param ){return preg_replace('/\d/', '', $param );}

	public static function encode($_value_ = array()){
		$__json__ = json_encode( $_value_ );
		$__rc__ =  FDBCripto::cripto( $__json__ );
		return bin2hex( $__rc__ );
	}

	public static function decode( $_value_ /*string*/ ){
		$__cripted__ = hex2bin( $_value_ );
		return json_decode( FDBCripto::decripto( $__cripted__ ) , true );
	}

	public static function show_error( $__message = null, $__exit = true )
	{
		$__style = "
			border:1px solid #A65657;
			border-radius:3px;
			color:white;
			padding:10px;
			font-family:Courier,Arial,sans-serif;
			background-color:#BE4B49;
		";

		$__err = "<div style='$__style'>$__message</div>";
		print $__err;
		if($__exit) exit(1);
	}

	public static function isInt($s){
		return filter_var($s, FILTER_VALIDATE_INT) !== false;
	}

	public static function isNum($s){
		return eint($s);
	}

	private static function boolean_check($v){
		if(is_bool($v)) return $v;
		if(self::isInt($v)) return ($v > 0);

		$v = strtolower(trim($v));
		switch($v){
			case '1': return true; break;
			case 'true': return true; break;
			case 'yes': return true; break;
			case 'ok': return true; break;
			case 'truth': return true; break;
			default:
				return false;
			break;
		}
	}

	public static function isTrue($v)
	{return self::boolean_check($v);}

	public static function isNull($val)
	{return (empty($val) || $val === NULL ||$val=='null'||$val=='NULL')?true:false;}

	public static function array_keep_by_keys($_arr1, $_arr2)
	{
		// array keep by keys ...
		foreach($_arr1 as $__k=>$__v)
		   if(!in_array($__k,$_arr2))
			   unset($_arr1[$__k]);
		return $_arr1;
	}

	/*
	 * Array to Object conversion
	 * @param array
	 * @return object
	 */
	public static function ato($_array_)
	{return json_decode(json_encode($_array_),FALSE);}

	public static function deleteDirectory($dir)
	{
    	if (!file_exists($dir)) return true;

    	if (!is_dir($dir)) return unlink($dir);

	    foreach (scandir($dir) as $item):
	        if ($item == '.' || $item == '..') continue;
	        if (!self::deleteDirectory( $dir.DIRECTORY_SEPARATOR.$item) ) return false;
	    endforeach;

    	return rmdir($dir);
	}

}