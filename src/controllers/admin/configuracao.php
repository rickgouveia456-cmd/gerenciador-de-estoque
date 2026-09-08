<?php
/**
 * Configuração Inicial do Sistema — 3.7.1
 */
requer_login();
$u = usuario_atual();
if ($u['perfil'] !== 'admin') {
    flash('Acesso restrito a administradores.', 'danger');
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $campos = [
        'empresa_nome', 'empresa_cnpj', 'empresa_cidade',
        'empresa_estado', 'empresa_telefone', 'empresa_email',
        'sistema_versao',
    ];

    foreach ($campos as $campo) {
        $valor = trim($_POST[$campo] ?? '');
        cfg_set($campo, $valor);
    }

    // Upload de logo
    if (!empty($_FILES['empresa_logo']['tmp_name'])) {
        $ext  = strtolower(pathinfo($_FILES['empresa_logo']['name'], PATHINFO_EXTENSION));
        $allow = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        if (in_array($ext, $allow) && $_FILES['empresa_logo']['size'] < 2 * 1024 * 1024) {
            $dados = file_get_contents($_FILES['empresa_logo']['tmp_name']);
            $b64   = 'data:image/' . $ext . ';base64,' . base64_encode($dados);
            cfg_set('empresa_logo_b64', $b64);
        } else {
            flash('Logo inválida. Use PNG, JPG ou SVG até 2MB.', 'warning');
        }
    }

    cfg_set('onboarding_feito', '1');
    flash('Configurações salvas com sucesso!', 'success');
    redirect('/admin/configuracao');
}

// Carrega valores atuais
$config = [
    'empresa_nome'     => cfg('empresa_nome',     'Stanza Construtora'),
    'empresa_cnpj'     => cfg('empresa_cnpj',     ''),
    'empresa_cidade'   => cfg('empresa_cidade',   'Salvador'),
    'empresa_estado'   => cfg('empresa_estado',   'BA'),
    'empresa_telefone' => cfg('empresa_telefone', ''),
    'empresa_email'    => cfg('empresa_email',    ''),
    'sistema_versao'   => cfg('sistema_versao',   '1.0.0'),
    'empresa_logo_b64' => cfg('empresa_logo_b64', ''),
    'onboarding_feito' => cfg('onboarding_feito', '0'),
];

$pageTitle  = 'Configuração do Sistema';
$activeMenu = 'configuracao';
ob_start();
require VIEWS_PATH . '/admin/configuracao.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
