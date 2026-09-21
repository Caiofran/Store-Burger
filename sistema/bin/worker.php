<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';
require Brasa\ROOT.'/vendor/phpmailer/Exception.php';require Brasa\ROOT.'/vendor/phpmailer/PHPMailer.php';require Brasa\ROOT.'/vendor/phpmailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
Brasa\query("UPDATE outbox SET status='pending',locked_at=NULL WHERE status='sending' AND locked_at<UTC_TIMESTAMP()-INTERVAL 5 MINUTE");
for($i=0;$i<30;$i++){
    $row=Brasa\transaction(function(){ $r=Brasa\one("SELECT * FROM outbox WHERE status='pending' AND available_at<=UTC_TIMESTAMP() ORDER BY id LIMIT 1 FOR UPDATE SKIP LOCKED");if($r)Brasa\query("UPDATE outbox SET status='sending',attempts=attempts+1,locked_at=UTC_TIMESTAMP() WHERE id=?",[$r['id']]);return $r; });
    if(!$row)break;
    try{
        $p=Brasa\decode(Brasa\unseal($row['payload']));
        if(Brasa\local()&&Brasa\env('MAIL_TRANSPORT')==='spool'){
            $path=Brasa\privateDir('mail').'/'.$row['id'].'.json';file_put_contents($path,Brasa\json($p),LOCK_EX);chmod($path,0600);
        }else{
            Brasa\mailReady();$mail=new PHPMailer(true);$mail->isSMTP();$mail->Host=Brasa\env('SMTP_HOST');$mail->Port=(int)Brasa\env('SMTP_PORT','587');$mail->SMTPAuth=true;$mail->Username=Brasa\env('SMTP_USER');$mail->Password=Brasa\env('SMTP_PASSWORD');$mail->SMTPSecure=Brasa\env('SMTP_SECURITY','tls');$mail->Timeout=15;$mail->CharSet='UTF-8';$mail->setFrom(Brasa\env('MAIL_FROM'),'Brasa Burger Co.');$mail->addAddress($p['to']);$mail->Subject=$p['subject'];$mail->Body=$p['text'];$mail->send();
        }
        Brasa\query("UPDATE outbox SET status='sent',sent_at=UTC_TIMESTAMP(),payload='' WHERE id=?",[$row['id']]);
    }catch(Throwable){$attempts=(int)$row['attempts']+1;Brasa\query('UPDATE outbox SET status=?,available_at=?,locked_at=NULL WHERE id=?',[$attempts>=3?'failed':'pending',gmdate('Y-m-d H:i:s',time()+30*$attempts),$row['id']]);error_log(Brasa\json(['event'=>'mail.failed','outbox'=>$row['id']]));}
}
Brasa\query('DELETE FROM rate_limits WHERE expires_at<UTC_TIMESTAMP()');
Brasa\query('DELETE FROM challenges WHERE expires_at<UTC_TIMESTAMP()-INTERVAL 1 DAY');
Brasa\query("DELETE FROM outbox WHERE status='sent' AND sent_at<UTC_TIMESTAMP()-INTERVAL 7 DAY");
Brasa\query("UPDATE outbox SET payload='' WHERE status='failed' AND created_at<UTC_TIMESTAMP()-INTERVAL 1 DAY");
// Preserve orders and audit records until the controller approves the applicable retention policy.
echo "Fila e expirações processadas.\n";
