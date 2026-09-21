<?php
declare(strict_types=1);
namespace Brasa;

function headers(): void {
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store');
    if(!local())header('Strict-Transport-Security: max-age=31536000');
    header_remove('X-Powered-By');
}
function sessionStart(): void {
    appKey();
    $url=env('APP_URL');
    if(!filter_var($url,FILTER_VALIDATE_URL)||(!local()&&parse_url($url,PHP_URL_SCHEME)!=='https'))throw new \RuntimeException('Configure HTTPS APP_URL');
    ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.use_trans_sid','0');
    session_save_path(privateDir('sessions'));session_name('brasa_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!local(),'httponly'=>true,'samesite'=>'Lax']);
    session_start();
    $now=time();$limit=($_SESSION['role']??'customer')==='customer'?3600:900;
    if(isset($_SESSION['uid'])&&(($now-($_SESSION['last']??0))>$limit||($now-($_SESSION['started']??0))>28800))clearSession();
    $_SESSION['last']=$now;$_SESSION['csrf']??=bin2hex(random_bytes(32));
}
function clearSession(): void { $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32)); }
function csrf(): void {
    $token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
    if(!is_string($token)||!hash_equals($_SESSION['csrf']??'', $token)||$token==='')fail('Sua sessão mudou. Atualize a página.',403);
    $origin=$_SERVER['HTTP_ORIGIN']??'';
    if($origin!==''&&!hash_equals(rtrim(env('APP_URL'),'/'),$origin))fail('Origem não permitida.',403);
    if(($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site')fail('Origem não permitida.',403);
}
function user(bool $required=true): ?array {
    $u=isset($_SESSION['uid'])?one('SELECT * FROM users WHERE id=?',[$_SESSION['uid']]):null;
    if($u&&(!$u['active']||(int)$u['session_version']!==($_SESSION['version']??0))) { clearSession();$u=null; }
    if(!$u&&$required)fail('Entre na sua conta para continuar.',401);
    return $u;
}
function publicUser(?array $u): ?array { return $u?['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'phone'=>$u['phone'],'role'=>$u['role'],'verified'=>(bool)$u['verified_at']]:null; }
function permit(array $roles): array { $u=user();if(!in_array($u['role'],$roles,true))fail('Acesso não autorizado.',403);return $u; }
function recent(): void { if(time()-($_SESSION['reauth']??0)>300)fail('Confirme sua senha e código de segurança novamente.',428); }
function ip(): string {
    $remote=$_SERVER['REMOTE_ADDR']??'cli';$trusted=array_filter(array_map('trim',explode(',',env('TRUSTED_PROXIES'))));
    if(!in_array($remote,$trusted,true))return $remote;
    $chain=explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']??'');
    for($i=count($chain)-1;$i>=0;$i--){$candidate=trim($chain[$i]);if(!filter_var($candidate,FILTER_VALIDATE_IP))return $remote;if(!in_array($candidate,$trusted,true))return $candidate;}
    return $remote;
}
function rate(string $scope,string $identity,int $max,int $seconds): void {
    $window=(int)floor(time()/$seconds);$key=hash_hmac('sha256',$scope.'|'.$identity.'|'.$window,appKey());
    query('INSERT INTO rate_limits(bucket,hits,expires_at) VALUES(?,1,?) ON DUPLICATE KEY UPDATE hits=hits+1',[$key,gmdate('Y-m-d H:i:s',($window+2)*$seconds)]);
    $hits=(int)one('SELECT hits FROM rate_limits WHERE bucket=?',[$key])['hits'];
    if($hits>$max){header('Retry-After: '.max(1,(($window+1)*$seconds-time())));fail('Muitas tentativas. Aguarde antes de tentar novamente.',429);}
}
function requestBody(): array {
    if((int)($_SERVER['CONTENT_LENGTH']??0)>65536)fail('Solicitação muito grande.',413);
    if(!str_starts_with($_SERVER['CONTENT_TYPE']??'','application/json'))fail('Formato não suportado.',415);
    $raw=file_get_contents('php://input',false,null,0,65537);if(strlen($raw)>65536)fail('Solicitação muito grande.',413);
    try{$v=json_decode($raw,true,20,JSON_THROW_ON_ERROR);}catch(\JsonException){fail('Solicitação inválida.',400);}
    if(!is_array($v)||array_is_list($v)&&$v!==[])fail('Solicitação inválida.',400);return $v;
}
function base32(string $bytes): string { $bits='';foreach(str_split($bytes) as $c)$bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);$out='';foreach(str_split($bits,5)as $c)$out.='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'[bindec(str_pad($c,5,'0'))];return $out; }
function from32(string $s): string { $bits='';foreach(str_split(strtoupper($s))as $c){$n=strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',$c);if($n===false)throw new \RuntimeException('Invalid TOTP secret');$bits.=str_pad(decbin($n),5,'0',STR_PAD_LEFT);} $out='';foreach(str_split($bits,8)as $c)if(strlen($c)===8)$out.=chr(bindec($c));return $out; }
function totp(string $secret,int $step,int $digits=6): string { $hash=hash_hmac('sha1',pack('N2',intdiv($step,4294967296),$step%4294967296),from32($secret),true);$offset=ord($hash[19])&15;$n=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;return str_pad((string)($n%(10**$digits)),$digits,'0',STR_PAD_LEFT); }
function verifyTotp(array $u,mixed $code): void {
    if(!is_string($code)||!preg_match('/^\d{6}$/',$code)||!$u['totp_secret'])fail('Credenciais inválidas.',401);
    $step=(int)floor(time()/30);$secret=unseal($u['totp_secret']);
    for($i=-1;$i<=1;$i++)if($step+$i>(int)$u['totp_last_step']&&hash_equals(totp($secret,$step+$i),$code)){
        $s=query('UPDATE users SET totp_last_step=? WHERE id=? AND totp_last_step<?',[$step+$i,$u['id'],$step+$i]);
        if($s->rowCount()===1)return;
    }
    fail('Código de segurança inválido ou já utilizado.',401);
}
