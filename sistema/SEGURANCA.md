# Segurança e operação

## Prioridade crítica — controles implementados

| Risco | Implementação | Como verificar |
|---|---|---|
| Acesso indevido | Conta verificada para comprar; autorização no servidor por função; MFA obrigatório para equipe; cozinha recebe dados reduzidos | Tentar API sem sessão, com cliente e com cozinha; confirmar recusa e ausência de dados pessoais/financeiros |
| Roubo de senha/sessão | Argon2id, senha de 15–128 caracteres, cookies HttpOnly/SameSite, Secure em produção, rotação, expiração e revogação | Conferir cookies em HTTPS, logout, expiração, MFA e revogação após alteração de acesso |
| Força bruta | Limites persistidos no MySQL, códigos temporários de uso único com tentativas limitadas e prevenção de replay TOTP | Repetir tentativas, códigos vencidos e utilizados; conferir 429 e recusa |
| Fraude de preço/pedido | Centavos inteiros; recálculo de produtos, adicionais, cupom, bairro e total; confirmação transacional; idempotência; revisão de catálogo e pedido | Alterar valores e IDs no cliente; repetir confirmação; concorrer o mesmo cupom em dois processos; tentar transições inválidas |
| Acesso a endereço de terceiro | Consulta e alteração vinculadas ao cliente autenticado | Enviar ID de endereço de outra conta; esperar recusa |
| Injeção e requisição forjada | PDO com prepared statements nativos; validação de tipos/listas; escape na interface; token CSRF e validação de origem | Enviar JSON inválido, texto com HTML, IDs adulterados, requisições sem CSRF e com origem externa |
| Upload executável | Somente PNG/JPEG/WebP até 3 MiB; inspeção MIME, dimensões e decodificação; armazenamento privado; nomes aleatórios; resposta sem execução | Rejeitar PHP disfarçado, SVG e imagem truncada; conferir checksum da imagem válida |
| Segredos expostos | Chaves por ambiente; segredos MFA e fila de e-mail criptografados; configurações fora da raiz pública | Solicitar caminhos privados por HTTP; revisar pacote, imagem e permissões dos volumes |

A proteção contra senhas comuns contém uma lista pequena; não é uma base abrangente de senhas vazadas. O rate limiting de aplicação não substitui mitigação de DDoS na borda. O identificador público de rastreio deve ser tratado como um link privado, embora exponha somente andamento limitado.

## Prioridade alta — privacidade e integridade

Pagamentos são registrados como modalidade e confirmação de recebimento; não coletar PAN, CVV ou dados de cartão nas observações. Uma futura integração online exigirá projeto próprio, verificação de webhook, assinatura, idempotência e conciliação antes de ser ativada.

Auditoria registra ações, ator e referências, sem senhas, códigos ou payloads de e-mail. O log HTTP incluído omite query strings. Restrinja acesso aos logs e aplique retenção aprovada. Configure o proxy e a plataforma para também não registrarem cookies, Authorization, corpos, códigos ou parâmetros sensíveis. Verifique amostras após login, recuperação e checkout. Falhas do PHP não são exibidas ao público.

A página de privacidade e os pedidos de atendimento ao titular estão implementados. O controlador deve completar sua identificação, canal, bases legais, prazos de retenção e procedimento de resposta antes do lançamento. A análise jurídica e o cumprimento organizacional da LGPD não são automatizados. Para solicitação de exclusão já analisada e autorizada, o operador responsável pode executar `php bin/anonymize.php --approved-request ID`; a operação é irreversível e preserva registros financeiros mínimos. Teste antes em cópia isolada e registre exclusões para reaplicação em restaurações. Restrinja também backups, mensagens locais e acesso de funcionários.

## Prioridade alta — implantação e continuidade

- HTTPS: terminar TLS no EasyPanel, redirecionar HTTP, restringir portas e manter certificados válidos. Conferir cookie Secure, HSTS, CSP, nosniff e proteção contra framing na resposta pública. A CSP ainda permite estilos inline exigidos pelo layout; scripts são locais.
- Atualizações: inventariar PHP, MySQL, imagem base e PHPMailer; acompanhar avisos oficiais, testar patches em homologação e reconstruir a imagem. O PHPMailer incluído é 7.1.1, com licença no pacote. Não usar atualização automática sem recuperação disponível.
- Backup: executar e verificar diariamente, copiar para outro local e testar restauração periodicamente. Chave de backup deve ficar separada dos arquivos. A entrega foi testada com restauração isolada, mas o agendamento e a retenção externos dependem da operação.
- Monitoramento: alertar sobre indisponibilidade, erros 5xx, falhas de fila, disco cheio, falta de backup, certificado próximo do vencimento e aumento de tentativas negadas. O endpoint de saúde verifica o banco; ele não mede entrega de e-mail nem restauração. Alertas externos ainda precisam ser configurados.
- Incidentes: definir responsável e canal; conter acesso comprometido, revogar sessões, preservar evidências restritas, corrigir a causa e restaurar/testar quando necessário. Rotacionar credenciais afetadas; a troca de APP_KEY exige migração dos dados cifrados ou novo cadastro MFA. Avaliar com o responsável legal a necessidade de comunicação aos titulares e à ANPD segundo as regras vigentes. Fazer um exercício antes da operação.

## Critério de liberação

Não liberar pedidos reais sem domínio/TLS, SMTP validado, administrador com MFA configurado, logo e dados reais revisados, termos de privacidade aprovados, volumes persistentes, backup externo, monitoramento, build de homologação e teste de ponta a ponta no navegador. A implementação fornece controles de aplicação; não constitui garantia de ausência de vulnerabilidades.
