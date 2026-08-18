"""Gerador de Certificado de Treinamento em PPTX — Padrão Stanza."""
import io
from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.util import Cm

# Cores padrão Stanza
LARANJA  = RGBColor(0xFF, 0x6B, 0x00)   # laranja Stanza
ROXO     = RGBColor(0x5B, 0x0F, 0x91)   # roxo logo
PRETO    = RGBColor(0x00, 0x00, 0x00)
BRANCO   = RGBColor(0xFF, 0xFF, 0xFF)
CINZA_SC = RGBColor(0x40, 0x40, 0x40)

# Conteúdos programáticos por NR (extensível)
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
        ('NORMAS E PROCEDIMENTOS DE SEGURANÇA:', [
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
            'Condutas em situações de emergência, incluindo noções de técnicas de resgate e primeiros socorros',
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
            'Sistema de bloqueio de funcionamento durante manutenção',
            'Manual de operação do fabricante',
            'Riscos mecânicos, elétricos e outros relevantes',
            'Método de trabalho seguro e Permissão de Trabalho',
            'Noções sobre acidentes, doenças e medidas de controle (EPC/EPI)',
            'Sinalização de segurança e procedimentos de emergência',
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

# Configurações por tipo de treinamento
NR_CONFIG = {
    'NR-18': {
        'nome_cert': 'Treinamento de Integração em Saúde e Segurança no Trabalho',
        'nr': 'NR 01',
        'portaria': 'Portaria nº 915 de 30/07/19',
        'carga': 4,
        'validade': 12,
    },
    'NR-35': {
        'nome_cert': 'Treinamento de Trabalho em Altura',
        'nr': 'NR 35',
        'portaria': 'Portaria MTb 3214/78 do Ministério do Trabalho',
        'carga': 8,
        'validade': 24,
    },
    'NR-33': {
        'nome_cert': 'Treinamento de Trabalho em Espaço Confinado',
        'nr': 'NR 33',
        'portaria': 'Portaria MTb 3214/78 do Ministério do Trabalho',
        'carga': 16,
        'validade': 12,
    },
    'NR-10': {
        'nome_cert': 'Treinamento de Segurança em Instalações e Serviços com Eletricidade',
        'nr': 'NR 10',
        'portaria': 'Portaria MTb 3214/78 do Ministério do Trabalho',
        'carga': 40,
        'validade': 24,
    },
    'NR-17': {
        'nome_cert': 'Noções de Ergonomia',
        'nr': 'NR 17',
        'portaria': 'Portaria 3.214/78 do Ministério do Trabalho',
        'carga': 1,
        'validade': 24,
    },
    'NR-18 Ferramentas': {
        'nome_cert': 'Treinamento para Uso de Ferramentas (Soprador, Furadeira, Rompedor, Martelete)',
        'nr': 'NR 18',
        'portaria': 'Portaria MTb 3214/78',
        'carga': 2,
        'validade': 12,
    },
    'Brigada de Incêndio': {
        'nome_cert': 'Treinamento de Brigada de Incêndio',
        'nr': 'NR 23',
        'portaria': 'Portaria MTb 3214/78',
        'carga': 8,
        'validade': 12,
    },
    'Primeiros Socorros': {
        'nome_cert': 'Treinamento de Primeiros Socorros',
        'nr': 'NR 7',
        'portaria': 'Portaria MTb 3214/78',
        'carga': 4,
        'validade': 24,
    },
}


def _add_textbox(slide, left, top, width, height, text, font_size=12,
                 bold=False, color=PRETO, align=PP_ALIGN.LEFT, italic=False,
                 word_wrap=True):
    txBox = slide.shapes.add_textbox(left, top, width, height)
    tf = txBox.text_frame
    tf.word_wrap = word_wrap
    p = tf.paragraphs[0]
    p.alignment = align
    run = p.add_run()
    run.text = text
    run.font.size = Pt(font_size)
    run.font.bold = bold
    run.font.italic = italic
    run.font.color.rgb = color
    return txBox


def _slide_certificado(prs, participante, treinamento, empresa, cnpj, local_emissao, data_formatada):
    """Gera um slide de certificado no padrão Stanza."""
    from pptx.util import Inches, Pt, Cm
    from pptx.dml.color import RGBColor
    from pptx.enum.text import PP_ALIGN

    slide_layout = prs.slide_layouts[6]  # blank
    slide = prs.slides.add_slide(slide_layout)

    W = prs.slide_width
    H = prs.slide_height

    # Fundo branco (default)

    # ── Faixa laranja diagonal (decorativa) — topo esquerdo ──
    # Simulado com retângulos inclinados
    from pptx.util import Emu
    from pptx.oxml.ns import qn
    import lxml.etree as etree

    # Bloco laranja canto superior esquerdo
    shape_tl = slide.shapes.add_shape(
        1,  # MSO_SHAPE_TYPE.RECTANGLE
        Cm(0), Cm(0), Cm(5), Cm(0.8)
    )
    shape_tl.fill.solid()
    shape_tl.fill.fore_color.rgb = LARANJA
    shape_tl.line.fill.background()

    # Bloco roxo canto inferior direito
    shape_br = slide.shapes.add_shape(
        1, W - Cm(8), H - Cm(3), Cm(8), Cm(3)
    )
    shape_br.fill.solid()
    shape_br.fill.fore_color.rgb = ROXO
    shape_br.line.fill.background()

    # Bloco laranja sobre o roxo
    shape_or = slide.shapes.add_shape(
        1, W - Cm(6), H - Cm(2.5), Cm(6), Cm(2.5)
    )
    shape_or.fill.solid()
    shape_or.fill.fore_color.rgb = LARANJA
    shape_or.line.fill.background()

    # ── Título CERTIFICADO ──
    _add_textbox(slide,
        Cm(1), Cm(1.2), W - Cm(2), Cm(1.8),
        'CERTIFICADO',
        font_size=36, bold=True, color=PRETO, align=PP_ALIGN.CENTER
    )

    # ── Texto principal ──
    cfg = NR_CONFIG.get(treinamento.tipo, {})
    nome_cert = treinamento.descricao or cfg.get('nome_cert', treinamento.tipo)
    nr_ref    = treinamento.nr_referencia or cfg.get('nr', '')
    portaria  = treinamento.portaria or cfg.get('portaria', '')
    carga     = treinamento.carga_horaria or cfg.get('carga', '')
    nome_p    = (participante.colaborador or '').upper()
    cpf_p     = participante.cpf or ''
    funcao_p  = (participante.funcao or '').upper()

    texto_cert = (
        f"Certificamos que {nome_p}"
        + (f", CPF: {cpf_p}" if cpf_p else '')
        + f", na função {funcao_p}, participou do {nome_cert}, "
        f"promovido pela empresa {empresa}"
        + (f" – CNPJ: {cnpj}" if cnpj else '')
        + f", em conformidade com a {nr_ref}"
        + (f", da {portaria}" if portaria else '')
        + (f", com carga horária de {carga:02d} horas." if carga else '.')
    )

    # Caixa de texto principal
    txBox = slide.shapes.add_textbox(Cm(1.5), Cm(3.2), W - Cm(3), Cm(6))
    tf = txBox.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.JUSTIFY

    # Texto com partes em negrito
    def add_part(p, text, bold=False, font_size=13, color=PRETO):
        run = p.add_run()
        run.text = text
        run.font.size = Pt(font_size)
        run.font.bold = bold
        run.font.color.rgb = color

    add_part(p, 'Certificamos que ')
    add_part(p, nome_p, bold=True)
    if cpf_p:
        add_part(p, ', CPF: ')
        add_part(p, cpf_p, bold=True)
    add_part(p, ', na função ')
    add_part(p, funcao_p, bold=True)
    add_part(p, f', participou do ')
    add_part(p, nome_cert, bold=True)
    add_part(p, ', promovido pela empresa ')
    add_part(p, empresa, bold=True)
    if cnpj:
        add_part(p, ' – CNPJ: ')
        add_part(p, cnpj, bold=True)
    add_part(p, f', em conformidade com a ')
    add_part(p, nr_ref, bold=True)
    if portaria:
        add_part(p, f', da {portaria}')
    if carga:
        add_part(p, f', com carga horária de ')
        add_part(p, f'{carga:02d} horas', bold=True)
    add_part(p, '.')

    # ── Data ──
    _add_textbox(slide,
        Cm(1.5), Cm(10.2), Cm(12), Cm(1),
        f'{local_emissao}, {data_formatada}.',
        font_size=12, italic=True, color=PRETO
    )

    # ── Assinaturas ──
    # Instrutor
    inst_nome  = treinamento.responsavel or ''
    inst_cargo = treinamento.cargo_responsavel or 'Técnico em Segurança do Trabalho'
    inst_mte   = treinamento.registro_mte or ''

    txInst = slide.shapes.add_textbox(Cm(1.5), Cm(12.0), Cm(9), Cm(2.5))
    tf = txInst.text_frame
    tf.word_wrap = True
    for i, linha in enumerate([
        '______________________________',
        'Instrutor',
        inst_nome,
        inst_cargo,
        (f'MTE: {inst_mte}' if inst_mte else ''),
    ]):
        if i == 0:
            p = tf.paragraphs[0]
        else:
            p = tf.add_paragraph()
        p.alignment = PP_ALIGN.CENTER
        run = p.add_run()
        run.text = linha
        run.font.size = Pt(10 if i > 0 else 11)
        run.font.bold = (i == 1)
        run.font.color.rgb = PRETO

    # Colaborador
    txColab = slide.shapes.add_textbox(Cm(12), Cm(12.0), Cm(9), Cm(2.5))
    tf = txColab.text_frame
    tf.word_wrap = True
    for i, linha in enumerate(['______________________________', 'Colaborador', nome_p]):
        if i == 0:
            p = tf.paragraphs[0]
        else:
            p = tf.add_paragraph()
        p.alignment = PP_ALIGN.CENTER
        run = p.add_run()
        run.text = linha
        run.font.size = Pt(10 if i > 0 else 11)
        run.font.bold = (i == 1)
        run.font.color.rgb = PRETO

    # ── Logo "stanza" texto (como no modelo) ──
    _add_textbox(slide,
        W // 2 - Cm(4), H - Cm(2.2), Cm(8), Cm(1.5),
        'stanza',
        font_size=28, bold=True, color=LARANJA, align=PP_ALIGN.CENTER
    )

    return slide


def _slide_conteudo(prs, tipo_treinamento):
    """Gera slide de conteúdo programático."""
    slide_layout = prs.slide_layouts[6]
    slide = prs.slides.add_slide(slide_layout)
    W = prs.slide_width
    H = prs.slide_height

    # Fundo branco + decoração roxo/laranja
    shape_br = slide.shapes.add_shape(1, W - Cm(8), H - Cm(3), Cm(8), Cm(3))
    shape_br.fill.solid()
    shape_br.fill.fore_color.rgb = ROXO
    shape_br.line.fill.background()

    shape_or = slide.shapes.add_shape(1, W - Cm(6), H - Cm(2.5), Cm(6), Cm(2.5))
    shape_or.fill.solid()
    shape_or.fill.fore_color.rgb = LARANJA
    shape_or.line.fill.background()

    # Título
    _add_textbox(slide,
        Cm(1), Cm(0.8), W - Cm(6), Cm(1.8),
        'CONTEÚDO PROGRAMÁTICO',
        font_size=28, bold=True, color=PRETO, align=PP_ALIGN.LEFT
    )

    # Conteúdo
    conteudo = CONTEUDOS.get(tipo_treinamento, [])
    if not conteudo:
        # Fallback genérico
        conteudo = [('Conteúdo programático:', [
            'Aspectos de segurança do trabalho',
            'Riscos e medidas preventivas',
            'EPI e EPC aplicáveis',
            'Procedimentos de emergência',
        ])]

    top = Cm(3.0)
    for titulo, itens in conteudo:
        # Título da seção
        txSec = slide.shapes.add_textbox(Cm(1), top, W - Cm(8), Cm(0.7))
        tf = txSec.text_frame
        p = tf.paragraphs[0]
        run = p.add_run()
        run.text = titulo
        run.font.size = Pt(11)
        run.font.bold = True
        run.font.color.rgb = PRETO
        top += Cm(0.75)

        for item in itens:
            txItem = slide.shapes.add_textbox(Cm(1.2), top, W - Cm(8), Cm(0.55))
            tf = txItem.text_frame
            p = tf.paragraphs[0]
            run = p.add_run()
            run.text = f'- {item}'
            run.font.size = Pt(10)
            run.font.color.rgb = CINZA_SC
            top += Cm(0.55)

        top += Cm(0.3)

    # Logo stanza no rodapé
    _add_textbox(slide,
        W // 2 - Cm(4), H - Cm(2.2), Cm(8), Cm(1.5),
        'stanza',
        font_size=28, bold=True, color=LARANJA, align=PP_ALIGN.CENTER
    )

    return slide


def gerar_certificado_pptx(
    participantes,      # lista de TreinamentoParticipante
    treinamento,        # objeto Treinamento
    empresa='STANZA ENGENHARIA E CONSTRUÇÕES LTDA',
    cnpj='08.343.492/0133-70',
    local_emissao=None,
    data_formatada=None,
):
    """
    Gera um PPTX com 2 slides por participante:
      Slide 1 — Certificado
      Slide 2 — Conteúdo Programático

    Retorna BytesIO com o arquivo pronto para send_file.
    """
    from datetime import date as _date
    import locale

    if not data_formatada:
        # Formatar data em português
        meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                 'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']
        d = treinamento.data_realizacao
        data_formatada = f'{d.day:02d} de {meses[d.month-1]} de {d.year}'

    if not local_emissao:
        alm = treinamento.almoxarifado
        local_emissao = (alm.cidade or 'Local') if alm else 'Local'

    # Apresentação landscape 33.87 x 19.05 cm (padrão widescreen 16:9)
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
