<?php
/*
 * CRIA OU ATUALIZA UMA CONTA DO PAINEL (somente linha de comando)
 * ------------------------------------------------------------------
 * Uso, na pasta do site (no XAMPP use o php.exe de xampp\php):
 *     php ferramentas/criar-conta.php
 *
 * Gera/atualiza dados/senha.php NO SERVIDOR. Esse arquivo não vai no
 * pacote do site: cada ambiente (local e produção) cria o seu.
 * Pela web este script não faz nada.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/auth.php';

/**
 * Exibe uma pergunta no terminal e devolve a resposta digitada, sem espaços nas pontas.
 */
function perguntar(string $texto): string
{
    echo $texto;
    $linha = fgets(STDIN);
    return $linha === false ? '' : trim($linha);
}

echo "Conta do painel Nexus\n";
echo "Use um usuário neutro (3 a 40: letras, números, _ . -) e uma senha única de 14+ caracteres.\n\n";

$usuario = perguntar('Usuário: ');
if (!nexus_usuario_valido($usuario)) {
    fwrite(STDERR, "Usuário inválido (3 a 40 caracteres: letras, números, _ . -).\n");
    exit(1);
}

$senha = perguntar('Senha (aparece na tela enquanto digita): ');
if (strlen($senha) < 14) {
    fwrite(STDERR, "Use pelo menos 14 caracteres.\n");
    exit(1);
}
$forca = nexus_senha_validar($senha, $usuario);   // regras + consulta de vazamentos (NX-11)
if (!$forca['ok']) {
    fwrite(STDERR, "Senha fraca: " . implode('; ', $forca['motivos']) . ".\n");
    exit(1);
}
if ($senha !== perguntar('Repita a senha: ')) {
    fwrite(STDERR, "As senhas não conferem.\n");
    exit(1);
}

if (!nexus_definir_credenciais($usuario, $senha)) {
    fwrite(STDERR, "Não foi possível gravar dados/senha.php (verifique as permissões da pasta dados/).\n");
    exit(1);
}
if (!NEXUS_PRIVADO_ATIVO) {
    echo "\nAVISO: os dados estão dentro da pasta pública do site (protegidos só pelo .htaccess).\n";
    echo "       Em produção, rode: php ferramentas/mover-dados.php\n";
}
echo "\nPronto. Conta \"" . $usuario . "\" gravada em dados/senha.php.\n";
echo "Para remover as contas antigas, apague dados/senha.php antes de rodar este script.\n";
echo "\n2FA: conta nova configura o autenticador no primeiro login (o painel guia o passo a passo).\n";
echo "     Se a conta já existia, o 2FA dela foi mantido (para apagá-lo: php ferramentas/redefinir-2fa.php USUARIO).\n";
