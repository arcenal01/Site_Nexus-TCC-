<?php
/*
 * AUTENTICAÇÃO DO PAINEL
 * ------------------------------------------------------------------
 * O que mudou em relação à versão antiga:
 *
 *  - A senha NÃO fica escrita em nenhum arquivo de código. Só existe
 *    no servidor um HASH (Argon2id ou bcrypt, ver nexus_hash_config), dentro de
 *    "dados/senha.php" — pasta bloqueada pelo .htaccess. Esse arquivo é
 *    criado só no servidor (ferramentas/criar-conta.php) e nunca deve ir
 *    em zip, e-mail ou repositório.
 *  - A conferência da senha acontece no servidor, em PHP. O navegador
 *    nunca recebe a senha nem o hash.
 *  - O acesso é guardado numa sessão PHP com cookie HttpOnly +
 *    SameSite=Strict, e não mais num sessionStorage que o JavaScript
 *    da página consegue ler.
 *  - Toda gravação exige um token CSRF, para outro site não conseguir
 *    disparar alterações usando a sessão aberta do administrador.
 *  - Após 5 senhas erradas o IP fica bloqueado (10 min, dobrando a cada
 *    ciclo) e após 10 erradas a CONTA também, venha de qual IP vier.
 *  - Verificação em duas etapas (2FA, NX-07): depois da senha, o painel pede
 *    um código de 6 dígitos do aplicativo autenticador (ou um código de
 *    recuperação). Quem ainda não tem 2FA configura no primeiro login.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/totp.php';

/**
 * Lista de credenciais gravadas em dados/senha.php.
 * Cada item: ['usuario' => ..., 'hash' => ...] e, se a conta já configurou o 2FA,
 * também 'totp' (segredo) e 'recuperacao' (hashes). Lista vazia = não instalado.
 *
 * Só o formato atual (multiusuário) é aceito (NX-16). Os formatos antigos de uma
 * conta só, com ou sem usuário, foram removidos: contas sem nome de usuário são
 * ignoradas. Se o arquivo estiver num formato antigo, o painel fica "não configurado"
 * até as contas serem recriadas com ferramentas/criar-conta.php.
 */
function nexus_credenciais_lista(): array
{
    if (!is_file(NEXUS_ARQ_SENHA)) {
        return [];
    }
    $dado = require NEXUS_ARQ_SENHA;

    // Formato atual (multiusuário): ['usuarios' => [['usuario'=>,'hash'=>], ...]]
    if (is_array($dado) && isset($dado['usuarios']) && is_array($dado['usuarios'])) {
        $lista = [];
        foreach ($dado['usuarios'] as $u) {
            $usuario = is_array($u) && is_string($u['usuario'] ?? null) ? $u['usuario'] : '';
            $hash    = is_array($u) && is_string($u['hash'] ?? null) ? $u['hash'] : '';
            if ($hash === '' || trim($usuario) === '') {
                continue;   // sem usuário ou sem hash: conta inválida, ignorada (NX-16)
            }
            $item = ['usuario' => $usuario, 'hash' => $hash];
            // 2FA (opcional por conta): segredo TOTP + hashes dos códigos de recuperação
            $totp = is_array($u) && is_string($u['totp'] ?? null) ? $u['totp'] : '';
            if ($totp !== '' && preg_match('/^[A-Z2-7]{16,64}$/', $totp)) {
                $recup = [];
                foreach ((is_array($u['recuperacao'] ?? null) ? $u['recuperacao'] : []) as $r) {
                    if (is_string($r) && preg_match('/^[0-9a-f]{64}$/', $r)) {
                        $recup[] = $r;
                    }
                }
                $item['totp']       = $totp;
                $item['recuperacao'] = $recup;
            }
            $lista[] = $item;
        }
        return $lista;
    }

    return [];
}

/** Procura uma conta pelo nome de usuário (case-insensitive). */
function nexus_credencial_por_usuario(string $usuario): ?array
{
    foreach (nexus_credenciais_lista() as $u) {
        if (mb_strtolower(trim($u['usuario']), 'UTF-8') === mb_strtolower(trim($usuario), 'UTF-8')) {
            return $u;
        }
    }
    return null;
}

/**
 * Senhas mais comuns/óbvias (comparadas em minúsculas). Não é uma lista de
 * vazamentos completa: ela só barra o básico, o resto fica com a consulta
 * ao Have I Been Pwned (nexus_senha_vazada).
 */
function nexus_senhas_comuns(): array
{
    return [
        '123456', '1234567', '12345678', '123456789', '1234567890', '12345678910', '123123123', '111111111111',
        '000000000000', '123456789012', '1234567890123', 'password', 'password1', 'password12', 'password123',
        'passw0rd', 'p@ssw0rd', 'p@ssword', 'senha123', 'senha1234', 'senha12345', 'minhasenha', 'minhasenha123',
        'mudar123', 'mudar1234', 'trocar123', 'qwerty', 'qwerty123', 'qwertyuiop', 'qwertyuiop123', '1q2w3e4r5t',
        '1qaz2wsx', 'abc123456', 'abcd1234', 'abcdefgh', 'abcdefghijkl', 'admin123', 'admin1234', 'admin12345',
        'administrador', 'administrador123', 'nexus2026', 'nexus2025', 'nexus123', 'nexus1234', 'nexus12345',
        'nexustecno', 'nexustecno123', 'nexustecno2026', 'tecnologia', 'tecnologia123', 'letmein', 'letmein123',
        'iloveyou', 'iloveyou123', 'welcome1', 'welcome123', 'bemvindo', 'bemvindo123', 'brasil123', 'brasil2026',
        'corinthians', 'flamengo123', '87654321', '11111111', '00000000', '12341234', '123qweasd', 'qazwsxedc',
    ];
}

/**
 * Palavras fracas: uma senha que é só uma delas (com números e símbolos
 * em volta, ex.: "Senha@2026!") cai rápido em ataques por dicionário.
 */
function nexus_palavras_fracas(): array
{
    return [
        'senha', 'password', 'passwd', 'nexus', 'nexustecno', 'admin', 'administrador', 'painel', 'qwerty',
        'qwertyuiop', 'abcdefgh', 'abcdefghij', 'letmein', 'iloveyou', 'welcome', 'bemvindo', 'brasil',
        'escola', 'tecnologia', 'tecno', 'mudar', 'trocar', 'teste', 'secret', 'secreta', 'segredo',
    ];
}

/**
 * Regras de senha (NX-11), pensadas para frases longas em vez de "complexidade":
 *   - NEXUS_SENHA_MIN (12) a NEXUS_SENHA_MAX_BYTES (72 bytes; o bcrypt ignora o resto);
 *   - frase longa (16+ caracteres) OU mistura de ao menos 3 tipos de caractere;
 *   - não pode ser só números, nem uma senha/palavra óbvia, nem repetir o mesmo
 *     caractere em sequência, nem conter o nome de usuário.
 * $usuario é o nome de usuário da conta (vazio = não confere).
 * Só regras locais, sem rede; a consulta de vazamentos é nexus_senha_validar().
 * Devolve ['ok' => bool, 'motivos' => string[]].
 */
function nexus_senha_forte(string $senha, string $usuario = ''): array
{
    $motivos = [];
    $tamanho = mb_strlen($senha, 'UTF-8');
    if ($tamanho < NEXUS_SENHA_MIN) {
        $motivos[] = 'pelo menos ' . NEXUS_SENHA_MIN . ' caracteres';
    }
    if (strlen($senha) > NEXUS_SENHA_MAX_BYTES) {
        $motivos[] = 'no máximo ' . NEXUS_SENHA_MAX_BYTES . ' bytes (acentos e emojis contam mais de 1)';
    }
    $classes = 0;
    if (preg_match('/[a-z]/', $senha)) { $classes++; }
    if (preg_match('/[A-Z]/', $senha)) { $classes++; }
    if (preg_match('/[0-9]/', $senha)) { $classes++; }
    if (preg_match('/[^A-Za-z0-9]/', $senha)) { $classes++; }
    if ($tamanho < 16 && $classes < 3) {
        $motivos[] = 'use uma frase com 16+ caracteres ou misture ao menos 3 tipos: minúsculas, maiúsculas, números e símbolos';
    }
    if (preg_match('/^[0-9]+$/', $senha)) {
        $motivos[] = 'não use só números';
    }
    $minuscula = mb_strtolower($senha, 'UTF-8');
    $soLetras  = (string)preg_replace('/[^a-z]+/', '', $minuscula);
    if (
        in_array($minuscula, nexus_senhas_comuns(), true)
        || (strlen($soLetras) <= 14 && in_array($soLetras, nexus_palavras_fracas(), true))
    ) {
        $motivos[] = 'essa senha é comum ou baseada em uma palavra óbvia, escolha outra';
    }
    if (preg_match('/(.)\1{4,}/u', $senha)) {
        $motivos[] = 'evite repetir o mesmo caractere várias vezes seguidas';
    }
    $usuario = trim($usuario);
    if ($usuario !== '' && mb_strlen($usuario, 'UTF-8') >= 3 && mb_stripos($senha, $usuario, 0, 'UTF-8') !== false) {
        $motivos[] = 'não use o nome de usuário dentro da senha';
    }
    return ['ok' => empty($motivos), 'motivos' => $motivos];
}

/**
 * Consulta o serviço Have I Been Pwned (Pwned Passwords) pelo método
 * k-anonymity: só os 5 primeiros caracteres do SHA-1 da senha saem do
 * servidor; a comparação do resto é feita aqui. A senha nunca é enviada.
 * true = apareceu em vazamentos | false = não apareceu | null = não deu
 * para verificar (sem internet, serviço fora do ar, recurso desligado).
 * Falha "aberta" de propósito: se a consulta não funciona, a troca de senha
 * não fica travada.
 */
function nexus_senha_vazada(string $senha): ?bool
{
    if (!NEXUS_SENHA_CONSULTAR_VAZADAS) {
        return null;
    }
    $sha     = strtoupper(sha1($senha));
    $prefixo = substr($sha, 0, 5);
    $sufixo  = substr($sha, 5);
    $url     = 'https://api.pwnedpasswords.com/range/' . $prefixo;
    $corpo   = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => ['Add-Padding: true', 'User-Agent: Nexus-Painel'],
            ]);
            $r = curl_exec($ch);
            if (is_string($r) && (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
                $corpo = $r;
            }
        }
    } elseif ((bool)ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => [
            'timeout' => 4,
            'header'  => "Add-Padding: true\r\nUser-Agent: Nexus-Painel\r\n",
        ]]);
        $r = @file_get_contents($url, false, $ctx);
        if (is_string($r) && $r !== '') {
            $corpo = $r;
        }
    }
    if ($corpo === null) {
        return null;
    }
    foreach (preg_split('/\r\n|\n|\r/', $corpo) ?: [] as $linha) {
        $partes = explode(':', trim($linha), 2);
        if (count($partes) === 2 && hash_equals($sufixo, strtoupper(trim($partes[0]))) && (int)$partes[1] > 0) {
            return true;   // (as linhas de preenchimento do Add-Padding vêm com contagem 0)
        }
    }
    return false;
}

/** Regras locais + consulta de vazamentos. É esta que as telas de criar/trocar senha usam. */
function nexus_senha_validar(string $senha, string $usuario = ''): array
{
    $r = nexus_senha_forte($senha, $usuario);
    if ($r['ok'] && nexus_senha_vazada($senha) === true) {
        $r['ok']        = false;
        $r['motivos'][] = 'essa senha já apareceu em vazamentos de dados conhecidos, escolha outra';
    }
    return $r;
}

/* ---------------- Hash de senha (NX-11) ---------------- */

/** [algoritmo, opções] usados para gravar hashes novos. */
function nexus_hash_config(): array
{
    if (NEXUS_HASH_PREFERIR_ARGON2ID && defined('PASSWORD_ARGON2ID')) {
        return [PASSWORD_ARGON2ID, []];   // memória/tempo padrão do PHP (adequados para esta escala)
    }
    return [PASSWORD_BCRYPT, ['cost' => NEXUS_BCRYPT_CUSTO]];
}

/** Gera o hash de uma senha com o algoritmo/custo atuais. Devolve '' se falhar. */
function nexus_hash_senha(string $senha): string
{
    [$algo, $opcoes] = nexus_hash_config();
    $hash = password_hash($senha, $algo, $opcoes);
    return is_string($hash) ? $hash : '';
}

/** true se o hash gravado é de um algoritmo/custo mais fraco que o atual (regravar no próximo login). */
function nexus_hash_precisa_atualizar(string $hash): bool
{
    // Demonstração: o arquivo de contas é somente leitura, nunca é regravado.
    if (nexus_demo()) {
        return false;
    }
    [$algo, $opcoes] = nexus_hash_config();
    return password_needs_rehash($hash, $algo, $opcoes);
}

/**
 * O nome de usuário é aceitável? Letras, números, ponto, traço e underline, de 3 a 40 caracteres.
 */
function nexus_usuario_valido(string $usuario): bool
{
    return (bool)preg_match('/^[A-Za-z0-9_.\-]{3,40}$/', trim($usuario));
}

/** Grava a lista inteira de contas em dados/senha.php (escrita atômica). */
function nexus_gravar_lista_credenciais(array $lista): bool
{
    $limpa = [];
    foreach ($lista as $u) {
        $usuario = trim((string)($u['usuario'] ?? ''));
        $hash    = (string)($u['hash'] ?? '');
        if ($usuario !== '' && $hash !== '') {
            $item = ['usuario' => $usuario, 'hash' => $hash];
            if (is_string($u['totp'] ?? null) && $u['totp'] !== '') {
                $item['totp']        = $u['totp'];
                $item['recuperacao'] = array_values(array_filter(
                    is_array($u['recuperacao'] ?? null) ? $u['recuperacao'] : [],
                    'is_string'
                ));
            }
            $limpa[] = $item;
        }
    }
    $conteudo = "<?php\n"
        . "/* Credenciais do painel Nexus (multiusuário). Gerado automaticamente.\n"
        . "   NÃO edite à mão e NÃO compartilhe este arquivo. */\n"
        . "return ['usuarios' => " . var_export($limpa, true) . "];\n";

    if (!is_dir(NEXUS_DIR_DADOS)) {
        @mkdir(NEXUS_DIR_DADOS, 0775, true);
    }
    $temp = NEXUS_ARQ_SENHA . '.tmp' . bin2hex(random_bytes(4));
    if (@file_put_contents($temp, $conteudo, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($temp, NEXUS_ARQ_SENHA)) {
        @unlink($temp);
        return false;
    }
    @chmod(NEXUS_ARQ_SENHA, 0640);
    // O OPcache pode guardar a versão antiga do arquivo por alguns segundos;
    // sem isto o 2FA recém-configurado não valeria de imediato.
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate(NEXUS_ARQ_SENHA, true);
    }
    return true;
}

/**
 * Cria ou atualiza uma conta. Sem $usuarioAntigo, faz upsert pelo próprio
 * $usuario (cria se não existir, atualiza a senha se já existir). Com
 * $usuarioAntigo, localiza a conta por esse nome e a renomeia para $usuario
 * (usado ao trocar o próprio usuário/senha logado).
 */
function nexus_definir_credenciais(string $usuario, string $senhaLimpa, ?string $usuarioAntigo = null): bool
{
    $hash = nexus_hash_senha($senhaLimpa);
    if ($hash === '') {
        return false;
    }
    $usuario   = trim($usuario);
    $chaveAlvo = trim($usuarioAntigo ?? $usuario);

    // Trava: leitura + alteração + gravação sem ninguém gravar no meio
    // (senão uma troca de senha poderia apagar um 2FA recém-configurado).
    return (bool)nexus_com_trava('senha', function () use ($usuario, $hash, $chaveAlvo): bool {
        $lista = nexus_credenciais_lista();
        $achou = false;
        foreach ($lista as $i => $u) {
            if (mb_strtolower(trim($u['usuario']), 'UTF-8') === mb_strtolower($chaveAlvo, 'UTF-8')) {
                // array_merge mantém o 2FA (totp/recuperacao) da conta
                $lista[$i] = array_merge($u, ['usuario' => $usuario, 'hash' => $hash]);
                $achou = true;
                break;
            }
        }
        if (!$achou) {
            $lista[] = ['usuario' => $usuario, 'hash' => $hash];
        }
        return nexus_gravar_lista_credenciais($lista);
    });
}

/** true se já existe alguma conta com esse nome (ignorando $ignorar, para permitir manter o próprio nome ao editar). */
function nexus_usuario_existe(string $usuario, ?string $ignorar = null): bool
{
    foreach (nexus_credenciais_lista() as $u) {
        if ($ignorar !== null && mb_strtolower(trim($u['usuario']), 'UTF-8') === mb_strtolower(trim($ignorar), 'UTF-8')) {
            continue;
        }
        if (mb_strtolower(trim($u['usuario']), 'UTF-8') === mb_strtolower(trim($usuario), 'UTF-8')) {
            return true;
        }
    }
    return false;
}

/**
 * Existe ao menos uma conta cadastrada? Sem contas, o painel responde "não configurado" (NX-16).
 */
function nexus_instalado(): bool
{
    return nexus_credenciais_lista() !== [];
}

/* ---------------- Controle de tentativas (força bruta) ---------------- */

/*
 * Dois contadores independentes, ambos em dados/tentativas.json:
 *   - por IP (5 falhas)            -> chave = hash do IP
 *   - por conta (10 falhas)        -> chave = "u" + hash do nome de usuário
 * A tentativa é RESERVADA (contada) ANTES de conferir a senha, dentro de
 * uma trava de arquivo; só é zerada se o login der certo. Assim, requisições
 * simultâneas não conseguem passar todas pela checagem antes da contagem.
 */

function nexus_ip(): string
{
    // Só REMOTE_ADDR: nunca ler X-Forwarded-For aqui (o cliente pode forjar).
    // Atrás de CDN/proxy, configure o servidor (mod_remoteip / real_ip_header)
    // para entregar o IP verdadeiro em REMOTE_ADDR.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr(hash('sha256', (string)$ip), 0, 32); // guardamos só o hash do IP
}

/**
 * "Selo" da sessão: muda quando a senha ou o aparelho do 2FA da conta mudam.
 * Sessões abertas antes da mudança deixam de bater com o selo e caem na hora
 * (trocar a senha derruba quem tinha roubado uma sessão).
 */
function nexus_selo_sessao(?array $cred): string
{
    return $cred === null ? '' : hash('sha256', (string)$cred['hash'] . '|' . (string)($cred['totp'] ?? ''));
}

/** Impressão do navegador (hash do User-Agent), usada para amarrar a sessão. */
function nexus_ua(): string
{
    return substr(hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 32);
}

/** Chave do contador por conta (independe de maiúsculas/minúsculas e de espaços). */
function nexus_chave_conta(string $usuario): string
{
    return 'u' . substr(hash('sha256', mb_strtolower(trim($usuario), 'UTF-8')), 0, 32);
}

/** Lê todos os contadores, já sem registros com mais de 24h. */
function nexus_tentativas_todas(): array
{
    $todas = nexus_ler_json(NEXUS_ARQ_LOGIN, []);
    $agora = time();
    foreach ($todas as $chave => $reg) {
        if (!is_array($reg) || ($reg['ts'] ?? 0) < $agora - 86400) {
            unset($todas[$chave]);
        }
    }
    return $todas;
}

/**
 * Devolve o registro de tentativas de uma chave (IP ou conta), com valores padrão para campos ausentes.
 */
function nexus_registro(array $todas, string $chave): array
{
    $r = is_array($todas[$chave] ?? null) ? $todas[$chave] : [];
    return $r + ['n' => 0, 'ts' => 0, 'ciclos' => 0];
}

/**
 * Duração do bloqueio para quem já foi bloqueado $ciclos vezes seguidas
 * sem acertar a senha: dobra a cada novo ciclo (10min, 20min, 40min...),
 * com teto de 6 horas — dificulta um ataque que apenas espera o bloqueio
 * passar e tenta de novo.
 */
function nexus_duracao_bloqueio(int $ciclos, int $base = NEXUS_BLOQUEIO_SEGUNDOS): int
{
    $multiplicador = 2 ** max(0, min($ciclos, 5));
    return min($base * $multiplicador, 6 * 3600);
}

/** Segundos restantes de bloqueio de um registro (0 = liberado). */
function nexus_registro_restante(array $reg, int $max, int $base): int
{
    if ((int)$reg['n'] < $max) {
        return 0;
    }
    $fim = (int)$reg['ts'] + nexus_duracao_bloqueio((int)$reg['ciclos'], $base);
    return max(0, $fim - time());
}

/**
 * Segundos restantes de bloqueio (0 = liberado): vale o maior entre o do IP
 * atual e o da conta informada (se houver).
 */
function nexus_bloqueio_restante(?string $usuario = null): int
{
    $todas = nexus_tentativas_todas();
    $r = nexus_registro_restante(
        nexus_registro($todas, nexus_ip()),
        NEXUS_MAX_TENTATIVAS,
        NEXUS_BLOQUEIO_SEGUNDOS
    );
    if ($usuario !== null && trim($usuario) !== '') {
        $r = max($r, nexus_registro_restante(
            nexus_registro($todas, nexus_chave_conta($usuario)),
            NEXUS_MAX_TENTATIVAS_CONTA,
            NEXUS_BLOQUEIO_CONTA_SEGUNDOS
        ));
    }
    return $r;
}

/**
 * Tenta reservar uma tentativa em um registro. Devolve false se ele está
 * bloqueado; senão conta a tentativa (já abrindo um novo ciclo, mais longo,
 * se o bloqueio anterior expirou) e devolve true. Altera $todas.
 */
function nexus_reservar_em(array &$todas, string $chave, int $max, int $base): bool
{
    $reg = nexus_registro($todas, $chave);
    if ($reg['n'] >= $max) {
        if (nexus_registro_restante($reg, $max, $base) > 0) {
            return false; // ainda bloqueado
        }
        // bloqueio expirou: novo ciclo, mais longo que o anterior
        $reg = ['n' => 0, 'ts' => 0, 'ciclos' => min((int)$reg['ciclos'] + 1, 5)];
    }
    $todas[$chave] = ['n' => (int)$reg['n'] + 1, 'ts' => time(), 'ciclos' => (int)$reg['ciclos']];
    return true;
}

/**
 * Contadores usados por um "escopo" de tentativas. Escopo vazio = o login
 * (e o 2FA); outro nome (ex.: 'troca') tem contadores PRÓPRIOS, para que
 * errar a "senha atual" na troca de senha não bloqueie o login de ninguém
 * (e vice-versa). Devolve [[chave, máximo, base do bloqueio], ...] (IP e conta).
 */
function nexus_contadores_escopo(string $usuario, string $escopo = ''): array
{
    if ($escopo === '') {
        return [
            [nexus_ip(), NEXUS_MAX_TENTATIVAS, NEXUS_BLOQUEIO_SEGUNDOS],
            [nexus_chave_conta($usuario), NEXUS_MAX_TENTATIVAS_CONTA, NEXUS_BLOQUEIO_CONTA_SEGUNDOS],
        ];
    }
    return [
        ['i' . $escopo . '_' . nexus_ip(), NEXUS_MAX_TENTATIVAS_TROCA, NEXUS_BLOQUEIO_TROCA_SEGUNDOS],
        [nexus_chave_conta($escopo . ':' . $usuario), NEXUS_MAX_TENTATIVAS_TROCA, NEXUS_BLOQUEIO_TROCA_SEGUNDOS],
    ];
}

/**
 * Reserva uma tentativa ANTES de conferir a senha, para o IP e para a
 * conta, de forma atômica. false = bloqueado (não conferir a senha).
 * Só é desfeita por nexus_limpar_falhas(), quando a conferência der certo.
 * $escopo: '' = login/2FA; 'troca' = "senha atual" na troca de senha (NX-12).
 */
function nexus_reservar_tentativa(string $usuario, string $escopo = ''): bool
{
    return (bool)nexus_com_trava('tentativas', function () use ($usuario, $escopo): bool {
        $copia = nexus_tentativas_todas();
        foreach (nexus_contadores_escopo($usuario, $escopo) as [$chave, $max, $base]) {
            if (!nexus_reservar_em($copia, $chave, $max, $base)) {
                return false;
            }
        }
        nexus_gravar_json(NEXUS_ARQ_LOGIN, $copia);
        return true;
    });
}

/** Conferência correta: zera os contadores do IP e da conta (do escopo informado). */
function nexus_limpar_falhas(string $usuario, string $escopo = ''): void
{
    nexus_com_trava('tentativas', function () use ($usuario, $escopo): void {
        $todas = nexus_tentativas_todas();
        foreach (nexus_contadores_escopo($usuario, $escopo) as [$chave]) {
            unset($todas[$chave]);
        }
        nexus_gravar_json(NEXUS_ARQ_LOGIN, $todas);
    });
}

/** true se o limite do escopo (IP ou conta) já foi atingido, ou seja, a PRÓXIMA tentativa seria recusada. */
function nexus_escopo_esgotado(string $usuario, string $escopo): bool
{
    $todas = nexus_tentativas_todas();
    foreach (nexus_contadores_escopo($usuario, $escopo) as [$chave, $max, $base]) {
        if (nexus_registro_restante(nexus_registro($todas, $chave), $max, $base) > 0) {
            return true;
        }
    }
    return false;
}

/* ---------------- Sessão ---------------- */

/**
 * Confere usuário + senha contra a lista de contas cadastradas. Se bater,
 * abre uma etapa pendente de 2FA (ver nexus_2fa_pendente) e devolve true;
 * o acesso ao painel só é liberado por nexus_2fa_confirmar().
 */
function nexus_login(string $usuario, string $senha): bool
{
    $lista = nexus_credenciais_lista();
    if (!$lista) {
        return false;
    }

    // Conta a tentativa ANTES de conferir a senha (IP + conta, com trava).
    // Se estiver bloqueado, nem confere a senha.
    if (!nexus_reservar_tentativa($usuario)) {
        nexus_auditar('login_bloqueado', 'limite de tentativas', nexus_nome_para_log($usuario));
        return false;
    }

    // Procura a conta pelo usuário digitado. Percorre TODAS (sem parar na primeira
    // que bate), para o tempo gasto não depender de qual conta é.
    $alvo = null;
    foreach ($lista as $item) {
        $usuarioOk = hash_equals(
            hash('sha256', mb_strtolower(trim($item['usuario']), 'UTF-8')),
            hash('sha256', mb_strtolower(trim($usuario), 'UTF-8'))
        );
        if ($usuarioOk && $alvo === null) {
            $alvo = $item;
        }
    }

    // NX-10: a conferência de senha SEMPRE acontece, mesmo quando o usuário não
    // existe. Sem isso, usuário inexistente respondia na hora e usuário existente
    // demorava (o bcrypt é lento de propósito), e medir o tempo revelaria quais
    // nomes são válidos. Quando não há conta, confere contra o hash de outra
    // conta (mesmo algoritmo e custo) e joga o resultado fora.
    $hashConferido = $alvo !== null ? (string)$alvo['hash'] : (string)$lista[0]['hash'];
    $senhaOk       = password_verify($senha, $hashConferido);   // compara em tempo constante

    if ($alvo === null || !$senhaOk) {
        usleep(300000); // atraso igual nos dois casos, atrapalha tentativas automatizadas
        nexus_auditar(
            'login_falha',
            $alvo === null ? 'usuario desconhecido' : 'senha incorreta',
            $alvo === null ? '(desconhecido)' : (string)$alvo['usuario']
        );
        return false;   // a tentativa já foi contada na reserva; a mensagem não diz qual dos dois errou
    }

    $cred         = $alvo;
    $usuarioFinal = trim($cred['usuario']);
    if (nexus_hash_precisa_atualizar((string)$cred['hash'])) {
        // senha correta: se o algoritmo ou o custo atual é mais forte que o do hash gravado (NX-11), regrava
        nexus_definir_credenciais($usuarioFinal, $senha, $usuarioFinal);
    }

    // Senha correta. Falta o segundo fator: abre só uma sessão "pendente"
    // (que NÃO dá acesso ao painel) e os contadores de falha continuam
    // valendo até o código também ser aceito.
    $credAtual = nexus_credencial_por_usuario($usuarioFinal) ?? $cred;
    nexus_2fa_iniciar($credAtual);
    return true;
}

/* ---------------- Segundo fator (2FA) ---------------- */

/** Nome para o log: só o de uma conta que existe (quem digita a senha no campo errado não vai para o log). */
function nexus_nome_para_log(string $usuario): string
{
    $cred = nexus_credencial_por_usuario($usuario);
    return $cred !== null ? (string)$cred['usuario'] : '(desconhecido)';
}

/** Compara dois nomes de usuário ignorando maiúsculas/minúsculas e espaços. */
function nexus_mesmo_usuario(string $a, string $b): bool
{
    return mb_strtolower(trim($a), 'UTF-8') === mb_strtolower(trim($b), 'UTF-8');
}

/**
 * Grava (ou apaga, com $segredo = null) o 2FA de uma conta, com trava.
 * Apagar faz a conta configurar um novo aparelho no próximo login.
 */
function nexus_2fa_gravar(string $usuario, ?string $segredo, array $hashesRecuperacao = []): bool
{
    return (bool)nexus_com_trava('senha', function () use ($usuario, $segredo, $hashesRecuperacao): bool {
        $lista = nexus_credenciais_lista();
        foreach ($lista as $i => $u) {
            if (!nexus_mesmo_usuario($u['usuario'], $usuario)) {
                continue;
            }
            unset($u['totp'], $u['recuperacao']);
            if ($segredo !== null) {
                $u['totp']        = $segredo;
                $u['recuperacao'] = array_values($hashesRecuperacao);
            }
            $lista[$i] = $u;
            return nexus_gravar_lista_credenciais($lista);
        }
        return false;
    });
}

/** Resumo para a tela: 2FA ativo? quantos códigos de recuperação restam? */
function nexus_2fa_resumo(string $usuario): array
{
    $cred = nexus_credencial_por_usuario($usuario);
    return [
        'ativo'     => $cred !== null && !empty($cred['totp']),
        'restantes' => $cred !== null ? count($cred['recuperacao'] ?? []) : 0,
    ];
}

/**
 * Consome um código de recuperação (uso único). true se existia e foi
 * removido da lista.
 */
function nexus_2fa_usar_recuperacao(string $usuario, string $codigo): bool
{
    if (strlen(nexus_recuperacao_normalizar($codigo)) !== 10) {
        return false;
    }
    $h = nexus_recuperacao_hash($codigo);
    return (bool)nexus_com_trava('senha', function () use ($usuario, $h): bool {
        $lista = nexus_credenciais_lista();
        foreach ($lista as $i => $u) {
            if (!nexus_mesmo_usuario($u['usuario'], $usuario)) {
                continue;
            }
            $restantes = [];
            $achou     = false;
            foreach (($u['recuperacao'] ?? []) as $r) {
                if (!$achou && hash_equals($r, $h)) {
                    $achou = true;      // este é o código usado: sai da lista
                    continue;
                }
                $restantes[] = $r;
            }
            if (!$achou) {
                return false;
            }
            $lista[$i]['recuperacao'] = $restantes;
            return nexus_gravar_lista_credenciais($lista);
        }
        return false;
    });
}

/**
 * Registra que o passo de 30 s $passo foi usado por esta conta. false se esse
 * passo (ou um mais antigo) já foi usado: o mesmo código não vale duas vezes,
 * então quem espiar ou interceptar um código não consegue reaproveitá-lo.
 */
function nexus_totp_passo_novo(string $usuario, int $passo): bool
{
    return (bool)nexus_com_trava('totp', function () use ($usuario, $passo): bool {
        $todos = nexus_ler_json(NEXUS_ARQ_TOTP_USO, []);
        $chave = nexus_chave_conta($usuario);
        if ((int)($todos[$chave] ?? 0) >= $passo) {
            return false;
        }
        $todos[$chave] = $passo;
        nexus_gravar_json(NEXUS_ARQ_TOTP_USO, $todos);
        return true;
    });
}

/**
 * Valida um código digitado para uma conta que JÁ tem 2FA: 6 dígitos = código
 * do aplicativo; qualquer outra coisa = código de recuperação (uso único).
 */
function nexus_2fa_validar_codigo(array $cred, string $codigo): bool
{
    $usuario = (string)$cred['usuario'];
    $limpo   = (string)preg_replace('/\s+/', '', $codigo);
    if (preg_match('/^\d{6}$/', $limpo)) {
        $passo = nexus_totp_verificar((string)($cred['totp'] ?? ''), $limpo);
        return $passo !== null && nexus_totp_passo_novo($usuario, $passo);
    }
    return $limpo !== '' && nexus_2fa_usar_recuperacao($usuario, $limpo);
}

/** Etapa pendente de 2FA (senha já confere, falta o código), ou null. */
function nexus_2fa_pendente(): ?array
{
    nexus_iniciar_sessao();
    $p = $_SESSION['nexus_2fa'] ?? null;
    if (!is_array($p)) {
        return null;
    }
    $valida = is_string($p['usuario'] ?? null)
        && (int)($p['ts'] ?? 0) + NEXUS_2FA_JANELA_SEGUNDOS >= time()
        && ($p['ip'] ?? '') === nexus_ip()
        && ($p['ua'] ?? '') === nexus_ua();
    if (!$valida) {
        unset($_SESSION['nexus_2fa']);
        return null;
    }
    return $p;
}

/**
 * Descarta a etapa de 2FA pendente; a pessoa precisa informar usuário e senha novamente.
 */
function nexus_2fa_cancelar(): void
{
    nexus_iniciar_sessao();
    unset($_SESSION['nexus_2fa']);
}

/**
 * Abre a etapa de 2FA depois que a senha conferiu. Conta sem 2FA entra no
 * modo "configurar" (mostra um segredo novo); conta com 2FA, em "verificar".
 */
function nexus_2fa_iniciar(array $cred): void
{
    nexus_iniciar_sessao();
    session_regenerate_id(true);  // evita fixação de sessão
    unset($_SESSION['nexus_ok']);
    $p = [
        'usuario' => (string)$cred['usuario'],
        'modo'    => empty($cred['totp']) ? 'configurar' : 'verificar',
        'ts'      => time(),
        'ip'      => nexus_ip(),
        'ua'      => nexus_ua(),
        'erros'   => 0,
    ];
    if ($p['modo'] === 'configurar') {
        $p['segredo'] = nexus_totp_novo_segredo();   // só vai para o arquivo quando o código for confirmado
    }
    $_SESSION['nexus_2fa']  = $p;
    $_SESSION['nexus_csrf'] = bin2hex(random_bytes(32));
}

/** Libera o painel: senha E segundo fator conferiram. */
function nexus_concluir_login(string $usuario): void
{
    nexus_iniciar_sessao();
    session_regenerate_id(true);
    unset($_SESSION['nexus_2fa']);
    $_SESSION['nexus_ok']      = true;
    $_SESSION['nexus_usuario'] = $usuario;
    $_SESSION['nexus_ip']      = nexus_ip();
    $_SESSION['nexus_ua']      = nexus_ua();
    $_SESSION['nexus_visto']   = time();
    $_SESSION['nexus_inicio']  = time();   // para o tempo máximo absoluto da sessão
    $_SESSION['nexus_selo']    = nexus_selo_sessao(nexus_credencial_por_usuario($usuario));
    $_SESSION['nexus_csrf']    = bin2hex(random_bytes(32));
    nexus_limpar_falhas($usuario);
    nexus_auditar('login_ok', '', $usuario);
}

/**
 * Confere o código digitado na etapa pendente.
 * Devolve 'ok' | 'invalido' | 'bloqueado' | 'expirado' | 'erro'.
 * Cada tentativa é contada ANTES de conferir (mesmo limite por IP e por conta
 * do login) e só é zerada quando o acesso é liberado.
 */
function nexus_2fa_confirmar(string $codigo): string
{
    $p = nexus_2fa_pendente();
    if ($p === null) {
        return 'expirado';
    }
    $usuario = (string)$p['usuario'];
    $cred    = nexus_credencial_por_usuario($usuario);
    if ($cred === null) {
        nexus_2fa_cancelar();
        return 'expirado';
    }
    if (!nexus_reservar_tentativa($usuario)) {
        nexus_auditar('2fa_bloqueado', 'limite de tentativas', $usuario);
        return 'bloqueado';
    }

    $limpo = (string)preg_replace('/\s+/', '', $codigo);

    unset($_SESSION['nexus_2fa']['dica'], $_SESSION['nexus_2fa']['dica_desvio']);
    // Demonstração: o código fixo libera o acesso (sem gravar o 2FA, a tela continua igual no próximo login).
    if (defined('NEXUS_MODO_DEMO') && NEXUS_MODO_DEMO && defined('NEXUS_DEMO_CODIGO')
        && hash_equals((string)NEXUS_DEMO_CODIGO, $limpo)) {
        nexus_concluir_login($usuario);
        return 'ok';
    }
    if (($p['modo'] ?? '') === 'configurar') {
        // Primeira vez: o código precisa bater com o segredo que está na sessão.
        $segredo = (string)($p['segredo'] ?? '');
        $passo   = preg_match('/^\d{6}$/', $limpo) ? nexus_totp_verificar($segredo, $limpo) : null;
        if ($passo !== null && nexus_totp_passo_novo($usuario, $passo)) {
            if (nexus_demo()) {
                // Demonstração: o código foi válido, mas o segredo não é gravado (a conta pública
                // não pode ser alterada por visitantes) e não há códigos de recuperação.
                nexus_concluir_login($usuario);
                return 'ok';
            }
            [$planos, $hashes] = nexus_recuperacao_gerar(8);
            if (!nexus_2fa_gravar($usuario, $segredo, $hashes)) {
                return 'erro';
            }
            nexus_concluir_login($usuario);
            $_SESSION['nexus_codigos_novos'] = $planos;   // mostrados uma única vez
            return 'ok';
        }
        // Diagnóstico (só na configuração, quando o segredo acabou de ser mostrado na tela):
        // o código estaria certo se o relógio do servidor estivesse correto? Ou é um código já usado?
        if (preg_match('/^\d{6}$/', $limpo)) {
            if ($passo !== null) {
                $_SESSION['nexus_2fa']['dica'] = 'usado';
            } else {
                $agora = time();
                for ($d = -720; $d <= 720; $d++) {     // até ±6 horas
                    $esp = nexus_totp_codigo($segredo, intdiv($agora, NEXUS_TOTP_PERIODO) + $d);
                    if ($esp !== null && hash_equals($esp, $limpo)) {
                        $_SESSION['nexus_2fa']['dica']       = 'relogio';
                        $_SESSION['nexus_2fa']['dica_desvio'] = $d * NEXUS_TOTP_PERIODO;
                        break;
                    }
                }
            }
        }
    } elseif (nexus_2fa_validar_codigo($cred, $limpo)) {
        nexus_concluir_login($usuario);
        return 'ok';
    }

    usleep(300000);
    nexus_auditar('2fa_falha', 'código incorreto' . (isset($_SESSION['nexus_2fa']['dica']) ? ' (' . $_SESSION['nexus_2fa']['dica']
        . (isset($_SESSION['nexus_2fa']['dica_desvio']) ? ' ' . $_SESSION['nexus_2fa']['dica_desvio'] . 's' : '') . ')' : ''), $usuario);
    $erros = (int)($_SESSION['nexus_2fa']['erros'] ?? 0) + 1;
    $_SESSION['nexus_2fa']['erros'] = $erros;
    if ($erros >= NEXUS_2FA_MAX_ERROS) {
        nexus_2fa_cancelar();   // exige digitar usuário e senha de novo
        return 'expirado';
    }
    return 'invalido';
}

/**
 * Encerra a sessão: limpa os dados, remove o cookie e destrói a sessão no servidor.
 */
function nexus_logout(): void
{
    nexus_iniciar_sessao();
    if (!empty($_SESSION['nexus_ok'])) {
        nexus_auditar('sessao_encerrada', 'saiu ou expirou');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * true se a sessão do painel está válida.
 */
function nexus_logado(): bool
{
    nexus_iniciar_sessao();
    if (empty($_SESSION['nexus_ok'])) {
        return false;
    }
    // expira por inatividade
    if (time() - (int)($_SESSION['nexus_visto'] ?? 0) > NEXUS_SESSAO_MINUTOS * 60) {
        nexus_logout();
        return false;
    }
    // amarra a sessão ao IP e ao navegador que fizeram o login
    if (($_SESSION['nexus_ip'] ?? '') !== nexus_ip()) {
        nexus_logout();
        return false;
    }
    if (($_SESSION['nexus_ua'] ?? '') !== substr(hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 32)) {
        nexus_logout();
        return false;
    }
    // tempo máximo absoluto (a inatividade sozinha nunca expiraria quem não para de usar)
    if (time() - (int)($_SESSION['nexus_inicio'] ?? 0) > NEXUS_SESSAO_MAX_HORAS * 3600) {
        nexus_logout();
        return false;
    }
    // a senha (ou o aparelho do 2FA) mudou depois deste login? então a sessão não vale mais
    $selo = nexus_selo_sessao(nexus_credencial_por_usuario((string)($_SESSION['nexus_usuario'] ?? '')));
    if ($selo === '' || !hash_equals((string)($_SESSION['nexus_selo'] ?? ''), $selo)) {
        nexus_logout();
        return false;
    }
    $_SESSION['nexus_visto'] = time();
    return true;
}

/**
 * Devolve o token CSRF da sessão, criando um novo (32 bytes aleatórios) se ainda não existir.
 */
function nexus_csrf(): string
{
    nexus_iniciar_sessao();
    if (empty($_SESSION['nexus_csrf'])) {
        $_SESSION['nexus_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['nexus_csrf'];
}

/**
 * O token recebido confere com o da sessão? Comparação em tempo constante.
 */
function nexus_csrf_valido(?string $token): bool
{
    nexus_iniciar_sessao();
    $esperado = $_SESSION['nexus_csrf'] ?? '';
    return is_string($token) && $esperado !== '' && hash_equals($esperado, $token);
}
