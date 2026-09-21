# Brasa Burger Co. — protótipo visual navegável

Esta entrega é exclusivamente um protótipo em HTML, CSS e JavaScript puro. Não há backend, banco de dados, autenticação real, envio de pedidos, mensagens automáticas ou cobranças. A etapa de PHP, MySQL, segurança e implantação permanece pendente de aprovação expressa deste protótipo; nenhuma arquitetura de produção foi criada.

## Abrir e explorar

- Loja: `index.html`.
- Painel: `admin/index.html` (também disponível em `/admin/` na prévia local).
- Abra os arquivos no navegador ou use a prévia local apresentada na conversa.
- Na prévia servida pelo mesmo endereço local, pedidos da loja aparecem no painel e mudanças de status podem ser acompanhadas entre abas. Ao abrir por `file://`, alguns navegadores isolam o armazenamento de cada arquivo, de modo que essa comunicação pode não acontecer.
- Todos os estilos, scripts, fontes e fotografias estão dentro do projeto. Só o contato por WhatsApp abre um serviço externo.

## Conceito visual

**Brasa urbana:** uma hamburgueria artesanal premium com atmosfera noturna, fotografia gastronômica quente e elementos de interface discretos. O laranja conduz as ações de compra. O hero usa composição editorial com título grande à esquerda e burger à direita. O cardápio combina três colunas no desktop e duas no celular.

| Papel | Cor |
|---|---|
| Ação principal / laranja-brasa | `#F26B21` |
| Fundo / preto quente | `#141210` |
| Superfícies / grafite quente | `#1C1916` |
| Superfície elevada / marrom profundo | `#24201C` |
| Detalhes / âmbar | `#F7AD55` |
| Texto principal | `#F7F1E9` |
| Texto secundário | `#ACA297` |

O tema claro usa fundo `#FAF6F0`, superfícies `#FFFDFA` e texto `#211B15`, preservando o laranja principal. A preferência fica salva no navegador. O alternador fica acima do botão de WhatsApp e ambos se ajustam à barra do carrinho no celular.

**Tipografia:** Oswald para títulos e Manrope para interface, descrições e preços. Fontes variáveis locais, com licenças OFL incluídas em `assets/`.

**Logo oficial:** o anexo citado no briefing não foi recebido nesta conversa. O espaço tracejado “LOGO OFICIAL” é uma indicação temporária, e o nome da empresa está em texto comum; não foi criada nenhuma logo substituta. Para aplicar o arquivo original, acesse Painel → Configurações → Logo oficial. O arquivo é preservado, sem recorte ou alteração, e salvo localmente para demonstração. Depois atualize a loja.

## Telas e fluxo

1. **Início:** cabeçalho fixo, status de funcionamento no horário de Brasília, entrega estimada, bairros atendidos, banner, cupom, destaques, cardápio, dúvidas e rodapé.
2. **Cardápio:** categorias roláveis, busca por nome ou ingrediente e fotografias individuais. Começa em Destaques; Todos mostra os dez produtos.
3. **Produto:** foto, descrição, quantidade de 1 a 20, até três adicionais nos produtos personalizáveis, remoção de ingredientes, observação de até 240 caracteres e preço atualizado.
4. **Carrinho:** lateral no desktop; acesso fixo no celular. Itens, quantidades, exclusão, cupom, bairro ou retirada, subtotal, desconto, taxa e total. Conteúdo persistente após atualizar.
5. **Checkout em quatro etapas:**
   - Seus dados e identificação simulada por senha ou código. O botão “Preencher com dados de demonstração” facilita a avaliação. A senha não é salva; o código demonstrativo é `123456`, sem envio de mensagens.
   - Delivery ou retirada, endereço e opção de agendamento. O agendamento é validado para o futuro, de terça a domingo entre 18h e 23h30, em Brasília.
   - Pix, dinheiro, débito ou crédito na entrega/retirada. Dinheiro permite informar o valor para troco.
   - Revisão com atalhos para editar cada etapa e confirmação explícita de que o pedido é simulado.
6. **Confirmação:** número, total, recebimento, pagamento e previsão simulada.
7. **Acompanhamento:** consulta por número e WhatsApp informado no pedido; atalho para o último pedido local; exemplo pronto; controle para simular todos os estados. Na retirada, as etapas finais usam “Pronto para retirada” e “Retirado”.

## Cardápio demonstrativo

Os preços adicionais ao briefing são propostas para avaliação, não informações confirmadas da operação.

| Produto | Preço |
|---|---:|
| Brasa Bacon | R$ 38,90 |
| Burger de Costela | R$ 42,90 |
| X-Bacon da Brasa | R$ 32,90 |
| Brasa Salad | R$ 31,90 |
| Batata Brasa | R$ 24,90 |
| Combo Brasa | R$ 54,90 |
| Combo em Boa Companhia | R$ 94,90 |
| Refrigerante de Cola | R$ 7,90 |
| Limonada da Casa | R$ 12,90 |
| Brownie da Brasa | R$ 19,90 |

Adicionais demonstrativos: bacon R$ 5,00; cheddar R$ 4,00; burger de 180g R$ 12,00. A foto do hero mostra a versão com burger extra.

**Entrega:** Centro R$ 6,90; Jardins R$ 8,90; Vila Nova R$ 5,90. Retirada grátis. Antes de selecionar um bairro, o carrinho identifica o valor como “Total parcial”.

**BRASA15:** 15% sobre produtos e adicionais, com arredondamento para centavos, sem desconto na taxa de entrega. Exemplo: Brasa Bacon (R$ 38,90), cupom (− R$ 5,84) e Centro (R$ 6,90) resultam em **R$ 39,96**.

## Painel de demonstração

**E-mail:** `admin@brasa.demo`  
**Senha:** `brasa2026`

O login é uma barreira visual de demonstração, feita no navegador, sem proteção real. As credenciais fictícias já aparecem preenchidas. O botão de sair retorna à entrada.

- **Visão geral:** indicadores calculados sobre os pedidos simulados, movimento por hora, produtos mais pedidos e pedidos recentes.
- **Pedidos:** Kanban com avanço por botões, busca, filtros, detalhes, observações, pagamento e atualização dos sete estados, incluindo cancelado e recusado.
- **Produtos:** lista, inclusão com imagem local, edição, ativação/pausa e exclusão com confirmação.
- **Categorias:** nome, posição e quantidade de produtos.
- **Adicionais:** grupos, seleção única ou múltipla, mínimo, máximo e opções com valores. Validação dos limites no formulário.
- **Cupons:** código, percentual, valor mínimo, limite e condições.
- **Áreas:** bairro, taxa, prazo e pedido mínimo.
- **Banners:** título, apoio, botão, destino e imagem local.
- **Configurações:** loja, logo, horário por dia, Brasília, pausa manual, agendamentos e alertas.
- **Administradores:** perfis fictícios com edição, pausa e exclusão; nenhuma conta real é criada.

As edições de catálogo, cupons, áreas e operação são simulações independentes no painel; a loja conserva o cardápio e as regras do briefing. Os pedidos e seus estados, a logo carregada e a preferência de tema são compartilhados entre loja e painel quando abertos na mesma origem. Essa delimitação também está indicada nas telas.

## Fotografias autorais

Onze fotografias originais foram produzidas com a ferramenta integrada de geração de imagens (built-in ImageGen): hero e dez produtos. Todas têm 1536 × 1024 pixels, fundo escuro, luz quente, sem textos, marcas adicionais ou marcas-d’água.

Arquivos finais: `assets/hero.png`, `assets/brasa-bacon.png`, `assets/costela.png`, `assets/x-bacon.png`, `assets/salad.png`, `assets/batata.png`, `assets/combo-individual.png`, `assets/combo-duplo.png`, `assets/cola.png`, `assets/limonada.png` e `assets/brownie.png`. O conjunto completo de prompts está em `assets/image-prompts.json`.

## Revisão realizada

- 18 verificações automatizadas de valores, taxas, cupom, adicionais, quantidades, restauração e persistência do carrinho, escape de conteúdo, presença dos assets e sintaxe JavaScript.
- Resposta HTTP da página inicial confirmada na prévia local.
- Layout responsivo definido para desktop, tablet e celular; navegação usa links, formulários e diálogos nativos, foco visível e respeito à preferência de movimento reduzido.
- Esta entrega não inclui um teste automatizado de interação em navegador ou uma certificação de acessibilidade. A aprovação visual e a navegação pelo usuário são a próxima etapa.

`VALIDAR.cjs` é somente um utilitário de revisão: não é carregado pela loja e não implica dependência de Node no site ou no servidor.
