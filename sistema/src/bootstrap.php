<?php
declare(strict_types=1);

namespace Brasa;

const ROOT = __DIR__ . '/..';
date_default_timezone_set('UTC');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$configFile = getenv('BRASA_CONFIG') ?: ROOT . '/.env';
if (is_file($configFile)) {
    foreach (file($configFile, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', trim($line), $m) && getenv($m[1]) === false) {
            putenv($m[1] . '=' . trim($m[2], " \t\"'"));
        }
    }
}
function env(string $key, string $default = ''): string { return getenv($key) === false ? $default : (string)getenv($key); }
function local(): bool { return env('APP_ENV', 'production') === 'local'; }
function appKey(): string {
    $key = env('APP_KEY');
    if (!preg_match('/^[a-f0-9]{64}$/i', $key)) throw new \RuntimeException('Missing application key');
    return hex2bin($key);
}
function db(): \PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new \PDO('mysql:host=' . env('DB_HOST','127.0.0.1') . ';port=' . env('DB_PORT','3306') . ';dbname=' . env('DB_NAME','brasa') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASSWORD'), [\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION, \PDO::ATTR_EMULATE_PREPARES=>false, \PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}
function query(string $sql, array $args=[]): \PDOStatement { $s=db()->prepare($sql);$s->execute($args);return $s; }
function one(string $sql, array $args=[]): ?array { return query($sql,$args)->fetch() ?: null; }
function json(mixed $value): string { return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); }
function decode(string $value): array { return json_decode($value,true,32,JSON_THROW_ON_ERROR); }
function transaction(callable $fn): mixed {
    db()->beginTransaction();
    try { $result=$fn(); db()->commit(); return $result; }
    catch (\Throwable $e) { if(db()->inTransaction())db()->rollBack(); throw $e; }
}
final class HttpError extends \RuntimeException {
    public function __construct(public int $status, string $message) { parent::__construct($message); }
}
function fail(string $message, int $status=422): never { throw new HttpError($status,$message); }
function text(mixed $v,int $max=100,int $min=0): string {
    if(!is_string($v) || !mb_check_encoding($v,'UTF-8'))fail('Texto inválido.');
    $v=trim($v);$len=mb_strlen($v);
    if($len<$min||$len>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$v))fail('Confira o tamanho e o conteúdo dos campos.');
    return $v;
}
function integer(mixed $v,int $min=0,int $max=1000000): int {
    if(!is_int($v)||$v<$min||$v>$max)fail('Valor numérico inválido.');return $v;
}
function choice(mixed $v,array $allowed): string { if(!is_string($v)||!in_array($v,$allowed,true))fail('Opção inválida.');return $v; }
function email(mixed $v): string { $v=mb_strtolower(text($v,190,3));if(!filter_var($v,FILTER_VALIDATE_EMAIL))fail('Informe um e-mail válido.');return $v; }
function phone(mixed $v): string { $v=preg_replace('/\D/','',text($v,25,10));if(!preg_match('/^\d{10,11}$/',$v))fail('Informe telefone com DDD.');return $v; }
function seal(string $v): string { $n=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);return base64_encode($n.sodium_crypto_secretbox($v,$n,appKey())); }
function unseal(string $v): string { $b=base64_decode($v,true);if($b===false||strlen($b)<40)throw new \RuntimeException('Invalid ciphertext');$r=sodium_crypto_secretbox_open(substr($b,24),substr($b,0,24),appKey());if($r===false)throw new \RuntimeException('Invalid ciphertext');return $r; }
function audit(string $action,string $object='',array $details=[]): void {
    query('INSERT INTO audit_events(actor_id,action,object_id,details) VALUES(?,?,?,?)',[$_SESSION['uid']??null,$action,$object,json($details)]);
}
function privateDir(string $name): string { $p=ROOT.'/storage/private/'.$name;if(!is_dir($p)&&!mkdir($p,0700,true)&&!is_dir($p))throw new \RuntimeException('Storage unavailable');return $p; }

require_once __DIR__.'/Security.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Shop.php';
require_once __DIR__.'/Admin.php';
