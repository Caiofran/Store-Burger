<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';
$hex=Brasa\env('BACKUP_KEY');if(!preg_match('/^[a-f0-9]{64}$/i',$hex)||hash_equals(strtolower($hex),strtolower(Brasa\env('APP_KEY'))))throw new RuntimeException('Configure a separate BACKUP_KEY');
$key=hex2bin($hex);$target=Brasa\env('BACKUP_DIR');if($target===''||!is_dir($target)||!is_writable($target))throw new RuntimeException('Configure a writable private BACKUP_DIR');
$db=Brasa\env('DB_NAME');if(!preg_match('/^[a-zA-Z0-9_]+$/',$db))throw new RuntimeException('Invalid DB_NAME');
$work=Brasa\privateDir('backup-work').'/'.bin2hex(random_bytes(8));mkdir($work,0700);
$quote=fn($s)=>'"'.str_replace(["\\","\"","\r","\n"],["\\\\","\\\"","",''], $s).'"';
$ini=$work.'/client.cnf';file_put_contents($ini,"[client]\nhost=".$quote(Brasa\env('DB_HOST'))."\nport=".(int)Brasa\env('DB_PORT','3306')."\nuser=".$quote(Brasa\env('BACKUP_DB_USER'))."\npassword=".$quote(Brasa\env('BACKUP_DB_PASSWORD'))."\n");chmod($ini,0600);
$sql=$work.'/database.sql';$tarFile=$work.'/backup.tar';$destination=rtrim($target,'/\\').'/brasa-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.backup';
try {
    $args=[Brasa\env('MYSQLDUMP_PATH','mysqldump'),'--defaults-extra-file='.$ini,'--single-transaction','--skip-lock-tables','--no-tablespaces','--hex-blob','--default-character-set=utf8mb4'];
    if(Brasa\env('MYSQL_ORACLE_CLIENT','1')==='1')$args[]='--set-gtid-purged=OFF';$args[]=$db;
    $p=proc_open($args,[0=>['pipe','r'],1=>['file',$sql,'wb'],2=>['file',$work.'/error.log','wb']],$pipes);if(!is_resource($p))throw new RuntimeException('Dump unavailable');fclose($pipes[0]);$exit=proc_close($p);if($exit!==0)throw new RuntimeException('Database dump failed; inspect private error file');
    unlink($ini);$tar=new PharData($tarFile);$tar->addFile($sql,'database.sql');$manifest=['created'=>gmdate(DATE_ATOM),'databaseSha256'=>hash_file('sha256',$sql),'media'=>[]];
    foreach(glob(Brasa\ROOT.'/storage/media/*') as $file)if(is_file($file)){ $name=basename($file);$tar->addFile($file,'media/'.$name);$manifest['media'][$name]=hash_file('sha256',$file); }
    $tar->addFromString('manifest.json',Brasa\json($manifest));unset($tar);
    [$state,$header]=sodium_crypto_secretstream_xchacha20poly1305_init_push($key);$in=fopen($tarFile,'rb');$out=fopen($destination.'.part','xb');fwrite($out,"BRASA1".$header);
    while(!feof($in)){$plain=fread($in,1024*1024);if($plain==='')break;$cipher=sodium_crypto_secretstream_xchacha20poly1305_push($state,$plain,'',SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE);fwrite($out,pack('N',strlen($cipher)).$cipher);}
    $final=sodium_crypto_secretstream_xchacha20poly1305_push($state,'','',SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);fwrite($out,pack('N',strlen($final)).$final);fclose($in);fclose($out);chmod($destination.'.part',0600);rename($destination.'.part',$destination);
    echo "Backup criptografado criado: $destination\nCopie para armazenamento externo isolado e confira o recebimento.\n";
}finally{foreach([$ini,$sql,$tarFile]as $f)if(is_file($f))unlink($f);sodium_memzero($key);}
