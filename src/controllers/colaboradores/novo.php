<?php
requer_login(); csrf_check(); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','almoxarife','analista'])){flash('Acesso negado.','danger');redirect('/');}
$nome=trim($_POST['nome']??'');
if(!$nome){flash('Informe o nome.','warning');redirect('/colaboradores');}
$ex=db()->prepare('SELECT id FROM colaborador WHERE nome LIKE ?');$ex->execute([$nome]);
if($ex->fetch()){flash("\"$nome\" já está cadastrado.",'warning');redirect('/colaboradores');}
$obra=trim($_POST['obra']??'');$cidade=trim($_POST['cidade']??'');
if(!$obra&&$u['almoxarifado_id']){$stA=db()->prepare('SELECT * FROM almoxarifado WHERE id=?');$stA->execute([$u['almoxarifado_id']]);$a=$stA->fetch();if($a){$obra=$a['obra']??'';$cidade=$a['cidade']??'';}}
db()->prepare('INSERT INTO colaborador (nome,funcao,escopo,obra,cidade,tipo) VALUES (?,?,?,?,?,?)')->execute([$nome,trim($_POST['funcao']??'')?:null,trim($_POST['escopo']??'')?:null,$obra?:null,$cidade?:null,trim($_POST['tipo']??'peao')]);
flash("Colaborador \"$nome\" cadastrado!",'success');redirect('/colaboradores');
