<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
Brasa\headers();
$id=$_GET['id']??'';
if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/',$id)||!is_file(Brasa\ROOT.'/storage/media/'.$id.'.json')){http_response_code(404);exit;}
$meta=Brasa\decode(file_get_contents(Brasa\ROOT.'/storage/media/'.$id.'.json'));
if(!in_array($meta['mime'],['image/png','image/jpeg','image/webp'],true)){http_response_code(404);exit;}
header('Content-Type: '.$meta['mime']);header('Content-Length: '.filesize(Brasa\ROOT.'/storage/media/'.$id));header('Content-Security-Policy: sandbox');header('Cache-Control: public, max-age=86400');
$stream=fopen(Brasa\ROOT.'/storage/media/'.$id,'rb');
while(!feof($stream)){echo fread($stream,8192);flush();}
fclose($stream);
