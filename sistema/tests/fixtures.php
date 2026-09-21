<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
if(PHP_SAPI!=='cli'||!Brasa\local()||!getenv('BRASA_TEST_CREDENTIALS'))exit(1);
$fixture=[];$prefix='test-'.bin2hex(random_bytes(6));
foreach(['customer','other','admin','kitchen','manager']as $label){
    $role=$label==='other'?'customer':$label;$email=$prefix.'-'.$label.'@example.com';$password=bin2hex(random_bytes(16));$secret=Brasa\base32(random_bytes(20));
    Brasa\query('INSERT INTO users(email,name,phone,password_hash,role,verified_at,totp_secret) VALUES(?,?,?,?,?,UTC_TIMESTAMP(),?)',[$email,'Teste '.$label,'11999992026',Brasa\hashPassword($password),$role,$role==='customer'?null:Brasa\seal($secret)]);
    $fixture[$label]=['id'=>(int)Brasa\db()->lastInsertId(),'email'=>$email,'password'=>$password,'totp'=>$secret];
}
file_put_contents(getenv('BRASA_TEST_CREDENTIALS'),Brasa\json($fixture),LOCK_EX);
echo "Contas de teste isoladas criadas. Credenciais não exibidas.\n";
