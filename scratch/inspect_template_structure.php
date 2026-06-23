<?php
$filePath = dirname(__DIR__) . '/storage/453_BM-INV_VI_2026 ZCLEAN LAUNDRY.xlsx';
$zip = new ZipArchive();
if ($zip->open($filePath) === true) {
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    
    echo "Total length of sheet1.xml: " . strlen($sheetXml) . " bytes\n";
    
    // Parse using SimpleXML to examine structure
    $xml = simplexml_load_string($sheetXml);
    echo "Root name: " . $xml->getName() . "\n";
    
    // Dump some row tags
    echo "Dumping first few rows:\n";
    $i = 0;
    foreach ($xml->sheetData->row as $row) {
        $rowNum = (string) $row['r'];
        echo "Row $rowNum: ";
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $style = (string) $cell['s'];
            $type = (string) $cell['t'];
            $val = (string) $cell->v;
            echo "$ref [s=$style, t=$type, v=$val] | ";
        }
        echo "\n";
        $i++;
        if ($i >= 10) break;
    }
} else {
    echo "Failed to open zip file.\n";
}
