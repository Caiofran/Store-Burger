<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
if(PHP_SAPI!=='cli'||!Brasa\local())exit(1);
$n=0;
function test(string $label,callable $fn): void {global $n;$fn();$n++;echo "OK $label\n";}
function equal(mixed $a,mixed $b): void {if($a!==$b)throw new RuntimeException('Assertion failed: '.var_export($a,true).' != '.var_export($b,true));}
function rejects(callable $f): void {try{$f();}catch(Brasa\HttpError){return;}throw new RuntimeException('Expected rejection');}
test('TOTP RFC 6238 SHA1 em 59 segundos',fn()=>equal(Brasa\totp(Brasa\base32('12345678901234567890'),1,8),'94287082'));
test('TOTP RFC 6238 SHA1 em 1111111109 segundos',fn()=>equal(Brasa\totp(Brasa\base32('12345678901234567890'),intdiv(1111111109,30),8),'07081804'));
test('Segredo criptografado e recuperável',fn()=>equal(Brasa\unseal(Brasa\seal('conteudo-privado')),'conteudo-privado'));
test('Tipos e limites numéricos',function(){rejects(fn()=>Brasa\integer('1'));rejects(fn()=>Brasa\integer(-1));rejects(fn()=>Brasa\integer(1.5));equal(Brasa\integer(3890),3890);});
test('Senhas usam Argon2id',function(){$hash=Brasa\hashPassword('Uma frase de senha exclusiva 2026!');equal(password_verify('Uma frase de senha exclusiva 2026!',$hash),true);equal(password_get_info($hash)['algoName'],'argon2id');});
test('Senha curta rejeitada',fn()=>rejects(fn()=>Brasa\hashPassword('brasa2026')));
test('Imagem externa e SVG rejeitados',function(){rejects(fn()=>Brasa\mediaPath('https://example.com/image.png'));rejects(fn()=>Brasa\mediaPath('/assets/logo.svg'));});
test('Expediente de Brasília e segunda fechada',function(){$s=Brasa\settings();$tz=new DateTimeZone('America/Sao_Paulo');equal(Brasa\opening($s,new DateTimeImmutable('2026-09-07 20:00',$tz)),false);equal(Brasa\opening($s,new DateTimeImmutable('2026-09-08 20:00',$tz)),true);equal(Brasa\opening($s,new DateTimeImmutable('2026-09-08 23:30',$tz)),false);});
test('Seleções duplicadas rejeitadas',fn()=>rejects(fn()=>Brasa\selected(['bacon','bacon'])));
echo "$n verificações unitárias concluídas.\n";
