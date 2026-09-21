(() => {
  const B = Brasa,
    E = B.escape,
    I = B.icon,
    M = B.money,
    $ = (s) => document.querySelector(s);
  const root = $("#admin-root");
  let page = "dashboard",
    query = "",
    filter = "all";
  const navigation = [
    ["dashboard", "Visão geral", "grid"],
    ["orders", "Pedidos", "bag"],
    ["products", "Produtos", "burger"],
    ["categories", "Categorias", "grid"],
    ["extras", "Adicionais", "plus"],
    ["coupons", "Cupons", "ticket"],
    ["areas", "Áreas de entrega", "pin"],
    ["banners", "Banners", "image"],
    ["settings", "Configurações", "settings"],
    ["users", "Administradores", "user"],
  ];
  const defaults = {
    products: B.products.map((p) => ({ ...p, active: true })),
    categories: [
      { id: "burgers", name: "Burgers", position: 1, active: true },
      { id: "combos", name: "Combos", position: 2, active: true },
      { id: "porcoes", name: "Porções", position: 3, active: true },
      { id: "bebidas", name: "Bebidas", position: 4, active: true },
      { id: "sobremesas", name: "Sobremesas", position: 5, active: true },
    ],
    extras: [
      {
        id: "extra-1",
        name: "Uma dose extra de sabor",
        type: "Múltipla",
        min: 0,
        max: 3,
        options:
          "Bacon extra | 5,00\nCheddar extra | 4,00\nBurger extra · 180g | 12,00",
        active: true,
      },
      {
        id: "extra-2",
        name: "Ponto da carne",
        type: "Única",
        min: 1,
        max: 1,
        options: "Ao ponto | 0,00\nBem passado | 0,00",
        active: false,
      },
    ],
    coupons: [
      {
        id: "coupon-1",
        name: "BRASA15",
        discount: 15,
        minimum: 0,
        limit: 1,
        description: "15% sobre produtos e adicionais; entrega não inclusa.",
        active: true,
      },
    ],
    areas: Object.entries(B.fees).map(([name, fee], i) => ({
      id: "area-" + i,
      name,
      fee,
      minimum: 0,
      time: "35–50 min",
      active: true,
    })),
    banners: [
      {
        id: "banner-1",
        name: "A sua fome pede brasa.",
        subtitle: "Fogo, sabor e nada de atalhos.",
        cta: "Escolher meu burger",
        link: "#cardapio",
        image: "../assets/hero.png",
        active: true,
      },
      {
        id: "banner-2",
        name: "Sua primeira brasa tem 15% OFF.",
        subtitle: "Use o cupom BRASA15 no carrinho.",
        cta: "Aplicar BRASA15",
        link: "#promocoes",
        image: "",
        active: true,
      },
    ],
    users: [
      {
        id: "admin-1",
        name: "Equipe Brasa",
        email: "admin@brasa.demo",
        role: "Administrador",
        active: true,
      },
      {
        id: "admin-2",
        name: "Operação da cozinha",
        email: "cozinha@example.com",
        role: "Operador",
        active: true,
      },
    ],
  };
  const collections = Object.fromEntries(
    Object.keys(defaults).map((k) => [
      k,
      B.get("brasa-admin-" + k, defaults[k]),
    ]),
  );
  const settings = B.get("brasa-admin-settings", {
    name: "Brasa Burger Co.",
    phone: "(11) 99999-2026",
    address: "Rua das Brasas, 147 — Centro",
    timezone: "America/Sao_Paulo",
    paused: false,
    scheduled: true,
    minimum: 0,
    alertSound: false,
    hours: [
      { day: "Segunda", open: false, start: "18:00", end: "23:30" },
      ...["Terça", "Quarta", "Quinta", "Sexta", "Sábado", "Domingo"].map(
        (day) => ({ day, open: true, start: "18:00", end: "23:30" }),
      ),
    ],
  });
  const seedCustomers = [
    "Marina Alves",
    "Pedro Santos",
    "Júlia Costa",
    "Lucas Ferreira",
    "Camila Rocha",
    "Rafael Lima",
  ];
  const seedStatuses = [
    "Recebido",
    "Recebido",
    "Confirmado",
    "Em preparação",
    "Saiu para entrega",
    "Entregue",
  ];
  const seeds = B.get(
    "brasa-admin-seed-orders",
    seedCustomers.map((name, i) => ({
      number: String(2020 + i),
      created: new Date(Date.now() - (i + 1) * 7 * 60000).toISOString(),
      customer: {
        name,
        phone: "(11) 99999-2026",
        email: "cliente@example.com",
        street: "Rua de Demonstração",
        number: String(120 + i),
        complement: "",
        payment: i % 2 ? "Crédito" : "Pix",
        timing: "now",
      },
      cart: [
        {
          productId: B.products[i % B.products.length].id,
          qty: i % 3 === 0 ? 2 : 1,
          extras: [],
          removed: [],
          note: i === 3 ? "Cortar ao meio, por favor." : "",
        },
      ],
      totals: B.totals(
        [
          {
            productId: B.products[i % B.products.length].id,
            qty: i % 3 === 0 ? 2 : 1,
            extras: [],
          },
        ],
        "",
        i === 2 ? "pickup" : "delivery",
        i % 2 ? "Jardins" : "Centro",
      ),
      mode: i === 2 ? "pickup" : "delivery",
      neighborhood: i % 2 ? "Jardins" : "Centro",
      status: seedStatuses[i],
      seed: true,
    })),
  );
  let editing = null,
    uploadedImage = null;
  const allOrders = () =>
    [...B.get("brasa-orders", []), ...seeds].sort(
      (a, b) => new Date(b.created) - new Date(a.created),
    );
  const close = () =>
    `<button class="icon-button" data-admin="close" aria-label="Fechar">${I("close")}</button>`;
  const statusClass = (status) =>
    ["Entregue", "Retirado"].includes(status)
      ? "delivered"
      : ["Cancelado", "Recusado"].includes(status)
        ? "canceled"
        : status === "Recebido"
          ? "received"
          : status === "Em preparação"
            ? "preparing"
            : "";
  const statusTag = (status) =>
    `<span class="status-tag ${statusClass(status)}">${E(status)}</span>`;
  const activeTag = (active) =>
    `<span class="status-tag ${active ? "active" : "canceled"}">${active ? "Ativo" : "Pausado"}</span>`;
  const notice = () =>
    `<div class="admin-notice">${I("lock")}Ambiente de demonstração. Alterações ficam neste navegador. O acesso é uma simulação visual, sem proteção real.</div>`;
  const thumb = (p) => p.image || `../assets/${p.id}.png`;
  const time = (o) =>
    new Intl.DateTimeFormat("pt-BR", {
      timeZone: "America/Sao_Paulo",
      hour: "2-digit",
      minute: "2-digit",
    }).format(new Date(o.created));
  const productName = (id) =>
    collections.products.find((p) => p.id === id)?.name ||
    B.products.find((p) => p.id === id)?.name ||
    "Produto";
  function login() {
    root.innerHTML = `<main class="admin-login"><section class="login-visual"><img src="../assets/hero.png" alt="Hambúrguer Brasa Burger"><a href="../index.html" class="brand"><span class="logo-slot">LOGO<br>OFICIAL</span><span class="brand-name">Brasa Burger Co.<small>PAINEL DA EQUIPE</small></span></a><div><p class="eyebrow">POR TRÁS DE CADA MORDIDA.</p><h1>A BRASA<br>COMEÇA<br><em>POR AQUI.</em></h1><p>Pedidos organizados. Cozinha no ritmo.<br>Todo o cuidado que o seu cliente merece.</p></div></section><section class="login-panel"><div class="login-card"><div class="login-lock">${I("lock")}</div><p class="eyebrow">ÁREA ADMINISTRATIVA</p><h2>BOM TER VOCÊ AQUI.</h2><p>Acesse o painel de demonstração da Brasa Burger.</p><form id="admin-login-form"><label class="field"><span>E-mail</span><input name="email" type="email" required value="admin@brasa.demo" autocomplete="off"></label><label class="field"><span>Senha demonstrativa</span><input name="password" type="password" required value="brasa2026" autocomplete="off"></label><p id="login-error" class="error-message" role="alert"></p><button class="button primary full">Entrar no painel ${I("arrow")}</button></form><div class="demo-credentials"><strong>Acesso demonstrativo · sem autenticação real</strong>E-mail: admin@brasa.demo<br>Senha: brasa2026<br>Use apenas essas credenciais fictícias.</div><a href="../index.html" class="back-store">← Voltar para a loja</a></div></section></main>`;
  }
  function shell() {
    const pending = allOrders().filter((o) => o.status === "Recebido").length;
    root.innerHTML = `<div class="admin-layout"><aside class="admin-sidebar"><a class="brand" href="../index.html"><span class="logo-slot">LOGO<br>OFICIAL</span><span class="brand-name">Brasa Burger Co.<small>PAINEL DA EQUIPE</small></span></a><p class="sidebar-caption">OPERAÇÃO</p><nav class="admin-nav" aria-label="Navegação administrativa">${navigation.map(([id, label, icon], i) => `${i === 8 ? '<p class="sidebar-caption">GESTÃO</p>' : ""}<button data-page="${id}" class="${page === id ? "active" : ""}">${I(icon)}${label}${id === "orders" && pending ? `<span class="nav-count">${pending}</span>` : ""}</button>`).join("")}</nav><div style="flex:1;min-height:24px"></div><div class="sidebar-bottom"><span class="admin-avatar">EB</span><span><strong>Equipe Brasa</strong><small>Administrador · demo</small></span><button data-admin="logout" aria-label="Sair do painel">${I("logout")}</button></div></aside><main class="admin-main"><header class="admin-topbar"><button class="admin-mobile-menu" data-admin="menu" aria-label="Abrir menu administrativo">${I("menu")}</button><span class="breadcrumb">Painel <span>/</span><strong>${navigation.find((n) => n[0] === page)[1]}</strong></span><div class="topbar-actions"><span class="demo-pill">DEMONSTRAÇÃO</span><button class="store-toggle ${settings.paused ? "paused" : ""}" data-admin="pause"><b class="status-dot"></b>${settings.paused ? "Loja pausada" : "Operação ativa"}</button><button class="icon-button" data-admin="alerts" aria-label="Ver alertas de pedidos">${I("bell")}</button><button class="icon-button" data-action="theme" data-admin="theme" aria-label="Alternar tema">${I("sun")}</button><a href="../index.html">Ver loja ${I("arrow")}</a></div></header><div class="admin-content" id="admin-content"></div></main></div>`;
    renderPage();
    applyLogo();
    B.updateTheme();
  }
  function heading(title, description, action = "") {
    return `<div class="admin-page-heading"><div>${page === "dashboard" ? '<div class="dashboard-greeting">A CASA ESTÁ NO RITMO.</div>' : ""}<h1>${title}</h1><p>${description}</p></div>${action}</div>`;
  }
  function dashboard() {
    const orders = allOrders(),
      valid = orders.filter(
        (o) => !["Cancelado", "Recusado"].includes(o.status),
      ),
      sales = valid.reduce((s, o) => s + o.totals.total, 0),
      active = valid.filter((o) => o.status !== "Entregue"),
      average = valid.length ? Math.round(sales / valid.length) : 0;
    const chart = [18, 19, 20, 21, 22, 23].map((h) => ({
      hour: h,
      value: valid
        .filter(
          (o) =>
            Number(
              new Intl.DateTimeFormat("en-US", {
                timeZone: "America/Sao_Paulo",
                hour: "2-digit",
                hourCycle: "h23",
              }).format(new Date(o.created)),
            ) === h,
        )
        .reduce((s, o) => s + o.totals.total, 0),
    }));
    const max = Math.max(1, ...chart.map((c) => c.value));
    const ranked = Object.entries(
      valid
        .flatMap((o) => o.cart)
        .reduce(
          (m, i) => ((m[i.productId] = (m[i.productId] || 0) + i.qty), m),
          {},
        ),
    )
      .sort((a, b) => b[1] - a[1])
      .slice(0, 3);
    return (
      heading(
        "Boa noite, equipe Brasa.",
        "Uma visão do movimento e dos pedidos de demonstração.",
        `<button class="button secondary" data-page="orders">${I("bag")} Ver pedidos</button>`,
      ) +
      `<div class="metric-grid">${[
        [
          "Vendas simuladas",
          M(sales),
          "Total dos pedidos não cancelados",
          "money",
        ],
        [
          "Pedidos recebidos",
          String(orders.length),
          "Inclui exemplos e pedidos da loja",
          "bag",
        ],
        ["Ticket médio", M(average), "Por pedido não cancelado", "chart"],
        ["Na operação", String(active.length), "Aguardando conclusão", "flame"],
      ]
        .map(
          ([name, value, note, icon]) =>
            `<article class="metric-card"><div class="metric-top">${name}<i>${I(icon)}</i></div><strong>${value}</strong><span>${note}</span></article>`,
        )
        .join(
          "",
        )}</div><div class="dashboard-middle"><section class="admin-panel"><div class="panel-heading"><h2>Movimento por horário</h2><span>Brasília · dados simulados</span></div><div class="bar-chart" role="img" aria-label="Vendas simuladas por hora: ${chart.map((c) => `${c.hour} horas: ${M(c.value)}`).join(", ")}">${chart.map((c) => `<div class="bar-item"><span>${c.value ? M(c.value) : "—"}</span><div class="bar" style="height:${Math.max(2, (c.value / max) * 113)}px"></div><small>${c.hour}h</small></div>`).join("")}</div><p class="chart-note">Pedidos fora do expediente aparecem nos totais, sem entrar neste gráfico.</p></section><section class="admin-panel"><div class="panel-heading"><h2>Os favoritos da casa</h2><span>Unidades nos pedidos</span></div>${
        ranked.length
          ? ranked
              .map(([id, n], i) => {
                const p = collections.products.find((p) => p.id === id);
                return `<div class="top-product"><img src="${E(p ? thumb(p) : "../assets/brasa-bacon.png")}" alt="${E(productName(id))}"><div><strong>${E(productName(id))}</strong><small>#${i + 1} entre os mais pedidos</small></div><b>${n}</b></div>`;
              })
              .join("")
          : '<p class="muted">Os favoritos aparecerão com os pedidos.</p>'
      }</section></div><div class="panel-heading"><h2>Últimos pedidos</h2><a href="#orders" data-page="orders">Ver todos ${I("arrow")}</a></div>${ordersTable(orders.slice(0, 6))}${notice()}`
    );
  }
  function ordersTable(orders) {
    return `<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Pedido</th><th>Cliente</th><th>Recebimento</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>${orders.map((o) => `<tr><td><strong>#${E(o.number)}</strong><small>${time(o)} · ${o.seed ? "Exemplo" : "Loja simulada"}</small></td><td>${E(o.customer.name)}<small>${E(o.customer.payment)}</small></td><td>${o.mode === "pickup" ? "Retirada" : E(o.neighborhood)}</td><td><strong>${M(o.totals.total)}</strong></td><td>${statusTag(o.status)}</td><td><button class="icon-button" data-order="${E(o.number)}" aria-label="Ver pedido ${E(o.number)}">${I("arrow")}</button></td></tr>`).join("") || '<tr><td colspan="6">Nenhum pedido encontrado.</td></tr>'}</tbody></table></div>`;
  }
  function ordersPage() {
    const list = allOrders().filter((o) =>
      (o.number + " " + o.customer.name)
        .toLowerCase()
        .includes(query.toLowerCase()),
    );
    const activeStatuses = B.statuses.slice(0, 4);
    const filtered =
      filter === "all" ? list : list.filter((o) => o.status === filter);
    return (
      heading(
        "PEDIDOS NA BRASA.",
        "Do primeiro sinal até a última entrega. Clique em um pedido para ver os detalhes.",
        `<button class="button secondary" data-admin="alerts">${I("bell")} Alertas</button>`,
      ) +
      `<div class="table-toolbar"><label class="search">${I("search")}<input id="admin-search" value="${E(query)}" placeholder="Buscar pedido ou cliente" aria-label="Buscar pedidos"></label><select id="order-filter" aria-label="Filtrar por status"><option value="all">Em operação · Kanban</option>${B.statuses.map((s) => `<option ${filter === s ? "selected" : ""}>${E(s)}</option>`).join("")}</select></div>${
        filter === "all"
          ? `<div class="kanban">${activeStatuses
              .map((status, index) => {
                const items = filtered.filter((o) => o.status === status);
                return `<section class="kanban-column"><div class="kanban-heading"><span><b class="status-dot"></b>${status}</span><b>${items.length}</b></div>${items.map((o) => `<article class="kanban-card"><button class="card-detail" data-order="${E(o.number)}"><div class="kanban-card-top"><strong>#${E(o.number)}</strong><small>${time(o)}</small></div><h3>${E(o.customer.name)}</h3><p>${o.cart.map((i) => `${i.qty}× ${E(productName(i.productId))}`).join("<br>")}</p><div class="order-info"><strong>${M(o.totals.total)}</strong><span>${o.mode === "pickup" ? "Retirada" : E(o.neighborhood)}</span></div></button><button class="button ${index === 0 ? "primary" : "secondary"} full" data-advance="${E(o.number)}">${["Confirmar pedido", "Iniciar preparo", o.mode === "pickup" ? "Pronto para retirada" : "Enviar para entrega", o.mode === "pickup" ? "Concluir retirada" : "Concluir entrega"][index]} ${I("arrow")}</button></article>`).join("") || '<div class="kanban-empty">Tudo em dia por aqui.</div>'}</section>`;
              })
              .join(
                "",
              )}</div><p class="record-helper">Use o filtro para consultar entregues, cancelados e recusados. Os pedidos feitos na loja aparecem automaticamente neste navegador.</p>`
          : ordersTable(filtered)
      }${notice()}`
    );
  }
  const schemas = {
    products: {
      title: "PRODUTOS COM PERSONALIDADE.",
      description:
        "Organize os sabores, preços, imagens e a disponibilidade do cardápio.",
      singular: "produto",
      icon: "burger",
      fields: [
        ["name", "Nome do produto", "text"],
        ["price", "Preço em R$", "money"],
        ["category", "Categoria", "category"],
        ["description", "Descrição e ingredientes", "textarea"],
        ["badge", "Selo do produto (opcional)", "optional"],
        ["image", "Imagem do produto", "file"],
      ],
    },
    categories: {
      title: "CADA SABOR NO SEU LUGAR.",
      description:
        "Defina as categorias e a ordem em que aparecem no cardápio.",
      singular: "categoria",
      icon: "grid",
      fields: [
        ["name", "Nome da categoria", "text"],
        ["position", "Ordem de exibição", "number"],
      ],
    },
    extras: {
      title: "UM TOQUE A MAIS.",
      description:
        "Configure grupos de adicionais, limites e tipos de escolha.",
      singular: "grupo de adicionais",
      icon: "plus",
      fields: [
        ["name", "Nome do grupo", "text"],
        ["type", "Tipo de seleção", "selection"],
        ["min", "Mínimo de escolhas", "number"],
        ["max", "Máximo de escolhas", "number"],
        ["options", "Opções e preços · uma por linha", "textarea"],
      ],
    },
    coupons: {
      title: "UM BOM MOTIVO PRA PEDIR.",
      description: "Cupons e condições para dar mais sabor à próxima compra.",
      singular: "cupom",
      icon: "ticket",
      fields: [
        ["name", "Código do cupom", "text"],
        ["discount", "Desconto em %", "percent"],
        ["minimum", "Valor mínimo em R$", "money"],
        ["limit", "Limite de uso por cliente", "number"],
        ["description", "Condições", "textarea"],
      ],
    },
    areas: {
      title: "ATÉ ONDE A BRASA CHEGA.",
      description: "Bairros atendidos, taxas, prazos e pedido mínimo.",
      singular: "área de entrega",
      icon: "pin",
      fields: [
        ["name", "Nome do bairro", "text"],
        ["fee", "Taxa de entrega em R$", "money"],
        ["minimum", "Pedido mínimo em R$", "money"],
        ["time", "Prazo estimado", "text"],
      ],
    },
    banners: {
      title: "A VITRINE DA SUA BRASA.",
      description: "Organize os destaques da página inicial e suas chamadas.",
      singular: "banner",
      icon: "image",
      fields: [
        ["name", "Título", "text"],
        ["subtitle", "Texto de apoio", "text"],
        ["cta", "Texto do botão", "text"],
        ["link", "Destino do botão (ex.: #cardapio)", "text"],
        ["image", "Imagem do banner", "file"],
      ],
    },
    users: {
      title: "QUEM CUIDA DA BRASA.",
      description: "Visualize os perfis e a organização dos acessos da equipe.",
      singular: "administrador",
      icon: "user",
      fields: [
        ["name", "Nome completo", "text"],
        ["email", "E-mail", "email"],
        ["role", "Perfil de acesso", "role"],
      ],
    },
  };
  function collectionPage() {
    const s = schemas[page],
      list = collections[page].filter((r) =>
        (r.name + " " + (r.description || ""))
          .toLowerCase()
          .includes(query.toLowerCase()),
      );
    let content =
      heading(
        s.title,
        s.description,
        `<button class="button primary" data-admin="new">${I("plus")} Adicionar ${s.singular}</button>`,
      ) +
      `<div class="table-toolbar"><label class="search">${I("search")}<input id="admin-search" value="${E(query)}" placeholder="Buscar ${s.singular}" aria-label="Buscar ${s.singular}"></label><span class="muted">${list.length} registros</span></div>`;
    if (page === "products" || page === "users")
      content += `<div class="admin-table-wrap"><table class="admin-table"><thead><tr>${page === "products" ? "<th>Produto</th><th>Categoria</th><th>Preço</th>" : "<th>Nome</th><th>E-mail</th><th>Perfil</th>"}<th>Status</th><th>Ações</th></tr></thead><tbody>${list.map((r) => `<tr>${page === "products" ? `<td><div class="table-product"><img src="${E(thumb(r))}" alt="${E(r.name)}"><span><strong>${E(r.name)}</strong><small>${E(r.badge || "Artesanal · Brasa Burger")}</small></span></div></td><td>${E(collections.categories.find((c) => c.id === r.category)?.name || r.category)}</td><td><strong>${M(r.price)}</strong></td>` : `<td><strong>${E(r.name)}</strong></td><td>${E(r.email)}</td><td>${E(r.role)}</td>`}<td>${activeTag(r.active)}</td><td><div class="table-actions"><button class="icon-button" data-edit="${E(r.id)}" aria-label="Editar ${E(r.name)}">${I("edit")}</button><button class="icon-button" data-toggle="${E(r.id)}" aria-label="${r.active ? "Pausar" : "Ativar"} ${E(r.name)}">${I(r.active ? "minus" : "check")}</button><button class="icon-button" data-delete="${E(r.id)}" aria-label="Excluir ${E(r.name)}">${I("trash")}</button></div></td></tr>`).join("") || '<tr><td colspan="5">Nenhum registro encontrado.</td></tr>'}</tbody></table></div>`;
    else
      content += `<div class="record-grid">${list.map((r) => `<article class="record-card"><div class="record-top"><span class="record-icon">${I(s.icon)}</span>${activeTag(r.active)}</div>${page === "banners" && r.image ? `<img class="record-banner" src="${E(r.image)}" alt="${E(r.name)}">` : ""}<h3>${E(r.name)}</h3>${page === "categories" ? `<p>Posição ${r.position} no cardápio</p><strong class="record-value">${collections.products.filter((p) => p.category === r.id).length} <small style="font-size:12px;font-weight:500">produtos</small></strong>` : page === "extras" ? `<p>Seleção ${E(r.type.toLowerCase())} · mínimo ${r.min} / máximo ${r.max}</p><p>${E(r.options).replaceAll("\n", "<br>")}</p>` : page === "coupons" ? `<strong class="record-value">${r.discount}% OFF</strong><p>${E(r.description)}</p><p>Pedido mínimo: ${M(r.minimum)}<br>Limite: ${r.limit} uso(s) por cliente</p>` : page === "areas" ? `<strong class="record-value">${M(r.fee)}</strong><p>${I("clock")} ${E(r.time)}<br>Pedido mínimo: ${M(r.minimum)}</p>` : `<p>${E(r.subtitle)}<br>Botão: ${E(r.cta)}</p>`}<div class="record-bottom"><button data-edit="${E(r.id)}">${I("edit")} Editar</button><button data-toggle="${E(r.id)}">${r.active ? "Pausar" : "Ativar"}</button><button class="delete-record" data-delete="${E(r.id)}" aria-label="Excluir ${E(r.name)}">${I("trash")}</button></div></article>`).join("") || '<div class="empty-state"><h3>Nenhum registro encontrado.</h3><p>Adicione um registro ou tente outra busca.</p></div>'}</div>`;
    content += `<p class="record-helper">${page === "products" ? "A edição do catálogo pode ser explorada aqui; a vitrine pública mantém o cardápio do briefing nesta etapa." : page === "users" ? "Os perfis são demonstrativos. Nenhuma conta ou permissão real é criada." : page === "coupons" ? "As condições são ilustrativas. A compra simulada utiliza BRASA15 com 15% sobre os produtos." : page === "areas" ? "A loja simulada mantém as taxas do briefing: Centro R$ 6,90, Jardins R$ 8,90 e Vila Nova R$ 5,90." : "As alterações desta tela são demonstrativas e ficam disponíveis no painel após atualizar."}</p>${notice()}`;
    return content;
  }
  function settingsPage() {
    return (
      heading(
        "O JEITO BRASA DE FUNCIONAR.",
        "Informações da loja, horário de Brasília e controles da operação.",
      ) +
      `<form id="settings-form"><div class="settings-grid"><section class="admin-panel"><h2>${I("burger")} Informações da loja</h2>${[
        ["name", "Nome da hamburgueria"],
        ["phone", "WhatsApp"],
        ["address", "Endereço"],
      ]
        .map(
          ([key, label]) =>
            `<label class="field"><span>${label}</span><input name="${key}" value="${E(settings[key])}" required maxlength="150"></label>`,
        )
        .join(
          "",
        )}<label class="field"><span>Logo oficial</span><input type="file" id="official-logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"><small>Envie o arquivo oficial. Ele será exibido sem alteração na loja e no painel.</small></label><div id="logo-preview">${B.get("brasa-official-logo", "") ? `<img src="${E(B.get("brasa-official-logo", ""))}" alt="Logo oficial" style="max-width:160px;max-height:100px;object-fit:contain">` : '<p class="muted">Aguardando o arquivo da logo oficial.</p>'}</div></section><section class="admin-panel"><h2>${I("clock")} Horário de funcionamento</h2><label class="field"><span>Fuso horário</span><select name="timezone"><option value="America/Sao_Paulo">Brasília · America/Sao_Paulo</option></select></label>${settings.hours.map((h, i) => `<div class="schedule-row"><input type="checkbox" name="day-${i}" ${h.open ? "checked" : ""} aria-label="Abrir ${h.day}"><span>${h.day}</span><input type="time" name="start-${i}" value="${h.start}" aria-label="Abertura ${h.day}" required><span style="min-width:0">até</span><input type="time" name="end-${i}" value="${h.end}" aria-label="Fechamento ${h.day}" required></div>`).join("")}</section><section class="admin-panel"><h2>${I("settings")} Operação</h2><label class="check-option"><input name="paused" type="checkbox" ${settings.paused ? "checked" : ""}>Pausar recebimento manualmente</label><label class="check-option"><input name="scheduled" type="checkbox" ${settings.scheduled ? "checked" : ""}>Permitir pedidos agendados</label><label class="field" style="margin-top:20px"><span>Pedido mínimo em R$</span><input name="minimum" type="number" min="0" step="0.01" value="${settings.minimum / 100}"></label><p class="muted">Configurações de operação demonstrativas. A loja continua disponível para simular a compra.</p></section><section class="admin-panel"><h2>${I("bell")} Alertas de pedidos</h2><label class="check-option"><input name="alertSound" type="checkbox" ${settings.alertSound ? "checked" : ""}>Habilitar som ao receber pedido</label><p class="muted" style="margin:15px 0">Os alertas aparecem no painel quando um pedido simulado é criado na loja, aberta neste mesmo navegador.</p><button class="button secondary" type="button" data-admin="test-sound">${I("bell")} Testar som de alerta</button></section></div><p id="settings-error" class="error-message" role="alert"></p><div class="settings-save"><p>Configurações salvas apenas neste navegador.</p><button class="button primary">Salvar configurações ${I("check")}</button></div></form>${notice()}`
    );
  }
  function renderPage() {
    const content = $("#admin-content");
    content.innerHTML =
      page === "dashboard"
        ? dashboard()
        : page === "orders"
          ? ordersPage()
          : page === "settings"
            ? settingsPage()
            : collectionPage();
  }
  function switchPage(id) {
    if (!navigation.some((n) => n[0] === id)) return;
    page = id;
    query = "";
    filter = "all";
    history.replaceState(null, "", "#" + page);
    shell();
    window.scrollTo(0, 0);
  }
  function editRecord(id = null) {
    const schema = schemas[page];
    const existing = id ? collections[page].find((r) => r.id === id) : null;
    editing = { page, id };
    uploadedImage = null;
    const r = existing || {
      name: "",
      price: 0,
      category: "burgers",
      description: "",
      badge: "",
      active: true,
      position: collections[page].length + 1,
      type: "Múltipla",
      min: 0,
      max: 3,
      options: "",
      discount: 15,
      minimum: 0,
      limit: 1,
      fee: 590,
      time: "35–50 min",
      subtitle: "",
      cta: "Ver cardápio",
      link: "#cardapio",
      email: "",
      role: "Operador",
    };
    $("#admin-dialog").innerHTML =
      `<div class="dialog-head"><h2 id="admin-dialog-title">${existing ? "Editar" : "Adicionar"} ${schema.singular}</h2>${close()}</div><form id="record-form" class="dialog-body admin-form">${schema.fields
        .map(([key, label, type]) => {
          const value = r[key] ?? "";
          if (type === "file")
            return `<label class="field"><span>${label}</span><input type="file" id="record-image" accept="image/png,image/jpeg,image/webp"><small>Arquivo local de até 3 MB. A imagem original será preservada.</small></label><div id="record-image-preview">${value ? `<img src="${E(value)}" alt="Prévia da imagem" style="height:100px;border-radius:6px">` : page === "products" && existing ? `<img src="${E(thumb(r))}" alt="${E(r.name)}" style="height:100px;border-radius:6px">` : ""}</div>`;
          if (["category", "selection", "role"].includes(type)) {
            const options =
              type === "category"
                ? collections.categories.map((c) => [c.id, c.name])
                : type === "selection"
                  ? [
                      ["Múltipla", "Múltipla"],
                      ["Única", "Única"],
                    ]
                  : [
                      ["Administrador", "Administrador"],
                      ["Operador", "Operador"],
                      ["Gerente", "Gerente"],
                    ];
            return `<label class="field"><span>${label}</span><select name="${key}">${options.map(([v, label]) => `<option value="${E(v)}" ${value === v ? "selected" : ""}>${E(label)}</option>`).join("")}</select></label>`;
          }
          return `<label class="field"><span>${label}</span>${type === "textarea" ? `<textarea name="${key}" required maxlength="800" ${key === "options" ? 'placeholder="Bacon extra | 5,00"' : ""}>${E(value)}</textarea>${key === "options" ? "<small>Formato: nome | preço. Ex.: Bacon extra | 5,00</small>" : ""}` : `<input name="${key}" value="${E(type === "money" ? Number(value) / 100 : value)}" type="${["money", "number", "percent"].includes(type) ? "number" : type === "email" ? "email" : "text"}" ${type === "optional" ? "" : "required"} ${["money", "number", "percent"].includes(type) ? `min="0" step="${type === "money" ? ".01" : "1"}" ${type === "percent" ? 'max="100"' : ""}` : 'maxlength="150"'}>`}</label>`;
        })
        .join(
          "",
        )}<label class="check-option"><input type="checkbox" name="active" ${r.active ? "checked" : ""}>Ativo no painel</label><p class="error-message" id="record-error" role="alert"></p><button class="button primary full">Salvar ${schema.singular} ${I("check")}</button></form>`;
    $("#admin-dialog").setAttribute("aria-labelledby", "admin-dialog-title");
    $("#admin-dialog").showModal();
  }
  function saveRecord(form) {
    const { page: group, id } = editing,
      schema = schemas[group],
      data = new FormData(form);
    let record = id
      ? { ...collections[group].find((r) => r.id === id) }
      : { id: group + "-" + Date.now() };
    for (const [key, label, type] of schema.fields) {
      if (type === "file") continue;
      const value = String(data.get(key) || "").trim();
      record[key] =
        type === "money"
          ? Math.round(Number(value) * 100)
          : ["number", "percent"].includes(type)
            ? Number(value)
            : value;
    }
    record.active = data.has("active");
    if (group === "extras") {
      if (
        record.min > record.max ||
        (record.type === "Única" && (record.max !== 1 || record.min > 1))
      ) {
        $("#record-error").textContent =
          "O mínimo não pode superar o máximo. Para seleção única, use máximo 1.";
        return;
      }
      if (
        !record.options
          .split("\n")
          .every((line) => /^.+\|\s*\d+(?:[.,]\d{1,2})?\s*$/.test(line))
      ) {
        $("#record-error").textContent =
          "Use uma opção por linha, no formato: nome | preço (ex.: Bacon extra | 5,00).";
        return;
      }
    }
    if (group === "coupons") {
      record.name = record.name.toUpperCase();
      if (
        collections.coupons.some((c) => c.id !== id && c.name === record.name)
      ) {
        $("#record-error").textContent = "Já existe um cupom com esse código.";
        return;
      }
    }
    if (uploadedImage) record.image = uploadedImage;
    if (group === "products" && !id && !record.image) {
      $("#record-error").textContent =
        "Escolha uma imagem local para o novo produto.";
      return;
    }
    if (!id) collections[group].push(record);
    else
      collections[group][collections[group].findIndex((r) => r.id === id)] =
        record;
    if (!B.save("brasa-admin-" + group, collections[group])) {
      $("#record-error").textContent =
        "O navegador ficou sem espaço. Escolha uma imagem menor e tente novamente.";
      return;
    }
    $("#admin-dialog").close();
    renderPage();
    B.toast("Alteração salva no painel de demonstração.");
  }
  function askDelete(id) {
    const record = collections[page].find((r) => r.id === id);
    if (!record) return;
    $("#admin-dialog").innerHTML =
      `<div class="dialog-head"><h2>Excluir este registro?</h2>${close()}</div><div class="dialog-body"><p>Excluir <strong>${E(record.name)}</strong> do painel de demonstração?</p><p class="muted" style="margin-top:10px">O registro será removido da lista salva neste navegador.</p><div class="checkout-actions"><button class="button secondary" data-admin="close">Voltar</button><button class="button danger" data-confirm-delete="${E(id)}">Excluir registro</button></div></div>`;
    $("#admin-dialog").showModal();
  }
  function setOrderStatus(number, status) {
    const order = allOrders().find((o) => o.number === number);
    if (!order) return;
    if (order.seed) {
      seeds.find((o) => o.number === number).status = status;
      B.save("brasa-admin-seed-orders", seeds);
    } else {
      const orders = B.get("brasa-orders", []);
      orders.find((o) => o.number === number).status = status;
      B.save("brasa-orders", orders);
    }
    renderPage();
    B.toast(`Pedido #${number}: ${status.toLowerCase()}.`);
  }
  function orderDetail(number) {
    const o = allOrders().find((o) => o.number === number);
    if (!o) return;
    $("#admin-dialog").innerHTML =
      `<div class="dialog-head"><h2 id="admin-dialog-title">Pedido #${E(o.number)}</h2>${close()}</div><div class="dialog-body"><div class="panel-heading">${statusTag(o.status)}<span>${time(o)} · ${o.seed ? "Exemplo de operação" : "Criado na loja simulada"}</span></div><div class="order-detail-metadata"><div><small>Cliente</small><strong>${E(o.customer.name)}</strong><br>${E(o.customer.phone)}</div><div><small>Recebimento</small><strong>${o.mode === "pickup" ? "Retirada" : E(o.neighborhood)}</strong><br>${o.mode === "pickup" ? "Rua das Brasas, 147 — Centro" : `${E(o.customer.street)}, ${E(o.customer.number)}`}</div><div><small>Pagamento</small>${E(o.customer.payment)} na ${o.mode === "pickup" ? "retirada" : "entrega"}${o.customer.payment === "Dinheiro" && o.customer.change ? `<br>Troco para ${M(Math.round(Number(o.customer.change) * 100))}` : ""}</div><div><small>Horário</small>${o.customer.timing === "scheduled" ? E(o.customer.schedule.replace("T", " às ")) : "O quanto antes"}</div></div><div class="order-detail-products">${o.cart.map((i) => `<div><span><strong>${i.qty}× ${E(productName(i.productId))}</strong>${i.extras?.length ? `<small>+ ${i.extras.map((id) => E(B.extras.find((e) => e.id === id)?.name || id)).join(", ")}</small>` : ""}${i.removed?.length ? `<small>Sem ${i.removed.map(E).join(", ")}</small>` : ""}${i.note ? `<small>Observação: ${E(i.note)}</small>` : ""}</span><strong>${M(B.unit(i) * i.qty)}</strong></div>`).join("")}</div><div class="totals"><div><span>Subtotal</span><span>${M(o.totals.subtotal)}</span></div>${o.totals.discount ? `<div class="discount"><span>Cupom ${E(o.coupon)}</span><span>− ${M(o.totals.discount)}</span></div>` : ""}<div><span>Entrega</span><span>${o.totals.fee ? M(o.totals.fee) : "Grátis"}</span></div><div class="total"><span>Total</span><strong>${M(o.totals.total)}</strong></div></div><form id="order-status-form" data-number="${E(o.number)}"><label class="field"><span>Atualizar status do pedido</span><select name="status">${B.statuses.map((s, i) => `<option value="${s}" ${s === o.status ? "selected" : ""}>${o.mode === "pickup" && i === 3 ? "Pronto para retirada" : o.mode === "pickup" && i === 4 ? "Retirado" : s}</option>`).join("")}</select></label><button class="button primary full">Salvar status ${I("check")}</button></form></div>`;
    $("#admin-dialog").setAttribute("aria-labelledby", "admin-dialog-title");
    $("#admin-dialog").showModal();
  }
  function alerts() {
    const waiting = allOrders().filter((o) => o.status === "Recebido");
    $("#admin-dialog").innerHTML =
      `<div class="dialog-head"><h2>Alertas da operação</h2>${close()}</div><div class="dialog-body"><p class="eyebrow">${waiting.length} PEDIDO(S) AGUARDANDO</p>${waiting.length ? waiting.map((o) => `<button class="info-box" style="width:100%;text-align:left" data-alert-order="${E(o.number)}"><strong>${I("bell")} Pedido #${E(o.number)} · ${M(o.totals.total)}</strong><p>${E(o.customer.name)} · recebido às ${time(o)}</p></button>`).join("") : '<div class="empty-state"><h3>Tudo em dia.</h3><p>Nenhum pedido aguardando confirmação.</p></div>'}<button class="button secondary full" data-admin="test-sound">Testar som de alerta</button></div>`;
    $("#admin-dialog").showModal();
  }
  function playSound() {
    try {
      const Audio = window.AudioContext || window.webkitAudioContext;
      const ctx = new Audio();
      const oscillator = ctx.createOscillator(),
        gain = ctx.createGain();
      oscillator.connect(gain);
      gain.connect(ctx.destination);
      oscillator.frequency.setValueAtTime(660, ctx.currentTime);
      oscillator.frequency.setValueAtTime(880, ctx.currentTime + 0.14);
      gain.gain.setValueAtTime(0.09, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
      oscillator.start();
      oscillator.stop(ctx.currentTime + 0.5);
      oscillator.onended = () => ctx.close();
    } catch {
      B.toast("O som não está disponível neste navegador.");
    }
  }
  function applyLogo() {
    const src = B.get("brasa-official-logo", "");
    if (src)
      document.querySelectorAll(".logo-slot").forEach((slot) => {
        slot.innerHTML = `<img src="${E(src)}" alt="Logo oficial Brasa Burger Co." style="width:100%;height:100%;object-fit:contain">`;
        slot.style.border = "none";
      });
  }
  function loadImage(input, official = false) {
    const f = input.files?.[0];
    if (!f) return;
    if (f.size > 3 * 1024 * 1024) {
      B.toast("Escolha uma imagem de até 3 MB para a demonstração.");
      input.value = "";
      return;
    }
    if (
      ![
        "image/png",
        "image/jpeg",
        "image/webp",
        ...(official ? ["image/svg+xml"] : []),
      ].includes(f.type)
    ) {
      B.toast("Escolha uma imagem PNG, JPG ou WebP.");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      const src = String(reader.result);
      if (official) {
        if (!B.save("brasa-official-logo", src)) {
          B.toast("Sem espaço no navegador. Use uma imagem menor.");
          return;
        }
        $("#logo-preview").innerHTML =
          `<img src="${E(src)}" alt="Logo oficial" style="max-width:160px;max-height:100px;object-fit:contain">`;
        applyLogo();
        B.toast(
          "Logo oficial aplicada sem alterações. Atualize a loja para vê-la.",
        );
      } else {
        uploadedImage = src;
        $("#record-image-preview").innerHTML =
          `<img src="${E(src)}" alt="Prévia da imagem selecionada" style="max-height:150px;border-radius:7px">`;
      }
    };
    reader.readAsDataURL(f);
  }
  document.addEventListener("click", (e) => {
    const t = e.target.closest("button,a");
    if (!t) return;
    if (t.dataset.page) {
      e.preventDefault();
      switchPage(t.dataset.page);
      return;
    }
    if (t.dataset.edit) {
      editRecord(t.dataset.edit);
      return;
    }
    if (t.dataset.toggle) {
      const r = collections[page].find((r) => r.id === t.dataset.toggle);
      r.active = !r.active;
      B.save("brasa-admin-" + page, collections[page]);
      renderPage();
      B.toast(r.active ? "Registro ativado." : "Registro pausado.");
      return;
    }
    if (t.dataset.delete) {
      askDelete(t.dataset.delete);
      return;
    }
    if (t.dataset.confirmDelete) {
      collections[page] = collections[page].filter(
        (r) => r.id !== t.dataset.confirmDelete,
      );
      B.save("brasa-admin-" + page, collections[page]);
      $("#admin-dialog").close();
      renderPage();
      B.toast("Registro excluído do painel.");
      return;
    }
    if (t.dataset.order) {
      orderDetail(t.dataset.order);
      return;
    }
    if (t.dataset.alertOrder) {
      $("#admin-dialog").close();
      orderDetail(t.dataset.alertOrder);
      return;
    }
    if (t.dataset.advance) {
      const o = allOrders().find((o) => o.number === t.dataset.advance);
      setOrderStatus(o.number, B.statuses[B.statuses.indexOf(o.status) + 1]);
      return;
    }
    const a = t.dataset.admin;
    if (a === "close") $("#admin-dialog").close();
    if (a === "theme") B.theme();
    if (a === "logout") {
      try {
        sessionStorage.removeItem("brasa-admin-demo");
      } catch {}
      login();
    }
    if (a === "new") editRecord();
    if (a === "menu") $(".admin-sidebar").classList.toggle("visible");
    if (a === "pause") {
      settings.paused = !settings.paused;
      B.save("brasa-admin-settings", settings);
      shell();
      B.toast(
        settings.paused
          ? "Pausa manual simulada ativada."
          : "Operação simulada retomada.",
      );
    }
    if (a === "alerts") alerts();
    if (a === "test-sound") {
      playSound();
      B.toast("Teste do som de novo pedido.");
    }
  });
  document.addEventListener("input", (e) => {
    if (e.target.id === "admin-search") {
      query = e.target.value;
      const pos = e.target.selectionStart;
      renderPage();
      const input = $("#admin-search");
      input.focus();
      input.setSelectionRange(pos, pos);
    }
  });
  document.addEventListener("change", (e) => {
    if (e.target.id === "order-filter") {
      filter = e.target.value;
      renderPage();
    }
    if (e.target.id === "record-image") loadImage(e.target);
    if (e.target.id === "official-logo") loadImage(e.target, true);
  });
  document.addEventListener("submit", (e) => {
    const f = e.target;
    e.preventDefault();
    const data = new FormData(f);
    if (f.id === "admin-login-form") {
      if (
        data.get("email").trim().toLowerCase() === "admin@brasa.demo" &&
        data.get("password") === "brasa2026"
      ) {
        try {
          sessionStorage.setItem("brasa-admin-demo", "true");
        } catch {}
        page = location.hash.slice(1) || "dashboard";
        if (!navigation.some((n) => n[0] === page)) page = "dashboard";
        shell();
      } else
        $("#login-error").textContent =
          "Use o e-mail admin@brasa.demo e a senha brasa2026.";
    }
    if (f.id === "record-form") saveRecord(f);
    if (f.id === "order-status-form") {
      setOrderStatus(f.dataset.number, data.get("status"));
      $("#admin-dialog").close();
    }
    if (f.id === "settings-form") {
      for (const key of ["name", "phone", "address", "timezone"])
        settings[key] = String(data.get(key)).trim();
      for (const key of ["paused", "scheduled", "alertSound"])
        settings[key] = data.has(key);
      settings.minimum = Math.round(Number(data.get("minimum")) * 100);
      const hours = settings.hours.map((h, i) => ({
        ...h,
        open: data.has("day-" + i),
        start: data.get("start-" + i),
        end: data.get("end-" + i),
      }));
      if (hours.some((h) => h.open && h.end <= h.start)) {
        $("#settings-error").textContent =
          "O fechamento precisa ser depois da abertura em cada dia ativo.";
        return;
      }
      settings.hours = hours;
      B.save("brasa-admin-settings", settings);
      B.toast("Configurações de demonstração salvas.");
      shell();
    }
  });
  document.addEventListener("click", (e) => {
    const d = $("#admin-dialog");
    if (e.target === d) {
      const r = d.getBoundingClientRect();
      if (
        e.clientX < r.left ||
        e.clientX > r.right ||
        e.clientY < r.top ||
        e.clientY > r.bottom
      )
        d.close();
    }
  });
  window.addEventListener("storage", (e) => {
    if (e.key === "brasa-orders" && $(".admin-layout")) {
      if (settings.alertSound) playSound();
      if (["dashboard", "orders"].includes(page)) renderPage();
      B.toast("A operação recebeu uma atualização da loja.");
    }
  });
  let logged = false;
  try {
    logged = sessionStorage.getItem("brasa-admin-demo") === "true";
  } catch {}
  if (logged) {
    page = location.hash.slice(1) || "dashboard";
    if (!navigation.some((n) => n[0] === page)) page = "dashboard";
    shell();
  } else login();
})();
