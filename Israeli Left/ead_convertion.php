<?php
/**
 * Created by IntelliJ IDEA.
 * User: Igor van der Bom
 * Date: 5-9-2017
 * Time: 10:24
 */

ini_set('max_execution_time', 3000);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require_once "misc_functions.php";

echo date("Y-m-d H:i:s") . "<br>";
print("<br>Starting script!<br>");

/**
 * Variables used to determine the directory in which the files need to be looked and collected
 */
$rootDirectory = "/Greenstone Export";
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

$eadFile = "Templates/ead_israeli.xml";
$c01File = "Templates/c01_israeli.xml";
$c03File = "Templates/c01_c03_israeli.xml";

$allDirectoryFiles = array();
foreach ($directories as $testDirectory) {
    $directoryToGetContentsFrom = $rootDirectory . $testDirectory;
    $allDirectoryFiles[] = getDirContents($directoryToGetContentsFrom);
}

/**
 * Array to hold the c01DomDocuments after looping through the directory
 */
$c01DomDocuments = array();

print("<br>Adding c02 elements to c01 per directory!<br>");

foreach ($allDirectoryFiles as $allDirectoryFile) {
    $israeliFiles = array();
    /**
     * gets the docmets file from the given directory.
     */
    foreach ($allDirectoryFile as $fullFileName) {
        if (basename($fullFileName) === "docmets.xml") {
            $israeliFiles[] = $fullFileName;
        }
    }

    /**
     * elements used during each loop of the files
     */
    $docmetsUnitID = '';
    $docmetsTitle = '';
    $docmetsDate = '';
    $docmetsType = '';
    $docmetsCreator = '';
    $docmetsLanguage = '';
    $docmetsSource = '';
    $docmetsSubject = '';
    $docmetsPublisher = '';
    $docmetsNumPages = '';
    $domNodesToRemove = array();
    $counter = 0;
    $timesPassed = 0;

    /**
     * Creating the C01 file Dom Document
     */
    $c01DomDocument = new DOMDocument();
    $c01DomDocument->preserveWhiteSpace = false;
    $c01DomDocument->load($c01File);
    $c01DomDocument->formatOutput = true;

    /**
     * Creating the C03 file Dom Document
     */
    $c03DomDocument = new DOMDocument();
    $c03DomDocument->preserveWhiteSpace = false;
    $c03DomDocument->load($c03File);
    $c03DomDocument->formatOutput = true;

    /**
     * Preparation to create the c02 Dom Node.
     */
    $c02xPath = new DOMXPath($c01DomDocument);
    $c02query = "/ead/archdesc/dsc/c01/c02";
    $c02xPathQuery = $c02xPath->query($c02query);
    $c02child = $c02xPathQuery->item(0)->cloneNode(true);

    $c03DomDocumentXPath = new DOMDocument();
    $c03DomDocumentXPath->preserveWhiteSpace = false;
    $c03DomDocumentXPath->load($c03File);
    $c03DomDocumentXPath->formatOutput = true;
    $c03xPath = new DOMXPath($c03DomDocument);
    $c03query = "/ead/archdesc/dsc/c01/c02";
    $c03xPathQuery = $c03xPath->query($c03query);
    $c03child = $c03xPathQuery->item(0);

    /**
     * Used to determine whether this is the first loop or not
     * This for the filling of the information for dc.Source
     */
    $firstLoop = true;

    $explodedIsraeliFiles = array();

    /**
     * Loop through the files and add them to the main file
     */
    foreach ($israeliFiles as $israeliFile) {
        /**
         * Elements to be set to empty string for it being possible that no data exists in the docmets files
         */
        $docmetsUnitID = '';
        $docmetsTitle = '';
        $docmetsDate = '';
        $docmetsType = '';
        $docmetsCreator = '';
        $docmetsLanguage = '';
        $docmetsSubject = '';
        $docmetsPublisher = '';
        $docmetsNumPages = '';

        $docmetsDomDocument = new DOMDocument();
        $docmetsDomDocument->load($israeliFile);

        $explodedIsraeliFiles = explode('\\', $israeliFile);

        for ($i = 0; $i < count($explodedIsraeliFiles); $i++) {
            if ($explodedIsraeliFiles[$i] === "Greenstone Export") {
                $docmetsSource = $explodedIsraeliFiles[$i + 1];
            }
        }

        {

            /**
             * Filling the data from the israeliFiles in the variables
             */
            $docmetsElements = $docmetsDomDocument->getElementsByTagName("Metadata");
            foreach ($docmetsElements as $docmetsElement => $metadata) {
                if ($metadata->getAttribute("name") === "UnitID") {
                    $docmetsUnitID = $metadata->nodeValue;
                } else if ($metadata->getAttribute("name") === "dc.Title") {
                    $docmetsTitle = $metadata->nodeValue;
                } else if ($metadata->getAttribute("name") === "dc.Date") {
                    $docmetsDate = $metadata->nodeValue;
                    if (strlen($docmetsDate) === 4) {
                        $docmetsDate = $docmetsDate . '0101';
                        $docmetsDate = date("Y", strtotime($docmetsDate));
                    } else if (strlen($docmetsDate) === 6) {
                        $docmetsDate = $docmetsDate . '01';
                        $docmetsDate = date("m-Y", strtotime($docmetsDate));
                    } else if (strlen($docmetsDate) === 8) {
                        $docmetsDate = date("d-m-Y", strtotime($docmetsDate));
                    }
                } else if ($metadata->getAttribute("name") === "dc.Type") {
                    $docmetsType = $metadata->nodeValue;
                } else if ($metadata->getAttribute("name") === "dc.Creator") {
                    if ($docmetsCreator !== "" && !is_array($docmetsCreator)) {
                        $temp = array();
                        $temp[] = $docmetsCreator;
                        $temp[] = $metadata->nodeValue;
                        $docmetsCreator = $temp;
                    } else if (is_array($docmetsCreator)) {
                        $docmetsCreator[] = $metadata->nodeValue;
                    } else {
                        $docmetsCreator = $metadata->nodeValue;
                    }
                } else if ($metadata->getAttribute("name") === "dc.Language") {
                    $docmetsLanguage = $metadata->nodeValue;
                } else if ($metadata->getAttribute("name") === "dc.Publisher") {
                    if (empty($docmetsPublisher)) {
                        $docmetsPublisher = $metadata->nodeValue;
                    }
                } else if ($metadata->getAttribute("name") === "NumPages") {
                    $docmetsNumPages = $metadata->nodeValue;
                } else if ($metadata->getAttribute("name") === "dc.Subject") {
                    $docmetsSubject = $metadata->nodeValue;
                }
            }

            /**
             * Check whether this is the first loop to set the dc.Source and fill in the information for the first iteration
             */
            if ($firstLoop) {
                /**
                 * Loop through the c01 file to find the locations where the data needs to be placed
                 */
                foreach ($c01DomDocument->documentElement->childNodes as $c01) {
                    /**
                     * Check whether the Node is of Node Type XML_ELEMENT_NODE.
                     */
                    if ($c01->nodeType === XML_ELEMENT_NODE) {
                        $oldSource = $c01->getElementsByTagName('unittitle')->Item(0);
                        $newSource = $c01DomDocument->createElement('unittitle', $docmetsSource);
                        $oldSource->parentNode->replaceChild($newSource, $oldSource);

                        $oldUnitID = $c01->getElementsByTagName('unitid')->Item(0);
                        $newUnitID = $c01DomDocument->createElement('unitid', $docmetsUnitID);
                        $oldUnitID->parentNode->replaceChild($newUnitID, $oldUnitID);

                        $oldTitle = $c01->getElementsByTagName('unittitle')->Item(1);
                        $newTitle = $c01DomDocument->createElement('unittitle', $docmetsTitle . '. ');
                        $newDate = $c01DomDocument->createElement('unitdate', $docmetsDate . '.');
                        $newTitle->appendChild($newDate);
                        $oldTitle->parentNode->replaceChild($newTitle, $oldTitle);

                        $oldType = $c01->getElementsByTagName('genreform')->Item(0);
                        $oldType->nodeValue = str_replace("{dc.Type}", $docmetsType, $oldType->nodeValue);
                        $oldType->nodeValue = str_replace("{NumPages}", $docmetsNumPages, $oldType->nodeValue);

                        $oldCreator = $c01->getElementsByTagName('origination')->Item(0);
                        $newCreator = $c01DomDocument->createElement('origination', $docmetsCreator);
                        $newCreator->setAttribute('label', 'Creator');
                        $oldCreator->parentNode->replaceChild($newCreator, $oldCreator);

                        $oldLanguage = $c01->getElementsByTagName('language')->Item(0);
                        $newLanguage = $c01DomDocument->createElement('language', $docmetsLanguage);
                        $oldLanguage->parentNode->replaceChild($newLanguage, $oldLanguage);

                        $oddElements = array();
                        $oddElements = $c01->getElementsByTagName('odd');
                        foreach ($oddElements as $oddElement) {
                            if ($oddElement->attributes->Item(0)->nodeValue === "Publisher") {
                                if ($docmetsPublisher !== "") {
                                    $oddElement->nodeValue = str_replace("{dc.Publisher}", $docmetsPublisher, $oddElement->nodeValue);
                                } else {
                                    $domNodesToRemove[] = $oddElement;
                                }
                            } else if ($oddElement->attributes->Item(0)->nodeValue === "Subject") {
                                if ($docmetsSubject !== "") {
                                    $oddElement->nodeValue = str_replace("{dc.Subject}", $docmetsSubject, $oddElement->nodeValue);
                                } else {
                                    $domNodesToRemove[] = $oddElement;
                                }
                            }
                        }
                    }
                }
            } else {
                /**
                 * Create a new c02 Dom Node to be appended at the end of the c02 collection
                 */
                $c02DomNode = $c02child->cloneNode(true);

                /**
                 * Replacing the fields in the c02 with the information collected from the israeli documents
                 * After checking whether the Node is of Node Type XML_ELEMENT_NODE
                 */
                if ($c02DomNode->nodeType === XML_ELEMENT_NODE) {
                    $timesPassed++;
                    $oldUnitID = $c02DomNode->getElementsByTagName('unitid')->Item(0);
                    $newUnitID = $c01DomDocument->createElement('unitid', $docmetsUnitID);
                    $oldUnitID->parentNode->replaceChild($newUnitID, $oldUnitID);

                    $oldTitle = $c02DomNode->getElementsByTagName('unittitle')->Item(0);
                    $newTitle = $c01DomDocument->createElement('unittitle', $docmetsTitle . '. ');
                    $newDate = $c01DomDocument->createElement('unitdate', $docmetsDate . '.');
                    $newTitle->appendChild($newDate);
                    $oldTitle->parentNode->replaceChild($newTitle, $oldTitle);

                    $oldType = $c02DomNode->getElementsByTagName('genreform')->Item(0);
                    $oldType->nodeValue = str_replace("{dc.Type}", $docmetsType, $oldType->nodeValue);
                    $oldType->nodeValue = str_replace("{NumPages}", $docmetsNumPages, $oldType->nodeValue);

                    $oldCreator = $c02DomNode->getElementsByTagName('origination')->Item(0);
                    $oldCreatorParentNode = $oldCreator->parentNode;
                    if (is_array($docmetsCreator)) {
                        $firstCreator = true;
                        $elementToPlaceTheCreatorAfter = null;
                        foreach ($docmetsCreator as $item) {
                            $newCreator = $c01DomDocument->createElement('origination', $item);
                            $newCreator->setAttribute('label', 'Creator');
                            if ($firstCreator) {
                                $oldCreatorParentNode->replaceChild($newCreator, $oldCreator);
                                $firstCreator = false;
                                $elementToPlaceTheCreatorAfter = $newCreator;
                            } else {
                                $elementToPlaceTheCreatorAfter->parentNode->insertBefore($newCreator, $elementToPlaceTheCreatorAfter->nextSibling);
                                $elementToPlaceTheCreatorAfter = $newCreator;
                            }
                        }
                    } else {
                        $newCreator = $c01DomDocument->createElement('origination', $docmetsCreator);
                        $newCreator->setAttribute('label', 'Creator');
                        $oldCreatorParentNode->replaceChild($newCreator, $oldCreator);
                    }

                    $oldLanguage = $c02DomNode->getElementsByTagName('language')->Item(0);
                    $newLanguage = $c01DomDocument->createElement('language', $docmetsLanguage);
                    $oldLanguage->parentNode->replaceChild($newLanguage, $oldLanguage);

                    $oddElements = array();
                    $oddElements = $c02DomNode->getElementsByTagName('odd');
                    foreach ($oddElements as $oddElement) {
                        if ($oddElement->attributes->Item(0)->nodeValue === "Publisher") {
                            if ($docmetsPublisher !== "") {
                                $oddElement->nodeValue = str_replace("{dc.Publisher}", $docmetsPublisher, $oddElement->nodeValue);
                            } else {
                                $domNodesToRemove[] = $oddElement;
                            }
                        } else if ($oddElement->attributes->Item(0)->nodeValue === "Subject") {
                            if ($docmetsSubject !== "") {
                                $oddElement->nodeValue = str_replace("{dc.Subject}", $docmetsSubject, $oddElement->nodeValue);
                            } else {
                                $domNodesToRemove[] = $oddElement;
                            }
                        }
                    }
                }

                /**
                 * Prepare variables to determine where the c02 Dom Node needs to be placed
                 */
                $unitIDToTest = $c02DomNode->getElementsByTagName('unitid')->item(0)->nodeValue;
                $unitIDElement = $c02DomNode->getElementsByTagName('unitid')->item(0);
                $unitIDClosest = '';

                /**
                 * Loop through the c01 Dom Document
                 */
                foreach ($c01DomDocument->documentElement->childNodes as $c01) {

                    $unitIDClosestDifference = PHP_INT_MAX;

                    /**
                     * Check whether the Node Type is XML Element Node
                     */
                    if ($c01->nodeType === XML_ELEMENT_NODE) {

                        /**
                         * Get the c02 and the unitid elements form c01
                         */
                        $c01Element = $c01->getElementsByTagName('c02')->Item(0);
                        $unitIDsExisting = $c01->getElementsByTagName('unitid');

                        /**
                         * loop through the unitid elements to determine the difference between unitids
                         * to determine the location of the new Dom Node
                         */
                        foreach ($unitIDsExisting as $unitID) {

                            $unitIDDifference = $unitIDToTest - $unitID->nodeValue;
                            if ($unitIDDifference < 0) {
                                $unitIDDifference = $unitIDDifference * -1;
                            }
                            if ($unitIDDifference < $unitIDClosestDifference) {
                                $unitIDClosestDifference = $unitIDDifference;
                                $unitIDClosest = $unitID->parentNode->parentNode;
                            }
                        }

                        /**
                         * Calculate on which side the new Dom Node needs to be placed of the closest existing Node
                         * And then do so. Eliminating the ones that are the same.
                         */
                        if ($unitIDToTest !== $unitIDClosest->getElementsByTagName('unitid')->item(0)->nodeValue) {
                            $result = ($unitIDToTest - $unitIDClosest->getElementsByTagName('unitid')->item(0)->nodeValue < 0) ? 'before' : 'after';
                            if ($result === 'before') {
                                $unitIDClosest->parentNode->insertBefore($c02DomNode, $unitIDClosest);
                            } else {
                                $unitIDClosest->parentNode->insertBefore($c02DomNode, $unitIDClosest->nextSibling);
                            }
                        }
                    }
                }
            }
        }

        /**
         * Set the variable firstLoop to false to indicate the dc.Source does not need to be replaced
         */
        $firstLoop = false;
    }

    foreach ($domNodesToRemove as $item) {
        if (is_null($item) || is_null($item->parentNode)) {
        } else {
            $item->parentNode->removeChild($item);
        }
    }

    /**
     * Saves the EAD
     */
    $c01DomDocument->save('Completed EAD/testEAD.xml');

    /**
     * Add the c01 Dom Document to the array to append it to the main EAD
     */
    $c01DomDocuments[] = $c01DomDocument;
}
print("<br>Adding c01 elements to EAD!");

/**
 * Add the c01DomDocuments to the main EAD
 */
addC01DataToTheEad($c01DomDocuments, $eadFile, 'Completed EAD/testEAD.xml');

print("<br>Finished script!");

/**
 * Function to add the c01 Dom Documents to the main EAD
 * @param $c01DomDocuments
 * @param $eadFileToLoad
 * @param $eadFileToSave
 */
function addC01DataToTheEad($c01DomDocuments, $eadFileToLoad, $eadFileToSave)
{
    $eadDomDocument = new DOMDocument();
    $eadDomDocument->preserveWhiteSpace = false;
    $eadDomDocument->formatOutput = true;
    $eadDomDocument->load($eadFileToLoad);
    $firstLoop = true;

    $unitIDClosest = '';

    foreach ($c01DomDocuments as $c01DomDocument) {
        $c01xPath = new DOMXPath($c01DomDocument);
        $c01query = "/ead/archdesc/dsc/c01";
        $c01xPathQuery = $c01xPath->query($c01query);
        if ($c01xPathQuery !== null || !is_bool($c01xPathQuery))
            $c01child = $c01xPathQuery->item(0);

        $dscElements = $eadDomDocument->getElementsByTagName('dsc');

        foreach ($dscElements as $dscElement) {

            $eadC01Element = $dscElement->getElementsByTagName('c01')->Item(0);
            $eadC01ElementsToCheck = $dscElement->getElementsByTagName('c01');

            if ($eadC01Element->nodeType === XML_ELEMENT_NODE) {

                if ($firstLoop) {
                    $eadC01Element->parentNode->replaceChild($eadDomDocument->importNode($c01child, true), $eadC01Element);
                } else {

                    $unitIDClosestDifference = PHP_INT_MAX;

                    $eadC01UnitIDElements = array();
                    foreach ($eadC01ElementsToCheck as $elementToCheck) {
                        $eadC01UnitIDElements[] = $elementToCheck->getElementsByTagName('unitid')->item(0);
                    }

                    $c01UnitIDElement = $c01child->getElementsByTagName('unitid')->item(0);

                    foreach ($eadC01UnitIDElements as $eadC01UnitIDElement) {
                        $unitIdDifference = $c01UnitIDElement->nodeValue - $eadC01UnitIDElement->nodeValue;

                        if ($unitIdDifference < 0) {
                            $unitIdDifference = $unitIdDifference * -1;
                        }
                        if ($unitIdDifference < $unitIDClosestDifference) {
                            $unitIDClosestDifference = $unitIdDifference;
                            $unitIDClosest = $eadC01UnitIDElement;
                        }
                    }

                    $result = ($c01UnitIDElement->nodeValue - $unitIDClosest->nodeValue < 0) ? 'before' : 'after';
                    if ($result === 'before') {
                        $eadC01Element->parentNode->insertBefore($eadDomDocument->importNode($c01child, true), $unitIDClosest->parentNode->parentNode->parentNode);
                    } else {
                        $eadC01Element->parentNode->insertBefore($eadDomDocument->importNode($c01child, true), $unitIDClosest->parentNode->parentNode->parentNode->nextSibling);
                    }
                }
                $firstLoop = false;
            }
        }
    }

    $eadDomDocument->save($eadFileToSave);
}

