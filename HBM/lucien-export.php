<?php
/**
 * Created by IntelliJ IDEA.
 * User: Igor van der Bom
 * Date: 17-10-2017
 * Time: 12:04
 */
require 'vendor/autoload.php';
use Ramsey\Uuid\Uuid;

function createListForLucien($archiveNumber, $photoId, $filenamePhoto){

    $row = array();
    $row[] = $archiveNumber;
    $row[] = $photoId;
    $row[] = $filenamePhoto;
    $uuid4 = "10622/" . Uuid::uuid4();
    $row[] = $uuid4;

    $fp = fopen('lucien.csv', 'a');

    fputcsv($fp, $row);
    fclose($fp);
}