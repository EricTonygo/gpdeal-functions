<?php

// If you need to parse XLS files, include php-excel-reader
require('php-excel-reader/excel_reader2.php');

require('./SpreadsheetReader.php');
//require('./gpdeal-functions.php');

$Reader = new SpreadsheetReader('countries.xlsx');
$Sheets = $Reader->Sheets();

foreach ($Sheets as $Index => $Name) {
    //if ($Name == "Cameroun") {
        echo 'Sheet #' . $Index . ': ' . $Name.'\n';

        $Reader->ChangeSheet($Index);

        echo 'Nombre de ligne: '.count($Reader).'\n';
        $i=0;
        foreach ($Reader as $Row) {
            if($i==2){
                print_r($Row);
            }
            $i++;
        }
    //}
}

