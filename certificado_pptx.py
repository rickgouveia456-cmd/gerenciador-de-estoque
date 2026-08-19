"""Gerador de Certificado de Treinamento em PPTX — Padrão Stanza."""
import io
from pptx import Presentation
from pptx.util import Pt, Cm
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE_TYPE

# ── Cores ────────────────────────────────────────────────────────────────────
LARANJA      = RGBColor(0xFF, 0x6B, 0x00)
ROXO         = RGBColor(0x5C, 0x2D, 0x91)
ROXO_CLARO   = RGBColor(0x7B, 0x2F, 0xBE)
VERDE        = RGBColor(0x00, 0x7A, 0x33)
PRETO        = RGBColor(0x00, 0x00, 0x00)
BRANCO       = RGBColor(0xFF, 0xFF, 0xFF)

# ── Conteúdos Programáticos ──────────────────────────────────────────────────
CONTEUDOS = {
    'NR-18': [
        ('ASPECTOS DE SEGURANÇA DO TRABALHO:', [
            'Condições do Ambiente de Trabalho',
            'Riscos inerentes às atividades desenvolvidas',
            'Diferença entre Perigo e Risco',
            'Diferença entre Condição Insegura e Ato Inseguro',
            'Equipamentos de Proteção Coletiva',
            'Noções sobre o Programa de Gerenciamento de Riscos (PGR)',
            'Noções de Ergonomia',
        ]),
        ('NORMAS E PROCEDIMENTOS DE SEGURANÇA', [
            'Noções sobre Acidentes de Trabalho e Afastamentos',
            'Noções sobre NR 5 (CIPA)',
            'Procedimentos de Segurança em Caso de Acidentes',
        ]),
        ('FATOR PESSOAL E RELAÇÕES HUMANAS NO TRABALHO:', [
            'Respeito aos colegas, superior hierárquico e procedimentos internos de segurança',
            'Responsabilidade quanto aos recursos materiais disponibilizados para seu labor',
            'Função do setor de segurança do trabalho no atendimento e apoio ao trabalhador',
        ]),
    ],
    'NR-35': [
        ('CONTEÚDO PROGRAMÁTICO — TRABALHO EM ALTURA:', [
            'Normas e regulamentos aplicáveis ao trabalho em altura',
            'Análise de Risco (AR) e condições impeditivas',
            'Riscos potenciais inerentes ao trabalho em altura e medidas de prevenção e controle',
            'Sistemas, equipamentos e procedimentos de proteção coletiva',
            'EPI para trabalho em altura: seleção, inspeção, conservação e limitação de uso',
            'Acidentes típicos em trabalhos em altura',
            'Condutas em situações de emergência, incluindo noções de resgate e primeiros socorros',
        ]),
    ],
    'NR-33': [
        ('CONTEÚDO PROGRAMÁTICO — ESPAÇO CONFINADO:', [
            'Conceito de espaço confinado e riscos associados',
            'Classificação dos espaços confinados',
            'Medidas de prevenção e procedimentos de segurança',
            'Permissão de entrada e trabalho (PET)',
            'Sistemas de ventilação e monitoramento de atmosfera',
            'Equipamentos de proteção individual e coletiva',
            'Procedimentos de resgate e primeiros socorros',
        ]),
    ],
    'NR-10': [
        ('CONTEÚDO PROGRAMÁTICO — ELETRICIDADE (NR-10):', [
            'Fundamentos de eletricidade: conceitos e grandezas',
            'Riscos elétricos e medidas de controle',
            'Normas técnicas e regulamentadoras aplicáveis',
            'Proteções coletivas: aterramento, bloqueio e isolamento',
            'Equipamentos de proteção individual para eletricidade',
            'Segurança em instalações elétricas energizadas e desenergizadas',
            'Primeiros socorros em acidentes com eletricidade',
        ]),
    ],
    'NR-17': [
        ('CONTEÚDO PROGRAMÁTICO — ERGONOMIA (NR-17):', [
            'Conceito e definições — NR-17 — Ergonomia — Legislação',
            'Educação Postural no Trabalho',
            'Transporte Manual de Carga',
            'Organização no Trabalho',
            'Métodos Preventivos — Postura Adequada',
            'LER/DORT',
            'Orientação, Postura e Ginástica Laboral',
        ]),
    ],
    'NR-18 Ferramentas': [
        ('CONTEÚDO PROGRAMÁTICO — USO DE FERRAMENTAS:', [
            'Princípios de segurança na utilização de máquinas e ferramentas',
            'Operação com segurança de máquinas e ferramentas',
            'Inspeção e manutenção com segurança',
            'Sistema de bloqueio durante manutenção',
            'Manual de operação do fabricante',
            'Riscos mecânicos, elétricos e outros relevantes',
            'Método de trabalho seguro e Permissão de Trabalho',
            'Noções sobre acidentes e medidas de controle (EPC/EPI)',
        ]),
    ],
    'Brigada de Incêndio': [
        ('CONTEÚDO PROGRAMÁTICO — BRIGADA DE INCÊNDIO:', [
            'Química do fogo: triângulo e tetraedro do fogo',
            'Classificação de incêndios e agentes extintores',
            'Sistemas de detecção, alarme e combate a incêndio',
            'Operação de extintores e hidrantes',
            'Técnicas de evacuação e abandono de área',
            'Prestação de primeiros socorros',
            'Plano de emergência da obra',
        ]),
    ],
    'Primeiros Socorros': [
        ('CONTEÚDO PROGRAMÁTICO — PRIMEIROS SOCORROS:', [
            'Conceitos básicos de primeiros socorros',
            'Avaliação da cena e segurança do socorrista',
            'Hemorragias: tipos e controle',
            'Fraturas, entorses e luxações',
            'Queimaduras: classificação e atendimento',
            'Parada cardiorrespiratória e RCP',
            'Transporte de vítimas',
        ]),
    ],
}

NR_CONFIG = {
    'NR-18': {
        'nome_cert': 'Treinamento de Integração em Saúde e Segurança no Trabalho',
        'nr': 'NR 01',
        'portaria': 'Portaria nº 915 de 30/07/19',
        'carga': 4, 'validade': 12,
    },
    'NR-35': {
        'nome_cert': 'Treinamento de Trabalho em Altura',
        'nr': 'NR 35',
        'portaria': 'Portaria nº 915 de 30/07/19',
        'carga': 8, 'validade': 24,
    },
    'NR-33': {
        'nome_cert': 'Treinamento de Trabalho em Espaço Confinado',
        'nr': 'NR 33',
        'portaria': 'Portaria MTb 3214/78 do Ministério do Trabalho',
        'carga': 16, 'validade': 12,
    },
    'NR-10': {
        'nome_cert': 'Treinamento de Segurança em Instalações e Serviços com Eletricidade',
        'nr': 'NR 10',
        'portaria': 'Portaria MTb 3214/78 do Ministério do Trabalho',
        'carga': 40, 'validade': 24,
    },
    'NR-17': {
        'nome_cert': 'Noções de Ergonomia',
        'nr': 'NR 17',
        'portaria': 'Portaria 3.214/78 do Ministério do Trabalho',
        'carga': 1, 'validade': 24,
    },
    'NR-18 Ferramentas': {
        'nome_cert': 'Treinamento para Uso de Ferramentas',
        'nr': 'NR 18',
        'portaria': 'Portaria MTb 3214/78',
        'carga': 2, 'validade': 12,
    },
    'Brigada de Incêndio': {
        'nome_cert': 'Treinamento de Brigada de Incêndio',
        'nr': 'NR 23',
        'portaria': 'Portaria MTb 3214/78',
        'carga': 8, 'validade': 12,
    },
    'Primeiros Socorros': {
        'nome_cert': 'Treinamento de Primeiros Socorros',
        'nr': 'NR 7',
        'portaria': 'Portaria MTb 3214/78',
        'carga': 4, 'validade': 24,
    },
}


# ── Helpers ──────────────────────────────────────────────────────────────────
def _rect(slide, l, t, w, h, rgb, rotation=0):
    s = slide.shapes.add_shape(1, l, t, w, h)
    s.fill.solid()
    s.fill.fore_color.rgb = rgb
    s.line.fill.background()
    if rotation:
        s.rotation = rotation
    return s


def _oval(slide, l, t, w, h, rgb):
    s = slide.shapes.add_shape(9, l, t, w, h)
    s.fill.solid()
    s.fill.fore_color.rgb = rgb
    s.line.fill.background()
    return s


def _oval_border(slide, l, t, w, h, rgb, border_rgb, border_pt=2):
    s = slide.shapes.add_shape(9, l, t, w, h)
    s.fill.solid()
    s.fill.fore_color.rgb = rgb
    s.line.color.rgb = border_rgb
    s.line.width = Pt(border_pt)
    return s


def _tb(slide, l, t, w, h, text, size=12, bold=False, italic=False,
        color=PRETO, align=PP_ALIGN.LEFT, wrap=True, font='Calibri'):
    tb = slide.shapes.add_textbox(l, t, w, h)
    tf = tb.text_frame
    tf.word_wrap = wrap
    p = tf.paragraphs[0]
    p.alignment = align
    run = p.add_run()
    run.text = text
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.italic = italic
    run.font.color.rgb = color
    run.font.name = font
    return tb


def _connector(slide, x1, y1, x2, y2, rgb, pt=1.0):
    try:
        c = slide.shapes.add_connector(1, x1, y1, x2, y2)
        c.line.color.rgb = rgb
        c.line.width = Pt(pt)
        return c
    except Exception:
        return None


# ── Elementos decorativos ─────────────────────────────────────────────────────
def _diagonais_topo_esq(slide):
    """3 linhas diagonais roxas no canto superior esquerdo."""
    for i in range(3):
        off = Cm(i * 0.45)
        _connector(slide,
                   Cm(0.4) + off, Cm(0.6),
                   Cm(2.8) + off, Cm(4.0),
                   ROXO, pt=2.0)


def _setas_canto_dir(slide, W, H):
    """
    Logo Stanza — fundo laranja + 2 chevrons roxos (dois '>' deitados = o Z).
    Cada chevron é formado por um paralelogramo.
    """
    # Bloco laranja de fundo (base)
    _rect(slide, W - Cm(9.5), H - Cm(5.8), Cm(9.5), Cm(5.8), LARANJA)

    # Paralelogramo superior roxo escuro (chevron 1 — maior)
    # shape 60 = paralelogramo no MSO
    try:
        p1 = slide.shapes.add_shape(60, W - Cm(8.8), H - Cm(7.0), Cm(6.0), Cm(3.0))
        p1.fill.solid(); p1.fill.fore_color.rgb = ROXO
        p1.line.fill.background()
    except Exception:
        _rect(slide, W - Cm(8.8), H - Cm(6.8), Cm(5.8), Cm(2.8), ROXO, rotation=-12)

    # Paralelogramo inferior roxo claro (chevron 2 — menor, deslocado)
    try:
        p2 = slide.shapes.add_shape(60, W - Cm(7.2), H - Cm(5.6), Cm(5.2), Cm(2.4))
        p2.fill.solid(); p2.fill.fore_color.rgb = ROXO_CLARO
        p2.line.fill.background()
    except Exception:
        _rect(slide, W - Cm(7.2), H - Cm(5.4), Cm(5.0), Cm(2.2), ROXO_CLARO, rotation=-12)

    # Cobertura laranja — recobre o lado esquerdo das setas
    _rect(slide, W - Cm(9.5), H - Cm(3.5), Cm(2.0), Cm(3.5), LARANJA)


def _logo_sst(slide, cx, cy, raio):
    """
    Símbolo Segurança do Trabalho — círculo verde com borda dupla e cruz branca.
    cx, cy = centro; raio em Cm units
    """
    r = raio
    # Círculo externo verde (maior — borda)
    _oval_border(slide, cx - r, cy - r, r*2, r*2, VERDE, VERDE, border_pt=0)

    # Anel interno branco (simula borda branca)
    ri = Cm(r.cm * 0.82)
    _oval(slide, cx - ri, cy - ri, ri*2, ri*2, BRANCO)

    # Círculo verde interno
    rv = Cm(r.cm * 0.68)
    _oval(slide, cx - rv, cy - rv, rv*2, rv*2, VERDE)

    # Cruz branca — vertical
    _rect(slide, cx - Cm(0.18), cy - Cm(0.65), Cm(0.36), Cm(1.3), BRANCO)
    # Cruz branca — horizontal
    _rect(slide, cx - Cm(0.65), cy - Cm(0.18), Cm(1.3), Cm(0.36), BRANCO)

    # Texto "SEGURANÇA DO TRABALHO" embaixo
    _tb(slide, cx - r - Cm(0.3), cy + Cm(r.cm * 0.85),
        r*2 + Cm(0.6), Cm(0.5),
        'SEGURANÇA DO TRABALHO',
        size=4.5, bold=True, color=BRANCO,
        align=PP_ALIGN.CENTER)


# ── Slide 1: Certificado ─────────────────────────────────────────────────────
def _slide_certificado(prs, participante, treinamento, empresa, cnpj,
                       local_emissao, data_formatada):
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    W = prs.slide_width
    H = prs.slide_height

    # Fundo branco
    _rect(slide, 0, 0, W, H, BRANCO)

    # Decoração canto inferior direito
    _setas_canto_dir(slide, W, H)

    # Linhas diagonais roxas topo esquerdo
    _diagonais_topo_esq(slide)

    # Logo SST canto inferior esquerdo
    _logo_sst(slide, Cm(2.2), H - Cm(3.2), Cm(1.8))

    # ── Título ──────────────────────────────────────────────────────────────
    _tb(slide, Cm(3), Cm(1.2), W - Cm(6), Cm(2.0),
        'CERTIFICADO', size=40, bold=True, align=PP_ALIGN.CENTER)

    # ── Corpo do texto ───────────────────────────────────────────────────────
    cfg = NR_CONFIG.get(treinamento.tipo, {})
    nome_cert = treinamento.descricao or cfg.get('nome_cert', treinamento.tipo)
    nr_ref    = treinamento.nr_referencia or cfg.get('nr', '')
    portaria  = treinamento.portaria or cfg.get('portaria', '')
    carga     = treinamento.carga_horaria or cfg.get('carga', '')
    nome_p    = (participante.colaborador or '').upper()
    cpf_p     = participante.cpf or ''
    funcao_p  = (participante.funcao or '').upper()

    # Caixa de texto — larga mas para antes das setas (mesma proporção do original)
    tb = slide.shapes.add_textbox(Cm(1.8), Cm(3.8), W - Cm(11.5), Cm(8.5))
    tf = tb.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.JUSTIFY
    p.space_after = Pt(6)

    def r(text, bold=False, sz=14):
        run = p.add_run()
        run.text = text
        run.font.size = Pt(sz)
        run.font.bold = bold
        run.font.color.rgb = PRETO
        run.font.name = 'Calibri'

    r('Certificamos que ')
    r(nome_p, bold=True)
    if cpf_p:
        r(', CPF: ')
        r(cpf_p, bold=True)
    r(', na função ')
    r(funcao_p, bold=True)
    r(', participou do ')
    r(nome_cert, bold=True)
    r(', promovido pela empresa ')
    r(empresa, bold=True)
    if cnpj:
        r(' \u2013 CNPJ: ')
        r(cnpj, bold=True)
    r(', em conformidade com a ')
    r(nr_ref)
    if portaria:
        r(f', da {portaria}')
    if carga:
        r(', com carga horária de ')
        r(f'{int(carga):02d} horas')
    r('.')

    # ── Data ─────────────────────────────────────────────────────────────────
    _tb(slide, Cm(1.8), Cm(12.2), Cm(14), Cm(1.0),
        f'{local_emissao}, {data_formatada}.',
        size=12, italic=True)

    # ── Assinaturas ──────────────────────────────────────────────────────────
    inst_nome  = treinamento.responsavel or ''
    inst_cargo = treinamento.cargo_responsavel or 'Técnico em Segurança do Trabalho'
    inst_mte   = treinamento.registro_mte or ''

    # Linha instrutor
    _connector(slide, Cm(2.5), Cm(13.8), Cm(9.0), Cm(13.8), PRETO, pt=0.75)

    # Textos instrutor
    tb_inst = slide.shapes.add_textbox(Cm(2), Cm(14.0), Cm(7.5), Cm(2.8))
    tf_i = tb_inst.text_frame; tf_i.word_wrap = True
    for i, (txt, bold, sz) in enumerate([
        ('Instrutor', True, 10),
        (inst_nome, False, 9),
        (inst_cargo, False, 9),
        (f'MTE: {inst_mte}' if inst_mte else '', False, 9),
    ]):
        if not txt: continue
        p2 = tf_i.paragraphs[0] if i == 0 else tf_i.add_paragraph()
        p2.alignment = PP_ALIGN.CENTER
        rn = p2.add_run(); rn.text = txt
        rn.font.size = Pt(sz); rn.font.bold = bold
        rn.font.color.rgb = PRETO; rn.font.name = 'Calibri'

    # Linha colaborador
    _connector(slide, Cm(11.0), Cm(13.8), Cm(17.5), Cm(13.8), PRETO, pt=0.75)
    _tb(slide, Cm(11), Cm(14.0), Cm(7), Cm(0.8),
        'Colaborador', size=10, bold=True, align=PP_ALIGN.CENTER)

    # ── Logo stanza ──────────────────────────────────────────────────────────
    _tb(slide, W//2 - Cm(4.5), H - Cm(3.2), Cm(9), Cm(1.8),
        'stanza', size=32, bold=True, color=LARANJA, align=PP_ALIGN.CENTER)

    return slide


# ── Slide 2: Conteúdo Programático ───────────────────────────────────────────
def _slide_conteudo(prs, tipo_treinamento):
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    W = prs.slide_width
    H = prs.slide_height

    # Fundo branco
    _rect(slide, 0, 0, W, H, BRANCO)

    # Decoração canto inferior direito
    _setas_canto_dir(slide, W, H)

    # Linhas diagonais roxas
    _diagonais_topo_esq(slide)

    # Logo SST canto SUPERIOR DIREITO (igual ao modelo do conteúdo)
    _logo_sst(slide, W - Cm(2.5), Cm(2.5), Cm(1.8))

    # Título
    _tb(slide, Cm(2), Cm(0.8), W - Cm(7), Cm(2.0),
        'CONTEÚDO PROGRAMÁTICO', size=32, bold=True, align=PP_ALIGN.LEFT)

    # Barra laranja vertical fina à esquerda do conteúdo
    _rect(slide, Cm(1.8), Cm(3.4), Cm(0.12), H - Cm(5.5), LARANJA)

    # Conteúdo programático
    conteudo = CONTEUDOS.get(tipo_treinamento, [
        ('Conteúdo programático:', [
            'Aspectos de segurança do trabalho',
            'Riscos e medidas preventivas',
            'EPI e EPC aplicáveis',
            'Procedimentos de emergência',
        ])
    ])

    top = Cm(3.5)
    for secao_titulo, itens in conteudo:
        _tb(slide, Cm(2.2), top, W - Cm(10), Cm(0.65),
            secao_titulo, size=11, bold=True)
        top += Cm(0.68)
        for item in itens:
            _tb(slide, Cm(2.2), top, W - Cm(10), Cm(0.52),
                f'- {item}', size=10)
            top += Cm(0.52)
        top += Cm(0.3)

    # Logo stanza
    _tb(slide, W//2 - Cm(4.5), H - Cm(3.0), Cm(9), Cm(1.6),
        'stanza', size=30, bold=True, color=LARANJA, align=PP_ALIGN.CENTER)

    return slide


# ── Função principal ──────────────────────────────────────────────────────────
def gerar_certificado_pptx(
    participantes,
    treinamento,
    empresa='STANZA INCORPORAÇÃO E CONSTRUÇÃO LTDA',
    cnpj='09.191.102/0001-06',
    local_emissao=None,
    data_formatada=None,
):
    """Gera PPTX com 2 slides por participante: Certificado + Conteúdo."""
    meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
             'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']
    if not data_formatada:
        d = treinamento.data_realizacao
        data_formatada = f'{d.day:02d} de {meses[d.month-1]} de {d.year}'
    if not local_emissao:
        alm = treinamento.almoxarifado
        local_emissao = (alm.cidade or 'Local') if alm else 'Local'

    prs = Presentation()
    prs.slide_width  = Cm(33.87)
    prs.slide_height = Cm(19.05)

    for p in participantes:
        if not p.concluiu:
            continue
        _slide_certificado(prs, p, treinamento, empresa, cnpj,
                           local_emissao, data_formatada)
        _slide_conteudo(prs, treinamento.tipo)

    buf = io.BytesIO()
    prs.save(buf)
    buf.seek(0)
    return buf
