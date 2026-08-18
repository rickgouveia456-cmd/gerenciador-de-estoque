"""Funções utilitárias do Logi-Prime — sem dependências de Flask/DB."""
import re
from datetime import datetime, timedelta


def extrair_colaborador(mov):
    """Extrai o nome do colaborador da observação da movimentação.
    Suporta: 'liberado P/ Nome', 'Colaborador: Nome'.
    Fallback para mov.responsavel.
    """
    obs = mov.observacao or ''
    m = re.search(r'liberado\s+[Pp][/\s]+(.+)', obs, re.IGNORECASE)
    if m:
        return m.group(1).strip()
    m = re.search(r'[Cc]olaborador[:\s]+([^|]+)', obs)
    if m:
        return m.group(1).strip()
    return mov.responsavel or 'Sem responsável'


def calcular_ruptura(itens_ativos, limite_dias=None):
    """Calcula previsão de ruptura para uma lista de itens ativos.

    Usa média ponderada para maior precisão:
    - Últimos 7 dias: peso 3  (tendência recente)
    - Dias 8-30: peso 1       (tendência histórica)

    Inclui itens abaixo do mínimo — calcula dias até zerar.
    Se limite_dias=None, retorna previsão para TODOS os itens com consumo.
    Retorna lista ordenada por urgência (menos dias primeiro).
    """
    agora_dt = datetime.utcnow()
    corte_30 = agora_dt - timedelta(days=30)
    corte_7  = agora_dt - timedelta(days=7)

    ruptura = []
    for it in itens_ativos:
        movs = [m for m in it.movimentacoes if m.tipo == 'saida' and m.data >= corte_30]
        if not movs:
            continue  # sem consumo nos últimos 30 dias — sem previsão

        saidas_7  = sum(m.quantidade for m in movs if m.data >= corte_7)
        saidas_30 = sum(m.quantidade for m in movs)

        consumo_diario_7  = saidas_7  / 7  if saidas_7  > 0 else 0
        consumo_diario_30 = saidas_30 / 30 if saidas_30 > 0 else 0

        # Média ponderada: tendência recente (7 dias) tem peso 3x
        if consumo_diario_7 > 0 and consumo_diario_30 > 0:
            consumo_diario = (consumo_diario_7 * 3 + consumo_diario_30 * 1) / 4
        elif consumo_diario_7 > 0:
            consumo_diario = consumo_diario_7
        else:
            consumo_diario = consumo_diario_30

        if consumo_diario <= 0:
            continue

        # Dias até zerar completamente
        dias_ate_zero = max(0, it.quantidade / consumo_diario)

        # Dias até atingir o estoque mínimo (negativo se já abaixo)
        estoque_disponivel = it.quantidade - it.estoque_minimo
        dias_ate_minimo = estoque_disponivel / consumo_diario

        if limite_dias is not None and dias_ate_minimo > limite_dias:
            continue

        # Urgência baseada em dias até zerar (para itens já abaixo do mínimo)
        if it.quantidade <= 0:
            urgencia = 'zerado'
        elif it.quantidade <= it.estoque_minimo:
            urgencia = 'critico' if dias_ate_zero <= 3 else 'alerta' if dias_ate_zero <= 7 else 'aviso'
        else:
            urgencia = (
                'critico' if dias_ate_minimo <= 3 else
                'alerta'  if dias_ate_minimo <= 7 else
                'aviso'   if dias_ate_minimo <= 15 else
                'normal'
            )

        ruptura.append({
            'item': it,
            'dias': int(round(max(0, dias_ate_minimo))),
            'dias_zero': int(round(dias_ate_zero)),
            'consumo_diario': round(consumo_diario, 2),
            'urgencia': urgencia,
        })

    ruptura.sort(key=lambda x: x['dias'])
    return ruptura
