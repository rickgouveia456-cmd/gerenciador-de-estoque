<?php
/**
 * Logi-Prime — Router simples
 * Mapeia URI -> controller
 */

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove query string e normaliza
$uri = rtrim($uri, '/') ?: '/';

// Mapa de rotas: [metodo, padrao_regex, arquivo_controller, params_capturados]
$routes = [
    // Auth
    ['GET|POST', '#^/login$#',  'auth/login.php',  []],
    ['POST',     '#^/logout$#', 'auth/logout.php', []],

    // Dashboard
    ['GET', '#^/$#',         'dashboard.php', []],
    ['GET', '#^/dashboard$#','dashboard.php', []],

    // Almoxarifados
    ['GET',      '#^/almoxarifado$#',                              'almoxarifado/index.php',   []],
    ['GET|POST', '#^/almoxarifado/novo$#',                         'almoxarifado/novo.php',    []],
    ['GET',      '#^/almoxarifado/(\d+)$#',                        'almoxarifado/show.php',    ['id']],
    ['GET|POST', '#^/almoxarifado/(\d+)/editar$#',                 'almoxarifado/editar.php',  ['id']],
    ['POST',     '#^/almoxarifado/(\d+)/deletar$#',                'almoxarifado/deletar.php', ['id']],
    ['GET',      '#^/almoxarifado/(\d+)/exportar$#',               'almoxarifado/exportar.php',['id']],
    ['GET|POST', '#^/almoxarifado/(\d+)/importar$#',               'almoxarifado/importar.php',['id']],
    ['GET',      '#^/almoxarifado/(\d+)/modelo_excel$#',           'almoxarifado/modelo_excel.php',['id']],
    ['POST',     '#^/almoxarifado/transferir$#',                   'almoxarifado/transferir.php', []],

    // Itens
    ['GET|POST', '#^/item/novo$#',                          'itens/novo.php',             []],
    ['GET',      '#^/item/(\d+)$#',                         'itens/show.php',             ['id']],
    ['GET|POST', '#^/item/(\d+)/editar$#',                  'itens/editar.php',           ['id']],
    ['POST',     '#^/item/(\d+)/deletar$#',                 'itens/deletar.php',          ['id']],
    ['POST',     '#^/item/(\d+)/desativar$#',               'itens/desativar.php',        ['id']],
    ['POST',     '#^/item/(\d+)/reativar$#',                'itens/reativar.php',         ['id']],
    ['POST',     '#^/item/(\d+)/movimentar$#',              'itens/movimentar.php',       ['id']],
    ['POST',     '#^/item/(\d+)/fixar$#',                   'itens/fixar.php',            ['id']],
    ['POST',     '#^/item/(\d+)/status_compra$#',           'itens/status_compra.php',    ['id']],
    ['GET|POST', '#^/movimentacao/lote$#',                  'itens/movimentacao_lote.php',[]],
    ['POST',     '#^/movimentacao/(\d+)/devolvido$#',       'itens/marcar_devolvido.php', ['id']],

    // Requisicoes simples
    ['GET',      '#^/requisicoes$#',                    'requisicoes/index.php',  []],
    ['GET|POST', '#^/requisicoes/nova$#',               'requisicoes/nova.php',   []],
    ['POST',     '#^/requisicoes/(\d+)/devolver$#',     'requisicoes/devolver.php',['id']],

    // Requisicoes mestre
    ['GET',      '#^/requisicoes/mestre$#',                     'requisicoes/mestre_index.php',   []],
    ['GET|POST', '#^/requisicoes/mestre/nova$#',                'requisicoes/mestre_nova.php',    []],
    ['GET',      '#^/requisicoes/mestre/(\d+)$#',               'requisicoes/mestre_detalhe.php', ['id']],
    ['GET|POST', '#^/requisicoes/mestre/(\d+)/editar$#',        'requisicoes/mestre_editar.php',  ['id']],
    ['POST',     '#^/requisicoes/mestre/(\d+)/aprovar$#',       'requisicoes/mestre_aprovar.php', ['id']],
    ['POST',     '#^/requisicoes/mestre/(\d+)/entregar$#',      'requisicoes/mestre_entregar.php',['id']],

    // Ferramentas
    ['GET',      '#^/ferramentas$#',                        'ferramentas/index.php',  []],
    ['GET|POST', '#^/ferramentas/nova$#',                   'ferramentas/nova.php',   []],
    ['GET',      '#^/ferramentas/(\d+)$#',                  'ferramentas/show.php',   ['id']],
    ['GET|POST', '#^/ferramentas/(\d+)/editar$#',           'ferramentas/editar.php', ['id']],
    ['POST',     '#^/ferramentas/(\d+)/usar$#',             'ferramentas/usar.php',   ['id']],
    ['POST',     '#^/ferramentas/(\d+)/devolver$#',         'ferramentas/devolver.php',['id']],
    ['POST',     '#^/ferramentas/(\d+)/manutencao$#',       'ferramentas/manutencao.php',['id']],

    // EPIs
    ['GET',      '#^/epis$#',                           'epis/index.php',  []],
    ['GET|POST', '#^/epis/novo$#',                      'epis/novo.php',   []],
    ['GET',      '#^/epis/(\d+)$#',                     'epis/show.php',   ['id']],
    ['GET|POST', '#^/epis/(\d+)/editar$#',              'epis/editar.php', ['id']],
    ['POST',     '#^/epis/(\d+)/usar$#',                'epis/usar.php',   ['id']],
    ['POST',     '#^/epis/(\d+)/devolver$#',            'epis/devolver.php',['id']],
    ['GET',      '#^/ficha_epi/(\d+)$#',                'epis/ficha.php',  ['colaborador_id']],
    ['GET',      '#^/epi_modulo$#',                     'epis/modulo.php', []],

    // Colaboradores
    ['GET',      '#^/colaboradores$#',          'colaboradores/index.php',  []],
    ['GET|POST', '#^/colaboradores/novo$#',     'colaboradores/novo.php',   []],
    ['GET|POST', '#^/colaboradores/(\d+)/editar$#','colaboradores/editar.php',['id']],
    ['POST',     '#^/colaboradores/(\d+)/deletar$#','colaboradores/deletar.php',['id']],

    // Usuarios
    ['GET',      '#^/usuarios$#',               'usuarios/index.php',  []],
    ['GET|POST', '#^/usuarios/novo$#',          'usuarios/novo.php',   []],
    ['GET|POST', '#^/usuarios/(\d+)/editar$#',  'usuarios/editar.php', ['id']],
    ['POST',     '#^/usuarios/(\d+)/deletar$#', 'usuarios/deletar.php',['id']],
    ['POST',     '#^/usuarios/(\d+)/acesso$#',  'usuarios/acesso.php', ['id']],

    // Catalogo
    ['GET',      '#^/catalogo$#',                   'catalogo/index.php',   []],
    ['GET|POST', '#^/catalogo/novo$#',              'catalogo/novo.php',    []],
    ['GET|POST', '#^/catalogo/(\d+)/editar$#',      'catalogo/editar.php',  ['id']],
    ['POST',     '#^/catalogo/(\d+)/deletar$#',     'catalogo/deletar.php', ['id']],
    ['GET',      '#^/catalogo/valor_estoque$#',     'catalogo/valor_estoque.php', []],
    ['GET|POST', '#^/catalogo/importar$#',          'catalogo/importar.php',[]],

    // Relatorios
    ['GET', '#^/relatorios$#',              'relatorios/index.php',    []],
    ['GET', '#^/relatorios/almoxarifado$#', 'relatorios/almoxarifado.php', []],
    ['GET', '#^/relatorios/consumo$#',      'relatorios/consumo.php',  []],

    // Admin
    ['GET',  '#^/admin$#',                          'admin/index.php',          []],
    ['GET',  '#^/admin/backup$#',                   'admin/backup.php',         []],
    ['POST', '#^/admin/backup/download$#',          'admin/backup_download.php',[]],
    ['GET',  '#^/admin/reativar_itens$#',           'admin/reativar_itens.php', []],
    ['POST', '#^/admin/reativar_item/(\d+)$#',      'admin/reativar_item.php',  ['id']],
    ['POST', '#^/admin/deletar_item/(\d+)$#',       'admin/deletar_item.php',   ['id']],
    ['POST', '#^/admin/transferir_itens$#',         'admin/transferir_itens.php',[]],

    // 2FA
    ['GET|POST', '#^/perfil/2fa/ativar$#',          'auth/2fa_ativar.php',      []],
    ['POST',     '#^/perfil/2fa/desativar$#',       'auth/2fa_desativar.php',   []],
    ['POST',     '#^/admin/2fa/desativar/(\d+)$#',  'auth/2fa_admin.php',       ['uid']],

    // API JSON
    ['GET',  '#^/api/alertas$#',                    'api/alertas.php',          []],
    ['GET',  '#^/api/colaboradores$#',              'api/colaboradores.php',    []],
    ['GET',  '#^/api/itens$#',                      'api/itens.php',            []],
    ['GET',  '#^/api/almoxarifados$#',              'api/almoxarifados.php',    []],
    ['POST', '#^/api/movimentacao/foto/(\d+)$#',    'api/foto_movimentacao.php',['id']],


    // Req mestre cancelar
    ['POST', '#^/requisicoes/mestre/(\d+)/cancelar$#', 'requisicoes/mestre_cancelar.php', ['id']],

    // Exportar + importar almoxarifado
    ['GET', '#^/almoxarifado/(\d+)/exportar$#',      'almoxarifado/exportar.php',     ['id']],
    ['GET|POST', '#^/almoxarifado/(\d+)/importar$#', 'almoxarifado/importar.php',     ['id']],
    ['GET', '#^/almoxarifado/(\d+)/modelo_excel$#',  'almoxarifado/modelo_excel.php', ['id']],

    // API extras
    ['GET', '#^/api/itens$#',          'api/itens.php',          []],
    ['GET', '#^/api/almoxarifados$#',  'api/almoxarifados.php',  []],

    // Kits
    ['GET',      '#^/almoxarifado/(\d+)/kits$#',                    'kits/index.php',   ['alm_id']],
    ['GET|POST', '#^/almoxarifado/(\d+)/kits/novo$#',               'kits/novo.php',    ['alm_id']],
    ['GET|POST', '#^/almoxarifado/(\d+)/kits/(\d+)/editar$#',       'kits/editar.php',  ['alm_id','kit_id']],
    ['POST',     '#^/almoxarifado/(\d+)/kits/(\d+)/excluir$#',      'kits/excluir.php', ['alm_id','kit_id']],
    // Healthcheck
    ['GET', '#^/healthz$#', 'api/healthz.php', []],
];

$params = [];
$matched = false;

foreach ($routes as [$allowedMethods, $pattern, $controller, $paramNames]) {
    if (!preg_match($pattern, $uri, $matches)) continue;
    // Verifica metodo HTTP
    $allowed = explode('|', $allowedMethods);
    if (!in_array($method, $allowed)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowed));
        die('Metodo nao permitido.');
    }
    // Extrai parametros nomeados
    array_shift($matches);
    foreach ($paramNames as $i => $name) {
        $params[$name] = $matches[$i] ?? null;
    }
    $matched = true;
    $controllerFile = ROOT_PATH . '/controllers/' . $controller;
    if (!file_exists($controllerFile)) {
        http_response_code(500);
        die('Controller nao encontrado: ' . h($controller));
    }
    require $controllerFile;
    break;
}

if (!$matched) {
    http_response_code(404);
    require ROOT_PATH . '/views/layouts/404.php';
}


