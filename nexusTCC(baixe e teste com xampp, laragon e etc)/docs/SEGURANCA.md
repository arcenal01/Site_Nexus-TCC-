# Segurança

Este documento descreve o modelo de ameaças do projeto e os controles implementados. Os identificadores `NX-nn` são os mesmos usados nos comentários do código, o que permite localizar cada controle na implementação.

## 1. Modelo de ameaças

O ativo principal é a **integridade do conteúdo publicado**: quem controla o painel altera o que todos os visitantes veem. Por isso o painel é a superfície mais crítica.

| Ameaça | Exemplo | Controles principais |
|--------|---------|----------------------|
| Acesso não autorizado ao painel | Adivinhar senha, reaproveitar senha vazada | NX-02, NX-07, NX-10, NX-11, NX-12 |
| Sequestro de sessão | Roubo de cookie, fixação de sessão | NX-04, NX-09 |
| Execução de código no navegador | XSS armazenado ou refletido | NX-13, escape de saída |
| Ações forjadas | CSRF, clickjacking | Token CSRF, NX-13 |
| Envio de arquivos maliciosos | Webshell com extensão dupla, polyglot | NX-05, NX-06, NX-14 |
| Exposição de dados internos | Baixar `senha.php` ou logs | NX-01, NX-03 |
| Vazamento de informações do servidor | Versões, caminhos, erros | NX-15, NX-16 |
| Perda ou corrupção de dados | Gravação simultânea, falha no meio da escrita | NX-08 |
| Privacidade de terceiros | Dados pessoais de estudantes e visitantes | NX-17, NX-18 |

## 2. Camadas de defesa

1. **Servidor (`.htaccess`)**: HTTPS, bloqueio de `dados/`, `inc/` e `ferramentas/`, ocultação de versões, páginas de erro neutras.
2. **Autenticação (`inc/auth.php`)**: senha forte, segundo fator, limite de tentativas, sessão endurecida.
3. **Aplicação (`demo-api.php`)**: método, sessão e CSRF verificados antes de qualquer ação; toda entrada é validada por lista de permissão.
4. **Navegador (`nexus_cabecalhos_seguranca`)**: CSP com nonce, proibição de quadros, `nosniff`, política de referenciador.
5. **Dados**: gravação atômica, trava de arquivo, controle de versão, backups e auditoria.

## 3. Catálogo de controles

### NX-01: Credenciais fora do código e do Git
- Nenhuma senha ou hash está no código-fonte. As contas ficam em `dados/senha.php`, criado só no servidor por `ferramentas/criar-conta.php`.
- `dados/senha.php` está no `.gitignore` e a pasta `dados/` é bloqueada na web.

### NX-02: Limite de tentativas de login
- Contadores por IP (5 falhas) e por conta (10 falhas), com bloqueio que dobra a cada ciclo.
- A tentativa é contada **antes** de conferir a senha, sob trava de arquivo, o que impede corridas.
- O IP vem de `REMOTE_ADDR`. O cabeçalho `X-Forwarded-For` não é lido de propósito; atrás de proxy, o servidor deve entregar o IP real.

### NX-03: Dados fora da raiz pública
- Se existir `nexus-privado/dados` ao lado do site, é ele que passa a ser usado (`ferramentas/mover-dados.php`).
- Em qualquer caso, `.htaccess` responde 404 para `/dados`, `/inc` e `/ferramentas`.

### NX-04: HTTPS e cookies seguros
- Redirecionamento para HTTPS (exceto `localhost`) e HSTS de um ano.
- Cookie da sessão sempre `Secure`, `HttpOnly` e `SameSite=Strict`.
- `X-Forwarded-Proto` só é aceito de proxies listados em `NEXUS_PROXIES`.

### NX-05: Pasta de uploads com lista de permissão
- `uploads/.htaccess` nega tudo e libera apenas extensões de imagem; nega extensões executáveis no meio do nome (`x.php.jpg`).
- Envia `nosniff` e CSP nas imagens.

### NX-06: Upload de imagens
- Tipo verificado pelo conteúdo (`getimagesize`), não pelo nome.
- Limites de 4 MB, 6000 px por lado e 24 milhões de pixels, conferidos **antes** de abrir a imagem (proteção contra bomba de descompressão).
- Nome aleatório; o nome enviado pelo navegador nunca é usado.
- A imagem é regravada via GD, removendo EXIF (inclusive GPS) e dados embutidos. Se a regravação falhar, o arquivo é apagado: a falha é sempre fechada.

### NX-07: Verificação em duas etapas (TOTP)
- TOTP conforme a RFC 6238, implementado em PHP puro (`inc/totp.php`). O QR code é gerado no servidor (`inc/qr.php`), sem serviço externo.
- Códigos de recuperação de uso único, armazenados como hash.
- Um código TOTP não pode ser reutilizado; tolerância de ±30 s de relógio.
- Erros de código contam no mesmo limite do login. Após 5 erros, é preciso refazer usuário e senha.
- Regerar códigos e trocar de aparelho exigem senha atual e um código válido.

### NX-08: Backup, auditoria e concorrência
- Backup automático antes de cada gravação (no máximo 1 por minuto, 60 mantidos).
- Log de auditoria em JSON (quando, quem, IP, ação). Nunca registra senhas nem códigos. Arquivado por tamanho e removido após 180 dias.
- Controle otimista de concorrência: o painel envia a versão do conteúdo que carregou. Se outra pessoa salvou antes, a gravação é recusada (409) em vez de sobrescrever.
- "Restaurar original" exige digitar `RESTAURAR`, no navegador e no servidor.

### NX-09: Sessões
- Modo estrito e somente por cookie; o PHP rejeita IDs que não criou.
- Sessão vinculada ao IP e ao navegador de origem.
- Expira por inatividade (120 min) e por tempo absoluto (8 h).
- Trocar a senha ou o aparelho do 2FA invalida todas as outras sessões da conta.
- Sessões armazenadas em pasta privada (`0700`), com faxina automática.

### NX-10: Login sem enumeração de usuários
- A senha é sempre conferida, mesmo para usuário inexistente (contra um hash de mesmo custo).
- Mensagem de erro e atraso são idênticos nos dois casos.

### NX-11: Política de senha e custo do hash
- Mínimo de 12 caracteres e máximo de 72 bytes (o bcrypt truncaria em silêncio).
- Recusa senhas comuns, só numéricas, repetições, variações óbvias de palavras e senhas que contenham o usuário.
- Consulta ao Pwned Passwords em modelo *k-anonymity*: só os 5 primeiros caracteres do SHA-1 saem do servidor.
- Hash Argon2id (ou bcrypt custo 12), com *rehash* automático no login se o custo ficar defasado.

### NX-12: Limite na "senha atual"
- A tela de troca de senha tem contadores próprios (por IP e por conta). No 5º erro a sessão é encerrada, de modo que uma sessão roubada não consegue adivinhar a senha.

### NX-13: CSP com nonce
- `script-src 'self'` mais um nonce aleatório por requisição. Um `<script>` injetado não teria o nonce e seria bloqueado.
- Painel e API: `X-Frame-Options: DENY` e `frame-ancestors 'none'`. `object-src 'none'` em todas as páginas.
- Limitação conhecida: `style-src` ainda aceita `unsafe-inline` por causa de atributos `style="..."`. Isso permite apenas CSS, não execução de código.

### NX-14: Validação de caminhos de imagem
- A API só aceita `imagens-tcc/NOME.ext` ou `uploads/tcc/NOME.ext` (nome com letras, números, `_` e `-`), e o arquivo precisa existir. Bloqueia `javascript:`, `data:`, URLs externas e travessia de diretórios.

### NX-15: Erros e informações do servidor
- `display_errors` desligado; erros vão para `dados/php-erros.log`.
- Exceções não tratadas viram uma mensagem genérica; o detalhe técnico fica no log.
- Respostas da API não citam pastas nem permissões.
- `ServerSignature Off`, `X-Powered-By` removido, páginas 403/404/500 neutras.

### NX-16: Sem instalador
- O painel nunca redireciona para instalador e nunca permite criar a conta pela web. Sem contas cadastradas, responde 503.
- Nomes comuns de instaladores (`install`, `setup`...) respondem 404 no `.htaccess`.
- Formatos antigos de credenciais foram removidos do código.

### NX-17: Privacidade e LGPD
- Página de Política de Privacidade ligada no rodapé.
- Sem cookies, formulários ou newsletter nas páginas públicas.
- Retenção do log de auditoria declarada (180 dias).

### NX-18: Higiene das imagens
- Metadados (EXIF/GPS) removidos, duplicatas eliminadas e peso reduzido.

## 4. Edição de demonstração

A edição `demo` foi desenhada para mostrar os controles acima sem criar risco:

- **Isolamento de credenciais:** só existe a conta pública `demo`. O código fixo de 2FA só tem efeito depois de usuário e senha corretos dessa conta, então não abre nenhuma outra.
- **Gravações descartadas:** `demo-api.php` executa a mesma validação de produção e responde sucesso sem gravar. Troca de senha e 2FA são recusadas com 403.
- **Privacidade:** IPs do log de auditoria são anonimizados.
- **Falha segura:** qualquer valor de `NEXUS_EDICAO` diferente de `demo` ativa a edição de produção.

## 5. Limitações conhecidas

- `style-src 'unsafe-inline'` (ver NX-13).
- Armazenamento em arquivos: adequado ao volume do projeto; para múltiplos servidores seria necessário um banco de dados.
- Limitação de requisições em volume (DoS) deve ser feita no servidor ou na CDN.
- `expose_php = Off` só pode ser definido no `php.ini` ou no painel da hospedagem.

## 6. Roteiro de testes manuais

1. Entrar só com usuário e senha **não** abre o painel.
2. Um código 2FA errado é recusado; o mesmo código usado duas vezes é recusado.
3. Cinco senhas erradas bloqueiam temporariamente o acesso.
4. Em duas abas, salvar na A e depois na B: a B é recusada e recarrega.
5. Um arquivo `.php` renomeado para `.jpg` é recusado no upload.
6. `/dados/senha.php` e `/inc/config.php` respondem 403 ou 404.
7. No console do navegador, injetar um `<script>` na página é bloqueado pela CSP.
