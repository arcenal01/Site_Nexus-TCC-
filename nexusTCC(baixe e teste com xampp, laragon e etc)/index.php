<?php
/*
 * PÁGINA INICIAL (index.php)
 * ------------------------------------------------------------------
 * Página pública principal: navegação, hero com cronômetro regressivo,
 * estatísticas, sobre o evento, categorias de TCCs, linha do tempo,
 * galeria de fotos, detalhes do evento, chamada para ação e rodapé.
 *
 * A página é montada no servidor. Os textos editáveis passam por
 * txt('chave'), que devolve o valor salvo no painel (dados/conteudo.json)
 * ou, se não houver, o padrão de fábrica (inc/padroes.php). Toda saída
 * é escapada com h(). O atributo data-edit="chave" apenas indica qual
 * campo do painel alimenta cada texto; o JavaScript não o utiliza.
 *
 * O que é salvo no painel aparece aqui na visita seguinte, para todos.
 * Comportamento da página (animações, galeria, cronômetro): script.js.
 */
require_once __DIR__ . '/inc/dados.php';
nexus_cabecalhos_seguranca();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Tecno - Apresentação de TCCs do curso TDS</title>
    <link rel="stylesheet" href="assets/fonts/fonts.css">
        <link rel="stylesheet" href="style.css?v=<?= (int) @filemtime(__DIR__ . '/style.css') ?>">
    <link rel="shortcut icon" href="favicon/nexusFavicon.ico" type="image/x-icon">
</head>
<body>
    <!-- ===== 01. Navegação principal ===== -->
    <nav>
        <div class="container">
        <a href="index.php"> 
            <div class="logo">
                <div class="logo-icon">N</div>
                <span class="logo-text">Nexus</span>
            </div>
        </a>
            <div class="links" id="navLinks">
                <a href="#sobre">Sobre</a>
                <a href="saiba-mais.php">Saiba Mais</a>
                <a href="tccs.php">TCCs</a>
                <a href="#linhaDotempo">Linha Do Tempo</a>
                <a href="#galeria">Galeria</a>
                <a href="#evento">Evento</a>
                <a href="#contato">Contato</a>
            </div>
            <button class="nav-toggle" id="navToggle" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="navLinks">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </button>
        </div>
    </nav>

    <!-- ===== 02. Secao principal (Hero) ===== -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="badge">EVENTO ANUAL</div>
                <h1>Nexus</h1>
                <p data-edit="idx-hero-paragrafo"><?= h(txt('idx-hero-paragrafo')) ?></p>
                <div class="hero-buttons">
                    <a href="saiba-mais.php" class="btn btn-primary">Saiba Mais →</a>
                </div>
            </div>
            
            <!-- ----- 02.1. Cronometro regressivo ----- -->
            <div class="hero-image">
                <div class="timer-title">
                    <svg class="timer-title-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Contagem Regressiva</span>
                </div>
                <div class="timer-box">
                    <div class="timer-cross">
                        <div class="timer-item top">
                            <span class="timer-label">Dias</span>
                            <span class="timer-number" id="dias">00</span>
                        </div>
                        <div class="timer-item left">
                            <span class="timer-label">Segundos</span>
                            <span class="timer-number" id="segundos">00</span>
                        </div>
                        <div class="timer-item right">
                            <span class="timer-label">Horas</span>
                            <span class="timer-number" id="horas">00</span>
                        </div>
                        <div class="timer-item bottom">
                            <span class="timer-label">Minutos</span>
                            <span class="timer-number" id="minutos">00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== 03. Estatísticas do evento ===== -->
    <section class="stats">
        <div class="container">
            <div class="stat-item reveal-up">
                <div class="stat-number" data-edit="idx-stat1-num"><?= h(txt('idx-stat1-num')) ?></div>
                <div class="stat-label" data-edit="idx-stat1-label"><?= h(txt('idx-stat1-label')) ?></div>
            </div>
            <div class="stat-item reveal-up">
                <div class="stat-number" data-edit="idx-stat2-num"><?= h(txt('idx-stat2-num')) ?></div>
                <div class="stat-label" data-edit="idx-stat2-label"><?= h(txt('idx-stat2-label')) ?></div>
            </div>
            <div class="stat-item reveal-up">
                <div class="stat-number" data-edit="idx-stat3-num"><?= h(txt('idx-stat3-num')) ?></div>
                <div class="stat-label" data-edit="idx-stat3-label"><?= h(txt('idx-stat3-label')) ?></div>
            </div>
            <div class="stat-item reveal-up">
                <div class="stat-number" data-edit="idx-stat4-num"><?= h(txt('idx-stat4-num')) ?></div>
                <div class="stat-label" data-edit="idx-stat4-label"><?= h(txt('idx-stat4-label')) ?></div>
            </div>
        </div>
    </section>

    <!-- ===== 04. Sobre o evento ===== -->
    <section class="about" id="sobre">
        <div class="container">
            <div class="about-image reveal-up"><img src="assets/images/estudantes-e-organizadores-reunidos-no-evento-nexus-tecno.jpg" alt=""></div>
            <div class="about-content reveal-up">
                <span class="badge">SOBRE O EVENTO</span>
                <h2 data-edit="idx-sobre-titulo"><?= h(txt('idx-sobre-titulo')) ?></h2>
                <p data-edit="idx-sobre-p1"><?= h(txt('idx-sobre-p1')) ?></p>
                <p data-edit="idx-sobre-p2"><?= h(txt('idx-sobre-p2')) ?></p>
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2.5"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="5" y1="5" x2="7.8" y2="7.8"/><line x1="16.2" y1="16.2" x2="19" y2="19"/><line x1="5" y1="19" x2="7.8" y2="16.2"/><line x1="16.2" y1="7.8" x2="19" y2="5"/></svg></div>
                        <div>
                            <h4>Inovação</h4>
                            <p>Projetos criativos e inovadores</p>
                        </div>
                    </div>
                    <div class="feature">
                        <div class="feature-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="12" r="2.8"/><circle cx="19" cy="12" r="2.8"/><line x1="7.8" y1="12" x2="16.2" y2="12"/><circle cx="12" cy="12" r="1.4" fill="#3b82f6" stroke="none"/></svg></div>
                        <div>
                            <h4>Networking</h4>
                            <p>Conexões profissionais valiosas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== 04.1. Essência (resumo) ===== -->
    <section class="essence-teaser">
        <div class="container">
            <div class="essence-purpose reveal-up">
                <div class="essence-purpose-icon"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="#ffffff" stroke="none"/></svg></div>
                <div>
                    <span class="essence-label">Nosso propósito</span>
                    <p class="essence-statement" data-edit="pmvv-proposito"><?= h(txt('pmvv-proposito')) ?></p>
                    <a href="saiba-mais.php#essencia" class="btn btn-white">Conheça nossa missão, visão e valores →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== 05. Categorias de TCCs ===== -->
    <section class="tccs" id="tccs">
        <div class="container">
            <div class="tccs-header reveal-up">
                <span class="badge">CATEGORIAS</span>
                <h2>Categorias de TCCs</h2>
                <p>Conheça os diferentes tipos de projetos que serão apresentados no evento</p>
            </div>
            <div class="tccs-grid">
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7L3 12l5 5"/><path d="M16 7l5 5-5 5"/><circle cx="12" cy="4" r="1.4" fill="#8b5cf6" stroke="none"/></svg></div>
                    <h3>Desenvolvimento Web</h3>
                    <p>Aplicações web inovadoras e responsivas</p>
                </div>
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="10" height="10" rx="1.5"/><line x1="9" y1="2" x2="9" y2="7"/><line x1="15" y1="2" x2="15" y2="7"/><line x1="9" y1="17" x2="9" y2="22"/><line x1="15" y1="17" x2="15" y2="22"/><line x1="2" y1="9" x2="7" y2="9"/><line x1="2" y1="15" x2="7" y2="15"/><line x1="17" y1="9" x2="22" y2="9"/><line x1="17" y1="15" x2="22" y2="15"/></svg></div>
                    <h3>Sistemas Embarcados</h3>
                    <p>Soluções de hardware e software integrado</p>
                </div>
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 19c8 1 14-5 14-14-9 0-14 5-14 14z"/><path d="M5 19c2-4 5-7 9-9"/></svg></div>
                    <h3>Sustentabilidade</h3>
                    <p>Tecnologia aplicada ao meio ambiente</p>
                </div>
                <div class="tcc-card reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="18" r="1.6" fill="#3b82f6" stroke="none"/><path d="M8.5 14.5a5 5 0 0 1 7 0"/><path d="M5.5 11.5a9 9 0 0 1 13 0"/></svg></div>
                    <h3>IoT</h3>
                    <p>Internet das Coisas e conectividade</p>
                </div>
                <div class="tcc-card tcc-card-hidden reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="4" cy="19" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="20" cy="5" r="1.6"/><line x1="5.4" y1="17.6" x2="10.6" y2="13.4"/><line x1="13.4" y1="10.6" x2="18.6" y2="6.4"/><path d="M15 5h5v5"/></svg></div>
                    <h3>Inovação Digital</h3>
                    <p>Novas fronteiras da tecnologia</p>
                </div>
                <div class="tcc-card tcc-card-hidden reveal-up">
                    <div class="tcc-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20s-7-4.35-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5c-2.5 4.65-9.5 9-9.5 9z"/><path d="M6 12h2.5l1.5-2.5 2 5 1.5-2.5H16"/></svg></div>
                    <h3>Saúde & Bem-estar</h3>
                    <p>Tecnologia para qualidade de vida</p>
                </div>
            </div>
            <button class="btn btn-outline tcc-more-button" type="button" aria-expanded="false">
                <span>Mais categorias</span>
                <span aria-hidden="true">+</span>
            </button>
        </div>
    </section>

    <!-- ===== 06. Linha do tempo ===== -->
    <section class="timeline-section" id="linhaDotempo">
        <div class="container">
            <div class="timeline-header reveal-up">
                <span class="badge">LINHA DO TEMPO</span>
                <h2>Nossa Jornada</h2>
                <p>Acompanhe os principais marcos do evento Nexus</p>
            </div>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-year">2024</div>
                    <div class="point-circle">
                        <div class="point-content">
                            <div class="timeline-date">2024</div>
                            <div class="timeline-title-item"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="1.6" style="display:inline-block;vertical-align:-4px;margin-right:6px"><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="9" stroke-dasharray="2 3"/></svg>A Ideia e a 1ª Edição</div>
                            <div class="timeline-description">
                                O Nexus nasceu como ideia no curso TDS: um evento para os alunos apresentarem seus TCCs à comunidade. Em 2024 saiu do papel, com a 1ª edição no Núcleo Pato Branco.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-year">2025</div>
                    <div class="point-circle">
                        <div class="point-content">
                            <div class="timeline-date">10/12/2025</div>
                            <div class="timeline-title-item"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="1.6" style="display:inline-block;vertical-align:-4px;margin-right:6px"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="#3b82f6" stroke="none"/></svg>2ª Edição</div>
                            <div class="timeline-description">
                                O evento cresceu e levou os projetos para um público maior, com a comunidade e empresários na ACIVI, em Coronel Vivida.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-year">2026</div>
                    <div class="point-circle">
                        <div class="point-content">
                            <div class="timeline-date">2026</div>
                            <div class="timeline-title-item"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-4px;margin-right:6px"><path d="M12 3l2.2 5.3L20 9l-4.3 3.7L17 19l-5-3.2L7 19l1.3-6.3L4 9l5.8-0.7z"/></svg>3ª Edição</div>
                            <div class="timeline-description">
                                O Nexus já faz parte do calendário do curso. A nova turma prepara seus TCCs para a próxima edição, e este site é a vitrine do evento.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="timeline-item more-dots">
                    <div class="more-dots-text">...</div>
                    <div class="point-circle">
                        <div class="point-content">
                            <div class="timeline-date">Daqui para frente</div>
                            <div class="timeline-title-item"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" style="display:inline-block;vertical-align:-4px;margin-right:6px"><circle cx="4" cy="18" r="1.3" fill="#3b82f6" stroke="none"/><circle cx="11" cy="13" r="1.3" fill="#3b82f6" stroke="none"/><circle cx="18" cy="8" r="1.3" fill="#3b82f6" stroke="none"/><path d="M5.5 16.8l4-3" stroke-dasharray="1 2.4"/><path d="M12.5 11.8l4-3" stroke-dasharray="1 2.4"/></svg>O Nexus segue</div>
                            <div class="timeline-description">
                                Novas turmas, novos projetos e novas edições a cada ano.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== 07. Galeria: painel de transmissão ===== -->
    <section class="backstage" id="galeria">
        <div class="container">
            <div class="backstage-header reveal-up">
                <span class="live-tag"><span class="dot"></span>REGISTROS NEXUS</span>
                <h2>Bastidores das Edições</h2>
                <p>Cada apresentação, cada equipe, cada conquista — os melhores momentos de quem já viveu o Nexus por dentro.</p>
            </div>

            <div class="event-picker">
                <button class="event-teaser reveal-up" type="button" data-target="channel-01" aria-expanded="false" style="--ch-accent: #22d3ee; --ch-accent-glow: rgba(34, 211, 238, 0.4);">
                    <div class="event-teaser-visual">
                        <span class="event-teaser-number">1ª EDIÇÃO</span>
                        <span class="event-teaser-icon">📸</span>
                    </div>
                    <div class="event-teaser-info">
                        <h3>Nexus Tecno</h3>
                        <p>1ª edição — onde tudo começou · Núcleo Pato Branco</p>
                        <span class="event-teaser-cta">
                            Confira as fotos
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    </div>
                </button>
                <button class="event-teaser reveal-up" type="button" data-target="channel-02" aria-expanded="false" style="--ch-accent: #c084fc; --ch-accent-glow: rgba(192, 132, 252, 0.4);">
                    <div class="event-teaser-visual">
                        <span class="event-teaser-number">2ª EDIÇÃO</span>
                        <span class="event-teaser-icon">📸</span>
                    </div>
                    <div class="event-teaser-info">
                        <h3>Nexus Tecno 2</h3>
                        <p>2ª edição — a comunidade em peso · 10/12/2025, ACIVI — Coronel Vivida</p>
                        <span class="event-teaser-cta">
                            Confira as fotos
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    </div>
                </button>
            </div>

            <div class="channel channel-collapsed" id="channel-01" style="--ch-accent: #22d3ee; --ch-accent-glow: rgba(34, 211, 238, 0.4);">
                <div class="channel-header">
                    <span class="channel-number">CANAL 01</span>
                    <span class="channel-title">Nexus Tecno</span>
                    <span class="channel-meta">· Núcleo Pato Branco</span>
                    <button class="channel-close" type="button" data-target="channel-01">Ocultar fotos</button>
                </div>
                <p class="channel-sub">1ª edição — onde tudo começou</p>
                <div class="filmstrip">
                    <div class="monitor-card" data-card data-legenda="Alunos e organizadores reunidos na 1ª edição do Nexus Tecno: o registro de quem fez o evento sair do papel.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·01 01/07</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/estudantes-e-organizadores-reunidos-no-evento-nexus-tecno.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Grupo de alunos e organizadores reunidos</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="A equipe do Nexus Tecno posa diante do telão que dá nome ao evento, um encontro de projetos de tecnologia e networking.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·01 02/07</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/equipe-em-frente-ao-telao-principal.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Equipe em frente ao telão principal</p></div>
                    </div>
                    
                    <div class="monitor-card" data-card data-legenda="Um aluno sobe ao palco para apresentar o site de uma clínica veterinária, pensado para aproximar a clínica dos tutores.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·01 04/07</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/apresentacao-do-projeto-de-firewall.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Apresentação do projeto de firewall</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="Uma aluna apresenta o e-commerce da Farmácia Coperfarma, uma vitrine virtual criada para deixar o acesso dos clientes e as compras muito mais simples.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·01 05/07</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/organizadora-conduzindo-a-abertura.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Organizadora conduzindo a abertura</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="Na sequência, ela mostra como a loja funciona por dentro: construída em WordPress, reúne carrinho, controle de estoque e pagamento digital, com páginas de Medicamentos e Beleza e Higiene.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·01 06/07</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/participante-discursando-para-o-publico.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Participante discursando para o público</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="Os crachás de organização da equipe que trabalhou para o Nexus Tecno acontecer.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·01 07/07</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/crachas-da-equipe-organizadora.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Crachás da equipe organizadora</p></div>
                    </div>
                </div>
            </div>

            <div class="channel channel-collapsed" id="channel-02" style="--ch-accent: #c084fc; --ch-accent-glow: rgba(192, 132, 252, 0.4);">
                <div class="channel-header">
                    <span class="channel-number">CANAL 02</span>
                    <span class="channel-title">Nexus Tecno 2</span>
                    <span class="channel-meta">· 10/12/2025, ACIVI — Coronel Vivida</span>
                    <button class="channel-close" type="button" data-target="channel-02">Ocultar fotos</button>
                </div>
                <p class="channel-sub">2ª edição — a comunidade em peso</p>
                <div class="filmstrip">
                    <div class="monitor-card" data-card data-legenda="É dada a largada: começa o Nexus Tecno 2, na ACIVI, em Coronel Vivida.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 01/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/abertura-oficial-na-acivi.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Abertura oficial na ACIVI</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="Hora de receber os kits do evento: participantes do Nexus Tecno 2 posam com as lembranças.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 02/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/entrega-de-kits-aos-participantes.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Entrega de kits aos participantes</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="Da Inteligência Artificial à Sociedade 5.0: a palestra que levou ao público do Nexus Tecno 2 uma conversa sobre tecnologia e o futuro.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 03/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/palestra-da-ia-a-sociedade-50.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Palestra: Da IA à Sociedade 5.0</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="Projeto TDS-404: um protótipo de embarcação semi-automatizada com Arduino e propulsão elétrica, capaz de reagir sozinho a chuva, infiltração de água, fumaça e obstáculos.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 04/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/projeto-de-barco-com-sensores.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Projeto de barco com sensores</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="A equipe por trás do site responsivo de um restaurante, com cardápio de pizzas e carrinho de compras, criado para divulgar os produtos e ampliar o alcance do negócio.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 05/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/equipe-do-e-commerce-de-pizzas.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Equipe do e-commerce de pizzas</p></div>
                    </div>
                    
                    <div class="monitor-card" data-card data-legenda="O Arduino Uno em destaque: a placa que controla a embarcação do TDS-404 e junta eletrônica, programação e automação em um só projeto.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 07/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/prototipo-com-arduino-uno.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Protótipo com Arduino Uno</p></div>
                    </div>
                    
                    <div class="monitor-card" data-card data-legenda="Dois alunos que também iriam apresentar seus projetos acompanham as apresentações, esperando a própria vez de subir ao palco no Nexus Tecno 2.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 09/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/plateia-acompanhando-as-defesas.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Plateia acompanhando as defesas</p></div>
                    </div>
                    <div class="monitor-card" data-card data-legenda="Mais uma palestra no Nexus Tecno 2, mostrando que o Nexus é um evento de projetos de tecnologia e networking, feito para aproximar os alunos e a comunidade.">
                        <div class="monitor-scanbar"><span class="rec-dot"></span><span>NEXUS·02 10/10</span></div>
                        <div class="monitor-frame">
                            <img src="assets/images/encerramento-da-noite.jpg" alt="">
                        </div>
                        <div class="monitor-caption"><p>Encerramento da noite</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== 08. Galeria: painel expandido (lightbox) ===== -->
    <div class="gallery-lightbox" id="galleryLightbox" aria-hidden="true">
        <div class="lightbox-panel" role="dialog" aria-modal="true" aria-labelledby="lightboxTitle">
            <button class="lightbox-close" type="button" id="lightboxClose" aria-label="Fechar">&times;</button>
            <div class="lightbox-media">
                <button class="lightbox-nav lightbox-prev" type="button" id="lightboxPrev" aria-label="Foto anterior">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <img id="lightboxImage" src="" alt="">
                <button class="lightbox-nav lightbox-next" type="button" id="lightboxNext" aria-label="Próxima foto">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
            <div class="lightbox-info">
                <span class="lightbox-scanbar"><span class="rec-dot"></span><span id="lightboxScanbar">NEXUS</span></span>
                <h3 id="lightboxTitle">Título</h3>
                <p id="lightboxDescription">Descrição.</p>
                <div class="lightbox-meta">
                    <span id="lightboxChannel">Canal</span>
                    <span id="lightboxWhen">Data</span>
                </div>
            </div>
        </div>
    </div>
    <!-- ===== 09. Detalhes do evento ===== -->
    <section class="event" id="evento">
        <div class="container">
            <div class="event-content reveal-up">
                <span class="badge">O EVENTO</span>
                <h2>Detalhes do Evento</h2>
                <div class="event-details">
                    <div class="detail-item reveal-up">
                        <div class="detail-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="16" y1="3" x2="16" y2="7"/></svg></div>
                        <div class="detail-content">
                            <h4>Data</h4>
                            <p>25 de novembro de 2026</p>
                        </div>
                    </div>
                    <div class="detail-item reveal-up">
                        <div class="detail-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8v5a4 4 0 0 1-8 0V4z"/><path d="M8 5H5a3 3 0 0 0 3 4"/><path d="M16 5h3a3 3 0 0 1-3 4"/><line x1="12" y1="13" x2="12" y2="17"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="17" x2="12" y2="20"/></svg></div>
                        <div class="detail-content">
                            <h4>Reconhecimento</h4>
                            <p>Projetos reconhecidos pela comunidade empresarial</p>
                        </div>
                    </div>
                    <div class="detail-item reveal-up">
                        <div class="detail-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="6" r="2.4"/><circle cx="5" cy="17" r="2.4"/><circle cx="19" cy="17" r="2.4"/><line x1="10.4" y1="7.8" x2="6.6" y2="15.2"/><line x1="13.6" y1="7.8" x2="17.4" y2="15.2"/></svg></div>
                        <div class="detail-content">
                            <h4>Público</h4>
                            <p>Comunidade, empresários e profissionais</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="event-image reveal-up"><img src="assets/images/crachas-da-equipe-organizadora.jpg" alt=""></div>
        </div>
    </section>

    <!-- ===== 10. Chamada para ação (CTA) ===== -->
    <section class="cta">
        <div class="container">
            <h2 class="reveal-up">Quer fazer parte do TDS?</h2>
            <p class="reveal-up">Conheça o Curso Técnico em Desenvolvimento de Sistemas do CEAB e veja de perto o que nossos estudantes são capazes de criar.</p>
            <div class="cta-buttons reveal-up">
                <a href="#contato" class="btn btn-white">Entre em Contato</a>
                <a href="saiba-mais.php" class="btn btn-outline-white">Mais Informações</a>
            </div>
        </div>
    </section>

    <!-- ===== 11. Rodapé e contato ===== -->
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
                        <a href="#sobre">Sobre</a>
                        <a href="saiba-mais.php">Saiba Mais</a>
                        <a href="#tccs">TCCs</a>
                        <a href="#linhaDotempo">Timeline</a>
                        <a href="#galeria">Galeria</a>
                        <a href="#evento">Evento</a>
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

    <!-- ===== 12. Barra de CTA fixa (somente mobile) ===== -->
    <div class="mobile-sticky-cta" id="mobileStickyCta">
        <a href="#contato" class="btn btn-primary">Entre em Contato</a>
    </div>

    <!-- ===== 13. Scripts e interações da página ===== -->
    <script nonce="<?= h(nexus_nonce()) ?>">
        /* Data do evento definida no painel (usada na contagem regressiva) */
        window.NEXUS_EVENT_DATE_OVERRIDE = <?= json_encode(nexus_data_evento()) ?>;
    </script>
    <script src="script.js?v=<?= (int) @filemtime(__DIR__ . '/script.js') ?>"></script>
    <?php nexus_demo_botao(); ?>
</body>
</html>