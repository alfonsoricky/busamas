<?php
$filePath = dirname(__DIR__) . '/storage/453_BM-INV_VI_2026 ZCLEAN LAUNDRY.xlsx';
$zip = new ZipArchive();
if ($zip->open($filePath) === true) {
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    $sharedStrings = [];
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);
        foreach ($xml->si as $si) {
            $text = '';
            if (isset($si->t)) {
                $text = (string) $si->t;
            } else {
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
            }
            $sharedStrings[] = $text;
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    
    $xml = simplexml_load_string($sheetXml);
    echo "Dumping cells with values:\n";
    foreach ($xml->sheetData->row as $row) {
        $rowNum = (string) $row['r'];
        $rowStr = "";
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $type = (string) $cell['t'];
            $val = (string) $cell->v;
            if ($type === 's') {
                $valStr = $sharedStrings[(int) $val] ?? $val;
            } else {
                $valStr = $val;
            }
            if (trim($valStr) !== '') {
                $rowStr .= "$ref: \"$valStr\" | ";
            }
        }
        if ($rowStr !== "") {
            echo "Row $rowNum -> $rowStr\n";
        }
    }
}
