<?php
use Medoo\Medoo;
set_time_limit(300);

const DB_NAME = 'liturgy_bible';
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASSWORD = '';

include_once 'lib/Medoo.php';
require 'lib/TPEpubCreator.php';

require_once 'lib/BibleLib/bibleConfig.php';
require_once 'lib/BibleLib/redletter.php';
require_once 'lib/BibleLib/bibleLib.php';

$epub = new TPEpubCreator();
$epub->temp_folder = 'output/';
$epub->epub_file = 'epubs/திருவிவிலியம்.epub';
$epub->publisher = 'TNBCLC';
$epub->creator = 'God';
$epub->css = file_get_contents("chapter.css");
$epub->title = 'திருவிவிலியம் - பொது மொழிபெயர்ப்பு';;

$bLib = new bibleLib();
for ($bkID = 1; $bkID <= 75; $bkID ++) {
    
    if( isset( $bLib->bookList [ str_pad($bkID, 3, 0)]  ) ){
        $bkID_ = str_pad($bkID, 3, 0);
        $op = "<h1 id='C{$bkID_}'>{$bLib->bookList [ $bkID_ ]}</h1>" .  $bLib->getChapterHTML($bkID_) . '<span style="page-break-after: always" />';
        $epub->AddPage( $op, false, $bLib->bookList [ $bkID_ ]);
    }
    
    $chapLimit = getAllBookDetail($bkID);
    
    $op = '';
    $op .= "<h1 id='C{$bkID}'>{$chapLimit['tn_s']}</h1><p class='bookTOC'>";
    //Create chapter index
    for ( $chID = $chapLimit['StartChapter'] ; $chID <= $chapLimit['totalChapters']; $chID ++) {
        $bkchID = $bLib->convertBkCh2Code($bkID, $chID);
        $op .= "<a href='#C{$bkchID}'>$chID</a>  .  ";
    }
    $op .= "</p>";
    
    //Introduction
    $op .= '<h2>முன்னுரை</h2>' . $bLib->getChapterHTML($bkID) . '<span style="page-break-after: always" />';
    
    //Fetch each chapter
    for ( $chID = $chapLimit['StartChapter'] ; $chID <= $chapLimit['totalChapters']; $chID ++) {
        $bkchID = $bLib->convertBkCh2Code($bkID, $chID);
        if($chID > 0){
            $op .= "<h1 id='C{$bkchID}'>{$chapLimit['tn_s']}<br/> <a href='#C{$bkID}'>அதிகாரம் $chID</a> </h1>";
        }else{
            $op .= "<h1 id='C{$bkchID}'>{$chapLimit['tn_s']}</h1>";
        }
        $op .= $bLib->getChapterHTML($bkID, $chID);
        $op .= "<span style='page-break-after: always' />";
    }
    $epub->AddPage( $op, false, $chapLimit['tn_s']);
   // break;
}

// Create the EPUB
if ( ! $epub->error ) {
    $epub->CreateEPUB();
    if ( ! $epub->error ) {
        echo 'Success: Download your book <a href="' . $epub->epub_file . '">here</a>.';
    }
} else {
    echo $epub->error;
}

function getAllBookDetail($bkNo = 1)
{
    $database = new medoo([
        'database_type' => 'mysql',
        'database_name' => DB_NAME,
        'server' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PASSWORD,
        'charset' => 'utf8'
    ]);

    $sql = "SELECT	`bn`, `tn_s`, (	SELECT count(DISTINCT (SUBSTRING(id,1,5))) FROM " . BLIB_VRS . " WHERE SUBSTRING(id,1,2) = bn GROUP BY SUBSTRING(id,1,2) ) AS `totalChapters` FROM `" . BLIB_INDEX . "` WHERE bn = $bkNo";

    $bks = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if ($bkNo == 44) {
        $bks[0]['StartChapter'] = 0; // சீராக்கின் ஞானம்
        $bks[0]['totalChapters'] = 51; // சீராக்கின் ஞானம்
    } else {
        $bks[0]['StartChapter'] = 1;
        $bks[0]['totalChapters'] = intval($bks[0]['totalChapters']);
    }
    $bks = $bks[0];
    return $bks;
}