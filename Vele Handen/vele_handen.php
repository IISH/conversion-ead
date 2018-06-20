<?php
$vele_handen_file = $argv[1];
$vele_handen_file = realpath($vele_handen_file);
$vele_handen_xml = new DOMDocument();
$vele_handen_xml->preserveWhiteSpace = false;
$vele_handen_xml->load($vele_handen_file);
$vele_handen_xml->formatOutput = true;
$vele_handen_data_finder = new DOMXPath($vele_handen_xml);
$vele_handen_data = $vele_handen_data_finder->query("//folio");
$vele_handen_output = trim($vele_handen_file, ".xml") . "_output.csv";
$folios = array();
$tagNames = array();

processData($vele_handen_data, $tagNames, $folios);
natsort($tagNames);
writeToCSV($tagNames, $folios, $vele_handen_output);

function writeToCSV($tagNames, $folios, $outputCSV){
    $rows = array();
    foreach($folios as $folio){
        $row = array();
        foreach($tagNames as $tagName){
            $tagNameFound = false;
            foreach($folio as $folio_key => $folio_value){
                if($folio_key == $tagName){
                    $row[$tagName] = $folio_value;
                    $tagNameFound = true;
                }
            }
            if(!$tagNameFound){
                $row[$tagName] = "";
            }
        }
        array_push($rows, $row);
    }

    $csvFile = fopen($outputCSV, 'w');
    fputcsv($csvFile, $tagNames, ';');

    foreach($rows as $row) {
        fputcsv($csvFile, $row, ';');
    }
    fclose($csvFile);
}

function processData($vele_handen_data, &$tagNames, &$folios){
    // handle the values!
    foreach ($vele_handen_data as $vele_handen_key => $vele_handen_value) {
        $folio = array();
        foreach ($vele_handen_value->childNodes as $folio_key => $folio_value) {
            if (substr($folio_value->nodeValue, 0, 2) == "[{") {
                if (isJson($folio_value->nodeValue)) {
                    $json = json_decode($folio_value->nodeValue);
                    $counter = 1;
                    foreach ($json as $json_value) {
                        $stdClassArray = (array)$json_value;
                        foreach ($stdClassArray as $stdKey => $stdValue) {
                            $csv_tag = $folio_value->nodeName . "_" . $stdKey . "_" . $counter;
                            $folio[$csv_tag] = $stdValue;
                            if (!in_array($csv_tag, $tagNames))
                                array_push($tagNames, $csv_tag);
                        }
                        ++$counter;
                    }
                }
            } elseif (substr($folio_value->nodeValue, 0, 2) == '["') {
                $json = json_decode($folio_value->nodeValue);
                $folio_string_value = "";
                foreach ($json as $json_value) {
                    $folio_string_value = $folio_string_value . $json_value . ", ";
                }
                $folio_string_value = trim($folio_string_value, ", ");
                $folio[$folio_value->nodeName] = $folio_string_value;
                if (!in_array($folio_value->nodeName, $tagNames))
                    array_push($tagNames, $folio_value->nodeName);
            } else {
                $folio[$folio_value->nodeName] = $folio_value->nodeValue;
                if (!in_array($folio_value->nodeName, $tagNames))
                    array_push($tagNames, $folio_value->nodeName);
            }
        }
        $folios[$folio["uuid"]] = $folio;
    }
    return $folios;
}

function isJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}