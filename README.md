# Drinkwater API

API para monitorar quantas vezes o usuário bebeu água, escrito em PHP e com banco de dados SQLite3 sem uso de frameworks ou bibliotecas de terceiros.
Seguindo padrão REST, entradas e saídas em formato JSON. 
 

## ✅   Instalação

Faça o download a partir do link: https://github.com/marcreinan/drinkwater_api/archive/master.zip ou se você tiver o GIT e o PHP instalado em sua maquina, digite no terminal o seguinte comando dentro da pasta de sua preferência:
```bash
git clone https://github.com/marcreinan/drinkwater_api.git
cd drinkwater_api
php -S localhost:8000
```
Para iniciar com um banco de dados sem registros base, delete o arquivo ```db.sqlite3``` dentro da pasta ```api/v1```

## ☔   Dependências 

Para correta execução desse projeto é necessário que você tenha instalado o PHP 5.5.8 e o driver SQLite3 para acesso ao banco de dados.

## 🚀   Tecnologias

Esse projeto foi desenvolvido com as seguintes tecnologias:

- [PHP](https://php.net)
- [SQLite3](https://www.sqlite.org)
- [JSON](https://www.json.org)
- [JWT](https://jwt.io)
- [HTTP response status codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)

## 💻   Endpoints

O endereço raiz da API se encontra em ```api/v1/```, os endpoints são inseridos a partir daqui. ex: ```http://localhost:8000/api/v1/login```

| URL   | Método | Entrada | Saída | Headers | Ação|
|-------|--------|---------|-------|---------|-----|
|/users | POST| email* , name* , password* ||| Cria um novo usuário|
|/login |POST|email* , password*|token, id, email, name, drink_counter||Autentica um usuário |
|/users/:id|GET|   |id, name, email, drink_counter|Token* |Obtém um usuário|
|/users|GET|   |(array de usuários)|Token* |Obtém todos os usuários
|/users/:id|PUT|email, name, password|   |Token* |Editar o seu próprio usuário 
|/users/:id|DELETE|||Token* |Deleta o usuário 
|/users/:id/drink|POST|drink_ml* |   |Token* |Incrementa o contador de quantas vezes bebeu água
|/users/page/:page|GET|   |(array de usuários)|Token* |Obtém os usuários em páginas de 5 registros
|/users/:id/history|GET|   |(array de registros de usuários)|Token* |Obtém os registros de um usuário
|/users/:id/history/asc|GET|   |(array de registros de usuários)|Token* |Obtém os registros de um usuário com ordenação ascendente
|/users/ranking|GET|   |(array de registros de usuários)|Token* |Obtém o ranking da data atual
|/users/ranking/asc|GET|   |(array de registros de usuários)|Token* |Obtém o ranking da data atual com ordenação ascendente
|/users/ranking/total|GET|   |(array de registros de usuários)|Token* |Obtém o ranking total
|/users/ranking/total/asc|GET|   |(array de registros de usuários)|Token* |Obtém o ranking total com ordenação ascendente

---
