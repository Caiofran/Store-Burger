<?php
// Only for the local PHP development server. Apache uses its own configuration.
declare(strict_types=1);
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)??'/');
if(str_contains($path,'..')||str_contains($path,"\0")||preg_match('~/(?:\.|storage|src|vendor|database|bin)~',$path)){http_response_code(404);exit;}
$file=__DIR__.$path;
if($path==='/')$file=__DIR__.'/index.php';
if($path==='/admin/'||$path==='/admin')$file=__DIR__.'/admin/index.php';
if(is_file($file)&&str_ends_with($file,'.php')){require $file;return true;}
if(is_file($file))return false;
http_response_code(404);echo 'Página não encontrada.';
