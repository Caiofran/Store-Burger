/* Verificações locais: execução com Node é apenas uma ferramenta de revisão, não uma dependência do protótipo. */
const fs=require('node:fs');const vm=require('node:vm');const assert=require('node:assert/strict');
const storage=new Map();const context={window:{},Intl,localStorage:{getItem:k=>storage.get(k)||null,setItem:(k,v)=>storage.set(k,v)},setTimeout,clearTimeout};
vm.createContext(context);vm.runInContext(fs.readFileSync('shared.js','utf8'),context);const B=context.window.Brasa;
let checks=0;function test(name,fn){fn();checks++;console.log('OK '+name)}
const cart=[{productId:'brasa-bacon',qty:1,extras:[]}];
test('Preços do briefing',()=>assert.deepEqual(Array.from(B.products.slice(0,5),p=>p.price),[3890,4290,3290,3190,2490]));
test('Retirada sem taxa',()=>{const t=B.totals(cart,'','pickup');assert.equal(t.total,3890);assert.equal(t.fee,0)});
test('Taxas de todos os bairros',()=>{for(const [n,fee]of Object.entries({Centro:690,Jardins:890,'Vila Nova':590})){const t=B.totals(cart,'','delivery',n);assert.equal(t.fee,fee);assert.equal(t.total,3890+fee)}});
test('Taxa ainda não definida',()=>{const t=B.totals(cart);assert.equal(t.fee,null);assert.equal(t.total,3890)});
test('BRASA15 arredonda centavos sem descontar entrega',()=>{const t=B.totals(cart,'BRASA15','delivery','Centro');assert.equal(t.discount,584);assert.equal(t.total,3996)});
test('Cupom ignora espaços e caixa',()=>assert.equal(B.totals(cart,' brasa15 ','pickup').discount,584));
test('Cupom inexistente não aplica desconto',()=>assert.equal(B.totals(cart,'INVALIDO','pickup').discount,0));
test('Adicionais e quantidades com cupom',()=>{const t=B.totals([{productId:'brasa-bacon',qty:2,extras:['bacon','cheddar','burger']}],'BRASA15','delivery','Jardins');assert.equal(t.subtotal,11980);assert.equal(t.discount,1797);assert.equal(t.total,11073)});
test('Remoção e observação não alteram preço',()=>assert.equal(B.unit({...cart[0],removed:['Cheddar'],note:'Sem sal'}),3890));
test('Restauração do carrinho rejeita itens inválidos',()=>{const result=B.validateCart([null,{},...cart,{productId:'fake',qty:1},{productId:'salad',qty:-2},{productId:'salad',qty:21}]);assert.equal(result.length,1)});
test('Restauração normaliza adicionais duplicados e desconhecidos',()=>assert.deepEqual(Array.from(B.validateCart([{productId:'brasa-bacon',qty:1,extras:['bacon','bacon','fake'],removed:['Cheddar','Fake']}])[0].extras),['bacon']));
test('Persistência salva e recupera carrinho',()=>{B.save('test-cart',cart);assert.equal(B.get('test-cart',[])[0].productId,'brasa-bacon')});
test('JSON corrompido usa valor padrão',()=>{storage.set('bad','{INVALID}');assert.equal(B.get('bad','fallback'),'fallback')});
test('Texto do usuário é escapado ao entrar no HTML',()=>assert.equal(B.escape('<img src="x" onerror="alert(1)">'), '&lt;img src=&quot;x&quot; onerror=&quot;alert(1)&quot;&gt;'));
test('Dez produtos com fotos individuais e hero locais',()=>{assert.equal(B.products.length,10);for(const p of B.products)assert.ok(fs.statSync('assets/'+p.id+'.png').size>1000);assert.ok(fs.statSync('assets/hero.png').size>1000)});
test('Fontes e licenças locais',()=>{for(const f of ['oswald.ttf','manrope.ttf','oswald-license.txt','manrope-license.txt'])assert.ok(fs.statSync('assets/'+f).size>0)});
test('Telas principais e painel presentes',()=>{for(const f of ['index.html','styles.css','app.js','shared.js','admin/index.html','admin/admin.css','admin/admin.js'])assert.ok(fs.statSync(f).size>0)});
test('JavaScript com sintaxe válida',()=>{for(const f of ['shared.js','app.js','admin/admin.js'])new vm.Script(fs.readFileSync(f,'utf8'),{filename:f})});
console.log('\n'+checks+' verificações concluídas. Sem backend ou serviços externos.');
