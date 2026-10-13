import json, os, pathlib, subprocess, tempfile, time, urllib.request, urllib.error, http.cookiejar, shutil
root=pathlib.Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory() as temp:
 temp=pathlib.Path(temp);pub=temp/'public';server=temp/'server';data=temp/'data';data.mkdir()
 shutil.copytree(root/'dist',pub);shutil.copytree(root/'server',server)
 hash=subprocess.check_output(['php','-r',"echo password_hash('test-only-password',PASSWORD_DEFAULT);"],text=True)
 (server/'config.php').write_text("<?php return ['data_dir'=>"+repr(str(data))+",'admin_login'=>'staff','admin_password_hash'=>"+repr(hash)+"]; ")
 proc=subprocess.Popen(['php','-S','127.0.0.1:18765','-t',str(pub)],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
 base='http://127.0.0.1:18765/api/';jar=http.cookiejar.CookieJar();client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
 def call(path,body=None,token=None):
  headers={'Content-Type':'application/json'}
  if token:headers['X-CSRF-Token']=token
  req=urllib.request.Request(base+path,json.dumps(body).encode() if body is not None else None,headers)
  try:
   with client.open(req) as r:return r.status,json.loads(r.read())
  except urllib.error.HTTPError as e:return e.code,json.loads(e.read())
 try:
  for _ in range(50):
   try:status,j=call('catalog.php');break
   except OSError:time.sleep(.1)
  assert status==200 and len(j['items'])==26
  assert all(not p['id'].startswith('inventory-') for p in j['items'])
  assert next(p for p in j['items'] if p['id']=='sk8')['price']==30000
  assert call('admin.php?action=list')[0]==401
  assert call('admin.php?action=login',{'login':'staff','password':'wrong'})[0]==401
  status,j=call('admin.php?action=login',{'login':'staff','password':'test-only-password'});assert status==200
  token=j['csrf'];status,j=call('admin.php?action=list');assert status==200 and len(j['items'])==36
  p=next(p for p in j['items'] if p['id']=='sk8');revision=p.pop('revision');p['price']=31234;p['desc']='Тест сохранения'
  assert call('admin.php?action=save',{'product':p,'revision':revision})[0]==403
  status,j=call('admin.php?action=save',{'product':p,'revision':revision},token);assert status==200
  assert call('admin.php?action=save',{'product':p,'revision':revision},token)[0]==409
  status,j=call('catalog.php');assert next(x for x in j['items'] if x['id']=='sk8')['price']==31234
  p['photo']='javascript:alert(1)';assert call('admin.php?action=save',{'product':p,'revision':revision+1},token)[0]==422
  p['photo']='assets/no-photo.svg';p['published']=False
  assert call('admin.php?action=save',{'product':p,'revision':revision+1},token)[0]==200
  assert all(x['id']!='sk8' for x in call('catalog.php')[1]['items'])
  assert call('admin.php?action=logout',{},token)[0]==200
  assert call('admin.php?action=list')[0]==401
  print('PASS: authentication, CSRF, persistent catalog updates, conflicts, draft privacy, hidden products, URL validation')
 finally:proc.terminate();proc.wait()
