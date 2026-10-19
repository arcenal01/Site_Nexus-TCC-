<?php
/*
 * CONTA PÚBLICA DE DEMONSTRAÇÃO
 * ------------------------------------------------------------------
 * Este arquivo é versionado de propósito: a conta abaixo existe somente na
 * edição de demonstração (NEXUS_EDICAO=demo), é divulgada no README e não
 * dá acesso a nenhum dado real. Em produção este arquivo NÃO é lido:
 * as contas reais ficam em dados/senha.php, que nunca é versionado.
 *
 * O hash é Argon2id. Na demonstração nada é regravado (ver
 * nexus_hash_precisa_atualizar em inc/auth.php), então o arquivo é imutável.
 */
return ['usuarios' => [
    [
        'usuario' => 'demo',
        'hash'    => '$argon2id$v=19$m=65536,t=4,p=1$RG1hY2NsLnlCYlNDNDcxcw$TLM0rw9AsV/Coav4jY1Faz4DdmdL92c6WzO92d9lxR4',
    ],
]];
