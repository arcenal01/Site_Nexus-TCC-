# Implantação e operação

## Requisitos

| Item | Requisito |
|------|-----------|
| PHP | 8.0 ou superior (testado na 8.3) |
| Extensões | `mbstring` e `gd` (obrigatórias); `argon2` recomendada |
| Servidor | Apache com `mod_rewrite` e `AllowOverride All`, ou Nginx com as regras abaixo |
| HTTPS | Certificado válido no domínio (obrigatório em produção) |

Nenhuma dependência externa (Composer, npm) é necessária.

## Edições

A variável de ambiente `NEXUS_EDICAO` define o comportamento (`demo` ou `producao`). Ver a tabela no [README](../README.md#edições-demonstração-e-produção).

Para publicar a **demonstração**, nenhuma configuração é necessária.

Para publicar em **produção**, defina `NEXUS_EDICAO=producao` no ambiente do servidor (por exemplo com `SetEnv NEXUS_EDICAO producao` no Apache ou `fastcgi_param` no Nginx).

> **Importante:** o repositório público e o site real devem ser tratados como ambientes separados. Nunca versione `dados/senha.php`, o nome real do painel ou qualquer credencial de produção.

## Publicação em produção

1. **Envie os arquivos** para o servidor, sem as pastas `.git` nem arquivos locais.
2. **Defina `NEXUS_EDICAO=producao`.**
3. **Renomeie o painel e a API** para nomes não óbvios (por exemplo, `demo-painel.php` e `demo-api.php` para algo próprio) e atualize a constante `API` em `demo-painel.php`. O nome só precisa ser conhecido por quem administra o site.
4. **Crie a primeira conta**, apenas no servidor:
   ```bash
   php ferramentas/criar-conta.php
   ```
   A senha deve ter pelo menos 12 caracteres e não pode constar em vazamentos conhecidos.
5. **Mova os dados para fora da raiz pública** (recomendado):
   ```bash
   php ferramentas/mover-dados.php
   ```
   Isso cria `nexus-privado/dados` ao lado da pasta pública. O PHP precisa de permissão de escrita nela.
6. **Ative o HTTPS** e confirme o redirecionamento de `http://` para `https://`.
7. **No primeiro login**, configure o aplicativo autenticador e guarde os códigos de recuperação.
8. **Se houver CDN ou proxy** (por exemplo Cloudflare), informe os IPs confiáveis em `NEXUS_PROXIES` e use o modo SSL "Full (strict)".

### Regras equivalentes para Nginx

```nginx
location ~ ^/(dados|inc|ferramentas)/ { deny all; return 404; }
location ^~ /uploads/ {
    location ~ \.(php|phtml|phar)$ { return 403; }
}
```

### Camada extra opcional

É possível exigir autenticação HTTP ou restrição por IP antes de o PHP executar, nas duas URLs do painel (a página e a API). Exemplo para o `.htaccess`:

```apache
<FilesMatch "^(NOME-DO-PAINEL|NOME-DA-API)\.php$">
    AuthType Basic
    AuthName "Área restrita"
    AuthUserFile /caminho/FORA/da/raiz/.htpasswd
    Require valid-user
</FilesMatch>
```

## Verificação pós-publicação

| Teste | Resultado esperado |
|-------|--------------------|
| `/dados/senha.php`, `/dados/conteudo.json`, `/inc/config.php` | 403 ou 404 |
| `/ferramentas/criar-conta.php` | 404 |
| `/instalar.php`, `/install.php`, `/setup.php` | 404 |
| `http://seudominio/` | Redireciona para `https://` |
| URL inexistente | Página simples, sem versão do Apache/PHP |
| Cabeçalhos da resposta | Sem `X-Powered-By`; com CSP, HSTS e `nosniff` |
| Login só com usuário e senha | Não abre o painel (exige o código 2FA) |
| Mesmo código 2FA usado duas vezes | Segunda tentativa recusada |
| Errar a senha 5 vezes | Bloqueio temporário |

## Ferramentas de linha de comando

Todas ficam em `ferramentas/`, só executam via CLI (na web respondem 404) e a pasta também é bloqueada pelo servidor.

| Script | Função |
|--------|--------|
| `criar-conta.php` | Cria ou atualiza uma conta do painel |
| `redefinir-2fa.php [USUARIO]` | Reinicia o 2FA de uma conta (sem argumento, lista as contas) |
| `calibrar-hash.php` | Mede o custo do hash no servidor (meta: 200 a 300 ms) |
| `mover-dados.php` | Move `dados/` para uma pasta privada fora da raiz pública |
| `restaurar-backup.php [ARQUIVO]` | Lista ou restaura backups do conteúdo |
| `limpar-uploads.php [--apagar]` | Lista ou remove imagens órfãs com mais de 30 dias |

## Rotinas de manutenção

- Revisar e arquivar `dados/php-erros.log` periodicamente.
- Agendar `limpar-uploads.php --apagar` (por exemplo, mensalmente).
- Conferir `dados/auditoria.log`: tentativas bloqueadas, falhas de 2FA e gravações.
- Ao migrar para um PHP sem suporte a Argon2id, defina `NEXUS_HASH_PREFERIR_ARGON2ID` como `false` **antes** de criar as contas.
