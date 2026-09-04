<?php
requer_admin();

$todos = db()->query(
    'SELECT u.*, a.nome AS alm_nome FROM usuario u
     LEFT JOIN almoxarifado a ON a.id = u.almoxarifado_id
     ORDER BY u.nome'
)->fetchAll();

$grupos = [
    'admin'             => ['label' => 'Admin',          'label_curto' => 'Admin',       'icone' => '👑',  'cor' => '#7c3aed', 'usuarios' => []],
    'almoxarife'        => ['label' => 'Almoxarife',     'label_curto' => 'Almoxarife',  'icone' => '📦',  'cor' => '#ff6b35', 'usuarios' => []],
    'mestre'            => ['label' => 'Mestre de Obra', 'label_curto' => 'Mestre',      'icone' => '🦺',  'cor' => '#f0a500', 'usuarios' => []],
    'tecnico_seguranca' => ['label' => 'Téc. Segurança', 'label_curto' => 'Tec. Seg.',   'icone' => '🔒',  'cor' => '#059669', 'usuarios' => []],
    'analista'          => ['label' => 'Analista',       'label_curto' => 'Analista',    'icone' => '📊',  'cor' => '#2563eb', 'usuarios' => []],
    'colaborador'       => ['label' => 'Colaborador',    'label_curto' => 'Colaborador', 'icone' => '👔',  'cor' => '#64748b', 'usuarios' => []],
    'engenheiro'        => ['label' => 'Engenheiro',     'label_curto' => 'Engenheiro',  'icone' => '⚙️',  'cor' => '#0891b2', 'usuarios' => []],
];

foreach ($todos as $u2) {
    $perfil = $u2['perfil'];
    if (!isset($grupos[$perfil])) $perfil = 'colaborador';
    $grupos[$perfil]['usuarios'][] = $u2;
}

$almoxarifados  = db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll();
$permissoesDisp = [
    'fazer_requisicao' => 'Fazer Requisições',
    'ver_relatorios'   => 'Ver Relatórios',
    'ver_alertas'      => 'Ver Alertas',
];

$pageTitle  = 'Usuários';
$activeMenu = 'usuarios';
ob_start();
require VIEWS_PATH . '/usuarios/index.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
