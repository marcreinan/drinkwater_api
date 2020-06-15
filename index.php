
<html>
	<head>
		<title>Drink Water API</title>
		<style type="text/css">
			body{
				text-align: center;
			}
			#container{
				width: 200px;
				position:absolute;
				top:50px;
				left:50%;
				margin-left:-100px;
			}
			div{
				margin-top: 10px;
			}
		</style>
	</head>
	<body>
		<div id="container">
			<h2>Cadastrar novo usuário</h2>
			<form method="POST" action="api/users">
				<div>
					<label for="name">Name:</label>
					<input type="text" name="name" id="name" placeholder="Name"/> 
				</div>
				<div>
					<label for="age">Email:</label>
					<input type="text" name="email" id="email" placeholder="Email"/>
				</div>
				<div>
					<label for="age">Password:</label>
					<input type="text" name="password" id="password" placeholder="password"/>
				</div>
				<div>
					<input type="submit" value="Send" name="btn"  /> 
				</div>
			</form>
		</div>
	</body>
</html>