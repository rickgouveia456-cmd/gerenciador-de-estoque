<?php
requer_login();
$colab = trim($_GET["colaborador"] ?? "");
if (!$colab) { json_response(["tem_ficha" => false]); }

$st = db()->prepare(
    "SELECT f.id, COUNT(ife.id) AS total_itens
     FROM ficha_epi f
     LEFT JOIN item_ficha_epi ife ON ife.ficha_id = f.id
     WHERE LOWER(f.colaborador) = LOWER(?) AND f.status = ?
     GROUP BY f.id
     ORDER BY f.criado_em DESC
     LIMIT 1"
);
$st->execute([$colab, "ativa"]);
$row = $st->fetch();

if ($row) {
    json_response([
        "tem_ficha"   => true,
        "ficha_id"    => (int)$row["id"],
        "total_itens" => (int)$row["total_itens"],
    ]);
} else {
    json_response(["tem_ficha" => false]);
}