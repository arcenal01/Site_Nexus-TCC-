<?php
/*
 * PAINEL ADMINISTRATIVO DO NEXUS (edição de demonstração)
 * ------------------------------------------------------------------
 * Interface de edição de conteúdo protegida por login em duas etapas
 * (usuário/senha + código TOTP). Nenhuma credencial existe neste
 * arquivo: a conferência acontece no servidor (inc/auth.php), contra
 * hashes Argon2id/bcrypt guardados fora do alcance da web.
 *
 * Em produção, o que é salvo aqui vai para dados/conteudo.json e vale
 * para todos os visitantes na hora. Na demonstração, as ações são
 * validadas e descartadas (ver demo-api.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/dados.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/qr.php';

nexus_cabecalhos_seguranca(true);
nexus_iniciar_sessao();

// Sem nenhuma conta cadastrada (NX-16): o painel NÃO redireciona para instalador nenhum e não
// permite criar a conta pela web. A primeira conta é criada apenas no servidor, por linha de
// comando: php ferramentas/criar-conta.php
if (!nexus_instalado()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    header('Retry-After: 3600');
    echo 'Painel não configurado.';
    exit;
}

$erroLogin = '';
$erro2fa   = '';

// ---------- Sair ----------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['acao'] ?? '') === 'sair') {
    if (nexus_csrf_valido($_POST['csrf'] ?? null)) {
        nexus_logout();
    }
    header('Location: ' . basename(__FILE__));
    exit;
}

// ---------- Entrar ----------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['acao'] ?? '') === 'entrar') {
    $restante = nexus_bloqueio_restante((string)($_POST['usuario'] ?? ''));
    if (!nexus_csrf_valido($_POST['csrf'] ?? null)) {
        $erroLogin = 'Sessão expirada. Tente novamente.';
    } elseif ($restante > 0) {
        $erroLogin = 'Muitas tentativas. Tente novamente em ' . nexus_formatar_tempo($restante) . '.';
    } elseif (nexus_login((string)($_POST['usuario'] ?? ''), (string)($_POST['senha'] ?? ''))) {
        header('Location: ' . basename(__FILE__));
        exit;
    } else {
        $restante  = nexus_bloqueio_restante((string)($_POST['usuario'] ?? ''));
        $erroLogin = $restante > 0
            ? 'Muitas tentativas. Acesso bloqueado por ' . nexus_formatar_tempo($restante) . '.'
            : 'Usuário ou senha incorretos.';
    }
}

// ---------- Segundo fator: conferir o código ----------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['acao'] ?? '') === 'codigo2fa') {
    if (!nexus_csrf_valido($_POST['csrf'] ?? null)) {
        $erro2fa = 'Sessão expirada. Tente novamente.';
    } elseif (nexus_2fa_pendente() === null) {
        $erro2fa = 'Tempo esgotado. Entre novamente com usuário e senha.';
    } else {
        $res = nexus_2fa_confirmar((string)($_POST['codigo'] ?? ''));
        if ($res === 'ok') {
            header('Location: ' . basename(__FILE__));
            exit;
        }
        $erro2fa = match ($res) {
            'bloqueado' => 'Muitas tentativas. Aguarde para tentar de novo.',
            'expirado'  => 'Muitos códigos incorretos ou tempo esgotado. Entre novamente com usuário e senha.',
            'erro'      => 'Não foi possível salvar a configuração. Avise o responsável pelo site.',
            default     => 'Código incorreto ou já utilizado. Digite o código atual do aplicativo.',
        };
        // Dica extra (só na primeira configuração): explica POR QUE o código não bateu.
        $dica = $_SESSION['nexus_2fa']['dica'] ?? null;
        if ($res === 'invalido' && $dica === 'relogio') {
            $dv = (int)($_SESSION['nexus_2fa']['dica_desvio'] ?? 0);
            $erro2fa = 'O código está certo, mas o RELÓGIO DO SERVIDOR está ' . ($dv > 0 ? 'atrasado' : 'adiantado')
                . ' cerca de ' . nexus_formatar_tempo(abs($dv)) . ' em relação ao celular. Acerte a data/hora/fuso do computador ou servidor (hora atual dele: '
                . gmdate('H:i:s') . ' UTC) e entre de novo.';
        } elseif ($res === 'invalido' && $dica === 'usado') {
            $erro2fa = 'Esse código já foi usado. Aguarde o app mostrar o próximo código (muda a cada 30 s) e digite de novo.';
        }
    }
}

// ---------- Segundo fator: desistir (volta para usuário e senha) ----------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['acao'] ?? '') === 'cancelar_2fa') {
    if (nexus_csrf_valido($_POST['csrf'] ?? null)) {
        nexus_2fa_cancelar();
    }
    header('Location: ' . basename(__FILE__));
    exit;
}

// ---------- Códigos de recuperação: usuário confirmou que guardou ----------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['acao'] ?? '') === 'codigos_ok') {
    if (nexus_logado() && nexus_csrf_valido($_POST['csrf'] ?? null)) {
        unset($_SESSION['nexus_codigos_novos']);
    }
    header('Location: ' . basename(__FILE__));
    exit;
}

$logado           = nexus_logado();
$csrf             = nexus_csrf();
$p2fa             = $logado ? null : nexus_2fa_pendente();   // etapa do código, se a senha já conferiu
$codigosNovos     = ($logado && !empty($_SESSION['nexus_codigos_novos']) && is_array($_SESSION['nexus_codigos_novos']))
    ? $_SESSION['nexus_codigos_novos'] : null;
$resumo2fa        = $logado ? nexus_2fa_resumo((string)($_SESSION['nexus_usuario'] ?? '')) : ['ativo' => false, 'restantes' => 0];
$bloqueioRestante = $logado ? 0 : nexus_bloqueio_restante($p2fa['usuario'] ?? null);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>Painel</title>
<link rel="stylesheet" href="assets/fonts/fonts.css">
<link rel="stylesheet" href="assets/css/painel.css">
</head>
<body>

<?php if (!$logado): ?>
<!-- ==================== TELA DE LOGIN ==================== -->
<?php if ($p2fa === null): ?>
<div id="loginScreen">
  <div class="login-box">
    <div class="logo-icon">N</div>
    <h1>Acesso restrito</h1>
    <p>Painel de edição de conteúdo do site Nexus.</p>
    <form method="post" autocomplete="off" id="loginForm">
      <input type="hidden" name="acao" value="entrar">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="text" name="usuario" placeholder="Usuário" autocomplete="username" autofocus required<?= $bloqueioRestante > 0 ? ' disabled' : '' ?>>
      <div class="campo-senha">
        <input type="password" name="senha" id="loginSenhaInput" placeholder="Senha de acesso" autocomplete="current-password" required<?= $bloqueioRestante > 0 ? ' disabled' : '' ?>>
        <button type="button" class="btn-olho" data-alvo="loginSenhaInput" aria-label="Mostrar senha">
          <svg class="olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg class="olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.4 20.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
      </div>
      <button type="submit" id="loginBtn"<?= $bloqueioRestante > 0 ? ' disabled' : '' ?>>Entrar</button>
      <div id="loginError"><?= h($erroLogin) ?></div>
    </form>
    <?php nexus_demo_aviso_login('login'); ?>
  </div>
</div>
<?php else: ?>
<!-- ==================== VERIFICAÇÃO EM DUAS ETAPAS ==================== -->
<div id="loginScreen">
  <div class="login-box"<?= ($p2fa['modo'] ?? '') === 'configurar' ? ' style="max-width:440px;"' : '' ?>>
    <div class="logo-icon">N</div>
<?php if (($p2fa['modo'] ?? '') === 'configurar'): ?>
    <h1>Ativar a verificação em duas etapas</h1>
    <p>Sua conta ainda não tem o segundo fator. Configure agora, leva 1 minuto.</p>
    <ol class="passos2fa">
      <li>Instale um aplicativo autenticador no celular (Google Authenticator, Microsoft Authenticator, Authy ou 2FAS).</li>
      <li>No aplicativo, toque em <b>+</b> e escolha <b>&ldquo;Ler código QR&rdquo;</b> (ou &ldquo;Escanear&rdquo;) e aponte a câmera para o QR code abaixo.</li>
      <li>Digite aqui o código de 6 dígitos que o aplicativo mostrar.</li>
    </ol>
<?php
$uri2fa = nexus_totp_uri((string)$p2fa['usuario'], (string)($p2fa['segredo'] ?? ''));
try {
    $qr2fa = nexus_qr_svg($uri2fa, 'QR code para o aplicativo autenticador');
} catch (Throwable $e) {
    $qr2fa = '';   // sem QR: ainda dá para digitar a chave
}
?>
<?php if ($qr2fa !== ''): ?>
    <div class="qr2fa"><?= $qr2fa ?></div>
    <p class="ou2fa">Sem câmera ou no próprio celular? Adicione digitando esta chave:</p>
<?php else: ?>
    <p class="ou2fa">No aplicativo, adicione uma conta digitando esta chave:</p>
<?php endif; ?>
    <div class="segredo2fa"><?= h(nexus_totp_segredo_legivel((string)($p2fa['segredo'] ?? ''))) ?></div>
    <p style="margin:10px 0 18px;font-size:.78rem;"><a href="<?= h($uri2fa) ?>">Abrir no aplicativo (celular)</a> &middot; tipo da chave: baseada em tempo</p>
<?php else: ?>
    <h1>Verificação em duas etapas</h1>
    <p>Digite o código de 6 dígitos do aplicativo autenticador. Perdeu o celular? Use um código de recuperação.</p>
<?php endif; ?>
    <form method="post" autocomplete="off" id="loginForm">
      <input type="hidden" name="acao" value="codigo2fa">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="text" name="codigo" placeholder="<?= ($p2fa['modo'] ?? '') === 'configurar' ? 'Código de 6 dígitos' : 'Código' ?>" inputmode="<?= ($p2fa['modo'] ?? '') === 'configurar' ? 'numeric' : 'text' ?>" autocomplete="one-time-code" maxlength="20" autofocus required<?= $bloqueioRestante > 0 ? ' disabled' : '' ?>>
      <button type="submit" id="loginBtn"<?= $bloqueioRestante > 0 ? ' disabled' : '' ?>>Confirmar</button>
      <div id="loginError"><?= h($erro2fa) ?></div>
    </form>
    <form method="post">
      <input type="hidden" name="acao" value="cancelar_2fa">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <button type="submit" class="link-voltar">Voltar</button>
    </form>
    <?php nexus_demo_aviso_login('2fa'); ?>
  </div>
</div>
<?php endif; ?>
<?php if ($bloqueioRestante > 0): ?>
<script nonce="<?= h(nexus_nonce()) ?>">
// Contagem regressiva do bloqueio por tentativas erradas: trava os campos
// e o botão enquanto durar, e libera sozinho quando o tempo acaba.
(function(){
  var restante = <?= (int)$bloqueioRestante ?>;
  var btn = document.getElementById('loginBtn');
  /**
   * Atualiza o texto do botão com o tempo restante do bloqueio (mm:ss) e recarrega a página quando chega a zero.
   */
  var tick = function(){
    if (restante <= 0){ location.reload(); return; }
    var m = Math.floor(restante / 60), s = restante % 60;
    btn.textContent = 'Bloqueado (' + m + ':' + (s < 10 ? '0' : '') + s + ')';
    restante--;
  };
  tick();
  setInterval(tick, 1000);
})();
</script>
<?php endif; ?>

<?php elseif ($codigosNovos !== null): ?>
<!-- ==================== CÓDIGOS DE RECUPERAÇÃO (aparecem uma vez) ==================== -->
<div id="loginScreen">
  <div class="login-box" style="max-width:440px;">
    <div class="logo-icon">N</div>
    <h1>Guarde seus códigos de recuperação</h1>
    <p>Cada código funciona <b>uma única vez</b> e substitui o aplicativo se você perder o celular. Eles <b>não serão mostrados de novo</b>: guarde num lugar seguro (gerenciador de senhas ou papel).</p>
    <div class="codigos2fa" id="codigos2fa"><?php foreach ($codigosNovos as $c): ?><span><?= h($c) ?></span><?php endforeach; ?></div>
    <button type="button" class="btn-copiar" id="copiarCodigos">Copiar códigos</button>
    <form method="post">
      <input type="hidden" name="acao" value="codigos_ok">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <label class="aviso-guardei"><input type="checkbox" id="guardei"> Já guardei os códigos em local seguro</label>
      <button type="submit" id="continuarBtn" disabled>Continuar para o painel</button>
    </form>
  </div>
</div>
<script nonce="<?= h(nexus_nonce()) ?>">
(function(){
  var cb = document.getElementById('guardei'), btn = document.getElementById('continuarBtn');
  cb.addEventListener('change', function(){ btn.disabled = !cb.checked; });
  var copiar = document.getElementById('copiarCodigos');
  copiar.addEventListener('click', function(){
    var texto = Array.prototype.map.call(document.querySelectorAll('#codigos2fa span'), function(s){ return s.textContent; }).join('\n');
    if (navigator.clipboard && navigator.clipboard.writeText){
      navigator.clipboard.writeText(texto).then(function(){ copiar.textContent = 'Copiado ✓'; },
        function(){ copiar.textContent = 'Não foi possível copiar: selecione os códigos'; });
    } else {
      copiar.textContent = 'Selecione os códigos e copie manualmente';
    }
  });
})();
</script>

<?php else: ?>
<!-- ==================== DASHBOARD ==================== -->
<div id="dashboard">
  <header class="topbar">
    <h1>Painel de conteúdo · Nexus</h1>
<?php if (nexus_demo()): ?>
    <span class="demo-selo" title="As alterações são validadas, mas não publicadas">Demonstração · nada é publicado</span>
<?php endif; ?>
    <a class="btn btn-ghost" href="index.php" target="_blank" rel="noopener">Ver o site</a>
    <button class="btn btn-danger" id="resetBtn" type="button">Restaurar original</button>
    <form method="post">
      <input type="hidden" name="acao" value="sair">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <button class="btn btn-ghost" type="submit">Sair</button>
    </form>
  </header>
  <div id="statusMsg"></div>

  <nav class="tabs">
    <button class="tab-btn active" data-tab="inicio">Início</button>
    <button class="tab-btn" data-tab="saibamais">Saiba Mais</button>
    <button class="tab-btn" data-tab="tccs">TCCs</button>
    <button class="tab-btn" data-tab="geral">Geral</button>
    <button class="tab-btn" data-tab="senha">Senha e 2FA</button>
    <button class="tab-btn" data-tab="ajuda">Como funciona</button>
  </nav>

  <main>
    <!-- ---------- TCCs ---------- -->
    <section class="tab-panel" id="tab-tccs">
      <div class="card-box">
        <h2>Projetos de TCC</h2>
        <p class="hint">Edite os projetos que já existem no site ou adicione novos. Tudo o que você salvar aqui aparece no site na hora, para todos os visitantes.</p>
        <button class="btn btn-primary" id="addTccBtn" type="button">+ Adicionar novo TCC</button>
        <div class="tcc-list" id="tccList"></div>
      </div>
    </section>

    <!-- ---------- Início ---------- -->
    <section class="tab-panel active" id="tab-inicio">
      <div class="card-box">
        <h2>Página inicial (index.php)</h2>
        <div class="field"><label>Parágrafo do topo (hero)</label><textarea data-key="idx-hero-paragrafo"></textarea></div>
        <div class="row2">
          <div class="field"><label>Estatística 1 — número</label><input type="text" data-key="idx-stat1-num"></div>
          <div class="field"><label>Estatística 1 — legenda</label><input type="text" data-key="idx-stat1-label"></div>
        </div>
        <div class="row2">
          <div class="field"><label>Estatística 2 — número</label><input type="text" data-key="idx-stat2-num"></div>
          <div class="field"><label>Estatística 2 — legenda</label><input type="text" data-key="idx-stat2-label"></div>
        </div>
        <div class="row2">
          <div class="field"><label>Estatística 3 — número</label><input type="text" data-key="idx-stat3-num"></div>
          <div class="field"><label>Estatística 3 — legenda</label><input type="text" data-key="idx-stat3-label"></div>
        </div>
        <div class="row2">
          <div class="field"><label>Estatística 4 — número</label><input type="text" data-key="idx-stat4-num"></div>
          <div class="field"><label>Estatística 4 — legenda</label><input type="text" data-key="idx-stat4-label"></div>
        </div>
        <div class="field"><label>Título da seção "Sobre"</label><input type="text" data-key="idx-sobre-titulo"></div>
        <div class="field"><label>Sobre — parágrafo 1</label><textarea data-key="idx-sobre-p1"></textarea></div>
        <div class="field"><label>Sobre — parágrafo 2</label><textarea data-key="idx-sobre-p2"></textarea></div>
      </div>
    </section>

    <!-- ---------- Saiba Mais ---------- -->
    <section class="tab-panel" id="tab-saibamais">
      <div class="card-box">
        <h2>Página Saiba Mais</h2>
        <div class="field"><label>Título do topo</label><input type="text" data-key="sm-hero-titulo"></div>
        <div class="field"><label>Parágrafo do topo</label><textarea data-key="sm-hero-paragrafo"></textarea></div>
        <div class="row2">
          <div class="field"><label>Estatística 1 — número</label><input type="text" data-key="sm-stat1-num"></div>
          <div class="field"><label>Estatística 1 — legenda</label><input type="text" data-key="sm-stat1-label"></div>
        </div>
        <div class="row2">
          <div class="field"><label>Estatística 2 — número</label><input type="text" data-key="sm-stat2-num"></div>
          <div class="field"><label>Estatística 2 — legenda</label><input type="text" data-key="sm-stat2-label"></div>
        </div>
        <div class="row2">
          <div class="field"><label>Estatística 3 — número</label><input type="text" data-key="sm-stat3-num"></div>
          <div class="field"><label>Estatística 3 — legenda</label><input type="text" data-key="sm-stat3-label"></div>
        </div>
        <div class="field"><label>Propósito</label><textarea data-key="pmvv-proposito"></textarea></div>
        <div class="field"><label>Missão</label><textarea data-key="pmvv-missao"></textarea></div>
        <div class="field"><label>Visão</label><textarea data-key="pmvv-visao"></textarea></div>
      </div>
    </section>

    <!-- ---------- Geral ---------- -->
    <section class="tab-panel" id="tab-geral">
      <div class="card-box">
        <h2>Contato (aparece no rodapé de todas as páginas)</h2>
        <div class="field"><label>Linha de e-mail</label><input type="text" data-key="contato-email"></div>
        <div class="field"><label>Linha de telefone</label><input type="text" data-key="contato-telefone"></div>
      </div>
      <div class="card-box">
        <h2>Cronômetro do evento (página inicial)</h2>
        <div class="field"><label>Data e hora do evento</label><input type="datetime-local" id="eventDateInput"></div>
        <p class="hint">É a data usada na contagem regressiva "Dias / Horas / Minutos / Segundos" na página inicial.</p>
      </div>
    </section>

    <!-- ---------- Senha ---------- -->
    <section class="tab-panel" id="tab-senha">
      <div class="card-box">
        <h2>Trocar usuário e senha do painel</h2>
        <p class="hint">Usuário e senha ficam guardados no servidor de forma criptografada (hash Argon2id ou bcrypt). Nem quem abrir o código-fonte da página consegue vê-los. Deixe "Novo usuário" em branco para manter o usuário atual. Dica: uma frase longa (16+ caracteres, ex.: quatro palavras sem relação) é mais forte e mais fácil de lembrar do que símbolos misturados. Não repita senhas de outros sites.</p>
        <div class="field"><label>Senha atual</label>
          <div class="campo-senha">
            <input type="password" id="senhaAtual" autocomplete="current-password">
            <button type="button" class="btn-olho" data-alvo="senhaAtual" aria-label="Mostrar senha">
              <svg class="olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.4 20.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
        <div class="row2">
          <div class="field"><label>Novo usuário (opcional)</label><input type="text" id="usuarioNovo" autocomplete="username"></div>
          <div class="field"><label>Nova senha</label>
            <div class="campo-senha">
              <input type="password" id="senhaNova" autocomplete="new-password">
              <button type="button" class="btn-olho" data-alvo="senhaNova" aria-label="Mostrar senha">
                <svg class="olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.4 20.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
        </div>
        <div class="medidor"><div class="medidor-barra" id="medidorBarra"></div></div>
        <div class="medidor-legenda" id="medidorLegenda">Digite a nova senha para ver a força dela.</div>
        <div class="field"><label>Repita a nova senha</label>
          <div class="campo-senha">
            <input type="password" id="senhaNova2" autocomplete="new-password">
            <button type="button" class="btn-olho" data-alvo="senhaNova2" aria-label="Mostrar senha">
              <svg class="olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.4 20.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
        <button class="btn btn-primary" id="trocarSenhaBtn" type="button">Salvar</button>
        <div class="upload-msg" id="senhaMsg"></div>
      </div>

      <div class="card-box" style="margin-top:20px;">
        <h2>Verificação em duas etapas (2FA)</h2>
        <p class="hint">Esta conta está protegida por senha <b>e</b> por um código do aplicativo autenticador. Códigos de recuperação restantes:
          <b id="dfaRestantes"<?= $resumo2fa['restantes'] <= 2 ? ' style="color:var(--danger)"' : '' ?>><?= (int)$resumo2fa['restantes'] ?></b>.
          Para qualquer uma das ações abaixo, informe a senha atual e um código válido (do aplicativo ou de recuperação).</p>
        <div class="field"><label>Senha atual</label>
          <div class="campo-senha">
            <input type="password" id="dfaSenha" autocomplete="current-password">
            <button type="button" class="btn-olho" data-alvo="dfaSenha" aria-label="Mostrar senha">
              <svg class="olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.4 20.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
        <div class="field"><label>Código do autenticador (ou de recuperação)</label><input type="text" id="dfaCodigo" autocomplete="one-time-code" maxlength="20"></div>
        <button class="btn btn-primary" id="dfaRegerarBtn" type="button">Gerar novos códigos de recuperação</button>
        <button class="btn btn-danger" id="dfaTrocarBtn" type="button">Trocar o aparelho do autenticador</button>
        <div class="upload-msg" id="dfaMsg"></div>
        <div class="codigos2fa" id="dfaCodigos" style="margin-top:14px;"></div>
      </div>
    </section>

    <!-- ---------- Ajuda ---------- -->
    <section class="tab-panel" id="tab-ajuda">
      <div class="card-box">
        <h2>Como funciona agora</h2>
        <p class="hint" style="font-size:.85rem;">
          1. Edite o que quiser nas abas acima. Cada alteração é gravada no servidor automaticamente, poucos segundos depois de você parar de digitar.<br><br>
          2. Assim que a mensagem <b>"Publicado no site ✓"</b> aparecer, o conteúdo já está no ar para todos os visitantes. Não existe mais o passo de baixar arquivo e enviar para a hospedagem.<br><br>
          3. Para conferir, clique em <b>"Ver o site"</b> no topo.<br><br>
          4. As fotos dos projetos são enviadas para a pasta <code>uploads/tcc</code> do servidor e recebem um nome aleatório, por segurança.<br><br>
          5. <b>"Restaurar original"</b> apaga todas as alterações e devolve o site ao conteúdo de fábrica. Isso vale para todos os visitantes na hora — por isso pede que você digite a palavra RESTAURAR. A sessão expira com 2 h sem uso (ou 8 h depois do login) e cai quando a senha da conta é trocada em outro lugar. O servidor guarda cópias de segurança do conteúdo e registra quem alterou o quê. Se duas pessoas editarem ao mesmo tempo, a segunda gravação é recusada e a página recarrega com a versão atual (ninguém apaga o trabalho do outro sem querer).<br><br>
          6. O acesso ao painel exige <b>senha + código do aplicativo autenticador</b>. Se perder o celular, use um código de recuperação (cada um vale uma vez) e, logo depois, troque o aparelho na aba <b>Senha e 2FA</b>.
        </p>
      </div>
    </section>
  </main>
</div>

<!-- ==================== MODAL DE EDIÇÃO DE TCC ==================== -->
<div id="tccModal">
  <div class="modal-box">
    <h2 id="tccModalTitle">Adicionar TCC</h2>
    <div class="field"><label>Título do projeto</label><input type="text" id="f_title"></div>
    <div class="row2">
      <div class="field">
        <label>Categoria (usada no filtro)</label>
        <select id="f_category">
          <option value="web">Web</option>
          <option value="redes">Redes</option>
          <option value="software">Software</option>
        </select>
      </div>
      <div class="field"><label>Categoria exibida no card</label><input type="text" id="f_categoryLabel" placeholder="Ex: Plataforma Web"></div>
    </div>
    <div class="field"><label>Descrição</label><textarea id="f_description"></textarea></div>
    <div class="field"><label>Para que serve</label><textarea id="f_purpose"></textarea></div>

    <div class="field">
      <label>Foto de capa do projeto</label>
      <input type="file" id="f_image" accept="image/png,image/jpeg,image/gif,image/webp">
      <img id="f_image_preview" class="img-preview">
      <div class="upload-msg" id="f_image_msg"></div>
    </div>
    <div class="field">
      <label>Logo do projeto (opcional)</label>
      <input type="file" id="f_logo" accept="image/png,image/jpeg,image/gif,image/webp">
      <img id="f_logo_preview" class="img-preview">
      <div class="upload-msg" id="f_logo_msg"></div>
    </div>

    <div class="field">
      <label>Equipe</label>
      <div id="teamRows"></div>
      <button type="button" class="small-btn" id="addTeamRowBtn">+ Adicionar integrante</button>
    </div>

    <div class="field"><label>Tecnologias (separadas por vírgula)</label><input type="text" id="f_tech" placeholder="Ex: React, Node.js, MongoDB"></div>

    <div class="modal-actions">
      <button class="btn btn-ghost" id="tccCancelBtn" type="button">Cancelar</button>
      <button class="btn btn-primary" id="tccSaveBtn" type="button">Salvar e publicar</button>
    </div>
  </div>
</div>

<?php
/*
 * "content" e "tccs.edits" são mapas (chave => valor), mas quando estão
 * vazios um array PHP comum vira "[]" no JSON, e não "{}". O JavaScript
 * então trata como lista, e qualquer edição guardada nela some sem erro
 * na hora de virar JSON de novo (bug real que já aconteceu: painel dizia
 * "Publicado no site" mas o conteúdo nunca mudava). Por isso forçamos
 * objeto aqui, mesmo vazio.
 */
// A versão é lida ANTES do conteúdo: se alguém salvar entre as duas leituras, a próxima gravação
// daqui é recusada (lado seguro) em vez de sobrescrever.
$revConteudo = nexus_revisao_conteudo();
$nexusAlteracoesParaJs = nexus_alteracoes();
$nexusAlteracoesParaJs['content'] = (object)$nexusAlteracoesParaJs['content'];
$nexusAlteracoesParaJs['tccs']['edits'] = (object)$nexusAlteracoesParaJs['tccs']['edits'];
?>
<script nonce="<?= h(nexus_nonce()) ?>">
/* Dados entregues pelo servidor (conteúdo original + alterações já publicadas) */
window.NEXUS_DEFAULTS = <?= json_encode(
    ['content' => nexus_padroes()['content'], 'eventDate' => nexus_padroes()['eventDate'], 'tccs' => nexus_padroes()['tccs']],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;
window.NEXUS_DATA = <?= json_encode(
    $nexusAlteracoesParaJs,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;
window.NEXUS_CSRF = <?= json_encode($csrf) ?>;
window.NEXUS_USUARIO = <?= json_encode((string)($_SESSION['nexus_usuario'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;   // para o medidor de senha
window.NEXUS_REV  = <?= json_encode($revConteudo) ?>;   // versão do conteúdo que esta tela carregou
</script>
<script nonce="<?= h(nexus_nonce()) ?>">
(function(){
  "use strict";

  /*
   * LÓGICA DO PAINEL (executa só com o usuário autenticado)
   * ----------------------------------------------------------------
   * Estado: 'dados' guarda somente as ALTERAÇÕES sobre os padrões de fábrica
   * (window.NEXUS_DEFAULTS, de inc/padroes.php). O que está em window.NEXUS_DATA
   * é o que já foi publicado. O conteúdo em vigor = padrão + alterações.
   *
   * Gravação: toda mudança chama salvar(), que envia o JSON inteiro para
   * demo-api.php junto com a versão (window.NEXUS_REV) que a tela carregou.
   * Se outra pessoa gravou antes, o servidor recusa (conflito) e a página
   * recarrega, em vez de sobrescrever o trabalho dela.
   *
   * Seções, em ordem: salvar no servidor, abas, textos simples, lista e popup
   * de TCCs, envio de imagens, restaurar original, medidor de senha, troca
   * de usuário/senha e ações do 2FA.
   */

  var defaults = window.NEXUS_DEFAULTS;
  var API = "demo-api.php";

  /**
   * Estrutura vazia do conteúdo salvo: nenhuma alteração sobre os padrões de fábrica.
   * @returns {{content: Object, eventDate: ?string, tccs: {edits: Object, deleted: string[], extra: Object[]}}}
   */
  function emptyData(){
    return {content:{}, eventDate:null, tccs:{edits:{}, deleted:[], extra:[]}};
  }

  // Um mapa vazio (content / tccs.edits) pode chegar do servidor como "[]"
  // em vez de "{}" (o PHP não distingue array vazio de objeto vazio). Se
  // isso não for corrigido aqui, gravar uma chave nesse array e depois
  // rodar JSON.stringify descarta essa chave silenciosamente — é assim que
  // uma edição "sumia" mesmo com o painel avisando que salvou.
  function comoObjeto(v){
    return (v && typeof v === "object" && !Array.isArray(v)) ? v : {};
  }

  // Estado atual = exatamente o que está publicado no servidor
  var dados = window.NEXUS_DATA || emptyData();
  dados.content = comoObjeto(dados.content);
  dados.tccs = dados.tccs || {edits:{}, deleted:[], extra:[]};
  dados.tccs.edits = comoObjeto(dados.tccs.edits);
  dados.tccs.deleted = dados.tccs.deleted || [];
  dados.tccs.extra = dados.tccs.extra || [];

  var statusTimer = null;
  /**
   * Mostra uma mensagem de status no topo do painel e a apaga sozinha após 4 segundos.
   * @param {string} msg Texto exibido.
   * @param {boolean} [isError] Se verdadeiro, usa a cor de erro.
   */
  function showStatus(msg, isError){
    var el = document.getElementById("statusMsg");
    el.textContent = msg;
    el.style.color = isError ? "var(--danger)" : "var(--ok)";
    if (statusTimer) clearTimeout(statusTimer);
    statusTimer = setTimeout(function(){ el.textContent = ""; }, 4000);
  }

  /**
   * Envia uma ação (POST) para demo-api.php, sempre com o token CSRF da sessão.
   * @param {Object|FormData} campos Campos do formulário; use FormData quando houver arquivo.
   * @param {boolean} [comArquivo] Indica que 'campos' já é um FormData.
   * @returns {Promise<Object>} Resposta JSON da API (ou um objeto de erro genérico se vier algo inesperado).
   */
  function pedir(campos, comArquivo){
    var body;
    if (comArquivo){
      body = campos;
      body.append("csrf", window.NEXUS_CSRF);
    } else {
      body = new URLSearchParams();
      Object.keys(campos).forEach(function(k){ body.append(k, campos[k]); });
      body.append("csrf", window.NEXUS_CSRF);
    }
    return fetch(API, {method:"POST", body:body, credentials:"same-origin"})
      .then(function(r){ return r.json().catch(function(){ return {ok:false, mensagem:"Resposta inesperada do servidor."}; }); });
  }

  // ---------------- SALVAR NO SERVIDOR (tempo real) ----------------
  var saveTimer = null;
  var salvando = false;
  var pendente = false;

  var travado = false;   // true depois de um conflito: nada mais é enviado até recarregar
  /**
   * Trata o conflito de versão: outra pessoa salvou antes. Bloqueia novos envios e recarrega a página para mostrar o conteúdo atual.
   * @param {string} [msg] Mensagem vinda do servidor.
   */
  function conflitoRecarregar(msg){
    travado = true;
    window.alert(msg || "O conteúdo foi alterado por outra pessoa. A página será recarregada.");
    location.reload();
  }

  /**
   * Envia o conteúdo ao servidor. Se já houver um envio em andamento, marca como pendente e reenvia ao terminar, para nunca enviar duas gravações ao mesmo tempo.
   */
  function enviarAgora(){
    if (travado) return;
    if (salvando){ pendente = true; return; }
    salvando = true;
    showStatus("Salvando…");
    pedir({acao:"salvar", dados: JSON.stringify(dados), rev: window.NEXUS_REV}).then(function(res){
      salvando = false;
      if (res && res.ok){
        window.NEXUS_REV = res.rev;
        showStatus(res.mensagem || "Publicado no site ✓");
      } else if (res && res.erro === "conflito"){
        conflitoRecarregar(res.mensagem);
        return;
      } else if (res && res.erro === "sessao"){
        showStatus("Sessão expirada. Recarregando…", true);
        setTimeout(function(){ location.reload(); }, 1500);
        return;
      } else {
        showStatus((res && res.mensagem) || "Não foi possível salvar.", true);
      }
      if (pendente){ pendente = false; enviarAgora(); }
    }).catch(function(){
      salvando = false;
      showStatus("Sem conexão com o servidor. A alteração não foi publicada.", true);
    });
  }

  /**
   * Agenda o salvamento. Digitação usa debounce de 700 ms; ações pontuais (botões, data) usam envio imediato.
   * @param {boolean} [imediato] Se verdadeiro, envia sem esperar.
   */
  function salvar(imediato){
    if (saveTimer) clearTimeout(saveTimer);
    if (imediato){ enviarAgora(); return; }
    saveTimer = setTimeout(enviarAgora, 700);
  }

  // ---------------- TABS ----------------
  document.querySelectorAll(".tab-btn").forEach(function(btn){
    btn.addEventListener("click", function(){
      document.querySelectorAll(".tab-btn").forEach(function(b){ b.classList.remove("active"); });
      document.querySelectorAll(".tab-panel").forEach(function(p){ p.classList.remove("active"); });
      btn.classList.add("active");
      document.getElementById("tab-" + btn.getAttribute("data-tab")).classList.add("active");
    });
  });

  // ---------------- CAMPOS DE TEXTO SIMPLES ----------------
  /**
   * Texto em vigor de um campo: o valor editado, se existir e não for vazio; senão o padrão de fábrica.
   * @param {string} key Chave do campo (ver inc/padroes.php).
   * @returns {string}
   */
  function effectiveContent(key){
    if (Object.prototype.hasOwnProperty.call(dados.content, key) && dados.content[key]) return dados.content[key];
    return defaults.content[key] || "";
  }

  /**
   * Preenche os campos de texto e a data do evento com os valores em vigor e liga os ouvintes (uma única vez) que gravam cada digitação em 'dados'.
   */
  function renderTextFields(){
    document.querySelectorAll("[data-key]").forEach(function(el){
      el.value = effectiveContent(el.getAttribute("data-key"));
      if (!el.dataset.bound){
        el.dataset.bound = "1";
        el.addEventListener("input", function(){
          dados.content[el.getAttribute("data-key")] = el.value;
          salvar();
        });
      }
    });

    var dt = document.getElementById("eventDateInput");
    var iso = dados.eventDate || defaults.eventDate;
    dt.value = iso ? String(iso).substring(0,16) : "";
    if (!dt.dataset.bound){
      dt.dataset.bound = "1";
      dt.addEventListener("change", function(){
        dados.eventDate = dt.value ? (dt.value + ":00") : null;
        salvar(true);
      });
    }
  }

  // ---------------- LISTA DE TCCs ----------------
  /**
   * Monta a lista final de TCCs: os padrões com as edições aplicadas por cima, marcados como ocultos quando excluídos, mais os projetos criados no painel (extras).
   * @returns {Object[]}
   */
  function effectiveTccList(){
    var list = defaults.tccs.map(function(def){
      var base = JSON.parse(JSON.stringify(def));
      var override = dados.tccs.edits[def.id];
      if (override) base = Object.assign(base, JSON.parse(JSON.stringify(override)));
      base.id = def.id;
      base.isExtra = false;
      base.isDeleted = dados.tccs.deleted.indexOf(def.id) !== -1;
      return base;
    });
    dados.tccs.extra.forEach(function(item){
      var it = JSON.parse(JSON.stringify(item));
      it.isExtra = true;
      it.isDeleted = false;
      list.push(it);
    });
    return list;
  }

  /**
   * Redesenha a lista de projetos da aba TCCs, com os botões Editar e Ocultar/Restaurar (ou Remover, no caso de projetos criados no painel). Usa textContent e createElement, nunca innerHTML com dados, para evitar XSS.
   */
  function renderTccList(){
    var wrap = document.getElementById("tccList");
    wrap.innerHTML = "";
    effectiveTccList().forEach(function(item){
      var box = document.createElement("div");
      box.className = "tcc-item" + (item.isDeleted ? " is-hidden" : "");

      var thumb = document.createElement("div");
      thumb.className = "thumb";
      if (item.image){
        thumb.style.backgroundImage = "url('" + encodeURI(item.image) + "')";
      } else {
        thumb.textContent = "sem foto";
      }
      box.appendChild(thumb);

      var ttl = document.createElement("div");
      ttl.className = "ttl";
      ttl.textContent = item.title || "(sem título)";
      if (item.isExtra){
        var b = document.createElement("span"); b.className="badge-extra"; b.textContent="novo"; ttl.appendChild(b);
      }
      if (item.isDeleted){
        var b2 = document.createElement("span"); b2.className="badge-oculto"; b2.textContent="oculto"; ttl.appendChild(b2);
      }
      box.appendChild(ttl);

      var cat = document.createElement("div");
      cat.className = "cat";
      cat.textContent = item.categoryLabel || item.category;
      box.appendChild(cat);

      var actions = document.createElement("div");
      actions.className = "actions";

      var editBtn = document.createElement("button");
      editBtn.className = "btn btn-ghost";
      editBtn.textContent = "Editar";
      editBtn.addEventListener("click", function(){ openTccModal(item); });
      actions.appendChild(editBtn);

      var hideBtn = document.createElement("button");
      hideBtn.className = "btn " + (item.isDeleted ? "btn-primary" : "btn-danger");
      hideBtn.textContent = item.isDeleted ? "Restaurar" : "Ocultar";
      hideBtn.addEventListener("click", function(){
        if (item.isExtra){
          if (!confirm("Remover definitivamente o projeto \"" + (item.title || "") + "\" do site?")) return;
          dados.tccs.extra = dados.tccs.extra.filter(function(x){ return x.id !== item.id; });
        } else {
          var idx = dados.tccs.deleted.indexOf(item.id);
          if (idx === -1) dados.tccs.deleted.push(item.id);
          else dados.tccs.deleted.splice(idx,1);
        }
        salvar(true);
        renderTccList();
      });
      actions.appendChild(hideBtn);

      box.appendChild(actions);
      wrap.appendChild(box);
    });
  }

  // ---------------- MODAL DE EDIÇÃO / CRIAÇÃO DE TCC ----------------
  var modal = document.getElementById("tccModal");
  var editingId = null;
  var editingIsExtra = false;
  var currentExtraImages = [];   // imagens adicionais do card (o painel não edita, só preserva)
  var currentImage = "";
  var currentLogo = "";

  /**
   * Abre o popup de edição ou de criação de projeto.
   * @param {?Object} item Projeto a editar, ou null para criar um novo.
   */
  function openTccModal(item){
    editingId = item ? item.id : null;
    editingIsExtra = item ? item.isExtra : true;
    document.getElementById("tccModalTitle").textContent = item ? "Editar TCC" : "Adicionar novo TCC";

    document.getElementById("f_title").value = item ? (item.title || "") : "";
    document.getElementById("f_category").value = item ? (item.category || "web") : "web";
    document.getElementById("f_categoryLabel").value = item ? (item.categoryLabel || "") : "";
    document.getElementById("f_description").value = item ? (item.description || "") : "";
    document.getElementById("f_purpose").value = item ? (item.purpose || "") : "";
    document.getElementById("f_tech").value = item && item.tech ? item.tech.join(", ") : "";
    document.getElementById("f_image").value = "";
    document.getElementById("f_logo").value = "";
    document.getElementById("f_image_msg").textContent = "";
    document.getElementById("f_logo_msg").textContent = "";

    currentImage = item ? (item.image || "") : "";
    currentExtraImages = item && Array.isArray(item.extraImages) ? item.extraImages.slice(0, 10) : [];
    currentLogo = item ? (item.logo || "") : "";
    updatePreview("f_image_preview", currentImage);
    updatePreview("f_logo_preview", currentLogo);

    var teamRows = document.getElementById("teamRows");
    teamRows.innerHTML = "";
    var team = (item && item.team && item.team.length) ? item.team : [{name:"",role:""}];
    team.forEach(addTeamRow);

    modal.style.display = "flex";
  }

  /**
   * Mostra (ou esconde) a miniatura da imagem enviada.
   * @param {string} imgId ID do elemento <img>.
   * @param {string} src Caminho da imagem; vazio esconde a miniatura.
   */
  function updatePreview(imgId, src){
    var img = document.getElementById(imgId);
    if (src){ img.src = src; img.style.display = "block"; }
    else { img.removeAttribute("src"); img.style.display = "none"; }
  }

  /**
   * Adiciona uma linha (nome e função) à lista de integrantes do popup.
   * @param {{name: string, role: string}} [member] Dados iniciais; vazio cria uma linha em branco.
   */
  function addTeamRow(member){
    member = member || {name:"", role:""};
    var row = document.createElement("div");
    row.className = "team-row";
    var i1 = document.createElement("input");
    i1.type = "text"; i1.placeholder = "Nome"; i1.className = "team-name"; i1.value = member.name || "";
    var i2 = document.createElement("input");
    i2.type = "text"; i2.placeholder = "Função"; i2.className = "team-role"; i2.value = member.role || "";
    var bt = document.createElement("button");
    bt.type = "button"; bt.textContent = "remover";
    bt.addEventListener("click", function(){ row.remove(); });
    row.appendChild(i1); row.appendChild(i2); row.appendChild(bt);
    document.getElementById("teamRows").appendChild(row);
  }

  document.getElementById("addTeamRowBtn").addEventListener("click", function(){ addTeamRow(); });
  document.getElementById("addTccBtn").addEventListener("click", function(){ openTccModal(null); });
  document.getElementById("tccCancelBtn").addEventListener("click", function(){ modal.style.display = "none"; });

  // Envia a imagem para o servidor e guarda só o caminho do arquivo
  function enviarImagem(input, msgId, aplicar){
    var file = input.files[0];
    if (!file) return;
    var msg = document.getElementById(msgId);
    if (file.size > 4 * 1024 * 1024){
      msg.textContent = "Imagem muito grande (máximo 4 MB).";
      msg.style.color = "var(--danger)";
      input.value = "";
      return;
    }
    msg.style.color = "var(--muted)";
    msg.textContent = "Enviando imagem…";
    var fd = new FormData();
    fd.append("acao", "upload");
    fd.append("arquivo", file);
    pedir(fd, true).then(function(res){
      if (res && res.ok && res.caminho){
        aplicar(res.caminho);
        msg.style.color = "var(--ok)";
        msg.textContent = "Imagem enviada ✓";
      } else {
        msg.style.color = "var(--danger)";
        msg.textContent = (res && res.mensagem) || "Falha no envio.";
      }
    }).catch(function(){
      msg.style.color = "var(--danger)";
      msg.textContent = "Falha no envio.";
    });
  }

  document.getElementById("f_image").addEventListener("change", function(){
    enviarImagem(this, "f_image_msg", function(caminho){
      currentImage = caminho;
      updatePreview("f_image_preview", caminho);
    });
  });
  document.getElementById("f_logo").addEventListener("change", function(){
    enviarImagem(this, "f_logo_msg", function(caminho){
      currentLogo = caminho;
      updatePreview("f_logo_preview", caminho);
    });
  });

  document.getElementById("tccSaveBtn").addEventListener("click", function(){
    var title = document.getElementById("f_title").value.trim();
    if (!title){ alert("Informe o título do projeto."); return; }

    var team = [];
    document.querySelectorAll("#teamRows .team-row").forEach(function(row){
      var name = row.querySelector(".team-name").value.trim();
      var role = row.querySelector(".team-role").value.trim();
      if (name || role) team.push({name:name, role:role});
    });

    var tech = document.getElementById("f_tech").value.split(",")
      .map(function(t){ return t.trim(); }).filter(Boolean);

    var item = {
      title: title,
      category: document.getElementById("f_category").value,
      categoryLabel: document.getElementById("f_categoryLabel").value.trim() || "Projeto",
      description: document.getElementById("f_description").value.trim(),
      purpose: document.getElementById("f_purpose").value.trim(),
      image: currentImage,
      logo: currentLogo,
      team: team,
      tech: tech
    };
    if (currentExtraImages.length) item.extraImages = currentExtraImages;

    if (editingId && !editingIsExtra){
      dados.tccs.edits[editingId] = item;
    } else if (editingId && editingIsExtra){
      item.id = editingId;
      dados.tccs.extra = dados.tccs.extra.map(function(x){ return x.id === editingId ? item : x; });
    } else {
      item.id = "extra-" + Date.now().toString(36) + Math.random().toString(36).slice(2,6);
      dados.tccs.extra.push(item);
    }

    salvar(true);
    renderTccList();
    modal.style.display = "none";
  });

  modal.addEventListener("click", function(e){ if (e.target === modal) modal.style.display = "none"; });

  // ---------------- RESTAURAR ORIGINAL ----------------
  document.getElementById("resetBtn").addEventListener("click", function(){
    var palavra = window.prompt("Isso apaga TODAS as alterações já publicadas e devolve o site ao conteúdo original, para todos os visitantes (fica um backup no servidor).\n\nPara confirmar, digite RESTAURAR em maiúsculas:");
    if (palavra === null) return;
    if (palavra.trim() !== "RESTAURAR"){
      showStatus("Palavra incorreta. Nada foi alterado.", true);
      return;
    }
    pedir({acao:"restaurar", confirmacao:"RESTAURAR", rev: window.NEXUS_REV}).then(function(res){
      if (res && res.ok){
        window.NEXUS_REV = res.rev;
        dados = emptyData();
        showStatus(res.mensagem || "Restaurado ✓");
        renderAll();
      } else if (res && res.erro === "conflito"){
        conflitoRecarregar(res.mensagem);
      } else {
        showStatus((res && res.mensagem) || "Não foi possível restaurar.", true);
      }
    });
  });

  // ---------------- MEDIDOR DE FORÇA DA SENHA ----------------
  // Espelha as regras do servidor (NX-11): 12 a 72 bytes; frase de 16+ caracteres OU
  // 3 tipos de caractere; não só números; sem senha/palavra óbvia; sem repetição
  // em sequência; sem o nome de usuário. O servidor ainda confere se a senha
  // apareceu em vazamentos conhecidos (isso o navegador não faz).
  var SENHA_MIN = 12, SENHA_MAX_BYTES = 72;
  var SENHAS_COMUNS = ["123456","1234567","12345678","123456789","1234567890","12345678910","123123123","111111111111","000000000000","123456789012","1234567890123","password","password1","password12","password123","passw0rd","p@ssw0rd","p@ssword","senha123","senha1234","senha12345","minhasenha","minhasenha123","mudar123","mudar1234","trocar123","qwerty","qwerty123","qwertyuiop","qwertyuiop123","1q2w3e4r5t","1qaz2wsx","abc123456","abcd1234","abcdefgh","abcdefghijkl","admin123","admin1234","admin12345","administrador","administrador123","nexus2026","nexus2025","nexus123","nexus1234","nexus12345","nexustecno","nexustecno123","nexustecno2026","tecnologia","tecnologia123","letmein","letmein123","iloveyou","iloveyou123","welcome1","welcome123","bemvindo","bemvindo123","brasil123","brasil2026","corinthians","flamengo123","87654321","11111111","00000000","12341234","123qweasd","qazwsxedc"];
  var PALAVRAS_FRACAS = ["senha","password","passwd","nexus","nexustecno","admin","administrador","painel","qwerty","qwertyuiop","abcdefgh","abcdefghij","letmein","iloveyou","welcome","bemvindo","brasil","escola","tecnologia","tecno","mudar","trocar","teste","secret","secreta","segredo"];
  var CORES_FORCA   = ["#ef4444","#ef4444","#f59e0b","#3b82f6","#22c55e"];
  var ROTULOS_FORCA = ["Muito fraca","Fraca","Razoável","Forte","Muito forte"];

  /**
   * Tamanho do texto em bytes UTF-8, que é o que o servidor limita (bcrypt aceita no máximo 72 bytes).
   * @param {string} texto
   * @returns {number}
   */
  function bytesUtf8(texto){
    return (window.TextEncoder ? new TextEncoder().encode(texto).length : unescape(encodeURIComponent(texto)).length);
  }

  /**
   * Avalia a senha com as mesmas regras do servidor (NX-11), só para dar retorno imediato ao usuário. A validação que vale é a do servidor.
   * @param {string} senha Senha digitada.
   * @param {string} usuario Nome de usuário, para impedir senhas que o contenham.
   * @returns {{score: number, motivos: string[], forte: boolean}} Nota de 0 a 4, motivos de rejeição e se a senha é aceitável.
   */
  function avaliarForcaSenha(senha, usuario){
    var motivos = [];
    var tamanho = Array.from(senha).length;     // conta caracteres (não unidades UTF-16), como o mb_strlen do servidor
    if (tamanho < SENHA_MIN) motivos.push("use pelo menos " + SENHA_MIN + " caracteres");
    if (bytesUtf8(senha) > SENHA_MAX_BYTES) motivos.push("no máximo " + SENHA_MAX_BYTES + " bytes (acentos e emojis contam mais de 1)");
    var classes = 0;
    if (/[a-z]/.test(senha)) classes++;
    if (/[A-Z]/.test(senha)) classes++;
    if (/[0-9]/.test(senha)) classes++;
    if (/[^A-Za-z0-9]/.test(senha)) classes++;
    if (tamanho < 16 && classes < 3) motivos.push("use uma frase com 16+ caracteres ou misture minúsculas, maiúsculas, números e símbolos");
    if (/^[0-9]+$/.test(senha)) motivos.push("não use só números");
    var minuscula = senha.toLowerCase();
    var soLetras = minuscula.replace(/[^a-z]+/g, "");
    var comum = SENHAS_COMUNS.indexOf(minuscula) !== -1 || (soLetras.length <= 14 && PALAVRAS_FRACAS.indexOf(soLetras) !== -1);
    if (comum) motivos.push("essa senha é comum ou baseada em uma palavra óbvia");
    if (/(.)\1{4,}/u.test(senha)) motivos.push("evite repetir o mesmo caractere seguidas vezes");
    usuario = (usuario || "").trim();
    if (usuario.length >= 3 && minuscula.indexOf(usuario.toLowerCase()) !== -1) motivos.push("não use o nome de usuário dentro da senha");

    var pontos = tamanho >= 20 ? 3 : tamanho >= 16 ? 2 : tamanho >= SENHA_MIN ? 1 : 0;
    if (classes >= 3) pontos++;
    if (comum) pontos = 0;
    return {score: Math.max(0, Math.min(4, pontos)), motivos: motivos, forte: motivos.length === 0};
  }

  // usuário que a conta vai ter: o "Novo usuário" digitado, ou o atual
  function usuarioDaConta(){
    var novo = document.getElementById("usuarioNovo").value.trim();
    return novo || window.NEXUS_USUARIO || "";
  }

  var campoSenhaNova = document.getElementById("senhaNova");
  var barraForca = document.getElementById("medidorBarra");
  var legendaForca = document.getElementById("medidorLegenda");
  /**
   * Atualiza a barra e o texto do medidor de força conforme o que foi digitado.
   */
  function atualizarMedidor(){
    var senha = campoSenhaNova.value;
    if (!senha){
      barraForca.style.width = "0%";
      legendaForca.textContent = "Digite a nova senha para ver a força dela.";
      return;
    }
    var r = avaliarForcaSenha(senha, usuarioDaConta());
    barraForca.style.width = ((r.score + 1) / 5 * 100) + "%";
    barraForca.style.background = CORES_FORCA[r.score];
    legendaForca.textContent = ROTULOS_FORCA[r.score] + (r.motivos.length ? " — " + r.motivos.join(", ") + "." : ".");
  }
  campoSenhaNova.addEventListener("input", atualizarMedidor);
  document.getElementById("usuarioNovo").addEventListener("input", atualizarMedidor);

  // ---------------- TROCAR USUÁRIO / SENHA ----------------
  document.getElementById("trocarSenhaBtn").addEventListener("click", function(){
    var msg = document.getElementById("senhaMsg");
    var atual = document.getElementById("senhaAtual").value;
    var novoUsuario = document.getElementById("usuarioNovo").value.trim();
    var nova = campoSenhaNova.value;
    var nova2 = document.getElementById("senhaNova2").value;
    msg.style.color = "var(--danger)";
    if (!atual){ msg.textContent = "Informe a senha atual."; return; }
    if (novoUsuario && !/^[A-Za-z0-9_.\-]{3,40}$/.test(novoUsuario)){
      msg.textContent = "Usuário inválido: use de 3 a 40 letras, números, ponto, traço ou underline.";
      return;
    }
    if (nova === atual){ msg.textContent = "A nova senha precisa ser diferente da atual."; return; }
    var forca = avaliarForcaSenha(nova, novoUsuario || window.NEXUS_USUARIO || "");
    if (!forca.forte){ msg.textContent = "Senha fraca: " + forca.motivos.join("; ") + "."; return; }
    if (nova !== nova2){ msg.textContent = "As duas senhas novas não são iguais."; return; }
    msg.style.color = "var(--muted)";
    msg.textContent = "Salvando…";
    pedir({acao:"trocar_senha", atual:atual, nova:nova, novo_usuario:novoUsuario}).then(function(res){
      if (res && res.ok){
        msg.style.color = "var(--ok)";
        msg.textContent = res.mensagem || "Alterado ✓";
        document.getElementById("senhaAtual").value = "";
        document.getElementById("usuarioNovo").value = "";
        campoSenhaNova.value = "";
        document.getElementById("senhaNova2").value = "";
        barraForca.style.width = "0%";
        legendaForca.textContent = "Digite a nova senha para ver a força dela.";
      } else if (res && res.erro === "sessao"){
        msg.style.color = "var(--danger)";
        msg.textContent = (res.mensagem || "Sessão encerrada.") + " Recarregando…";
        setTimeout(function(){ location.reload(); }, 2500);
      } else {
        msg.style.color = "var(--danger)";
        msg.textContent = (res && res.mensagem) || "Não foi possível salvar.";
      }
    });
  });

  // ---------------- 2FA: novos códigos de recuperação / trocar aparelho ----------------
  var dfaMsg = document.getElementById("dfaMsg");
  /**
   * Executa uma ação sensível do 2FA (gerar novos códigos de recuperação ou trocar o aparelho). Exige a senha atual e um código válido, pede confirmação e mostra os códigos novos na tela.
   * @param {string} acao Nome da ação na API.
   * @param {string} confirmacao Texto da caixa de confirmação.
   */
  function dfaExecutar(acao, confirmacao){
    var senha  = document.getElementById("dfaSenha").value;
    var codigo = document.getElementById("dfaCodigo").value.trim();
    dfaMsg.style.color = "var(--danger)";
    if (!senha || !codigo){
      dfaMsg.textContent = "Informe a senha atual e um código do aplicativo (ou de recuperação).";
      return;
    }
    if (confirmacao && !window.confirm(confirmacao)) return;
    dfaMsg.style.color = "var(--muted)";
    dfaMsg.textContent = "Verificando…";
    pedir({acao:acao, atual:senha, codigo:codigo}).then(function(res){
      document.getElementById("dfaCodigo").value = "";
      if (res && res.ok){
        document.getElementById("dfaSenha").value = "";
        dfaMsg.style.color = "var(--ok)";
        dfaMsg.textContent = res.mensagem || "Pronto ✓";
        if (res.codigos){
          var caixa = document.getElementById("dfaCodigos");
          caixa.textContent = "";
          res.codigos.forEach(function(c){
            var s = document.createElement("span");
            s.textContent = c;
            caixa.appendChild(s);
          });
        }
        if (typeof res.restantes === "number"){
          var r = document.getElementById("dfaRestantes");
          r.textContent = String(res.restantes);
          r.style.color = res.restantes <= 2 ? "var(--danger)" : "";
        }
        if (res.sair){ setTimeout(function(){ location.reload(); }, 2500); }
      } else if (res && res.erro === "sessao"){
        location.reload();
      } else {
        dfaMsg.style.color = "var(--danger)";
        dfaMsg.textContent = (res && res.mensagem) || "Não foi possível concluir.";
      }
    }).catch(function(){
      dfaMsg.style.color = "var(--danger)";
      dfaMsg.textContent = "Sem conexão com o servidor.";
    });
  }
  document.getElementById("dfaRegerarBtn").addEventListener("click", function(){
    dfaExecutar("regerar_recuperacao", "Gerar novos códigos? Os códigos antigos deixam de valer.");
  });
  document.getElementById("dfaTrocarBtn").addEventListener("click", function(){
    dfaExecutar("reiniciar_2fa", "Trocar o aparelho? Você será desconectado e vai configurar o novo aplicativo no próximo login. Os códigos de recuperação atuais deixam de valer.");
  });

  /**
   * Redesenha todas as áreas editáveis a partir do estado em 'dados'.
   */
  function renderAll(){
    renderTextFields();
    renderTccList();
  }
  renderAll();
})();
</script>
<?php endif; ?>
<script nonce="<?= h(nexus_nonce()) ?>">
// Botão de mostrar/ocultar senha — funciona em qualquer campo marcado com
// .btn-olho, tanto na tela de login quanto na aba "Senha" do painel.
document.addEventListener('click', function(ev){
  var btn = ev.target.closest('.btn-olho');
  if (!btn) return;
  var alvo = document.getElementById(btn.getAttribute('data-alvo'));
  if (!alvo) return;
  var mostrando = alvo.type === 'text';
  alvo.type = mostrando ? 'password' : 'text';
  btn.classList.toggle('mostrando', !mostrando);
  btn.setAttribute('aria-label', mostrando ? 'Mostrar senha' : 'Ocultar senha');
});
</script>
</body>
</html>
