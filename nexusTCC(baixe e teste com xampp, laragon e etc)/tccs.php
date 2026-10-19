<?php
/*
 * VITRINE DE TCCs (tccs.php)
 * ------------------------------------------------------------------
 * Lista todos os projetos de conclusão de curso, com filtro por
 * categoria (web, redes e software), popup com os detalhes e zoom
 * da captura de tela.
 *
 * Os cards vêm de nexus_lista_tccs() (inc/dados.php): projetos
 * padrão + edições do painel - projetos ocultos + projetos novos.
 * Os textos fixos passam por txt('chave') e toda saída é escapada
 * com h(). Filtro, popup e zoom: script.js.
 */
require_once __DIR__ . '/inc/dados.php';
nexus_cabecalhos_seguranca();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amostras de TCCs - Nexus</title>
    <link rel="stylesheet" href="assets/fonts/fonts.css">
    <link rel="stylesheet" href="style.css?v=<?= (int) @filemtime(__DIR__ . '/style.css') ?>">
    <link rel="shortcut icon" href="favicon/nexusFavicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="index.php">
                <div class="logo">
                    <div class="logo-icon">N</div>
                    <span class="logo-text">Nexus</span>
                </div>
            </a>
            <div class="links" id="navLinks">
                <a href="index.php">Início</a>
                <a href="#tccs">TCCs</a>
                <a href="saiba-mais.php">Saiba Mais</a>
                <a href="#contato">Contato</a>
            </div>
            <button class="nav-toggle" id="navToggle" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="navLinks">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="badge">AMOSTRAS DE PROJETOS</div>
            <h1>Conheça os TCCs</h1>
            <p>Explore alguns dos projetos mais inovadores apresentados no evento Nexus. Veja a descrição, tecnologias utilizadas e os talentos por trás de cada ideia.</p>
            <div class="hero-mini-stats">
                <div class="hero-mini-stat">
                    <span class="hero-mini-stat-number"><?= count(nexus_lista_tccs()) ?></span>
                    <span class="hero-mini-stat-label">Projetos</span>
                </div>
                <div class="hero-mini-stat-divider"></div>
                <div class="hero-mini-stat">
                    <span class="hero-mini-stat-number">3</span>
                    <span class="hero-mini-stat-label">Categorias</span>
                </div>
                <div class="hero-mini-stat-divider"></div>
                <div class="hero-mini-stat">
                    <span class="hero-mini-stat-number">20+</span>
                    <span class="hero-mini-stat-label">Tecnologias</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter + Grid Section -->
    <section class="tccs-section" id="tccs">
        <div class="container">
            <div class="filter-section">
                <div class="filter-tags">
                    <button class="filter-tag active" data-filter="all">Todos</button>
                    <button class="filter-tag" data-filter="web">Web</button>
                    <button class="filter-tag" data-filter="redes">Redes</button>
                    <button class="filter-tag" data-filter="software">Software</button>
                </div>
            </div>

            <div class="tcc-showcase-grid">
                <?php foreach (nexus_lista_tccs() as $tcc): ?>
                <?= nexus_card_tcc($tcc) ?>
                <?php endforeach; ?>

            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Quer Ver Mais Projetos?</h2>
            <p>Visite o Nexus Tecno e conheça de perto os projetos desenvolvidos pelos estudantes do curso Técnico em Desenvolvimento de Sistemas.</p>
            <div class="cta-buttons">
                <a href="index.php#contato" class="btn btn-white">Entre em Contato</a>
                <a href="index.php" class="btn btn-outline-white">Voltar ao Início</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contato">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="logo">
                        <div class="logo-icon">N</div>
                        <span class="logo-text">Nexus</span>
                    </div>
                    <p style="margin-top: 1rem;">Iniciativa do Curso Técnico em Desenvolvimento de Sistemas do CEAB. Conectando conhecimento, tecnologia e oportunidades.</p>
                </div>
                <div>
                    <h4>Links</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="index.php">Início</a>
                        <a href="tccs.php">TCCs</a>
                        <a href="saiba-mais.php">Saiba Mais</a>
                        <a href="index.php#evento">Evento</a>
                    </div>
                </div>
                <div id="footerContatoInfo">
                    <h4>Contato</h4>
                    <p data-edit="contato-email"><?= h(txt('contato-email')) ?></p>
                    <p data-edit="contato-telefone"><?= h(txt('contato-telefone')) ?></p>
                    <p><a href="https://www.instagram.com/TDS_Ceab" target="_blank" rel="noopener noreferrer">Instagram: @TDS_Ceab</a></p>
                    <p><a href="#">Redes Sociais</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Nexus. Todos os direitos reservados.</p>
                <p>Este site é uma criação escolar, com o objetivo de ser um trabalho de conclusão de curso (TCC).</p>
                <div class="footer-links">
                    <a href="privacidade.php">Política de Privacidade</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Lightbox com a imagem do site/programa ampliada -->
    <div class="tcc-image-lightbox" id="tccImageLightbox">
        <button class="tcc-image-lightbox-close" id="tccImageLightboxClose" type="button" aria-label="Fechar">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <img class="tcc-image-lightbox-img" id="tccImageLightboxImg" src="" alt="">
    </div>

    <!-- Popup com o conteúdo completo do TCC selecionado -->
    <div class="tcc-modal-overlay" id="tccModalOverlay">
        <div class="tcc-modal" role="dialog" aria-modal="true" aria-label="Detalhes do projeto">
            <button class="tcc-modal-close" id="tccModalClose" type="button" aria-label="Fechar">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="tcc-modal-body" id="tccModalBody"></div>
        </div>
    </div>

    <!-- Barra de CTA fixa (somente mobile) -->
    <div class="mobile-sticky-cta" id="mobileStickyCta">
        <a href="index.php#contato" class="btn btn-primary">Entre em Contato</a>
    </div>

    <script src="script.js?v=<?= (int) @filemtime(__DIR__ . '/script.js') ?>"></script>
    <?php nexus_demo_botao(); ?>
</body>
</html>
