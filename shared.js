/* Dados e utilitários exclusivos do protótipo. Nenhuma conexão com serviços. */
window.Brasa = (() => {
  const icons = {
    arrow: '<path d="M4 12h16m-6-6 6 6-6 6"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    close: '<path d="m6 6 12 12M6 18 18 6"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    search: '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    pin: '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
    bag: '<path d="M5 7h14l1 14H4L5 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
    cart: '<path d="M3 3h2l2.5 12H19l2-9H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
    flame:
      '<path d="M13 2s1 5-3 8c-2-1-2-3-2-3s-5 5-4 9a8 8 0 0 0 16 0c0-6-7-14-7-14Z"/><path d="M12 13s-3 3-2 5a2 2 0 0 0 4 0c0-2-2-5-2-5Z"/>',
    burger:
      '<path d="M4 9a8 6 0 0 1 16 0H4Zm0 7h16v1a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-1ZM3 12h18M4 14h16"/><path d="m9 6 .1.1m6-.1 .1.1"/>',
    fries: '<path d="m5 10 2 11h10l2-11H5ZM7 10V4h3v6m1 0V2h3v8m1 0V5h3v5"/>',
    drink: '<path d="M6 8h12l-2 13H8L6 8Zm6 0 2-6h5M6 11h12"/>',
    dessert:
      '<path d="M4 10h16v10H4zM4 15h16M4 10l8-7 8 7"/><path d="M12 3V1"/>',
    grid: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    ticket:
      '<path d="M3 5h18v5a2 2 0 0 0 0 4v5H3v-5a2 2 0 0 0 0-4V5Z"/><path d="M9 5v2m0 3v2m0 3v4m4-10 4 6m-4 0 4-6"/>',
    copy: '<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V3H3v13h5"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>',
    moon: '<path d="M20 15.2A9 9 0 0 1 8.8 4 9 9 0 1 0 20 15.2Z"/>',
    whatsapp:
      '<path d="M20 11.5a8 8 0 0 1-12 7L3 20l1.5-5A8 8 0 1 1 20 11.5Z"/><path d="M8 7c-2 3 3 8 6 8l2-2-3-1-1 1-2-2 1-1-1-3H8Z"/>',
    trash: '<path d="M4 6h16M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7m4-7v7"/>',
    truck:
      '<path d="M2 5h12v12H2V5Zm12 5h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
    card: '<rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 9h20M6 15h4"/>',
    money:
      '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.1m11.8 0h.1"/>',
    pix: '<path d="m12 2 5 5-5 5-5-5 5-5Zm0 10 5 5-5 5-5-5 5-5ZM2 12l5-5 5 5-5 5-5-5Zm10 0 5-5 5 5-5 5-5-5Z"/>',
    user: '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
    lock: '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4m-4 4v3"/>',
    settings:
      '<path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1M5.6 18.4l2.1-2.1m8.6-8.6 2.1-2.1"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
    chart: '<path d="M3 3v18h18M7 16v-5m5 5V7m5 9v-8"/>',
    bell: '<path d="M4 17h16l-2-4V9a6 6 0 0 0-12 0v4l-2 4Zm5 3h6"/>',
    logout: '<path d="M9 4H3v16h6m5-4 4-4-4-4m-6 4h13"/>',
    edit: '<path d="m14 5 5 5M4 20l1-6L17 2l5 5L10 19l-6 1Z"/>',
    image:
      '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><path d="m3 17 6-5 4 3 4-5 4 7"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    star: '<path d="m12 3 3 6 6 1-4.5 4.5 1 6.5-5.5-3-5.5 3 1-6.5L3 10l6-1 3-6Z"/>',
  };
  const icon = (name) =>
    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[name] || icons.grid}</svg>`;
  const hydrate = (root = document) =>
    root
      .querySelectorAll("[data-icon]")
      .forEach((el) => (el.innerHTML = icon(el.dataset.icon)));
  const money = (n) =>
    new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(n / 100);
  const escape = (s) =>
    String(s ?? "").replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  const get = (key, fallback) => {
    try {
      return JSON.parse(localStorage.getItem(key)) ?? fallback;
    } catch {
      return fallback;
    }
  };
  const save = (key, data) => {
    try {
      localStorage.setItem(key, JSON.stringify(data));
      return true;
    } catch {
      return false;
    }
  };
  const products = [
    {
      id: "brasa-bacon",
      name: "Brasa Bacon",
      price: 3890,
      category: "burgers",
      badge: "O MAIS PEDIDO",
      featured: true,
      description:
        "Pão brioche, blend de 180g na brasa, cheddar, bacon crocante, cebola caramelizada e molho da casa.",
      ingredients: ["Cheddar", "Bacon", "Cebola caramelizada", "Molho da casa"],
      custom: true,
    },
    {
      id: "costela",
      name: "Burger de Costela",
      price: 4290,
      category: "burgers",
      badge: "SABOR DE RESPEITO",
      featured: true,
      description:
        "Pão brioche, burger de costela de 180g, queijo prato, rúcula, cebola crispy e barbecue.",
      ingredients: ["Queijo prato", "Rúcula", "Cebola crispy", "Barbecue"],
      custom: true,
    },
    {
      id: "x-bacon",
      name: "X-Bacon da Brasa",
      price: 3290,
      category: "burgers",
      badge: "CLÁSSICO DA CASA",
      featured: true,
      description:
        "Pão macio, burger de 120g na brasa, cheddar derretido, bacon crocante e maionese da casa.",
      ingredients: ["Cheddar", "Bacon", "Maionese da casa"],
      custom: true,
    },
    {
      id: "salad",
      name: "Brasa Salad",
      price: 3190,
      category: "burgers",
      description:
        "Pão brioche, blend de 180g, queijo, alface fresquinha, tomate e molho da casa.",
      ingredients: ["Queijo", "Alface", "Tomate", "Molho da casa"],
      custom: true,
    },
    {
      id: "batata",
      name: "Batata Brasa",
      price: 2490,
      category: "porcoes",
      description:
        "Batatas douradas e crocantes, cobertas com cheddar cremoso e bacon. Porção de 350g.",
      ingredients: ["Cheddar", "Bacon"],
      custom: true,
    },
    {
      id: "combo-individual",
      name: "Combo Brasa",
      price: 5490,
      category: "combos",
      badge: "COMBINAÇÃO PERFEITA",
      description:
        "Brasa Bacon + batata frita individual de 150g + refrigerante de cola de 350ml. A fome encontrou seu par.",
      ingredients: [],
      custom: false,
    },
    {
      id: "combo-duplo",
      name: "Combo em Boa Companhia",
      price: 9490,
      category: "combos",
      description:
        "Dois Brasa Bacon + batata frita de 300g + dois refrigerantes de cola de 350ml. Melhor quando é junto.",
      ingredients: [],
      custom: false,
    },
    {
      id: "cola",
      name: "Refrigerante de Cola",
      price: 790,
      category: "bebidas",
      description:
        "Cola geladinha, no copo de 350ml. O clássico que acompanha qualquer brasa.",
      ingredients: [],
      custom: false,
    },
    {
      id: "limonada",
      name: "Limonada da Casa",
      price: 1290,
      category: "bebidas",
      description:
        "Limão espremido na hora, gelo e o equilíbrio entre doce e cítrico. Copo de 400ml.",
      ingredients: [],
      custom: false,
    },
    {
      id: "brownie",
      name: "Brownie da Brasa",
      price: 1990,
      category: "sobremesas",
      description:
        "Brownie de chocolate quentinho com sorvete de baunilha e calda de chocolate. O final que você merece.",
      ingredients: [],
      custom: false,
    },
  ];
  const extras = [
    { id: "bacon", name: "Bacon extra", price: 500 },
    { id: "cheddar", name: "Cheddar extra", price: 400 },
    { id: "burger", name: "Burger extra · 180g", price: 1200 },
  ];
  const fees = { Centro: 690, Jardins: 890, "Vila Nova": 590 };
  const categories = [
    { id: "destaques", name: "Destaques", icon: "flame" },
    { id: "todos", name: "Todos", icon: "grid" },
    { id: "burgers", name: "Burgers", icon: "burger" },
    { id: "combos", name: "Combos", icon: "bag" },
    { id: "porcoes", name: "Porções", icon: "fries" },
    { id: "bebidas", name: "Bebidas", icon: "drink" },
    { id: "sobremesas", name: "Sobremesas", icon: "dessert" },
  ];
  const statuses = [
    "Recebido",
    "Confirmado",
    "Em preparação",
    "Saiu para entrega",
    "Entregue",
    "Cancelado",
    "Recusado",
  ];
  const unit = (item) => {
    const p = products.find((p) => p.id === item.productId);
    return p
      ? p.price +
          (item.extras || []).reduce(
            (s, id) => s + (extras.find((e) => e.id === id)?.price || 0),
            0,
          )
      : 0;
  };
  const totals = (cart, coupon = "", mode = "delivery", neighborhood = "") => {
    const subtotal = cart.reduce((s, i) => s + unit(i) * i.qty, 0);
    const discount =
      coupon.trim().toUpperCase() === "BRASA15"
        ? Math.round(subtotal * 0.15)
        : 0;
    const fee = mode === "pickup" ? 0 : (fees[neighborhood] ?? null);
    return { subtotal, discount, fee, total: subtotal - discount + (fee || 0) };
  };
  const validateCart = (cart) =>
    Array.isArray(cart)
      ? cart
          .filter(
            (i) =>
              i &&
              products.some((p) => p.id === i.productId) &&
              Number.isInteger(i.qty) &&
              i.qty > 0 &&
              i.qty <= 20,
          )
          .map((i) => ({
            ...i,
            extras: Array.isArray(i.extras)
              ? [...new Set(i.extras)]
                  .filter((id) => extras.some((e) => e.id === id))
                  .slice(0, 3)
              : [],
            removed: Array.isArray(i.removed)
              ? i.removed.filter((v) =>
                  products
                    .find((p) => p.id === i.productId)
                    .ingredients.includes(v),
                )
              : [],
            note: String(i.note || "").slice(0, 240),
          }))
      : [];
  let toastTimer;
  function toast(message) {
    const el = document.getElementById("toast");
    if (!el) return;
    el.textContent = message;
    el.classList.add("visible");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove("visible"), 3500);
  }
  function theme() {
    const value =
      document.documentElement.dataset.theme === "dark" ? "light" : "dark";
    document.documentElement.dataset.theme = value;
    try {
      localStorage.setItem("brasa-theme", value);
    } catch {}
    updateTheme();
  }
  function updateTheme() {
    document.querySelectorAll('[data-action="theme"]').forEach((b) => {
      const dark = document.documentElement.dataset.theme === "dark";
      b.innerHTML = icon(dark ? "sun" : "moon");
      b.setAttribute("aria-label", `Ativar tema ${dark ? "claro" : "escuro"}`);
    });
  }
  return {
    icon,
    hydrate,
    money,
    escape,
    get,
    save,
    products,
    extras,
    fees,
    categories,
    statuses,
    unit,
    totals,
    validateCart,
    toast,
    theme,
    updateTheme,
  };
})();
