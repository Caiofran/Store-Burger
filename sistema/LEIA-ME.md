# Brasa Burger Co. — versão funcional

Site público e administração em PHP 8.4, MySQL 8.4 e JavaScript puro. O protótipo original permanece na pasta superior. A raiz pública desta versão é exclusivamente `public/`.

## Entrega

Cardápio, personalização, carrinho persistente, cupom, checkout com conta verificada, endereços, entrega/retirada, agendamento, pagamento no recebimento e acompanhamento. Administração com MFA, permissões, pedidos em Kanban, catálogo, imagens, adicionais, cupons, bairros, banners, horários, configurações e usuários. Valores e disponibilidade são conferidos no servidor na confirmação.

O código por e-mail está implementado. Código por SMS/WhatsApp não está integrado. O botão WhatsApp abre uma conversa; não envia mensagens automaticamente. Não há cobrança online nem armazenamento de cartão. A logo oficial precisa ser fornecida pelo estabelecimento e enviada nas configurações; os arquivos originais aceitos são preservados.

## Instalação no EasyPanel

1. Crie um MySQL privado, sem porta pública, e um serviço de aplicação com o contexto de build nesta pasta e o Dockerfile incluído. Direcione o domínio HTTPS à porta 80 interna. Bloqueie acesso direto à porta HTTP externa e habilite redirecionamento para HTTPS no proxy.
2. Configure as variáveis de `.env.example` no serviço. Use `APP_ENV=production`, `APP_URL` com o domínio HTTPS exato e gere `APP_KEY` com `php -r "echo bin2hex(random_bytes(32));"`. Não publique `.env` nem inclua credenciais na imagem. `TRUSTED_PROXIES` aceita somente os IPs exatos dos proxies controlados; não permita todos os clientes.
3. Crie o banco `brasa` com UTF-8. Execute `php bin/migrate.php` em uma tarefa de instalação com credenciais temporárias autorizadas a criar tabelas. Depois use na aplicação um usuário restrito a SELECT, INSERT, UPDATE e DELETE nesse banco. Não mantenha credenciais de migração no serviço web.
4. Monte volumes persistentes em `/var/www/brasa/storage/private` e `/var/www/brasa/storage/media`, pertencentes ao usuário `www-data`, com acesso restrito. Execute o worker com o mesmo usuário. Não exponha esses volumes pelo servidor web. Esta entrega usa uma instância web; múltiplas réplicas exigem revisão do armazenamento de sessões.
5. Crie um arquivo privado temporário com uma senha longa e exclusiva. Execute `php bin/create-admin.php seu-email arquivo-privado-com-senha`. Cadastre a chave do arquivo de matrícula gerado em um aplicativo autenticador TOTP. Remova com segurança os arquivos de senha e matrícula após configurar o acesso. Não existe administrador ou senha padrão.
6. Configure `MAIL_TRANSPORT=smtp`, SMTP_HOST, SMTP_PORT, SMTP_SECURITY (`tls`), SMTP_USER, SMTP_PASSWORD e MAIL_FROM. Habilite um serviço worker com a mesma imagem, configuração e volumes, usando `sh bin/queue.sh`. Configure SPF/DKIM/DMARC no provedor. Teste recebimento, expiração e recuperação de conta no endereço real. `spool` é exclusivo do desenvolvimento local e grava mensagens privadas, sem enviá-las.
7. Revise no painel produtos, taxas, horários, contato de privacidade e logo. Execute `php bin/readiness.php` e resolva todas as pendências. Configure os procedimentos de SEGURANCA.md antes de liberar pedidos reais.

O Dockerfile foi preparado, mas o build não foi executado neste ambiente por ausência de um daemon Docker. Valide a imagem e o fluxo completo no ambiente de homologação antes de publicar. Não configure a raiz do projeto inteiro como document root.

## Backups e recuperação

Gere BACKUP_KEY independentemente de APP_KEY e guarde uma cópia em cofre separado. Configure BACKUP_DIR privado, usuário dedicado de backup e MYSQLDUMP_PATH. A imagem usa cliente MariaDB e `MYSQL_ORACLE_CLIENT=0`; com cliente Oracle compatível use 1. O usuário de backup precisa de leitura e permissões de metadados necessárias ao dump; não use root em produção.

Agende `php bin/backup.php` diariamente e copie o arquivo criptografado para armazenamento externo. A cópia externa e o agendamento são responsabilidade da implantação, não são automáticos neste pacote. Monitore falhas e idade do último backup. Defina retenção, RPO e RTO com o responsável pelo negócio.

Para recuperar, crie uma pasta privada vazia e execute `php bin/verify-backup.php arquivo.backup pasta-vazia`. O comando autentica e extrai SQL e imagens sem sobrescrever banco. Importe o SQL primeiro em um banco isolado, restaure as imagens em volume isolado e confira pedidos, totais e imagens. A troca do banco de produção exige janela de manutenção e backup anterior. Preserve APP_KEY para ler segredos existentes; sua perda impede descriptografá-los. Reaplique exclusões de titulares ocorridas após o backup antes de reabrir o serviço.

## Validação realizada

PHP 8.4.25 e MySQL 8.4.9 locais: 9 verificações unitárias e 59 verificações de API, incluindo permissões, sessão, OTP, MFA, adulteração de preços, endereços, idempotência, concorrência de cupom e uploads. Sintaxe de todos os arquivos PHP e JavaScript validada. Backup criptografado extraído e restaurado em banco separado, com conferência de quantidades e totais.

Os testes estão em `tests/`; fixtures devem rodar somente em banco descartável local. Limites de tentativa são intencionais e afetam repetições imediatas. Credenciais de testes e dados locais não fazem parte do pacote. Não foi realizado teste interativo do navegador, envio SMTP real, build Docker ou implantação na VPS. Os testes não representam certificação nem auditoria independente.
