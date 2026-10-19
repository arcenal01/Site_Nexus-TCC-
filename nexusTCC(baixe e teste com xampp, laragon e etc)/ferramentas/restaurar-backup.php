<?php
/*
 * RESTAURA UM BACKUP DO CONTEÚDO (somente linha de comando)
 * ------------------------------------------------------------------
 *   php ferramentas/restaurar-backup.php                 -> lista os backups (do mais novo ao mais antigo)
 *   php ferramentas/restaurar-backup.php ARQUIVO.json    -> volta o site para esse backup
 *
 * Antes de restaurar, o conteúdo atual também vira um backup, então dá para desfazer.
 * Os backups ficam em dados/backups/ (feitos automaticamente a cada gravação do painel).
 * Pela web este script não faz nada.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/config.php';

$arqs = glob(NEXUS_DIR_BACKUPS . '/conteudo-*.json') ?: [];
rsort($arqs);

$alvo = $argv[1] ?? '';
if ($alvo === '') {
    if (!$arqs) {
        echo "Ainda não há backups (eles são criados quando o painel salva).\n";
        exit(0);
    }
    echo "Backups disponíveis:\n";
    foreach ($arqs as $a) {
        echo ' - ' . basename($a) . '  (' . date('d/m/Y H:i:s', (int)filemtime($a)) . ', ' . filesize($a) . " bytes)\n";
    }
    echo "\nPara restaurar: php ferramentas/restaurar-backup.php NOME-DO-ARQUIVO.json\n";
    exit(0);
}

$nome = basename($alvo);
if (!preg_match('/^conteudo-[0-9]{8}-[0-9]{6}-[0-9a-f]{4}\\.json$/', $nome) || !is_file(NEXUS_DIR_BACKUPS . '/' . $nome)) {
    fwrite(STDERR, "Backup não encontrado: " . $nome . "\n");
    exit(1);
}
$origem = NEXUS_DIR_BACKUPS . '/' . $nome;
$dados  = json_decode((string)file_get_contents($origem), true);
if (!is_array($dados)) {
    fwrite(STDERR, "O backup está corrompido (JSON inválido).\n");
    exit(1);
}

$ok = nexus_com_trava('conteudo', function () use ($dados): bool {
    nexus_backup_conteudo(true);
    return nexus_gravar_json(NEXUS_ARQ_DADOS, $dados);
});
if (!$ok) {
    fwrite(STDERR, "Não foi possível gravar dados/conteudo.json (verifique as permissões).\n");
    exit(1);
}
nexus_auditar('backup_restaurado', $nome, 'servidor (linha de comando)');
echo "Pronto: o site voltou para " . $nome . ". O conteúdo anterior também ficou em backup.\n";
