<?php
require_once dirname(__DIR__) . '/app/helpers.php';

echo "=== Testing Template XML Modification ===\n";

$templateFile = dirname(__DIR__) . '/storage/453_BM-INV_VI_2026 ZCLEAN LAUNDRY.xlsx';
$outputFile = dirname(__DIR__) . '/scratch/test_output.xlsx';

if (!file_exists($templateFile)) {
    echo "ERROR: Template file not found.\n";
    exit(1);
}

// Copy template to output file
copy($templateFile, $outputFile);

$zip = new ZipArchive();
if ($zip->open($outputFile) !== true) {
    echo "ERROR: Failed to open output zip.\n";
    exit(1);
}

$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
if ($sheetXml === false) {
    echo "ERROR: Failed to read sheet1.xml.\n";
    $zip->close();
    exit(1);
}

$sheet = simplexml_load_string($sheetXml);

function set_cell($sheet, $ref, $val, $type = 'n') {
    preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
    $rowNum = $m[2];

    // Find row
    $rowEl = null;
    foreach ($sheet->sheetData->row as $r) {
        if ((string) $r['r'] === $rowNum) {
            $rowEl = $r;
            break;
        }
    }
    if (!$rowEl) return;

    // Find cell
    $cellEl = null;
    foreach ($rowEl->c as $c) {
        if ((string) $c['r'] === $ref) {
            $cellEl = $c;
            break;
        }
    }
    if (!$cellEl) {
        $cellEl = $rowEl->addChild('c');
        $cellEl['r'] = $ref;
    }

    // Clear value
    unset($cellEl->v);
    unset($cellEl->is);

    if ($val === null || $val === '') {
        unset($cellEl['t']);
        return;
    }

    if ($type === 'inlineStr') {
        $cellEl['t'] = 'inlineStr';
        $is = $cellEl->addChild('is');
        $is->addChild('t', htmlspecialchars((string) $val, ENT_XML1, 'UTF-8'));
    } else {
        unset($cellEl['t']);
        $cellEl->addChild('v', (string) $val);
    }
}

// Set header details
set_cell($sheet, 'B12', 'TEST LAUNDRY BARU', 'inlineStr');
set_cell($sheet, 'F12', ': 23 Juni 2026', 'inlineStr');
set_cell($sheet, 'B13', 'Jl. Baru No. 123', 'inlineStr');
set_cell($sheet, 'B14', 'Kuta, Bali', 'inlineStr');
set_cell($sheet, 'F14', ': 999/BM-INV/TEST/2026', 'inlineStr');
set_cell($sheet, 'F16', ': PO-12345', 'inlineStr');
set_cell($sheet, 'B17', 'Bapak Budi', 'inlineStr');
set_cell($sheet, 'B18', 'hp : 0812-3456-7890', 'inlineStr');

// Set item details (we have 2 items)
// Item 1
set_cell($sheet, 'A23', 1);
set_cell($sheet, 'B23', 'N-IRON SUPER', 'inlineStr');
set_cell($sheet, 'C23', '20 KG', 'inlineStr');
set_cell($sheet, 'D23', 2);
set_cell($sheet, 'E23', 'pail', 'inlineStr');
set_cell($sheet, 'F23', 2310000);
set_cell($sheet, 'G23', 4620000);

// Item 2
set_cell($sheet, 'A25', 2);
set_cell($sheet, 'B25', 'DETERGENT CAIR', 'inlineStr');
set_cell($sheet, 'C25', '5 LITER', 'inlineStr');
set_cell($sheet, 'D25', 10);
set_cell($sheet, 'E25', 'jerigen', 'inlineStr');
set_cell($sheet, 'F25', 120000);
set_cell($sheet, 'G25', 1200000);

// Clear remaining item rows (27, 29, 31, 33, 35)
$clearRows = [27, 29, 31, 33, 35];
$cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
foreach ($clearRows as $r) {
    foreach ($cols as $c) {
        set_cell($sheet, $c . $r, '');
    }
}

// Set Total
set_cell($sheet, 'G36', 5820000);

// Date footer
set_cell($sheet, 'F42', '     Denpasar, 23 Juni 2026', 'inlineStr');

// Sales Signature
set_cell($sheet, 'F47', '( Ferdy )', 'inlineStr');

// Save XML back to zip
$newSheetXml = $sheet->asXML();
$zip->addFromString('xl/worksheets/sheet1.xml', $newSheetXml);
$zip->close();

echo "SUCCESS: Saved test output file to: " . $outputFile . "\n";
