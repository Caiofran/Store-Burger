<?php require __DIR__."/../src/bootstrap.php"; Brasa\headers(); ?>
<!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#14110f">
  <meta name="description" content="Brasa Burger Co. — hambúrguer artesanal feito na brasa. Delivery no Centro, Jardins e Vila Nova.">
  <title>Brasa Burger Co. · Feito na brasa. Sem atalhos.</title>
  <link rel="stylesheet" href="styles.css">

  <script src="shared.js" defer></script><script src="/api-client.js" defer></script><script src="app.js" defer></script>
</head>
<body>

<header class="site-header">
  <a class="brand" href="#inicio" aria-label="Brasa Burger Co. — início"><span class="logo-slot" title="Aguardando arquivo da logo oficial"><span>LOGO<br>OFICIAL</span></span><span class="brand-name">Brasa Burger Co.<small>ARTESANAL · FEITO NA BRASA</small></span></a>
  <nav aria-label="Navegação principal"><a class="nav-link active" href="#inicio">Início</a><a class="nav-link" href="#cardapio">Cardápio</a><a class="nav-link" href="#promocoes">Promoções</a><a class="nav-link" href="#duvidas">Dúvidas</a></nav>
  <div class="header-actions"><button class="track-button" data-action="account" aria-label="Minha conta"><i data-icon="user"></i></button><button class="track-button" data-action="track"><i data-icon="bag"></i><span>Acompanhar pedido</span></button><button class="cart-button" data-action="cart" aria-label="Abrir carrinho"><i data-icon="cart"></i><span class="cart-count">0</span></button></div>
</header>
<main id="inicio">
  <div class="service-line wrap"><span class="store-status"><b class="status-dot"></b><span id="store-status">Consultando horário</span></span><span><i data-icon="clock"></i>35–50 min</span><span><i data-icon="pin"></i>Centro, Jardins e Vila Nova</span><span class="service-last">Retirada grátis <span>·</span> Entrega a partir de R$ 5,90</span></div>
  <section class="hero wrap" aria-labelledby="hero-title">
    <img class="hero-photo" src="assets/hero.png" alt="Hambúrguer artesanal com cheddar derretido e bacon crocante, iluminado pela luz quente da brasa" fetchpriority="high">
    <div class="hero-shade"></div><div class="hero-content"><p class="eyebrow"><span></span> FOGO, SABOR E NADA DE ATALHOS.</p><h1 id="hero-title">A SUA FOME<br>PEDE <em>BRASA.</em></h1><p class="hero-description">Carne suculenta, pão macio e aquele sabor<br class="desktop-only"> de verdade. Da nossa brasa pra sua casa.</p><a class="button primary" href="#cardapio">Escolher meu burger <i data-icon="arrow"></i></a><div class="hero-bottom"><span class="little-fire"><i data-icon="flame"></i></span><span>Feito na hora.<br><strong>Do primeiro ao último mordidão.</strong></span></div></div>
    <div class="hero-label"><span>O QUERIDINHO DA CASA</span><strong>Brasa Bacon</strong><span>Na foto: com burger extra.</span></div>
    <div class="hero-index"><span>01</span> / BRASA ORIGINAL</div>
  </section>
  <section class="coupon-banner wrap" id="promocoes"><div class="coupon-icon"><i data-icon="ticket"></i></div><div><strong>Sua primeira brasa tem <em>15% OFF.</em></strong><p>Use o cupom no carrinho e deixe o resto com a gente.</p></div><button class="coupon-code" data-action="promo">BRASA15 <i data-icon="copy"></i></button><span class="coupon-note">Consulte as condições<br>ao revisar o pedido.</span></section>
  <section class="menu-section wrap" id="cardapio">
    <div class="section-heading"><div><p class="eyebrow">ESCOLHA SEU PRÓXIMO FAVORITO</p><h2>O CARDÁPIO <span>DA BRASA.</span></h2></div><label class="search"><i data-icon="search"></i><input type="search" id="menu-search" placeholder="O que vai ser hoje?" aria-label="Buscar no cardápio"></label></div>
    <nav class="categories" aria-label="Categorias" id="categories"></nav>
    <div class="menu-heading"><h3 id="menu-title"><i data-icon="flame"></i> Os favoritos da casa</h3><span id="product-count"></span></div>
    <div class="product-grid" id="product-grid"></div><div id="empty-search" class="empty-state" hidden><i data-icon="search"></i><h3>Nenhum sabor por aqui.</h3><p>Tente outro nome ou escolha uma categoria.</p><button class="button secondary" data-action="reset-search">Ver todo o cardápio</button></div>
  </section>
  <section class="craft-strip wrap"><div><i data-icon="flame"></i><strong>BRASA DE VERDADE</strong><span>O fogo faz a diferença.</span></div><div><i data-icon="burger"></i><strong>FEITO DO SEU JEITO</strong><span>Seu burger, sua combinação.</span></div><div><i data-icon="bag"></i><strong>CAPRICHO ATÉ A PORTA</strong><span>Preparado na hora para você.</span></div></section>
  <section class="faq-section wrap" id="duvidas"><div><p class="eyebrow">ANTES DA PRIMEIRA MORDIDA</p><h2>FICOU COM<br><span>ALGUMA DÚVIDA?</span></h2><a class="text-link" href="https://wa.me/5511999992026" target="_blank" rel="noopener">Fale com a nossa equipe <i data-icon="arrow"></i></a></div><div class="faq-list"><details><summary>Onde vocês entregam?<i data-icon="plus"></i></summary><p>Atendemos Centro (R$ 6,90), Jardins (R$ 8,90) e Vila Nova (R$ 5,90). O prazo estimado é de 35 a 50 minutos. Você também pode retirar gratuitamente na Rua das Brasas, 147 — Centro.</p></details><details><summary>Quais são os horários de funcionamento?<i data-icon="plus"></i></summary><p>De terça a domingo, das 18h às 23h30, no horário de Brasília. Segunda-feira a brasa descansa. Você pode agendar um pedido dentro do expediente.</p></details><details><summary>Como posso pagar meu pedido?<i data-icon="plus"></i></summary><p>Dinheiro, Pix, débito ou crédito, na entrega ou na retirada. Se precisar de troco, informe no checkout. O pagamento é realizado no recebimento.</p></details><details><summary>Posso personalizar meu hambúrguer?<i data-icon="plus"></i></summary><p>Sim! Abra um produto, escolha adicionais, retire ingredientes e deixe uma observação. Para dúvidas sobre alergênicos e restrições alimentares, converse com a equipe antes do pedido.</p></details><details><summary>Como funciona o cupom BRASA15?<i data-icon="plus"></i></summary><p>Digite BRASA15 no carrinho para ganhar 15% de desconto sobre os produtos e adicionais. A taxa de entrega é cobrada separadamente.</p></details></div></section>
</main>
<footer class="wrap"><div><strong>Brasa Burger Co.</strong><p>Rua das Brasas, 147 — Centro<br>Terça a domingo · 18h às 23h30</p></div><div><a href="https://wa.me/5511999992026" target="_blank" rel="noopener">(11) 99999-2026</a><p>Centro · Jardins · Vila Nova</p></div><div class="footer-end"><a href="/admin/">Área administrativa <i data-icon="arrow"></i></a><p>© 2026 Brasa Burger Co. · <a href="/privacidade.php">Privacidade</a></p></div></footer>
<div class="floating-actions"><button class="theme-button" data-action="theme" aria-label="Ativar tema claro" title="Alternar tema"><i data-icon="sun"></i></button><a class="whatsapp-button" href="https://wa.me/5511999992026" target="_blank" rel="noopener" aria-label="Falar com a Brasa Burger no WhatsApp"><i data-icon="whatsapp"></i></a></div>
<button class="mobile-cart" data-action="cart" hidden><span><i data-icon="bag"></i><b class="cart-count">0</b> Ver meu carrinho</span><strong id="mobile-total">R$ 0,00</strong></button>
<dialog id="account-dialog" class="checkout-dialog"></dialog>
<dialog id="product-dialog" class="product-dialog"></dialog>
<dialog id="cart-dialog" class="drawer"></dialog>
<dialog id="checkout-dialog" class="checkout-dialog"></dialog>
<dialog id="tracking-dialog" class="tracking-dialog"></dialog>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
</body></html>
