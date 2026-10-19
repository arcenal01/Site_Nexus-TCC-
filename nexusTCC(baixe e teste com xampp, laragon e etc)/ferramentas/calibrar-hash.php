<?php
/*
 * CALIBRA O CUSTO DO HASH DE SENHA (somente linha de comando) — NX-11
 * ------------------------------------------------------------------
 * Uso, na pasta do site:
 *     php ferramentas/calibrar-hash.php
 *
 * Mede quanto tempo o SEU servidor leva para conferir uma senha em cada
 * custo do bcrypt (e no Argon2id, se o PHP tiver suporte) e indica qual
 * custo fica perto de 200 a 300 ms. Para mudar, ajuste NEXUS_BCRYPT_CUSTO
 * em inc/config.php. As contas existentes são atualizadas sozinhas no
 * próximo login de cada uma. Pela web este script não faz nada.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/config.php';

/**
 * Mede, em milissegundos, o tempo de gerar um hash com o algoritmo e as opções informados
 * (menor valor de 3 medidas).
 */
function medir_ms(string $algo, array $opcoes): float
{
    $senha = 'calibracao-' . bin2hex(random_bytes(6));
    $melhor = INF;
    for ($i = 0; $i < 3; $i++) {            // 3 medidas, vale a menor (a mais "limpa")
        $ini  = hrtime(true);
        $hash = password_hash($senha, $algo, $opcoes);
        $fim  = hrtime(true);
        if (is_string($hash)) {
            $melhor = min($melhor, ($fim - $ini) / 1e6);
        }
    }
    return $melhor;
}

echo "Calibração do hash de senha (alvo: 200 a 300 ms por conferência)\n\n";
echo "Configuração atual: ";
if (NEXUS_HASH_PREFERIR_ARGON2ID && defined('PASSWORD_ARGON2ID')) {
    echo "Argon2id (o custo do bcrypt abaixo só vale se o Argon2id ficar indisponível)\n\n";
} else {
    echo "bcrypt, custo " . NEXUS_BCRYPT_CUSTO . "\n\n";
}

$recomendado = null;
echo "bcrypt:\n";
foreach ([10, 11, 12, 13, 14] as $custo) {
    $ms = medir_ms(PASSWORD_BCRYPT, ['cost' => $custo]);
    $marca = '';
    if ($ms >= 200 && $ms <= 300 && $recomendado === null) {
        $recomendado = $custo;
        $marca = '  <-- na faixa';
    }
    if ($custo === NEXUS_BCRYPT_CUSTO) {
        $marca .= '  (atual)';
    }
    printf("  custo %2d: %7.0f ms%s\n", $custo, $ms, $marca);
}

if (defined('PASSWORD_ARGON2ID')) {
    $ms = medir_ms(PASSWORD_ARGON2ID, []);
    printf("\nArgon2id (padrão do PHP): %.0f ms\n", $ms);
} else {
    echo "\nArgon2id: não disponível neste PHP (o site usa bcrypt).\n";
}

echo "\n";
if ($recomendado !== null) {
    echo "Sugestão: NEXUS_BCRYPT_CUSTO = {$recomendado} em inc/config.php.\n";
} else {
    echo "Nenhum custo caiu exatamente na faixa; escolha o maior que ainda deixe o login confortável\n";
    echo "(abaixo de ~500 ms) e nunca menos de 12.\n";
}
echo "Lembrete: o custo 12 ou mais é o mínimo recomendado hoje. Servidores lentos podem ficar acima de 300 ms; é aceitável.\n";
