<?php

include 'fdbtypes.php';
include 'contextset.class.php';

class FDBTable
{
	// @var object Database
	private $parent = null;
	
	// @var string - name of this instance
	private $className = null;
	
	// @var string - path to the file
    private $file = null;
	
	// @var string
	private $filename = null;

	// @var object - 
    private $fields = null;
	
	// @var int - last inserted register 
    private $last_insert_id = null;

	// @var int - total rows in the table
    private $count = null;
	
    public function __construct( $_properties = null, $parent = null )
    {
		// database Object
		$this->parent = $parent;
        $this->file = $_properties['file'] ;
        $this->filename = $_properties['filename'] ;
        $this->last_insert_id =$_properties['last_insert_id'];
        $this->fields = json_decode(json_encode($_properties['fields']));
    }
    
	/**
	 * Set this classe Name, Instance Name at system
	 **/
	public function setName( $val ){
		$this->className = $val;
	}
    

	private function validate( $val, $props, $field )
	{
		$val = is_string($val) ? trim($val) : $val;
		
		// checking for auto_increment field...
		if(isset($props->auto_increment) && FDBUtils::isTrue($props->auto_increment)):
			if(FDBUtils::isNull($val))
				return $this->last_insert_id;
		endif;

		// checking if data can be assigned as null value...
		if($val === NULL):
			if( @$props->null == 0 ){	
				// verifying for default value ...
				if(isset($props->default)):
					$val = $props->default;
				else:
					FDBUtils::show_error( "Table::validate() : The '$field' field expects a value of type '{$props->type}' and can't be NULL." );
				endif;
			}
			else
				return $val;
		endif;

		// checking data type ...
		if(isset($props->type)):
			$method = 'as_'.$props->type ;
			if(method_exists( 'FDBTypes', $method )):
				$val = FDBTypes::$method( $val );
			else:
				FDBUtils::show_error( 'Table::validate() : The <b>"'.$props->type.'"</b> type was not declared on the file "types.php".' );
			endif;
		endif;
		
		// checking max lenght ...
		if( isset($props->size) && (strlen($val)>$props->size) ){
			$val = substr($val,0,$props->size);
		}
		
		return $val;
	}
	
	private function checkData( $_vals = array(), $__check_null = true ){
		$_nvals = array();
		foreach($this->fields as $k=>$props):
			if(array_key_exists($k, $_vals)):
				$_nvals[$k] = $this->validate( $_vals[$k], $props, $k );
			else:
				if( isset($props->null) && (!FDBUtils::isTrue($props->null)) )
					FDBUtils::show_error( 'Table::checkData() : "'.$k .'" is required.' );
			endif;
		endforeach;

		$diff = array_diff_key($_vals,$_nvals);
		if(count($diff)>0):
			$__err = "";
			foreach($diff as $k=>$v):
				$__err .= '"'.$k.'" was not recognized as a field name from "'.$this->className.'" table.' . PHP_EOL;
			endforeach;
			FDBUtils::show_error( $__err );
		endif;
		
		return $_nvals;
	}
	
    /**
     * turns a string into an array
     * the text must be in the format: key1=value1; key2=value2;...$keyN=valueN
     * 
     * @param string $args - string to be converted
     * @return array
     **/
    private function parseArgs( $args = null )
	{
        if( !is_array($args)):
            $_arr_ = explode( ';', $args );
            foreach($_arr_ as $index=>$str):
                $temp = explode("=",$str);
                $new_args[trim($temp[0])] = trim($temp[1]);
            endforeach;
            return $new_args;
        endif;
        return $args;
    }
	
	/**
	 * insert data to the end of file and update database context
	 * 
	 * @param string $__str
	 * @return bool
	 **/
	private function append( $__str = "")
	{
		file_put_contents( $this->file, $__str.PHP_EOL , FILE_APPEND|LOCK_EX );
		
		// update context 
		$this->parent->update_dbtree(
			$this->className,
			array(
				'file' => $this->filename,
				'fields' => $this->fields,
				'last_insert_id' => $this->last_insert_id
			)
		);
		
		return true;
	}
	
	/**
	 * Appends data to table
	 * 
	 * input example :
	 *  $_values_ = array( 'id'=>1, 'name'=>'John Doe', 'email' => 'johndoe@mail.com') 
	 *  or
	 *  $_value_  = 'id=1; name=John Doe; email=johndoe@mail.com';
	 * 
	 * @param  string|array $_values_ 
	 * @return int - returns the auto Inserted ID
	 */
	public function insert( $_values_ = null /*string|array*/ )
	{
        if(!is_array($_values_)){
            $_values_ = $this->parseArgs( $_values_ );
			if(!is_array($_values_)):
				FDBUtils::show_error( 'Table->insert : Data given isn\'t array type.' );
				return false;
			endif;
        }
		
		$this->last_insert_id++;
		
		// check required fields ...
		$_row = array();
		foreach($this->fields as $__k => $v) $_row[$__k] = null;
		$_values_ = array_merge($_row,$_values_);

		// checking received data...
		$_values_ = $this->checkData( $_values_ );
		
		$__str__ = $this->last_insert_id . "." . FDBUtils::encode( $_values_ );
		
		$this->append( $__str__ );

		// update total rows
		if(!is_null($this->count)) $this->count++;
		
		return $this->last_insert_id;
	}
	
	/**
	 * @todo  - precisa alterar para permitir atualizar N registros ao mesmo tempo
	 * 
	 * @param  [type] $_values [description]
	 * @param  [type] $__where [description]
	 * @return [type]          [description]
	 */
	public function update( $_values, $__where )
	{
		if(!isset($__where)) return false;
		
		if(strpos($__where,"=")!==false)
			list( $__key, $__id ) = explode( "=" , $__where );
		else return false;
		
		$_row = $this->select()->where($__where)->asArray( true );

		if(!isset($_row) || !is_array($_row) || empty($_row)) return false;

		$_new_row = array_merge( $_row, $_values );
		$_new_row = $this->checkData( $_new_row );
		$__str = trim($__id) . "." . FDBUtils::encode( $_new_row ) . PHP_EOL;
		
		$handle = fopen( $this->file, "r");
		$__result = "";
		if ($handle):
			while (($__line = fgets($handle)) !== false){
				$__pos = strpos($__line,".");
				$__pos++;
				$__line_id = (int)trim(substr($__line, 0,$__pos));
				if($__line_id == $__id)
					$__result .= $__str;
				else
					$__result .= $__line;
			}
			fclose($handle);
		else:
			FDBUtils::show_error( " Table->update : Can\'t open table. Failed to update row." );
		endif;
		
		file_put_contents( $this->file, $__result );
		return true;
	}
	
	
	public function select( $param = null /* context would be nice but Array is fine too! */ )
	{
		if( !(is_array($param) && isset($param['initialized']) )){
			$temp = new ContextSet( array( 'callback' => $this, 'initialized' => true ) );
			$temp->select( $param );
			return $temp;
		}
		
		// turns array into object ... 
		$context = FDBUtils::ato( $param );

		$_result = array();
		$handle = fopen( $this->file, "r");
		if ($handle):
			while (($__line = fgets($handle)) !== false){
				$__pos = strpos($__line,".");
				$__pos++;
				$__string = rtrim(substr($__line,$__pos,strlen($__line)),PHP_EOL);
				$_arr = FDBUtils::decode( $__string ) ;
				
				if(isset($context->where)):
					$__condition = null;
					if(strpos($context->where, " = " )!==false) $__condition =  " = ";
					if(strpos($context->where, " > " )!==false) $__condition =  " > ";
					if(strpos($context->where, " < " )!==false) $__condition =  " < ";
					if(strpos($context->where," >= " )!==false) $__condition = " >= ";
					if(strpos($context->where," <= " )!==false) $__condition = " <= ";
					if(strpos($context->where," != " )!==false) $__condition = " != ";
					if(strpos($context->where," >< " )!==false) $__condition = " >< ";
					if(strpos($context->where," between " )!==false) $__condition = " between ";
					if(strpos($context->where," in " )!==false) $__condition = " in ";
					
					if(is_null($__condition))
						FDBUtils::show_error( 'Table->select() : unrecognized condition "'.$__condition.'"' );
					
					list($__field, $__compare) = explode( $__condition, $context->where );
					$__field = trim($__field);
					$__compare = trim($__compare);
					$__condition = trim($__condition);

					switch($__condition){
						case '=' : $r = ( strtolower($_arr[$__field]) == strtolower($__compare)); break;
						case '>' : $r = ( $_arr[$__field] >  $__compare); break;
						case '<' : $r = ( $_arr[$__field] <  $__compare); break;
						case '>=': $r = ( $_arr[$__field] >= $__compare); break;
						case '<=': $r = ( $_arr[$__field] <= $__compare); break;
						case '!=': $r = ( $_arr[$__field] != $__compare); break;
						
						case '><':
						case 'between':
							list($v1,$v2) = explode(",",$__compare);
							$r = false;
							if(isset($_arr[$__field]))
								$r = ($_arr[$__field] >= $v1 && $_arr[$__field] <= $v2);
						break;

						case 'in':
							$_arr_tmp = explode(",",$__compare);
							$r = (in_array($_arr[$__field],$_arr_tmp));
						break;
						
						default: break;
					}

					if( !$r ) continue;
				endif;
				
				if(isset($context->fields) && is_array($context->fields))
					$_arr = FDBUtils::array_keep_by_keys( $_arr, $context->fields );
				
				$_ret = ($context->return_type == 'asArray') ? $_arr : FDBUtils::ato($_arr);
				if(!$context->firstOnly):
					$_result[] = $_ret;
				else:
					$_result = $_ret;
					break;
				endif;
				
			}
			fclose($handle);
		else:
			FDBUtils::show_error( "Can\'t open file." );
		endif; 
		return $_result;
	}
	
	/**
	 * Remove a row from the table
	 *
	 * @param int param - the index of the row to remove
	 * @return int - the number of deleted rows
	 */
	public function delete( $__remove = null )
	{
		$__result = "";
		$__deleted_rows = 0;

		if($__remove == null ){
			$this->parent->setError( '[NOTICE]FDBtable::delete()-> No ID passed as parameters.' );
			return $__deleted_rows;
		}

		$handle = fopen( $this->file, "r");
		if ($handle):
			while (($__line = fgets($handle)) !== false){
				$__pos = strpos($__line,".");
				$__pos++;
				//$__string = rtrim(substr($__line,$__pos,strlen($__line)),PHP_EOL);
				$__id = (int)trim(substr($__line, 0,$__pos));

				if($__id != $__remove):
					$__result .= $__line ;
				else:
					$__deleted_rows++;
				endif;
			}
			fclose($handle);
		else:
			$this->parent->setError( '[NOTICE]FDBtable::delete()-> Can\'t open table to remove row.' );
		endif;
		
		file_put_contents( $this->file, $__result );
		
		// update total rows - count / decrement
		if(!is_null($this->count)&&($this->count>0)) $this->count--;
		
		return $__deleted_rows;
	}
	
	public function count( $__force_count_from_file = false )
	{
		if($__force_count_from_file){
			$__count = 0;
			$handle = fopen( $this->file, 'r');
			if($handle)
				while(($__line=fgets($handle))!==false)
					$__count++;
			$this->count = $__count;
			fclose($handle);
			return $this->count;
		}
		else{
			return (is_null($this->count)) ? self::count( true ) : $this->count;
		}
	}
	
	
}


    



    