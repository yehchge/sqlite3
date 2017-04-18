<?php

require "CSQLite3.php";

$CSQLite3 = new CSQLite3("ex1.db");

// Add
$data = array();
$data['one'] = sRandomString('',7);
$data['two'] = rand(1,100);
$id = $CSQLite3->bInsert('tbl1', $data);
if ($id){
    echo 'Insert ID is : ', $id."\r\n";
}

// Update
$aWhere = array();
$aData = array();
$aWhere['two'] = 10;
$aData['one'] = sRandomString('',10);
$iChangeRows = $CSQLite3->bUpdate('tbl1', $aWhere , $aData);
if ($iChangeRows) {
    echo 'Number of rows modified: ', $iChangeRows."\r\n";
}

// Delete
// $iDbq2 = $CSQLite3->vDelete('tbl1', "two=10");
// if ($iDbq2) {
//     echo 'Number of rows deleted: ', $iDbq2."\r\n";
// }

// List
$results = $CSQLite3->iQuery("SELECT * FROM tbl1 ORDER BY id DESC LIMIT 0,10");
while($row = $CSQLite3->aFetchAssoc($results)){
    echo $row['one']." , ".$row['two']."\r\n";
}

function sRandomString($sString,$sNum){ //(字元,回傳幾位)
    if(strlen($sString)==0){
        $s="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $s.="abcdefghijklmnopqrstuvwxyz";
        $s.="0123456789";
    } else {
        $s=$sString;
    }
    $rs = '';
    for($i=0;$i<$sNum;$i++){
        $rs.=$s{rand(0,strlen($s)-1)};
    }
    return $rs;
}
