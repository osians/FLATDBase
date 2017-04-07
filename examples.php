<?php 

# Arquivo morre aqui, apenas para fins de teste
die();

# necessario para o funcionamento de todos os comandos
require_once 'src/flatdbase.class.php';

# ****************************************************
# Banco de dados
# ----------------------------------------------------
# Como criar um banco de dados
$database = new FlatDbase();

# verifica se o banco de dados existe
# caso nao exista, tenta cria-lo
if( !$database->exists( "database_test" ) )
 	 $database->create( "database_test" );

# ----------------------------------------------------
# - selecionando um banco de dados para trabalho 
$database->init( 'database_test' );

# ----------------------------------------------------
# - Como Excluir banco de dados
$database->delete( 'database_test' );

# ----------------------------------------------------
# - Como renomear um banco de dados
$database->rename( 'old_database_name', 'new_database_name' );

# ----------------------------------------------------
# - Como listar bancos de dados existentes
# retorna um array contendo uma lista dos bancos 
# de dados existentes na tabela "db"
$database->show_databases();

# ----------------------------------------------------
# - Como lista as tabelas dentro de um banco de dados
# retornar um array contendo uma lista das tabelas existentes
$database->show_tables( 'database_test' );

# ----------------------------------------------------
# - Como cria uma tabela
#  verifica se tabela ja existe, do contrario cria uma nova

if( !$database->table_exists( "users" ) ):
    # o indice do array sera o nome do campo na tabela, no caso: id, nome, email, status
    # type - tipo de dado aceito, trata-se de regras para entrada de valores. Os tipos são definidos no arquivo Type.php, 
    # cada metodo trata-se de um tipo de entrada permitido. "as_email" por exemplo só permite ser inserido na tabela informações 
    # validadas como email.
    # size - total de caracteres permitidos 
    # null - quando 0 impede a entrada de valores nulos neste campo/coluna
    # auto_increment - insere um numero unico automaticamente no campo
    # default - ao tentar inserir um informação na tabela, caso a mesma seja nula, tenta usar o valor default 
 	$_fields = array(
 		'id'    => array('type'=>'integer',   'size'=>'10','null'=>'0','auto_increment'=>'1'),
 		'nome'  => array('type'=>'stringOnly','size'=>'32','null'=>'0'),
 		'email' => array('type'=>'email',     'size'=>'64','null'=>'0'),
 		'status'=> array('type'=>'boolean',   'size'=>'1', 'null'=>'1','default'=>'1'),
	);
    # comando que cria efetivamente a tabela no banco de dados selecionado
 	$database->create_table( 'users', $_fields );
endif;

# ----------------------------------------------------
# - Como renomear tabelas
#   retorna true caso tenha sucesso na operacao
$boolean = $database->rename_table( 'users', 'usuarios' );

# ----------------------------------------------------
# - Excluindo uma tabela 
$boolean = $database->delete_table( 'users' );

# ----------------------------------------------------
# - obtendo descricao de uma tabela
# retorna um objeto ou null caso tabela nao encontrada
$object = $database->desc( 'users' );

# ----------------------------------------------------
# - edita tabelas?
# @todo! =D

 
# ****************************************************
# Tabelas
# ----------------------------------------------------
# - Inserindo registros na tabela 
# O Acesso as tabelas e' feito atraves da chamada $database->tabela->metodo(); 
# Se a tabela a ser acessada se chamada "clientes" a chamada deve ser $database->clientes->metodo();
# Metodo 1 de insercao, Array
$database->users->insert(
    array(
        'id'    => null,
        'nome'  => ' Ana Silva ',
        'email' => ' ana.silva@email.example.com ',
        'status'=> 1
    ));

# Metodo 2 de insercao, String
$string01 = "id=null; nome=Wanderlei Santana do Nascimento; email=cat_boris@hotmail.com; status = 0 ";
$database->users->insert( $string01 );

# ----------------------------------------------------
# - Excluir um registro de uma tabela 
#  Exclui um registro, dado o seu ID
$database->users->delete( 2 );

# ----------------------------------------------------
# - Listar todos os registros de uma tabela 
# Listando como array...
$result = $database->users->select();
foreach( $result->asObject() as $row )
    var_dump( $row );

# listando como objeto
$result = $database->users->select()->asObject();
foreach ($result as $row )
    var_dump( $row );


# ----------------------------------------------------
# - Atualizando um registro
$database->users->update(
    array('email'=>'osians.veeper.2007@hotmail.com',
        'nome' => 'Wanderlei Santana do Nascimento','status' => 0),
    "id = 10" /* where condition */
) ;

# ----------------------------------------------------
# - aplicando filtros nas consultas
#  seleciona tudo da tabela "users" como objeto
$result = $database->users->select()->asObject();
# selectiona apenas o primeiro resultado como objeto 
$result = $database->users->select()->asObject( true );
# selecionando poucas colunas da tabela como array
$result = $database->users->select( 'nome,email,id' )->asArray();
# selecionando todas as colunas da tabela onde o ID igual a 10
$result = $database->users->select()->where( 'id = 10' );
# selecionando todos as colunas da tabela onde o ID seja 10,13 ou 15
$result = $database->users->select()->where( 'id in 10,13,15' );
# selecionando todas as colunas da tabela onde o ID esteja entre 10 e 20
$result = $database->users->select( 'id,nome,email' )->where( 'id between 10,20' );
# selecionando todas as colunas da tabela onde o ID esteja entre 10 e 20
$result = $database->users->select( 'id,nome,email' )->where( 'id >< 10,20' );
# selecionando todos as colunas da tabela onde a coluna email = wahley@uol.com.br
$result = $database->users->select( '*' )->where( 'email = wahley@uol.com.br' );
# percorrendo dados encontrados 
foreach( $result->asArray() as $row )
    var_dump( $row );

# ----------------------------------------------------
# - retorna o numero de registros numa tabela
$database->users->count();
