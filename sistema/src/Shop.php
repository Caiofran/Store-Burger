<?php
declare(strict_types=1);
namespace Brasa;

const STATUSES=['Recebido','Confirmado','Em preparação','Saiu para entrega','Entregue','Cancelado','Recusado'];
function records(string $kind,bool $onlyActive=false): array {
    $list=[];foreach(query('SELECT id,data,revision FROM catalog WHERE kind=? ORDER BY id',[$kind])->fetchAll()as $r){$d=decode($r['data']);$d['id']=$r['id'];$d['revision']=(int)$r['revision'];if(!$onlyActive||($d['active']??false))$list[]=$d;}return $list;
}
function record(string $kind,string $id): ?array { $r=one('SELECT data,revision FROM catalog WHERE kind=? AND id=?',[$kind,$id]);if(!$r)return null;$d=decode($r['data']);$d['id']=$id;$d['revision']=(int)$r['revision'];return $d; }
function settings(): array { return record('settings','store')??throw new \RuntimeException('Run migrations first'); }
function opening(array $s,\DateTimeImmutable $date): bool {
    $day=(int)$date->format('N')-1;$h=$s['hours'][$day];$time=$date->format('H:i');
    return (bool)$h['open']&&$time>=$h['start']&&$time<$h['end'];
}
function catalog(): array {
    $s=settings();$cats=records('categories',true);usort($cats,fn($a,$b)=>$a['position']<=>$b['position']);$ids=array_column($cats,'id');
    return ['products'=>array_values(array_filter(records('products',true),fn($p)=>in_array($p['category'],$ids,true))), 'categories'=>$cats,'groups'=>records('extras',true),'areas'=>records('areas',true),'banners'=>records('banners',true), 'store'=>$s,'open'=>!$s['paused']&&opening($s,new \DateTimeImmutable('now',new \DateTimeZone('America/Sao_Paulo')))];
}
function selected(mixed $v,int $max=30): array { if(!is_array($v)||!array_is_list($v)||count($v)>$max)fail('Seleção inválida.');$out=[];foreach($v as $i)$out[]=text($i,100,1);if(count(array_unique($out))!==count($out))fail('Seleções repetidas não são permitidas.');return $out; }
function calculate(array $d,?array $u): array {
    $s=settings();$cart=$d['cart']??null;if(!is_array($cart)||!array_is_list($cart)||count($cart)<1||count($cart)>30)fail('Escolha de 1 a 30 itens.');
    $items=[];$subtotal=0;$quantity=0;
    foreach($cart as $i){
        if(!is_array($i))fail('Item inválido.');$id=text($i['productId']??null,64,1);$p=record('products',$id);$category=$p?record('categories',$p['category']):null;
        if(!$p||!$p['active']||!$category||!$category['active'])fail('Um produto não está mais disponível. Atualize o cardápio.');
        $qty=integer($i['qty']??null,1,20);$quantity+=$qty;if($quantity>60)fail('O limite é de 60 unidades por pedido.');
        $extras=selected($i['extras']??[]);$removed=selected($i['removed']??[]);$note=text($i['note']??'',240);
        if(array_diff($removed,$p['ingredients']))fail('Ingrediente não pertence ao produto.');
        $options=[];$allowed=[];$unit=(int)$p['price'];
        foreach($p['groups']as $gid){$g=record('extras',$gid);if(!$g||!$g['active'])continue;$groupIds=array_column($g['options'],'id');$picked=array_values(array_intersect($extras,$groupIds));$count=count($picked);if($count<$g['min']||$count>$g['max']||($g['type']==='Única'&&$count>1))fail('Confira as escolhas de '.$g['name'].'.');$allowed=array_merge($allowed,$groupIds);foreach($g['options']as $option)if(in_array($option['id'],$picked,true)){$unit+=$option['price'];$options[]=$option;}}
        if(array_diff($extras,$allowed))fail('Adicional não permitido para este produto.');
        $line=$unit*$qty;$subtotal+=$line;if($subtotal>1000000)fail('Pedido acima do limite permitido.');
        $items[]=['productId'=>$id,'name'=>$p['name'],'image'=>$p['image'],'qty'=>$qty,'unit'=>$unit,'line'=>$line,'extras'=>$options,'removed'=>$removed,'note'=>$note];
    }
    $mode=choice($d['mode']??null,['delivery','pickup']);$area=null;$address=null;$fee=0;
    if($mode==='delivery'){
        if(isset($d['addressId'])&&$d['addressId']!==''){
            if(!$u)fail('Entre na sua conta.',401);$r=one('SELECT data FROM addresses WHERE id=? AND user_id=?',[text($d['addressId'],32,32),$u['id']]);if(!$r)fail('Endereço não encontrado.',404);$a=decode($r['data']);
        }else $a=$d['address']??[];
        if(!is_array($a))fail('Endereço inválido.');
        $area=record('areas',text($a['areaId']??null,64,1));if(!$area||!$area['active'])fail('Bairro não atendido.');
        $address=['areaId'=>$area['id'],'neighborhood'=>$area['name'],'street'=>text($a['street']??null,120,3),'number'=>text($a['number']??null,12,1),'complement'=>text($a['complement']??'',70)];$fee=$area['fee'];
    }
    $minimum=max($s['minimum'],$area['minimum']??0);if($subtotal<$minimum)fail('O pedido não atingiu o valor mínimo para esta modalidade.');
    $code=strtoupper(text($d['coupon']??'',24));$discount=0;$coupon=null;
    if($code!==''){
        foreach(records('coupons',true)as $c)if($c['name']===$code){$coupon=$c;break;}
        if(!$coupon||($coupon['expires']!==''&&$coupon['expires']<gmdate('Y-m-d'))||$subtotal<$coupon['minimum'])fail('Cupom indisponível para este pedido.');
        if($u){$used=one('SELECT uses FROM coupon_uses WHERE coupon_id=? AND user_id=?',[$coupon['id'],$u['id']]);if(($used['uses']??0)>=$coupon['limit'])fail('Você já utilizou este cupom o número permitido de vezes.');}
        $discount=intdiv($subtotal*$coupon['discount']+50,100);
    }
    $timing=choice($d['timing']??'now',['now','scheduled']);$now=new \DateTimeImmutable('now',new \DateTimeZone('America/Sao_Paulo'));$when=null;
    if($s['paused'])fail('Estamos com os pedidos pausados no momento.');
    if($timing==='scheduled'){
        if(!$s['scheduled'])fail('Agendamento indisponível.');$raw=text($d['schedule']??null,16,16);$when=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$raw,new \DateTimeZone('America/Sao_Paulo'));
        if(!$when||$when->format('Y-m-d\TH:i')!==$raw||$when<$now->modify('+30 minutes')||$when>$now->modify('+7 days')||!opening($s,$when))fail('Agende com 30 minutos de antecedência, em até 7 dias, dentro do expediente.');
    }elseif(!opening($s,$now))fail('Estamos fora do expediente. Escolha um horário de agendamento.');
    $payment=choice($d['payment']??null,['Pix','Dinheiro','Débito','Crédito']);$total=$subtotal-$discount+$fee;$change=isset($d['change'])?integer($d['change'],0,1000000):0;
    if($payment==='Dinheiro'&&$change!==0&&$change<$total)fail('O valor para troco deve cobrir o total.');if($payment!=='Dinheiro')$change=0;
    return ['items'=>$items,'totals'=>['subtotal'=>$subtotal,'discount'=>$discount,'fee'=>$fee,'total'=>$total], 'coupon'=>$code,'couponId'=>$coupon['id']??null,'mode'=>$mode,'address'=>$address,'payment'=>$payment,'change'=>$change,'timing'=>$timing,'schedule'=>$when?->format('Y-m-d\TH:i'),'scheduledUtc'=>$when?->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'estimate'=>$mode==='pickup'?'25–35 min':($area['time']??'35–50 min'),'pickupAddress'=>$s['address']];
}
function makeQuote(array $d): array {
    $u=user(false);rate('quote',session_id(),40,60);
    $result=transaction(function()use($d,$u){query('SELECT revision FROM catalog_lock WHERE id=1 FOR SHARE');return calculate($d,$u);});
    $id=bin2hex(random_bytes(24));$_SESSION['quotes']??=[];
    foreach($_SESSION['quotes']as $k=>$q)if($q['expires']<time())unset($_SESSION['quotes'][$k]);
    if(count($_SESSION['quotes'])>=5)array_shift($_SESSION['quotes']);
    $_SESSION['quotes'][$id]=['input'=>$d,'result'=>$result,'uid'=>$u['id']??null,'expires'=>time()+300];
    return ['quote'=>$id,'expires'=>300]+$result;
}
function placeOrder(array $d): array {
    $u=permit(['customer']);if(!$u['verified_at'])fail('Confirme seu e-mail para pedir.',403);rate('order-user',(string)$u['id'],8,300);rate('order-ip',ip(),20,300);
    $qid=text($d['quote']??null,48,48);$key=text($d['requestKey']??null,64,32);if(!preg_match('/^[a-f0-9]{32,64}$/',$key))fail('Identificador de confirmação inválido.');
    $hash=hash('sha256',$qid);$existing=one('SELECT * FROM orders WHERE user_id=? AND request_key=?',[$u['id'],$key]);
    if($existing){if(!hash_equals($existing['request_hash'],$hash))fail('Esta confirmação já foi usada em outro pedido.',409);return orderView($existing,$u);}
    $quote=$_SESSION['quotes'][$qid]??null;if(!$quote||$quote['expires']<time()||(int)$quote['uid']!==(int)$u['id'])fail('Sua revisão expirou. Revise novamente o pedido.',409);
    return transaction(function()use($quote,$u,$key,$hash){
        query('SELECT revision FROM catalog_lock WHERE id=1 FOR UPDATE');
        $existing=one('SELECT * FROM orders WHERE user_id=? AND request_key=?',[$u['id'],$key]);if($existing){if(!hash_equals($existing['request_hash'],$hash))fail('Confirmação já utilizada.',409);return orderView($existing,$u);}
        $fresh=calculate($quote['input'],$u);if(!hash_equals(hash('sha256',json($quote['result'])),hash('sha256',json($fresh))))fail('O pedido mudou. Revise os valores antes de confirmar.',409);
        if($fresh['scheduledUtc']){ $count=(int)one('SELECT COUNT(*) AS n FROM orders WHERE scheduled_at=? AND status NOT IN (?,?)',[$fresh['scheduledUtc'],'Cancelado','Recusado'])['n'];if($count>=settings()['slotCapacity'])fail('Esse horário está cheio. Escolha outro horário.'); }
        $fresh['customer']=['name'=>$u['name'],'phone'=>$u['phone'],'email'=>$u['email']];
        $publicId=bin2hex(random_bytes(16));query('INSERT INTO orders(public_id,user_id,request_key,request_hash,snapshot,total_cents,scheduled_at) VALUES(?,?,?,?,?,?,?)',[$publicId,$u['id'],$key,$hash,json($fresh),$fresh['totals']['total'],$fresh['scheduledUtc']]);$id=db()->lastInsertId();
        if($fresh['couponId'])query('INSERT INTO coupon_uses(coupon_id,user_id,uses) VALUES(?,?,1) ON DUPLICATE KEY UPDATE uses=uses+1',[$fresh['couponId'],$u['id']]);
        audit('order.created',$id,['total'=>$fresh['totals']['total']]);return orderView(one('SELECT * FROM orders WHERE id=?',[$id]),$u);
    });
}
function orderView(array $o,array $u): array {
    $s=decode($o['snapshot']);$out=['id'=>(int)$o['id'],'number'=>(string)(2026+(int)$o['id']),'publicId'=>$o['public_id'],'status'=>$o['status'],'paymentStatus'=>$o['payment_status'],'revision'=>(int)$o['revision'],'created'=>$o['created_at'].'Z']+$s;
    if($u['role']==='kitchen'){
        $out=array_intersect_key($out,array_flip(['id','number','status','revision','created','items','mode','schedule']));
        $out['items']=array_map(function($item){$safe=array_intersect_key($item,array_flip(['name','qty','removed','note']));$safe['extras']=array_map(fn($e)=>['name'=>$e['name']],$item['extras']);return $safe;},$out['items']);
    }
    return $out;
}
function orderStatus(array $d): array {
    $u=permit(['operator','manager','admin','kitchen']);$id=integer($d['id']??null,1,PHP_INT_MAX);$next=choice($d['status']??null,STATUSES);$revision=integer($d['revision']??null,1);$reason=text($d['reason']??'',160);
    return transaction(function()use($u,$id,$next,$revision,$reason,$d){
        $o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);if(!$o)fail('Pedido não encontrado.',404);if((int)$o['revision']!==$revision)fail('Outro membro da equipe atualizou o pedido. Atualize a tela.',409);
        $map=['Recebido'=>['Confirmado','Recusado','Cancelado'],'Confirmado'=>['Em preparação','Cancelado'],'Em preparação'=>['Saiu para entrega','Cancelado'],'Saiu para entrega'=>['Entregue','Cancelado']];
        if(!in_array($next,$map[$o['status']]??[],true))fail('Mudança de status não permitida.');
        if($u['role']==='kitchen'&&!in_array($next,['Em preparação','Saiu para entrega'],true))fail('Seu perfil não pode executar esta ação.',403);
        if(in_array($next,['Cancelado','Recusado'],true)&&($reason===''||!in_array($u['role'],['manager','admin'],true)))fail('Cancelamentos exigem gerente e motivo.',403);
        $snapshot=decode($o['snapshot']);if($next==='Confirmado'&&$snapshot['mode']==='delivery'&&($d['addressChecked']??false)!==true)fail('Confira o endereço e o bairro antes de confirmar.');
        query('UPDATE orders SET status=?,revision=revision+1 WHERE id=?',[$next,$id]);audit('order.status',(string)$id,['from'=>$o['status'],'to'=>$next,'reason'=>$reason]);return orderView(one('SELECT * FROM orders WHERE id=?',[$id]),$u);
    });
}
function addressAction(string $action,array $d=[]): array {
    $u=permit(['customer']);
    if($action==='list')return array_map(fn($r)=>['id'=>$r['id']]+decode($r['data']),query('SELECT id,data FROM addresses WHERE user_id=?',[$u['id']])->fetchAll());
    if($action==='delete'){query('DELETE FROM addresses WHERE id=? AND user_id=?',[text($d['id']??null,32,32),$u['id']]);return ['ok'=>true];}
    $a=$d['address']??[];$area=record('areas',text($a['areaId']??null,64,1));if(!$area||!$area['active'])fail('Bairro não atendido.');
    $value=['areaId'=>$area['id'],'street'=>text($a['street']??null,120,3),'number'=>text($a['number']??null,12,1),'complement'=>text($a['complement']??'',70)];
    $id=bin2hex(random_bytes(16));if((int)one('SELECT COUNT(*) n FROM addresses WHERE user_id=?',[$u['id']])['n']>=10)fail('Você pode salvar até dez endereços.');
    query('INSERT INTO addresses(id,user_id,data) VALUES(?,?,?)',[$id,$u['id'],json($value)]);return ['id'=>$id]+$value;
}
