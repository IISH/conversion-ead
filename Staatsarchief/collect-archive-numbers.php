<?php
/**
 * Created by IntelliJ IDEA.
 * User: Igor van der Bom
 * Date: 10-1-2018
 * Time: 10:20
 */

echo date("Y-m-d H:i:s") ."<br>";
echo "Start collecting archive numbers"."<br>";
print("<br>");

$directory = "staatsarch-eric_1";
$files = getFilesFromDirectory("staatsarch-eric_1");
$content_files = array();

//foreach($files as $file){
//    print(substr($file,0,3));
//    print("<br>");
//
////    $collection_file = fopen($directory.'/'.$file, 'r');
////    print(fread($collection_file, 10));
////    print("<br>");
//
//    $zip = new ZipArchive;
//    if ($zip->open($directory.'/'.$file) === TRUE) {
//        $zip->extractTo($directory.'/'.substr($file,0,3));
//        $zip->close();
//        echo 'ok';
//    } else {
//        echo 'failed';
//    }
//
//}

$directories = getFilesFromDirectory($directory);
foreach($files as $file){
    if(!is_file($directory.'/'.$file)) {
//        print("Folder: ". $file);
//        print('<br>');
        $unzipped_files = getFilesFromDirectory($directory.'/'.$file);
        foreach($unzipped_files as $unzipped_file){
            if (strpos($unzipped_file, 'content.xml') !== false) {
//                print($unzipped_file);
//                print('<br>');
                array_push($content_files, $directory . '/' . $file . '/' . $unzipped_file);
            }
        }
    }
}

print("Checking the content files!<br>");

foreach($content_files as $content_file){
    $dom_document = new DOMDocument();
    $dom_document->load($content_file);
//    print_r($dom_document->textContent);
//    print('<br>');
    $xpath_archive_number_finder = new DOMXPath($dom_document);
//    $collection_standard = "Standard";
//    $menu_items = $xpath_archive_number_finder->query("//office:text/text:p[@style-name='$collection_standard']");
    $menu_items = $xpath_archive_number_finder->query("//office:text/text:p");
//    print($menu_items->length);
    print('<br>');
    print_r($menu_items->item(0)->textContent);
    print('<br>');
    print_r($menu_items->item(2)->textContent);
    print('<br>');
    print_r($menu_items->item(4)->textContent);
    print('<br>');
    print_r($menu_items->item(6)->textContent);
    print('<br>');
    print_r($menu_items->item(8)->textContent);
    print('<br>');
    print_r($menu_items->item(10)->textContent);
    print('<br>');
//    foreach($menu_items as $menu_item) {
//        print_r($menu_item);
//        print('<br>');
//    }
    print('<br>');
}

function getFilesFromDirectory($directory){
    $allFiles = scandir($directory);
    return array_diff($allFiles, array('.', '..'));
}

print("<br>");
echo date("Y-m-d H:i:s") ."<br>";
echo "Done collecting archive numbers"."<br>";
print("<br>");