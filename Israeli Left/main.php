<?php
/**
 * Created by IntelliJ IDEA.
 * User: Igor van der Bom
 * Date: 4-7-2017
 * Time: 14:03
 */
print("Start script <br>");
ini_set('max_execution_time', 300); //300 seconds = 5 minutes

echo date("Y-m-d H:i:s") . "<br>";

$counter = 1;
$rootDirectory = "Greenstone Export";
$json = file_get_contents("Data/directories.json");
$jsonIterator = new RecursiveIteratorIterator(
    new RecursiveArrayIterator(json_decode($json, TRUE)),
    RecursiveIteratorIterator::SELF_FIRST);
$directories = array();
foreach ($jsonIterator as $key => $val) {
    if (is_array($val)) {
    } else {
        array_push($directories, $val);
    }
}

$allDirectoryFiles = array();
foreach ($directories as $directory) {
    $directoryToGetContentsFrom = $rootDirectory . $directory;
    $allDirectoryFiles[] = getDirContents($directoryToGetContentsFrom);
}

function getDirContents($dir, &$results = array())
{
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

foreach ($allDirectoryFiles as $allDirectoryFile) {
    $domDocumentItemNames = array();
    foreach ($allDirectoryFile as $allFile) {
        if (isset(pathinfo($allFile)['extension'])) {
            if (pathinfo($allFile)['extension'] !== "dir" && pathinfo($allFile)['extension'] === 'xml' && pathinfo($allFile, PATHINFO_FILENAME) === 'docmets' && array_key_exists('extension', pathinfo($allFile))) {

                $domDocument = new DOMDocument();
                $domDocument->load($allFile);

                $elements = $domDocument->getElementsByTagName("Metadata");
                $documentArray = array();
                $titleIsSet = false;
                foreach ($elements as $element) {
                    if ($element->getAttribute("name") === "Identifier") {
                        $documentArray['identifier'] = $element->nodeValue;
                    }
                    if ($element->getAttribute("name") === "Title" || $element->getAttribute("name") === "dc.Title") {
                        if (!$titleIsSet) {
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
                }
                $documentArray['count'] = 0;
                $domDocumentItemNames[] = $documentArray;

            }
        }
    }

    for ($i = 0; $i < count($domDocumentItemNames); $i++) {
        $domDocumentItemNames{$i} = str_replace('"', '', $domDocumentItemNames[$i]);
    }

    sort($domDocumentItemNames);

    for ($i = 0; $i < count($domDocumentItemNames); $i++) {
        $domDocumentItemNames[$i]['count'] = $counter;
        $counter++;
        print('<br>');
        print_r($domDocumentItemNames[$i]);
    }

    foreach ($allDirectoryFile as $allFile) {
        if (isset(pathinfo($allFile)['extension'])) {
            if (pathinfo($allFile)['extension'] !== "dir" && pathinfo($allFile)['extension'] === 'xml' && pathinfo($allFile, PATHINFO_FILENAME) === 'docmets' && array_key_exists('extension', pathinfo($allFile))) {

                $domDocument = new DOMDocument();
                $domDocument->load($allFile);
                $domDocument->formatOutput = true;

                $elements = $domDocument->getElementsByTagName("Metadata");

                /**
                 * Check for existing UnitID element
                 */
                $xpath = new DOMXpath($domDocument);
                $xpathElements = $xpath->query("/mets:mets/mets:dmdSec/mets:mdWrap/mets:xmlData/gsdl3:Metadata");
                foreach ($xpathElements as $xpathElement) {
                    if ($xpathElement->getAttribute("name") === "UnitID") {
                        $xpathElement->parentNode->removeChild($xpathElement);
                    }
                }

                /**
                 * Loop through the elements of the file
                 */
                $identifier = "";
                $title = "";
                $titleChecked = false;
                $elementToWorkWith = null;
                foreach ($elements as $element => $metadata) {
                    if ($metadata->getAttribute("name") === "Identifier") {
                        $identifier = $metadata->nodeValue;
                        $elementToWorkWith = $element;
                        foreach ($domDocumentItemNames as $domDocumentItemName) {
                            if ($metadata->nodeValue === $domDocumentItemName['identifier']) {
                                $unitId = $domDocument->createElement("gsdl3:Metadata", $domDocumentItemName['count']);
                                $unitId->setAttribute("name", "UnitID");
                                $newElement = $elements[$element]->parentNode->appendChild($unitId);
                            }
                        }
                    }

                    $domDocument->save($allFile);
                }
            }
        }
    }
}

echo "End of script!";
