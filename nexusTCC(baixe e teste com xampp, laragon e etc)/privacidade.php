<?php
/*
 * POLÍTICA DE PRIVACIDADE (NX-17)
 * ------------------------------------------------------------------
 * O contato exibido aqui é o mesmo e-mail do rodapé (editável no painel).
 * O prazo do log de auditoria vem de NEXUS_AUDITORIA_DIAS (inc/config.php),
 * então se o prazo mudar a política acompanha sozinha.
 */
require_once __DIR__ . '/inc/dados.php';
nexus_cabecalhos_seguranca();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - Nexus</title>
    <meta name="description" content="Como o site do Nexus Tecno trata nomes, fotos e dados de acesso: o que é publicado, por quê, por quanto tempo e como pedir a remoção.">
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
            <div class="badge">PRIVACIDADE</div>
            <h1>Política de Privacidade</h1>
            <p>Quais dados o site do Nexus mostra, por quê, por quanto tempo e como você pode pedir a remoção.</p>
        </div>
    </section>

    <!-- Texto da política -->
    <section class="policy">
        <div class="container">
            <article class="policy-body">
                <p class="policy-updated">Última atualização: outubro de 2026</p>

                <h2>1. Quem somos</h2>
                <p>O Nexus Tecno é uma iniciativa do Curso Técnico em Desenvolvimento de Sistemas do Colégio Estadual Arnaldo Busato (CEAB). Este site é um trabalho de conclusão de curso (TCC) e serve para divulgar os projetos apresentados no evento, as edições anteriores e o próprio curso.</p>
                <p>Em linhas gerais, os dados pessoais publicados aqui são tratados pelo curso/colégio para essa finalidade. Para qualquer assunto sobre privacidade, use o contato da seção 8.</p>

                <h2>2. O que o site mostra</h2>
                <ul>
                    <li><strong>Nomes de estudantes</strong> ligados aos TCCs que eles apresentaram (na página de TCCs).</li>
                    <li><strong>Fotos de edições do evento</strong>, na galeria e em outras seções, em que podem aparecer estudantes, organizadores e participantes.</li>
                    <li><strong>Logotipos e imagens dos projetos</strong>, enviados pelas equipes.</li>
                </ul>
                <p>Essas informações ficam abertas a qualquer pessoa que visite o site e podem ser encontradas por mecanismos de busca.</p>

                <h2>3. Para que usamos e em que nos baseamos</h2>
                <p>Mostramos nomes e fotos apenas para divulgar os trabalhos dos estudantes, o evento Nexus e o curso. A base é a <strong>autorização</strong> da própria pessoa ou, quando se tratar de estudante menor de 18 anos, de um dos pais ou do responsável legal (LGPD, Lei 13.709/2018, arts. 7º, I, e 14, §1º). Sem autorização, o nome ou a foto não deve ser publicado.</p>

                <h2>4. O que não coletamos de quem visita</h2>
                <ul>
                    <li>O site <strong>não tem cadastro, formulário, comentários nem newsletter</strong>: não pedimos nome, e-mail ou telefone de visitantes.</li>
                    <li>As páginas públicas <strong>não usam cookies</strong>, ferramentas de estatísticas, publicidade nem recursos de terceiros (as fontes e imagens são servidas pelo próprio site).</li>
                    <li>Como em qualquer site, o servidor de hospedagem pode guardar registros técnicos de acesso (como endereço IP, data e página acessada) para funcionamento e segurança. Eles são gerenciados pela empresa de hospedagem.</li>
                </ul>

                <h2>5. Área restrita de edição</h2>
                <p>O conteúdo do site é editado por poucas pessoas, em uma área restrita, protegida por usuário, senha e verificação em duas etapas. Nela, usamos um cookie de sessão estritamente necessário para manter a pessoa conectada, que não serve para rastrear visitantes.</p>
                <p>Para segurança e para saber quem alterou o quê, essa área registra, a cada ação, a data, o usuário, o endereço IP e o tipo de ação (por exemplo, entrar, salvar, enviar imagem). Senhas e códigos nunca são gravados. Esses registros são guardados por até <strong><?= (int)NEXUS_AUDITORIA_DIAS ?> dias</strong> e depois apagados.</p>

                <h2>6. Quanto tempo mantemos</h2>
                <p>Nomes e fotos permanecem no site enquanto houver autorização e o site estiver no ar. Se a autorização for retirada, o conteúdo é removido do site (cópias já guardadas por terceiros, como buscadores, podem demorar a desaparecer).</p>

                <h2>7. Seus direitos</h2>
                <p>Você (ou o responsável, se for menor de 18 anos) pode pedir, a qualquer momento e sem custo:</p>
                <ul>
                    <li>saber quais dados seus estão no site e ter acesso a eles;</li>
                    <li>corrigir um nome ou uma informação errada;</li>
                    <li><strong>remover seu nome ou sua foto</strong> do site;</li>
                    <li>retirar a autorização que deu antes;</li>
                    <li>receber informações sobre com quem os dados são compartilhados.</li>
                </ul>
                <p>Atendemos os pedidos o mais rápido possível. Se achar que algo não foi tratado corretamente, você também pode procurar a Autoridade Nacional de Proteção de Dados (ANPD).</p>

                <h2 id="contato-privacidade">8. Como falar com a gente</h2>
                <p>Envie seu pedido para o e-mail de contato do Nexus: <strong><?= h(preg_replace('/^\s*e-?mail:\s*/i', '', txt('contato-email'))) ?></strong>. Diga qual página ou foto está em questão para agilizarmos a remoção ou a correção.</p>

                <h2>9. Segurança</h2>
                <p>O site é servido por conexão segura (HTTPS), a edição é restrita e protegida por duas etapas e o código passou por uma auditoria de segurança. Nenhum sistema é totalmente imune, mas tomamos cuidado para proteger as informações publicadas e os registros da área de edição.</p>

                <h2>10. Mudanças nesta política</h2>
                <p>Se algo mudar (por exemplo, se o site passar a ter um formulário ou ferramenta de estatísticas), esta página será atualizada antes e a data no topo será alterada.</p>
            </article>
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

    <script src="script.js?v=<?= (int) @filemtime(__DIR__ . '/script.js') ?>"></script>
    <?php nexus_demo_botao(); ?>
</body>
</html>
