<?php
requer_login();
$u = usuario_atual();
// GGO e admin podem acessar gestão de usuários
if (!in_array($u['perfil'], ['admin', 'ggo'])) {
    flash('Acesso restrito.', 'danger');
    redirect('/');
}

// Admin vê todos; GGO vê só usuários da sua cidade
if ($u['perfil'] === 'admin') {
    $todos = db()->query(
        'SELECT u.*, a.nome AS alm_nome, a.cidade AS alm_cidade
         FROM usuario u
         LEFT JOIN almoxarifado a ON a.id = u.almoxarifado_id
         ORDER BY u.nome'
    )->fetchAll();
} else {
    // GGO: filtrar por almoxarifados da sua cidade
    $cidade = ggo_cidade();
    $almIds = almoxarifados_permitidos_ids();
    if ($cidade && $almIds) {
        $ph = implode(',', array_fill(0, count($almIds), '?'));
        $st = db()->prepare(
            "SELECT u.*, a.nome AS alm_nome, a.cidade AS alm_cidade
             FROM usuario u
             LEFT JOIN almoxarifado a ON a.id = u.almoxarifado_id
             WHERE u.almoxarifado_id IN ($ph) OR u.almoxarifado_id IS NULL
             ORDER BY u.nome"
        );
        $st->execute($almIds);
        // Filtra também usuários sem almoxarifado mas da mesma cidade via acesso_extra
        $todosRaw = $st->fetchAll();
        // Inclui apenas os que têm almoxarifado da cidade ou são GGO/admin
        $todos = array_filter($todosRaw, function($uu) use ($almIds) {
            return !$uu['almoxarifado_id'] || in_array((int)$uu['almoxarifado_id'], $almIds);
        });
        $todos = array_values($todos);
    } else {
        $todos = [];
    }
}

$grupos = [
    'admin'             => ['label' => 'Admin',          'label_curto' => 'Admin',       'icone' => '👑',  'cor' => '#7c3aed', 'usuarios' => []],
    'ggo'               => ['label' => 'GGO',            'label_curto' => 'GGO',         'icone' => '🏙️',  'cor' => '#dc2626', 'usuarios' => []],
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

// GGO vê só almoxarifados da sua cidade; admin vê todos
$almoxarifados = ($u['perfil'] === 'admin')
    ? db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll()
    : (function() {
        $ids = almoxarifados_permitidos_ids();
        if (!$ids) return [];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $s = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");
        $s->execute($ids);
        return $s->fetchAll();
    })();

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
