<?php

/**
 * Classe Login - Responsável por autenticação e autorização de usuários
 */
class Login{
	private $email; //email do usuário informado
	private $password; //senha informada
	private $db; //instância do banco de dados

	function __construct($email = '', $password = ''){ //construtor
		$this->db = ConnectionDB::getInstance(); //Pega a instância do banco de dados
		$this->email = $email; //atribue o email
		$this->password = $password; //atribue a senha
	}
	//Metódo que realiza o login e gera o JWT caso o email e a senha estejam corretos
	function login(){
		//verifica se o email não está vazio
		if(empty($this->email)){
			http_response_code(401);
			return $arr_json = array('msg' => 'Email inválido(s)');
		}
		//verifica se a senha não está vazia
		if(empty($this->password)){
			http_response_code(401);
			return $arr_json = array('msg' => 'Senha inválida');
		}
		//codifica a senha em SHA1
		$this->password = SHA1($this->password);
		//SQL Query - Pegar usuário por email e senha
		$sql = 'SELECT 
    					u.id,u.name,u.email,
    					CASE WHEN COUNT(d.user_id) > 0 THEN COUNT(d.user_id) ELSE 0 END AS drink_counter
						FROM
    					users u
    				LEFT JOIN userDrinks d
						ON u.id = d.user_id
						WHERE
							u.email = :email AND u.password = :password
						GROUP BY
							u.id';
						
		$stmt = $this->db->prepare($sql); //verifica o SQL
		$stmt->bindValue(':email', $this->email, PDO::PARAM_STR); //atribue o email ao sql
		$stmt->bindValue(':password', $this->password, PDO::PARAM_STR); //Atribue a senha ao sql
		$stmt->execute(); //executa o sql

		if ($user  = $stmt->fetch()) { //pega o usuário caso exista
			$jwt = new JWT(); //inicia um novo JWT
			$token = $jwt->encode(['user_id' => $user['id'], 'email' => $user['email']]);//gera o JWT
			$user['token'] = $token; //adiciona o token a resposta
			return $arr_json[] = $user; //retorna as informações do usuário
		} else { //caso o usuário não seja encontrado
			http_response_code(401);
			return $arr_json = array('msg' => "Não foi possivel fazer o login, verifique o email ou a senha");
		}
	}
}