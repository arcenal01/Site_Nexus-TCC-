<?php
/*
 * CAMADA DE DADOS DO SITE (inc/dados.php)
 * ------------------------------------------------------------------
 * Única porta de entrada para o conteúdo exibido nas páginas públicas
 * e no painel.
 *
 * Modelo de dados: o site guarda apenas as ALTERAÇÕES feitas no painel,
 * em dados/conteudo.json. O conteúdo em vigor é sempre
 *     padrões de fábrica (inc/padroes.php) + alterações salvas.
 * Assim, "Restaurar original" é apagar as alterações, e o site nunca
 * fica sem conteúdo.
 *
 * Formato de dados/conteudo.json:
 *   content   => [chave => texto]             textos editados
 *   eventDate => 'AAAA-MM-DDTHH:MM:SS'|null   data do evento
 *   tccs      => [edits => [id => projeto],   projetos padrão editados
 *                deleted => [id, ...],        projetos padrão ocultos
 *                extra => [projeto, ...]]     projetos criados no painel
 *
 * Como a leitura acontece a cada visita, o que é salvo no painel
 * aparece imediatamente para todos, sem publicar arquivos.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Estrutura vazia de alterações.
 */
function nexus_dados_vazios(): array
{
    return [
        'content'   => [],
        'eventDate' => null,
        'tccs'      => ['edits' => [], 'deleted' => [], 'extra' => []],
    ];
}

/**
 * Conteúdo original do site (padrões de fábrica).
 */
function nexus_padroes(): array
{
    static $padroes = null;
    if ($padroes === null) {
        $padroes = require __DIR__ . '/padroes.php';
    }
    return $padroes;
}

/**
 * Alterações salvas pelo painel (dados/conteudo.json), já normalizadas.
 */
function nexus_alteracoes(): array
{
    static $dados = null;
    if ($dados !== null) {
        return $dados;
    }

    $bruto = nexus_ler_json(NEXUS_ARQ_DADOS, nexus_dados_vazios());

    $dados = nexus_dados_vazios();
    if (isset($bruto['content']) && is_array($bruto['content'])) {
        foreach ($bruto['content'] as $chave => $valor) {
            if (is_string($chave) && is_scalar($valor)) {
                $dados['content'][$chave] = (string)$valor;
            }
        }
    }
    if (!empty($bruto['eventDate']) && is_string($bruto['eventDate'])) {
        $dados['eventDate'] = $bruto['eventDate'];
    }
    if (isset($bruto['tccs']) && is_array($bruto['tccs'])) {
        $t = $bruto['tccs'];
        if (isset($t['edits']) && is_array($t['edits'])) {
            $dados['tccs']['edits'] = $t['edits'];
        }
        if (isset($t['deleted']) && is_array($t['deleted'])) {
            $dados['tccs']['deleted'] = array_values(array_filter($t['deleted'], 'is_string'));
        }
        if (isset($t['extra']) && is_array($t['extra'])) {
            $dados['tccs']['extra'] = array_values(array_filter($t['extra'], 'is_array'));
        }
    }

    return $dados;
}

/**
 * Texto de uma chave editável ("data-edit"), já com o padrão como reserva.
 * Use assim no HTML:  <p><?= h(txt('idx-hero-paragrafo')) ?></p>
 */
function txt(string $chave): string
{
    $alt = nexus_alteracoes()['content'];
    if (isset($alt[$chave]) && $alt[$chave] !== '') {
        return $alt[$chave];
    }
    $padrao = nexus_padroes()['content'];
    return $padrao[$chave] ?? '';
}

/**
 * Data/hora do evento usada na contagem regressiva da página inicial.
 */
function nexus_data_evento(): string
{
    $alt = nexus_alteracoes();
    if (!empty($alt['eventDate'])) {
        return $alt['eventDate'];
    }
    return (string)nexus_padroes()['eventDate'];
}

/**
 * Lista final de TCCs exibida no site:
 * padrões + edições do painel − ocultados + novos adicionados.
 *
 * @param bool $incluirOcultos usado pelo painel, para mostrar também os ocultos
 */
function nexus_lista_tccs(bool $incluirOcultos = false): array
{
    $padroes = nexus_padroes()['tccs'];
    $alt     = nexus_alteracoes()['tccs'];
    $lista   = [];

    foreach ($padroes as $base) {
        $item = $base;
        if (isset($alt['edits'][$base['id']]) && is_array($alt['edits'][$base['id']])) {
            $item = array_merge($item, $alt['edits'][$base['id']]);
        }
        $item['id']        = $base['id'];
        $item['isExtra']   = false;
        $item['isDeleted'] = in_array($base['id'], $alt['deleted'], true);
        if ($item['isDeleted'] && !$incluirOcultos) {
            continue;
        }
        $lista[] = $item;
    }

    foreach ($alt['extra'] as $extra) {
        if (empty($extra['id'])) {
            continue;
        }
        $extra['isExtra']   = true;
        $extra['isDeleted'] = false;
        $lista[] = $extra;
    }

    return $lista;
}

/* ==================================================================
 * MONTAGEM DO HTML DOS CARDS DE TCC
 * Mesma marcação usada antes — o visual não muda em nada.
 * ================================================================== */

function nexus_icone(string $chave): string
{
    static $icones = [
        'description' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l4 4v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v4h4"/><line x1="8.5" y1="12" x2="15.5" y2="12"/><line x1="8.5" y1="15.5" x2="15.5" y2="15.5"/><line x1="8.5" y1="19" x2="12.5" y2="19"/></svg>',
        'team'        => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8.5" cy="8" r="3"/><path d="M2.5 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><circle cx="17" cy="8.5" r="2.4"/><path d="M15.2 14.8c2.7 0.4 4.5 2.3 4.5 5.2"/></svg>',
        'tech'        => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.7 2.7-2-2z"/></svg>',
        'purpose'     => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/></svg>',
        'zoom'        => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.5" cy="10.5" r="6.5"/><line x1="15.5" y1="15.5" x2="21" y2="21"/><line x1="10.5" y1="7.5" x2="10.5" y2="13.5"/><line x1="7.5" y1="10.5" x2="13.5" y2="10.5"/></svg>',
        'expand'      => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>',
        'placeholder' => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="2.2"/><circle cx="5" cy="17" r="2.2"/><circle cx="19" cy="17" r="2.2"/><line x1="12" y1="7.2" x2="6.4" y2="15.2"/><line x1="12" y1="7.2" x2="17.6" y2="15.2"/><line x1="7.2" y1="17" x2="16.8" y2="17"/></svg>',
    ];
    return $icones[$chave] ?? '';
}

/**
 * Monta uma seção do cartão de projeto (ícone + título + conteúdo já escapado).
 */
function nexus_card_secao(string $icone, string $titulo, string $interno): string
{
    return '<div class="tcc-card-section">'
        . '<div class="tcc-section-title"><span class="tcc-icon">' . nexus_icone($icone) . '</span> ' . h($titulo) . '</div>'
        . $interno
        . '</div>';
}

/**
 * Monta a área de imagem do cartão de projeto: captura de tela com zoom (e galeria, se houver
 * imagens extras), logo opcional ou, sem imagem, um espaço reservado.
 */
function nexus_card_area_imagem(array $t): string
{
    $titulo = (string)($t['title'] ?? '');
    $imagem = (string)($t['image'] ?? '');
    $logo   = (string)($t['logo'] ?? '');

    if ($imagem !== '') {
        // Lista de imagens: a principal + as adicionais (se houver)
        $imagens = [$imagem];
        foreach ((array)($t['extraImages'] ?? []) as $extra) {
            if (is_string($extra) && $extra !== '') {
                $imagens[] = $extra;
            }
        }
        $total = count($imagens);

        $html = '<div class="tcc-card-image-area has-screenshot tcc-image-zoomable"'
            . ' data-zoom-src="' . h($imagem) . '"'
            . ' data-zoom-alt="Captura de tela do projeto ' . h($titulo) . '"';
        if ($total > 1) {
            $html .= ' data-images="' . h(json_encode($imagens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '"'
                . ' data-index="0"';
        }
        $html .= '>'
            . '<img class="tcc-card-image-area-img" src="' . h($imagem) . '" alt="Captura de tela do projeto ' . h($titulo) . '">';
        if ($logo !== '') {
            $html .= '<div class="tcc-card-logo-badge"><img src="' . h($logo) . '" alt="Logo do projeto ' . h($titulo) . '"></div>';
        }
        $html .= '<div class="tcc-image-zoom-overlay"><span class="tcc-image-zoom-icon">' . nexus_icone('zoom') . '</span></div>';

        if ($total > 1) {
            $html .= '<button class="tcc-img-nav tcc-img-prev" type="button" data-dir="-1" aria-label="Imagem anterior">&#8249;</button>'
                . '<button class="tcc-img-nav tcc-img-next" type="button" data-dir="1" aria-label="Próxima imagem">&#8250;</button>'
                . '<div class="tcc-img-dots" aria-hidden="true">';
            for ($i = 0; $i < $total; $i++) {
                $html .= '<span class="tcc-img-dot' . ($i === 0 ? ' is-active' : '') . '"></span>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    return '<div class="tcc-card-image-area">'
        . '<div class="tcc-card-image-placeholder">'
        . '<div class="tcc-card-image-icon">' . nexus_icone('placeholder') . '</div>'
        . '<div class="tcc-card-image-text">Imagem não informada</div>'
        . '</div></div>';
}

/**
 * HTML completo de um card de TCC (inclusive a div externa).
 */
function nexus_card_tcc(array $t): string
{
    $html = '<div class="tcc-showcase-card" data-category="' . h($t['category'] ?? 'web') . '" data-tcc-id="' . h($t['id'] ?? '') . '">';
    $html .= nexus_card_area_imagem($t);
    $html .= '<div class="tcc-card-truncate"><div class="tcc-card-info">';
    $html .= '<div class="tcc-card-title">' . h($t['title'] ?? '') . '</div>';
    $html .= '<div class="tcc-card-category">' . h($t['categoryLabel'] ?? '') . '</div>';
    $html .= '<div class="tcc-card-content">';

    $html .= nexus_card_secao(
        'description',
        'Descrição',
        '<div class="tcc-section-description">' . h($t['description'] ?? '') . '</div>'
    );

    if (true) {
        $equipe = '<div class="tcc-team">';
        foreach ((array)($t['team'] ?? []) as $membro) {
            if (!is_array($membro)) {
                continue;
            }
            $equipe .= '<div class="team-member">'
                . '<div class="team-member-name">' . h($membro['name'] ?? '') . '</div>'
                . '<div class="team-member-role">' . h($membro['role'] ?? '') . '</div>'
                . '</div>';
        }
        $equipe .= '</div>';
        $html .= '<div class="tcc-divider"></div>' . nexus_card_secao('team', 'Equipe', $equipe);
    }

    if (true) {
        $tecs = '<div class="tcc-section-list">';
        foreach ((array)($t['tech'] ?? []) as $tec) {
            $tecs .= '<span class="tcc-tag">' . h($tec) . '</span>';
        }
        $tecs .= '</div>';
        $html .= '<div class="tcc-divider"></div>' . nexus_card_secao('tech', 'Tecnologias', $tecs);
    }

    if (true) {
        $html .= '<div class="tcc-divider"></div>' . nexus_card_secao(
            'purpose',
            'Para que Serve',
            '<div class="tcc-section-description">' . h($t['purpose'] ?? '') . '</div>'
        );
    }

    $html .= '</div></div>'; // tcc-card-content + tcc-card-info
    $html .= '<div class="tcc-card-fade"><button class="tcc-card-expand-btn" type="button" aria-label="Ver projeto completo">'
        . nexus_icone('expand') . '<span>Ver projeto completo</span></button></div>';
    $html .= '</div>'; // tcc-card-truncate
    $html .= '</div>'; // tcc-showcase-card

    return $html;
}
