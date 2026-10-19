<?php
/*
 * REDEFINE O 2FA DE UMA CONTA DO PAINEL (somente linha de comando)
 * ------------------------------------------------------------------
 * Para quando alguém perdeu o celular E os códigos de recuperação.
 *
 *   php ferramentas/redefinir-2fa.php             -> lista as contas e a situação do 2FA
 *   php ferramentas/redefinir-2fa.php USUARIO     -> apaga o 2FA dessa conta
 *
 * Depois disso, no próximo login (usuário + senha) o painel pede para
 * configurar o autenticador de novo e mostra novos códigos de recuperação.
 * Pela web este script não faz nada.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/auth.php';

$lista = nexus_credenciais_lista();
if (!$lista) {
    fwrite(STDERR, "Nenhuma conta cadastrada. Rode antes: php ferramentas/criar-conta.php\n");
    exit(1);
}

$usuario = $argv[1] ?? '';
if ($usuario === '') {
    echo "Contas e situação do 2FA:\n";
    foreach ($lista as $u) {
        $situacao = !empty($u['totp'])
            ? '2FA ativo (' . count($u['recuperacao'] ?? []) . ' códigos de recuperação)'
            : '2FA ainda não configurado (será pedido no próximo login)';
        echo ' - ' . $u['usuario'] . ': ' . $situacao . "\n";
    }
    echo "\nPara apagar o 2FA de uma conta: php ferramentas/redefinir-2fa.php USUARIO\n";
    exit(0);
}

$cred = nexus_credencial_por_usuario($usuario);
if ($cred === null) {
    fwrite(STDERR, "Conta não encontrada: " . $usuario . "\n");
    exit(1);
}
if (!nexus_2fa_gravar($cred['usuario'], null)) {
    fwrite(STDERR, "Não foi possível gravar dados/senha.php (verifique as permissões da pasta dados/).\n");
    exit(1);
}
echo "2FA da conta \"" . $cred['usuario'] . "\" apagado. No próximo login ela vai configurar o autenticador de novo.\n";
