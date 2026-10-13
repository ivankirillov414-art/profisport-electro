<?php
declare(strict_types=1);
require __DIR__.'/../../server/bootstrap.php';
$action=(string)($_GET['action']??'me');$method=$_SERVER['REQUEST_METHOD'];
try {
 if($action==='login'&&$method==='POST'){
  session_start_safe();$in=input_json();$address=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??''));
  $s=$pdo->prepare('SELECT * FROM login_attempts WHERE address=?');$s->execute([$address]);$attempt=$s->fetch();
  if($attempt&&(int)$attempt['blocked_until']>time())reply(['ok'=>false,'error'=>'rate_limit'],429);
  if(!hash_equals((string)$config['admin_login'],(string)($in['login']??''))||!password_verify((string)($in['password']??''),(string)$config['admin_password_hash'])){
   $count=$attempt?(int)$attempt['attempts']+1:1;if($attempt&&(int)$attempt['blocked_until']>0)$count=1;
   $s=$pdo->prepare('INSERT INTO login_attempts(address,attempts,blocked_until) VALUES(?,?,?) ON CONFLICT(address) DO UPDATE SET attempts=excluded.attempts,blocked_until=excluded.blocked_until');$s->execute([$address,$count,$count>=5?time()+900:0]);
   reply(['ok'=>false,'error'=>'invalid_credentials'],401);
  }
  $pdo->prepare('DELETE FROM login_attempts WHERE address=?')->execute([$address]);session_regenerate_id(true);$_SESSION=['admin'=>true,'last_activity'=>time(),'csrf'=>bin2hex(random_bytes(32))];reply(['ok'=>true,'csrf'=>$_SESSION['csrf']]);
 }
 admin_guard();
 if($action==='me'&&$method==='GET')reply(['ok'=>true,'csrf'=>$_SESSION['csrf']]);
 if($action==='list'&&$method==='GET'){$items=[];foreach($pdo->query('SELECT body,revision FROM products ORDER BY rowid') as $r){$p=json_decode($r['body'],true,512,JSON_THROW_ON_ERROR);$p['revision']=(int)$r['revision'];$items[]=$p;}reply(['ok'=>true,'items'=>$items]);}
 if($method!=='POST')reply(['ok'=>false,'error'=>'method'],405);
 csrf_guard();
 if($action==='logout'){$_SESSION=[];session_destroy();reply(['ok'=>true]);}
 if($action!=='save')reply(['ok'=>false,'error'=>'action'],404);
 $in=input_json();$p=$in['product']??null;if(!is_array($p))reply(['ok'=>false,'error'=>'invalid_product'],422);
 $strings=['id'=>80,'sku'=>120,'brand'=>120,'name'=>200,'type'=>80,'color'=>80,'battery'=>200,'wheels'=>200,'brakes'=>200,'desc'=>10000,'photo'=>1000,'source'=>1000,'supplier'=>200,'checkedAt'=>40,'stockCheckedAt'=>40,'availability'=>200];
 $clean=[];foreach($strings as $key=>$limit){$value=$p[$key]??'';if(!is_string($value)||strlen($value)>$limit*4)reply(['ok'=>false,'error'=>'invalid_'.$key],422);$clean[$key]=trim($value);}
 if(!preg_match('/^[a-zA-Z0-9_-]{1,80}$/',$clean['id'])||$clean['name']==='')reply(['ok'=>false,'error'=>'name_and_id_required'],422);
 foreach(['price','range','power','weight','load'] as $key){$value=$p[$key]??null;if($value!==null&&(!is_numeric($value)||!is_finite((float)$value)||(float)$value<0||(float)$value>100000000))reply(['ok'=>false,'error'=>'invalid_'.$key],422);$clean[$key]=$value===null?null:(float)$value;}
 foreach(['photo','source'] as $key){$url=$clean[$key];if($url!==''&&!preg_match('~^https://[^\s]+$~D',$url)&&!($key==='photo'&&preg_match('~^assets/[a-zA-Z0-9_./-]+$~D',$url)&&!str_contains($url,'..')))reply(['ok'=>false,'error'=>'invalid_'.$key],422);}
 $clean['published']=($p['published']??false)===true;
 $clean['priceKind']=($p['priceKind']??'')==='shop'?'shop':'supplier';
 $stock=(string)($p['stockStatus']??'unknown');if(!in_array($stock,['in_stock','out_of_stock','on_order','unknown'],true))reply(['ok'=>false,'error'=>'invalid_stock'],422);$clean['stockStatus']=$stock;
 $clean['availability']=['in_stock'=>'В наличии в ProfiSport','out_of_stock'=>'Нет в наличии','on_order'=>'Под заказ','unknown'=>'Наличие уточняется'][$stock];
 if($clean['published']&&!in_array($clean['type'],['Электросамокат','Электровелосипед','Электроскутер','Электромотоцикл','Электротрицикл'],true))reply(['ok'=>false,'error'=>'type_required'],422);
 if($clean['photo']==='')$clean['photo']='assets/no-photo.svg';
 $revision=(int)($in['revision']??0);$pdo->beginTransaction();
 $s=$pdo->prepare('SELECT revision FROM products WHERE id=?');$s->execute([$clean['id']]);$existing=$s->fetchColumn();
 if($existing!==false){if((int)$existing!==$revision){$pdo->rollBack();reply(['ok'=>false,'error'=>'conflict'],409);}$s=$pdo->prepare('UPDATE products SET body=?,revision=revision+1 WHERE id=?');$s->execute([json_encode($clean,JSON_UNESCAPED_UNICODE),$clean['id']]);$revision++;}
 else {if($revision!==0){$pdo->rollBack();reply(['ok'=>false,'error'=>'conflict'],409);}$s=$pdo->prepare('INSERT INTO products(id,body) VALUES(?,?)');$s->execute([$clean['id'],json_encode($clean,JSON_UNESCAPED_UNICODE)]);$revision=1;}
 $pdo->commit();$clean['revision']=$revision;reply(['ok'=>true,'product'=>$clean]);
} catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('electro_admin: '.$e->getMessage());reply(['ok'=>false,'error'=>'server_error'],500);}
