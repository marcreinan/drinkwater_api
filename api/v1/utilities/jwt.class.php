<?php
/*
* Classe com mini implementação do JWT para uso básico na api, sem uso de lib externa
*/
class JWT{
  private $SECRET = "drinkwaterapi2020061319871223"; //API Secret
  public $header = array(); //Header JWT
  public $payload = array(); //Payload JWT

    public function __construct(){
      $this->header = [ //Preenche o header do token
          'typ' => 'JWT', //tipo do token
          'alg' => 'HS256' //algoritmo
      ];

      $date = new DateTime("now"); //pega a data atual, para setar a data de expiração
      date_add($date, date_interval_create_from_date_string('15 minutes')); //adiciona 15 minutos
      
      $this->payload = [ //Prenche o payload do token
          'exp' => $date->getTimestamp() //seta a timestamp de expiração do token
      ];

    }
    //Metódo para codificar dados no JWT
    function encode($data){
      //Na API utilizamos o id do usuario e o email para a autenticação e autorização
      $this->payload['uid'] = $data['user_id']; //seta o id do usuário
      $this->payload['email'] = $data['email']; //seta o email do usuário
      
      //Codifica em json depois em Base 64
      $header = base64_encode(json_encode($this->header)); //Codificando o header
      $payload = base64_encode(json_encode($this->payload)); // Codificando o payload
      
      //Faz a assinatura do JWT com a API SECRET
      $sign = hash_hmac('sha256', $header . "." . $payload, $this->SECRET);
      $sign = base64_encode($sign);
      
      //Token gerado
      $token = $header . '.' . $payload . '.' . $sign;
      
      return $token;
    }
    //Metódo que verifica se o token informado é válido
    function verify($token){
      $tks = explode('.',$token); //divide o token em 3 partes
      if(count($tks) != 3) return false; //verifica o tamanho das partes

      list($header, $payload, $sign) = $tks; //passa cada parte pra uma variavel
      //Assina um novo token de checagem com o header e o payload do token informado
      $check = hash_hmac('sha256', $header . "." . $payload, $this->SECRET);
      $check = base64_encode($check); //codifica em Base64
      
      //Monta um novo token com o header e o payload recebido
      $tokenCheck = $header . '.' . $payload . '.' . $check;
      
      //Checa se o token informado é igual ao token gerado e assinado pelo metódo  
      if($tokenCheck === $token){
        //checa se o payload é válido, caso contrário lança uma exceção
        if (null === $payload = json_decode(base64_decode($payload))) {
          throw new UnexpectedValueException('Payload inválido');
        }
        //checa se o token está expirado, testando se o timestamp atual é menor que o do token
        if(isset($payload->exp) && $payload->exp > (new Datetime('now'))->getTimestamp()){
          return true; //caso o token seja válido
        }else{
          return false; //caso o token seja inválido
        }
      }else{
        return false; //caso o token seja inválido
      }   
    }
    //Metódo que decodifica um token verificado retornando o payload
    function decode($tokenVerified){
      $tks = explode('.', $tokenVerified); //divide o token em 3 partes
        if (count($tks) != 3) { //Verifica o numero de partes
            throw new UnexpectedValueException('Número errado de seguimentos');
        }
        list($headb64, $payload64, $cryptob64) = $tks; //passa cada parte para uma variavel
        //verifica o header
        if (null === ($header = json_decode(base64_decode($headb64)))) {
          throw new UnexpectedValueException('Header inválido');
        }
        //verifica o payload
        if (null === $payload = json_decode(base64_decode($payload64))) {
          throw new UnexpectedValueException('Payload inválido');
        }
        //verifica a assinatura
        if (false === ($sig = base64_decode($cryptob64))) {
            throw new UnexpectedValueException('Assinatura inválida');
        }
        //verifica o algoritmo
        if (empty($header->alg)) {
            throw new UnexpectedValueException('Algoritmo não informado');
        }
        return $payload;
    }

}



