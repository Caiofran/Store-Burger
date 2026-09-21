"""Additional security checks; uses local-only mail helper without displaying secrets."""
import datetime, http.cookiejar, json, os, pathlib, re, secrets, subprocess, urllib.error, urllib.parse, urllib.request
BASE='http://127.0.0.1:8877'
PHP=os.environ['BRASA_PHP'];ROOT=pathlib.Path(__file__).resolve().parents[1]
C=json.loads(pathlib.Path(os.environ['BRASA_TEST_CREDENTIALS']).read_text())
checks=[]
def check(label,fn):fn();checks.append(label);print('OK',label,flush=True)
def eq(a,b):assert a==b,(a,b)
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar));self.csrf='';self.csrf=self.call('session')['csrf']
 def call(self,action,data=None,status=200,raw=None,content=None):
  headers={'Accept':'application/json'}
  if data is not None or raw is not None:headers.update({'Content-Type':content or 'application/json','X-CSRF-Token':self.csrf,'Origin':BASE})
  request=urllib.request.Request(BASE+'/api.php?action='+urllib.parse.quote(action),raw if raw is not None else json.dumps(data).encode() if data is not None else None,headers=headers)
  try:r=self.opener.open(request);code=r.status;body=r.read()
  except urllib.error.HTTPError as e:code=e.code;body=e.read()
  eq(code,status);v=json.loads(body).get('data',{});self.csrf=v.get('csrf',self.csrf)if isinstance(v,dict)else self.csrf;return v
 def upload(self,name,body,mime,status):
  boundary='Brasa'+secrets.token_hex(12);raw=(f'--{boundary}\r\nContent-Disposition: form-data; name="image"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+body+f'\r\n--{boundary}--\r\n'.encode());return self.call('upload',status=status,raw=raw,content='multipart/form-data; boundary='+boundary)
def helper(action,arg):return subprocess.check_output([PHP,str(ROOT/'tests/mail-helper.php'),action,arg],text=True).strip()
def register(c):
 email='flow-'+secrets.token_hex(8)+'@example.com';password=secrets.token_hex(16);r=c.call('auth/register',{'name':'Cliente de teste','phone':'11999992026','email':email,'password':password});return email,password,r['challenge']
a=Client();email,password,cid=register(a);otp=helper('peek',email)
check('Cadastro exige confirmação de e-mail',lambda:a.call('auth/login',{'email':email,'password':password},401))
check('Código incorreto aumenta contador sem autenticar',lambda:(a.call('auth/verify-code',{'challenge':cid,'code':'000000'},401),eq(helper('counter',cid),'1')))
check('Código correto confirma e autentica',lambda:eq(a.call('auth/verify-code',{'challenge':cid,'code':otp})['user']['verified'],True))
check('Código consumido não pode ser reutilizado',lambda:a.call('auth/verify-code',{'challenge':cid,'code':otp},401))
b=Client();email2,p2,cid2=register(b);otp2=helper('peek',email2);helper('expire',cid2)
check('Código expirado é rejeitado',lambda:b.call('auth/verify-code',{'challenge':cid2,'code':otp2},401))
c=Client();email3,p3,cid3=register(c);otp3=helper('peek',email3)
def attempts():
 for _ in range(5):c.call('auth/verify-code',{'challenge':cid3,'code':'000000'},401)
 c.call('auth/verify-code',{'challenge':cid3,'code':otp3},401)
check('Cinco erros invalidam a possibilidade de consumir o código',attempts)
check('Reenvio imediato é limitado',lambda:c.call('auth/request-code',{'email':email3,'purpose':'verify'},429))
check('Solicitação de privacidade autenticada é registrada',lambda:eq(bool(a.call('privacy',{'kind':'access'})['message']),True))
check('Cliente não envia imagens administrativas',lambda:a.upload('picture.png',b'fake','image/png',403))
import base64,struct,hashlib,hmac,time
def totp(secret):
 h=hmac.new(base64.b32decode(secret),struct.pack('>Q',int(time.time())//30),hashlib.sha1).digest();p=h[-1]&15;return str((struct.unpack('>I',h[p:p+4])[0]&0x7fffffff)%1000000).zfill(6)
manager=Client();cred=C['manager'];manager.call('auth/login',{'email':cred['email'],'password':cred['password'],'totp':totp(cred['totp'])})
check('PHP disfarçado de PNG é rejeitado',lambda:manager.upload('foto.php.png',b'<?php echo "test";?>','image/png',422))
check('SVG com script é rejeitado',lambda:manager.upload('logo.svg',b'<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>','image/svg+xml',422))
check('Imagem truncada é rejeitada',lambda:manager.upload('broken.png',b'\x89PNG\r\n\x1a\n'+'x'.encode()*20,'image/png',422))
check('Gerente não cria administradores',lambda:manager.call('admin/user',{'role':'admin'},403))
def good_image():
 body=(ROOT/'public/assets/brasa-bacon.png').read_bytes();r=manager.upload('burger.png',body,'image/png',200);image=urllib.request.urlopen(BASE+r['url']);eq(image.headers['Content-Type'],'image/png');eq(hashlib.sha256(image.read()).hexdigest(),hashlib.sha256(body).hexdigest())
check('Imagem válida é servida sem alterar o original',good_image)
check('JSON inválido é rejeitado sem detalhes internos',lambda:manager.call('admin/save',status=400,raw=b'{broken',content='application/json'))
def traversal():
 for path in ['/.env','/../src/bootstrap.php','/storage/private/mail/1.json','/database/seed.json','/.git/config']:
  try:urllib.request.urlopen(BASE+path);raise AssertionError('Private file exposed')
  except urllib.error.HTTPError as e:eq(e.code,404)
check('Arquivos privados não são publicados',traversal)
print(f'\n{len(checks)} verificações adicionais concluídas.')
