<?php
requer_login();
$almId=(int)($_GET['alm']??0); $q=trim($_GET['q']??'');
$sql='SELECT id,nome,quantidade,unidade,categoria,ca FROM item WHERE ativo=1';$binds=[];
if($almId){$sql.=' AND almoxarifado_id=?';$binds[]=$almId;}
if($q){$sql.=' AND nome LIKE ?';$binds[]="%$q%";}
$sql.=' ORDER BY nome LIMIT 50';$st=db()->prepare($sql);$st->execute($binds);
json_response($st->fetchAll());
