<?php
print("Reading parameters and starting program.\n");
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

print("Starting to process the data provided.");
processData($vele_handen_data, $tagNames, $folios);
print("Done processing.\n");
print("Sorting the column names in alphabetical order.\n");
natsort($tagNames);
print("Done sorting.\n");
print("Writing the data to the CSV file.");
writeToCSV($tagNames, $folios, $vele_handen_output);
print("Done writing to CSV.\n");
print("File can be found under the name: $vele_handen_output.\n");

// Writes the output to a csv file
function writeToCSV($tagNames, $folios, $outputCSV){
    $rows = array();
    // Loops through al the folios to output a folio on each row.
    foreach($folios as $folio){
        print(".");
        $row = array();
        // Loops through the tag names to compare them with the folio keys to make sure the correct data is in the correct place.
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

    // Opens the csv file (or creates it when not existing)
    $csvFile = fopen($outputCSV, 'w');
    // Writes the column names to the csv file
    fputcsv($csvFile, $tagNames, ';');

    // Writes each row to the csv file.
    foreach($rows as $row) {
        fputcsv($csvFile, $row, ';');
    }
    // Closes the csv file after writing to it.
    fclose($csvFile);
    print(".\n");
}

// Processes the data given
function processData($vele_handen_data, &$tagNames, &$folios){
    // handle the values!
    foreach ($vele_handen_data as $vele_handen_key => $vele_handen_value) {
        $folio = array();
        // Loops through all objects gathered by looking for folio tags
        foreach ($vele_handen_value->childNodes as $folio_key => $folio_value) {
            print(".");
            // Checks if the data in the tag is an array of array(s), so it splits it and creates new tag names and data sets.
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
            } elseif (substr($folio_value->nodeValue, 0, 2) == '["') { // Checks if the data in the tag is a simple array and safes it with the tag given by the xml fil.
                $json = json_decode($folio_value->nodeValue);
                $folio_string_value = "";
                foreach ($json as $json_value) {
                    $folio_string_value = $folio_string_value . $json_value . ", ";
                }
                $folio_string_value = trim($folio_string_value, ", ");
                $folio[$folio_value->nodeName] = $folio_string_value;
                if (!in_array($folio_value->nodeName, $tagNames))
                    array_push($tagNames, $folio_value->nodeName);
            } else { // It is just as simple value to save. e.g. "1234"
                $folio[$folio_value->nodeName] = $folio_value->nodeValue;
                if (!in_array($folio_value->nodeName, $tagNames))
                    array_push($tagNames, $folio_value->nodeName);
            }
        }
        $folios[$folio["uuid"]] = $folio;
    }
    print(".\n");
}

// Checks whether the given string can be converted to JSON.
function isJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}