<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
if(PHP_SAPI!=='cli'||!Brasa\local())exit(1);
ob_start();Brasa\sessionStart();$checks=0;$uid=null;
function verify(bool $ok,string $name): void {global $checks;if(!$ok)throw new RuntimeException($name);$checks++;echo "OK $name\n";}
function deny(callable $fn): bool {try{$fn();return false;}catch(Brasa\HttpError){return true;}}
function otp(int $uid,string $purpose,string $code): string {
    $id=bin2hex(random_bytes(24));
    Brasa\query('INSERT INTO challenges(id,user_id,purpose,verifier,expires_at) VALUES(?,?,?,?,?)',[$id,$uid,$purpose,hash_hmac('sha256',$id.'|'.$code,Brasa\appKey()),gmdate('Y-m-d H:i:s',time()+300)]);
    return $id;
}
try {
    $legacy='short legacy';
    verify(password_verify($legacy,Brasa\encodePassword($legacy)),'Atualização do hash preserva senha existente');
    verify(deny(fn()=>Brasa\hashPassword($legacy)),'Cadastro continua rejeitando senha curta');
    $keys=['MAIL_TRANSPORT','SMTP_HOST','SMTP_SECURITY','SMTP_USER','SMTP_PASSWORD','MAIL_FROM','SMTP_PORT'];$before=[];
    foreach($keys as $key)$before[$key]=getenv($key);
    try {
        foreach(['MAIL_TRANSPORT'=>'smtp','SMTP_HOST'=>'smtp.example.com','SMTP_SECURITY'=>'','SMTP_USER'=>'test','SMTP_PASSWORD'=>'test','MAIL_FROM'=>'test@example.com','SMTP_PORT'=>'587'] as $k=>$v)putenv("$k=$v");
        verify(deny(fn()=>Brasa\mailReady()),'SMTP sem criptografia rejeitado');
        putenv('SMTP_SECURITY=tls');Brasa\mailReady();verify(true,'Configuração SMTP TLS aceita sem envio');
        putenv('SMTP_PORT=0');verify(deny(fn()=>Brasa\mailReady()),'Porta SMTP inválida rejeitada');
    }finally{foreach($before as $k=>$v)putenv($v===false?$k:"$k=$v");}
    Brasa\query('INSERT INTO users(email,name,password_hash) VALUES(?,?,?)',['regression-'.bin2hex(random_bytes(12)).'@example.com','Regression',Brasa\hashPassword('Senha exclusiva para regressao 2026!')]);
    $uid=(int)Brasa\db()->lastInsertId();$code='819274';
    $unverified=otp($uid,'login',$code);
    verify(deny(fn()=>Brasa\authAction('verify-code',['challenge'=>$unverified,'code'=>$code])),'Código de login não confirma cadastro pendente');
    Brasa\query('UPDATE users SET verified_at=UTC_TIMESTAMP() WHERE id=?',[$uid]);
    $pending=otp($uid,'login',$code);$reset=otp($uid,'reset',$code);
    $version=(int)Brasa\one('SELECT session_version FROM users WHERE id=?',[$uid])['session_version'];
    Brasa\authAction('verify-code',['challenge'=>$reset,'code'=>$code,'newPassword'=>'Outra senha exclusiva de regressao 2026!']);
    verify((int)Brasa\one('SELECT COUNT(*) AS n FROM challenges WHERE user_id=? AND consumed_at IS NULL',[$uid])['n']===0,'Recuperação revoga todos os códigos anteriores');
    verify((int)Brasa\one('SELECT session_version FROM users WHERE id=?',[$uid])['session_version']===$version+1,'Recuperação revoga sessões anteriores');
    verify(deny(fn()=>Brasa\authAction('verify-code',['challenge'=>$pending,'code'=>$code])),'Código anterior não pode reabrir conta recuperada');
    echo "$checks verificações de regressão concluídas.\n";
} finally {
    Brasa\clearSession();
    if($uid!==null){Brasa\query('DELETE FROM audit_events WHERE actor_id=? OR (object_id=? AND action IN (?,?))',[$uid,(string)$uid,'auth.password_reset','auth.login']);Brasa\query('DELETE FROM challenges WHERE user_id=?',[$uid]);Brasa\query('DELETE FROM users WHERE id=?',[$uid]);}
    ob_end_flush();
}
