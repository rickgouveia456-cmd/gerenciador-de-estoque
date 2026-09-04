<?php
requer_login();
$busca  = trim($_GET['q'] ?? '');
$escopo = $_GET['escopo'] ?? '';

$sql   = 'SELECT id, nome, funcao, escopo FROM colaborador WHERE ativo=1';
$binds = [];

if ($busca) {
    $sql .= ' AND nome LIKE ?';
    $binds[] = "%$busca%";
}
if ($escopo) {
    $sql .= ' AND escopo=?';
    $binds[] = $escopo;
}

$sql .= ' ORDER BY nome LIMIT 50';
$stmt = db()->prepare($sql);
$stmt->execute($binds);
json_response($stmt->fetchAll());
