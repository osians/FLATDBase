# FLATDBase

A simple flat file database system created with pure PHP. This simple database system stores data in TXT files and manipulates all information using PHP classes.

## Getting started

Feel free to download and test it. Use the `example.php` as reference. 

### Accessing FLATDbase 
To access and use FLATDbase from any PHP file, just include and make a instance of it by using the following code:
```php
require_once 'src/flatdbase.class.php';

$database = new FlatDbase();
```

### You can create and initialize a new database using:
```php
# check if a database already exists. if not, create it.
if( !$database->exists( "database_test" ) )
 	 $database->create( "database_test" );

# If you already have a previously created database you can boot it using the following method
$database->init( 'database_test' );

```

### Show, Delete and Rename Databases
```php
# This method displays a list of databases
var_dump( $database->show_databases(); );

# deleting a database
if( $database->delete( 'database_test' ) )
	echo "Successfully deleted";

# rename a database
$database->rename( 'old_database_name', 'new_database_name' );
```

### Tables
Now it's time to create some tables into the database. Manipulate and popular it.
```php
# check if table exists 
if( !$database->table_exists( "users" ) ):

 	$_fields = array(
 		'id'    => array('type'=>'integer',   'size'=>'10','null'=>'0','auto_increment'=>'1'),
 		'nome'  => array('type'=>'stringOnly','size'=>'32','null'=>'0'),
 		'email' => array('type'=>'email',     'size'=>'64','null'=>'0'),
 		'status'=> array('type'=>'boolean',   'size'=>'1', 'null'=>'1','default'=>'1'),
	);

	$database->create_table( 'users', $_fields );

endif;

# rename a table 
$boolean = $database->rename_table( 'users', 'usuarios' );

# get description from a table 
$object = $database->desc( 'users' );

# delete a table 
$boolean = $database->delete_table( 'users' );

# add data into the table Method 1. Using Array
$database->users->insert(
    array(
        'id'    => null,
        'nome'  => ' Ana Silva ',
        'email' => ' ana.silva@email.example.com ',
        'status'=> 1
    ));

# add data into the table Method 1. Using a String
$string01 = "id=null; nome=Wanderlei Santana do Nascimento; email=sans.pds@gmail.com; status = 0 ";
$database->users->insert( $string01 );

# delete a register by ID
$database->users->delete( 2 );

# select all registers from a table 
$result = $database->users->select();
foreach( $result->asObject() as $row )
    var_dump( $row );

# - Update 
$database->users->update(
    array('email'=>'osians.veeper.2007@hotmail.com',
        'nome' => 'Wanderlei Santana do Nascimento','status' => 0),
    "id = 10" /* where condition */
) ;


```

### Applying filters in queries
Some kind of simple filter are allowed to get more accurate data. Here are some examples of this.

```php

#  select all from "users" table
$result = $database->users->select()->asObject();
# select only the first result. This will return as object
$result = $database->users->select()->asObject( true );
# select 3 columns as array
$result = $database->users->select( 'nome,email,id' )->asArray();
# filter by ID
$result = $database->users->select()->where( 'id = 10' );
# filter by some IDs
$result = $database->users->select()->where( 'id in 10,13,15' );
$result = $database->users->select( 'id,nome,email' )->where( 'id between 10,20' );
$result = $database->users->select( 'id,nome,email' )->where( 'id >< 10,20' );
# get all records where the email is like sans.pds@gmail.com
$result = $database->users->select( '*' )->where( 'email = sans.pds@gmail.com' );
# loop through each founded record
foreach( $result->asArray() as $row )
    var_dump( $row );

# this return the number of records
$database->users->count();

```

Now, I think the `example.php` file is a good place to know more about this library. Maybe you'll find some things written in Portuguese, I'll translate this as soon as I can.

Thanks.
Wanderlei Santana <sans.pds@gmail.com>