<?php
/**
 * Created by IntelliJ IDEA.
 * User: Igor van der Bom
 * Date: 20-11-2017
 * Time: 14:27
 */

ini_set('max_execution_time', 500);
ini_set('auto_detect_line_endings', TRUE);
libxml_use_internal_errors(true);

echo date("Y-m-d H:i:s") ."<br>";
echo "Start staatsarchief export"."<br>";
print("<br>");

/**
 * The General idea is to loop through the files and find the collections that exist in the files.
 * For each collection a separate EAD file needs to be loaded and filled in.
 * This means adjusting the info for the EAD file and add the C01 files to the EAD file.
*/

/** Setting up parameters for the script! **/
$target_dir = "result/";
$state_archive_data_dir = "data/staatsarchief/";
$state_archive_collections_file = "data/staatsarchief/collecties.php";
$collections_dom_document = new DOMDocument();
$collections_dom_document->loadHTMLFile($state_archive_collections_file);
$state_archive_ead_file = "templates/ead_staatsarch-new-v2.xml";
$state_archive_ead_file = realpath($state_archive_ead_file);
$state_archive_c01_file = "templates/c01_staatsarchief-v2.xml";
$state_archive_c01_file = realpath($state_archive_c01_file);
$c01_item_list = array();
$ead_file_list = array();
$item_counter = 0;
$files_handled = array();
$ead_item_counter = 0;
$collection_description_files = array();
$savrz_numbers = array();
$handled_files_directories = array();
$archive_number_number = 4600;
$archive_number_premise = "ARCH0";

/** Start of the code */
$xpath_collecties_finder = new DOMXPath($collections_dom_document);
$collections_div_class_name = "searchopt";
$collections_class_name = "menu";

/**
 * Nogtedoen/ archive code
 */
getUnderlyingFilesToHandle($state_archive_data_dir, $collections_dom_document);

$menu_items = $xpath_collecties_finder->query("//span[@class='$collections_div_class_name']
                                                            /a[@class='$collections_class_name']");
/** Fills the list of collection_description_files */
getArchiveNumbersFromDescription();

foreach($menu_items as $menu_item){
    $menu_file = $menu_item->getAttribute('href');
    searchMenuForLinksToCollections($menu_file, $state_archive_data_dir, $collections_dom_document);
}

print(__LINE__." - Number of items handled: " . $item_counter . "<br>");
print(__LINE__." - Items in item list is ".sizeof($c01_item_list)."<br>");

foreach($c01_item_list as $c01_item){
    if(strpos($c01_item->getElementsByTagName('unitid')->item(0)->nodeValue, 'BG')){
        print(__LINE__." - Item nodeValue contains BG: ". $c01_item->getElementsByTagName('unitid')->item(0)->nodeValue . "<br>");
    }
}
print("<br>");
print(__LINE__." - Number of ead titles in ead file list is: ". sizeof($ead_file_list)."<br>");
print(__LINE__." - Number of files handled: ". sizeof($files_handled)."<br>");
$wrong_file_counter = 0;
$good_file_counter = 0;
foreach($files_handled as $file_handled){
    if(strpos($file_handled,'idx.php')) {
        $wrong_file_counter++;
    }else{
        $good_file_counter++;
    }
}
foreach($ead_file_list as $ead_file_key => $ead_file_value){
//    print(__LINE__."<br>");
//    print_r($ead_file_value);
//    print("<BR>");
    $savrz_values = explode(" ", $ead_file_key);
    foreach($savrz_values as $savrz_value){
        if(strpos($savrz_value,'SAVRZ') === 0){
            if(!in_array(substr($savrz_value, 0,8), $savrz_numbers)) {
                array_push($savrz_numbers, substr($savrz_value, 0,8));
            }
        }
    }
}

//sort($savrz_numbers);
//foreach($savrz_numbers as $savrz_number){
//    print(__LINE__ . " -> ");
//    print_r($savrz_number);
//    print('<br>');
//}

print(__LINE__);
print_r($ead_file_list);
print("<br>");

print(__LINE__." - Number of correct files handled is: $good_file_counter<br>");
print(__LINE__." - Number of wrong files handled is: $wrong_file_counter<br>");

print("<br>");
addC01FilesWithSameSAVRZNumberToEad($c01_item_list, $ead_file_list);

print("<br>");
echo "Done!<br>";

/** End of the code */

/** Start of the function declarations */
function getUnderlyingFilesToHandle($directory, $new_dom_document){
    $files_in_directory = getFilesFromDirectory($directory);
    foreach($files_in_directory as $file_in_directory){
        if(is_file($directory.'/'.$file_in_directory)) {
            print(__LINE__ . ' -> ' . $file_in_directory . '<br>');
            searchMenuForLinksToCollections($file_in_directory, $directory.'/', $new_dom_document);
        }
    }
}

function getFilesFromDirectory($directory){
    $allFiles = scandir($directory);
    return array_diff($allFiles, array('.', '..'));
}

function searchMenuForLinksToCollections($menu, $directory, $new_dom_document){
    global $files_handled;
    if (preg_match("/(.php){1}/", $menu)) {
        if (!preg_match("/(index.php)/", $menu)) {
            if (!in_array($directory . $menu, $files_handled)) {
                print(__LINE__ . ' -> ' . $directory . $menu . '<br>');
                array_push($files_handled, $directory . $menu);
                collectItemsBelongingToCollection($menu, $directory, $new_dom_document);
            }
        } else {
            $directory = preg_replace('/(\/)+/', '/', $directory);
            if (!in_array($directory . $menu, $files_handled)) {
                array_push($files_handled, $directory . $menu);
                $new_dom_document = new DOMDocument();
                $new_dom_document->loadHTMLFile($directory . $menu);
                $xpath_index_files_finder = new DOMXPath($new_dom_document); // looks in the first given dom document, not in the current file!!!
                $index_files = $xpath_index_files_finder->query("//a");
                foreach ($index_files as $index_file) {
                    if ($index_file->nodeName === 'a') {
                        if (strpos($index_file->getAttribute('href'), 'index') === false && strpos($index_file->getAttribute('href'), 'search.socialhistory') === false) {
                            searchMenuForLinksToCollections($index_file->getAttribute('href'), $directory, $new_dom_document);
                        }
                    }
                }
            }
        }
    } else if (preg_match("/[a-z]*[\/]/", $menu)) {
        $collection_files = getFilesFromDirectory($directory . rtrim($menu, '/'));
        foreach ($collection_files as $collection_file) {
            searchMenuForLinksToCollections($collection_file, $directory . $menu, $new_dom_document);
        }
    } else if (preg_match("/([#])+([a-z]*)?/", $menu)) {
        $new_dom_document = new DOMDocument();
        $new_dom_document->loadHTMLFile($directory.$menu);
        $xpath_relative_link_finder = new DOMXPath($new_dom_document);
        $relative_link_to_find = str_replace('#', '', $menu);
        $relative_links = $xpath_relative_link_finder->query("//
                                                        a[@name='$relative_link_to_find']");
        /** loop while the next element is not a h3, div, table or whatever. */
        foreach ($relative_links as $relative_link) {
            $relative_link_parent = $relative_link->parentNode;
            $relative_link_next_sibling = $relative_link_parent->nextSibling;
            while ($relative_link_next_sibling->nodeName !== "h3") {
                if($relative_link_next_sibling !== null) {
                    if ($relative_link_next_sibling->nodeName === 'a') {
                        /** repeat the function to check if the href is a link or a file */
                        searchMenuForLinksToCollections($relative_link_next_sibling->getAttribute('href'), $directory, $new_dom_document);
                    }
                    $relative_link_next_sibling = $relative_link_next_sibling->nextSibling;
                }
            }
        }
    } else if (!preg_match("/(.php)/", $menu) && !preg_match("/(.jpg)/", $menu)) {
        if (file_exists($directory . $menu)) {
            getUnderlyingFilesToHandle($directory . $menu, $new_dom_document);
        }
    } else {
//        print(__LINE__ . ' - ' . $directory . $menu . '<br>');
    }
}

/**
 * @param $collection_file
 * @param $directory
 */
function collectItemsBelongingToCollection($collection_file, $directory, $new_dom_document){
    /** Setup the data to look for in the file */
    $title_class = "title";
    $item_class = "item";
    $content_class = "content";
    $topmenu_class = "topmenu";
    $group_class = "group";
    $ead_unit_id_code = "";
    print(__LINE__ . ' -> ' . $collection_file . '<br>');

    /** Setup to get the content element in the file */
    $collection_file_dom_document = new DOMDocument();
    $file_loaded = $collection_file_dom_document->loadHTMLFile($directory.$collection_file);
    $xpath_collection_content_finder = new DOMXPath($collection_file_dom_document);
    $collection_content = $xpath_collection_content_finder->query("//td[@class='$content_class']");

    print($file_loaded . '<br>');

    /** Getting the group items */
    $collection_group_items = $xpath_collection_content_finder->query("//div
                                                            [@class='$group_class']", $collection_content[0]);
    print(__LINE__ . ' -> ');
    print_r($collection_group_items);
    print('<br>');
    $xpath_collection_group_finder = new DOMXPath($collection_file_dom_document);
    if($collection_group_items->length == 0){
        $collection_group_elements = $xpath_collection_group_finder->query("//ul/li/a");
        foreach($collection_group_elements as $collection_group_element){
            searchMenuForLinksToCollections(strtok($collection_group_element->getAttribute('href'), "#"),$directory, $new_dom_document);
        }
    }else {
        $collection_group_elements = $xpath_collection_group_finder->query("//div[starts-with(@class, '$group_class')]", $collection_group_items[0]);
    }

    global $state_archive_ead_file, $archive_number_number, $archive_number_premise;
    $ead_file = loadFile($state_archive_ead_file);
    $ead_file->preserveWhiteSpace = false;
    $ead_file->formatOutput = true;
    print(__LINE__ . ' -> ');
    print_r($collection_group_elements);
    print('<br>');
    if($collection_group_elements->length > 0) {
        $ead_file_name = "";
        foreach ($collection_group_elements as $collection_group_element) {
            $class_name = $collection_group_element->getAttribute('class');
            $node_value = trim($collection_group_element->nodeValue);
            $items_to_find = ['\\', ':', '*', '?', '"', '<', '>', '|'];
            $node_value = str_replace($items_to_find, '-', $node_value);
            $node_value = str_replace("&", 'en', $node_value);
            switch ($class_name) {
                case "grouptitle":
                    $node_value = str_replace('/', ',', $node_value);
                    replaceValueInEadWithCorrectValue($ead_file, 'titleproper', 'grouptitle', $node_value, true);
                    replaceValueInEadWithCorrectValue($ead_file, 'unittitle', 'grouptitle', $node_value, true);
                    replacePreferciteGroupTitleInEad($ead_file, 'prefercite', 'grouptitle', $node_value);
                    $ead_file_name = $node_value;
                    break;
                case "groupcallno":
                    $unit_title_array = explode(' ', $node_value);
                    $unit_title_valid_array = array();
                    for($i = 0; $i < count($unit_title_array); $i++){
                        if(strpos(strtoupper($unit_title_array[$i]), "SAVRZ") === 0){
                            array_push($unit_title_valid_array, $unit_title_array[$i]);
                        }
                    }
//                    print_r($unit_title_valid_array);
//                    print('<br>');
                    $archive_number = $archive_number_premise . ($archive_number_number + intval(substr($node_value, 5, 3)));
                    replaceValueInEadWithCorrectValue($ead_file, 'eadid', 'ARCHNR', substr($node_value,0,8), true);
                    replaceValueInEadWithCorrectValue($ead_file, 'unitid', '', $archive_number, false);
                    replaceValueInEadWithCorrectValue($ead_file, 'identifier', 'ARCHNR', $archive_number, true);
//                    $ead_file_name = $archive_number;
                    $ead_unit_id_code = $node_value;
                    print(__LINE__ . ' -> ' . $node_value . '<br>');
                    break;
                case "groupsize":
                    replaceValueInEadWithCorrectValue($ead_file, 'extent', '', $node_value, false);
                    break;
                case "groupperiod":
//                    replaceValueInEadWithCorrectValue($ead_file, 'unitdate', '', $node_value .".", false);
//                    $exploded_date = explode('-',$node_value);
//                    foreach($exploded_date as $item){
//                        trim($item);
//                    }
//                    $new_date = implode('/',$exploded_date);
//                    $node_value = str_replace(' - ','//',$node_value);
//                    replaceValueInEadWithCorrectValue($ead_file, 'normal', 'normal', $new_date, false);
                    break;
                default:
                    break;
            }
            print(__LINE__ . ' -> ' . $ead_file_name . '<br>');
        }

        /** Getting the menu items */
        $collection_top_menu_items = $xpath_collection_content_finder->query("//div
                                                            [@class='$topmenu_class']/a", $collection_content[0]);

        global $state_archive_c01_file, $item_counter, $c01_item_list;
        /** Looping through the collected information */
        $collection_id_array = array();
        if ($collection_top_menu_items->length > 0) {
            foreach ($collection_top_menu_items as $collection_file_link) {
                $collection_file_link_href = $collection_file_link->getAttribute('href');
                if (strpos($collection_file_link_href, '#') !== false) {
                    $xpath_collection_item_finder = new DOMXPath($collection_file_dom_document);
                    $collection_file_link_href_name = str_replace("#", "", $collection_file_link_href);
                    $collection_items = $xpath_collection_item_finder->query("//div[@class='$item_class']
                                        /div[@class='$title_class']/a[@name='$collection_file_link_href_name']");

                    /** Looping through the collected items in the file and collecting the data */
                    foreach ($collection_items as $collection_item) {
                        $item_counter++;
                        $c01_file = loadFile($state_archive_c01_file);
                        $c01_file->preserveWhiteSpace = false;
                        $c01_file->formatOutput = true;
                        $collection_item_main_div = $collection_item->parentNode->parentNode;
                        $collection_item_sub_divs = $collection_item_main_div->getElementsByTagName('div');
                        $c01_item_list = determineWhichDataToReplace($collection_item_sub_divs, $c01_file, $collection_id_array, $c01_item_list);
                    }
                }
            }
        } else if ($collection_top_menu_items->length == 0) {
            $xpath_collection_item_finder = new DOMXPath($collection_file_dom_document);
            $collection_items = $xpath_collection_item_finder->query("//div[@class='$item_class']");
            foreach ($collection_items as $collection_item) {
                $item_counter++;
                $c01_file = loadFile($state_archive_c01_file);
                $c01_file->preserveWhiteSpace = false;
                $c01_file->formatOutput = true;
                $collection_item_main_div = $collection_item;
                $collection_item_sub_divs = $collection_item_main_div->getElementsByTagName('div');
                $c01_item_list = determineWhichDataToReplace($collection_item_sub_divs, $c01_file, $collection_id_array, $c01_item_list);
            }
        }

//        clearUnfilledValuesFromEadFile($ead_file);

        global $ead_item_counter, $ead_file_list;
        if($ead_file_name !== "") {
            print(__LINE__ . " - " . $ead_file_name . '<br>');
            print_r($ead_file);
            print("<br><br>");
            $ead_file_list[$ead_unit_id_code] = $ead_file;
        }else{
//            print(__LINE__." - Item not saved!<BR><BR>");
        }
        $ead_item_counter = 0;
        /** End of foreach for items */
    }else{
        print(__LINE__ . ' -> Zero!! <br><br>');
    }
}

function determineWhichDataToReplace($collection_item_sub_divs, $c01_file, $collection_id_array, $c01_item_list){
    $title_class = "title";
    $callno_class = "callno";
    $format_class = "format";
    $period_class = "period";
    $person_class = "person";
    $notes_class = "notes";
    $subject_class = "subject";
    $geographical_class = "geographical";
    $organization_class = "organization";
    $SAVRZ_code = "";
    $c01_file->preserveWhiteSpace = false;
    $c01_file->formatOutput = true;

    foreach ($collection_item_sub_divs as $collection_item_sub_div) {
        switch ($collection_item_sub_div->getAttribute("class")) {
            case $format_class:
                replaceValueInC01WithCorrectValue($c01_file, 'genreform', $collection_item_sub_div->nodeValue, false);
                break;
            case $period_class:
//                print(__LINE__ . " - Handling unit date!<br>");
                replaceValueInC01WithCorrectValue($c01_file, 'unitdate', $collection_item_sub_div->nodeValue, false);
                break;
            case $geographical_class:
                replaceValueInC01WithCorrectValue($c01_file, $geographical_class, $collection_item_sub_div->nodeValue, true);
                break;
            case $organization_class:
                replaceValueInC01WithCorrectValue($c01_file, $organization_class, $collection_item_sub_div->nodeValue, true);
                break;
            case $notes_class:
                replaceValueInC01WithCorrectValue($c01_file, $notes_class, $collection_item_sub_div->nodeValue, true);
                break;
            case $title_class:
                replaceValueInC01WithCorrectValue($c01_file, 'unittitle', $collection_item_sub_div->nodeValue, false);
                break;
            case $callno_class:
                if ($collection_item_sub_div->nodeValue !== "") {
                    if (strpos($collection_item_sub_div->nodeValue, "SAVR",0) !== false) {
                        $collection_id = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "SAVR"), 8);
                        $SAVRZ_code = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "SAVR"));
                        if (!in_array($collection_id, $collection_id_array)) {
                            array_push($collection_id_array, $collection_id);
                        }
                        $unit_id = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "Doos"));
                        $unit_id = rtrim($unit_id);
                        $unit_id = rtrim($unit_id, ")");
                        replaceValueInC01WithCorrectValue($c01_file, 'unitid', $unit_id, false);
                        $box_information = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "Doos"), 8);
                        replaceValueInC01WithCorrectValue($c01_file, 'container', $box_information, false);
                    } else if (strpos($collection_item_sub_div->nodeValue, "BG",0) !== false) {
                        $collection_id = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "BG"), 10);
                        $SAVRZ_code = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "BG"));
                        if (!in_array($collection_id, $collection_id_array)) {
                            array_push($collection_id_array, $collection_id);
                        }
                        $unit_id = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "BG H12") + 7);
                        $unit_id = rtrim($unit_id);
                        $unit_id = rtrim($unit_id, ")");
                        replaceValueInC01WithCorrectValue($c01_file, 'unitid', $unit_id, false);
                        $box_information = substr($collection_item_sub_div->nodeValue, strpos($collection_item_sub_div->nodeValue, "Map"), 8);
                        replaceValueInC01WithCorrectValue($c01_file, 'container', $box_information, false);
                    }
                } else {
                    $SAVRZ_code = "Item " . sizeof($c01_item_list);
                    replaceValueInC01WithCorrectValue($c01_file, 'unitid', "Doos 999 Map 999", false);
                    replaceValueInC01WithCorrectValue($c01_file, 'container', "Doos 999", false);
                }
                break;
            case $person_class:
                replaceValueInC01WithCorrectValue($c01_file, $person_class, $collection_item_sub_div->nodeValue, true);
                break;
            case $subject_class:
                replaceValueInC01WithCorrectValue($c01_file, $subject_class, $collection_item_sub_div->nodeValue, true);
                break;
            default:
                break;
        }
    }
    clearUnfilledValuesFromc01File($c01_file);
    $c01_item_list[$SAVRZ_code] = $c01_file;
    return $c01_item_list;
}

function addC01FilesWithSameSAVRZNumberToEad($c01_item_list, $ead_file_list){
//    $ead_savrz_number = "no number present";
    foreach($ead_file_list as $ead_file_key => $ead_file_value){
        $ead_savrz_number = $ead_file_key;
        addDescriptionToTheEAD($ead_file_value);
        if(strpos($ead_file_key, "SAVR", 0) === 0) {
            $savrz_code = substr($ead_file_key, strpos($ead_file_key, "SAVR"), 8);
            foreach ($c01_item_list as $c01_key => $c01_value) {
                if (substr($c01_key, strpos($c01_key, "SAVR"), 8) === $savrz_code) {
                    addC01FilesFromCollectionToTheEAD($ead_file_value, $c01_value);
                }
            }
        }else if(strpos($ead_file_key, "BG H12", 0) === 0){
            $savrz_code = substr($ead_file_key, strpos($ead_file_key, "BG H12"), 6);
            foreach ($c01_item_list as $c01_key => $c01_value) {
                if (substr($c01_key, strpos($c01_key, "BG H12"), 6) === $savrz_code) {
                    addC01FilesFromCollectionToTheEAD($ead_file_value, $c01_value);
                }
            }
        }
        $ead_file_value->preserveWhiteSpace = false;
        $ead_file_value->formatOutput = true;
        $ead_file_unit_id = $ead_file_value->getElementsByTagName("eadid")->item(0)->nodeValue;
        $ead_file_unit_id = substr($ead_file_unit_id, strpos($ead_file_unit_id, '10622/') + 6);
        $ead_file_unit_id = trim($ead_file_unit_id);
        print(__LINE__." - <b>Saving ead file</b> as $ead_file_unit_id.xml with savrz number: $ead_savrz_number<br>");
        $ead_file_value->save('ead-files/' . $ead_file_unit_id . '.xml');
    }
}

function addC01FilesFromCollectionToTheEAD($ead_file, $c01_file){
    global $ead_item_counter;
    $lowest_box_number = PHP_INT_MAX;
    $lowest_map_number = PHP_INT_MAX;
    $lowest_first_map_number = PHP_INT_MAX;
    $lowest_second_map_number = PHP_INT_MAX;
    $ead_dsc_list = $ead_file->getElementsByTagName('dsc')->item(0);
    $c01_file->preserveWhiteSpace = false;
    $c01_file->formatOutput = true;
    if($ead_dsc_list != null) {
        $ead_item_counter++;
        if (strpos($ead_dsc_list->nodeValue, '{c01}') !== false) {
            $list_head = $ead_file->createElement('head', str_replace('{c01}', '', $ead_dsc_list->nodeValue));
            $ead_dsc_list->nodeValue = "";
            $ead_dsc_list->appendChild($list_head);
        }
        /** Starting the sorting */
        $c01_new_container = $c01_file->getElementsByTagName('unitid')->item(0);
        $c01_existing_containers = $ead_dsc_list->getElementsByTagName('unitid');
        $element_to_place_before = "";
        if(sizeof($c01_existing_containers) !== 0){
            foreach ($c01_existing_containers as $c01_existing_container){
//                print(__LINE__ . " Node value is: " . $c01_existing_container->nodeValue . "<br>");
                if(strpos($c01_existing_container->nodeValue, "Doos", 0) === false){
                    $existing_box_number = trim(substr($c01_existing_container->nodeValue, 0, 4));
                    $existing_map_number = (substr($c01_existing_container->nodeValue, strpos($c01_existing_container->nodeValue, 'Map ') + 4, 4));
                    $new_box_number = trim(substr($c01_new_container->nodeValue, 0, 4));
                    $new_map_number = (substr($c01_new_container->nodeValue, strpos($c01_new_container->nodeValue, 'Map ') + 4, 4));
                }else{
                    $existing_box_number = trim(substr($c01_existing_container->nodeValue, strpos($c01_existing_container->nodeValue, 'Doos ') + 5, 4));
                    $existing_map_number = (substr($c01_existing_container->nodeValue, strpos($c01_existing_container->nodeValue, 'Map ') + 4, 4));
                    $new_box_number = trim(substr($c01_new_container->nodeValue, strpos($c01_new_container->nodeValue, 'Doos ') + 5, 4));
                    $new_map_number = (substr($c01_new_container->nodeValue, strpos($c01_new_container->nodeValue, 'Map ') + 4, 4));
                }
                if(strpos($existing_map_number, '.') || strpos($new_map_number, '.')){
                    $existing_map_number_array = explode('.', $existing_map_number );
                    $existing_map_first_number = intval($existing_map_number_array[0]);
                    if(sizeof($existing_map_number_array) > 1) {
                        $existing_map_second_number = intval($existing_map_number_array[1]);
                    }else{
                        $existing_map_second_number = intval(0);
                        array_push($existing_map_number_array, 0);
                    }
                    $new_map_number_array = explode('.',$new_map_number);
                    $new_map_first_number = intval($new_map_number_array[0]);
                    if(sizeof($new_map_number_array) > 1) {
                        $new_map_second_number = intval($new_map_number_array[1]);
                    }else{
                        $new_map_second_number = intval(0);
                        array_push($new_map_number_array, 0);
                    }
                    if ($new_box_number === $existing_box_number && $existing_box_number <= $lowest_box_number) {
                        if(sizeof($new_map_number_array) > 1 && sizeof($existing_map_number_array) > 1 ) {
                            if ($new_map_first_number === $existing_map_first_number && $existing_map_first_number <= $lowest_first_map_number) {
                                if ($new_map_second_number < $existing_map_second_number && $existing_map_second_number < $lowest_second_map_number) {
                                    $lowest_box_number = $existing_box_number;
                                    $lowest_first_map_number = $existing_map_first_number;
                                    $lowest_second_map_number = $existing_map_second_number;
                                    $element_to_place_before = $c01_existing_container;
                                }
                            }else if($new_map_first_number < $existing_map_first_number && $existing_map_first_number < $lowest_first_map_number){
                                $lowest_box_number = $existing_box_number;
                                $lowest_first_map_number = $existing_map_first_number;
                                $lowest_second_map_number = $existing_map_second_number;
                                $element_to_place_before = $c01_existing_container;
                            }
                        }else{
                            if ($new_map_first_number < $existing_map_first_number && $existing_map_first_number < $lowest_first_map_number) {
                                $lowest_box_number = $existing_box_number;
                                $lowest_first_map_number = $existing_map_first_number;
                                $element_to_place_before = $c01_existing_container;
                            }
                        }
                    }else if(strcmp($new_box_number, $existing_box_number) < 0 && strcmp($existing_box_number, $lowest_box_number) < 0){
                        $lowest_box_number = $existing_box_number;
                        $element_to_place_before = $c01_existing_container;
                    }
                }else {
                    if ($new_box_number === $existing_box_number) {
                        if ($new_map_number < $existing_map_number && $existing_map_number < $lowest_map_number) {
                            $lowest_box_number = $existing_box_number;
                            $lowest_map_number = $existing_map_number;
                            $element_to_place_before = $c01_existing_container;
                        }
                    }else if(strcmp($new_box_number, $existing_box_number) < 0 && strcmp($existing_box_number, $lowest_box_number) < 0){
                            $lowest_box_number = $existing_box_number;
                            $element_to_place_before = $c01_existing_container;
                    }
                }
            }
        }
        /** Adding the c01 file in the right spot */
        foreach($c01_file->getElementsByTagName('c01') as $elem) {
            $newNode = $ead_file->importNode($elem, true);
            if($element_to_place_before !== ""){
//                $new = $newNode->getElementsByTagName('unitid')->item(0)->nodeValue;
//                print(__LINE__ . " - Placed $new before $element_to_place_before->nodeValue<br>");
                $ead_dsc_list->insertBefore($newNode, $element_to_place_before->parentNode->parentNode);
            }else{
//                $new = $newNode->getElementsByTagName('unitid')->item(0)->nodeValue;
//                print(__LINE__ . " - Placed $new<br>");
                $ead_dsc_list->appendChild($newNode);
            }
        }
    }
    return $ead_file;
}

function addDescriptionToTheEAD($ead_file){
    global $collection_description_files;
    $xpath_ead_archive_number_finder = new DOMXPath($ead_file);
    $ead_unit_id = "eadid";
    $ead_title_proper = "titleproper";
    $ead_archive_number = $xpath_ead_archive_number_finder->query("//*[local-name()='$ead_unit_id']");
    $ead_title_proper_element = $xpath_ead_archive_number_finder->query("//*[local-name()='$ead_title_proper']");

    foreach($collection_description_files as $collection_description_file){
        $xpath_archive_number_finder = new DOMXPath($collection_description_file);
        $menu_items = $xpath_archive_number_finder->query("//office:text/text:p");
        $menu_items_secondary = $xpath_archive_number_finder->query("//office:text");
        $menu_items_secondary = $menu_items_secondary->item(0)->childNodes;
//        print(__LINE__ . " - " . $menu_items->item(0)->textContent . " - " . $menu_items->item(1)->textContent . " - " . $menu_items->item(2)->textContent . '<br>');
        try {
            $collection_title = trim($menu_items->item(2)->textContent);
        }catch(Exception $e){
            print(__LINE__ . " - " . $e->getMessage() . "<br>");
        }

//        print(__LINE__ . " -> " . $ead_archive_number->item(0)->textContent . "<br>");
        if(trim($menu_items->item(0)->textContent) === substr(trim($ead_archive_number->item(0)->textContent),28,8)){
            // variables to use for editing the ead according to the documents given
            $ead_title_proper_element->item(0)->textContent = preg_replace('/\b(?!Archief)\b\S.+/', $collection_title, $ead_title_proper_element->item(0)->textContent);
            $ead_archive_number->item(0)->textContent = str_replace($menu_items->item(0)->textContent, substr($ead_archive_number->item(0)->getAttribute('identifier'),10,9), $ead_archive_number->item(0)->textContent);

            $encoding_analog = "520\$a";
            $history_encoding_analog = "545\$a";
            $related_encoding_analog = "544\$a";
            $format_encoding_analog = "300\$a";
            $unitdate_encoding_analog = "245\$g";

            $format_elements = $ead_file->getElementsByTagName('extent');
            foreach($format_elements as $format_element){
                if($format_element->getAttribute("encodinganalog") === $format_encoding_analog){
                    foreach($menu_items as $menu_item){
                        if(strpos($menu_item->textContent, 'cm') !== false || strpos($menu_item->textContent, 'centimeter') !== false) {
                            $format_element->textContent = $menu_item->textContent;
                        }
                    }
                }
            }

            $unitdate_elements = $ead_file->getElementsByTagName('unitdate');
            $date_added = false;
            foreach($unitdate_elements as $unitdate_element){
                if($unitdate_element->getAttribute("encodinganalog") === $unitdate_encoding_analog){
                    for($i = 0; $i < 8; $i++){
                        if(preg_match("/^([0-9]{4}.*[0-9]{4})|^[0-9]{4}/", $menu_items[$i]->textContent) === 1 && !$date_added){
                            $unitdate_element->textContent = $menu_items[$i]->textContent . '.';
                            if(strpos($menu_items[$i]->textContent, '–')){
                                $unit_date_exploded = explode('–', $menu_items[$i]->textContent);
                            }else {
                                $unit_date_exploded = explode('-', $menu_items[$i]->textContent);
                            }
                            $unit_date_normal = implode('/', $unit_date_exploded);
                            $unit_date_normal = preg_replace('/\s+/', '', $unit_date_normal);
                            $unitdate_attribute = $unitdate_element->getAttribute("normal");
                            $unitdate_attribute = str_replace('{normal}', $unit_date_normal, $unitdate_attribute);
                            $unitdate_element->setAttribute("normal", $unitdate_attribute);
                            $date_added = true;
                        }
                    }
                }
            }

            /** Filling the geschiedenis element */
            $history_elements = $ead_file->getElementsByTagName('bioghist');
            foreach($history_elements as $history_element){
                if($history_element->getAttribute("encodinganalog") === $history_encoding_analog){
                    foreach($history_element->childNodes as $childNode){
                        if($childNode->textContent === "{Geschiedenis}"){
                            $history_node = $ead_file->createElement('p', $menu_items->item(8)->textContent);
                            $childNode->parentNode->replaceChild($history_node, $childNode);
                        }
                    }
                }
            }

            $related_elements = $ead_file->getElementsByTagName('relatedmaterial');
            foreach($related_elements as $related_element){
                if($related_element->getAttribute("encodinganalog") === $related_encoding_analog){
                    foreach($related_element->childNodes as $childNode){
                        if($childNode->textContent === "{verwante archieven}"){
                            for($i = 9; $i < $menu_items_secondary->length; $i++){
                                if(strpos($menu_items_secondary->item($i)->textContent, "Zie tevens") === 0){
                                    $related_node = $ead_file->createElement('p', $menu_items_secondary->item(0)->textContent);
                                    $childNode->parentNode->replaceChild($related_node, $childNode);
                                }
                            }
                            try {
                                if($childNode->parentNode !== null) {
                                    if($childNode->parentNode->parentNode !== null) {
                                        $related_parent = $childNode->parentNode->parentNode;
                                        $childNode->parentNode->removeChild($childNode);
                                        $related_parent->parentNode->removeChild($related_parent);
                                    }
                                }
                            }catch(Exception $e){
                                print(__LINE__ . $e . "<br>");
                            }
                        }
                    }
                }
            }

            /** Filling the inhoud element */
            $elements = $ead_file->getElementsByTagName('scopecontent');
            foreach($elements as $element){
                if($element->getAttribute("encodinganalog") === $encoding_analog){
                    foreach($element->childNodes as $childNode){
                        if($childNode->textContent === "{inhoud}"){
                            $content_parent_node = $childNode->parentNode;
                            $child_replaced = false;
                            if($menu_items_secondary->length > 8){
                                for($i = 9; $i < $menu_items_secondary->length; $i++){
                                    if(strpos($menu_items_secondary->item($i)->textContent, 'In de collectie') === 0){
                                        if(!$child_replaced) {
                                            $content_node = $ead_file->createElement('p', $menu_items_secondary->item($i)->textContent);
                                            $childNode->parentNode->replaceChild($content_node, $childNode);
                                            $child_replaced = true;
                                        }else{
                                            $content_node = $ead_file->createElement('p', $menu_items_secondary->item($i)->textContent);
                                            $content_parent_node->appendChild($content_node);
                                        }
                                    }
                                    else if(strpos($menu_items_secondary->item($i)->textContent, 'Over de volgende onderwerpen') === 0 || $menu_items_secondary->item($i)->textContent !== ""){
//                                        $content_node = $ead_file->createElement('p', $menu_items_secondary->item($i)->textContent);
//                                        $content_parent_node->appendChild($content_node);
                                        if(!$child_replaced) {
                                            $content_node = $ead_file->createElement('p', $menu_items_secondary->item($i)->textContent);
                                            $childNode->parentNode->replaceChild($content_node, $childNode);
                                            $child_replaced = true;
                                        }else{
                                            $content_node = $ead_file->createElement('p', $menu_items_secondary->item($i)->textContent);
                                            $content_parent_node->appendChild($content_node);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            break;
        }
    }
}

function clearUnfilledValuesFromc01File($c01_file){
    $c01_file->preserveWhiteSpace = false;
    $c01_file->formatOutput = true;
    $xpath_unused_value_finder = new DOMXPath($c01_file);
    $unused_values = $xpath_unused_value_finder->evaluate("//*[local-name()='odd']");
    foreach($unused_values as $unused_value){
        if(strpos($unused_value->nodeValue, ': {') !== false){
            $unused_value->parentNode->removeChild($unused_value);
        }
    }
    $unitid_unused = $c01_file->getElementsByTagName('unitid')->item(0);
    if($unitid_unused->nodeValue === "{callno}"){
        $unitid_unused->nodeValue = "Doos 999 Map 999";
    }
    $notes_unused = $c01_file->getElementsByTagName('scopecontent')->item(0);
    if($notes_unused->nodeValue === "{notes}"){
        $notes_unused->parentNode->removeChild($notes_unused);
    }
}

function clearUnfilledValuesFromEadFile($ead_file){
    $xpath_unused_value_finder = new DOMXPath($ead_file);
    $unused_values = $xpath_unused_value_finder->query("//*");
    foreach($unused_values as $unused_value){
        if(strpos($unused_value->nodeValue, '{') !== false){
            $unused_value->parentNode->removeChild($unused_value);
        }
    }
}

function replaceValueInEadWithCorrectValue($file, $value_to_find, $text_to_find, $value_to_replace_with, $replace_partially = false){
    $xpath_ead_element_finder = new DOMXPath($file);
    if($value_to_find == "identifier"){
        $element_to_replace = $xpath_ead_element_finder->evaluate("//*[local-name()='eadid']");
        $element_value_to_replace = $element_to_replace[0]->getAttribute($value_to_find);
        $element_value_to_replace = str_replace('{' . $text_to_find . '}', $value_to_replace_with, $element_value_to_replace);
        $element_to_replace[0]->setAttribute($value_to_find, $element_value_to_replace);
    }
    else {
        $element_to_replace = $xpath_ead_element_finder->evaluate("//*[local-name()='$value_to_find']");
        if ($replace_partially == true) {
            $element_to_replace[0]->nodeValue = str_replace('{' . $text_to_find . '}', $value_to_replace_with, $element_to_replace[0]->nodeValue);
        } else {
            $element_to_replace[0]->nodeValue = $value_to_replace_with;
        }
    }
}

function replacePreferciteGroupTitleInEad($file, $value_to_find, $text_to_find, $value_to_replace_with){
    $xpath_ead_element_finder = new DOMXPath($file);
    $element_to_replace = $xpath_ead_element_finder->evaluate("//*[local-name()='$value_to_find']");
    $element_to_replace_children = $element_to_replace->item(0)->childNodes;
    $element_to_replace_children->item(1)->nodeValue = str_replace('{' . $text_to_find . '}', $value_to_replace_with, $element_to_replace_children->item(1)->nodeValue);
}

function replaceValueInC01WithCorrectValue($file, $value_to_find, $value_to_replace_with, $search_for_type){
    $value_to_replace_with = str_replace('&', '&amp;', $value_to_replace_with);
    $xpath_c01_element_finder = new DOMXPath($file);
    if($search_for_type === true){
        $element_to_replace = null;
        if($value_to_find != 'notes') {
            $element_to_replace = $xpath_c01_element_finder->evaluate("//c01/odd[@type='$value_to_find']");
        }else if($value_to_find == 'notes'){
            $scope_content = 'scopecontent';
            $element_to_replace = $xpath_c01_element_finder->evaluate("//*[local-name()='$scope_content']");
        }
        if($element_to_replace != null) {
            $items_to_find = ['{'.$value_to_find.'}', '&'];
            $items_to_replace = [$value_to_replace_with, '&amp;'];
            $value_to_add_to_element = str_replace($items_to_find, $items_to_replace, $element_to_replace[0]->nodeValue);
//            $temp = $file->createElement('p', str_replace('{' . $value_to_find . '}', $value_to_replace_with, $element_to_replace[0]->nodeValue));
            $temp = $file->createElement('p', $value_to_add_to_element);
            $element_to_replace[0]->nodeValue = "";
            $element_to_replace[0]->appendChild($temp);
        }
    }else{
        $element_to_replace = $xpath_c01_element_finder->evaluate("//*[local-name()='$value_to_find']");
        if($element_to_replace[0] != null) {
            if ($element_to_replace[0]->hasChildNodes()) {
                $existing_childNodes = array();
                foreach ($element_to_replace[0]->childNodes as $childNode) {
                    if($childNode->nodeType == XML_ELEMENT_NODE) {
                        array_push($existing_childNodes, $childNode->cloneNode());
                    }
                }
                if($value_to_find === "unittitle") {
                    if (substr($value_to_replace_with, -1) == '.') {
                        $element_to_replace[0]->nodeValue = trim($value_to_replace_with);
                    } else{
                        $element_to_replace[0]->nodeValue = trim($value_to_replace_with) . ".";
                    }
                }else if($value_to_find === "unitdate"){
                    $element_to_replace[0]->nodeValue = trim($value_to_replace_with).".";
                }else{
                    $element_to_replace[0]->nodeValue = $value_to_replace_with;
                }
                foreach ($existing_childNodes as $existing_childNode) {
                    $element_to_replace[0]->appendChild($existing_childNode);
                }
            } else {
                if($value_to_find === "unitdate"){
                    $element_to_replace[0]->nodeValue = trim($value_to_replace_with).".";
                }else {
                    $element_to_replace[0]->nodeValue = $value_to_replace_with;
                }
            }
        }
    }
}

function getArchiveNumbersFromDescription(){
    global $collection_description_files;
    $content_files = array();
//    $archive_numbers = array();
    $directory = "staatsarch-eric_1";
    $files = getFilesFromDirectory("staatsarch-eric_1");

    foreach($files as $file){
        if(!is_file($directory.'/'.$file)) {
            $unzipped_files = getFilesFromDirectory($directory.'/'.$file);
            foreach($unzipped_files as $unzipped_file){
                if (strpos($unzipped_file, 'content.xml') !== false) {
                    array_push($content_files, $directory . '/' . $file . '/' . $unzipped_file);
                }
            }
        }
    }

    foreach($content_files as $content_file){
        $dom_document = new DOMDocument();
        $dom_document->load($content_file);
        array_push($collection_description_files, $dom_document);
//        $xpath_archive_number_finder = new DOMXPath($dom_document);
//        $menu_items = $xpath_archive_number_finder->query("//office:text//text:p");
//        if(sizeof($menu_items) > 0) {
//            $archive_number = $menu_items->item(0);
//            array_push($archive_numbers, $archive_number);
//        }
    }
//    return $archive_numbers;
}

function loadFile($filename){
    /**
     * Creating the a Dom Document
     */
    $dom_document = new DOMDocument();
    $dom_document->preserveWhiteSpace = false;
    $dom_document->load($filename);
    $dom_document->formatOutput = true;

    return $dom_document;
}