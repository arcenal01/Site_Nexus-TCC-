<?php
/*
 * API DO PAINEL (edição de demonstração)
 * ------------------------------------------------------------------
 * Recebe as ações do painel administrativo e responde em JSON.
 *
 * Em PRODUÇÃO, as ações gravam em dados/conteudo.json e passam a valer
 * para todos os visitantes imediatamente. Nesta edição de DEMONSTRAÇÃO,
 * toda entrada passa pelas mesmas validações, porém nenhuma gravação é
 * efetivada (ver o bloco "Edição de demonstração" mais abaixo).
 *
 * Controles aplicados a toda requisição:
 *   - método POST obrigatório;
 *   - sessão autenticada (login + 2FA) e dentro do tempo de validade;
 *   - token CSRF válido;
 *   - cabeçalhos de segurança e CSP restritiva (nexus_cabecalhos_seguranca).
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/dados.php';
require_once __DIR__ . '/inc/auth.php';

nexus_cabecalhos_seguranca(true);
header('Content-Type: application/json; charset=utf-8');

/**
 * Envia a resposta JSON com o código HTTP informado e encerra a execução.
 */
function responder(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Falha interna (NX-15): o detalhe técnico vai para o log de erros e quem usa o
 * painel recebe só uma mensagem genérica, sem nomes de pastas nem permissões.
 */
function falha_interna(string $detalhe, string $mensagem = 'Não foi possível concluir. Avise o responsável pelo site.', int $codigo = 500): void
{
    nexus_log_erro($detalhe);
    responder(['ok' => false, 'mensagem' => $mensagem], $codigo);
}

/**
 * "content" e "tccs.edits" são mapas (chave => valor). Um array PHP vazio
 * vira "[]" no JSON (e não "{}"), então sem isso o JavaScript recebe uma
 * lista em vez de objeto nesses dois campos quando ainda não há nada
 * salvo — e qualquer edição guardada ali some ao virar JSON de novo.
 */
function nexus_pacote_para_json(array $pacote): array
{
    $pacote['content']      = (object)($pacote['content'] ?? []);
    $pacote['tccs']['edits'] = (object)($pacote['tccs']['edits'] ?? []);
    return $pacote;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder(['ok' => false, 'erro' => 'Método não permitido.'], 405);
}
if (!nexus_logado()) {
    responder(['ok' => false, 'erro' => 'sessao', 'mensagem' => 'Sessão expirada. Faça login novamente.'], 401);
}
if (!nexus_csrf_valido($_POST['csrf'] ?? null)) {
    responder(['ok' => false, 'erro' => 'csrf', 'mensagem' => 'Token de segurança inválido. Recarregue a página.'], 403);
}

$acao = (string)($_POST['acao'] ?? '');

/* ==================================================================
 * Funções de limpeza/validação do conteúdo recebido
 * ================================================================== */

function limpar_texto($valor, int $max = 4000): string
{
    if (!is_scalar($valor)) {
        return '';
    }
    $texto = (string)$valor;
    $texto = str_replace(["\r\n", "\r"], "\n", $texto);
    // remove caracteres de controle (menos quebra de linha e tab)
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto) ?? '';
    if (function_exists('mb_substr')) {
        $texto = mb_substr($texto, 0, $max, 'UTF-8');
    } else {
        $texto = substr($texto, 0, $max);
    }
    return trim($texto);
}

/**
 * Caminho de imagem aceito (NX-14): restritivo por construção. Só
 * "imagens-tcc/NOME.ext" ou "uploads/tcc/NOME.ext" (sem barra inicial, sem
 * pontos extras, sem subpastas), com extensão de imagem, e o arquivo
 * PRECISA existir. Bloqueia javascript:, data:, http(s)://, caminhos
 * absolutos, travessia de diretórios e referências a imagens inexistentes.
 */
function limpar_caminho_imagem($valor): string
{
    $caminho = trim((string)(is_scalar($valor) ? $valor : ''));
    if ($caminho === '' || strlen($caminho) > 120) {
        return '';
    }
    if (!preg_match('#^(imagens-tcc|uploads/tcc)/[A-Za-z0-9_\-]+\.(png|jpe?g|webp|gif)$#i', $caminho)) {
        return '';
    }
    if (!is_file(NEXUS_RAIZ . '/' . $caminho)) {
        return '';
    }
    return $caminho;
}

/**
 * Normaliza a categoria de um projeto. Só aceita 'web', 'redes' ou 'software'; qualquer outro valor vira 'web'.
 */
function limpar_categoria($valor): string
{
    $cat = strtolower(trim((string)(is_scalar($valor) ? $valor : '')));
    return in_array($cat, ['web', 'redes', 'software'], true) ? $cat : 'web';
}

/**
 * Valida e sanitiza os dados de um projeto (TCC) vindos do painel: limita tamanhos, filtra
 * categoria e imagens e remove equipe e tecnologias vazias. Devolve só campos conhecidos.
 */
function limpar_tcc(array $bruto): array
{
    $tcc = [
        'title'         => limpar_texto($bruto['title'] ?? '', 120),
        'category'      => limpar_categoria($bruto['category'] ?? 'web'),
        'categoryLabel' => limpar_texto($bruto['categoryLabel'] ?? '', 80),
        'description'   => limpar_texto($bruto['description'] ?? '', 2000),
        'purpose'       => limpar_texto($bruto['purpose'] ?? '', 2000),
        'image'         => limpar_caminho_imagem($bruto['image'] ?? ''),
        'logo'          => limpar_caminho_imagem($bruto['logo'] ?? ''),
        'team'          => [],
        'tech'          => [],
    ];
    if ($tcc['categoryLabel'] === '') {
        $tcc['categoryLabel'] = 'Projeto';
    }
    // Imagens adicionais (galeria do card): antes eram descartadas aqui. Passam
    // pela mesma validação restritiva; só entram no pacote se houver alguma.
    if (isset($bruto['extraImages']) && is_array($bruto['extraImages'])) {
        $extras = [];
        foreach (array_slice($bruto['extraImages'], 0, 10) as $extra) {
            $c = limpar_caminho_imagem($extra);
            if ($c !== '' && !in_array($c, $extras, true)) {
                $extras[] = $c;
            }
        }
        if ($extras) {
            $tcc['extraImages'] = $extras;
        }
    }
    if (isset($bruto['team']) && is_array($bruto['team'])) {
        foreach (array_slice($bruto['team'], 0, 20) as $membro) {
            if (!is_array($membro)) {
                continue;
            }
            $nome = limpar_texto($membro['name'] ?? '', 80);
            $func = limpar_texto($membro['role'] ?? '', 80);
            if ($nome !== '' || $func !== '') {
                $tcc['team'][] = ['name' => $nome, 'role' => $func];
            }
        }
    }
    if (isset($bruto['tech']) && is_array($bruto['tech'])) {
        foreach (array_slice($bruto['tech'], 0, 25) as $tec) {
            $t = limpar_texto($tec, 40);
            if ($t !== '') {
                $tcc['tech'][] = $t;
            }
        }
    }
    return $tcc;
}

/**
 * Valida e sanitiza o pacote completo enviado pelo painel (textos, data do evento e projetos).
 * Só passam chaves e identificadores que existem nos valores padrão do site; todo o resto é descartado.
 */
function limpar_pacote(array $bruto): array
{
    $limpo    = nexus_dados_vazios();
    $padroes  = nexus_padroes();
    $chavesOk = array_keys($padroes['content']);
    $idsOk    = array_column($padroes['tccs'], 'id');

    // 1) Textos simples — só chaves que realmente existem no site
    if (isset($bruto['content']) && is_array($bruto['content'])) {
        foreach ($bruto['content'] as $chave => $valor) {
            if (in_array($chave, $chavesOk, true)) {
                $texto = limpar_texto($valor, 4000);
                if ($texto !== '') {
                    $limpo['content'][$chave] = $texto;
                }
            }
        }
    }

    // 2) Data do evento — formato AAAA-MM-DDTHH:MM(:SS)
    // O regex só garante o formato (ex.: 2026-13-40T25:99:99 passaria nele),
    // então depois validamos se a data e a hora existem de verdade.
    if (!empty($bruto['eventDate']) && is_string($bruto['eventDate'])) {
        $d = trim($bruto['eventDate']);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/', $d, $m)) {
            [, $ano, $mes, $dia, $hora, $min] = $m;
            $seg = $m[6] ?? '00';
            $dataValida = checkdate((int)$mes, (int)$dia, (int)$ano);
            $horaValida = (int)$hora <= 23 && (int)$min <= 59 && (int)$seg <= 59;
            if ($dataValida && $horaValida) {
                $limpo['eventDate'] = strlen($d) === 16 ? $d . ':00' : $d;
            }
        }
    }

    // 3) TCCs
    if (isset($bruto['tccs']) && is_array($bruto['tccs'])) {
        $t = $bruto['tccs'];

        if (isset($t['edits']) && is_array($t['edits'])) {
            foreach ($t['edits'] as $id => $dados) {
                if (in_array($id, $idsOk, true) && is_array($dados)) {
                    $limpo['tccs']['edits'][$id] = limpar_tcc($dados);
                }
            }
        }
        if (isset($t['deleted']) && is_array($t['deleted'])) {
            foreach ($t['deleted'] as $id) {
                if (in_array($id, $idsOk, true) && !in_array($id, $limpo['tccs']['deleted'], true)) {
                    $limpo['tccs']['deleted'][] = $id;
                }
            }
        }
        if (isset($t['extra']) && is_array($t['extra'])) {
            foreach (array_slice($t['extra'], 0, 60) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $id = (string)($item['id'] ?? '');
                if (!preg_match('/^extra-[A-Za-z0-9]{1,30}$/', $id)) {
                    $id = 'extra-' . bin2hex(random_bytes(6));
                }
                $novo       = limpar_tcc($item);
                $novo['id'] = $id;
                if ($novo['title'] !== '') {
                    $limpo['tccs']['extra'][] = $novo;
                }
            }
        }
    }

    return $limpo;
}

/* ==================================================================
 * Ações
 * ================================================================== */

/* ------------------------------------------------------------------
 * Edição de demonstração
 * ------------------------------------------------------------------
 * A conta pública de demonstração não pode ser alterada: trocar senha
 * e reiniciar o 2FA ficam desativados.
 */
if (nexus_demo() && in_array($acao, ['trocar_senha', 'regerar_recuperacao', 'reiniciar_2fa'], true)) {
    responder([
        'ok'       => false,
        'mensagem' => 'Desativado na demonstração: a conta pública não pode ser alterada.',
    ], 403);
}


/** Resumo curto do que mudou entre dois pacotes (para o log de auditoria). */
function resumo_alteracao(array $a, array $b): string
{
    $partes = [];
    $ca = is_array($a['content'] ?? null) ? $a['content'] : [];
    $cb = is_array($b['content'] ?? null) ? $b['content'] : [];
    $mud = [];
    foreach (array_unique(array_merge(array_keys($ca), array_keys($cb))) as $k) {
        if (($ca[$k] ?? null) !== ($cb[$k] ?? null)) {
            $mud[] = (string)$k;
        }
    }
    if ($mud) {
        $partes[] = 'textos: ' . implode(',', array_slice($mud, 0, 8)) . (count($mud) > 8 ? ' (+' . (count($mud) - 8) . ')' : '');
    }
    if (($a['eventDate'] ?? null) !== ($b['eventDate'] ?? null)) {
        $partes[] = 'data do evento';
    }
    $ta = is_array($a['tccs'] ?? null) ? $a['tccs'] : [];
    $tb = is_array($b['tccs'] ?? null) ? $b['tccs'] : [];
    $ea = is_array($ta['edits'] ?? null) ? $ta['edits'] : [];
    $eb = is_array($tb['edits'] ?? null) ? $tb['edits'] : [];
    $editados = 0;
    foreach (array_unique(array_merge(array_keys($ea), array_keys($eb))) as $id) {
        if (($ea[$id] ?? null) !== ($eb[$id] ?? null)) {
            $editados++;
        }
    }
    if ($editados > 0) {
        $partes[] = 'TCCs editados: ' . $editados;
    }
    $xa = is_array($ta['extra'] ?? null) ? count($ta['extra']) : 0;
    $xb = is_array($tb['extra'] ?? null) ? count($tb['extra']) : 0;
    if ($xa !== $xb) {
        $partes[] = 'TCCs novos: ' . $xa . '→' . $xb;
    }
    $da = is_array($ta['deleted'] ?? null) ? count($ta['deleted']) : 0;
    $db = is_array($tb['deleted'] ?? null) ? count($tb['deleted']) : 0;
    if ($da !== $db) {
        $partes[] = 'TCCs ocultos: ' . $da . '→' . $db;
    }
    return implode('; ', $partes);
}

const MSG_CONFLITO = 'Outra pessoa (ou outra aba) alterou o conteúdo enquanto você editava, então esta gravação foi recusada para não apagar o trabalho dela. A página será recarregada com a versão atual.';

if ($acao === 'salvar') {
    $json  = (string)($_POST['dados'] ?? '');
    if (strlen($json) > 2 * 1024 * 1024) {
        responder(['ok' => false, 'mensagem' => 'Conteúdo muito grande.'], 413);
    }
    $bruto = json_decode($json, true);
    if (!is_array($bruto)) {
        responder(['ok' => false, 'mensagem' => 'Dados inválidos.'], 400);
    }
    $limpo = limpar_pacote($bruto);
    $rev   = (string)($_POST['rev'] ?? '');

    // Demonstração: o conteúdo já passou por toda a validação e sanitização acima, mas é descartado.
    if (nexus_demo()) {
        nexus_auditar('demo_salvar_simulado', resumo_alteracao(nexus_ler_json(NEXUS_ARQ_DADOS, []), $limpo));
        responder([
            'ok'       => true,
            'mensagem' => 'Demonstração: alterações validadas, mas não publicadas ✓',
            'rev'      => $rev,
            'dados'    => nexus_pacote_para_json($limpo),
        ]);
    }

    // Tudo dentro de uma trava: confere a versão, faz o backup e grava sem
    // que outra requisição entre no meio ("última gravação vence" evitado).
    $r = nexus_com_trava('conteudo', function () use ($limpo, $rev): array {
        if (!hash_equals(nexus_revisao_conteudo(), $rev)) {
            return ['estado' => 'conflito'];
        }
        $antes = nexus_ler_json(NEXUS_ARQ_DADOS, []);
        nexus_backup_conteudo();
        if (!nexus_gravar_json(NEXUS_ARQ_DADOS, $limpo)) {
            return ['estado' => 'erro'];
        }
        return ['estado' => 'ok', 'rev' => nexus_revisao_conteudo(), 'resumo' => resumo_alteracao($antes, $limpo)];
    });
    if ($r['estado'] === 'conflito') {
        nexus_auditar('salvar_conflito', 'versão desatualizada');
        responder(['ok' => false, 'erro' => 'conflito', 'mensagem' => MSG_CONFLITO], 409);
    }
    if ($r['estado'] !== 'ok') {
        falha_interna('salvar: não foi possível gravar conteudo.json (verifique a permissão de escrita da pasta de dados)', 'Não foi possível salvar. Avise o responsável pelo site.');
    }
    if ($r['resumo'] !== '') {   // o auto-salvar repete gravações sem mudança: não enche o log
        nexus_auditar('salvar', $r['resumo']);
    }
    responder(['ok' => true, 'mensagem' => 'Publicado no site ✓', 'rev' => $r['rev'], 'dados' => nexus_pacote_para_json($limpo)]);
}

if ($acao === 'restaurar') {
    if (trim((string)($_POST['confirmacao'] ?? '')) !== 'RESTAURAR') {
        responder(['ok' => false, 'mensagem' => 'Confirmação ausente: digite RESTAURAR para apagar as alterações.'], 400);
    }
    $rev = (string)($_POST['rev'] ?? '');

    if (nexus_demo()) {
        nexus_auditar('demo_restaurar_simulado');
        responder([
            'ok'       => true,
            'mensagem' => 'Demonstração: restauração simulada, nada foi alterado ✓',
            'rev'      => $rev,
            'dados'    => nexus_pacote_para_json(nexus_dados_vazios()),
        ]);
    }

    $r = nexus_com_trava('conteudo', function () use ($rev): array {
        if (!hash_equals(nexus_revisao_conteudo(), $rev)) {
            return ['estado' => 'conflito'];
        }
        nexus_backup_conteudo(true);   // este backup é sempre feito
        if (!nexus_gravar_json(NEXUS_ARQ_DADOS, nexus_dados_vazios())) {
            return ['estado' => 'erro'];
        }
        return ['estado' => 'ok', 'rev' => nexus_revisao_conteudo()];
    });
    if ($r['estado'] === 'conflito') {
        nexus_auditar('restaurar_conflito', 'versão desatualizada');
        responder(['ok' => false, 'erro' => 'conflito', 'mensagem' => MSG_CONFLITO], 409);
    }
    if ($r['estado'] !== 'ok') {
        falha_interna('restaurar: não foi possível gravar conteudo.json', 'Não foi possível restaurar. Avise o responsável pelo site.');
    }
    nexus_auditar('restaurar', 'conteúdo devolvido ao original (backup feito)');
    responder(['ok' => true, 'mensagem' => 'Site restaurado ao conteúdo original ✓ (backup guardado)', 'rev' => $r['rev'], 'dados' => nexus_pacote_para_json(nexus_dados_vazios())]);
}

if ($acao === 'upload') {
    if (!isset($_FILES['arquivo']) || ($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        responder(['ok' => false, 'mensagem' => 'Falha no envio do arquivo.'], 400);
    }
    $arq = $_FILES['arquivo'];
    if ($arq['size'] > NEXUS_UPLOAD_MAX_BYTES) {
        responder(['ok' => false, 'mensagem' => 'Imagem muito grande (máximo 4 MB).'], 413);
    }
    if (!is_uploaded_file($arq['tmp_name'])) {
        responder(['ok' => false, 'mensagem' => 'Envio inválido.'], 400);
    }

    // Confere que é mesmo uma imagem (não basta o nome do arquivo)
    $info = @getimagesize($arq['tmp_name']);
    if ($info === false) {
        responder(['ok' => false, 'mensagem' => 'O arquivo enviado não é uma imagem válida.'], 415);
    }
    $extensoes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];
    if (!isset($extensoes[$info[2]])) {
        responder(['ok' => false, 'mensagem' => 'Formato não aceito. Use JPG, PNG, GIF ou WEBP.'], 415);
    }

    // Limite de dimensões ANTES de abrir a imagem: um PNG pequeno pode declarar
    // dezenas de milhares de pixels por lado e esgotar a memória do servidor.
    if (!nexus_imagem_dimensoes_ok((int)$info[0], (int)$info[1])) {
        responder(['ok' => false, 'mensagem' => 'Dimensões da imagem fora do limite (máximo 6000 x 6000 px).'], 413);
    }

    // Demonstração: o arquivo foi aceito pelas verificações de tipo real, formato e dimensões,
    // mas não é armazenado no servidor.
    if (nexus_demo()) {
        nexus_auditar('demo_upload_simulado', $extensoes[$info[2]] . ' ' . $info[0] . 'x' . $info[1]);
        responder([
            'ok'       => false,
            'mensagem' => 'Demonstração: a imagem passou nas validações (tipo real, formato e dimensões), mas não é armazenada.',
        ]);
    }

    if (!is_dir(NEXUS_DIR_UPLOAD) && !@mkdir(NEXUS_DIR_UPLOAD, 0775, true) && !is_dir(NEXUS_DIR_UPLOAD)) {
        falha_interna('upload: pasta de uploads indisponível (' . NEXUS_DIR_UPLOAD . ')', 'Não foi possível enviar a imagem. Avise o responsável pelo site.');
    }

    // Nome aleatório: o nome original enviado pelo navegador nunca é usado
    $nome    = 'tcc-' . date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $extensoes[$info[2]];
    $destino = NEXUS_DIR_UPLOAD . '/' . $nome;
    if (!@move_uploaded_file($arq['tmp_name'], $destino)) {
        falha_interna('upload: move_uploaded_file falhou (verifique a permissão da pasta de uploads)', 'Não foi possível enviar a imagem. Avise o responsável pelo site.');
    }
    @chmod($destino, 0644);

    // Regrava a imagem via GD: remove EXIF (pode conter GPS de quem tirou a
    // foto) e neutraliza qualquer polyglot embutido no arquivo. Se não for
    // possível (GD ausente ou erro), o arquivo é APAGADO: nunca publicamos
    // o original sem tratamento.
    if (!nexus_reprocessar_imagem($destino, $info[2])) {
        @unlink($destino);
        responder(['ok' => false, 'mensagem' => 'Não foi possível processar a imagem. Tente outro arquivo (JPG ou PNG).'], 422);
    }

    nexus_auditar('upload', (string)$nome);
    responder(['ok' => true, 'caminho' => NEXUS_URL_UPLOAD . '/' . $nome]);
}

if ($acao === 'trocar_senha') {
    $atual        = (string)($_POST['atual'] ?? '');
    $nova         = (string)($_POST['nova'] ?? '');
    $novoUsuario  = trim((string)($_POST['novo_usuario'] ?? ''));

    // Só é permitido trocar a própria conta (a que está logada nesta sessão) —
    // isso deixa o painel multiusuário sem abrir brecha para uma conta
    // alterar a senha de outra.
    $usuarioSessao = (string)($_SESSION['nexus_usuario'] ?? '');
    $cred = $usuarioSessao !== '' ? nexus_credencial_por_usuario($usuarioSessao) : null;

    if ($cred === null) {
        responder(['ok' => false, 'erro' => 'sessao', 'mensagem' => 'Sessão expirada. Faça login novamente.'], 401);
    }
    // NX-12: a "senha atual" também tem limite de tentativas (por IP e por conta,
    // contadores próprios da troca). A tentativa é contada ANTES de conferir.
    if (!nexus_reservar_tentativa($usuarioSessao, 'troca')) {
        nexus_auditar('senha_troca_bloqueada', 'limite de tentativas; sessão encerrada');
        nexus_logout();
        responder(['ok' => false, 'erro' => 'sessao', 'mensagem' => 'Muitas tentativas de senha atual. Sua sessão foi encerrada; entre de novo mais tarde.'], 429);
    }
    if (!password_verify($atual, $cred['hash'])) {
        usleep(300000);
        nexus_auditar('senha_troca_falha', 'senha atual incorreta');
        if (nexus_escopo_esgotado($usuarioSessao, 'troca')) {
            // atingiu o limite: encerra a sessão (uma sessão roubada não consegue continuar adivinhando)
            nexus_auditar('senha_troca_bloqueada', 'limite de tentativas; sessão encerrada');
            nexus_logout();
            responder(['ok' => false, 'erro' => 'sessao', 'mensagem' => 'Senha atual incorreta várias vezes. Sua sessão foi encerrada; entre de novo mais tarde.'], 429);
        }
        responder(['ok' => false, 'mensagem' => 'A senha atual está incorreta.'], 403);
    }
    // senha atual correta: zera só os contadores da troca (os do login continuam como estavam)
    nexus_limpar_falhas($usuarioSessao, 'troca');
    if (hash_equals($atual, $nova)) {
        responder(['ok' => false, 'mensagem' => 'A nova senha precisa ser diferente da atual.'], 400);
    }
    // NX-11: regras de força + nome de usuário que a conta vai ter + consulta de vazamentos
    $usuarioAlvo = $novoUsuario !== '' ? $novoUsuario : (string)$cred['usuario'];
    $forca = nexus_senha_validar($nova, $usuarioAlvo);
    if (!$forca['ok']) {
        responder(['ok' => false, 'mensagem' => 'Senha fraca: ' . implode('; ', $forca['motivos']) . '.'], 400);
    }
    if ($novoUsuario !== '') {
        if (!nexus_usuario_valido($novoUsuario)) {
            responder(['ok' => false, 'mensagem' => 'Usuário inválido: use de 3 a 40 letras, números, ponto, traço ou underline.'], 400);
        }
        if (nexus_usuario_existe($novoUsuario, $cred['usuario'])) {
            responder(['ok' => false, 'mensagem' => 'Já existe outra conta com esse nome de usuário.'], 400);
        }
    }
    $usuarioFinal = $novoUsuario !== '' ? $novoUsuario : $cred['usuario'];
    if (!nexus_definir_credenciais($usuarioFinal, $nova, $cred['usuario'])) {
        falha_interna('trocar_senha: não foi possível gravar dados/senha.php', 'Não foi possível gravar as novas credenciais. Avise o responsável pelo site.');
    }
    nexus_auditar('senha_trocada', $novoUsuario !== '' ? 'usuário e senha alterados' : 'senha alterada');
    $_SESSION['nexus_usuario'] = $usuarioFinal;
    // Esta sessão continua valendo (com o selo novo); todas as OUTRAS sessões da
    // conta caem, porque o selo delas é o da senha antiga.
    $_SESSION['nexus_selo'] = nexus_selo_sessao(nexus_credencial_por_usuario($usuarioFinal));
    session_regenerate_id(true);
    responder(['ok' => true, 'mensagem' => $novoUsuario !== '' ? 'Usuário e senha alterados ✓' : 'Senha alterada ✓']);
}

if ($acao === 'regerar_recuperacao' || $acao === 'reiniciar_2fa') {
    // Ações sensíveis: exigem a senha atual E um código válido (aplicativo ou
    // recuperação), e contam no mesmo limite de tentativas do login.
    $usuarioSessao = (string)($_SESSION['nexus_usuario'] ?? '');
    $cred = $usuarioSessao !== '' ? nexus_credencial_por_usuario($usuarioSessao) : null;
    if ($cred === null || empty($cred['totp'])) {
        responder(['ok' => false, 'mensagem' => 'O 2FA não está ativo nesta conta.'], 400);
    }
    if (!nexus_reservar_tentativa($usuarioSessao)) {
        responder(['ok' => false, 'mensagem' => 'Muitas tentativas. Aguarde alguns minutos.'], 429);
    }
    $senhaOk = password_verify((string)($_POST['atual'] ?? ''), $cred['hash']);
    // só confere (e consome) o código se a senha estiver certa
    if (!$senhaOk || !nexus_2fa_validar_codigo($cred, (string)($_POST['codigo'] ?? ''))) {
        usleep(300000);
        nexus_auditar('2fa_acao_falha', $acao);
        responder(['ok' => false, 'mensagem' => 'Senha ou código incorretos.'], 403);
    }
    nexus_limpar_falhas($usuarioSessao);

    if ($acao === 'regerar_recuperacao') {
        [$planos, $hashes] = nexus_recuperacao_gerar(8);
        if (!nexus_2fa_gravar($cred['usuario'], (string)$cred['totp'], $hashes)) {
            falha_interna('regerar_recuperacao: não foi possível gravar dados/senha.php', 'Não foi possível salvar os novos códigos. Avise o responsável pelo site.');
        }
        nexus_auditar('2fa_codigos_regerados');
        responder([
            'ok'        => true,
            'mensagem'  => 'Novos códigos gerados. Guarde-os agora: não serão mostrados de novo.',
            'codigos'   => $planos,
            'restantes' => count($planos),
        ]);
    }

    // reiniciar_2fa: apaga o segredo atual; o próximo login configura o novo aparelho
    if (!nexus_2fa_gravar($cred['usuario'], null)) {
        falha_interna('reiniciar_2fa: não foi possível gravar dados/senha.php', 'Não foi possível reiniciar o 2FA. Avise o responsável pelo site.');
    }
    nexus_auditar('2fa_reiniciado', 'aparelho do autenticador trocado');
    nexus_logout();
    responder(['ok' => true, 'sair' => true, 'mensagem' => 'Pronto. Você será desconectado: entre de novo para configurar o novo aparelho.']);
}

responder(['ok' => false, 'mensagem' => 'Ação desconhecida.'], 400);
