<?php
/**
 * Modelo de importação do Catálogo — formato XLSX com identidade Stanza
 * GET /catalogo/modelo_csv
 */
requer_login();

// ─── Gerador XLSX mínimo (sem dependências) ───────────────────────────────────
function xlsx_escape(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Gera um XLSX em memória e retorna os bytes.
 * $sheets = [ ['name'=>'Aba', 'rows'=>[ [...células...] ], 'colWidths'=>[...] ] ]
 * Cada célula: ['v'=>valor, 't'=>'s'|'n'|'b', 's'=>styleId]
 */
function gerar_xlsx(array $sheets): string
{
    // ── Shared strings ────────────────────────────────────────────────────────
    $sst = [];       // string → index
    $sstArr = [];    // index → string

    $addStr = function(string $s) use (&$sst, &$sstArr): int {
        if (!isset($sst[$s])) {
            $sst[$s] = count($sstArr);
            $sstArr[] = $s;
        }
        return $sst[$s];
    };

    // ── Estilos (número de styles.xml) ───────────────────────────────────────
    // IDs de estilo que vamos usar:
    // 0 = normal
    // 1 = cabeçalho principal  (laranja Stanza, branco, bold)
    // 2 = cabeçalho secundário (azul escuro, branco, bold)
    // 3 = instrução (cinza claro, itálico)
    // 4 = exemplo bold
    // 5 = número (alinhado à direita)
    // 6 = aviso vermelho
    $stylesXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="7">
    <font><sz val="10"/><name val="Calibri"/></font>
    <font><sz val="11"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="11"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="10"/><i/><color rgb="FF555555"/><name val="Calibri"/></font>
    <font><sz val="10"/><b/><name val="Calibri"/></font>
    <font><sz val="10"/><name val="Calibri"/></font>
    <font><sz val="10"/><b/><color rgb="FFC0392B"/><name val="Calibri"/></font>
  </fonts>
  <fills count="5">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFF6B35"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1A2035"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF0F3F8"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFD0D5E0"/></left>
      <right style="thin"><color rgb="FFD0D5E0"/></right>
      <top style="thin"><color rgb="FFD0D5E0"/></top>
      <bottom style="thin"><color rgb="FFD0D5E0"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="8">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"><alignment wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0"><alignment wrapText="1"/></xf>
    <xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0"/>
    <xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0"><alignment horizontal="right"/></xf>
    <xf numFmtId="0" fontId="6" fillId="0" borderId="1" xfId="0"><alignment horizontal="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0"><alignment horizontal="center"/></xf>
  </cellXfs>
</styleSheet>
XML;

    // ── Processar planilhas ───────────────────────────────────────────────────
    $sheetXmls   = [];
    $sheetRels   = [];
    $sheetFiles  = [];

    foreach ($sheets as $si => $sheet) {
        $rid  = $si + 1;
        $name = xlsx_escape($sheet['name']);
        $rows = $sheet['rows'];
        $colW = $sheet['colWidths'] ?? [];

        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // larguras de coluna
        if ($colW) {
            $xml .= '<cols>';
            foreach ($colW as $ci => $w) {
                $n = $ci + 1;
                $xml .= '<col min="'.$n.'" max="'.$n.'" width="'.$w.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach ($rows as $ri => $row) {
            $rn = $ri + 1;
            $xml .= '<row r="'.$rn.'" ht="'.($row['h'] ?? 18).'" customHeight="1">';
            foreach ($row['cells'] as $ci => $cell) {
                $cn  = $ci + 1;
                $col = chr(64 + $cn); // A–Z (até 26 colunas)
                $ref = $col.$rn;
                $s   = $cell['s'] ?? 0;
                $t   = $cell['t'] ?? 's';
                $v   = $cell['v'] ?? '';

                if ($t === 's') {
                    $idx = $addStr((string)$v);
                    $xml .= '<c r="'.$ref.'" t="s" s="'.$s.'"><v>'.$idx.'</v></c>';
                } elseif ($t === 'n') {
                    $xml .= '<c r="'.$ref.'" t="n" s="'.$s.'"><v>'.xlsx_escape((string)$v).'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" s="'.$s.'"/>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        // Merge cells para título
        if (!empty($sheet['merges'])) {
            $xml .= '<mergeCells count="'.count($sheet['merges']).'">';
            foreach ($sheet['merges'] as $m) {
                $xml .= '<mergeCell ref="'.$m.'"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';

        $fname = 'xl/worksheets/sheet'.$rid.'.xml';
        $sheetXmls[$fname]  = $xml;
        $sheetRels[]        = '<sheet name="'.$name.'" sheetId="'.$rid.'" r:id="rId'.$rid.'"/>';
        $sheetFiles[]       = ['rId'=>'rId'.$rid,'fname'=>'worksheets/sheet'.$rid.'.xml'];
    }

    // ── Shared strings XML ────────────────────────────────────────────────────
    $sstXml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $sstXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sstArr).'" uniqueCount="'.count($sstArr).'">';
    foreach ($sstArr as $str) {
        $sstXml .= '<si><t xml:space="preserve">'.xlsx_escape($str).'</t></si>';
    }
    $sstXml .= '</sst>';

    // ── workbook.xml ──────────────────────────────────────────────────────────
    $wbXml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $wbXml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $wbXml .= '<sheets>'.implode('', $sheetRels).'</sheets>';
    $wbXml .= '</workbook>';

    // ── workbook.xml.rels ─────────────────────────────────────────────────────
    $wbRels  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $wbRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $wbRels .= '<Relationship Id="rId999" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $wbRels .= '<Relationship Id="rId998" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
    foreach ($sheetFiles as $sf) {
        $wbRels .= '<Relationship Id="'.$sf['rId'].'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="'.$sf['fname'].'"/>';
    }
    $wbRels .= '</Relationships>';

    // ── [Content_Types].xml ───────────────────────────────────────────────────
    $ct  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $ct .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
    $ct .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
    $ct .= '<Default Extension="xml" ContentType="application/xml"/>';
    $ct .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
    $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    $ct .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';
    foreach ($sheetFiles as $si => $sf) {
        $ct .= '<Override PartName="/xl/'.$sf['fname'].'" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    }
    $ct .= '</Types>';

    // ── _rels/.rels ───────────────────────────────────────────────────────────
    $rels  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
    $rels .= '</Relationships>';

    // ── Montar ZIP ────────────────────────────────────────────────────────────
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
    @unlink($tmp);
    $tmp .= '.xlsx';

    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $zip->addFromString('[Content_Types].xml', $ct);
    $zip->addFromString('_rels/.rels',         $rels);
    $zip->addFromString('xl/workbook.xml',     $wbXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
    $zip->addFromString('xl/styles.xml',       $stylesXml);
    $zip->addFromString('xl/sharedStrings.xml',$sstXml);
    foreach ($sheetXmls as $fname => $content) {
        $zip->addFromString($fname, $content);
    }
    $zip->close();

    $bytes = file_get_contents($tmp);
    @unlink($tmp);
    return $bytes;
}

// ─── Definir conteúdo das abas ────────────────────────────────────────────────
// Estilos: 0=normal+borda, 1=header laranja, 2=header azul, 3=instrução cinza,
//          4=bold, 5=número, 6=aviso vermelho, 7=cinza claro centrado

$s = function(string $v, int $style = 0): array {
    return ['v' => $v, 't' => 's', 's' => $style];
};
$n = function($v, int $style = 5): array {
    return ['v' => $v, 't' => 'n', 's' => $style];
};
$e = function(int $style = 0): array {
    return ['v' => '', 't' => 'b', 's' => $style];
};

$sheets = [];

// ════════════════════════════════════════════
// ABA 1 — Modelo de Importação
// ════════════════════════════════════════════
$rows1 = [];

// Linha 1 — título principal
$rows1[] = ['h' => 28, 'cells' => [
    $s('LOGI-PRIME · STANZA CONSTRUTORA', 1),
    $e(1), $e(1), $e(1), $e(1), $e(1),
]];

// Linha 2 — subtítulo
$rows1[] = ['h' => 18, 'cells' => [
    $s('Modelo de Importação — Catálogo de Insumos', 2),
    $e(2), $e(2), $e(2), $e(2), $e(2),
]];

// Linha 3 — vazia
$rows1[] = ['h' => 6, 'cells' => [ $e(), $e(), $e(), $e(), $e(), $e() ]];

// Linha 4 — instrução
$rows1[] = ['h' => 15, 'cells' => [
    $s('► Preencha a partir da linha 8. Não altere os cabeçalhos. Salve como UTF-8 ou mantenha o formato XLSX.', 3),
    $e(3), $e(3), $e(3), $e(3), $e(3),
]];

// Linha 5 — instrução categoria
$rows1[] = ['h' => 15, 'cells' => [
    $s('► Categorias válidas: geral | epi | eletrica | hidraulica | gas | maquinario', 3),
    $e(3), $e(3), $e(3), $e(3), $e(3),
]];

// Linha 6 — instrução unidade
$rows1[] = ['h' => 15, 'cells' => [
    $s('► Unidades comuns: un | m | m² | kg | sc | cx | lt | pc | rl | gl | bt | pct', 3),
    $e(3), $e(3), $e(3), $e(3), $e(3),
]];

// Linha 7 — vazia
$rows1[] = ['h' => 6, 'cells' => [ $e(), $e(), $e(), $e(), $e(), $e() ]];

// Linha 8 — cabeçalhos das colunas
$rows1[] = ['h' => 20, 'cells' => [
    $s('Nome do Item *',     1),
    $s('Código Ref',         1),
    $s('Unidade *',          1),
    $s('Categoria',          1),
    $s('CA (EPI)',            1),
    $s('Valor Unitário (R$)', 1),
]];

// Linhas 9–14 — exemplos
$exemplos = [
    ['Capacete Aba Total tipo III', 'CAP-001', 'un',  'epi',        'CA35519', 45.90],
    ['Cimento CP-II 50kg',          'CIM-002', 'sc',  'geral',      '',        38.50],
    ['Disjuntor Bipolar 20A DIN',   'DIS-003', 'un',  'eletrica',   '',        22.00],
    ['Registro de Gaveta 3/4"',     'REG-004', 'un',  'hidraulica', '',        18.75],
    ['Botijão de Gás P13',          'BOT-005', 'un',  'gas',        '',       120.00],
    ['Furadeira de Impacto 750W',   'FUR-006', 'un',  'maquinario', '',       380.00],
];

foreach ($exemplos as $i => $ex) {
    $rows1[] = ['h' => 17, 'cells' => [
        $s($ex[0], 4),
        $s($ex[1], 0),
        $s($ex[2], 7),
        $s($ex[3], 7),
        $s((string)$ex[4], 0),
        $n($ex[5], 5),
    ]];
}

// Linha final — aviso
$rows1[] = ['h' => 6,  'cells' => [ $e(), $e(), $e(), $e(), $e(), $e() ]];
$rows1[] = ['h' => 14, 'cells' => [
    $s('* Campos obrigatórios. Remova estas linhas de exemplo antes de importar.', 6),
    $e(6), $e(6), $e(6), $e(6), $e(6),
]];

$sheets[] = [
    'name'      => 'Modelo Importação',
    'rows'      => $rows1,
    'colWidths' => [40, 14, 10, 14, 12, 18],
    'merges'    => ['A1:F1', 'A2:F2', 'A4:F4', 'A5:F5', 'A6:F6', 'A'.count($rows1).':F'.count($rows1)],
];

// ════════════════════════════════════════════
// ABA 2 — Referência de Categorias
// ════════════════════════════════════════════
$rows2 = [];
$rows2[] = ['h' => 24, 'cells' => [$s('Referência de Categorias e Unidades', 2), $e(2), $e(2)]];
$rows2[] = ['h' => 6,  'cells' => [$e(), $e(), $e()]];
$rows2[] = ['h' => 18, 'cells' => [$s('Código',    1), $s('Nome Exibido',  1), $s('Obs', 1)]];

$cats = [
    ['geral',      'Geral',        'Materiais de uso geral em obra'],
    ['epi',        'EPI',          'Equipamentos de Proteção Individual — preencher CA'],
    ['eletrica',   'Elétrica',     'Materiais elétricos e eletrotécnicos'],
    ['hidraulica', 'Hidráulica',   'Materiais hidráulicos e de encanamento'],
    ['gas',        'Gás',          'Materiais para instalações de gás'],
    ['maquinario', 'Maquinário',   'Equipamentos, máquinas e ferramentas'],
];
foreach ($cats as $c) {
    $rows2[] = ['h' => 16, 'cells' => [$s($c[0], 7), $s($c[1], 4), $s($c[2], 0)]];
}

$rows2[] = ['h' => 6,  'cells' => [$e(), $e(), $e()]];
$rows2[] = ['h' => 18, 'cells' => [$s('Unidade', 1), $s('Descrição', 1), $e(1)]];

$unds = [
    ['un',  'Unidade'],    ['m',   'Metro'],      ['m²',  'Metro quadrado'],
    ['kg',  'Quilograma'], ['sc',  'Saco'],        ['cx',  'Caixa'],
    ['lt',  'Litro'],      ['pc',  'Peça'],        ['rl',  'Rolo'],
    ['gl',  'Galão'],      ['bt',  'Barra/Tubo'],  ['pct', 'Pacote'],
];
foreach ($unds as $u) {
    $rows2[] = ['h' => 16, 'cells' => [$s($u[0], 7), $s($u[1], 0), $e()]];
}

$sheets[] = [
    'name'      => 'Referência',
    'rows'      => $rows2,
    'colWidths' => [16, 22, 48],
    'merges'    => ['A1:C1'],
];

// ─── Download ─────────────────────────────────────────────────────────────────
$xlsx = gerar_xlsx($sheets);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Modelo_Catalogo_LogiPrime.xlsx"');
header('Content-Length: '.strlen($xlsx));
header('Cache-Control: no-cache, no-store, must-revalidate');
echo $xlsx;
exit;
