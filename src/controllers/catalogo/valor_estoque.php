<?php
requer_login(); $u=usuario_atual();
$ids=almoxarifados_permitidos_ids();
$alms=$u['perfil']==='admin'?db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll():
    ($ids?(function($ids){$ph=implode(',',array_fill(0,count($ids),'?'));$s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");$s->execute($ids);return $s->fetchAll();})($ids):[]);
$resumo=[];$totalGeral=0;
foreach($alms as $alm){
    $st=db()->prepare('SELECT * FROM item WHERE almoxarifado_id=? AND ativo=1');$st->execute([$alm['id']]);$itens=$st->fetchAll();
    $valorAlm=0;$itensComValor=[];$semValor=0;
    foreach($itens as $it){
        if($it['valor_unitario']&&(float)$it['valor_unitario']>0){$vt=(float)$it['quantidade']*(float)$it['valor_unitario'];$valorAlm+=$vt;$itensComValor[]=['item'=>$it,'valor_total'=>$vt];}
        else $semValor++;
    }
    usort($itensComValor,fn($a,$b)=>$b['valor_total']<=>$a['valor_total']);
    $totalGeral+=$valorAlm;
    $resumo[]=['almoxarifado'=>$alm,'valor_total'=>$valorAlm,'itens'=>$itensComValor,'itens_sem_valor'=>$semValor,'total_itens'=>count($itens)];
}
usort($resumo,fn($a,$b)=>$b['valor_total']<=>$a['valor_total']);
$pageTitle='Valor em Estoque';$activeMenu='catalogo';
ob_start();require VIEWS_PATH.'/catalogo/valor_estoque.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
