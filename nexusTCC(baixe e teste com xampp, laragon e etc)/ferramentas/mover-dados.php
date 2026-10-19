<?php
/*
 * MOVE A PASTA dados/ PARA FORA DA RAIZ PÚBLICA (somente linha de comando)
 * ------------------------------------------------------------------
 * Uso, na pasta do site:   php ferramentas/mover-dados.php
 *
 * Copia dados/ para ../nexus-privado/dados (ao lado da pasta do site).
 * Depois disso o site passa a usar essa pasta automaticamente. Os arquivos
 * antigos NÃO são apagados: confira que o painel e o site funcionam e então
 * apague a pasta dados/ de dentro do site (deixe só o dados/.htaccess ou
 * remova a pasta inteira).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/config.php';

if (NEXUS_PRIVADO_ATIVO) {
    echo "Já está usando a pasta privada: " . NEXUS_DIR_DADOS . "\n";
    exit(0);
}

$destinoBase = dirname(NEXUS_RAIZ) . '/nexus-privado';
$destino     = $destinoBase . '/dados';
if (!is_dir($destino) && !@mkdir($destino, 0770, true) && !is_dir($destino)) {
    fwrite(STDERR, "Não foi possível criar $destino (verifique as permissões da pasta acima do site).\n");
    exit(1);
}

$copiados = 0;
foreach (['senha.php', 'conteudo.json', 'tentativas.json', 'totp-uso.json', 'auditoria.log'] as $nome) {
    $origem = NEXUS_RAIZ . '/dados/' . $nome;
    if (is_file($origem)) {
        if (!@copy($origem, $destino . '/' . $nome)) {
            fwrite(STDERR, "Falha ao copiar $nome.\n");
            exit(1);
        }
        @chmod($destino . '/' . $nome, 0660);
        echo "copiado: $nome\n";
        $copiados++;
    }
}
echo "\nPronto ($copiados arquivo(s)). Dados agora em: $destino\n";
echo "Teste o painel e o site; depois apague a pasta dados/ de dentro do site.\n";
echo "O servidor web (PHP) precisa ter permissão de ESCRITA em $destino.\n";
