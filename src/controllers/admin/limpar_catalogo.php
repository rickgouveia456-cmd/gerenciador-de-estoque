<?php
requer_admin();

// Deletar registros cujo nome tem caracteres fora do UTF-8 válido ou de controle
$todos = db()->query("SELECT id, nome FROM catalogo_insumo WHERE ativo=1")->fetchAll();
$deletados = 0;
foreach ($todos as $r) {
    $nome = $r['nome'];
    // Detectar encoding inválido ou caracteres de controle que indicam lixo
    if (!mb_check_encoding($nome, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $nome)) {
        db()->prepare("UPDATE catalogo_insumo SET ativo=0 WHERE id=?")->execute([$r['id']]);
        $deletados++;
    }
}
flash("$deletados registros corrompidos removidos do catálogo.", 'success');
redirect('/catalogo');
