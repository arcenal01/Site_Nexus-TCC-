# Nexus

Site institucional do **Nexus Tecno**, evento de projetos de tecnologia e networking do curso Técnico em Desenvolvimento de Sistemas, com um **painel administrativo de edição de conteúdo** protegido por várias camadas de segurança.

Este repositório é a **edição de demonstração (portfólio)** do projeto. Ela existe para que o código, a arquitetura e as decisões de segurança possam ser avaliados publicamente, sem expor o ambiente real que roda em produção (ver [Edições](#edições-demonstração-e-produção)).

> **Stack:** PHP 8 (sem framework e sem dependências externas) · HTML/CSS/JavaScript puros · armazenamento em arquivos JSON · Apache (`.htaccess`).

---

## Painel de demonstração

Em todas as páginas públicas há um botão flutuante **"Ver painel · demo"** que abre o painel administrativo desta edição.

| Campo       | Valor                       |
|-------------|-----------------------------|
| Usuário     | `demo`                      |
| Senha       | `Demo@Nexus-Portfolio-2026` |
| Código 2FA  | `0000`                      |

Essas credenciais são **públicas e exclusivas da demonstração**: não dão acesso a nenhum dado real e não funcionam em produção. As mesmas informações aparecem na própria tela de login.

O que acontece ao usar o painel de demonstração:

- Login, verificação em duas etapas, limite de tentativas, sessão, CSRF e cabeçalhos de segurança funcionam **exatamente como em produção**.
- Edições de texto, de projetos (TCCs) e de data do evento passam por **toda a validação e sanitização**, mas **não são gravadas**. O site público não muda.
- Envio de imagem é validado (tipo real, formato e dimensões), porém o arquivo não é armazenado.
- Troca de senha e redefinição do 2FA ficam desativadas, pois a conta é pública.
- O log de auditoria registra as ações da demonstração com o **IP anonimizado**.

---

## Segurança em resumo

Resumo dos controles implementados. O detalhamento (onde está o código, como testar) está em [`docs/SEGURANCA.md`](docs/SEGURANCA.md).

| Área | Controle |
|------|----------|
| Autenticação | Senhas com Argon2id (ou bcrypt custo 12), rehash automático, política de senha com checagem de vazamentos (Pwned Passwords, k-anonymity) |
| Segundo fator | TOTP (RFC 6238) implementado em PHP puro, QR code gerado no servidor, códigos de recuperação de uso único, bloqueio de reutilização de código |
| Força bruta | Limite de tentativas por IP e por conta, com contagem antes da verificação e bloqueio progressivo |
| Enumeração de usuários | A senha é sempre conferida, inclusive para usuários inexistentes, sem diferença mensurável de tempo ou de mensagem |
| Sessão | Cookie `HttpOnly`, `Secure`, `SameSite=Strict`, modo estrito, vínculo a IP e navegador, expiração por inatividade e tempo máximo absoluto, invalidação ao trocar a senha |
| CSRF | Token por sessão em toda ação que altera estado |
| XSS | Escape de toda saída, CSP com nonce (sem `unsafe-inline` em scripts), `X-Frame-Options`, `nosniff` |
| Upload | Tipo real verificado, limites de tamanho e dimensão, nome aleatório, regravação via GD (remove EXIF e payloads embutidos), pasta com lista de permissão |
| Dados | Pasta de dados bloqueada na web (ou fora da raiz pública), gravação atômica com trava de arquivo, controle de concorrência por versão, backups automáticos |
| Auditoria | Log em JSON com retenção configurável, sem senhas nem códigos |
| Servidor | Redirecionamento HTTPS, HSTS, ocultação de versões, páginas de erro neutras, erros do PHP fora da tela |
| Privacidade | Política de Privacidade (LGPD), sem cookies nas páginas públicas, sem formulários nem coleta de dados de visitantes |

---

## Estrutura do projeto

```
.
├── index.php, tccs.php, saiba-mais.php, privacidade.php   Páginas públicas
├── demo-painel.php                  Painel administrativo (login, 2FA e dashboard)
├── demo-api.php                     API JSON do painel (salvar, restaurar, upload, conta)
├── script.js, style.css             Comportamento e estilo do site público
├── assets/                          Fontes locais, imagens e CSS do painel
├── imagens-tcc/                     Logos e capturas de tela dos projetos
├── inc/
│   ├── config.php                   Constantes, sessão, cabeçalhos de segurança, auditoria
│   ├── auth.php                     Login, 2FA, política de senha, sessões
│   ├── totp.php, qr.php             TOTP (RFC 6238) e gerador de QR code em PHP puro
│   ├── dados.php, padroes.php       Leitura do conteúdo e valores padrão
│   └── demo.php                     Recursos exclusivos da edição de demonstração
├── ferramentas/                     Scripts de linha de comando (bloqueados na web)
├── dados/                           Dados de execução (bloqueados na web; fora do Git)
├── uploads/                         Imagens enviadas pelo painel (somente imagens)
└── docs/                            Documentação técnica
```

---

## Por onde começar a ler o código

O código é comentado em português: cada arquivo abre com um cabeçalho que explica o seu papel, e as funções trazem uma descrição do que fazem, dos parâmetros e do retorno.

| Se você quer entender... | Comece por |
|--------------------------|------------|
| Como o site monta as páginas e de onde vêm os textos | `inc/dados.php` (modelo de dados) e `inc/padroes.php` (conteúdo original) |
| Login, 2FA, sessão, limite de tentativas e política de senha | `inc/auth.php`, com apoio de `inc/totp.php` e `inc/qr.php` |
| Configuração, cabeçalhos de segurança, CSP e auditoria | `inc/config.php` |
| O que o painel envia e como o servidor valida | `demo-api.php` (servidor) e o bloco `<script>` de `demo-painel.php` (navegador) |
| Animações, galeria, cronômetro e popups do site | `script.js` (uma função autoexecutável por recurso) |
| Organização do CSS | cabeçalho de `style.css` (índice das seções) |

Os identificadores `NX-nn` que aparecem nos comentários (por exemplo `NX-11`) correspondem aos controles de segurança descritos em [`docs/SEGURANCA.md`](docs/SEGURANCA.md), que indica onde cada um está implementado.

---

## Como executar localmente

Requisitos: **PHP 8.0 ou superior** (testado na 8.3) com as extensões `mbstring` e `gd`.

```bash
git clone <url-do-repositório>
cd nexus
php -S localhost:8000
```

Abra `http://localhost:8000`. A edição de demonstração é o padrão e já funciona sem configuração.

> O servidor embutido do PHP ignora o `.htaccess`. Para validar as regras de servidor (bloqueio de pastas, HTTPS, páginas de erro), use Apache com `mod_rewrite` habilitado (por exemplo, XAMPP).

---

## Edições: demonstração e produção

A mesma base de código atende duas edições, escolhidas pela variável de ambiente `NEXUS_EDICAO`.

| Aspecto | `demo` (este repositório) | `producao` |
|---------|---------------------------|------------|
| Contas | Uma conta pública (`dados/senha.demo.php`) | Contas reais em `dados/senha.php`, criadas só no servidor por linha de comando |
| 2FA | Código fixo `0000` libera a etapa | Somente aplicativo autenticador e códigos de recuperação |
| Gravações | Validadas e descartadas | Efetivadas, com backup e auditoria |
| Troca de senha e 2FA | Desativadas | Disponíveis, com limite de tentativas |
| Log de auditoria | IP anonimizado | IP completo (retenção configurável) |
| Botão "Ver painel" | Exibido | Não existe |

Qualquer valor diferente de `demo` em `NEXUS_EDICAO` é tratado como `producao` (falha de forma segura).

---

## Privacidade deste repositório

Para a publicação do código foram omitidos dados pessoais:

- as fotos do evento (`assets/images/`) são as originais, publicadas com a autorização do autor;
- nomes de estudantes e de organizadores foram trocados por rótulos genéricos;
- o e-mail e o telefone de contato são valores de exemplo;
- credenciais, logs, backups, sessões e uploads reais nunca fazem parte do repositório (ver `.gitignore`).

---

## Documentação

- [`docs/SEGURANCA.md`](docs/SEGURANCA.md): modelo de ameaças e catálogo de controles.
- [`docs/IMPLANTACAO.md`](docs/IMPLANTACAO.md): requisitos, publicação em produção, rotinas de manutenção e ferramentas de linha de comando.
- [`SECURITY.md`](SECURITY.md): como reportar vulnerabilidades.

## Licença

A definir pelo autor antes da publicação (por exemplo MIT para o código). As imagens e os textos do evento não estão cobertos por essa licença.
