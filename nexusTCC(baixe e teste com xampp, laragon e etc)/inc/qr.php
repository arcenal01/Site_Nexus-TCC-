<?php
/*
 * GERADOR DE QR CODE (PHP puro, sem bibliotecas nem serviços externos)
 * ------------------------------------------------------------------
 * Usado só para mostrar o QR da verificação em duas etapas (2FA). Tudo é
 * calculado no servidor e entregue como SVG dentro da página: a chave do
 * 2FA nunca sai do site (nada de "api de QR code" de terceiros) e a CSP
 * continua valendo.
 *
 * Suporta texto em bytes (UTF-8), correção de erro nível M, versões 1 a 10
 * (até 213 bytes) — de sobra para o endereço otpauth://.
 * Baseado no algoritmo da norma ISO/IEC 18004.
 */

declare(strict_types=1);

/** [bytes de correção por bloco, [[qtd. blocos, bytes de dados por bloco], ...]] — nível M. */
const NEXUS_QR_BLOCOS_M = [
    1  => [10, [[1, 16]]],
    2  => [16, [[1, 28]]],
    3  => [26, [[1, 44]]],
    4  => [18, [[2, 32]]],
    5  => [24, [[2, 43]]],
    6  => [16, [[4, 27]]],
    7  => [18, [[4, 31]]],
    8  => [22, [[2, 38], [2, 39]]],
    9  => [22, [[3, 36], [2, 37]]],
    10 => [26, [[4, 43], [1, 44]]],
];

const NEXUS_QR_ALINHAMENTO = [
    1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
    6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
];

/** Tabelas do corpo de Galois GF(256) (polinômio 0x11D) usadas no Reed-Solomon. */
function nexus_qr_gf(): array
{
    static $t = null;
    if ($t === null) {
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }
        $t = [$exp, $log];
    }
    return $t;
}

/** Bytes de correção de erro (Reed-Solomon) para um bloco de dados. */
function nexus_qr_reed_solomon(array $dados, int $grau): array
{
    [$exp, $log] = nexus_qr_gf();
    // polinômio gerador de grau $grau
    $gerador = [1];
    for ($i = 0; $i < $grau; $i++) {
        $novo = array_fill(0, count($gerador) + 1, 0);
        foreach ($gerador as $j => $c) {
            $novo[$j]     ^= $c;
            $novo[$j + 1] ^= $c === 0 ? 0 : $exp[$log[$c] + $i];
        }
        $gerador = $novo;
    }
    $resto = array_fill(0, $grau, 0);
    foreach ($dados as $b) {
        $fator = $b ^ array_shift($resto);
        $resto[] = 0;
        if ($fator !== 0) {
            for ($i = 0; $i < $grau; $i++) {
                $g = $gerador[$i + 1];
                if ($g !== 0) {
                    $resto[$i] ^= $exp[$log[$fator] + $log[$g]];
                }
            }
        }
    }
    return $resto;
}

/**
 * Matriz do QR code: array de linhas, cada uma com bool (true = módulo escuro).
 * Lança InvalidArgumentException se o texto não couber (mais de 213 bytes).
 */
function nexus_qr_matriz(string $texto): array
{
    $bytes = array_values(unpack('C*', $texto) ?: []);
    $n     = count($bytes);

    // --- escolhe a menor versão em que o texto cabe ---
    $versao = 0;
    foreach (NEXUS_QR_BLOCOS_M as $v => [$ecc, $grupos]) {
        $dadosTotal = 0;
        foreach ($grupos as [$qtd, $tam]) {
            $dadosTotal += $qtd * $tam;
        }
        $bitsLen = $v >= 10 ? 16 : 8;
        if (4 + $bitsLen + 8 * $n <= $dadosTotal * 8) {
            $versao = $v;
            break;
        }
    }
    if ($versao === 0) {
        throw new InvalidArgumentException('Texto grande demais para o QR code.');
    }
    [$ecc, $grupos] = NEXUS_QR_BLOCOS_M[$versao];
    $dadosTotal = 0;
    foreach ($grupos as [$qtd, $tam]) {
        $dadosTotal += $qtd * $tam;
    }

    // --- bits de dados: modo bytes + tamanho + conteúdo + terminador + enchimento ---
    $bits = [];
    $poe = function (int $valor, int $qtd) use (&$bits): void {
        for ($i = $qtd - 1; $i >= 0; $i--) {
            $bits[] = ($valor >> $i) & 1;
        }
    };
    $poe(0b0100, 4);
    $poe($n, $versao >= 10 ? 16 : 8);
    foreach ($bytes as $b) {
        $poe($b, 8);
    }
    $poe(0, min(4, $dadosTotal * 8 - count($bits)));
    while (count($bits) % 8 !== 0) {
        $bits[] = 0;
    }
    $palavras = [];
    for ($i = 0; $i < count($bits); $i += 8) {
        $v = 0;
        for ($j = 0; $j < 8; $j++) {
            $v = ($v << 1) | $bits[$i + $j];
        }
        $palavras[] = $v;
    }
    for ($pad = 0xEC; count($palavras) < $dadosTotal; $pad ^= 0xEC ^ 0x11) {
        $palavras[] = $pad;
    }

    // --- divide em blocos, calcula a correção de cada um e intercala ---
    $blocosDados = [];
    $blocosEcc   = [];
    $pos = 0;
    foreach ($grupos as [$qtd, $tam]) {
        for ($k = 0; $k < $qtd; $k++) {
            $bloco         = array_slice($palavras, $pos, $tam);
            $pos          += $tam;
            $blocosDados[] = $bloco;
            $blocosEcc[]   = nexus_qr_reed_solomon($bloco, $ecc);
        }
    }
    $final = [];
    $maxTam = max(array_map('count', $blocosDados));
    for ($i = 0; $i < $maxTam; $i++) {
        foreach ($blocosDados as $b) {
            if (isset($b[$i])) {
                $final[] = $b[$i];
            }
        }
    }
    for ($i = 0; $i < $ecc; $i++) {
        foreach ($blocosEcc as $b) {
            $final[] = $b[$i];
        }
    }

    // --- desenha os padrões fixos ---
    $tam      = $versao * 4 + 17;
    $mod      = array_fill(0, $tam, array_fill(0, $tam, false));
    $reservado = array_fill(0, $tam, array_fill(0, $tam, false));
    $poeMod = function (int $x, int $y, bool $escuro) use (&$mod, &$reservado, $tam): void {
        if ($x >= 0 && $x < $tam && $y >= 0 && $y < $tam) {
            $mod[$y][$x]       = $escuro;
            $reservado[$y][$x] = true;
        }
    };
    for ($i = 0; $i < $tam; $i++) {        // linhas de tempo
        $poeMod(6, $i, $i % 2 === 0);
        $poeMod($i, 6, $i % 2 === 0);
    }
    foreach ([[3, 3], [$tam - 4, 3], [3, $tam - 4]] as [$cx, $cy]) {   // 3 quadrados de canto
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $d = max(abs($dx), abs($dy));
                $poeMod($cx + $dx, $cy + $dy, $d !== 2 && $d !== 4);
            }
        }
    }
    $alin = NEXUS_QR_ALINHAMENTO[$versao];
    $ult  = count($alin) - 1;
    foreach ($alin as $i => $ax) {
        foreach ($alin as $j => $ay) {
            if (($i === 0 && $j === 0) || ($i === 0 && $j === $ult) || ($i === $ult && $j === 0)) {
                continue;
            }
            for ($dy = -2; $dy <= 2; $dy++) {
                for ($dx = -2; $dx <= 2; $dx++) {
                    $poeMod($ax + $dx, $ay + $dy, max(abs($dx), abs($dy)) !== 1);
                }
            }
        }
    }
    $desenharFormato = function (int $mascara) use ($poeMod, $tam): void {
        $dados = (0 << 3) | $mascara;                 // nível M = 0
        $r = $dados;
        for ($i = 0; $i < 10; $i++) {
            $r = ($r << 1) ^ (($r >> 9) * 0x537);
        }
        $b = (($dados << 10) | $r) ^ 0x5412;
        $bit = fn(int $i): bool => (($b >> $i) & 1) === 1;
        for ($i = 0; $i <= 5; $i++) {
            $poeMod(8, $i, $bit($i));
        }
        $poeMod(8, 7, $bit(6));
        $poeMod(8, 8, $bit(7));
        $poeMod(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $poeMod(14 - $i, 8, $bit($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $poeMod($tam - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $poeMod(8, $tam - 15 + $i, $bit($i));
        }
        $poeMod(8, $tam - 8, true);                   // módulo escuro fixo
    };
    $desenharFormato(0);   // só para reservar o espaço; é redesenhado com a máscara escolhida
    if ($versao >= 7) {
        $r = $versao;
        for ($i = 0; $i < 12; $i++) {
            $r = ($r << 1) ^ (($r >> 11) * 0x1F25);
        }
        $b = ($versao << 12) | $r;
        for ($i = 0; $i < 18; $i++) {
            $bit = (($b >> $i) & 1) === 1;
            $a   = $tam - 11 + $i % 3;
            $c   = intdiv($i, 3);
            $poeMod($a, $c, $bit);
            $poeMod($c, $a, $bit);
        }
    }

    // --- coloca os bytes de dados em zigue-zague ---
    $i = 0;
    $total = count($final) * 8;
    for ($direita = $tam - 1; $direita >= 1; $direita -= 2) {
        if ($direita === 6) {
            $direita = 5;
        }
        for ($vert = 0; $vert < $tam; $vert++) {
            for ($j = 0; $j < 2; $j++) {
                $x  = $direita - $j;
                $sobe = (($direita + 1) & 2) === 0;
                $y  = $sobe ? $tam - 1 - $vert : $vert;
                if (!$reservado[$y][$x] && $i < $total) {
                    $mod[$y][$x] = (($final[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                    $i++;
                }
            }
        }
    }

    // --- escolhe a máscara com a menor penalidade ---
    $mascarar = function (int $m) use (&$mod, $reservado, $tam): void {
        for ($y = 0; $y < $tam; $y++) {
            for ($x = 0; $x < $tam; $x++) {
                $inverte = match ($m) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => ($x * $y) % 2 + ($x * $y) % 3 === 0,
                    6 => (($x * $y) % 2 + ($x * $y) % 3) % 2 === 0,
                    default => (($x + $y) % 2 + ($x * $y) % 3) % 2 === 0,
                };
                if ($inverte && !$reservado[$y][$x]) {
                    $mod[$y][$x] = !$mod[$y][$x];
                }
            }
        }
    };
    $melhor = 0;
    $menor  = PHP_INT_MAX;
    for ($m = 0; $m < 8; $m++) {
        $mascarar($m);
        $desenharFormato($m);
        $p = nexus_qr_penalidade($mod, $tam);
        $mascarar($m);                                // desfaz
        if ($p < $menor) {
            $menor  = $p;
            $melhor = $m;
        }
    }
    $mascarar($melhor);
    $desenharFormato($melhor);
    return $mod;
}

/** Penalidade da norma (regras 1 a 4): quanto menor, mais fácil de ler. */
function nexus_qr_penalidade(array $mod, int $tam): int
{
    $p = 0;
    for ($pass = 0; $pass < 2; $pass++) {
        for ($a = 0; $a < $tam; $a++) {
            $linha = [];
            for ($b = 0; $b < $tam; $b++) {
                $linha[] = $pass === 0 ? $mod[$a][$b] : $mod[$b][$a];
            }
            // regra 1: sequências de 5+ módulos iguais
            $corrida = 1;
            for ($b = 1; $b < $tam; $b++) {
                if ($linha[$b] === $linha[$b - 1]) {
                    $corrida++;
                    if ($corrida === 5) {
                        $p += 3;
                    } elseif ($corrida > 5) {
                        $p++;
                    }
                } else {
                    $corrida = 1;
                }
            }
            // regra 3: padrão parecido com o quadrado de canto (1011101 com 4 claros ao lado)
            $s = implode('', array_map(fn($v) => $v ? '1' : '0', $linha));
            $p += 40 * (substr_count($s, '10111010000') + substr_count($s, '00001011101'));
        }
    }
    // regra 2: blocos 2x2 da mesma cor
    for ($y = 0; $y < $tam - 1; $y++) {
        for ($x = 0; $x < $tam - 1; $x++) {
            $c = $mod[$y][$x];
            if ($c === $mod[$y][$x + 1] && $c === $mod[$y + 1][$x] && $c === $mod[$y + 1][$x + 1]) {
                $p += 3;
            }
        }
    }
    // regra 4: equilíbrio entre escuros e claros
    $escuros = 0;
    foreach ($mod as $l) {
        $escuros += count(array_filter($l));
    }
    $pct = $escuros * 100 / ($tam * $tam);
    $p  += intdiv((int)abs($pct - 50), 5) * 10;
    return $p;
}

/**
 * QR code como SVG pronto para colocar na página (fundo branco, margem de 4 módulos).
 * $descricao vira o texto alternativo para leitores de tela.
 */
function nexus_qr_svg(string $texto, string $descricao = 'QR code'): string
{
    $mod = nexus_qr_matriz($texto);
    $tam = count($mod);
    $m   = 4;                                          // margem exigida pela norma
    $d   = '';
    foreach ($mod as $y => $linha) {
        $x = 0;
        while ($x < $tam) {
            if ($linha[$x]) {                          // junta módulos escuros vizinhos numa barra só
                $ini = $x;
                while ($x < $tam && $linha[$x]) {
                    $x++;
                }
                $d .= 'M' . ($ini + $m) . ' ' . ($y + $m) . 'h' . ($x - $ini) . 'v1h-' . ($x - $ini) . 'z';
            } else {
                $x++;
            }
        }
    }
    $lado = $tam + 2 * $m;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $lado . ' ' . $lado . '"'
        . ' role="img" aria-label="' . htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') . '" shape-rendering="crispEdges">'
        . '<rect width="' . $lado . '" height="' . $lado . '" fill="#fff"/>'
        . '<path d="' . $d . '" fill="#000"/></svg>';
}
