<?php

header('Content-Type: text/html; charset=utf-8');

# Este arquivo contém alguns parametros de configuracao do sistema
include 'config.php' ;

# usado para criptografar e descriptografar as informacoes
# do banco de dados por meio de chave
include 'fdbcripto.class.php' ;

# funcoes uteis utilizadas durante os processos
include 'fdbutils.class.php' ;

# Objeto resposavel por gerenciar os dados em uma tabela
include 'fdbtable.class.php' ;


/* ****************************************************
 *
 * PHP version 5+
 *
 * @author    Wanderlei Santana <sans.pds@gmail.com>
 * @copyright 2016 Wanderlei Santana
 * @license   LICENSE.md MIT License
 * @version   201604160217
 *
 */

class FlatDbase
{
	/**
	 * @var array $database - keeps context from database
	 * array(
	 *  'name',   // Database name
	 *  'folder', // Database Container Folder
	 *  'header', // Path to the database
	 *  'dbtree'  // tables into database
	 * )
	 *
	 */
	private $database  = null;

    // @var array - errors
	private $errors	= null;


    /** ----------------------------------------------------
	 * construct method
	 *
     * @param string $dbname - database name to work in
     **/
	public function __construct( $dbname = null )
	{
		defined( 'DS' ) || define( 'DS', DIRECTORY_SEPARATOR );

		$tmp = explode( DS, realpath( __FILE__ ) );
		array_pop($tmp);
		$tmp[] = flatdbase_config::database_folder_name;

		// set container folder
		$this->database_path = join( DS, $tmp ).DS;
		$this->init( $dbname );
	}

	public function getErrors(){
		return $this->errors;
	}
	public function setError( $value = null ){
		$this->errors[] = $value;
	}

	/** ----------------------------------------------------
	 * initialize database for work
	 *
	 * @param  string $dbname - database name
	 * @return bool
	 */
	public function init( $dbname =  null )
	{
		if( !is_dir($this->database_path) )
			if( !mkdir($this->database_path) )
				$this->show_error( "Database->init() : Container folder does not exist." );

		// database name validate
		$dbname = FDBUtils::make_safe( $dbname, flatdbase_config::max_dbname_size );

		if(empty($dbname)){
			$this->errors[] = '[NOTICE]FlatDbase::init()-> Undefined database name';
			return false;
		}

		if(!$this->exists($dbname)){
			$this->errors[] = '[NOTICE]FlatDbase::init()-> The "'.$dbname.'" database does not exist.';
			return false;
		}

		$this->database['name'] = $dbname;

		// set database folder container
		$this->database['folder'] = $this->database_path.$dbname.DS;

        // set database file header
		$__database__ = $this->database_path.$dbname.DS.$dbname.flatdbase_config::ext_header;

		$this->database['header'] = realpath( $__database__ );

		// load database estrutura
		$this->load_dbtree();
		return true;
	}

	/** ----------------------------------------------------
	 * Check if a database exists
	 *
	 * @param  string $dbname - database name
	 * @return bool
	 */
	public function exists( $__dbname )
	{
		$__dbname = FDBUtils::make_safe( $__dbname , flatdbase_config::max_dbname_size );

		// check database folder
		if( !is_dir($this->database_path.$__dbname) )
        {
			$this->errors[] = "[NOTICE]Flatbase::exists() -> Path to the database '".$__dbname."' not found." ;
			return false;
		}

		// check database exists
		$__database__ = $this->database_path .
			$__dbname . DS . $__dbname . flatdbase_config::ext_header;

		if(!file_exists($__database__)){
			$this->errors[] = "[NOTICE]Flatbase::exists() -> The '".$__dbname."' database does not exist." ;
			return false;
		}

		return true;
	}

	/** ----------------------------------------------------
	 *
	 **/
	protected function load_dbtree()
	{
		if($this->exists( $this->database['name'] ))
			$this->database['dbtree'] = FDBUtils::decode(
				file_get_contents($this->database['header'])
            );

		if(is_array($this->database['dbtree'])):
			foreach ($this->database['dbtree'] as $table => $properties):
				$properties = json_decode($properties,true) ;
				$properties['filename'] = $properties['file'];
				$properties['file'] = $this->getTablePath( $properties['file'] );
                $this->$table = new FDBTable( $properties, $this );
				$this->$table->setName( $table );
			endforeach;
		endif;
	}

	/** ----------------------------------------------------
	 * reset Db Tree
	 * @param string $indice - table name
	 * @param array $values - table structure as array('file'=>,'fields'=>,'last_inserted_id'=>)
	 * @return void
	 **/
	public function update_dbtree( $indice, $value )
	{
		if(is_array($this->database['dbtree'])):
			$this->database['dbtree'][$indice] = json_encode($value);
			file_put_contents(
				$this->database['header'],
				FDBUtils::encode( $this->database['dbtree'] )
			);
		endif;
	}

	/** ----------------------------------------------------
	 * Create a new Database structure
	 *
	 * When this method is called, it creates a folder with the same name
	 * received by parameter inside the "db folder" (see config.php database_folder_name).
	 * Then, inside this new folder creates a file header that will
	 * keep a list of database tables.
	 *
	 * @param  string $dbname - the database name that will be created
	 * @return bool - case success return true otherwise false
	 */
	public function create( $dbname = null )
	{
		if($dbname == null ) return false ;

		// make_safe - check if this is a valid string
		$dbname = FDBUtils::make_safe( $dbname, flatdbase_config::max_dbname_size );

		// set database folder container
		$this->database['folder'] = $this->database_path.$dbname.DS;

		// database folder
		$__fdr__ = $this->database_path.$dbname;

		if( !is_dir( $__fdr__ ) )
			if( !mkdir( $__fdr__ ) ){
				$this->show_error( "Can't create database container folder." );
				return false;
			}

		// database file header
		$__database__ = $__fdr__.DS.$dbname . flatdbase_config::ext_header;

		if(!file_exists($__database__)):
			// create the database structure
			file_put_contents( $__database__, FDBUtils::encode( 'No data.' ) );

			// create htaccess, denny folder access
			file_put_contents( $__fdr__.DS.'.htaccess', 'Deny from all' );

			$this->database['name'] = $dbname;
			$this->database['header'] = realpath( $__database__ );
			$this->init($dbname);
		else:
			// $this->show_error( "Database '".$database."' already exist." );
			$this->errors[] = 'Error -> FlatDbase::create() : The "'.$dbname.'" database already exists.';
			return false;
		endif;
		return true;
	}

    /** ----------------------------------------------------
     * Delete Database
     *
     * @param string $dbname - database to be deleted
     * @return bool  - true if deleted successfully
     **/
    public function delete( $dbname = null )
    {
        if( $dbname == null ) return false;
        $dbname = FDBUtils::make_safe($dbname, flatdbase_config::max_dbname_size );
        if(!$this->exists($dbname)){
			$this->errors[] = '[NOTICE]FlatDbase::delete() -> The "'.$dbname.'" does not exist.' ;
			return false;
		}

        $db = $this->database_path.$dbname;
        if(!is_dir($db)){
			$this->errors[] = '[NOTICE]FlatDbase::delete() -> Can\'t locate "'.$dbname.'" database.' ;
			return false;
        }

        if( FDBUtils::deleteDirectory($db) ):
            return true;
        else:
			FDBUtils::show_error( '[NOTICE]FlatDbase::delete() -> Unable to delete database "'.$dbname.'"' );
            return false;
        endif;
    }

	/** ----------------------------------------------------
	 *	rename a database
	 *
	 *	@param string - Old database name
	 *	@param string - new database name
	 *	@return bool
	 **/
	public function rename( $__oldname, $__newname )
	{
        if( $__oldname == null || $__newname == null) return false;

        $__oldname = FDBUtils::make_safe($__oldname);
        if(!$this->exists($__oldname))
        {
        	FDBUtils::show_error( 'FlatDbase::rename() -> Table "'.$__oldname.'" does not exist.' );
        	return false;
        }

        $__newname = FDBUtils::make_safe($__newname, flatdbase_config::max_dbname_size );

        // database path
        $__olddb = $this->database_path.$__oldname;
        if( !is_dir($__olddb) ):
        	$__oldname = FDBUtils::make_safe( $__oldname, flatdbase_config::max_dbname_size );
        	$__olddb = $this->database_path.$__oldname;
        	if(!is_dir($__olddb)){
            	FDBUtils::show_error( 'FlatDbase->rename() : Can\'t locate "'.$__oldname.'" database.' );
            	return false;
        	}
    	endif;

		$__newdb = $this->database_path.$__newname;
        if(!rename($__olddb,$__newdb)):
            FDBUtils::show_error( 'FlatDbase->rename() > Unable to rename database "'.$__oldname.'"' );
            return false;
        endif;

		// old file header
		$__old_header = $__newdb.DS.$__oldname.flatdbase_config::ext_header;
		$__new_header = $__newdb.DS.$__newname.flatdbase_config::ext_header;
		if(!rename($__old_header, $__new_header)):
			FDBUtils::show_error( 'FlatDbase->rename() > Unable to rename database header "'.$__oldname.flatdbase_config::ext_header.'"' );
			return false;
		endif;

		$this->init($__newname);

		return true;
	}

	/** ----------------------------------------------------
	 * database list
	 *
	 * @return array
	 **/
	public function show_databases()
	{
		$_arr = array();
		//	$__arr = glob($this->database_path.'*', GLOB_ONLYDIR);
		$_folders = scandir($this->database_path);
		foreach($_folders as $__folder)
			if(!in_array($__folder, array('.','..')))
			   $_arr[] = $__folder;
		return $_arr;
	}

	/** ----------------------------------------------------
	 * list of tables in a database
	 *
	 * @param string $__database - database name
	 * @return array
	 **/
	public function show_tables( $__database )
	{
		$_arr = array();
		$__database = FDBUtils::make_safe($__database);
		if(!$this->exists($__database)) return $_arr;

		$__folder = $this->database_path.$__database.DS;

		$_folders = scandir($__folder);
		foreach($_folders as $__folder)
			if(!in_array($__folder, array('.','..',	'.htaccess',$__database.flatdbase_config::ext_header)))
			   $_arr[] = str_replace(flatdbase_config::ext_data,'', $__folder);
		return $_arr;
	}

    /** ----------------------------------------------------
     * Verifica se uma tabela existe no contexto do banco de dados
     * @param string $tablename - table name
     * @return bool - true if exists
     **/
	public function table_exists( $tablename = null )
	{
		if($tablename == null) return false;

		$tablename = FDBUtils::make_safe($tablename);
		if(empty($tablename))
            return false;

		if(!isset($this->database['dbtree'])){
			// FDBUtils::show_error( "Database->table_exists : Database context not initialized." );
			$this->errors[] = '[ERROR]FlatDbase::table_exists()-> No Database selected.';
			return false;
		}

		$dbtree = $this->database['dbtree'];
		$exists = false;

		if(is_array($dbtree)):
			foreach ($dbtree as $table => $properties):
				if($table == $tablename){
					$exists = true;
					break;
				}
			endforeach;
		endif;

		return $exists;
	}

	/** ----------------------------------------------------
    * Caminho completo para a tabela, dado o seu nome
    **/
    public function getTablePath($__table){
    	return $this->database['folder'].$__table.flatdbase_config::ext_data;
    }

    /** ----------------------------------------------------
     * Cria uma nova tabela dentro do banco de dados atual
     **/
	public function create_table( $__tablename__ = null, $_fields_ = array() )
	{
        if(!isset($this->database['name']))
            FDBUtils::show_error( 'FlatDbase::create_table() : No database Selected.' );

		$__tablename__ = FDBUtils::make_safe( $__tablename__,flatdbase_config::max_tablename_size );
		if($__tablename__ == null ) return false;
		if(!is_array($_fields_)) return false;

		if($this->table_exists( $__tablename__ )):
			$this->errors[] = '[ERROR]FlatDbase::create_table()-> Table "'.$__tablename__.'" already exists.';
			# print 'Table "'.$__tablename__.'" already exists.';
			return false;
		endif;

		// initialize table structure
		$_table_estructure_ = array(
			'file' => '',
			'fields' => array(),
			'last_insert_id' => 0
		);

		// table file
		if(!isset($this->database['folder']))
			FDBUtils::show_error( 'Flatdabse::create_table() : Undefined database folder. Have you started the database? $database->init( "dbname" );' );

		$__table = $this->getTablePath( $__tablename__ );

		if(file_exists($__table)):
			print 'Can\'t create table. File "'.$__tablename__.'" already exists.';
			return false;
		endif;

		// set table file name
		$_table_estructure_['file'] = $__tablename__;

		foreach ($_fields_ as $key => $value):

			// checking data type ...
			if(isset($value['type'])):
				$method = 'as_'.$value['type'];
				if(!method_exists( 'FDBTypes', $method )):
					FDBUtils::show_error( 'FlatDbase::create_table : The "'.$value['type'].'" type was not declared in "fdbtypes.php".' );
					return false;
				endif;
			endif;

			if(isset($value['size']))
				$value['size'] = abs( $value['size'] );

			if(isset($value['auto_increment'])):
				if(is_numeric($value['auto_increment']))
					$value['auto_increment'] = abs( $value['auto_increment'] );
				$value['auto_increment'] = FDBUtils::isTrue( $value['auto_increment'] );
			endif;

			if(isset($value['null']))
				$value['null'] = FDBUtils::isTrue( $value['null'] );

			$_campos_[$key] = $value;
		endforeach;

		$_table_estructure_['fields'] = $_campos_ ;
		if(!isset($this->database['dbtree']) || !is_array($this->database['dbtree']))
			$this->database['dbtree'] = array();

		$this->database['dbtree'][$__tablename__] = json_encode($_table_estructure_) ;

		if(isset($this->database['header'])):
			// create file
			if(touch( $__table )):
				file_put_contents(
					$this->database['header'],
					FDBUtils::encode( $this->database['dbtree'] )
				);
				$this->load_dbtree();
			else:
				FDBUtils::show_error( "Can't create file '".$__table."' " );
				exit(1);
			endif;
		else:
			FDBUtils::show_error( 'No database selected.' );
		endif;

		$this->init( $this->database['name'] );
		
		return true;
	}

	/**
	 * returns properties from a table
	 * @param string $__tablename 
	 * @return object | null
	 **/
	public function desc( $__tablename )
	{
		$__tablename = FDBUtils::make_safe($__tablename, flatdbase_config::max_tablename_size);
		if($this->table_exists($__tablename)):
			return json_decode( $this->database['dbtree'][$__tablename] );
		endif;
		return null;
	}
	
	/** ----------------------------------------------------
	 * rename a table
	 * */
	public function rename_table( $__oldname, $__newname )
	{
		$__oldname = FDBUtils::make_safe($__oldname);
		$__newname = FDBUtils::make_safe($__newname);
		if(!isset($this->database['name']))
			FDBUtils::show_error( "FlatDbase::rename_table() : No database selected." );

		if(!$this->table_exists($__oldname))
			FDBUtils::show_error( "FlatDbase::rename_table() : Table '".$__oldname."' does not exist." );

		if($this->table_exists($__newname))
			FDBUtils::show_error( "FlatDbase::rename_table() : The table '".$__newname."' already exist." );

		$_tables = $this->database['dbtree'];

		foreach($_tables as $__k=>$__json_table):
			if($__k == $__oldname):
				$_table = json_decode($__json_table,true);
				$_table['file'] = $__newname;

				if( rename($this->getTablePath($__oldname), $this->getTablePath($__newname)) ):
					unset($this->database['dbtree'][$__oldname]);
					$this->update_dbtree( $__newname, $_table );
					unset( $this->$__oldname );
					$this->load_dbtree();
				else:
					FDBUtils::show_error( 'FlatDbase::rename_table() : Unable to rename the "'.$__oldname.'" table.' );
					return false;
				endif;

				break;
			endif;
		endforeach;

		return true;
	}

	/** ----------------------------------------------------
	 * Delete table from database
	 *
	 * @param string $__tablename
	 * @return bool
	 **/
	public function delete_table( $__tablename )
	{
		$__tablename = FDBUtils::make_safe( $__tablename );

		if(!isset($this->database['name']))
			FDBUtils::show_error( "FlatDbase::deleteTable() : No database selected." );

		if(!$this->table_exists($__tablename))
			FDBUtils::show_error( "FlatDbase::delete_table() : The '".$__tablename."' table does not exist." );

		if(unlink($this->getTablePath($__tablename))):
			unset( $this->database['dbtree'][$__tablename] );

			if(!file_put_contents(
				$this->database['header'],
				FDBUtils::encode( $this->database['dbtree'] ) )):
				FDBUtils::show_error( "FlatDbase::delete_table : Unable to delete '".$__tablename."' table." );
				return false;
			endif;
		else:
			FDBUtils::show_error( "FlatDbase::delete_table : Can't delete file '".$__tablename."'." );
		endif;

		return true;
	}

	/** ----------------------------------------------------
	 *
	 **/
    public function __get( $name )
    {
        if(isset($this->$name))
            return $this->$name;
		FDBUtils::show_error( 'The "'.$name.'" table does not exists in the "'.$this->database['name'].'" database.' );
    }
}