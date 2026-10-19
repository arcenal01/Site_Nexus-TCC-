<?php
/*
 * LIMPA IMAGENS ÓRFÃS DE uploads/tcc/ (somente linha de comando)
 * ------------------------------------------------------------------
 * Apaga imagens que NÃO são citadas em conteudo.json e têm mais de 30 dias.
 *
 *   php ferramentas/limpar-uploads.php            -> só lista o que seria apagado
 *   php ferramentas/limpar-uploads.php --apagar   -> apaga de verdade
 *   php ferramentas/limpar-uploads.php --apagar --dias=60
 *
 * Para rodar todo mês (cron):  0 3 1 * *  php /caminho/ferramentas/limpar-uploads.php --apagar
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/config.php';

$apagar = in_array('--apagar', $argv, true);
$dias   = 30;
foreach ($argv as $a) {
    if (preg_match('/^--dias=(\d{1,4})$/', $a, $m)) {
        $dias = max(1, (int)$m[1]);
    }
}

$json = is_file(NEXUS_ARQ_DADOS) ? (string)file_get_contents(NEXUS_ARQ_DADOS) : '';
if ($json === '' && is_file(NEXUS_ARQ_DADOS)) {
    fwrite(STDERR, "Não consegui ler conteudo.json — nada foi apagado.\n");
    exit(1);
}
// json_encode escapa "/" como "\/" em alguns casos: normaliza antes de procurar os nomes
$json = str_replace('\\/', '/', $json);

$limite = time() - $dias * 86400;
$total = 0;
foreach (glob(NEXUS_DIR_UPLOAD . '/tcc-*') ?: [] as $arq) {
    $nome = basename($arq);
    if (!is_file($arq) || filemtime($arq) > $limite || str_contains($json, $nome)) {
        continue;
    }
    $total++;
    if ($apagar) {
        @unlink($arq);
        echo "apagado: $nome\n";
    } else {
        echo "seria apagado: $nome\n";
    }
}
echo $apagar ? "\n$total arquivo(s) apagado(s).\n" : "\n$total arquivo(s) órfão(s) com mais de $dias dias (use --apagar para remover).\n";
