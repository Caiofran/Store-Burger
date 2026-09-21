<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
use function Brasa\{headers,sessionStart,csrf,requestBody,rate,ip,fail,user,publicUser,authAction,catalog,makeQuote,placeOrder,query,one,orderView,orderStatus,adminData,adminSave,adminUserSave,paymentReceived,addressAction,uploadImage,choice,text,integer,audit,json,permit};
headers();header('Content-Type: application/json; charset=utf-8');
try {
    sessionStart();$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'';
    if(!is_string($action)||strlen($action)>60)fail('Rota inválida.',404);
    if(!in_array($method,['GET','POST'],true))fail('Método não permitido.',405);
    if($method==='POST')csrf();
    rate('global',ip(),300,60);
    if($method==='GET'){
        $result=match($action){
            'session'=>['csrf'=>$_SESSION['csrf'],'user'=>publicUser(user(false)),'mailEnabled'=>Brasa\env('MAIL_TRANSPORT','disabled')!=='disabled'],
            'catalog'=>catalog(),
            'orders'=>(function(){ $u=permit(['customer']);return array_map(fn($o)=>orderView($o,$u),query('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 100',[$u['id']])->fetchAll());})(),
            'addresses'=>addressAction('list'),
            'admin'=>adminData(text($_GET['kind']??'',24)),
            'track'=>(function(){rate('tracking',ip(),30,60);$token=text($_GET['token']??'',32,32);if(!preg_match('/^[a-f0-9]{32}$/',$token))fail('Pedido não encontrado.',404);$o=one('SELECT id,status,created_at,scheduled_at FROM orders WHERE public_id=?',[$token]);if(!$o||strtotime($o['scheduled_at']?:$o['created_at'])<time()-259200)fail('Acompanhamento indisponível.',404);return ['number'=>(string)(2026+$o['id']),'status'=>$o['status']];})(),
            'health'=>['ok'=>(bool)one('SELECT 1 AS ready')['ready']],
            default=>throw new Brasa\HttpError(404,'Rota não encontrada.')
        };
    } else {
        if($action==='upload')$result=uploadImage();
        else {
            $d=requestBody();
            if(str_starts_with($action,'auth/'))$result=authAction(substr($action,5),$d);
            else $result=match($action){
                'quote'=>makeQuote($d),'order'=>placeOrder($d),'admin/save'=>adminSave($d),'admin/user'=>adminUserSave($d),'admin/status'=>orderStatus($d),'admin/payment'=>paymentReceived($d),
                'address/save'=>addressAction('save',$d),'address/delete'=>addressAction('delete',$d),
                'privacy'=>(function()use($d){$u=permit(['customer']);rate('privacy',(string)$u['id'],3,86400);$kind=choice($d['kind']??null,['access','correction','deletion']);query('INSERT INTO privacy_requests(user_id,kind) VALUES(?,?)',[$u['id'],$kind]);audit('privacy.requested',(string)$u['id'],['kind'=>$kind]);return ['message'=>'Solicitação registrada. A equipe responsável entrará em contato.'];})(),
                default=>throw new Brasa\HttpError(404,'Rota não encontrada.')
            };
        }
    }
    echo json(['ok'=>true,'data'=>$result]);
}catch(Brasa\HttpError $e){http_response_code($e->status);echo json(['ok'=>false,'message'=>$e->getMessage()]);}
catch(Throwable $e){
    if(isset($GLOBALS['pdo'])&&Brasa\db()->inTransaction())Brasa\db()->rollBack();
    $id=bin2hex(random_bytes(8));error_log(json_encode(['event'=>'application.error','correlation'=>$id,'type'=>get_class($e)]));
    http_response_code(503);echo json(['ok'=>false,'message'=>'Serviço temporariamente indisponível. Tente novamente.','reference'=>$id]);
}
