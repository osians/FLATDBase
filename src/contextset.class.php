<?php

class ContextSet
{
	private $context = array();
	
	function __construct($_context = null ){
		$this->context = $_context;
	}

	function select($__fields = null)
    {
        if( !is_null($__fields) && $__fields!='*' ):
            $_tmp = explode( ",", Utils::remove_spaces($__fields) );
            if(is_array($_tmp))
                $this->context['fields'] = $_tmp;
        endif;
		return new ContextSet( $this->context ); 
	}

	function from( $__tabela ){
		$this->context['table'] = $__tabela; 
		return new ContextSet( $this->context ); 
	}

	function where( $__where = null ){
		if($__where !== null)
			$this->context['where'] = $__where;
        return new ContextSet( $this->context );
	}

	private function result( $__return_type = 'asArray', $firstOnly = false )
    {
        $_context = $this->getContext();
		$_context['return_type'] = $__return_type;
        $_context['firstOnly'] = $firstOnly;
		return $this->context['callback']->select( $_context );
	}
	
	function asArray( $firstOnly = false ){
		return $this->result( 'asArray', $firstOnly );
	}
	
    public function getContext()
    {
		$_context = $this->context;
		unset($_context['callback']);
        return $_context;
    }
    
	function asObject( $firstOnly = false ){
		return $this->result( 'asObject', $firstOnly );
	}
}
