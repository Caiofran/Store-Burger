<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
if(PHP_SAPI!=='cli'||!Brasa\local())exit(1);
$action=$argv[1]??'';
if($action==='peek'){
    $email=Brasa\email($argv[2]??'');
    foreach(Brasa\query("SELECT payload FROM outbox WHERE status='pending' ORDER BY id DESC LIMIT 30")->fetchAll()as $r){$p=Brasa\decode(Brasa\unseal($r['payload']));if($p['to']===$email){preg_match('/\b(\d{6})\b/',$p['text'],$m);echo $m[1]??'';exit;}}
    exit(2);
}
if($action==='expire')Brasa\query('UPDATE challenges SET expires_at=UTC_TIMESTAMP()-INTERVAL 1 SECOND WHERE id=?',[$argv[2]??'']);
if($action==='counter')echo Brasa\one('SELECT attempts FROM challenges WHERE id=?',[$argv[2]??''])['attempts'];
