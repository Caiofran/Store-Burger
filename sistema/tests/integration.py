"""HTTP integration suite. Run only against isolated local PHP/MySQL instances.
BRASA_TEST_CREDENTIALS points to the private JSON created by fixtures.php.
Two PHP processes exercise database concurrency without PHP session-file serialization.
"""
import base64, concurrent.futures, copy, datetime, hashlib, hmac, http.cookiejar, json, os, pathlib, secrets, struct, urllib.error, urllib.parse, urllib.request

BASE=os.environ.get('BRASA_TEST_URL','http://127.0.0.1:8877')
SECOND=os.environ.get('BRASA_TEST_URL_SECOND','http://127.0.0.1:8878')
assert BASE.startswith('http://127.0.0.1:') and SECOND.startswith('http://127.0.0.1:'), 'Only isolated loopback environments are permitted'
CREDENTIALS=json.loads(pathlib.Path(os.environ['BRASA_TEST_CREDENTIALS']).read_text())
passed=[]
def check(label,fn):
    fn();passed.append(label);print('OK',label,flush=True)
def eq(a,b):
    assert a==b, f'Expected {b!r}; got {a!r}'
def code(secret):
    key=base64.b32decode(secret);digest=hmac.new(key,struct.pack('>Q',int(datetime.datetime.now().timestamp())//30),hashlib.sha1).digest();pos=digest[-1]&15
    return str((struct.unpack('>I',digest[pos:pos+4])[0]&0x7fffffff)%1000000).zfill(6)
class Client:
    def __init__(self,base=BASE):
        self.base=base;self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar));self.csrf='';self.call('session');self.csrf=self.data['csrf']
    def call(self,action,data=None,expected=200,headers=None,params=None):
        url=self.base+'/api.php?'+urllib.parse.urlencode({'action':action,**(params or {})});h={'Accept':'application/json'}
        if data is not None:h.update({'Content-Type':'application/json','X-CSRF-Token':self.csrf,'Origin':BASE})
        h.update(headers or {});request=urllib.request.Request(url,json.dumps(data).encode() if data is not None else None,headers=h)
        try:r=self.opener.open(request);status=r.status;body=r.read();self.headers=r.headers
        except urllib.error.HTTPError as e:status=e.code;body=e.read();self.headers=e.headers
        eq(status,expected);obj=json.loads(body);self.data=obj.get('data',obj)
        if isinstance(self.data,dict) and 'csrf'in self.data:self.csrf=self.data['csrf']
        return self.data
    def login(self,label,expected=200,omit_totp=False):
        c=CREDENTIALS[label];d={k:c[k] for k in ['email','password']}
        if label not in ['customer','other'] and not omit_totp:d['totp']=code(c['totp'])
        return self.call('auth/login',d,expected)
guest=Client();customer=Client();other=Client();admin=Client();kitchen=Client()
check('Catálogo público com dez produtos',lambda:eq(len(guest.call('catalog')['products']),10))
check('CSRF ausente é rejeitado',lambda:guest.call('auth/logout',{},403,{'X-CSRF-Token':''}))
check('Origem externa é rejeitada',lambda:guest.call('auth/logout',{},403,{'Origin':'https://malicious.invalid'}))
check('Visitante não acessa pedidos ou painel',lambda:(guest.call('orders',expected=401),guest.call('admin',expected=401,params={'kind':'users'})))
check('Cliente autentica e cookie é HttpOnly/SameSite',lambda:(customer.login('customer'),eq(any('HttpOnly' in h and 'SameSite=Lax' in h for h in customer.headers.get_all('Set-Cookie',[])),True)))
check('Outro cliente autentica',lambda:other.login('other'))
check('Cliente não ganha privilégios por parâmetros extras',lambda:(customer.call('admin/save',{'kind':'categories','role':'admin'},403),customer.call('admin',expected=403,params={'kind':'users'})))
check('Admin sem MFA é rejeitado',lambda:admin.login('admin',401,True))
check('Admin com MFA acessa o painel',lambda:admin.login('admin'))
check('Segundo fator não pode ser reutilizado',lambda:Client().login('admin',401))
check('Perfil cozinha autentica',lambda:kitchen.login('kitchen'))
check('Cozinha não administra catálogo',lambda:kitchen.call('admin',expected=403,params={'kind':'products'}))
now=datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=-3)))
when=(now+datetime.timedelta(days=1)).replace(hour=19,minute=0,second=0,microsecond=0)
if when.weekday()==0:when+=datetime.timedelta(days=1)
payload={'cart':[{'productId':'brasa-bacon','qty':1,'extras':[],'removed':[],'note':'Teste de integração'}],'mode':'delivery','address':{'areaId':'centro','street':'Rua Exemplo','number':'123','complement':''},'payment':'Pix','change':0,'timing':'scheduled','schedule':when.strftime('%Y-%m-%dT%H:%M'),'coupon':'BRASA15'}
check('Total de R$39,96 com cupom e Centro',lambda:eq(customer.call('quote',payload)['totals']['total'],3996))
def tamper():
    d=copy.deepcopy(payload);d.update({'price':1,'total':1,'fee':0,'discount':999999,'user_id':CREDENTIALS['other']['id']});d['cart'][0]['price']=1
    eq(customer.call('quote',d)['totals']['total'],3996)
check('Preço, taxa, desconto e identidade adulterados ignorados',tamper)
for area,fee in [('centro',690),('jardins',890),('vila-nova',590)]:
    d=copy.deepcopy(payload);d['coupon']='';d['address']['areaId']=area
    check('Taxa correta para '+area,lambda d=d,fee=fee:eq(customer.call('quote',d)['totals']['fee'],fee))
d=copy.deepcopy(payload);d['mode']='pickup';d['coupon']=''
check('Retirada gratuita',lambda:eq(customer.call('quote',d)['totals']['total'],3890))
def invalid_item(field,value):
    d=copy.deepcopy(payload);d['cart'][0][field]=value;customer.call('quote',d,422)
check('Quantidade negativa rejeitada',lambda:invalid_item('qty',-1))
check('Quantidade fracionária rejeitada',lambda:invalid_item('qty',1.5))
check('Quantidade acima do limite rejeitada',lambda:invalid_item('qty',21))
check('Adicional desconhecido rejeitado',lambda:invalid_item('extras',['fake']))
check('Adicional duplicado rejeitado',lambda:invalid_item('extras',['bacon','bacon']))
check('Ingrediente não pertencente ao produto rejeitado',lambda:invalid_item('removed',['Fake']))
def wrong_product():
    d=copy.deepcopy(payload);d['cart'][0]['productId']='cola';d['cart'][0]['extras']=['burger'];customer.call('quote',d,422)
check('Bebida não aceita adicional de hambúrguer',wrong_product)
def invalid_schedule():
    d=copy.deepcopy(payload);d['schedule']='2020-01-01T19:00';customer.call('quote',d,422);d['schedule']=when.strftime('%Y-%m-%dT10:00');customer.call('quote',d,422)
check('Agendamento passado ou fora do horário rejeitado',invalid_schedule)
def address_ownership():
    a=customer.call('address/save',{'address':payload['address']});d=copy.deepcopy(payload);d['addressId']=a['id'];other.call('quote',d,404);other.call('address/delete',{'id':a['id']});eq(any(x['id']==a['id']for x in customer.call('addresses')),True)
check('Endereços pertencem apenas ao cliente autenticado',address_ownership)
def bad_change():
    d=copy.deepcopy(payload);d['payment']='Dinheiro';d['change']=100;customer.call('quote',d,422)
check('Troco insuficiente rejeitado no servidor',bad_change)
def quote_change():
    d=copy.deepcopy(payload);d['coupon']='';q=customer.call('quote',d);p=next(p for p in admin.call('admin',params={'kind':'products'})if p['id']=='brasa-bacon');p['price']+=100
    saved=admin.call('admin/save',{'kind':'products','id':p['id'],'revision':p['revision'],'data':p})
    customer.call('order',{'quote':q['quote'],'requestKey':secrets.token_hex(24)},409)
    saved['price']-=100;admin.call('admin/save',{'kind':'products','id':saved['id'],'revision':saved['revision'],'data':saved})
check('Preço alterado exige nova revisão antes de confirmar',quote_change)
q=customer.call('quote',payload);request={'quote':q['quote'],'requestKey':secrets.token_hex(24)};order=customer.call('order',request)
check('Pedido confirmado tem valor calculado e pagamento pendente',lambda:(eq(order['totals']['total'],3996),eq(order['paymentStatus'],'pending')))
check('Repetir confirmação retorna o mesmo pedido',lambda:eq(customer.call('order',request)['id'],order['id']))
check('Mesmo identificador com outra revisão é rejeitado',lambda:customer.call('order',{'quote':secrets.token_hex(24),'requestKey':request['requestKey']},409))
check('Cliente não altera status',lambda:customer.call('admin/status',{'id':order['id'],'revision':1,'status':'Entregue'},403))
check('Outro cliente não recebe pedido alheio',lambda:eq(any(o['id']==order['id']for o in other.call('orders')),False))
check('Cupom não pode ser reutilizado',lambda:customer.call('quote',payload,422))
check('Acompanhamento público não expõe dados pessoais',lambda:eq(set(guest.call('track',params={'token':order['publicId']})),{'number','status'}))
check('Token de acompanhamento inválido rejeitado',lambda:guest.call('track',expected=404,params={'token':'0'*32}))
def workflow():
    admin.call('admin/status',{'id':order['id'],'revision':1,'status':'Entregue'},422)
    admin.call('admin/status',{'id':order['id'],'revision':1,'status':'Confirmado','addressChecked':False},422)
    updated=admin.call('admin/status',{'id':order['id'],'revision':1,'status':'Confirmado','addressChecked':True})
    admin.call('admin/status',{'id':order['id'],'revision':1,'status':'Em preparação'},409)
    k=next(o for o in kitchen.call('admin',params={'kind':'orders'})if o['id']==order['id']);eq('customer' in k,False);eq('address'in k,False)
    kitchen.call('admin/status',{'id':order['id'],'revision':updated['revision'],'status':'Cancelado','reason':'Teste proibido'},403)
    updated=kitchen.call('admin/status',{'id':order['id'],'revision':updated['revision'],'status':'Em preparação'})
    eq(updated['status'],'Em preparação')
check('Estados, concorrência de edição e minimização por perfil',workflow)
def payment():
    o=next(o for o in admin.call('admin',params={'kind':'orders'})if o['id']==order['id'])
    customer.call('admin/payment',{'id':o['id'],'revision':o['revision'],'reason':'Teste'},403)
    p=admin.call('admin/payment',{'id':o['id'],'revision':o['revision'],'reason':'Conferido no recebimento de teste'})
    eq(p['paymentStatus'],'received');admin.call('admin/payment',{'id':p['id'],'revision':p['revision'],'reason':'Repetido teste'},422)
check('Recebimento financeiro autorizado, auditado e não repetível',payment)
def concurrency():
    a=other;b=Client(SECOND);b.login('other');qa=a.call('quote',payload);qb=b.call('quote',payload)
    def send(c,q):
        try:return c.call('order',{'quote':q['quote'],'requestKey':secrets.token_hex(24)})
        except AssertionError as e:
            if 'got 422' in str(e):return None
            raise
    with concurrent.futures.ThreadPoolExecutor(max_workers=2)as pool:
        results=list(pool.map(lambda args:send(*args),[(a,qa),(b,qb)]))
    eq(sum(r is not None for r in results),1)
check('Dois processos concorrentes não consomem o cupom duas vezes',concurrency)
check('Sessão revogada após logout',lambda:(customer.call('auth/logout',{}),customer.call('orders',expected=401)))
check('Auditoria registra mudanças sem senha ou segredo TOTP',lambda: eq(any('password' in str(r).lower()or CREDENTIALS['admin']['password']in str(r)for r in admin.call('admin',params={'kind':'audit'})),False))
def rate_limit():
    c=Client();d={'email':'nonexistent-'+secrets.token_hex(4)+'@example.com','password':'errada'}
    for i in range(8):c.call('auth/login',d,401)
    c.call('auth/login',d,429)
check('Rate limit de conta bloqueia tentativas repetidas',rate_limit)
print(f'\n{len(passed)} verificações HTTP concluídas.')
