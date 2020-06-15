<?php

/**
 * Classe User - verifica os parametros e as rotas para chamar o metódo correspondente
 */
class User{
	//Atributos
	private $id; //id do usuário
	private $name; //nome do usuário
	private $email; //email do usuário
	private $password; //senha do usuário
	private $drink_ml; //qtd de agua ingerida pelo usuário
	private $db; //banco de dados
	private $method; //metódo HTTP da requisição
	//construtor
	function __construct($name = '', $email = '', $password = '', $drink_ml = ''){
		$this->db = ConnectionDB::getInstance(); //instância do banco de dados
		$this->name = $name; //nome
		$this->email = $email; //email
		$this->password = $password; //password
		$this->drink_ml = $drink_ml; //drink_ml
	}
	//Metódo que verifica o Metódo HTTP, a rota informada e os recursos
	function verifyMethod($method, $resource){
		$headers = array(); //headers da requisição
		foreach (getallheaders() as $key => $value) {//percorre os headers da requisição
			$headers[$key] = $value; //adiciona os valores ao array headers
		}
		//Pega o token vindo do header('Authorization') ou ('token)
		$token = isset($headers["Authorization"]) ? $headers["Authorization"] : (isset($headers["token"]) ? $headers["token"] : null);
		
		$jwt = new JWT(); //instancia um novo JWT

		//Verifica qual o metódo HTTP para disparar a ação correspondente.
		switch ($method) {
			//HTTP GET
			case 'GET':
				if ($jwt->verify($token)) { //verifica o token
					$payload = $jwt->decode($token); //decodifica o payload do token
					if (!empty($resource[1])) { //verifica se o recurso iduser não está vazio
						if (isset($resource[2]) && $resource[2] == 'history') { //verifica se o subrecurso é history
							//ROTA GET /users/{iduser}/history
								return $this->doGetHistory($resource); //chama a função correspondente
							} else if (isset($resource[1]) && $resource[1] == 'ranking') { //verifica se o subrecurso é ranking
								//ROTA GET /users/ranking
								return $this->doGetRanking($resource);//chama a função correspondente
							}else{
								//ROTA GET /users/{iduser}
							return $this->doGet($resource); //chama a função correspondente
						}
					} else {
						//ROTA GET /users/
						return $this->doGet($resource); //chama a função correspondente
					}
				//JWT inválido	
				} else {
					http_response_code(401);
					return $arr_json = array('msg' => 'Token inválido');
				}
				break;
			//HTTP POST	
			case 'POST':
				if (empty($resource[1])) {  //verifica se o recurso iduser está vazio
					if ($this->name == '') {//verifica se o name está vazio
						http_response_code(400);
						return $arr_json = array('msg' => "O nome(name) é obrigatório");
					}
					if ($this->password == '') {//verifica se a senha está vazia
						http_response_code(400);
						return $arr_json = array('msg' => "A senha(password) é obrigatória");
					}
					if ($this->email == '') { //verifica se o email esta vazio
						http_response_code(400);
						return $arr_json = array('msg' => "O email é obrigatório");
					} else {
						if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) { //verifica se o email é válido
							//ROTA POST /users/
							return $this->doPost(); //chama a função correspondente
						} else {
							http_response_code(400);
							return $arr_json = array('msg' => "O email é inválido");
						}
					}
				} else {
					//ROTA POST /users/{iduser}/drink
					if ($jwt->verify($token)) { //verifica o token
						if (!empty($resource[2]) && $resource[2] == 'drink' && $this->drink_ml != '') { //verifica o subrecurso e o parametro
							//ROTA POST /users/{iduser}/drink
							return $this->doPostDrink($resource); //chama a função correspondente
						} else {
							http_response_code(400);
							return $arr_json = array('msg' => "Parâmetro(s)  inválido(s)");
						}
					//JWT inválido
					} else {
						http_response_code(401);
						return $arr_json = array('msg' => 'Token inválido');
					}
				}
				break;
			//HTTP PUT
			case 'PUT':
				if ($jwt->verify($token)) { //verifica o token
					$payload = $jwt->decode($token); //decodifica o payload do token
					if ($payload->uid == $resource[1]) { //verifica se o usuário autenticado é o mesmo do recurso
						//Caso altere o email
						if (isset($this->email)) { //verifica se o email foi informado
							if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) { //verifica se o email é válido
								//ROTA PUT /users/{iduser}
								return $this->doPut($resource);//chama a função correspondente
							} else {
								http_response_code(400);
								return $arr_json = array('msg' => "O email é inválido");
							}
						//Sem alterar o email
						} else {
							//ROTA PUT /users/{iduser}
							return $this->doPut($resource);
						}
					//O usuário autenticado não tem autorização a este recurso
					} else {
						http_response_code(403);
						return $arr_json = array('msg' => 'Acesso não autorizado');
					}
				//JWT inválido
				} else {
					http_response_code(401);
					return $arr_json = array('msg' => 'Token inválido');
				}
				break;
			//HTTP DELETE
			case 'DELETE':
				if ($jwt->verify($token)) { //verifica o token
					$payload = $jwt->decode($token); //decodifica o payload do token
					if (isset($resource[1]) && $resource[1] != null) {//verifica se o recurso iduser foi passado
						if ($payload->uid == $resource[1]) {//verifica se o usuário autenticado é o mesmo do recurso
							//ROTA DELETE /users/{iduser}
							return $this->doDelete($resource);//chama a função correspondente
						//O usuário autenticado não tem autorização a este recurso
						} else {
							http_response_code(403);
							return $arr_json = array('msg' => 'Acesso não autorizado');
						}
					//Parametro id não informado
					} else {
						http_response_code(400);
						return $arr_json = array('msg' => "Parametro id inválido");
					}
				//JWT inválido
				} else {
					http_response_code(401);
					return $arr_json = array('msg' => 'Token inválido');
				}
			break;
			//Rota padrão para os outros metódos não permitidos
			default:
				http_response_code(405);
				return array('status' => 405);
				break;
		}
	}
	//Metódo que retorna o ranking de usuário com base na quantidade de água ingerida
	function doGetRanking($resource){
		$sql = $order = '';
		//pega o recurso order, caso tenha sido informado
		if($resource[2] == 'total'){
			$order = isset($resource[3]) ? ($resource[3] == 'asc' ? 'ASC' : 'DESC') : 'DESC';
			$sql = "SELECT 
								u.name,
							CASE WHEN (SELECT SUM(d.drink_ml)) > 0 THEN SUM(d.drink_ml) ELSE 0 END AS total_quantity
							FROM
								users u
							LEFT JOIN userDrinks d
							ON u.id = d.user_id
							GROUP BY
								u.id
							ORDER BY
								total_quantity ";
		}else{
			$order = isset($resource[2]) ? ($resource[2] == 'asc' ? 'ASC' : 'DESC') : 'DESC';
			//Sql query - Pega o nome do usuário e a soma da quantidade de drink_ml
			$sql = "SELECT 
								u.name,
							CASE WHEN (SELECT SUM(d.drink_ml)) > 0 THEN SUM(d.drink_ml) ELSE 0 END AS total_quantity
							FROM
								users u
							LEFT JOIN userDrinks d
							ON u.id = d.user_id
							WHERE
								(SELECT DATE(d.created)) = (SELECT DATE('now'))
							GROUP BY
								u.id
							ORDER BY
							total_quantity ";
		}
		$sql .= $order; //adiciona o parametro de ordenação ao sql
		$stmt = $this->db->prepare($sql); //prepara o sql //prepara o sql
		$stmt->execute(); //executa o sql

		if ($row = $stmt->fetch()) { //caso retorne linhas , pega cada uma adicionando ao array de retorno
			do {
				$arr_json[] = $row;
			} while ($row = $stmt->fetch());
			return $arr_json; //retorna os registros
		} else {
			http_response_code(404);
			return $arr_json = array('msg' => 'Não há registros cadastrados');
		}
	}
	//Metódo que retorna o historico de um usuário
	function doGetHistory($resource){
		//pega o recurso order, caso tenha sido informado
		$order = isset($resource[3]) ? ($resource[3] == 'asc' ? 'ASC' : 'DESC') : 'DESC';
		//Sql Query - Pega os registros do usuário, por data e quantidade
		$sql = 'SELECT 
    					d.created, d.drink_ml
						FROM
    					userDrinks d
						WHERE
							user_id = :user_id
						ORDER BY
							created ';
		$sql .= $order;
		$stmt = $this->db->prepare($sql); //prepara o sql
		$stmt->bindValue(':user_id', $resource[1], PDO::PARAM_INT); //atribui o id_user
		$stmt->execute(); //executa o sql

		if ($row = $stmt->fetch()) { //Tenta pegar os registros, adicionando cada um ao array de retorno
			do {
				$arr_json[] = $row;
			} while ($row = $stmt->fetch());
			return $arr_json; //retorna os valores
		//caso não exista registros
		} else {
			http_response_code(404);
			return $arr_json = array('msg' => 'Não há registros para esse usuário');
		}
	}
	//Metódo GET /users
	function doGet($resource){
		//Sql query - pega um ou todos os usuários de acordo com o recurso
		$sql = 'SELECT 
    					u.id, u.name, u.email,
    					CASE WHEN COUNT(d.user_id) > 0 THEN COUNT(d.user_id) ELSE 0 END AS drink_counter
						FROM
    					users u
    				LEFT JOIN userDrinks d
						ON u.id = d.user_id';
		//GET /users/{iduser}
		if (!empty($resource[1]) && is_numeric($resource[1])) { //verifica de o recurso iduser existe
			$sql .= ' WHERE u.id = :id GROUP BY u.id'; //completa o SQL
			$stmt = $this->db->prepare($sql); //prepara o sql
			$stmt->bindValue(':id', $resource[1], PDO::PARAM_INT); //atribui o id
			$stmt->execute(); //executa o sql
			if ($user  = $stmt->fetch()) { //caso exista o usuário
				return $arr_json[] = $user; //retorna os dados
			} else { //caso não exista
				http_response_code(404);
				return $arr_json = array('msg' => "Usuário não encontrado");
			}
		//GET /users/page/{numberpage}
		} else if (!empty($resource[1]) && $resource[1] == 'page') { //verifica se existe o recurso page
			$total_row = 5; //total de registros por página
			$pagina = isset($resource[2]) ? $resource[2] : 1; //verifica se a pagina foi informada
			$inicio = $pagina - 1; //paginação a partir de 1
			$inicio = $inicio * $total_row; //define o inicio do limite

			$sql .= ' WHERE 1 = 1 GROUP BY u.id LIMIT :inicio,:total_row'; //completa o sql

			$stmt = $this->db->prepare($sql); //prepara o sql
			$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT); //atribui o inicio
			$stmt->bindValue(':total_row', $total_row, PDO::PARAM_INT); //atribui o total
			$stmt->execute();

			if ($row = $stmt->fetch()) { //caso exista registros, adiciona ao array de resultado
				do {
					$arr_json[] = $row;
				} while ($row = $stmt->fetch());
				return $arr_json; //retorna o array de resultados
			} else {
				http_response_code(404);
				return $arr_json = array('msg' => 'Não existem usuários cadastrados');
			}
		//GET /users/
		} else {
			$sql .= ' WHERE 1 = 1 GROUP BY u.id'; //completa o sql
			$stmt = $this->db->prepare($sql); //prepara o sql
			$stmt->execute(); //executa o sql

			if ($row = $stmt->fetch()) { //caso exista registros, adiciona ao array de resultado
				do {
					$arr_json[] = $row;
				} while ($row = $stmt->fetch());
				return $arr_json; //retorna o array de resultados
			} else {
				http_response_code(404);
				return $arr_json = array('msg' => 'Não existem usuários cadastrados');
			}
		}
	}
	//Metódo POST /users
	function doPost(){
		//Sql query - pega um usuário pelo email
		$sql = "SELECT email FROM users WHERE email = :email";
		$stmt = $this->db->prepare($sql); //prepara o sql
		$stmt->bindValue(':email', $this->email, PDO::PARAM_STR); //atribui o email
		$stmt->execute(); //executa o sql
		if ($data = $stmt->fetch()) { //verifica se já existe o usuário
			http_response_code(400);
			return $arr_json = array('msg' => "O usuário com email:{$this->email} já existe");
		} else { //caso não exista insere um novo
			//POST /users
			$this->password = SHA1($this->password); //encripta em SHA1
			//Sql query - insere um usuário
			$sql = "INSERT INTO users (name,email,password) VALUES (:name,:email,:password)";
			$stmt = $this->db->prepare($sql); //prepara o sql
			$stmt->bindValue(':name', $this->name, PDO::PARAM_STR); //atribui o name
			$stmt->bindValue(':email', $this->email, PDO::PARAM_STR); //atribui o email
			$stmt->bindValue(':password', $this->password, PDO::PARAM_STR); //atribui o password
			
			if ($stmt->execute()) { //executa o sql
				http_response_code(201);
				return $arr_json = array('status' => 201, 'success' => true);
			} else {
				http_response_code(400);
				return $arr_json = array('status' => 400, 'success' => false);
			}
		}
	}
	//Metódo que incrementa o registro do usuário
	function doPostDrink($resource){
		//Sql query - pega um usuário pelo id
		$sql = "SELECT * FROM users WHERE id = :id";
		$stmt = $this->db->prepare($sql); //prepara o sql
		$stmt->bindValue(':id', $resource[1], PDO::PARAM_INT); //atribui o id
		if ($user = $stmt->fetch($stmt->execute())) { //caso exista o usuário
			//SQL query - insere um registro na userDrinks
			$sql = "INSERT INTO userDrinks (user_id,drink_ml,created) VALUES (:user_id,:drink_ml,:created)";
			$stmt = $this->db->prepare($sql); //prepara o sql
			$stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT); //atribui o user_id
			$stmt->bindValue(':drink_ml', $this->drink_ml, PDO::PARAM_INT); //atribui o drink_ml
			$stmt->bindValue(':created', date("Y-m-d H:i:s"), PDO::PARAM_STR); //atribui o created

			if ($stmt->execute()) { //executa o sql
				return $this->doGet($resource); //chama o doGet para mostrar as informações do usuário
				//return $arr_json = array('status' => 200);
			} else {
				http_response_code(500);
				return $arr_json = array('status' => 500, 'success' => false);
			}
		}
	}
	//Metódo PUT
	function doPut($resource){
		//Sql query - pega um usuário por email
		$sql = "SELECT * FROM users WHERE email = :email";
		$stmt = $this->db->prepare($sql); //prepara o sql
		$stmt->bindValue(':email', $this->email, PDO::PARAM_STR); //atribui o email

		if ($data = $stmt->fetch($stmt->execute())) { //verifica se já existe o email
			http_response_code(500);
			return $arr_json = array('msg' => "O usuário com email:{$this->email} já existe");
		} else {

			//Sql query - atualiza os dados de um usuário
			$sql = "UPDATE users SET";

			if ($this->name != '') { //testa se existe o name
				$sql .= ' name = :name,'; //completa o sql
			}
			if ($this->email != '') { //testa se existe o email
				$sql .= ' email = :email,'; //completa o sql
			}
			if ($this->password != '') { //testa se existe a senha
				$this->password = SHA1($this->password); //encripta em SHA1
				$sql .= ' password = :password,'; //completa o sql
			}
			$sql = substr($sql, 0, -1); //formata o sql
			$sql .= " WHERE id = :id"; //completa com where
			$stmt = $this->db->prepare($sql); //prepara o sql
			$stmt->bindValue(':id', $resource[1], PDO::PARAM_INT); //atribui o id

			if ($this->name != '') {
				$stmt->bindValue(':name', $this->name, PDO::PARAM_STR);//atribui o name
			}
			if ($this->email != '') {
				$stmt->bindValue(':email', $this->email, PDO::PARAM_STR);//atribui o email
			}
			if ($this->password != '') {
				$stmt->bindValue(':password', $this->password, PDO::PARAM_STR);//atribui o password
			}

			if ($stmt->execute()) { //executa o sql
				return $arr_json = array('status' => 200, 'success' => true);
			} else {
				http_response_code(400);
				return $arr_json = array('status' => 400, 'success' => false);
			}
		}
	}
	//Metódo DELETE
	function doDelete($resource){
		//Sql query - Deleta um usuário por id
		$sql = 'DELETE FROM users WHERE id = :id';
		$stmt = $this->db->prepare($sql); //prepara o sql
		$stmt->bindValue(":id", $resource[1], PDO::PARAM_INT); //atribui o id

		if ($stmt->execute()) { //executa o sql
			return $arr_json = array('status' => 200);
		} else {
			http_response_code(404);
			return $arr_json = array('msg' => "Usuário não encontrado");
		}
	}
}
