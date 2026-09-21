<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';
if($argc!==3){fwrite(STDERR,"Uso: php bin/create-admin.php email arquivo-privado-com-senha\n");exit(1);}
$email=Brasa\email($argv[1]);$password=rtrim(file_get_contents($argv[2]),"\r\n");$hash=Brasa\hashPassword($password);
if(Brasa\one('SELECT id FROM users WHERE email=?',[$email]))throw new RuntimeException('Conta já existente.');
$secret=Brasa\base32(random_bytes(20));Brasa\query("INSERT INTO users(email,name,password_hash,role,verified_at,totp_secret) VALUES(?,?,?,'admin',UTC_TIMESTAMP(),?)",[$email,'Administrador Brasa',$hash,Brasa\seal($secret)]);
$path=Brasa\privateDir('enrollment').'/'.bin2hex(random_bytes(8)).'.txt';
file_put_contents($path,"Configure um aplicativo TOTP (6 dígitos, período de 30 segundos).\nConta: $email\nChave: $secret\nApague este arquivo após o cadastro seguro.\n",LOCK_EX);chmod($path,0600);
echo "Administrador criado com MFA obrigatório. Instruções privadas em: $path\n";
