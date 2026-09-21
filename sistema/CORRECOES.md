# Correções complementares — 09/09/2026

- Recuperação de senha agora invalida todos os códigos pendentes da conta, além das sessões anteriores, e registra o evento na auditoria.
- Verificação de código de login ou recuperação exige conta previamente confirmada. Apenas o fluxo de confirmação pode verificar um cadastro pendente.
- Atualização do hash após login não reaplica regras de criação de senha a uma credencial já verificada, evitando bloqueio indevido em migrações.
- Configuração SMTP exige TLS ou SSL, porta válida, usuário e senha; configuração sem criptografia falha antes de enfileirar códigos.
- Verificação de implantação detecta reutilização da chave da aplicação no backup. Comparação normaliza a representação hexadecimal para impedir contorno por letras maiúsculas.

Validação: nove testes adicionais em `tests/auth-regression.php` aprovados em ambiente local, sem enviar e-mails externos. Os testes criam e removem somente sua própria conta descartável. A configuração SMTP é validada, mas conexão e entrega reais continuam pendentes. As pendências de VPS, domínio, privacidade, cópia externa e monitoramento estão em LEIA-ME.md e SEGURANCA.md.
