<?php
/*
 * EDIÇÃO DE DEMONSTRAÇÃO (PORTFÓLIO)
 * ------------------------------------------------------------------
 * Funções de apoio exclusivas da edição pública de demonstração
 * (NEXUS_EDICAO = "demo", ver inc/config.php). Em produção nada daqui
 * aparece nem tem efeito: todas as funções retornam sem fazer nada.
 *
 * Princípios da demonstração:
 *   1. O painel é público de propósito, para mostrar o resultado final.
 *   2. A conta usada é exclusiva da demonstração (dados/senha.demo.php).
 *   3. Nenhuma ação altera dados: tudo é validado pelo mesmo código de
 *      produção, mas a gravação é descartada (ver demo-api.php).
 *   4. Nenhum nome de arquivo, usuário ou credencial desta edição vale
 *      para o ambiente de produção.
 */

declare(strict_types=1);

/** Arquivo do painel nesta edição (usado em links e redirecionamentos). */
const NEXUS_DEMO_PAINEL = 'demo-painel.php';

/** Credenciais PÚBLICAS da conta de demonstração (exibidas na tela de login). */
const NEXUS_DEMO_USUARIO = 'demo';
const NEXUS_DEMO_SENHA   = 'Demo@Nexus-Portfolio-2026';

/**
 * A aplicação está na edição de demonstração?
 */
function nexus_demo(): bool
{
    return defined('NEXUS_EDICAO') && NEXUS_EDICAO === 'demo';
}

/**
 * Imprime o botão flutuante "Ver painel" nas páginas públicas.
 * Chamar imediatamente antes de </body>. Não imprime nada em produção.
 */
function nexus_demo_botao(): void
{
    if (!nexus_demo()) {
        return;
    }
    echo '<a class="demo-fab" href="' . h(NEXUS_DEMO_PAINEL) . '" aria-label="Abrir o painel administrativo de demonstração">'
        . '<span class="demo-fab__ponto" aria-hidden="true"></span>'
        . '<span class="demo-fab__texto">Ver painel <small>demo</small></span>'
        . '</a>' . "\n";
}

/**
 * Imprime o aviso com as credenciais públicas na tela de login do painel.
 * Na etapa "login" mostra usuário, senha e código; na etapa "2fa", só o código.
 * Não imprime nada em produção.
 */
function nexus_demo_aviso_login(string $etapa = 'login'): void
{
    if (!nexus_demo()) {
        return;
    }
    $itens = $etapa === 'login'
        ? ['Usuário' => NEXUS_DEMO_USUARIO, 'Senha' => NEXUS_DEMO_SENHA, 'Código 2FA' => NEXUS_DEMO_CODIGO]
        : ['Código 2FA' => NEXUS_DEMO_CODIGO];

    echo '<div class="demo-aviso" role="note">' . "\n"
        . '  <strong>Painel de demonstração</strong>' . "\n"
        . '  <p>Ambiente público e isolado: nada que for feito aqui é publicado.</p>' . "\n"
        . '  <dl>' . "\n";
    foreach ($itens as $rotulo => $valor) {
        echo '    <dt>' . h($rotulo) . '</dt><dd><code>' . h($valor) . '</code></dd>' . "\n";
    }
    echo '  </dl>' . "\n"
        . '</div>' . "\n";
}
