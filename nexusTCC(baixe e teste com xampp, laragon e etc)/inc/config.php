<?php
/*
 * CONFIGURAÇÃO GERAL DO SITE NEXUS (versão PHP)
 * ------------------------------------------------------------------
 * Este arquivo define caminhos, constantes e funções de apoio usadas
 * por todas as páginas. Normalmente não precisa ser alterado.
 */

declare(strict_types=1);

// ---------- Caminhos ----------
define('NEXUS_RAIZ',      dirname(__DIR__));

// ---------- Edição da aplicação ----------
// "demo"     : edição pública de demonstração (portfólio). O painel é aberto a quem quiser
//              conhecê-lo, usa uma conta de demonstração isolada e NUNCA grava nada de verdade.
// "producao" : edição real. Contas e dados ficam apenas no servidor (dados/senha.php).
// Defina pela variável de ambiente NEXUS_EDICAO. Qualquer valor desconhecido é tratado como
// "producao" (falha de forma segura). Neste repositório o padrão é "demo".
$nexusEdicao = strtolower(trim((string)(getenv('NEXUS_EDICAO') ?: 'demo')));
define('NEXUS_EDICAO', $nexusEdicao === 'demo' ? 'demo' : 'producao');
unset($nexusEdicao);

// Pasta PRIVADA, fora da raiz pública (recomendado): se existir a pasta
// "nexus-privado/dados" ao lado da pasta do site (ou a indicada na variável
// de ambiente NEXUS_PRIVADO), senha, conteúdo e tentativas ficam lá e não
// dependem de .htaccess. Se não existir, usa "dados/" dentro do site (XAMPP/local).
//   /home/usuario/nexus-privado/dados/   <- aqui
//   /home/usuario/public_html/           <- site
// Para mudar para a pasta privada: php ferramentas/mover-dados.php
$nexusPrivado = (string)(getenv('NEXUS_PRIVADO') ?: dirname(NEXUS_RAIZ) . '/nexus-privado');
define('NEXUS_PRIVADO_ATIVO', @is_dir($nexusPrivado . '/dados'));
define('NEXUS_DIR_DADOS', NEXUS_PRIVADO_ATIVO ? $nexusPrivado . '/dados' : NEXUS_RAIZ . '/dados');
unset($nexusPrivado);
define('NEXUS_ARQ_DADOS', NEXUS_DIR_DADOS . '/conteudo.json');
// Credenciais: em produção, dados/senha.php (criado só no servidor, nunca versionado). Na
// demonstração, um arquivo versionado com UMA conta pública e sem nenhum privilégio real.
define('NEXUS_ARQ_SENHA', NEXUS_EDICAO === 'demo'
    ? NEXUS_RAIZ . '/dados/senha.demo.php'
    : NEXUS_DIR_DADOS . '/senha.php');
define('NEXUS_ARQ_LOGIN', NEXUS_DIR_DADOS . '/tentativas.json');
define('NEXUS_ARQ_AUDITORIA', NEXUS_DIR_DADOS . '/auditoria.log');   // quem fez o quê (NX-08)
define('NEXUS_DIR_BACKUPS', NEXUS_DIR_DADOS . '/backups');          // cópias do conteudo.json antes de cada gravação
define('NEXUS_ARQ_TOTP_USO', NEXUS_DIR_DADOS . '/totp-uso.json'); // último código 2FA usado por conta (impede reaproveitar)
define('NEXUS_DIR_UPLOAD', NEXUS_RAIZ . '/uploads/tcc');
define('NEXUS_URL_UPLOAD', 'uploads/tcc');

// ---------- Erros do PHP (NX-15) ----------
// Erros e avisos NUNCA aparecem na página (revelariam caminhos e trechos de código):
// vão para um log privado, dados/php-erros.log (no mesmo lugar que o restante dos dados e
// bloqueado ao público, pois .log é negado no .htaccess). Se a pasta não for gravável, o
// PHP usa o log padrão do servidor. Revise (e apague o antigo) de vez em quando.
// Observação: expose_php (versão do PHP no cabeçalho) só pode ser desligado no php.ini.
error_reporting(E_ALL);
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('html_errors', '0');
@ini_set('log_errors', '1');
if (@is_dir(NEXUS_DIR_DADOS) && @is_writable(NEXUS_DIR_DADOS)) {
    @ini_set('error_log', NEXUS_DIR_DADOS . '/php-erros.log');
}

/** Registra um detalhe técnico no log de erros (para o responsável), sem mostrá-lo ao visitante. */
function nexus_log_erro(string $detalhe): void
{
    error_log('[nexus] ' . str_replace(["\r", "\n"], ' ', $detalhe));
}

// Exceção não tratada: o detalhe vai para o log; o visitante recebe só uma mensagem genérica.
set_exception_handler(static function (Throwable $e): void {
    nexus_log_erro(get_class($e) . ': ' . $e->getMessage() . ' em ' . basename($e->getFile()) . ':' . $e->getLine());
    $json = false;
    foreach (headers_list() as $cab) {
        if (stripos($cab, 'Content-Type: application/json') === 0) {
            $json = true;
        }
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($json) {
        echo json_encode(['ok' => false, 'mensagem' => 'Não foi possível concluir. Avise o responsável pelo site.'], JSON_UNESCAPED_UNICODE);
    } else {
        echo 'Não foi possível concluir a operação. Tente novamente mais tarde.';
    }
});

// ---------- Limites de segurança do login ----------
define('NEXUS_MAX_TENTATIVAS', 5);          // tentativas erradas antes do bloqueio
define('NEXUS_BLOQUEIO_SEGUNDOS', 600);     // 10 minutos de bloqueio
define('NEXUS_MAX_TENTATIVAS_CONTA', 10);   // falhas por NOME DE USUÁRIO (vale para qualquer IP)
define('NEXUS_BLOQUEIO_CONTA_SEGUNDOS', 900); // 15 minutos de bloqueio por conta
define('NEXUS_MAX_TENTATIVAS_TROCA', 5);       // senhas atuais erradas seguidas na tela "Trocar senha" (NX-12): por IP e por conta
define('NEXUS_BLOQUEIO_TROCA_SEGUNDOS', 900); // 15 minutos de bloqueio da troca de senha (dobra a cada ciclo)
define('NEXUS_SESSAO_MINUTOS', 120);        // sessão do painel expira em 2h de inatividade
define('NEXUS_SESSAO_MAX_HORAS', 8);        // e, mesmo ativa, no máximo 8h depois do login (NX-09)
define('NEXUS_DIR_SESSOES', NEXUS_DIR_DADOS . '/sessoes'); // pasta PRIVADA das sessões (não a /tmp compartilhada)

// ---------- Segundo fator (2FA) do painel ----------
define('NEXUS_2FA_JANELA_SEGUNDOS', 300);   // tempo para digitar o código depois de acertar a senha
define('NEXUS_2FA_MAX_ERROS', 5);           // códigos errados seguidos antes de exigir novo login
define('NEXUS_2FA_EMISSOR', 'Nexus');       // nome que aparece no aplicativo autenticador

// ---------- Edição de demonstração ----------
// Ativa-se somente com NEXUS_EDICAO=demo (ver acima). Nessa edição:
//   - o código fixo NEXUS_DEMO_CODIGO libera a etapa de 2FA (a tela continua a real);
//   - gravações (conteúdo, upload, senha, 2FA) são validadas, mas não persistem;
//   - o código fixo só abre a conta de demonstração, que não tem privilégio algum.
define('NEXUS_MODO_DEMO', NEXUS_EDICAO === 'demo');
define('NEXUS_DEMO_CODIGO', '0000');

// ---------- Backup e auditoria do conteúdo (NX-08) ----------
define('NEXUS_BACKUPS_MANTER', 60);          // quantos backups do conteúdo guardar (os mais recentes)
define('NEXUS_BACKUP_INTERVALO', 60);        // no máximo 1 backup por minuto (o "Restaurar" sempre faz o seu)
define('NEXUS_AUDITORIA_DIAS', 180);         // retenção do log de auditoria (informe na política de privacidade)
define('NEXUS_AUDITORIA_MAX_BYTES', 524288); // acima disso o log é arquivado e um novo começa

// ---------- Senhas (NX-11) ----------
define('NEXUS_SENHA_MIN', 12);               // tamanho mínimo (em caracteres) de uma senha nova
define('NEXUS_SENHA_MAX_BYTES', 72);         // o bcrypt ignora tudo depois de 72 bytes; recusamos senhas maiores em vez de cortar em silêncio
define('NEXUS_BCRYPT_CUSTO', 12);            // custo do bcrypt (só vale se o Argon2id não for usado); ajuste com: php ferramentas/calibrar-hash.php
define('NEXUS_HASH_PREFERIR_ARGON2ID', true); // usa Argon2id quando o PHP tiver suporte; false = sempre bcrypt (mais portátil entre servidores)
define('NEXUS_SENHA_CONSULTAR_VAZADAS', true); // ao criar/trocar senha, confere no Have I Been Pwned (só envia 5 caracteres do hash SHA-1)

// ---------- Limites de upload ----------
define('NEXUS_UPLOAD_MAX_BYTES', 4 * 1024 * 1024); // 4 MB por imagem

/**
 * Escapa texto para saída em HTML. Use SEMPRE ao imprimir conteúdo
 * que veio do painel.
 */
function h($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formata uma quantidade de segundos de forma legível (ex.: "45 s",
 * "3 min", "1h20min"). Usado nas mensagens de bloqueio por tentativas.
 */
function nexus_formatar_tempo(int $segundos): string
{
    if ($segundos < 60) {
        return $segundos . ' s';
    }
    $min = (int)ceil($segundos / 60);
    if ($min < 60) {
        return $min . ' min';
    }
    $h = intdiv($min, 60);
    $resto = $min % 60;
    return $resto > 0 ? ($h . 'h' . $resto . 'min') : ($h . 'h');
}

// IPs de proxies/CDN em que se pode confiar no cabeçalho X-Forwarded-Proto.
// Ajuste para o IP do seu proxy/faixas da CDN (ou use a variável de ambiente
// NEXUS_PROXIES, separada por vírgulas). Qualquer outro IP é ignorado, porque
// o visitante pode forjar esse cabeçalho.
define('NEXUS_PROXIES_CONFIAVEIS', array_filter(array_map(
    'trim',
    explode(',', (string)(getenv('NEXUS_PROXIES') ?: '127.0.0.1,::1'))
)));

/**
 * A requisição chegou por HTTPS? Direto (HTTPS=on) ou atrás de um proxy
 * CONFIÁVEL que informou X-Forwarded-Proto: https.
 */
function nexus_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', NEXUS_PROXIES_CONFIAVEIS, true)
        && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/**
 * Ambiente de desenvolvimento (XAMPP em http://localhost)? Só nesse caso o
 * cookie "Secure" e o HSTS são dispensados. Exige ao mesmo tempo: host
 * localhost/127.0.0.1, conexão vinda do próprio computador e nenhum
 * cabeçalho de proxy (que indicaria um site público atrás de proxy).
 */
function nexus_local(): bool
{
    $host = strtolower((string)preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
        && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
        && !isset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
}

/**
 * Inicia a sessão do painel com cookies endurecidos.
 */
function nexus_iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('NEXUSPAINEL');

    // NX-09: o PHP só aceita IDs de sessão que ele mesmo criou, só por cookie,
    // e guarda a sessão pelo tempo que o painel realmente promete.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string)(NEXUS_SESSAO_MINUTOS * 60));

    // Pasta própria para as sessões (em hospedagem compartilhada o /tmp padrão
    // pode ser lido por outras contas do servidor). Se não der para criá-la,
    // cai no padrão do PHP em vez de quebrar o painel.
    $dirSessoes = NEXUS_DIR_SESSOES;
    if (!is_dir($dirSessoes)) {
        @mkdir($dirSessoes, 0700, true);
    }
    if (is_dir($dirSessoes) && is_writable($dirSessoes)) {
        session_save_path($dirSessoes);
        if (random_int(1, 20) === 1) {   // faxina de vez em quando (muitas hospedagens desligam a do PHP)
            nexus_limpar_sessoes($dirSessoes);
        }
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,        // o cookie não pode ser lido por JavaScript
        'secure'   => !nexus_local(), // sempre Secure (só o localhost de desenvolvimento fica de fora)
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Apaga sessões velhas da pasta privada: as vencidas e, mais cedo (30 min), as
 * "anônimas" (criadas só por abrir a tela de login, para o token CSRF) — assim
 * visitas em massa não enchem a pasta. Olha no máximo 2000 arquivos por vez.
 */
function nexus_limpar_sessoes(string $dir): void
{
    $agora    = time();
    $vencida  = $agora - (NEXUS_SESSAO_MINUTOS * 60 + 300);
    $anonimas = $agora - 1800;
    $n = 0;
    foreach (glob($dir . '/sess_*') ?: [] as $arq) {
        if (++$n > 2000) {
            break;
        }
        $mt = @filemtime($arq);
        if ($mt === false) {
            continue;
        }
        if ($mt < $vencida) {
            @unlink($arq);
            continue;
        }
        if ($mt < $anonimas && (int)@filesize($arq) <= 8192) {
            $conteudo = @file_get_contents($arq);
            if ($conteudo !== false && strpos($conteudo, 'nexus_ok') === false && strpos($conteudo, 'nexus_2fa') === false) {
                @unlink($arq);   // ninguém logou nem está no meio do login
            }
        }
    }
}

/**
 * Nonce da requisição (NX-13): um valor aleatório novo a cada página, enviado
 * no cabeçalho da CSP e repetido em cada <script> inline. Só scripts com esse
 * valor executam; um <script> injetado por uma falha futura não teria o nonce
 * e seria bloqueado pelo navegador. Uso nas páginas:
 *     <script nonce="<?= h(nexus_nonce()) ?>"> ... </script>
 */
function nexus_nonce(): string
{
    static $nonce = null;
    return $nonce ??= base64_encode(random_bytes(16));
}

/**
 * Cabeçalhos de segurança aplicados a todas as páginas.
 * $painel = true: páginas que não podem aparecer dentro de quadros (painel e API).
 */
function nexus_cabecalhos_seguranca(bool $painel = false): void
{
    header_remove('X-Powered-By');   // não anuncia a versão do PHP
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: ' . ($painel ? 'DENY' : 'SAMEORIGIN'));
    header('Referrer-Policy: same-origin');

    // O site não usa câmera, microfone nem geolocalização — nega tudo isso
    // explicitamente pra qualquer aba/iframe que tente pedir.
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    // CSP (NX-13): só recursos do próprio site e imagens em data:. Scripts
    // inline só executam com o nonce da requisição (sem 'unsafe-inline' em
    // script-src); scripts externos só do próprio site. Os ESTILOS ainda aceitam
    // 'unsafe-inline' porque as páginas usam atributos style="..." (isso só
    // permite CSS, não executa código; não coloque nonce aqui, senão o navegador
    // passa a ignorar o 'unsafe-inline' e os estilos inline quebram).
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'nonce-" . nexus_nonce() . "'; " .
        "style-src 'self' 'unsafe-inline'; " .
        "font-src 'self'; " .
        "img-src 'self' data:; " .
        "connect-src 'self'; " .
        "object-src 'none'; " .
        "frame-ancestors " . ($painel ? "'none'" : "'self'") . "; " .
        "base-uri 'self'; " .
        "form-action 'self'"
    );

    // HSTS: enviado sempre fora do localhost (o navegador ignora o cabeçalho
    // em respostas HTTP, então é seguro). Exige que o site rode 100% em HTTPS.
    if (!nexus_local()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    if ($painel) {
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    } else {
        // Páginas públicas: nunca guarda uma versão antiga em cache do
        // navegador/CDN. Sem isso, uma alteração publicada no painel pode
        // demorar a aparecer para quem já visitou o site antes.
        header('Cache-Control: no-cache, must-revalidate');
    }
}

/**
 * Executa $fn com exclusividade (trava de arquivo), para que requisições
 * simultâneas não leiam/gravem o mesmo contador ao mesmo tempo.
 * Se a trava não puder ser criada, executa mesmo assim (sem exclusividade).
 */
function nexus_com_trava(string $nome, callable $fn)
{
    $fp = @fopen(NEXUS_DIR_DADOS . '/' . $nome . '.lock', 'c');
    if ($fp === false) {
        return $fn();
    }
    flock($fp, LOCK_EX);
    try {
        return $fn();
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/**
 * Lê um JSON de arquivo. Devolve $padrao em caso de erro. (Não trava: as
 * gravações são atômicas — arquivo temporário + rename — e os contadores
 * de login usam nexus_com_trava().)
 */
function nexus_ler_json(string $arquivo, array $padrao = []): array
{
    if (!is_file($arquivo)) {
        return $padrao;
    }
    $conteudo = @file_get_contents($arquivo);
    if ($conteudo === false || trim($conteudo) === '') {
        return $padrao;
    }
    $dados = json_decode($conteudo, true);
    return is_array($dados) ? $dados : $padrao;
}

/**
 * Grava um JSON de forma atômica (escreve num temporário e renomeia),
 * para o site nunca ler um arquivo pela metade.
 */
function nexus_gravar_json(string $arquivo, array $dados): bool
{
    $dir = dirname($arquivo);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    $temp = $arquivo . '.tmp' . bin2hex(random_bytes(4));
    if (@file_put_contents($temp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($temp, $arquivo)) {
        @unlink($temp);
        return false;
    }
    @chmod($arquivo, 0664);
    return true;
}

/**
 * "Versão" do conteúdo publicado (hash do conteudo.json). O painel manda a
 * versão que carregou ao salvar; se mudou no meio, outra pessoa/aba salvou
 * antes e a gravação é recusada (evita desfazer o trabalho do outro).
 */
function nexus_revisao_conteudo(): string
{
    if (!is_file(NEXUS_ARQ_DADOS)) {
        return 'vazio';
    }
    $h = @md5_file(NEXUS_ARQ_DADOS);
    return $h === false ? 'vazio' : $h;
}

/**
 * Copia o conteudo.json atual para dados/backups/ ANTES de sobrescrevê-lo.
 * Guarda os NEXUS_BACKUPS_MANTER mais recentes. Sem $forcar, não faz mais de
 * um backup por NEXUS_BACKUP_INTERVALO segundos (o auto-salvar do painel grava
 * a cada pausa na digitação). Falha em silêncio: backup não pode derrubar o painel.
 */
function nexus_backup_conteudo(bool $forcar = false): void
{
    if (!is_file(NEXUS_ARQ_DADOS)) {
        return;
    }
    $dir = NEXUS_DIR_BACKUPS;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return;
    }
    $arqs = glob($dir . '/conteudo-*.json') ?: [];
    rsort($arqs);
    if (!$forcar && $arqs) {
        $mt = @filemtime($arqs[0]);
        if ($mt !== false && time() - $mt < NEXUS_BACKUP_INTERVALO) {
            return;
        }
    }
    $nome = $dir . '/conteudo-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.json';
    if (!@copy(NEXUS_ARQ_DADOS, $nome)) {
        return;
    }
    @chmod($nome, 0660);
    $arqs = glob($dir . '/conteudo-*.json') ?: [];
    rsort($arqs);
    foreach (array_slice($arqs, NEXUS_BACKUPS_MANTER) as $velho) {
        @unlink($velho);
    }
}

/**
 * Anonimiza um endereço IP (último octeto IPv4 zerado; IPv6 mantém só os 3 primeiros blocos).
 * Usado no log da edição de demonstração para não guardar IPs de visitantes.
 */
function nexus_ip_anonimo(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/\.\d+$/', '.0', $ip) ?? '-';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return implode(':', array_slice(explode(':', $ip), 0, 3)) . '::';
    }
    return '-';
}

/**
 * Registra uma ação no log de auditoria (dados/auditoria.log, uma linha JSON
 * por evento): quando, quem, de qual IP, o quê. Nunca grava senhas nem códigos.
 * O log é arquivado quando passa de NEXUS_AUDITORIA_MAX_BYTES e arquivos com
 * mais de NEXUS_AUDITORIA_DIAS dias são apagados. Falha em silêncio.
 */
function nexus_auditar(string $acao, string $detalhe = '', ?string $usuario = null): void
{
    if ($usuario === null) {
        $usuario = session_status() === PHP_SESSION_ACTIVE ? (string)($_SESSION['nexus_usuario'] ?? '-') : '-';
    }
    $linha = json_encode([
        'quando'  => date('c'),
        'usuario' => mb_substr($usuario, 0, 60, 'UTF-8'),
        'ip'      => nexus_demo() ? nexus_ip_anonimo((string)($_SERVER['REMOTE_ADDR'] ?? '-')) : (string)($_SERVER['REMOTE_ADDR'] ?? '-'),
        'acao'    => $acao,
        'detalhe' => mb_substr($detalhe, 0, 300, 'UTF-8'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($linha === false) {
        return;
    }
    nexus_com_trava('auditoria', function () use ($linha): void {
        $arq = NEXUS_ARQ_AUDITORIA;
        if (is_file($arq) && (int)@filesize($arq) > NEXUS_AUDITORIA_MAX_BYTES) {
            @rename($arq, NEXUS_DIR_DADOS . '/auditoria-' . date('Ymd-His') . '.log');
            foreach (glob(NEXUS_DIR_DADOS . '/auditoria-*.log') ?: [] as $velho) {
                $mt = @filemtime($velho);
                if ($mt !== false && $mt < time() - NEXUS_AUDITORIA_DIAS * 86400) {
                    @unlink($velho);
                }
            }
        }
        @file_put_contents($arq, $linha . "\n", FILE_APPEND | LOCK_EX);
        @chmod($arq, 0660);
    });
}

/**
 * Reabre uma imagem já salva em disco e regrava do zero via GD.
 *
 * Isso descarta qualquer metadado EXIF embutido no arquivo original
 * (que pode conter a localização GPS de onde a foto foi tirada) e
 * neutraliza qualquer "polyglot" — bytes extras escondidos depois dos
 * dados da imagem que tentem se passar por outro tipo de arquivo.
 *
 * Corrige a orientação da foto antes de descartar o EXIF (fotos de
 * celular costumas vir "deitadas" e dependem da tag de orientação
 * para aparecer em pé).
 *
 * FALHA DE FORMA SEGURA: se a extensão GD não estiver instalada, ou se a
 * regravação der errado, devolve false — quem chama deve apagar o arquivo,
 * senão o original (com EXIF/GPS e possíveis bytes embutidos) ficaria no ar.
 */
function nexus_reprocessar_imagem(string $caminho, int $tipoImagem): bool
{
    if (!function_exists('gd_info')) {
        return false;
    }

    $imagem = match ($tipoImagem) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($caminho),
        IMAGETYPE_PNG  => @imagecreatefrompng($caminho),
        IMAGETYPE_GIF  => @imagecreatefromgif($caminho),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($caminho) : false,
        default        => false,
    };
    if ($imagem === false) {
        return false;
    }

    // Corrige a orientação (fotos de celular) antes do EXIF ser descartado.
    if ($tipoImagem === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($caminho);
        $orientacao = $exif['Orientation'] ?? 1;
        $rotacoes = [3 => 180, 6 => -90, 8 => 90];
        if (isset($rotacoes[$orientacao])) {
            $girada = @imagerotate($imagem, $rotacoes[$orientacao], 0);
            if ($girada !== false) {
                imagedestroy($imagem);
                $imagem = $girada;
            }
        }
    }

    // PNG/GIF/WEBP podem ter transparência — preserva ao regravar.
    imagesavealpha($imagem, true);

    $ok = match ($tipoImagem) {
        IMAGETYPE_JPEG => imagejpeg($imagem, $caminho, 85),
        IMAGETYPE_PNG  => imagepng($imagem, $caminho, 6),
        IMAGETYPE_GIF  => imagegif($imagem, $caminho),
        IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($imagem, $caminho, 85) : false,
        default        => false,
    };

    imagedestroy($imagem);
    return $ok;
}

/** Limites de dimensão aceitos no upload (proteção contra "bomba de descompressão"). */
const NEXUS_IMG_MAX_LADO   = 6000;      // pixels por lado
const NEXUS_IMG_MAX_PIXELS = 24000000;  // largura x altura

/** Converte valores do php.ini como "128M" em bytes (-1 = sem limite). */
function nexus_bytes_ini(string $valor): int
{
    $valor = trim($valor);
    if ($valor === '' || $valor === '-1') {
        return -1;
    }
    $n = (int)$valor;
    switch (strtolower(substr($valor, -1))) {
        case 'g': $n *= 1024;
        // no break
        case 'm': $n *= 1024;
        // no break
        case 'k': $n *= 1024;
    }
    return $n;
}

/**
 * As dimensões declaradas são aceitáveis e cabem na memória do PHP?
 * (o GD guarda ~4 bytes por pixel, e a rotação de fotos de celular cria
 * uma cópia; por isso conta ~9 bytes por pixel).
 */
function nexus_imagem_dimensoes_ok(int $larg, int $alt): bool
{
    if ($larg < 1 || $alt < 1 || $larg > NEXUS_IMG_MAX_LADO || $alt > NEXUS_IMG_MAX_LADO
        || $larg * $alt > NEXUS_IMG_MAX_PIXELS) {
        return false;
    }
    $limite = nexus_bytes_ini((string)ini_get('memory_limit'));
    return $limite < 0 || ($larg * $alt * 9 + memory_get_usage()) <= $limite;
}

require_once __DIR__ . '/demo.php';
