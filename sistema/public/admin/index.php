<?php
declare(strict_types=1);
require __DIR__.'/../../src/bootstrap.php';
Brasa\headers();
try{Brasa\sessionStart();$user=Brasa\user(false);if(!$user||$user['role']==='customer'){header('Location: /admin/login.php',true,303);exit;}}
catch(Throwable){http_response_code(503);echo 'Painel temporariamente indisponível.';exit;}
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Painel · Brasa Burger Co.</title><link rel="stylesheet" href="/styles.css"><link rel="stylesheet" href="/admin/admin.css"><script src="/shared.js" defer></script><script src="/api-client.js" defer></script><script src="/admin/admin.js" defer></script></head><body><div id="admin-root"><div class="empty-state">Carregando operação…</div></div><dialog id="admin-dialog"></dialog><div id="toast" class="toast" role="status" aria-live="polite"></div></body></html>
