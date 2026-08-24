<?php
// Tenta conexao basica com o banco
try {
    db()->query('SELECT 1');
    json_response(['status' => 'ok', 'db' => 'ok']);
} catch (Exception $e) {
    json_response(['status' => 'error', 'db' => 'fail'], 500);
}
