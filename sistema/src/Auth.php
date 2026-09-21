<?php
declare(strict_types=1);
namespace Brasa;

function passwordInput(mixed $value,int $min=0): string {
    if(!is_string($value)||!mb_check_encoding($value,'UTF-8')||str_contains($value,"\0")||mb_strlen($value)<$min||mb_strlen($value)>128)fail('Confira o tamanho da senha.');
    return $value;
}
function hashPassword(mixed $value): string {
    $password=passwordInput($value,15);
    if(in_array(mb_strtolower($password),['123456789012345','passwordpassword','brasa2026brasa2026'],true))fail('Escolha uma frase de senha menos previsível.');
    return encodePassword($password);
}
// Rehashing an existing verified password must not apply new enrollment policies.
function encodePassword(string $password): string {
    if(!defined('PASSWORD_ARGON2ID'))throw new \RuntimeException('Argon2id unavailable');
    return password_hash($password,PASSWORD_ARGON2ID,['memory_cost'=>19456,'time_cost'=>2,'threads'=>1]);
}
function establish(array $u): array {
    clearSession();$_SESSION['uid']=(int)$u['id'];$_SESSION['version']=(int)$u['session_version'];
    $_SESSION['role']=$u['role'];$_SESSION['started']=time();$_SESSION['last']=time();$_SESSION['reauth']=time();
    audit('auth.login',(string)$u['id']);return ['user'=>publicUser($u),'csrf'=>$_SESSION['csrf']];
}
function mailReady(): void {
    $mode=env('MAIL_TRANSPORT','disabled');
    if($mode==='smtp'&&env('SMTP_HOST')!==''&&filter_var(env('MAIL_FROM'),FILTER_VALIDATE_EMAIL)
        &&in_array(env('SMTP_SECURITY','tls'),['tls','ssl'],true)
        &&ctype_digit(env('SMTP_PORT','587'))&&(int)env('SMTP_PORT','587')>=1&&(int)env('SMTP_PORT','587')<=65535
        &&env('SMTP_USER')!==''&&env('SMTP_PASSWORD')!=='')return;
    if(local()&&$mode==='spool')return;
    fail('O envio de códigos está indisponível. Tente novamente mais tarde.',503);
}
function challenge(string $address,string $purpose): array {
    mailReady();rate('code-contact',$address,5,3600);rate('code-resend',$address,1,60);rate('code-ip',ip(),20,3600);
    $u=one('SELECT * FROM users WHERE email=? AND active=1 AND role=?',[$address,'customer']);
    $id=bin2hex(random_bytes(24));$code=(string)random_int(100000,999999);$verifier=hash_hmac('sha256',$id.'|'.$code,appKey());
    transaction(function()use($u,$purpose,$id,$verifier,$code,$address){
        if($u)query('UPDATE challenges SET consumed_at=UTC_TIMESTAMP() WHERE user_id=? AND purpose=? AND consumed_at IS NULL',[$u['id'],$purpose]);
        query('INSERT INTO challenges(id,user_id,purpose,verifier,expires_at) VALUES(?,?,?,?,?)',[$id,$u['id']??null,$purpose,$verifier,gmdate('Y-m-d H:i:s',time()+300)]);
        if($u&&($purpose==='verify'||$u['verified_at']))query('INSERT INTO outbox(payload) VALUES(?)',[seal(json(['to'=>$address,'subject'=>'Seu código Brasa Burger','text'=>"Seu código é $code. Ele expira em 5 minutos. Não compartilhe. Se não solicitou, ignore esta mensagem."]))]);
    });
    return ['challenge'=>$id,'message'=>'Se o endereço estiver habilitado, você receberá um código.'];
}
function authAction(string $action,array $d): array {
    if($action==='register'){
        mailReady();rate('register-ip',ip(),5,3600);
        $address=email($d['email']??null);$name=text($d['name']??null,100,2);$tel=phone($d['phone']??null);$hash=hashPassword($d['password']??null);
        query('INSERT IGNORE INTO users(email,name,phone,password_hash) VALUES(?,?,?,?)',[$address,$name,$tel,$hash]);
        return challenge($address,'verify');
    }
    if($action==='login'){
        $address=email($d['email']??null);rate('login-ip',ip(),30,900);rate('login-account',$address,8,900);
        $u=one('SELECT * FROM users WHERE email=?',[$address]);$password=passwordInput($d['password']??'');
        $dummy='$argon2id$v=19$m=19456,t=2,p=1$ckJCZ0tWT2M2TWIxZkJJZQ$IoGV23B3DCUQ1/wjWosMDGSMcFTFvihHRHQppadZHu4';
        $ok=password_verify($password,$u['password_hash']??$dummy);
        if(!$u||!$ok||!$u['active']||!$u['verified_at'])fail('Credenciais inválidas ou conta ainda não confirmada.',401);
        if($u['role']!=='customer')verifyTotp($u,$d['totp']??'');
        if(password_needs_rehash($u['password_hash'],PASSWORD_ARGON2ID,['memory_cost'=>19456,'time_cost'=>2,'threads'=>1]))query('UPDATE users SET password_hash=? WHERE id=?',[encodePassword($password),$u['id']]);
        return establish($u);
    }
    if($action==='request-code')return challenge(email($d['email']??null),choice($d['purpose']??'login',['login','verify','reset']));
    if($action==='verify-code'){
        rate('verify-ip',ip(),30,900);$id=text($d['challenge']??null,48,48);$code=text($d['code']??null,6,6);
        $newHash=isset($d['newPassword'])?hashPassword($d['newPassword']):null;
        $result=transaction(function()use($id,$code,$newHash){
            $ch=one('SELECT * FROM challenges WHERE id=? FOR UPDATE',[$id]);
            if(!$ch||$ch['consumed_at']||strtotime($ch['expires_at'])<=time()||(int)$ch['attempts']>=5)return null;
            query('UPDATE challenges SET attempts=attempts+1 WHERE id=?',[$id]);
            if(!$ch['user_id']||!hash_equals($ch['verifier'],hash_hmac('sha256',$id.'|'.$code,appKey())))return null;
            $u=one('SELECT * FROM users WHERE id=? FOR UPDATE',[$ch['user_id']]);
            if(!$u||!$u['active']||$u['role']!=='customer')return null;
            if($ch['purpose']!=='verify'&&!$u['verified_at'])return null;
            if($ch['purpose']==='reset'&&!$newHash)fail('Informe a nova senha.');
            query('UPDATE challenges SET consumed_at=UTC_TIMESTAMP() WHERE id=?',[$id]);
            if($ch['purpose']==='verify')query('UPDATE users SET verified_at=UTC_TIMESTAMP() WHERE id=?',[$u['id']]);
            if($ch['purpose']==='reset'){
                query('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[$newHash,$u['id']]);
                query('UPDATE challenges SET consumed_at=UTC_TIMESTAMP() WHERE user_id=? AND consumed_at IS NULL',[$u['id']]);
                audit('auth.password_reset',(string)$u['id']);
            }
            return one('SELECT * FROM users WHERE id=?',[$u['id']]);
        });
        if(!$result)fail('Código inválido, expirado ou já utilizado.',401);
        return establish($result);
    }
    if($action==='logout'){if(user(false))audit('auth.logout');clearSession();return ['csrf'=>$_SESSION['csrf'],'user'=>null];}
    if($action==='reauth'){
        $u=user();rate('reauth',(string)$u['id'],8,900);
        if(!password_verify(passwordInput($d['password']??''),$u['password_hash']??''))fail('Credenciais inválidas.',401);
        if($u['role']!=='customer')verifyTotp($u,$d['totp']??'');
        $_SESSION['reauth']=time();return ['ok'=>true];
    }
    fail('Operação não encontrada.',404);
}
