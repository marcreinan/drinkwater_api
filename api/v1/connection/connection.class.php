<?php
/*
*   Classe responsável pela conexão ao banco de dados e criação das tabelas
*/
class ConnectionDB {
  public static $instance; //instância do banco de dados que será retornada para manipulação

  private function __construct(){} //construtor

  public static function getInstance(){ //Metódo que cria a conexão ao banco e retorna como umas instÂncia
    if (!isset(self::$instance)) { //verifica se o atributo já possui uma conexão setada
      try {
        /**************************************
        * Cria o banco de dados  e            *
        * abre as conexões                    *
        **************************************/
        // Cria (caso não exista) e se conecta ao banco de dados SQLite no arquivo
        self::$instance = new PDO('sqlite:db.sqlite3', null, null, array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
          PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ));
        self::$instance->exec('PRAGMA foreign_keys = ON;');//habilita o uso de chave estrangeira
        /************************************
        * Cria as tabelas 'users' e 'userDrinks
        *************************************/
        $databaseSql = <<<SQL
            CREATE TABLE IF NOT EXISTS `users` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` TEXT NOT NULL,
              `email` TEXT NOT NULL,
              `password` TEXT NOT NULL,
              UNIQUE( `email` )
            );

            CREATE TABLE IF NOT EXISTS `userDrinks` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `user_id` INTEGER NOT NULL CONSTRAINT `userProfile_userId` REFERENCES `users`( `id` ) ON UPDATE CASCADE ON DELETE CASCADE,
              `drink_ml` INTEGER NOT NULL,
              `created` DATETIME
            );
SQL;
        self::$instance->exec($databaseSql);//Crias as tabelas no banco de dados

      } catch (PDOException $e) { //Caso aconteceça algum erro no banco
        http_response_code(400);  //envia codigo 400 Bad Request
        print $e->getMessage(); //envia a msg de erro
      }
    }

    return self::$instance; //retorna a instancia do banco que foi criado
  }
}
