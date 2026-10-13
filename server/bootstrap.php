<?php
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
function reply(array $data,int $code=200):never {http_response_code($code);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
$configPath=__DIR__.'/config.php';
if(!is_file($configPath))reply(['ok'=>false,'error'=>'not_configured'],503);
$config=require $configPath;
if(empty($config['data_dir'])||empty($config['admin_password_hash']))reply(['ok'=>false,'error'=>'not_configured'],503);
$dataDir=rtrim((string)$config['data_dir'],'/');
$realData=realpath($dataDir);$root=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));
if(!$realData||($root&&($realData===$root||str_starts_with($realData,$root.'/'))))reply(['ok'=>false,'error'=>'unsafe_data_directory'],503);
$pdo=new PDO('sqlite:'.$dataDir.'/electro.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA busy_timeout=5000');
$pdo->exec('CREATE TABLE IF NOT EXISTS products (id TEXT PRIMARY KEY, body TEXT NOT NULL, revision INTEGER NOT NULL DEFAULT 1)');
$pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (address TEXT PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, blocked_until INTEGER NOT NULL DEFAULT 0)');
$pdo->exec('CREATE TABLE IF NOT EXISTS catalog_meta (key TEXT PRIMARY KEY,value TEXT NOT NULL)');
$pdo->beginTransaction();
if(!$pdo->query("SELECT value FROM catalog_meta WHERE key='seeded'")->fetchColumn()){
 $seed=json_decode(file_get_contents(__DIR__.'/catalog-seed.json'),true,512,JSON_THROW_ON_ERROR);
 $s=$pdo->prepare('INSERT OR IGNORE INTO products(id,body) VALUES(?,?)');
 foreach($seed as $p)$s->execute([$p['id'],json_encode($p,JSON_UNESCAPED_UNICODE)]);
 $pdo->exec("INSERT INTO catalog_meta(key,value) VALUES('seeded','1')");
}
$pdo->commit();
function session_start_safe():void {if(session_status()===PHP_SESSION_ACTIVE)return;session_name('electro_admin');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Strict']);ini_set('session.use_strict_mode','1');session_start();}
function admin_guard():void {session_start_safe();if(empty($_SESSION['admin'])||(int)($_SESSION['last_activity']??0)<time()-3600)reply(['ok'=>false,'error'=>'unauthorized'],401);$_SESSION['last_activity']=time();}
function csrf_guard():void {if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'')))reply(['ok'=>false,'error'=>'csrf'],403);}
function input_json():array {$raw=file_get_contents('php://input',false,null,0,65537);if(strlen($raw)>65536)reply(['ok'=>false,'error'=>'too_large'],413);$v=json_decode($raw,true);if(!is_array($v))reply(['ok'=>false,'error'=>'invalid_json'],422);return $v;}
