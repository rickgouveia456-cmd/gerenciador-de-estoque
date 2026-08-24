<?php
requer_login(); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','almoxarife','analista'])){flash('Acesso negado.','danger');redirect('/');}
$st=db()->query('SELECT * FROM colaborador ORDER BY ativo DESC, nome');$todos=$st->fetchAll();
$grupos=['estrutura'=>['label'=>'🏗️ Estrutura','cor'=>'#f0a500','colaboradores'=>[]],'infraestrutura'=>['label'=>'🔧 Infraestrutura','cor'=>'#0ea5e9','colaboradores'=>[]],'acabamento'=>['label'=>'🏕️ Acabamento','cor'=>'#22c55e','colaboradores'=>[]],'sem_escopo'=>['label'=>'📋 Sem Escopo','cor'=>'#94a3b8','colaboradores'=>[]]];
foreach($todos as $c){ $esc=strtolower(trim($c['escopo']??'')); $grupos[isset($grupos[$esc])?$esc:'sem_escopo']['colaboradores'][]=$c; }
$pageTitle='Colaboradores';$activeMenu='colaboradores';
ob_start();require VIEWS_PATH.'/colaboradores/index.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
