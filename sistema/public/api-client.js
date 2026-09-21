'use strict';
window.API={csrf:'',user:null,async request(action,data=null){
  const options={credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}};
  if(data!==null){options.method='POST';options.headers['X-CSRF-Token']=this.csrf;if(data instanceof FormData)options.body=data;else{options.headers['Content-Type']='application/json';options.body=JSON.stringify(data)}}
  const response=await fetch('/api.php?action='+encodeURIComponent(action),options);
  let body;try{body=await response.json()}catch{throw new Error('Não foi possível acessar o serviço. Tente novamente.')}
  if(!response.ok||!body.ok){const error=new Error(body.message||'Não foi possível concluir a solicitação.');error.status=response.status;throw error}
  if(body.data?.csrf)this.csrf=body.data.csrf;if(body.data&&Object.hasOwn(body.data,'user'))this.user=body.data.user;
  return body.data;
},async get(action,params={}){
  const query=new URLSearchParams({action,...params});const response=await fetch('/api.php?'+query,{credentials:'same-origin',cache:'no-store'});let body;try{body=await response.json()}catch{throw new Error('Não foi possível acessar o serviço.')}
  if(!response.ok||!body.ok){const error=new Error(body.message||'Serviço indisponível.');error.status=response.status;throw error}return body.data;
},async init(){return this.request('session')}};
