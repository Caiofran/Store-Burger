<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';
$sql=file_get_contents(Brasa\ROOT.'/database/schema.sql');
foreach(explode(';',$sql)as $statement)if(trim($statement)!=='')Brasa\db()->exec($statement);
$seed=Brasa\decode(file_get_contents(Brasa\ROOT.'/database/seed.json'));
foreach($seed as $kind=>$records)foreach($records as $record){$id=$record['id'];unset($record['id']);Brasa\query('INSERT IGNORE INTO catalog(kind,id,data) VALUES(?,?,?)',[$kind,$id,Brasa\json($record)]);}
echo "Migração concluída. Nenhum administrador padrão foi criado.\n";
