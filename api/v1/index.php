<?php
	/*
	* Drinkwater API - v 1.0.0
	* Ponto de entrada da api, responsável por manipular as rotas, chamadas HTTP, verificação de dados
	*
	*/

	include('connection/connection.class.php'); //Inclue a conexão ao banco de dados
	include('utilities/jwt.class.php'); //inclue a classe de manipulação do JWT
	
	header('Cache-Control: no-cache, must-revalidate'); //Desabilita o cache
	header('Content-Type: application/json; charset=utf-8'); //Seta o tipo da aplicação
	date_default_timezone_set('America/Bahia'); //Seta o timezone
	
	$route 	= $_SERVER['REQUEST_URI']; //Pega a rota chamada
	$method = $_SERVER['REQUEST_METHOD']; //Pega o metódo HTTP
	//Formatação da rota para extrair os recursos
	$route = substr($route, 1);
	$route = explode("?", $route);
	$route = explode("/", $route[0]);
	$route = array_diff($route, array('api', 'v1'));
	$route = array_values($route);
	//Pega os dados JSON vindos na requisição
	$data = json_decode(file_get_contents('php://input'), true);
	//Formata os dados experados
	$name =  isset($data['name'])?$data['name']:null; //Nome do usuário
	$email =  isset($data['email'])?$data['email']:null; //Email do usuário
	$password =  isset($data['password'])?$data['password']:null; //Senha do usuário
	$drink_ml =  isset($data['drink_ml'])?(int)$data['drink_ml']:null; //Qtd de água
	
	$arr_json = array(); //inicia array de retorno de respostas

	if (count($route) <= 4) { //verifica o tamanho da rota
		if(!empty($route[0])){ //verifica se existe o recurso 
			switch ($route[0]) { //escolhe o recurso com base na rota
				//ROTA /login
				case 'login':
					if($method == 'POST'){ //verifica se o metódo é POST
						include('login.class.php'); //inclue a classe de login
						$login = new Login($email,$password); //inicia um novo objeto Login
						$arr_json = $login->login($route); //passa os parametros da rota
					}else{ //caso não seja, mostra msg de erro
						http_response_code(501);
						$arr_json = array('msg' => "Método HTTP inválido para essa rota");
					}
				break;
			//ROTA /users	
			case 'users':
					include('user.class.php'); //inclue a classe users
					$user = new User($name,$email,$password,$drink_ml); //inicia um novo objeto User
					$arr_json = $user->verifyMethod($method,$route); //verifica o metódo e a rota
				break;				
			//ROTA 404 para requisições indesejadas
			default:
			http_response_code(404);
			$arr_json = array('status' => 404);
		break;
	}
	//ROTA PADRÃO	
}else{
	$arr_json = array('msg' => "Bem vindo(a) ao DrinkWater API"); //msg de boas vindas
}
//ROTA 404 para requisições indesejadas
}else{
	http_response_code(404);
		$arr_json = array('status' => 404);
	}

	echo json_encode($arr_json); //envia as respostas JSON 
?>