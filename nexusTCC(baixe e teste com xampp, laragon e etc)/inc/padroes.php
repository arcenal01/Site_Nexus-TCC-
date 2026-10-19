<?php
/*
 * CONTEÚDO ORIGINAL DO SITE (padrões de fábrica)
 * ------------------------------------------------------------------
 * É o conteúdo exibido enquanto ninguém editou nada no painel, e
 * também o que o botão "Restaurar original" devolve. As edições do
 * painel são guardadas à parte (dados/conteudo.json) e aplicadas por
 * cima deste arquivo (ver inc/dados.php).
 *
 * Estrutura do array retornado:
 *   content   textos editáveis, indexados por chave. O prefixo indica
 *             a página: idx- (index.php), sm- (saiba-mais.php), pmvv-
 *             (propósito, missão e visão) e contato- (rodapé).
 *   eventDate data e hora do evento, sem fuso. É interpretada como
 *             horário de Brasília (-03:00) pelo cronômetro (script.js).
 *   tccs      projetos da vitrine, no formato descrito mais abaixo.
 *
 * Normalmente não é preciso editar este arquivo: tudo é editável
 * pelo painel.
 */

declare(strict_types=1);

return [
    // Textos editáveis (chave => texto padrão). Impressos nas páginas por txt('chave').
    'content' => [
        'contato-email'       => 'Email: contato@exemplo.dev',
        'contato-telefone'    => 'Telefone: (XX) XXXX-XXXX',
        'idx-hero-paragrafo'  => 'Iniciativa do Curso Técnico em Desenvolvimento de Sistemas do Colégio Estadual Arnaldo Busato (CEAB), onde os estudantes apresentam seus TCCs à comunidade e aos empresários. Conectando conhecimento, tecnologia e oportunidades.',
        'idx-stat1-num'       => '50+',
        'idx-stat1-label'     => 'Projetos',
        'idx-stat2-num'       => '200+',
        'idx-stat2-label'     => 'Estudantes',
        'idx-stat3-num'       => '100+',
        'idx-stat3-label'     => 'Empresários',
        'idx-stat4-num'       => '2',
        'idx-stat4-label'     => 'Eventos',
        'idx-sobre-titulo'    => 'Sobre o Nexus',
        'idx-sobre-p1'        => 'O NEXUS TECNO é uma iniciativa do Curso Técnico em Desenvolvimento de Sistemas do Colégio Estadual Arnaldo Busato (CEAB), promovida em conjunto por docentes e estudantes. Mais do que um evento de tecnologia, é um espaço de conexão entre conhecimento, inovação, educação e empreendedorismo.',
        'idx-sobre-p2'        => 'Nele, os estudantes apresentam projetos e produtos digitais desenvolvidos ao longo da formação, aproximando a escola do setor empresarial e da comunidade por meio de palestras, parcerias e oportunidades de negócios.',
        'sm-hero-titulo'      => 'Saiba Mais Sobre o Nexus',
        'sm-hero-paragrafo'   => 'O Nexus nasceu para dar visibilidade a um trabalho que costuma ficar restrito à sala de aula: o TCC. Aqui você entende o propósito do evento, no que ele acredita, como funciona e quem faz parte dele.',
        'sm-stat1-num'        => '50+',
        'sm-stat1-label'      => 'Projetos',
        'sm-stat2-num'        => '200+',
        'sm-stat2-label'      => 'Estudantes',
        'sm-stat3-num'        => '2',
        'sm-stat3-label'      => 'Edições',
        'pmvv-proposito'      => 'Conectar conhecimento, tecnologia e empreendedorismo para transformar ideias em soluções digitais que gerem valor para a sociedade e impulsionem o desenvolvimento local.',
        'pmvv-missao'         => 'Promover a integração entre estudantes, educadores, empresas e comunidade, apresentando projetos tecnológicos e incentivando a criatividade, a inovação e o espírito empreendedor.',
        'pmvv-visao'          => 'Ser uma referência regional na integração entre educação tecnológica, mercado de trabalho e empreendedorismo, contribuindo para a formação de talentos e para o desenvolvimento de um ecossistema de inovação.',
    ],

    // Data e hora do evento (ISO 8601, sem fuso: vale o horário de Brasília).
    'eventDate' => '2026-11-25T23:59:59',

    // Projetos (TCCs) exibidos na vitrine. Cada item possui:
    //   id            identificador estável; as edições e ocultações do painel se
    //                 ligam a ele, então não deve ser reaproveitado
    //   title         nome do projeto
    //   category      'web' | 'redes' | 'software' (usada pelo filtro de tccs.php)
    //   categoryLabel rótulo exibido no card (ex.: 'Plataforma Web')
    //   image, logo   caminhos relativos em imagens-tcc/
    //   extraImages   (opcional) imagens adicionais; o card ganha setas para alternar
    //   description   resumo do projeto
    //   team          lista de integrantes, cada um com 'name' e 'role'
    //   tech          lista de tecnologias usadas
    //   purpose       finalidade do projeto
    // Nesta edição de demonstração os integrantes são nomes genéricos.
    'tccs' => [
        [
            'id'            => 'tcc-1',
            'title'         => 'Conecta',
            'category'      => 'web',
            'categoryLabel' => 'Plataforma Web',
            'image'         => 'imagens-tcc/conecta-preview.png',
            'logo'          => 'imagens-tcc/conecta-logo.png',
            'description'   => 'Plataforma on-line com objetivo de conexão entre profissionais prestadores de serviço e clientes.',
            'team'          => [
                ['name' => 'Integrante 1', 'role' => ''],
                ['name' => 'Integrante 2', 'role' => ''],
            ],
            'tech'          => [],
            'purpose'       => 'Conectar trabalhadores e freelancers a pessoas que precisam de serviços',
        ],
        [
            'id'            => 'tcc-2',
            'title'         => 'Líria',
            'category'      => 'web',
            'categoryLabel' => 'Plataforma Web',
            'image'         => 'imagens-tcc/liria-preview.png',
            'logo'          => 'imagens-tcc/liria-logo.png',
            'description'   => '',
            'team'          => [],
            'tech'          => [],
            'purpose'       => '',
        ],
        [
            'id'            => 'tcc-3',
            'title'         => 'Spartta Games',
            'category'      => 'software',
            'categoryLabel' => 'Sistema de Gestão',
            'image'         => 'imagens-tcc/spartta-preview.png',
            'logo'          => 'imagens-tcc/spartta-logo.png',
            'description'   => 'O Sparta Games é um sistema desktop completo feito em Delphi 12 para gerenciar uma loja física de jogos de videogame. No sistema vai poder cadastrar produtos (jogos e consoles), controlar o estoque, realizar vendas, gerenciar os clientes e gerar relatórios importantes, tudo de forma integrada e fácil de usar no dia a dia.',
            'team'          => [
                ['name' => 'Integrante 3', 'role' => ''],
                ['name' => 'Integrante 4', 'role' => ''],
            ],
            'tech'          => ['Delphi'],
            'purpose'       => '',
        ],
        [
            'id'            => 'tcc-4',
            'title'         => 'A.R.A.S',
            'category'      => 'web',
            'categoryLabel' => 'Plataforma Web',
            'image'         => 'imagens-tcc/aras-preview.png',
            'logo'          => 'imagens-tcc/aras-logo.png',
            'description'   => 'O A.R.A.S. (Automatic Resume Analysis System) é um sistema desenvolvido para automatizar a análise e triagem de currículos, utilizando Inteligência Artificial para comparar as informações dos candidatos com as características e requisitos de cargos disponíveis. O sistema identifica o cargo de maior compatibilidade e apresenta uma análise com similaridade, aptidão, status e justificativa, auxiliando no processo de seleção de candidatos.',
            'team'          => [
                ['name' => 'Integrante 5', 'role' => ''],
                ['name' => 'Integrante 6', 'role' => ''],
            ],
            'tech'          => [],
            'purpose'       => '',
        ],
        [
            'id'            => 'tcc-8',
            'title'         => 'FiberX',
            'category'      => 'redes',
            'categoryLabel' => 'Projeto de Redes',
            'image'         => 'imagens-tcc/fiberx-preview.png',
            'logo'          => 'imagens-tcc/fiberx-logo.jpg',
            // Imagens adicionais (a 1ª é a de 'image'). O card mostra setas para alternar.
            'extraImages'   => ['imagens-tcc/fiberx-interface.png'],
            'description'   => 'TCC de redes na parte de oficina mecânica de motos, para fins de organização, facilidade e segurança.',
            'team'          => [
                ['name' => 'Integrante 7', 'role' => ''],
                ['name' => 'Integrante 8', 'role' => ''],
            ],
            'tech'          => [],
            'purpose'       => '',
        ],
        [
            'id'            => 'tcc-9',
            'title'         => 'FabricRedes',
            'category'      => 'redes',
            'categoryLabel' => 'Projeto de Redes',
            'image'         => 'imagens-tcc/fabricredes-preview.png',
            'logo'          => 'imagens-tcc/fabricredes-logo.jpg',
            'description'   => 'TCC de redes sobre o mapeamento da rede da loja Disco Mania, apresentando os componentes utilizados, como cabos, switch, câmeras, sensores de movimento, servidor, entre outros.',
            'team'          => [
                ['name' => 'Integrante 9', 'role' => ''],
            ],
            'tech'          => [],
            'purpose'       => '',
        ],
        [
            'id'            => 'tcc-10',
            'title'         => 'Stock Manager',
            'category'      => 'software',
            'categoryLabel' => 'Sistema de Gestão',
            'image'         => 'imagens-tcc/stockmanager-preview.png',
            'logo'          => 'imagens-tcc/stockmanager-logo.png',
            'description'   => 'Stock Manager é um software desenvolvido para gerenciar o estoque de uma loja, permitindo controlar a quantidade de produtos disponíveis e emitir alertas quando o estoque estiver baixo. Além do controle de inventário, o sistema apresenta informações importantes, como o lucro líquido, o lucro bruto e a quantidade de produtos vendidos.',
            'team'          => [
                ['name' => 'Integrante 10', 'role' => ''],
                ['name' => 'Integrante 11', 'role' => ''],
            ],
            'tech'          => [],
            'purpose'       => '',
        ],
        [
            'id'            => 'tcc-11',
            'title'         => 'MedLink',
            'category'      => 'web',
            'categoryLabel' => 'Plataforma Web',
            'image'         => 'imagens-tcc/medlink-preview.png',
            'logo'          => 'imagens-tcc/medlink-logo.png',
            'description'   => 'O MedLink é uma plataforma de telemedicina desenvolvida para facilitar o contato entre pacientes e médicos. O sistema permite cadastro e gerenciamento de usuários, agendamento de consultas, videochamadas com câmera e áudio, chat, envio de orientações, histórico, avaliações e administração pelo gerenciador.',
            'team'          => [
                ['name' => 'Integrante 12', 'role' => ''],
                ['name' => 'Integrante 13', 'role' => ''],
            ],
            'tech'          => [],
            'purpose'       => '',
        ],
    ],
];
