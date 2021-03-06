<?php
/**
 * Creates downloadable data on the fly: XML, CSV or Excel
 *
 * LICENSE NOTE:
 *
 * Copyright  2003-2009 Active Agenda Inc., All Rights Reserved.
 *
 * Unless explicitly acquired and licensed from Licensor under another license, the
 * contents of this file are subject to the Reciprocal Public License ("RPL")
 * Version 1.5, or subsequent versions as allowed by the RPL, and You may not copy
 * or use this file in either source code or executable form, except in compliance
 * with the terms and conditions of the RPL. You may obtain a copy of the RPL from
 * Active Agenda Inc. at http://www.activeagenda.net/license.
 *
 * All software distributed under the RPL is provided strictly on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESS OR IMPLIED, AND LICENSOR HEREBY
 * DISCLAIMS ALL SUCH WARRANTIES, INCLUDING WITHOUT LIMITATION, ANY WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, QUIET ENJOYMENT, OR
 * NON-INFRINGEMENT. See the RPL for specific language governing rights and
 * limitations under the RPL.
 *
 * author         Mattias Thorslund <mthorslund@activeagenda.net>
 * copyright      2003-2009 Active Agenda Inc.
 * license        http://www.activeagenda.net/license
 **/

//general settings
require_once '../config.php';
set_include_path(PEAR_PATH . PATH_SEPARATOR . get_include_path());

//this include contains the search class
include_once CLASSES_PATH . '/search.class.php';
include_once CLASSES_PATH . '/components.php';

//this causes session timeouts to display a message instead of redirecting to the login screen 
DEFINE('IS_POPUP', true);

//main include file - performs all general application setup
require_once INCLUDE_PATH . '/page_startup.php';

$listFieldsFileName = GENERATED_PATH . "/{$ModuleID}/{$ModuleID}_ListFields.gen";

//check for cached page for this module
if (!file_exists($listFieldsFileName)){
    trigger_error("Could not find list fields file '$listFieldsFileName'.", E_USER_ERROR);
}

include_once $listFieldsFileName; //returns $fieldHeaders, $fieldTypes, $listFields, $linkFields, $fieldAlign

//remove IsBestPractice
unset($fieldHeaders['IsBestPractice']);
unset($fieldTypes['IsBestPractice']);
unset($linkFields['IsBestPractice']);
unset($fieldAlign['IsBestPractice']);

$headers = array();
foreach($fieldHeaders as $fieldName => $fieldHeader){
    $fieldHeader = ShortPhrase($fieldHeader);
    $headers[] = $fieldHeader;
}

$search = $_SESSION["Search_$ModuleID"];

if(!is_object($search)){
    trigger_error("An active search is required.", E_USER_ERROR);
}

if(!isset($_GET['type'])){
    trigger_error("Invalid request URL. A type is required.", E_USER_ERROR);
}

//check that there's a person selected
$fileTypeSelected = intval($_GET['type']);

$SQL = $search->getListSQL();
$SQL .= $User->getListFilterSQL($ModuleID, true);  //also checks permission

//execute SQL statement
$r = $dbh->getAll($SQL, DB_FETCHMODE_ASSOC);
dbErrorCheck($r);

switch($fileTypeSelected){
case 1:
    //csv
    $saveAsName = $ModuleID.'_'.date( 'Y-m-d_H.i.s').'.utf8.csv';

    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=$saveAsName");
    print '"'.join('", "', $headers) .'"'. "\r\n";
    foreach($r as $row){
        foreach($row as $name => $valule){
            $row[$name] = addslashes($valule);
        }
        print '"';
        print join('","', $row);
        print "\"\r\n";
    }

    break;
case 2:
    //xml
    $saveAsName = $ModuleID.'_'.date( 'Y-m-d_H.i.s').'.xml';

    header("Content-Type: text/xml");
    header("Content-Disposition: attachment; filename=$saveAsName");
	print "<?xml version='1.0' encoding='UTF-8'?>\n";
    print '<document module="'.$ModuleID.'" generated="'.date( 'Y-m-d H.i.s'). '">'."\n";
    if(count($r) > 0){
        foreach($r as $row){
            print "<record>\r\n";
            foreach($row as $name => $valule){
                print "<$name>$valule</$name>\r\n";
            }
            print "</record>\r\n";
        }
    }
    print "</document>\r\n";

    break;
case 3:
    //csv - semicolon, MS encoding
    //csv
    $saveAsName = $ModuleID.'_'.date( 'Y-m-d_H.i.s').'.microsoft.csv';

    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=$saveAsName");
    print iconv('UTF-8','Windows-1250','"'.join('"; "', $headers) .'"'). "\r\n";
    foreach($r as $row){
        foreach($row as $name => $valule){
            $row[$name] = addslashes($valule);
        }
        print '"';
        print iconv('UTF-8','Windows-1250',join('";"', $row));
        print "\"\r\n";
    }
    break;
default:
    trigger_error(gettext("Invalid request URL. Unknown file type requested."), E_USER_ERROR);
    break;
}
?>