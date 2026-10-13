<?php
declare(strict_types=1);
require __DIR__.'/../../server/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='GET')reply(['ok'=>false,'error'=>'method'],405);
try {$items=[];foreach($pdo->query('SELECT body FROM products ORDER BY rowid') as $r){$p=json_decode($r['body'],true,512,JSON_THROW_ON_ERROR);if(!empty($p['published']))$items[]=$p;}reply(['ok'=>true,'items'=>$items]);}
catch(Throwable $e){error_log('electro_catalog: '.$e->getMessage());reply(['ok'=>false,'error'=>'server_error'],500);}
