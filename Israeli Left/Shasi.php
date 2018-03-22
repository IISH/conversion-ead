<?php
/**
 * Created by IntelliJ IDEA.
 * User: Igor van der Bom
 * Date: 14-9-2017
 * Time: 11:19
 */

ini_set('max_execution_time', 300); //300 seconds = 5 minutes

echo date("Y-m-d H:i:s") ."<br>";

$counter = 1039;
$rootDirectory = "Greenstone Export";
$directory = "\Shasi - Israeli Socialist Left (shasi)";

$allDirectoryFiles = array();
$directoryToGetContentsFrom = $rootDirectory . $directory;
$allDirectoryFiles[] = getDirContents($directoryToGetContentsFrom);

function getDirContents($dir, &$results = array()){
    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(!is_dir($path)) {
            $results[] = $path;
        } else if($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}



foreach($allDirectoryFiles as $allDirectoryFile){
    $domDocumentItemNames = array();
    foreach($allDirectoryFile as $allFile){
        if(isset(pathinfo($allFile)['extension'])) {
            if (pathinfo($allFile)['extension'] !== "dir" && pathinfo($allFile)['extension'] === 'xml' && pathinfo($allFile, PATHINFO_FILENAME) === 'docmets' && array_key_exists('extension', pathinfo($allFile))) {

                $domDocument = new DOMDocument();
                $domDocument->load($allFile);

                $elements = $domDocument->getElementsByTagName("Metadata");
                $documentArray = array();
                $documentArray["subject"] = "Z. Without Subject";
                $titleIsSet = false;
                foreach($elements as $element){
                    if($element->getAttribute("name") === "Identifier") {
                        $documentArray['identifier'] = $element->nodeValue;
                    }
                    if($element->getAttribute("name") === "Title" || $element->getAttribute("name") === "dc.Title"){
                        if(!$titleIsSet) {
                            if ($element->nodeValue === '...' ||
                                strpos($element->nodeValue, 'A-PDF MERGER DEMO') !== false ||
                                strpos($element->nodeValue, 'Plase register PDF Split-Merge') !== false ||
                                strpos($element->nodeValue, 'A-PDF Split DEMO') !== false) {
                                $documentArray['title'] = "A-PDF Split DEMO";
                            } else {
                                $documentArray['title'] = $element->nodeValue;
                                $titleIsSet = true;
                            }
                        }
                    }
                    if($element->getAttribute("name") === "dc.Subject"){
                        $documentArray["subject"] = $element->nodeValue;
                    }
                }
                $documentArray['count'] = 0;
                $domDocumentItemNames[] = $documentArray;
            }
        }
    }

    foreach ($domDocumentItemNames as &$domDocumentItemName){
        $domDocumentItemName = str_replace('"', '', $domDocumentItemName);
    }

    sort($domDocumentItemNames);

    foreach ($domDocumentItemNames as &$domDocumentItemName){
        $domDocumentItemName['count'] = $counter;
        $counter++;
    }

    foreach($allDirectoryFile as $allFile){
        if(isset(pathinfo($allFile)['extension'])) {
            if (pathinfo($allFile)['extension'] !== "dir" && pathinfo($allFile)['extension'] === 'xml' && pathinfo($allFile, PATHINFO_FILENAME) === 'docmets' && array_key_exists('extension', pathinfo($allFile))) {

                $domDocument = new DOMDocument();
                $domDocument->load($allFile);

                $elements = $domDocument->getElementsByTagName("Metadata");

                $elementToReplace = null;

                /**
                 * Check for existing UnitID element
                 */
                $xpath = new DOMXpath($domDocument);
                $xpathElements = $xpath->query("/mets:mets/mets:dmdSec/mets:mdWrap/mets:xmlData/gsdl3:Metadata");
                foreach($xpathElements as $xpathElement){
                    if($xpathElement->getAttribute("name") === "UnitID"){
                        $elementToReplace = $xpathElement;
                    }
                }

                /**
                 * Loop through the elements of the file
                 */
                foreach($elements as $element => $metadata){

                    if ($metadata->getAttribute("name") === "Identifier") {
                        foreach($domDocumentItemNames as $domDocumentItemName){
                            if ($metadata->nodeValue === $domDocumentItemName['identifier']) {
                                $unitId = $domDocument->createElement("gsdl3:Metadata", $domDocumentItemName['count']);
                                $unitId->setAttribute("name", "UnitID");
                                if($elementToReplace !== null) {
                                    try {
                                        $newElement = $elements[$element]->parentNode->replaceChild($unitId, $elementToReplace);
                                    } catch (Exception $e) {
                                        print("<br>");
                                        print("Error!! : ");
                                        print_r($allFile);
                                    }
                                }else {
                                    $newElement = $elements[$element]->parentNode->appendChild($unitId);
                                }
                            }
                        }
                    }
                }
                $domDocument->save($allFile);
            }
        }
    }
}

echo "End of script!";