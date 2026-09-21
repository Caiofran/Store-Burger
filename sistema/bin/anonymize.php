<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';
if($argc!==3||$argv[1]!=='--approved-request'||!ctype_digit($argv[2]))throw new RuntimeException('Uso restrito: php bin/anonymize.php --approved-request ID. Execute somente após decisão documentada do controlador.');
$request=(int)$argv[2];
Brasa\transaction(function()use($request){
    $r=Brasa\one("SELECT * FROM privacy_requests WHERE id=? AND kind='deletion' AND status='pending' FOR UPDATE",[$request]);if(!$r)throw new RuntimeException('Solicitação de exclusão pendente não encontrada');
    $u=Brasa\one('SELECT * FROM users WHERE id=? FOR UPDATE',[$r['user_id']]);if(!$u||$u['role']!=='customer')throw new RuntimeException('Somente contas de clientes');
    Brasa\query('DELETE FROM addresses WHERE user_id=?',[$u['id']]);Brasa\query('DELETE FROM challenges WHERE user_id=?',[$u['id']]);
    foreach(Brasa\query('SELECT id,snapshot FROM orders WHERE user_id=? FOR UPDATE',[$u['id']])->fetchAll()as $o){$s=Brasa\decode($o['snapshot']);$s['customer']=['name'=>'Cliente anonimizado','email'=>'','phone'=>''];if($s['address'])foreach(['street','number','complement']as $k)$s['address'][$k]='';foreach($s['items']as &$item)$item['note']='';unset($item);Brasa\query('UPDATE orders SET snapshot=?,public_id=?,revision=revision+1 WHERE id=?',[Brasa\json($s),bin2hex(random_bytes(16)),$o['id']]);}
    Brasa\query("UPDATE users SET email=?,name='Cliente anonimizado',phone='',password_hash=NULL,active=0,session_version=session_version+1,totp_secret=NULL WHERE id=?",['removed-'.bin2hex(random_bytes(12)).'@invalid.example',$u['id']]);
    Brasa\query("UPDATE privacy_requests SET status='completed' WHERE id=?",[$request]);Brasa\audit('privacy.anonymized',(string)$u['id'],['request'=>$request]);
});
echo "Dados operacionais de identificação removidos. Dados financeiros mínimos e auditoria preservados para a política aplicável. Mantenha o registro de exclusão fora do backup e reaplique-o após eventual restauração; acompanhe a expiração das cópias antigas.\n";
