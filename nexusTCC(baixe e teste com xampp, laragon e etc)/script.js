/*
 * ==============================================================================
 *  NEXUS: SCRIPT DO SITE PÚBLICO (script.js)
 * ------------------------------------------------------------------------------
 *  JavaScript puro (sem bibliotecas), carregado por todas as páginas públicas
 *  (index.php, tccs.php, saiba-mais.php e privacidade.php).
 *
 *  Organização
 *    - Cada recurso é uma função autoexecutável (IIFE) independente, para não
 *      criar variáveis globais.
 *    - Cada recurso começa verificando se os elementos de que precisa existem na
 *      página atual e, se não existirem, encerra em silêncio. Por isso o mesmo
 *      arquivo serve a todas as páginas.
 *    - Animações usam IntersectionObserver, com alternativa para navegadores sem
 *      suporte, e respeitam a navegação por teclado e por leitores de tela
 *      (foco devolvido ao fechar popups, atributos aria-*, tecla Esc).
 *
 *  Contrato com o PHP
 *    - window.NEXUS_EVENT_DATE_OVERRIDE: definida em index.php com a data do evento
 *      salva no painel; usada pelo cronômetro regressivo.
 *
 *  Seções
 *    1. Comportamentos comuns e vitrine de TCCs
 *    2. Página inicial: categorias, galeria e CTA móvel
 *    3. Cronômetro regressivo
 *    4. Galerias de cada edição
 *    5. Destaque do bloco de contato
 * ==============================================================================
 */

// ======================================================================
// COMPORTAMENTOS COMUNS (todas as páginas) E VITRINE DE TCCs (tccs.php)
// ======================================================================

// Revela elementos com a classe .reveal-up conforme entram na tela (usado em todas as páginas do site)
(function () {
    const items = Array.from(document.querySelectorAll('.reveal-up'));
    if (items.length === 0) return;

    if (!('IntersectionObserver' in window)) {
        items.forEach(el => el.classList.add('in-view'));
        return;
    }

    // Agrupa os itens pelo elemento pai para escalonar a animação entre irmãos
    const groups = new Map();
    items.forEach(el => {
        const parent = el.parentElement;
        if (!groups.has(parent)) groups.set(parent, []);
        groups.get(parent).push(el);
    });

    // Marca o instante do carregamento: elementos que já aparecem na tela
    // sem precisar rolar (ex.: estatísticas logo abaixo do hero) entram numa
    // fila única em cascata, começando um pouco depois do hero para não
    // animar tudo ao mesmo tempo. Quem só aparece depois de rolar mantém
    // o pequeno escalonamento entre irmãos, disparado no momento do scroll.
    const pageLoadTime = performance.now();
    const INITIAL_WINDOW_MS = 400;
    let initialBatchCount = 0;

    /**
     * Dispara a animação de entrada de um elemento .reveal-up.
     * Itens já visíveis no carregamento entram numa fila única em cascata; os demais
     * usam um pequeno atraso entre irmãos, contado no momento da rolagem.
     * @param {HTMLElement} el Elemento a revelar.
     */
    function reveal(el) {
        if (el.classList.contains('in-view')) return;
        const elapsed = performance.now() - pageLoadTime;
        let delay;

        if (elapsed < INITIAL_WINDOW_MS) {
            delay = 550 + Math.min(initialBatchCount, 8) * 100;
            initialBatchCount += 1;
        } else {
            const siblings = groups.get(el.parentElement) || [el];
            const idx = siblings.indexOf(el);
            delay = Math.min(idx, 6) * 90;
        }

        setTimeout(() => el.classList.add('in-view'), delay);
        observer.unobserve(el);
    }

    // threshold baixo (dispara com só 1% visível) e rootMargin levemente
    // negativo: a revelação só acontece quando o elemento realmente
    // começa a entrar na tela pela parte de baixo, então o usuário vê o
    // efeito de fade+subida acontecer. A rede de segurança abaixo cobre
    // o caso de rolagens muito bruscas que pulam o elemento por completo.
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) reveal(entry.target);
        });
    }, { threshold: 0.01, rootMargin: '0px 0px -8% 0px' });

    items.forEach(el => observer.observe(el));

    // Rede de segurança: em rolagens muito bruscas ou saltos instantâneos
    // (ex.: âncoras de navegação, teclas Home/End) o navegador às vezes não
    // chega a pintar um quadro em que o elemento esteja na tela, então o
    // observer nunca dispara. Uma checagem leve e debounced no scroll/resize
    // garante que nada fique preso em opacidade zero.
    let safetyTimer = null;
    /**
     * Rede de segurança (com debounce de 120 ms) que revela os itens que o
     * IntersectionObserver deixou passar, como em saltos de âncora ou rolagens bruscas.
     */
    function safetyCheck() {
        if (safetyTimer) return;
        safetyTimer = setTimeout(() => {
            safetyTimer = null;
            const vh = window.innerHeight;
            items.forEach(el => {
                if (el.classList.contains('in-view')) return;
                const rect = el.getBoundingClientRect();
                // Revela tudo cujo topo já tenha alcançado (ou passado) a
                // borda inferior da tela — inclui elementos ainda visíveis
                // E elementos que uma rolagem brusca já deixou para trás,
                // que de outra forma ficariam presos em opacidade zero.
                if (rect.top < vh) reveal(el);
            });
        }, 120);
    }
    window.addEventListener('scroll', safetyCheck, { passive: true });
    window.addEventListener('resize', safetyCheck);
    window.addEventListener('load', safetyCheck);
})();

// Botão "três pontinhos" do menu mobile: abre/fecha os links ao lado
(function () {
    const toggle = document.getElementById('navToggle');
    const links = document.getElementById('navLinks');
    if (!toggle || !links) return;

    /**
     * Fecha o menu mobile e atualiza o atributo aria-expanded do botão.
     */
    function closeMenu() {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    /**
     * Abre o menu mobile e atualiza o atributo aria-expanded do botão.
     */
    function openMenu() {
        links.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = links.classList.contains('is-open');
        isOpen ? closeMenu() : openMenu();
    });

    // Fecha ao clicar em um link do menu
    links.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    // Fecha ao clicar fora do menu
    document.addEventListener('click', (e) => {
        if (!links.classList.contains('is-open')) return;
        if (links.contains(e.target) || toggle.contains(e.target)) return;
        closeMenu();
    });

    // Fecha com a tecla Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && links.classList.contains('is-open')) {
            closeMenu();
            toggle.focus();
        }
    });

    // Fecha o menu automaticamente se a tela crescer para o layout de desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) closeMenu();
    });
})();

// No toque (celular), destaca os blocos de TCC conforme o dedo passa por
// cima durante o scroll, simulando o hover do mouse — sem precisar clicar,
// só para o usuário perceber que a funcionalidade existe.
(function () {
    const isTouch = window.matchMedia('(hover: none), (pointer: coarse)').matches;
    if (!isTouch || !('IntersectionObserver' in window)) return;

    const cards = document.querySelectorAll('.tcc-card, .tcc-showcase-card');
    if (cards.length === 0) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            entry.target.classList.toggle('touch-active', entry.isIntersecting);
        });
    }, {
        // Faixa fina no centro vertical da tela: o card "acende"
        // assim que o dedo/scroll passa por cima dele
        rootMargin: '-42% 0px -42% 0px',
        threshold: 0
    });

    cards.forEach(card => observer.observe(card));
})();

// Filtro de categorias na vitrine de TCCs (tccs.php)
(function () {
    const filterTags = document.querySelectorAll('.filter-tag');
    const tccCards = document.querySelectorAll('.tcc-showcase-card');
    if (filterTags.length === 0 || tccCards.length === 0) return;

    filterTags.forEach(tag => {
        tag.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');

            filterTags.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            tccCards.forEach(card => {
                const category = card.getAttribute('data-category');
                card.style.display = (filter === 'all' || category === filter) ? 'block' : 'none';
            });
        });
    });
})();

// Revela os cards de TCC (tccs.php) com animação escalonada ao entrar na tela
(function () {
    const cards = Array.from(document.querySelectorAll('.tcc-showcase-card'));
    if (cards.length === 0) return;

    if (!('IntersectionObserver' in window)) {
        cards.forEach(c => c.classList.add('in-view'));
        return;
    }

    const pageLoadTime = performance.now();
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const elapsed = performance.now() - pageLoadTime;
                const idx = cards.indexOf(el);
                // Cards já visíveis ao carregar esperam o cabeçalho terminar de animar
                const delay = (elapsed < 400 ? 600 : 0) + idx * 110;
                setTimeout(() => el.classList.add('in-view'), delay);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.15 });

    cards.forEach(c => observer.observe(c));
})();

// Popup com o conteúdo completo do TCC (tccs.php)
(function () {
    const overlay = document.getElementById('tccModalOverlay');
    const body = document.getElementById('tccModalBody');
    const closeBtn = document.getElementById('tccModalClose');
    const cards = document.querySelectorAll('.tcc-showcase-card');
    if (!overlay || !body || cards.length === 0) return;

    let lastFocused = null;

    /**
     * Abre o popup do projeto copiando a imagem e as informações do card clicado.
     * Guarda o elemento que tinha o foco para devolvê-lo ao fechar (acessibilidade).
     * @param {HTMLElement} card Card .tcc-showcase-card de origem.
     */
    function openModalFromCard(card) {
        const imageArea = card.querySelector('.tcc-card-image-area');
        const info = card.querySelector('.tcc-card-info');
        if (!info) return;

        body.innerHTML = '';
        if (imageArea) body.appendChild(imageArea.cloneNode(true));
        body.appendChild(info.cloneNode(true));

        lastFocused = document.activeElement;
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
    }

    /**
     * Fecha o popup do projeto, libera a rolagem da página e devolve o foco.
     */
    function closeModal() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => { body.innerHTML = ''; }, 300);
        if (lastFocused) lastFocused.focus();
    }

    cards.forEach(card => {
        const trigger = card.querySelector('.tcc-card-expand-btn');
        if (trigger) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                openModalFromCard(card);
            });
        }
    });

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeModal();
    });
})();

// Alternar entre imagens do TCC (setas no card e no popup) (tccs.php)
// Delegação em fase de captura: funciona também na cópia do card dentro do popup
// e impede que o clique na seta abra o zoom da imagem.
document.addEventListener('click', function (e) {
    const btn = e.target.closest ? e.target.closest('.tcc-img-nav') : null;
    if (!btn) return;
    e.stopPropagation();
    e.preventDefault();

    const area = btn.closest('.tcc-card-image-area');
    if (!area) return;
    let imagens;
    try { imagens = JSON.parse(area.getAttribute('data-images') || '[]'); } catch (err) { return; }
    if (!Array.isArray(imagens) || imagens.length < 2) return;

    const dir = parseInt(btn.getAttribute('data-dir'), 10) || 1;
    const atual = parseInt(area.getAttribute('data-index') || '0', 10) || 0;
    const novo = (atual + dir + imagens.length) % imagens.length;

    const img = area.querySelector('.tcc-card-image-area-img');
    if (img) {
        img.src = imagens[novo];
        img.alt = 'Captura de tela do projeto (' + (novo + 1) + ' de ' + imagens.length + ')';
    }
    area.setAttribute('data-index', String(novo));
    area.setAttribute('data-zoom-src', imagens[novo]);
    area.querySelectorAll('.tcc-img-dot').forEach(function (d, i) {
        d.classList.toggle('is-active', i === novo);
    });
}, true);

// Lightbox com a imagem do site/programa ampliada (tccs.php)
(function () {
    const lightbox = document.getElementById('tccImageLightbox');
    const lightboxImg = document.getElementById('tccImageLightboxImg');
    const closeBtn = document.getElementById('tccImageLightboxClose');
    // Só as imagens dentro da vitrine abrem o lightbox (não a cópia dentro do popup de projeto)
    const zoomables = document.querySelectorAll('.tcc-showcase-grid .tcc-image-zoomable');
    if (!lightbox || !lightboxImg || zoomables.length === 0) return;

    let lastFocused = null;

    /**
     * Abre o zoom da captura de tela do projeto.
     * @param {string} src URL da imagem ampliada.
     * @param {string} [alt] Texto alternativo da imagem.
     */
    function openLightbox(src, alt) {
        lightboxImg.src = src;
        lightboxImg.alt = alt || '';
        lastFocused = document.activeElement;
        lightbox.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
    }

    /**
     * Fecha o zoom da captura de tela e devolve o foco ao elemento de origem.
     */
    function closeLightbox() {
        lightbox.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => { lightboxImg.src = ''; }, 300);
        if (lastFocused) lastFocused.focus();
    }

    zoomables.forEach(area => {
        area.setAttribute('tabindex', '0');
        area.setAttribute('role', 'button');
        area.setAttribute('aria-label', 'Ampliar imagem do projeto');

        area.addEventListener('click', () => {
            openLightbox(area.getAttribute('data-zoom-src'), area.getAttribute('data-zoom-alt'));
        });
        area.addEventListener('keydown', (e) => {
            if (e.target !== area) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openLightbox(area.getAttribute('data-zoom-src'), area.getAttribute('data-zoom-alt'));
            }
        });
    });

    closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('is-open')) closeLightbox();
    });
})();

// ======================================================================
// PÁGINA INICIAL: categorias de TCCs, galeria de fotos e CTA fixo no mobile
// ======================================================================

// Expande as categorias extras na página inicial
(function () {
    const grid = document.querySelector('.tccs-grid');
    const button = document.querySelector('.tcc-more-button');
    if (!grid || !button) return;

    button.addEventListener('click', function () {
        const isExpanded = grid.classList.toggle('is-expanded');
        button.classList.toggle('is-expanded', isExpanded);
        button.setAttribute('aria-expanded', String(isExpanded));
        button.querySelector('span').textContent = isExpanded ? 'Menos categorias' : 'Mais categorias';
    });
})();

// Revela os cards da galeria conforme entram na tela
(function () {
    const cards = document.querySelectorAll('.monitor-card');
    if (!('IntersectionObserver' in window) || cards.length === 0) {
        cards.forEach(c => c.classList.add('in-view'));
        return;
    }
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, idx) => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const delay = (Array.from(el.parentElement.children).indexOf(el) % 6) * 70;
                setTimeout(() => el.classList.add('in-view'), delay);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.15 });
    cards.forEach(c => observer.observe(c));
})();

// Arrastar cada cartão individualmente com resistência elástica.
// O cartão original permanece no lugar; somente um proxy visual sobe para a camada superior.
(function () {
    const cards = document.querySelectorAll('.monitor-card');
    if (cards.length === 0) return;

    const MAX_X = 82;
    const MAX_Y = 56;
    const DRAG_THRESHOLD = 4;
    let activeProxyCleanup = null;

    /**
     * Efeito de resistência elástica: quanto mais se arrasta, menos o cartão anda,
     * aproximando-se de 'max' sem nunca ultrapassá-lo.
     * @param {number} delta Distância arrastada em pixels (pode ser negativa).
     * @param {number} max Deslocamento máximo permitido.
     * @returns {number} Deslocamento amortecido, com o mesmo sinal de 'delta'.
     */
    function rubberBand(delta, max) {
        const sign = delta < 0 ? -1 : 1;
        const distance = Math.abs(delta);
        return sign * max * (1 - 1 / (distance / max + 1));
    }

    cards.forEach((card) => {
        let dragging = false;
        let pointerId = null;
        let startX = 0;
        let startY = 0;
        let moved = false;
        let suppressClick = false;
        let dragProxy = null;
        let restoreTimer = null;

        /**
         * Remove a cópia visual (proxy) do cartão e restaura o cartão original,
         * cancelando qualquer retorno animado pendente.
         */
        function clearProxy() {
            if (restoreTimer !== null) {
                window.clearTimeout(restoreTimer);
                restoreTimer = null;
            }
            if (dragProxy) {
                dragProxy.remove();
                dragProxy = null;
            }
            card.style.removeProperty('visibility');
            card.style.removeProperty('transition');
            card.style.removeProperty('transform');
            if (activeProxyCleanup === clearProxy) activeProxyCleanup = null;
        }

        /**
         * Fixa o cartão na posição final (sem transição) antes de começar o arrasto.
         */
        function settleCardBeforeDrag() {
            card.classList.add('in-view');
            card.style.transition = 'none';
            card.style.transform = 'translateY(0)';
        }

        /**
         * Cria o proxy: um clone do cartão posicionado sobre o original, em camada
         * superior, que é o elemento que de fato se move durante o arrasto. O original fica oculto.
         */
        function createProxy() {
            if (activeProxyCleanup && activeProxyCleanup !== clearProxy) {
                activeProxyCleanup();
            }
            const rect = card.getBoundingClientRect();
            const computedStyle = getComputedStyle(card);
            const accent = computedStyle.getPropertyValue('--ch-accent').trim();
            const accentGlow = computedStyle.getPropertyValue('--ch-accent-glow').trim();

            dragProxy = card.cloneNode(true);
            dragProxy.classList.add('drag-proxy');
            dragProxy.style.position = 'absolute';
            dragProxy.style.left = `${rect.left + window.scrollX}px`;
            dragProxy.style.top = `${rect.top + window.scrollY}px`;
            dragProxy.style.width = `${rect.width}px`;
            dragProxy.style.height = `${rect.height}px`;
            dragProxy.style.margin = '0';
            dragProxy.style.zIndex = '40';
            dragProxy.style.pointerEvents = 'none';
            dragProxy.style.setProperty('--drag-x', '0px');
            dragProxy.style.setProperty('--drag-y', '0px');
            if (accent) dragProxy.style.setProperty('--ch-accent', accent);
            if (accentGlow) dragProxy.style.setProperty('--ch-accent-glow', accentGlow);
            document.body.appendChild(dragProxy);
            activeProxyCleanup = clearProxy;

            card.style.visibility = 'hidden';
            dragProxy.classList.add('is-dragging');
        }

        /**
         * Devolve o proxy ao lugar de origem.
         * @param {boolean} [immediate=false] Se verdadeiro, remove o proxy sem animação.
         */
        function returnProxy(immediate = false) {
            if (!dragProxy) return;
            if (immediate) {
                clearProxy();
                return;
            }
            dragProxy.classList.remove('is-dragging');
            dragProxy.style.setProperty('--drag-x', '0px');
            dragProxy.style.setProperty('--drag-y', '0px');
            restoreTimer = window.setTimeout(clearProxy, 700);
        }

        card.querySelectorAll('img').forEach((img) => {
            img.addEventListener('dragstart', (e) => e.preventDefault());
        });

        card.addEventListener('pointerdown', (e) => {
            // O efeito de arrastar com resistência elástica é só para
            // mouse (desktop). No toque, deixamos o navegador rolar a
            // tira de fotos normalmente, sem interferência do JS.
            if (e.pointerType !== 'mouse') return;
            if (e.button !== 0) return;

            clearProxy();
            settleCardBeforeDrag();
            dragging = true;
            moved = false;
            pointerId = e.pointerId;
            startX = e.clientX;
            startY = e.clientY;
            card.setPointerCapture(pointerId);
        });

        card.addEventListener('pointermove', (e) => {
            if (!dragging || e.pointerId !== pointerId) return;

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            if (Math.abs(dx) > DRAG_THRESHOLD || Math.abs(dy) > DRAG_THRESHOLD) {
                moved = true;
                suppressClick = true;
                e.preventDefault();
                if (!dragProxy) createProxy();
            }

            if (dragProxy) {
                dragProxy.style.setProperty('--drag-x', `${rubberBand(dx, MAX_X)}px`);
                dragProxy.style.setProperty('--drag-y', `${rubberBand(dy, MAX_Y)}px`);
            }
        }, { passive: false });

        /**
         * Encerra o arrasto (soltar ou cancelar o ponteiro): devolve o proxy e,
         * se houve movimento, suprime por 80 ms o clique que o navegador dispararia.
         * @param {PointerEvent} [e] Evento de pointerup ou pointercancel.
         */
        function release(e) {
            if (!dragging) return;
            if (e && e.pointerId !== pointerId) return;

            dragging = false;
            if (dragProxy) returnProxy(e && e.type === 'pointercancel');

            if (pointerId !== null) {
                try { card.releasePointerCapture(pointerId); } catch (err) { /* noop */ }
            }
            pointerId = null;

            if (moved) {
                window.setTimeout(() => { suppressClick = false; }, 80);
            }
        }

        card.addEventListener('pointerup', release);
        card.addEventListener('pointercancel', release);

        card.addEventListener('click', (e) => {
            if (!suppressClick) return;
            e.preventDefault();
            e.stopPropagation();
            suppressClick = false;
        }, true);
    });
})();

// Expandir foto + texto ao clicar em um card da galeria
(function () {
    const cards = Array.from(document.querySelectorAll('.monitor-card'));
    if (cards.length === 0) return;

    const lightbox = document.getElementById('galleryLightbox');
    const lbImage = document.getElementById('lightboxImage');
    const lbTitle = document.getElementById('lightboxTitle');
    const lbDescription = document.getElementById('lightboxDescription');
    const lbScanbar = document.getElementById('lightboxScanbar');
    const lbChannel = document.getElementById('lightboxChannel');
    const lbWhen = document.getElementById('lightboxWhen');
    const btnClose = document.getElementById('lightboxClose');
    const btnPrev = document.getElementById('lightboxPrev');
    const btnNext = document.getElementById('lightboxNext');
    const panel = lightbox.querySelector('.lightbox-panel');

    let currentIndex = 0;
    let lastFocused = null;

    /**
     * Monta o texto padrão do lightbox quando o card não traz 'data-legenda'.
     * @param {string} caption Legenda da foto.
     * @param {string} channelTitle Título da edição do evento.
     * @param {string} channelSub Subtítulo da edição (tem prioridade sobre o título).
     * @returns {string} Descrição completa exibida no lightbox.
     */
    function describeMoment(caption, channelTitle, channelSub) {
        const base = caption ? caption.replace(/\.$/, '') : 'Um momento do Nexus';
        const context = channelSub ? channelSub : channelTitle;
        return base + ' — um registro de ' + context + ', parte da coleção de bastidores que mostra a energia de quem viveu o Nexus por dentro, do preparo às apresentações finais.';
    }

    /**
     * Preenche o lightbox com os dados do card de índice 'index' (imagem, legenda,
     * edição e cores de destaque herdadas da edição) e atualiza o índice atual.
     * @param {number} index Posição do card em 'cards'.
     */
    function render(index) {
        const card = cards[index];
        const img = card.querySelector('.monitor-frame img');
        const captionEl = card.querySelector('.monitor-caption p');
        const scanbarEl = card.querySelector('.monitor-scanbar span:last-child');
        const channel = card.closest('.channel');
        const channelTitle = channel ? channel.querySelector('.channel-title')?.textContent.trim() : 'Nexus';
        const channelMeta = channel ? channel.querySelector('.channel-meta')?.textContent.replace('·', '').trim() : '';
        const channelSub = channel ? channel.querySelector('.channel-sub')?.textContent.trim() : '';
        const caption = captionEl ? captionEl.textContent.trim() : 'Momento Nexus';
        const accent = channel ? getComputedStyle(channel).getPropertyValue('--ch-accent').trim() : '#8b5cf6';
        const accentGlow = channel ? getComputedStyle(channel).getPropertyValue('--ch-accent-glow').trim() : 'rgba(139, 92, 246, 0.4)';

        lbImage.src = img ? img.src : '';
        lbImage.alt = caption;
        lbTitle.textContent = caption;
        lbDescription.textContent = card.dataset.legenda || describeMoment(caption, channelTitle, channelSub);
        lbScanbar.textContent = scanbarEl ? scanbarEl.textContent.trim() : 'NEXUS';
        lbChannel.textContent = channelTitle || 'Nexus';
        lbWhen.textContent = channelMeta || channelSub || '';
        panel.style.setProperty('--ch-accent', accent);
        panel.style.setProperty('--ch-accent-glow', accentGlow);

        currentIndex = index;
    }

    /**
     * Abre o lightbox da galeria no card informado.
     * @param {number} index Posição do card em 'cards'.
     */
    function openLightbox(index) {
        lastFocused = document.activeElement;
        render(index);
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        btnClose.focus();
    }

    /**
     * Fecha o lightbox da galeria e devolve o foco ao card de origem.
     */
    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocused) lastFocused.focus();
    }

    /**
     * Navega para a foto anterior ou seguinte, voltando ao início/fim (circular).
     * @param {number} delta -1 para anterior, +1 para seguinte.
     */
    function step(delta) {
        const next = (currentIndex + delta + cards.length) % cards.length;
        render(next);
    }

    cards.forEach((card, index) => {
        card.addEventListener('click', () => openLightbox(index));
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openLightbox(index);
            }
        });
        if (!card.hasAttribute('tabindex')) card.setAttribute('tabindex', '0');
    });

    btnClose.addEventListener('click', closeLightbox);
    btnPrev.addEventListener('click', () => step(-1));
    btnNext.addEventListener('click', () => step(1));

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('is-open')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') step(-1);
        if (e.key === 'ArrowRight') step(1);
    });
})();

// Barra de CTA fixa no mobile: some no hero (pra não competir com o botão
// que já está lá), aparece durante a rolagem e some de novo perto do
// rodapé, pra não tampar os links de contato
(function () {
    const bar = document.getElementById('mobileStickyCta');
    if (!bar) return;

    /**
     * Indica se a largura da tela é de celular (até 767 px).
     */
    const isMobile = () => window.matchMedia('(max-width: 767px)').matches;

    let nearFooter = false;
    const footer = document.querySelector('footer');
    if (footer && 'IntersectionObserver' in window) {
        const footerObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => { nearFooter = entry.isIntersecting; });
            updateVisibility();
        }, { rootMargin: '0px 0px -15% 0px' });
        footerObserver.observe(footer);
    }

    /**
     * Mostra a barra de CTA só no mobile, depois de rolar 60% da altura da tela
     * e enquanto o rodapé não está perto.
     */
    function updateVisibility() {
        if (!isMobile()) {
            bar.classList.remove('is-visible');
            return;
        }
        const pastHero = window.scrollY > window.innerHeight * 0.6;
        bar.classList.toggle('is-visible', pastHero && !nearFooter);
    }

    window.addEventListener('scroll', updateVisibility, { passive: true });
    window.addEventListener('resize', updateVisibility);
    updateVisibility();
})();

// ==================== CRONÔMETRO REGRESSIVO ====================
// Só existe na página inicial (hero). Nas demais páginas os elementos não
// existem, então saímos cedo para não travar o restante do script.js.
(function () {
    // A data vem do servidor: index.php define window.NEXUS_EVENT_DATE_OVERRIDE com a
    // data salva no painel. Se ela não existir, vale a data padrão abaixo (a mesma de
    // 'eventDate' em inc/padroes.php).
    // O evento acontece no horário de Brasília, então fixamos o offset -03:00 na
    // string antes de criar o Date. Assim a contagem regressiva bate igual para
    // qualquer visitante, não importa o fuso horário do navegador dele — e evita
    // depender de toLocaleString(), que não tem formato padronizado entre navegadores.
    const dataAlvoBruta = window.NEXUS_EVENT_DATE_OVERRIDE || "2026-11-25T23:59:59";
    const temOffset = /Z$|[+-]\d{2}:\d{2}$/.test(dataAlvoBruta);
    const dataAlvo = new Date(temOffset ? dataAlvoBruta : dataAlvoBruta + "-03:00");

    const diasEl = document.getElementById('dias');
    const horasEl = document.getElementById('horas');
    const minutosEl = document.getElementById('minutos');
    const segundosEl = document.getElementById('segundos');

    if (!diasEl || !horasEl || !minutosEl || !segundosEl) return;

    /**
     * Calcula o tempo restante até a data do evento e atualiza os quatro
     * campos (dias, horas, minutos, segundos). Depois do evento, mostra tudo zerado.
     */
    function atualizarCronometro() {
        const diferenca = dataAlvo - new Date();

        if (diferenca <= 0) {
            diasEl.textContent = "00";
            horasEl.textContent = "00";
            minutosEl.textContent = "00";
            segundosEl.textContent = "00";
            return;
        }

        const dias = Math.floor(diferenca / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferenca % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferenca % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferenca % (1000 * 60)) / 1000);

        diasEl.textContent = String(dias).padStart(2, '0');
        horasEl.textContent = String(horas).padStart(2, '0');
        minutosEl.textContent = String(minutos).padStart(2, '0');
        segundosEl.textContent = String(segundos).padStart(2, '0');
    }

    setInterval(atualizarCronometro, 1000);
    atualizarCronometro();
})();

// ======================================================================
// PÁGINA INICIAL: abrir e fechar as galerias de cada edição
// ======================================================================

// Alterna a exibição de cada evento: mantém as fotos ocultas até o clique
(function () {
    const teasers = document.querySelectorAll('.event-teaser');
    const closeButtons = document.querySelectorAll('.channel-close');

    /**
     * Revela, em cascata, os cards de foto de uma edição recém-aberta.
     * @param {HTMLElement} channel Elemento da edição (.channel).
     */
    function revealCards(channel) {
        channel.querySelectorAll('.monitor-card').forEach((card, idx) => {
            if (card.classList.contains('in-view')) return;
            const delay = (idx % 6) * 70;
            setTimeout(() => card.classList.add('in-view'), delay);
        });
    }

    /**
     * Expande a galeria de uma edição e rola a tela até ela.
     * @param {string} id ID do elemento da edição.
     */
    function openChannel(id) {
        const channel = document.getElementById(id);
        if (!channel) return;
        channel.classList.remove('channel-collapsed');
        revealCards(channel);
        const teaser = document.querySelector(`.event-teaser[data-target="${id}"]`);
        if (teaser) teaser.setAttribute('aria-expanded', 'true');
        channel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /**
     * Recolhe a galeria de uma edição e volta a rolagem para o card de entrada.
     * @param {string} id ID do elemento da edição.
     */
    function closeChannel(id) {
        const channel = document.getElementById(id);
        if (!channel) return;
        channel.classList.add('channel-collapsed');
        const teaser = document.querySelector(`.event-teaser[data-target="${id}"]`);
        if (teaser) {
            teaser.setAttribute('aria-expanded', 'false');
            teaser.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    teasers.forEach((teaser) => {
        teaser.addEventListener('click', () => openChannel(teaser.dataset.target));
    });

    closeButtons.forEach((btn) => {
        btn.addEventListener('click', () => closeChannel(btn.dataset.target));
    });
})();

// ======================================================================
// RODAPÉ: destaque do bloco de contato
// ======================================================================

// Destaque do bloco de contato no rodapé: qualquer link que leve até
// "#contato" (menu, botões "Entre em Contato", CTA fixo no mobile) faz o
// bloco de e-mail/telefone piscar por um instante ao chegar lá, pra ficar
// claro pro usuário por que ele foi jogado pro fim da página.
(function () {
    const contactInfo = document.getElementById('footerContatoInfo');
    if (!contactInfo) return;

    const triggers = document.querySelectorAll('a[href="#contato"], a[href$="#contato"]');
    if (triggers.length === 0) return;

    let hideTimer = null;

    /**
     * Faz o bloco de contato piscar. Reinicia a animação CSS caso o
     * usuário clique em vários links seguidos.
     */
    function highlightContact() {
        clearTimeout(hideTimer);
        contactInfo.classList.remove('footer-contato-flash');
        // Força reflow pra permitir reiniciar a animação em cliques seguidos
        void contactInfo.offsetWidth;
        contactInfo.classList.add('footer-contato-flash');
        hideTimer = setTimeout(() => {
            contactInfo.classList.remove('footer-contato-flash');
        }, 1700);
    }

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            // Pequeno atraso pra piscar já perto do fim da rolagem suave
            setTimeout(highlightContact, 350);
        });
    });
})();
