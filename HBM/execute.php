<?php
// think it is needed?
ini_set('max_execution_time', 500);
ini_set('auto_detect_line_endings', TRUE);
require_once "lucien-export.php";

// display date to show the code has done something
echo date("Y-m-d H:i:s") ."<br>";
echo "Start"."<br>";

// Target directory for the output
mkdir("target");
$targetdir = "target/";
// load template for collection
$ead_template = load_file("ead_hbm.xml");
// load template for photo
$c01_template = load_file("c01_hbm.xml");
// database source
$csvCollectionsFile = "Data/Collecties.csv";
// photo's source
$csvPhotosFile = "Data/Fotos.csv";

// collection variables
$collectionArray = array();
$collectionHeader = array();
$collectionRow = 1;
$archnrCounter = 350;
// photos variables
$photosArray = array();
$photoHeader = array();
$photoRow = 1;

// open the CSV Collections document.
if (($handle = utf8_fopen_read($csvCollectionsFile)) !== false) {
    $previousCollection = array();
    // Loop through every record in the document
    while (($collection = fgetcsv($handle, 0, ';', "\n"))) {
        $numOfCollectionColumns = count($collection);

        // add every item to the collectionArray
        if ($collectionRow === 1) {
            // First row contains column names
            $collectionHeader = array_flip($collection);
            $collectionArray[] = $collectionHeader;
        } else { // This part of the code is for when there is a break line in one of the fields present in the csv (usually the description field)
            if ($numOfCollectionColumns === count($collectionHeader)) {
                $collectionArray[] = $collection;
            } else if ($numOfCollectionColumns > count($collectionHeader) && count($previousCollection) !== 0) { // Not sure about this one
                // This is done so the $previousCollection isn't unnecessarily filled.
                $tempCollection = array();
                foreach ($collection as $col) {
                    $tempCollection[] = $col;
                    unset($col);
                }
                $collectionArray[] = $tempCollection;
                $previousCollection = $collection;
            } else if ($numOfCollectionColumns > count($collectionHeader) && count($previousCollection) === 0) {
                echo "Something is not right!" . "<br>";
                echo "The amount of columns in the current data set is: " . $numOfCollectionColumns . "<br>";
                echo "The amount of columns from the previous is 0" . "<br>";
                print_r($collection);
                echo "<br>";
                $collectionArray[] = $collection;
            } else if(count($previousCollection) > count($collectionHeader)){
                print("<br>");
                print("The number of columns superceeds the number of columns on the header");
            } else if (count($previousCollection) !== 0 && strlen($collection[0]) !== 0) {
                $temp = $previousCollection[count($previousCollection) - 1] . "{paragraph}" . $collection[0];
                $previousCollection[count($previousCollection) - 1] = $temp;
                unset($collection[0]);
                foreach ($collection as $col) {
                    $previousCollection[] = $col;
                }
                if (count($previousCollection) === count($collectionHeader)) {
                    $collectionArray[] = $previousCollection;
                    unset($previousCollection);
                    $previousCollection = array();
                }
            } else if (count($previousCollection) === 0) {
                $previousCollection = $collection;
            } else if (strlen($collection[0]) === 0) {
            } else {
                echo "Whoops!!! Something went wrong with this collection" . "<br>";
                print_r($previousCollection);
                echo "<br>";
                print_r($collection);
                echo "<br>";
            }
        }
        $collectionRow++;
    }
}

global $csvPhotosFile, $photoRow, $photoHeader;
// open the CSV Photos document.
if (($handle = utf8_fopen_read($csvPhotosFile)) !== false) {
    // Loop through every record in the document.
    $previousPhoto = array();
    while (($photos = fgetcsv($handle, 0, ';', "\"")) !== false) {

        // count the number of items in the column to use to compare the rest of the data with
        $numOfColumns = count($photos);

        // add every item to the photoArray according to the checks
        if ($photoRow === 1) {
            $photoHeader = array_flip($photos);
            $photosArray[] = $photoHeader;
        } else { // This part of the code is for when there is a break line in one of the fields present in the csv (usually the description field)
            if ($numOfColumns === count($photoHeader)) {
                $photosArray[] = $photos;
            } else if ($numOfColumns > count($photoHeader) && count($previousPhoto) !== 0) {
                $tempArray = array();
                foreach ($photos as $photo) {
                    $tempArray[] = $photo;
                    unset($photo);
                }
                $photosArray[] = $tempArray;
                $previousPhoto = $photos;
            } else if ($numOfColumns > count($photoHeader) && count($previousPhoto) === 0) {
                echo "Something is not right!" . "<br>";
                echo "The amount of columns in the current data set is: " . $numOfColumns . "<br>";
                echo "The amount of columns from the previous is 0" . "<br>";
                $photosArray[] = $photos;
            } else if (count($previousPhoto) !== 0 && strlen($photos[0]) !== 0) {
                if (count($previousPhoto) - 1 === $photoHeader["beschrijving"]) {
                    echo "It is about the description" . "<br>";
                } else if (count($previousPhoto) - 1 === $photoHeader["Personen"]) {
                    echo "It is about the persons" . "<br>";
                }
                $temp = $previousPhoto[count($previousPhoto) - 1] . " " . $photos[0];
                $previousPhoto[count($previousPhoto) - 1] = $temp;
                unset($photos[0]);
                foreach ($photos as $photo) {
                    $previousPhoto[] = $photo;
                }
                if (count($previousPhoto) === count($photoHeader)) {
                    $photosArray[] = $previousPhoto;
                    unset($previousPhoto);
                    $previousPhoto = array();
                }
            } else if (count($previousPhoto) === 0) {
                $previousPhoto = $photos;
            } else if (strlen($photos[0]) === 0) {
            } else {
                echo "Whoops!!! Something went wrong!" . "<br>";
                print_r($previousPhoto);
                echo "<br>";
                print_r($photos);
                echo "<br>";
            }
        }
        $photoRow++;
    }
}

// loop through the collection to insert the information of every collection in the template EAD.
for($i = 1; $i < count($collectionArray); $i++) {
    $sxe = new SimpleXMLElement($ead_template);
    $photoEAD = new SimpleXMLElement($c01_template);
    $barfoo = replaceValuesTheHardWay($sxe, $collectionArray[$i], $collectionArray, calculateARCHNumber($archnrCounter), $photosArray);
    $archnrCounter++;
}

// call to function to insert the photo ead's in the corresponding collection ead
combineCollectionAndPhotoEAD($collectionArray, $collectionArray[0]);

echo date("Y-m-d H:i:s") ."<br>";
echo "Done";

// End of the running code



// Beginning of the defining of the functions used and present in the code.

/**
 * Function to calculate the ARCHNR value
 * @param $archNumber
 * @return string
 */
function calculateARCHNumber($archNumber){
    $archString = "".$archNumber;
    $archNumberLengthToFill = 5 - strlen($archString);
    $archNumberStringResult = "COLL";
    for($i = 0; $i < $archNumberLengthToFill; $i++){
        $archNumberStringResult .= "0";
    }
    $archNumberStringResult .= $archString;
    return $archNumberStringResult;
}

/**
 * Function to split a given string by the character given and with the given minimum length
 * @param $minimumLength
 * @param $character
 * @param $originalString
 * @return string
 */
function splitStringByLengthAndCharacter($minimumLength, $character, $originalString){
    $explodedString = explode($character, $originalString);
    $titleString = "";
    for($i = 0; $i < count($explodedString); $i++){
        $titleString.=$explodedString[$i];
        $titleString.=".";
        if(strlen($titleString) >= $minimumLength) {
            break;
        }
    }

    return $titleString;
}

/**
 * Function to update the EAD for photos and insert the information in the correct locations
 * After it being updated, it is saved in a seperate file with the same filename as the collection EAD for further use
 * @param $photoCollection array - of collections to loop through
 * @param $photoHeader array - header of the photos csv
 * @param $collectionCode string - code of the collection given
 * @param $filename string - filename of the photo EAD to save
 */
function addPhotosToEAD($photoCollection, $photoHeader, $collectionCode, $filename, $archnumber)
{
    $combinedPhotoEADDocument = new DOMDocument('1.0','utf-8');
//    $combinedPhotoEADDocument = new DOMDocument();
    $element = $combinedPhotoEADDocument->createElement("root");
    $combinedPhotoEADDocument->appendChild($element);
    $photoCounter = 1;

    $photoArray = $photoCollection;
    unset($photoArray[0]);
    foreach($photoArray as $photo) {
        try {
            $descriptionAsTitle = splitStringByLengthAndCharacter(39, ".", $photo[$photoHeader["beschrijving"]]);
            $codeToCheck = $photo[$photoHeader["codecollectie"]] . "" . $photo[$photoHeader["fld_source"]];
            if ($codeToCheck === $collectionCode) {
                createListForLucien($archnumber, $photoCounter, $photo[$photoHeader["volgnummer"]]);
                $photoEADNew = new SimpleXMLElement("c01_hbm.xml", null, true);
                foreach ($photoEADNew->children() as $c01) {
                    if ($c01->getName() === "did") {
                        foreach ($c01->children() as $child) {
                            if ($child->getName() === "unitid"){
                                $child[0] = str_replace('{volgnummer}', $photoCounter, $child[0]);
                                $photoCounter++;
                            }else if($child->getName() === "unittitle") {
                                $child[0] = str_replace('Eerste zin {beschrijving}', $descriptionAsTitle, $child[0]);
                                $child[0] = str_replace('{plaats}', $photo[$photoHeader["plaats"]], $child[0]);
                                $splitDate = explode(",",$photo[$photoHeader["datum"]]);
                                $splitDateResult = array();
                                if(count($splitDate) > 2){
                                    for($i = 0; $i < count($splitDate) - 1; $i++){
                                        $splitDateResult[] = $splitDate[$i];
                                    }
                                }else{$splitDateResult = $splitDate;}
                                $splitDateResult = array_reverse($splitDateResult);
                                $splitDateResult = implode(" ", $splitDateResult);
                                $child[0]->addChild('unitdate', $splitDateResult);
                            }else if($child->getName() === "physdesc"){
                                foreach($child->children() as $c){
                                    if($c->getName() === "extent"){
                                        $c[0] = str_replace('{materiaalsoort}', $photo[$photoHeader["materiaalsoort"]], $c[0]);
                                        $c[0] = str_replace('{afmetingen}',$photo[$photoHeader["afmetingen"]] ? "(".$photo[$photoHeader["afmetingen"]].")" : "", $c[0]);
                                    }
                                }
                            }
                        }
                    }else if($c01->getName() === "odd") {
                        foreach($c01->children() as $child){
                            if(strlen($photo[$photoHeader["Nationaliteiten"]]) !== 0) {
                                $child[0] = str_replace('{Nationaliteiten}', $photo[$photoHeader["Nationaliteiten"]], $child[0]);
                            }else{$child[0] = str_replace('Bevolkingsgroep: {Nationaliteiten}', "", $child[0]);}
                            if(strlen($photo[$photoHeader["Trefwoorden"]]) !== 0) {
                                $child[0] = str_replace('{Trefwoorden}', $photo[$photoHeader["Trefwoorden"]], $child[0]);
                            }else{$child[0] = str_replace('Trefwoorden: {Trefwoorden}', "", $child[0]);}
                        }
                    }else if($c01->getName() === "scopecontent"){
                        foreach ($c01->children() as $child) {
                            if (strlen($descriptionAsTitle) < strlen($photo[$photoHeader["beschrijving"]])) {
                                $child[0] = str_replace('{beschrijving}', $photo[$photoHeader["beschrijving"]], $child[0]);
                            } else {
                                $child[0] = str_replace('{beschrijving}', "", $child[0]);
                            }
                        }
                    }
                }
                $updatePhotoEAD = dom_import_simplexml($photoEADNew)->ownerDocument;

                $root = $combinedPhotoEADDocument->getElementsByTagName("root")->item(0);
                $photosEADs = $updatePhotoEAD->getElementsByTagName("c01");
                for ($i = 0; $i < $photosEADs->length; $i ++) {
                    $ead = $photosEADs->item($i);

                    // import/copy item from document 2 to document 1
                    $rootead = $combinedPhotoEADDocument->importNode($ead, true);

                    // append imported item to document 1 'res' element
                    $root->appendChild($rootead);
                }
            }
        }catch(Exception $e){
            echo $e."<br>";
        }
    }

    $combinedPhotoEADDocument->formatOutput = true;
    $combinedPhotoEADDocument->save('photos/' . trim($filename, '"') . '.xml');
}

/**
 * Function to replace the values from the basic collection EAD with the correct values collected from the csv file.
 * @param $sxe
 * @param $collection
 * @param $collectionArray
 * @param $photosArray
 * @return mixed
 */
function replaceValuesTheHardWay($sxe, $collection, $collectionArray, $ARCHNumberString, $photosArray){
    // set the column numbers according to the data needed
    $codeCollection = 0;
    $collectionName = 1;
    $collectionLocation = 2;
    $collectionDate = 3;
    $collectionIsPublic = 5;
    $collectionPhoneNumber = 7;
    $collectionBiograph = 8;
    $collectionFLDSource = 9;
    $collectionSurname = 10;
    $collectionInitials = 11;
    $collectionStreet = 12;
    $collectionZipcode = 13;

    global $photoHeader;

    // loop through the EAD and replace the existing template values with the correct values
    $tempEAD = $sxe;
    foreach ($tempEAD->children() as $child) {
        if ($child->getName() === "eadheader") {
            foreach ($child->children() as $headerChild) {
                if ($headerChild->getName() === "eadid") { // change eadid information
                    $headerChild[0] = str_replace("{ARCHNR}", $ARCHNumberString, $headerChild[0]);
                } else if($headerChild->getName() === "filedesc"){
                    foreach ($headerChild->children() as $headChild){
                        if($headChild->getName() === "titlestmt"){
                           foreach($headChild->children() as $head){
                               if($head->getName() === "titleproper"){
                                   $head[0] = str_replace("{codecollectie}", $collection[$collectionInitials]." ".$collection[$collectionSurname], $head[0]);
                               }
                           }
                        }
                    }
                }
            }
        }else if($child->getName() === "archdesc"){
            foreach($child->children() as $archdescs){
                if($archdescs->getName() === "did"){
                    foreach($archdescs->children() as $dids){
                        if($dids->getName() === "unittitle"){
                            $dids[0] = str_replace("{voorletters}", $collection[$collectionInitials], $dids[0]);
                            $dids[0] = str_replace("{achternaam}", $collection[$collectionSurname], $dids[0]);
                        }else if($dids->getName() === "unitdate"){
                            $period = getPeriodFromPhotos($photosArray, $photoHeader, ($collection[$codeCollection] . "" . $collection[$collectionFLDSource]));
                            $dids[0] = str_replace("{periode}", $period, $dids[0]);
                        }else if($dids->getName() === "unitid"){
                            $dids[0] = str_replace("{ARCHNR}", $ARCHNumberString, $dids[0]);
                        }else if($dids->getName() === "origination"){
                            foreach($dids->children() as $subDid) {
                                if($subDid->getName() === "persname"){
                                    $subDid[0] = str_replace("{achternaam}", $collection[$collectionSurname], $subDid[0]);
                                    $subDid[0] = str_replace("{voorletters}", $collection[$collectionInitials], $subDid[0]);
                                }
                            }
                        }else if($dids->getName() === "physdesc"){
                            foreach($dids->children() as $subDid) {
                                if($subDid->getName() === "extent"){
                                    $numberOfPhotos = getNumberOfPhotosForCollection($photosArray, $photoHeader, ($collection[$codeCollection] . "" . $collection[$collectionFLDSource]));
                                    $subDid[0] = str_replace("{aantal}", $numberOfPhotos, $subDid[0]); // number of pictures
                                }
                            }
                        }
                    }
                }else if($archdescs->getName() === "descgrp"){
                    foreach($archdescs->children() as $descgrps){
                        if($descgrps->getName() === "bioghist"){
                            $biographParagraphed = explode("{paragraph}", $collection[$collectionBiograph]);
                            if(count($biographParagraphed) > 1) {
                                for ($i = 1; $i < count($biographParagraphed); $i++) {
                                    $descgrps->addChild('p', trim(htmlspecialchars($biographParagraphed[$i]), '"'));
                                }
                            }
                            foreach($descgrps->children() as $child){
                                $child[0] = str_replace("{biografie}", $biographParagraphed[0], $child[0]);
                            }
                        }else if($descgrps->getName() === "acqinfo"){
                            foreach($descgrps->children() as $child){
                                $child[0] = str_replace("{voorletters}", $collection[$collectionInitials], $child[0]);
                                $child[0] = str_replace("{achternaam}", $collection[$collectionSurname], $child[0]);
                                $child[0] = str_replace("{datum}", $collection[$collectionDate], $child[0]);
                                $child[0] = str_replace("{straat}", (strlen($collection[$collectionStreet]) > 0 ? $collection[$collectionStreet]."," : ""), $child[0]);
                                $child[0] = str_replace("{postcode}", (strlen($collection[$collectionZipcode]) > 0 ? $collection[$collectionZipcode]."," : ""), $child[0]);
                                $child[0] = str_replace("{plaats}", (strlen($collection[$collectionLocation]) > 0 ? $collection[$collectionLocation] : ""), $child[0]);
                                $child[0] = str_replace("{telefoon}", (strlen($collection[$collectionPhoneNumber]) > 0 ? $collection[$collectionPhoneNumber] : ""), $child[0]);
                                if(strlen($collection[$collectionPhoneNumber]) === 0){
                                    $child[0] = str_replace(", Tel:", "", $child[0]);
                                }else if(strlen($collection[$collectionStreet]) + strlen($collection[$collectionZipcode]) + strlen($collection[$collectionLocation]) === 0){
                                    $child[0] = str_replace(", ", "", $child[0]);
                                }
                            }
                            $descgrps->children()[1]->addChild('date', "".$collection[$collectionDate]); // ugly, but works!
                        }else if($descgrps->getName() === "scopecontent"){
                            foreach($descgrps->children() as $child){
                                $child[0] = str_replace("{voorletters}", $collection[$collectionInitials], $child[0]);
                                $child[0] = str_replace("{achternaam}", $collection[$collectionSurname], $child[0]);
                            }
                        }else if($descgrps->getName() === "userestrict"){
                            foreach($descgrps->children() as $child){
                                $child[0] = str_replace("{beperktopenbaar}", "", $child[0]);
                            }
                        }else if($descgrps->getName() === "prefercite"){
                            foreach($descgrps->children() as $child){
                                $child[0] = str_replace("{codecollectie}", $collection[$collectionInitials]." ".$collection[$collectionSurname], $child[0]);
                            }
                        }
                    }
                }else if($archdescs->getName() === "dsc"){ // adjust the photo EAD and save it as a seperate file for the time being.
                    addPhotosToEAD($photosArray,$photoHeader,($collection[$codeCollection] . "" . $collection[$collectionFLDSource]), $collection[$collectionName], $ARCHNumberString);
                    $archdescs[0] = str_replace("{C01}", " ", $archdescs[0]); // Removes the <head> tag apparently
                    $archdescs[0]->addChild('head', "Lijst"); // Places a new <head> tag due to the line of code above
                }
            }
        }
    }
    $tempEAD->asXml("target/".trim($collection[$collectionName], '"').".xml");
    return $tempEAD;
}

/**
 * Function to combine the collection EAD with the photo EAD via the DOMDocument
 * This is done by loading the files with the same file names from collections and photos
 * @param $collections
 * @param $collectionHeader
 */
function combineCollectionAndPhotoEAD($collections, $collectionHeader){
    // remove the header from the collections (e.g. column headers)
    unset($collections[0]);
    // loop through the collections to add the photos to the corresponding collection
    foreach($collections as $collection) {
        // collection file
        $d1 = new DOMDocument();
        $filename = trim($collection[$collectionHeader["collectienaam"]], '"');
        $d1->load('target/'.$filename . '.xml');

        // photo file (belonging to collection)
        $d2 = new DOMDocument();
        $d2->load('photos/'.$filename . '.xml');

        $root = $d1->getElementsByTagName('dsc')->item(0);

        // get every c01 element from the photo EAD file
        foreach($d2->getElementsByTagName('c01') as $elem) {
            $newNode = $d1->importNode($elem, true);
            $root->appendChild($newNode);
        }

        // Replace the inner {period} with the period inserted from the collection
        $xp = new DomXPath($d1);
        $res = $xp->query("//*[@encodinganalog = '245\$g']");
        $date = str_replace(" - ", "/", $res->item(0)->nodeValue);
        $res->item(0)->setAttribute('normal', $date);

        // Replace the inner {ARCHNR} with the ARCHNR when creating the collection xml's
        $result = $xp->query("//*[@mainagencycode = 'NL-AmISG']");
        $archnr = $result->item(0)->nodeValue;
        $archnrArray = explode("/", $archnr);
        $archnr = $archnrArray[count($archnrArray) - 1];
        $result->item(0)->setAttribute('identifier', "hdl:10622/".$archnr);

        // Remove the "{beperktopenbaar}" from the xml
        $limitedPublic = $xp->query("//*[@encodinganalog = '540\$a']");
        foreach($limitedPublic as $item){
            $item->parentNode->removeChild($item);
        }

        // Clean the xml file
        $elements = $d1->getElementsByTagName("*");
        foreach($elements as $element) {
            if(trim($element->nodeValue) === ''){
                $element->parentNode->removeChild($element);
            }
        }

        // Extra clean in case of double empty tags
        $elements = $d1->getElementsByTagName("*");
        foreach($elements as $element) {
            if(trim($element->nodeValue) === ''){
                $element->parentNode->removeChild($element);
            }
        }

        // Save the combined file
        print($filename . " -> " . $archnr . "<br>");
        $d1->save('Result/' . $archnr . '.xml');
    }
}

/**
 * Function to get the number of photos belonging to the given collection
 * @param $photos
 * @param $photoHeader
 * @param $collectionCode
 * @return int
 */
function getNumberOfPhotosForCollection($photos, $photoHeader, $collectionCode){
    // set the local variable to the given variable
    $localPhotos = $photos;
    // remove the first photo, which is the photo header
    unset($localPhotos[0]);
    $counter = 0;
    // loop through the array to count the photos belonging to the collection
    foreach($localPhotos as $photo){
        $codeToCheck = $photo[$photoHeader["codecollectie"]] . "" . $photo[$photoHeader["fld_source"]];
        if($codeToCheck === $collectionCode){
            $counter++;
        }
    }

    return $counter;
}

/**
 * Function to get the period by checking the dates of the photos
 * @param $photos
 * @param $photoHeader
 * @param $collectionCode
 * @return string
 */
function getPeriodFromPhotos($photos, $photoHeader, $collectionCode){
    $localPhotos = $photos;
    unset($localPhotos[0]);

    $photoDates = array();

    foreach($localPhotos as $photo) {
        $codeToCheck = $photo[$photoHeader["codecollectie"]] . "" . $photo[$photoHeader["fld_source"]];
        if ($codeToCheck === $collectionCode) {
            $dateToAdd = str_replace(array("(ca.)", "ca.","-", ","), " ", $photo[$photoHeader["datum"]]);
            if($dateToAdd !== "" && $dateToAdd !== "onbekend" && $dateToAdd !== "Onbekend") {
                $dateToAdd = $dateToAdd . " ";
                if (strlen($dateToAdd) > 0) {
                    $dateArray = array();
                    $temp = "";
                    for ($i = 0; $i < strlen($dateToAdd); $i++) {
                        if ($dateToAdd[$i] === " " && $temp !== "") {
                            $dateArray[] = $temp;
                            $temp = "";
                        } else {
                            $temp = $temp . $dateToAdd[$i];
                        }
                    }
                    foreach ($dateArray as $date) {
                        if (strlen($date) === 4 && is_numeric($date)) {
                            $dateToAdd = $date;
                        }
                    }
                    $photoDates[] = $dateToAdd;
                }
            }
        }
    }

    sort($photoDates);
    $period = "1700 - 2199";
    if(count($photoDates) > 0) {
        $period = "" . $photoDates[0] . " - " . $photoDates[count($photoDates) - 1];
    }
    return $period;
}

/**
 * Function to save the file
 * @param $filename
 * @param $arrayToSave
 */
function save_file($filename, $arrayToSave){
    $doc = new DOMDocument('1.0');
    $doc->formatOutput = true;
    $root = $doc->createElement('root');
    $root = $doc->appendChild($root);
    foreach($arrayToSave as $key=>$value)
    {
        $em = $doc->createElement($key);
        $text = $doc->createTextNode($value);
        $em->appendChild($text);
        $root->appendChild($em);

    }
    $doc->save("target/".$filename.".xml");
}

/**
 * Function to load the file given by a location to the file
 * @param $file
 * @return bool|string
 */
function load_file( $file ) {
    $ret = file_get_contents ( $file );

    return $ret;
}

/** Function to adjust the character encoding to utf-8
 * @param $fileName
 * @return bool|resource
 */
function utf8_fopen_read($fileName) {
    $fc = file_get_contents($fileName);
    $handle=fopen("php://memory", "rw"); // Allows temporary data to be stored in a file-like wrapper.
    fwrite($handle, $fc); // Writes the contents of string to the file stream pointed to by handle.
    fseek($handle, 0); // Sets the file position indicator for the file referenced by handle.
    return $handle;
}
