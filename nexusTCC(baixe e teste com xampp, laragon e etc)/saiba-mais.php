<?php
/*
 * PÁGINA "SAIBA MAIS" (saiba-mais.php)
 * ------------------------------------------------------------------
 * Explica o evento: o que é o Nexus Tecno, no que acredita (propósito,
 * missão e visão), como funciona (da ideia à apresentação), para quem
 * é e como tirar dúvidas.
 *
 * Os textos editáveis passam por txt('chave'): valor salvo no painel
 * (dados/conteudo.json) ou, na falta dele, o padrão de fábrica
 * (inc/padroes.php). Toda saída é escapada com h().
 */
require_once __DIR__ . '/inc/dados.php';
nexus_cabecalhos_seguranca();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saiba Mais - Nexus</title>
    <link rel="stylesheet" href="assets/fonts/fonts.css">
    <link rel="stylesheet" href="style.css?v=<?= (int) @filemtime(__DIR__ . '/style.css') ?>">
    <link rel="shortcut icon" href="favicon/nexusFavicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Navegação -->
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
                <a href="tccs.php">TCCs</a>
                <a href="saiba-mais.php">Saiba Mais</a>
                <a href="#contato">Contato</a>
            </div>
            <button class="nav-toggle" id="navToggle" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="navLinks">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </button>
        </div>
    </nav>

    <!-- Hero interno -->
    <section class="page-hero">
        <div class="container">
            <div class="badge">SAIBA MAIS</div>
            <h1 data-edit="sm-hero-titulo"><?= h(txt('sm-hero-titulo')) ?></h1>
            <p data-edit="sm-hero-paragrafo"><?= h(txt('sm-hero-paragrafo')) ?></p>
            <div class="hero-mini-stats">
                <div class="hero-mini-stat">
                    <span class="hero-mini-stat-number" data-edit="sm-stat1-num"><?= h(txt('sm-stat1-num')) ?></span>
                    <span class="hero-mini-stat-label" data-edit="sm-stat1-label"><?= h(txt('sm-stat1-label')) ?></span>
                </div>
                <div class="hero-mini-stat-divider"></div>
                <div class="hero-mini-stat">
                    <span class="hero-mini-stat-number" data-edit="sm-stat2-num"><?= h(txt('sm-stat2-num')) ?></span>
                    <span class="hero-mini-stat-label" data-edit="sm-stat2-label"><?= h(txt('sm-stat2-label')) ?></span>
                </div>
                <div class="hero-mini-stat-divider"></div>
                <div class="hero-mini-stat">
                    <span class="hero-mini-stat-number" data-edit="sm-stat3-num"><?= h(txt('sm-stat3-num')) ?></span>
                    <span class="hero-mini-stat-label" data-edit="sm-stat3-label"><?= h(txt('sm-stat3-label')) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- O que é o Nexus -->
    <section class="about">
        <div class="container">
            <div class="about-image reveal-up"><img src="assets/images/estudantes-e-organizadores-reunidos-no-evento-nexus-tecno.jpg" alt=""></div>
            <div class="about-content reveal-up">
                <span class="badge">O EVENTO</span>
                <h2>O que é o Nexus Tecno</h2>
                <p>O NEXUS TECNO é uma iniciativa do Curso Técnico em Desenvolvimento de Sistemas do Colégio Estadual Arnaldo Busato (CEAB), promovida em conjunto por seu corpo docente e discente. Mais do que um evento de tecnologia, é um espaço de conexão entre conhecimento, inovação, educação e empreendedorismo.</p>
                <p>Nele, os estudantes apresentam projetos e produtos digitais criados ao longo da formação, mostrando competências técnicas, criatividade e a capacidade de transformar ideias em soluções para desafios reais. O evento também aproxima a escola do setor empresarial e da comunidade, com palestras de profissionais da área, parcerias e oportunidades de negócios.</p>
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/></svg></div>
                        <div>
                            <h4>Trabalho Real</h4>
                            <p>Projetos completos, do problema à solução</p>
                        </div>
                    </div>
                    <div class="feature">
                        <div class="feature-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L6 21l6-3 6 3-2.5-7.5"/></svg></div>
                        <div>
                            <h4>Avaliação Externa</h4>
                            <p>Feedback de professores e empresários</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Propósito, Missão, Visão e Valores -->
    <section class="essence" id="essencia">
        <div class="container">
            <div class="tccs-header reveal-up">
                <span class="badge">NOSSA ESSÊNCIA</span>
                <h2>No que o Nexus acredita</h2>
                <p>Nexus remete à conexão e à união de diferentes elementos. É essa a essência da iniciativa: conectar pessoas, ideias e oportunidades para construir, juntos, novas possibilidades por meio da tecnologia.</p>
            </div>

            <div class="essence-purpose reveal-up">
                <div class="essence-purpose-icon"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="#ffffff" stroke="none"/></svg></div>
                <div>
                    <span class="essence-label">Propósito</span>
                    <p class="essence-statement" data-edit="pmvv-proposito"><?= h(txt('pmvv-proposito')) ?></p>
                    <p class="essence-explain">É o motivo de o Nexus existir: dar às ideias dos estudantes um lugar para saírem da sala de aula e chegarem a quem pode usá-las.</p>
                </div>
            </div>

            <div class="essence-grid">
                <div class="essence-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21V4"/><path d="M5 4h12l-2.5 4L17 12H5"/></svg></div>
                    <span class="essence-label">Missão</span>
                    <p class="essence-statement" data-edit="pmvv-missao"><?= h(txt('pmvv-missao')) ?></p>
                    <p class="essence-explain">É o que fazemos a cada edição: reunir estudantes, professores, empresas e comunidade em torno de projetos reais.</p>
                </div>
                <div class="essence-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></div>
                    <span class="essence-label">Visão</span>
                    <p class="essence-statement" data-edit="pmvv-visao"><?= h(txt('pmvv-visao')) ?></p>
                    <p class="essence-explain">É aonde queremos chegar: que o Nexus seja reconhecido na região como a ponte entre a escola técnica e o mercado.</p>
                </div>
            </div>

            <div class="values-header reveal-up">
                <h3>Nossos valores</h3>
                <p>Os princípios que guiam a organização do evento e o trabalho de cada equipe.</p>
            </div>
            <div class="values-grid">
                <div class="value-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/></svg></div>
                    <h4>Inovação</h4>
                    <p>Buscar novas ideias e soluções para desafios reais.</p>
                </div>
                <div class="value-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5.5C4 4.5 7 4.5 12 7c5-2.5 8-2.5 10-1.5V19c-2-1-5-1-10 1.5C7 18 4 18 2 19z"/><line x1="12" y1="7" x2="12" y2="20.5"/></svg></div>
                    <h4>Conhecimento</h4>
                    <p>Valorizar a aprendizagem contínua e o desenvolvimento de competências.</p>
                </div>
                <div class="value-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.4"/><path d="M17 14c2.5 0 4.5 2 4.5 5"/></svg></div>
                    <h4>Colaboração</h4>
                    <p>Construir resultados por meio da cooperação entre escola, estudantes e comunidade.</p>
                </div>
                <div class="value-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c3 2 5 5 5 9 0 2-1 4-2 5H9c-1-1-2-3-2-5 0-4 2-7 5-9z"/><circle cx="12" cy="9.5" r="1.6"/><path d="M9 16l-2 4 3-1.5M15 16l2 4-3-1.5"/></svg></div>
                    <h4>Empreendedorismo</h4>
                    <p>Transformar ideias em oportunidades e projetos concretos.</p>
                </div>
                <div class="value-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z"/><polyline points="8.5 12 11 14.5 15.5 9.5"/></svg></div>
                    <h4>Ética e responsabilidade</h4>
                    <p>Desenvolver tecnologia com propósito, respeito e compromisso social.</p>
                </div>
                <div class="value-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.2 5.3L20 9l-4.3 3.7L17 19l-5-3.2L7 19l1.3-6.3L4 9l5.8-0.7z"/></svg></div>
                    <h4>Protagonismo</h4>
                    <p>Incentivar os estudantes a criar, experimentar e transformar o futuro.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Como funciona -->
    <section class="event">
        <div class="container" style="grid-template-columns: 1fr;">
            <div class="event-content reveal-up" style="width: 100%;">
                <div class="tccs-header reveal-up" style="margin-bottom: 3rem;">
                    <span class="badge">COMO FUNCIONA</span>
                    <h2>Da ideia à apresentação</h2>
                    <p>O caminho até a noite do Nexus passa por algumas etapas, sempre com acompanhamento de um professor orientador.</p>
                </div>
                <div class="event-details info-steps">
                    <div class="detail-item reveal-up">
                        <div class="detail-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="16" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="9" y1="14" x2="13" y2="14"/></svg></div>
                        <div class="detail-content">
                            <h4>Definição do Projeto</h4>
                            <p>Cada equipe escolhe um problema real e desenha uma solução técnica viável, orientada por um professor.</p>
                        </div>
                    </div>
                    <div class="detail-item reveal-up">
                        <div class="detail-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 6 3 12 8 18"/><polyline points="16 6 21 12 16 18"/></svg></div>
                        <div class="detail-content">
                            <h4>Desenvolvimento e Testes</h4>
                            <p>Meses de programação, protótipos e ajustes, com validações constantes ao longo do ano letivo.</p>
                        </div>
                    </div>
                    <div class="detail-item reveal-up">
                        <div class="detail-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="1.5"/><line x1="8" y1="20" x2="16" y2="20"/><line x1="12" y1="16" x2="12" y2="20"/></svg></div>
                        <div class="detail-content">
                            <h4>Noite de Apresentação</h4>
                            <p>No dia do evento, cada equipe apresenta o projeto para a banca, colegas, familiares e convidados, com palestras de profissionais da área.</p>
                        </div>
                    </div>
                    <div class="detail-item reveal-up">
                        <div class="detail-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8v4a4 4 0 0 1-8 0V4z"/><path d="M8 5H5a3 3 0 0 0 3 4"/><path d="M16 5h3a3 3 0 0 1-3 4"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="9" y1="19" x2="15" y2="19"/><line x1="12" y1="16" x2="12" y2="19"/></svg></div>
                        <div class="detail-content">
                            <h4>Reconhecimento</h4>
                            <p>Os projetos em destaque recebem reconhecimento da escola e da comunidade empresarial presente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Para quem é -->
    <section class="tccs">
        <div class="container">
            <div class="tccs-header reveal-up">
                <span class="badge">PÚBLICO</span>
                <h2>Para quem é o Nexus</h2>
                <p>Um evento pensado para aproximar diferentes pessoas em torno da mesma proposta: mostrar o que a tecnologia, na prática, é capaz de resolver.</p>
            </div>
            <div class="tccs-grid">
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-4 10 4-10 4-10-4z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/><line x1="22" y1="9" x2="22" y2="15"/></svg></div>
                    <h3>Estudantes</h3>
                    <p>Que querem apresentar o resultado de um ano de trabalho</p>
                </div>
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="1.5"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="11.5" x2="13" y2="11.5"/><line x1="12" y1="20" x2="12" y2="16"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div>
                    <h3>Professores e Escola</h3>
                    <p>Que acompanham e avaliam o desenvolvimento dos projetos</p>
                </div>
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="6" r="2.4"/><circle cx="5" cy="17" r="2.4"/><circle cx="19" cy="17" r="2.4"/><line x1="10.4" y1="7.8" x2="6.6" y2="15.2"/><line x1="13.6" y1="7.8" x2="17.4" y2="15.2"/></svg></div>
                    <h3>Comunidade</h3>
                    <p>Familiares e visitantes interessados em tecnologia</p>
                </div>
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="3" y1="12" x2="21" y2="12"/></svg></div>
                    <h3>Empresários e Parceiros</h3>
                    <p>Em busca de talentos e soluções inovadoras</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Ficou alguma dúvida?</h2>
            <p>Conheça os projetos que já estão em desenvolvimento ou fale diretamente com a organização do Nexus.</p>
            <div class="cta-buttons">
                <a href="tccs.php" class="btn btn-white">Ver os TCCs</a>
                <a href="#contato" class="btn btn-outline-white">Entre em Contato</a>
            </div>
        </div>
    </section>

    <!-- Rodapé -->
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

    <!-- Barra de CTA fixa (somente mobile) -->
    <div class="mobile-sticky-cta" id="mobileStickyCta">
        <a href="tccs.php" class="btn btn-primary">Ver os TCCs</a>
    </div>

    <script src="script.js?v=<?= (int) @filemtime(__DIR__ . '/script.js') ?>"></script>
    <?php nexus_demo_botao(); ?>
</body>
</html>
