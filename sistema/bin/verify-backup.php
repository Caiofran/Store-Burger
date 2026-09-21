<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';
if($argc!==3)throw new RuntimeException('Uso: php bin/verify-backup.php arquivo.backup diretorio-privado-vazio');
$hex=Brasa\env('BACKUP_KEY');if(!preg_match('/^[a-f0-9]{64}$/i',$hex))throw new RuntimeException('BACKUP_KEY required');
$destination=$argv[2];if(!is_dir($destination)||count(array_diff(scandir($destination),['.','..']))!==0)throw new RuntimeException('Destination must exist and be empty');
$tarPath=$destination.'/verified.tar';$in=fopen($argv[1],'rb');if(fread($in,6)!=='BRASA1')throw new RuntimeException('Invalid backup');
$header=fread($in,SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);$state=sodium_crypto_secretstream_xchacha20poly1305_init_pull($header,hex2bin($hex));$out=fopen($tarPath,'xb');$final=false;
try{
    while(!feof($in)){$length=fread($in,4);if($length==='')break;if(strlen($length)!==4)throw new RuntimeException('Truncated backup');$n=unpack('N',$length)[1];if($n<17||$n>1024*1024+17)throw new RuntimeException('Invalid chunk');$cipher='';while(strlen($cipher)<$n&&!feof($in))$cipher.=fread($in,$n-strlen($cipher));$r=sodium_crypto_secretstream_xchacha20poly1305_pull($state,$cipher);if($r===false)throw new RuntimeException('Backup authentication failed');[$plain,$tag]=$r;fwrite($out,$plain);if($tag===SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL){$final=true;if(fread($in,1)!=='')throw new RuntimeException('Trailing data');break;}}
    if(!$final)throw new RuntimeException('Incomplete backup');fclose($out);$out=null;fclose($in);$in=null;
    $tar=new PharData($tarPath);$manifest=Brasa\decode($tar['manifest.json']->getContent());
    if(!hash_equals($manifest['databaseSha256'],hash('sha256',$tar['database.sql']->getContent())))throw new RuntimeException('Database checksum mismatch');
    $files=['database.sql','manifest.json'];foreach($manifest['media']as $name=>$hash){if(!preg_match('/^[a-f0-9]{32}(?:\.json)?$/',$name)||!hash_equals($hash,hash('sha256',$tar['media/'.$name]->getContent())))throw new RuntimeException('Media checksum mismatch');$files[]='media/'.$name;}
    $tar->extractTo($destination,$files,false);unset($tar);unlink($tarPath);
    echo "Backup autenticado e arquivos conferidos. SQL e imagens extraídos em diretório privado. Nenhum banco foi sobrescrito.\n";
}catch(Throwable $e){if(is_resource($out))fclose($out);if(is_resource($in))fclose($in);if(is_file($tarPath))unlink($tarPath);throw $e;}
