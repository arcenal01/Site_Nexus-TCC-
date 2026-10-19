<?php
/*
 * SEGUNDO FATOR (2FA) — TOTP, RFC 6238
 * ------------------------------------------------------------------
 * Código de 6 dígitos que muda a cada 30 s, gerado por um aplicativo
 * autenticador (Google Authenticator, Microsoft Authenticator, Authy,
 * 2FAS, Aegis...). Sem bibliotecas de terceiros: só funções nativas do PHP.
 *
 * Também gera e confere os "códigos de recuperação" (uso único), que
 * servem de reserva se o celular for perdido.
 */

declare(strict_types=1);

const NEXUS_TOTP_ALFABETO = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // base32 (RFC 4648)
const NEXUS_TOTP_PERIODO  = 30;                                  // segundos por código
const NEXUS_TOTP_DIGITOS  = 6;
const NEXUS_RECUP_ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sem 0/O/1/I (evita confusão)

/** Binário -> base32 (sem preenchimento "="). */
function nexus_base32_codificar(string $bin): string
{
    $bits = '';
    $n = strlen($bin);
    for ($i = 0; $i < $n; $i++) {
        $bits .= str_pad(decbin(ord($bin[$i])), 8, '0', STR_PAD_LEFT);
    }
    $saida = '';
    foreach (str_split($bits, 5) as $grupo) {
        $grupo = str_pad($grupo, 5, '0', STR_PAD_RIGHT);
        $saida .= NEXUS_TOTP_ALFABETO[bindec($grupo)];
    }
    return $saida;
}

/** base32 -> binário. Devolve null se o texto não for base32 válido. */
function nexus_base32_decodificar(string $texto): ?string
{
    $texto = strtoupper((string)preg_replace('/[\s=\-]+/', '', $texto));
    if ($texto === '' || !preg_match('/^[A-Z2-7]+$/', $texto)) {
        return null;
    }
    $bits = '';
    foreach (str_split($texto) as $c) {
        $bits .= str_pad(decbin((int)strpos(NEXUS_TOTP_ALFABETO, $c)), 5, '0', STR_PAD_LEFT);
    }
    $bin = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) < 8) {
            break; // sobra de bits do preenchimento
        }
        $bin .= chr(bindec($byte));
    }
    return $bin;
}

/** Novo segredo aleatório (160 bits = 32 caracteres base32), como pede a RFC 4226. */
function nexus_totp_novo_segredo(): string
{
    return nexus_base32_codificar(random_bytes(20));
}

/** Código de 6 dígitos para um "passo" de 30 s (passo = time() / 30). */
function nexus_totp_codigo(string $segredo, int $passo): ?string
{
    $chave = nexus_base32_decodificar($segredo);
    if ($chave === null || $chave === '') {
        return null;
    }
    $contador = pack('N2', 0, $passo);              // inteiro de 64 bits, big-endian
    $hmac     = hash_hmac('sha1', $contador, $chave, true);
    $desloc   = ord($hmac[19]) & 0x0F;
    $numero   = ((ord($hmac[$desloc]) & 0x7F) << 24)
              | (ord($hmac[$desloc + 1]) << 16)
              | (ord($hmac[$desloc + 2]) << 8)
              |  ord($hmac[$desloc + 3]);
    return str_pad((string)($numero % (10 ** NEXUS_TOTP_DIGITOS)), NEXUS_TOTP_DIGITOS, '0', STR_PAD_LEFT);
}

/**
 * Confere um código digitado. Aceita o passo atual e um passo para cada lado
 * (±30 s), para tolerar relógio do celular um pouco adiantado/atrasado.
 * Devolve o passo que bateu, ou null. (Impedir que o MESMO código seja usado
 * duas vezes é feito por nexus_totp_passo_novo(), em auth.php.)
 */
function nexus_totp_verificar(string $segredo, string $codigo, ?int $agora = null): ?int
{
    $codigo = (string)preg_replace('/\D+/', '', $codigo);
    if (strlen($codigo) !== NEXUS_TOTP_DIGITOS) {
        return null;
    }
    $atual  = intdiv($agora ?? time(), NEXUS_TOTP_PERIODO);
    $achado = null;
    for ($d = -1; $d <= 1; $d++) {           // sem "return" antecipado: tempo igual em acerto e erro
        $esperado = nexus_totp_codigo($segredo, $atual + $d);
        if ($esperado !== null && hash_equals($esperado, $codigo)) {
            $achado = $atual + $d;
        }
    }
    return $achado;
}

/** Endereço otpauth:// (o celular abre o app autenticador ao tocar nele). */
function nexus_totp_uri(string $usuario, string $segredo): string
{
    $emissor = defined('NEXUS_2FA_EMISSOR') ? (string)NEXUS_2FA_EMISSOR : 'Nexus';
    return 'otpauth://totp/' . rawurlencode($emissor . ':' . $usuario)
        . '?secret=' . $segredo
        . '&issuer=' . rawurlencode($emissor)
        . '&algorithm=SHA1&digits=' . NEXUS_TOTP_DIGITOS
        . '&period=' . NEXUS_TOTP_PERIODO;
}

/** Segredo em grupos de 4 letras, para digitar com mais facilidade. */
function nexus_totp_segredo_legivel(string $segredo): string
{
    return trim(implode(' ', str_split($segredo, 4)));
}

/* ---------------- Códigos de recuperação ---------------- */

/** Deixa só letras/números em maiúsculas ("abcde-12345" -> "ABCDE12345"). */
function nexus_recuperacao_normalizar(string $codigo): string
{
    return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $codigo));
}

/**
 * Hash SHA-256 de um código de recuperação normalizado. Só o hash é gravado; o código em texto puro
 * é exibido uma única vez.
 */
function nexus_recuperacao_hash(string $codigo): string
{
    return hash('sha256', nexus_recuperacao_normalizar($codigo));
}

/**
 * Gera $quantidade códigos no formato XXXXX-XXXXX (50 bits cada).
 * Devolve [lista legível (mostrar UMA vez), lista de hashes (gravar)].
 */
function nexus_recuperacao_gerar(int $quantidade = 8): array
{
    $planos  = [];
    $hashes  = [];
    $maximo  = strlen(NEXUS_RECUP_ALFABETO) - 1;
    for ($i = 0; $i < $quantidade; $i++) {
        $c = '';
        for ($j = 0; $j < 10; $j++) {
            $c .= NEXUS_RECUP_ALFABETO[random_int(0, $maximo)];
        }
        $planos[] = substr($c, 0, 5) . '-' . substr($c, 5);
        $hashes[] = nexus_recuperacao_hash($c);
    }
    return [$planos, $hashes];
}
