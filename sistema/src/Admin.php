<?php
declare(strict_types=1);
namespace Brasa;

function mediaPath(mixed $v): string {
    $s=text($v,180);if($s==='')return '';
    if(preg_match('~^/assets/([a-z0-9-]+\.(?:png|jpg|webp))$~',$s,$m)&&is_file(ROOT.'/public/assets/'.$m[1]))return $s;
    if(preg_match('~^/media\.php\?id=([a-f0-9]{32})$~',$s,$m)&&is_file(ROOT.'/storage/media/'.$m[1].'.json'))return $s;
    fail('Imagem inválida. Envie o arquivo pelo painel.');
}
function boolField(array $d,string $key,bool $default=false): bool { $v=$d[$key]??$default;if(!is_bool($v))fail('Opção inválida.');return $v; }
function validateRecord(string $kind,array $d,string $id): array {
    if($kind==='settings'){
        $hours=$d['hours']??null;if(!is_array($hours)||count($hours)!==7||!array_is_list($hours))fail('Informe os sete dias da semana.');
        $clean=[];foreach($hours as $h){if(!is_array($h))fail('Horário inválido.');$start=text($h['start']??'',5,5);$end=text($h['end']??'',5,5);if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$start)||!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$end)||$end<=$start)fail('Confira abertura e fechamento.');$clean[]=['open'=>boolField($h,'open'),'start'=>$start,'end'=>$end];}
        return ['name'=>text($d['name']??null,100,2),'phone'=>phone($d['phone']??null),'address'=>text($d['address']??null,150,5),'logo'=>mediaPath($d['logo']??''),'paused'=>boolField($d,'paused'),'scheduled'=>boolField($d,'scheduled'),'minimum'=>integer($d['minimum']??0),'slotCapacity'=>integer($d['slotCapacity']??10,1,100),'hours'=>$clean,'timezone'=>'America/Sao_Paulo','privacyEmail'=>email($d['privacyEmail']??null)];
    }
    $r=['name'=>text($d['name']??null,100,1),'active'=>boolField($d,'active',true)];
    if($kind==='categories')return $r+['position'=>integer($d['position']??0,0,100)];
    if($kind==='products'){
        $cat=text($d['category']??null,64,1);if(!record('categories',$cat))fail('Categoria não encontrada.');$groups=selected($d['groups']??[],10);foreach($groups as $g)if(!record('extras',$g))fail('Grupo de adicionais não encontrado.');
        $image=mediaPath($d['image']??'');if($image==='')fail('Envie uma imagem para o produto.');
        return $r+['price'=>integer($d['price']??null,1,100000),'category'=>$cat,'description'=>text($d['description']??null,800,3),'badge'=>text($d['badge']??'',40),'featured'=>boolField($d,'featured'),'ingredients'=>selected($d['ingredients']??[],20),'groups'=>$groups,'image'=>$image];
    }
    if($kind==='extras'){
        $type=choice($d['type']??null,['Única','Múltipla']);$min=integer($d['min']??0,0,20);$max=integer($d['max']??null,1,20);$options=$d['options']??null;
        if(!is_array($options)||!array_is_list($options)||count($options)<1||count($options)>20||$min>$max||$max>count($options)||($type==='Única'&&$max!==1))fail('Confira os limites e as opções do grupo.');
        $clean=[];foreach($options as $i=>$o){if(!is_array($o))fail('Adicional inválido.');$oid=text($o['id']??($id.'-'.bin2hex(random_bytes(4))),64,1);if(!preg_match('/^[a-z0-9-]+$/',$oid))fail('Identificador inválido.');$clean[]=['id'=>$oid,'name'=>text($o['name']??null,70,1),'price'=>integer($o['price']??null,0,100000)];}
        $ids=array_column($clean,'id');if(count($ids)!==count(array_unique($ids)))fail('Adicionais repetidos.');foreach(records('extras') as $g)if($g['id']!==$id&&array_intersect($ids,array_column($g['options'],'id')))fail('Identificador já usado por outro grupo.');
        return $r+['type'=>$type,'min'=>$min,'max'=>$max,'options'=>$clean];
    }
    if($kind==='coupons'){
        $r['name']=strtoupper($r['name']);if(!preg_match('/^[A-Z0-9_-]{3,24}$/',$r['name']))fail('Código de cupom inválido.');foreach(records('coupons')as $c)if($c['id']!==$id&&$c['name']===$r['name'])fail('Código de cupom já cadastrado.');
        $expiry=text($d['expires']??'',10);if($expiry!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$expiry)||!\DateTimeImmutable::createFromFormat('!Y-m-d',$expiry)||\DateTimeImmutable::createFromFormat('!Y-m-d',$expiry)->format('Y-m-d')!==$expiry))fail('Data de validade inválida.');
        return $r+['discount'=>integer($d['discount']??null,1,100),'minimum'=>integer($d['minimum']??0),'limit'=>integer($d['limit']??1,1,100),'expires'=>$expiry,'description'=>text($d['description']??'',400)];
    }
    if($kind==='areas')return $r+['fee'=>integer($d['fee']??null,0,100000),'minimum'=>integer($d['minimum']??0),'time'=>text($d['time']??null,30,3)];
    if($kind==='banners')return $r+['subtitle'=>text($d['subtitle']??'',250),'cta'=>text($d['cta']??null,50,1),'link'=>choice($d['link']??'#cardapio',['#cardapio','#promocoes','#duvidas']),'image'=>mediaPath($d['image']??'')];
    fail('Coleção inválida.',404);
}
function adminData(string $kind): array {
    $u=permit(['kitchen','operator','manager','admin']);
    if($kind==='orders')return array_map(fn($o)=>orderView($o,$u),query('SELECT * FROM orders ORDER BY id DESC LIMIT 200')->fetchAll());
    if($kind==='users'){permit(['admin']);return query('SELECT id,name,email,phone,role,active,session_version AS revision FROM users WHERE role<>? ORDER BY id',['customer'])->fetchAll();}
    if($kind==='privacy'){permit(['admin']);return query('SELECT p.id,p.user_id,p.kind,p.status,p.created_at FROM privacy_requests p ORDER BY p.id DESC LIMIT 100')->fetchAll();}
    if($kind==='audit'){permit(['admin']);return query('SELECT id,actor_id,action,object_id,details,created_at FROM audit_events ORDER BY id DESC LIMIT 200')->fetchAll();}
    permit(['manager','admin']);choice($kind,['products','categories','extras','coupons','areas','banners','settings']);return records($kind);
}
function adminSave(array $d): array {
    $u=permit(['manager','admin']);$kind=choice($d['kind']??null,['products','categories','extras','coupons','areas','banners','settings']);
    if($kind==='settings'){permit(['admin']);recent();}
    $id=text($d['id']??'',64);if($id==='')$id=$kind.'-'.bin2hex(random_bytes(6));if(!preg_match('/^[a-z0-9-]{1,64}$/',$id))fail('Identificador inválido.');
    $data=$d['data']??null;if(!is_array($data))fail('Dados inválidos.');
    return transaction(function()use($kind,$id,$data,$d){
        query('SELECT revision FROM catalog_lock WHERE id=1 FOR UPDATE');$old=record($kind,$id);
        if($kind==='settings'&&$id!=='store')fail('Configuração inválida.');
        if($old&&($d['revision']??null)!==$old['revision'])fail('Este registro foi alterado. Atualize antes de salvar.',409);
        $clean=validateRecord($kind,$data,$id);
        query('INSERT INTO catalog(kind,id,data,revision) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE data=VALUES(data),revision=revision+1',[$kind,$id,json($clean)]);
        query('UPDATE catalog_lock SET revision=revision+1 WHERE id=1');audit('catalog.saved',$kind.':'.$id,['revision'=>($old['revision']??0)+1]);return record($kind,$id);
    });
}
function adminUserSave(array $d): array {
    $actor=permit(['admin']);recent();$id=integer($d['id']??0,0,PHP_INT_MAX);
    $name=text($d['name']??null,100,2);$address=email($d['email']??null);$role=choice($d['role']??null,['kitchen','operator','manager','admin']);$active=boolField($d,'active',true);
    return transaction(function()use($d,$actor,$id,$name,$address,$role,$active){
        query('SELECT revision FROM catalog_lock WHERE id=1 FOR UPDATE');
        if($id){
            $u=one('SELECT * FROM users WHERE id=? FOR UPDATE',[$id]);if(!$u||$u['role']==='customer')fail('Administrador não encontrado.',404);
            if((int)$u['session_version']!==($d['revision']??null))fail('Registro desatualizado.',409);
            if($id===(int)$actor['id']&&(!$active||$role!=='admin'))fail('Você não pode remover o próprio acesso.');
            if($address!==$u['email'])fail('A alteração de e-mail administrativo exige reprovisionamento pelo responsável técnico.');
            if($u['role']==='admin'&&(!$active||$role!=='admin')&&(int)one("SELECT COUNT(*) n FROM users WHERE role='admin' AND active=1")['n']<=1)fail('Mantenha pelo menos um administrador ativo.');
            query('UPDATE users SET name=?,role=?,active=?,session_version=session_version+1 WHERE id=?',[$name,$role,(int)$active,$id]);audit('admin.updated',(string)$id,['role'=>$role,'active'=>$active]);
            if($id===(int)$actor['id'])$_SESSION['version']++;
            return ['ok'=>true];
        }
        $hash=hashPassword($d['password']??null);if(one('SELECT id FROM users WHERE email=?',[$address]))fail('E-mail já cadastrado.');
        $secret=base32(random_bytes(20));query('INSERT INTO users(email,name,password_hash,role,active,verified_at,totp_secret) VALUES(?,?,?,?,?,UTC_TIMESTAMP(),?)',[$address,$name,$hash,$role,(int)$active,seal($secret)]);$id=db()->lastInsertId();audit('admin.created',$id,['role'=>$role]);return ['ok'=>true,'enrollment'=>$secret,'message'=>'Entregue esta chave de segundo fator por canal seguro. Ela não será exibida novamente.'];
    });
}
function paymentReceived(array $d): array {
    $u=permit(['manager','admin']);recent();$id=integer($d['id']??null,1,PHP_INT_MAX);$revision=integer($d['revision']??null,1);$reason=text($d['reason']??null,160,5);
    return transaction(function()use($id,$revision,$reason,$u){$o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);if(!$o)fail('Pedido não encontrado.',404);if($o['payment_status']!=='pending'||in_array($o['status'],['Cancelado','Recusado'],true))fail('Recebimento não permitido neste estado.');if((int)$o['revision']!==$revision)fail('Atualize o pedido.',409);query("UPDATE orders SET payment_status='received',revision=revision+1 WHERE id=?",[$id]);audit('payment.received',(string)$id,['reason'=>$reason]);return orderView(one('SELECT * FROM orders WHERE id=?',[$id]),$u);});
}
function uploadImage(): array {
    $u=permit(['manager','admin']);rate('upload',(string)$u['id'],12,3600);
    $f=$_FILES['image']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK||$f['size']>3*1024*1024||!is_uploaded_file($f['tmp_name']))fail('Envie uma imagem de até 3 MB.');
    $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);if(!in_array($mime,['image/png','image/jpeg','image/webp'],true))fail('Envie PNG, JPEG ou WebP. SVG exige revisão fora do upload.');
    $info=@getimagesize($f['tmp_name']);if(!$info||$info[0]>6000||$info[1]>6000||$info[0]*$info[1]>12000000)fail('A imagem excede os limites de dimensões.');
    $bytes=file_get_contents($f['tmp_name']);$image=@imagecreatefromstring($bytes);if(!$image)fail('O arquivo não é uma imagem válida.');imagedestroy($image);
    $id=bin2hex(random_bytes(16));$dir=ROOT.'/storage/media';if(!is_dir($dir))mkdir($dir,0700,true);
    if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$id))throw new \RuntimeException('Upload write failed');
    chmod($dir.'/'.$id,0600);file_put_contents($dir.'/'.$id.'.json',json(['mime'=>$mime,'size'=>strlen($bytes)]),LOCK_EX);audit('media.uploaded',$id,['bytes'=>strlen($bytes)]);
    return ['url'=>'/media.php?id='.$id];
}
