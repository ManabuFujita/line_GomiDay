#!/usr/local/php/7.4/lib/php
<?php

// Token
$channel_access_token = 'un5fQF1s/oPd3HelK21sobV8u2p+sa008J/Mj8CTR8wECt153FRFhkew+5mCFBdSFfhAj4NIDIZbKqrXz6C8DhgAtDPdxUpCW2JcykN+Tm5JmkxJhO2gXm3kz9EToBqW59DYlhtFD4C6zcPWS6KcggdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'd582103b27c7ad1074d534b1dc925f3f';

//LINESDKの読み込み
require_once('vendor/autoload.php');

require_once('lib/FuncFile.php');
require_once('data/config.php');

use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\Constant\HTTPHeader;

//LINEから送られてきたらtrueになる（Webhook用）
if(isset($_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE]))
{
  reply();
  return;
}

// 日付取得
$dateFormat = 'Y-m-d';
date_default_timezone_set('Asia/Tokyo');
$yesterdayYMD = date($dateFormat, strtotime('-1 day'));
$todayYMD = date($dateFormat);
$tomorrowYMD = date($dateFormat, strtotime('+1 day'));
$dayAfterTomorrowYMD = date($dateFormat, strtotime('+1 day'));

// DB接続
$mysqli = getConnection() ;

// $sql = "SELECT * FROM TgomiDay WHERE Date between '" . $tomorrowYMD . "' and '" . $dayAfterTomorrowYMD . "' order by date asc";
$sql = "SELECT * FROM TgomiDay WHERE Date = '" . $tomorrowYMD . "' order by date asc";

// SQL実行
$rset = execute($mysqli, $sql);

// データがなければ終了
if ($rset->num_rows === 0) return;

// データありの場合、メッセージ送信
$message = "明日は【";
while ($row = $rset->fetch_assoc()) {
  switch ($row["Date"]) {
    case $tomorrowYMD:
        $message .= $row["Kbn"];
        break;
  }
}
$message .= "ごみ】の日だよ！";

// メッセージ送信
broadcast($message);



//---------------------------------------------------
// DBに接続する
//---------------------------------------------------
function getConnection() {
    $server   = "mysql133.phy.lolipop.lan";
    $user     = "LAA0667847";
    $pass     = "vBuZPCtC";
    $database = "LAA0667847-wkw3p1";

    //-------------------
    //DBに接続
    //-------------------
    $mysqli = new mysqli($server, $user, $pass, $database);

    // 接続状況をチェックします
    // if (mysqli_connect_errno()) {
    //     die("データベースに接続できません:" . mysqli_connect_error() . "\n");
    // } else {
    //     echo "データベースの接続に成功しました。\n";
    // }

    $mysqli->set_charset('utf8');

    //-------------------
    // データベース選択
    //-------------------
    $mysqli->select_db($database);

    return $mysqli;
}

//---------------------------------------------------
// SQLを実行する
//---------------------------------------------------
function execute($mysqli, $sql) {
    $result = $mysqli->query($sql);

    return $result;
}


function reply()
{
  global $channel_access_token;
  global $channel_secret;

  //LINEBOTにPOSTで送られてきた生データの取得
  $inputData = file_get_contents("php://input");

  $fileSend->writeFileAdd($inputData);

  //LINEBOTSDKの設定
  $httpClient = new CurlHTTPClient($channel_access_token);
  $bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
  $signature = $_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE];
  $events = $bot->parseEventRequest($inputData, $signature);

  //大量にメッセージが送られると複数分のデータが同時に送られてくるため、foreachをしている。
  foreach($events as $event)
  {
    $sendMessage = new MultiMessageBuilder();
    // $textMessageBuilder = new TextMessageBuilder("test！");
    $message = $event->getText();
    $textMessageBuilder = new TextMessageBuilder($message);
    $sendMessage->add($textMessageBuilder);
    $bot->replyMessage($event->getReplyToken(), $sendMessage);
  }
}


// ブロードキャスト
function broadcast($message)
{
  global $channel_access_token;
  global $channel_secret;

  //LINEBOTSDKの設定
  $httpClient = new CurlHTTPClient($channel_access_token);
  $bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);

  $sendMessage = new MultiMessageBuilder();
  // $textMessageBuilder = new TextMessageBuilder('一斉送信のテスト');
  $textMessageBuilder = new TextMessageBuilder($message);
  $sendMessage->add($textMessageBuilder);
  $bot->broadcast($sendMessage);
}
