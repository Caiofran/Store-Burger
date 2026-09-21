<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';
$issues=[];
foreach(['pdo_mysql','mbstring','sodium','gd','fileinfo','openssl']as $ext)if(!extension_loaded($ext))$issues[]="Extensão ausente: $ext";
if(!defined('PASSWORD_ARGON2ID'))$issues[]='Argon2id indisponível';
if(Brasa\env('APP_ENV')!=='production')$issues[]='APP_ENV deve ser production na VPS';
if(!str_starts_with(Brasa\env('APP_URL'),'https://'))$issues[]='APP_URL precisa de HTTPS';
try{Brasa\appKey();Brasa\db();$s=Brasa\settings();if(str_ends_with($s['privacyEmail'],'@example.com'))$issues[]='Configure o contato real de privacidade';if($s['logo']==='')$issues[]='Logo oficial ainda não carregada';if(!Brasa\one("SELECT id FROM users WHERE role='admin' AND active=1 AND totp_secret IS NOT NULL"))$issues[]='Crie o administrador com MFA';if(Brasa\one("SELECT id FROM users WHERE role<>'customer' AND active=1 AND totp_secret IS NULL"))$issues[]='Há acesso administrativo sem MFA configurado';}catch(Throwable){$issues[]='Configuração, banco ou migração indisponível';}
try{Brasa\mailReady();}catch(Throwable){$issues[]='Configure SMTP e remetente';}
if(Brasa\env('MAIL_TRANSPORT')!=='smtp')$issues[]='Transporte de e-mail de produção deve ser smtp';
if((int)ini_get('post_max_size')<4||(int)ini_get('upload_max_filesize')<3)$issues[]='Ajuste limites de upload do PHP';
if(Brasa\env('BACKUP_DIR')===''||!preg_match('/^[a-f0-9]{64}$/i',Brasa\env('BACKUP_KEY')))$issues[]='Configure destino privado e chave independente de backup';
if(Brasa\env('BACKUP_KEY')!==''&&hash_equals(strtolower(Brasa\env('BACKUP_KEY')),strtolower(Brasa\env('APP_KEY'))))$issues[]='BACKUP_KEY precisa ser diferente de APP_KEY';
if($issues){foreach($issues as $issue)echo "PENDENTE: $issue\n";exit(1);}
echo "Configuração básica verificada. Confirme também HTTPS externo, worker, cópia externa de backup, restauração e responsável pelos alertas antes de abrir a operação.\n";
