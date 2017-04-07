<?php 

class FDBTypes
{
    public static function as_int($val){return self::as_integer($val);}
    public static function as_integer( $val = null ){
        $tmp = $val;
        $val = FDBUtils::numbersOnly($val);
        if( !FDBUtils::isInt($val))
            FDBUtils::show_error( "Types->as_integer() : '$tmp' is not a integer value." );
        return (int)$val;
    }
    
    public static function as_string( $val = null ){return $val;}
    public static function as_stringOnly( $val = null ){return $val;}
    public static function as_stringOnlyNoSpace( $val = null )
    {return FDBUtils::textOnlyNoSpaces( $val );}
    
    public static function as_bool($val){return self::as_boolean($val);}
    public static function as_boolean( $val = null ){
        return FDBUtils::isTrue( $val );
    }

    public static function as_email( $val = null )
    {
        $email = FDBUtils::textOnlyNoSpaces($val);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false):
          return $email;
        else:
          FDBUtils::show_error( "'$val' is not a valid E-mail type." );
        endif;
    }
}