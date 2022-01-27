#!/usr/local/php/7.4/lib/php
<?php

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
  // reply("test");
  $message = getScheduleMessage();
  reply($message);
  return;
}

// 日付取得
$dateFormat = 'Y-m-d';
date_default_timezone_set('Asia/Tokyo');
$yesterdayYMD = date($dateFormat, strtotime('-1 day'));
$todayYMD = date($dateFormat);
$tomorrowYMD = date($dateFormat, strtotime('+1 day'));
$dayAfterTomorrowYMD = date($dateFormat, strtotime('+1 day'));
$week1 = date($dateFormat, strtotime('+1 week'));

// DB接続
$mysqli = getConnection();

// 1週間後の予定を送信
send1Week($mysqli, $week1);

// 明日の予定を送信
sendTomorrow($mysqli, $tomorrowYMD);

//---------------------------------------------------
// 1週間後の予定を送信
//---------------------------------------------------
function send1Week($mysqli, $date)
{
  $sql = "SELECT * FROM TgomiDay WHERE Date = '" . $date . "' order by date asc";

  // SQL実行
  $rset = execute($mysqli, $sql);

  // データがなければ終了
  if ($rset->num_rows === 0) return;

  // データありの場合、メッセージ送信
  $message = "";
  while ($row = $rset->fetch_assoc())
  {
    switch ($row["Date"])
    {
      case $date:
        $kbn = $row["Kbn"];

        if (strpos($kbn, "紙") !== false or
            strpos($kbn, "不燃") !== false or
            strpos($kbn, "缶") !== false or
            strpos($kbn, "ペ") !== false or
            strpos($kbn, "ビン") !== false)
        {
          $message .= $kbn;
          break;
        }
    }
  }
  if ($message === "")
  {
    return;
  } else {
    $message = "【予定】来週は【" . $message . "ごみ】の日だよ！";
    // メッセージ送信
    broadcast($message);
  }
}


//---------------------------------------------------
// 明日の予定を送信
//---------------------------------------------------
function sendTomorrow($mysqli, $date)
{
  // $sql = "SELECT * FROM TgomiDay WHERE Date between '" . $tomorrowYMD . "' and '" . $dayAfterTomorrowYMD . "' order by date asc";
  $sql = "SELECT * FROM TgomiDay WHERE Date = '" . $date . "' order by date asc";

  // SQL実行
  $rset = execute($mysqli, $sql);

  // データがなければ終了
  if ($rset->num_rows === 0) return;

  // データありの場合、メッセージ送信
  $message = "明日は【";
  while ($row = $rset->fetch_assoc())
  {
    switch ($row["Date"])
    {
      case $date:
        $message .= $row["Kbn"];
        break;
    }
  }
  $message .= "ごみ】の日だよ！";

  // メッセージ送信
  broadcast($message);
}

//---------------------------------------------------
// DBに接続する
//---------------------------------------------------
function getConnection()
{
  global $server;
  global $user;
  global $pass;
  global $database;

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
function execute($mysqli, $sql)
{
  $result = $mysqli->query($sql);

  // echo "<PRE>";
  // print_r($result);
  // echo "</PRE>";

  return $result;
}

//---------------------------------------------------
// 直近のごみの日を返す
//---------------------------------------------------
function getScheduleMessage()
{
  // DB接続
  $mysqli = getConnection();

  // 明日以降の区分ごとの直近のごみの日を取得
  $dateFormat = 'Y-m-d';
  $tomorrowYMD = date($dateFormat, strtotime('+1 day'));
  $sql = "SELECT MIN(Date) AS Date_min, Kbn FROM TgomiDay where Date >= '" . $tomorrowYMD . "' GROUP BY Kbn ORDER BY Date_min ";

  // SQL実行
  $rset = execute($mysqli, $sql);

  // データがなければ終了
  if ($rset->num_rows === 0) return;

  // データありの場合、メッセージ送信
  $week = array( "日", "月", "火", "水", "木", "金", "土" );

  $message = "明日以降のごみの日は\n";
  while ($row = $rset->fetch_assoc())
  {
    // $dateFormat = "m/d";
    $dateFormat = "n/j";
    $timestamp = strtotime($row["Date_min"]);
    // $message .= $row["Kbn"] . " : " . date($dateFormat, $timestamp) . "(" . $week[date("w", $timestamp)] . ")\n";
    $message .= date($dateFormat, $timestamp) . "(" . $week[date("w", $timestamp)] . "): " . $row["Kbn"]  . "\n";
  }
  $message .= "だよ！";

  return $message;
}

function reply($message)
{
  global $channel_access_token;
  global $channel_secret;

  //LINEBOTにPOSTで送られてきた生データの取得
  $inputData = file_get_contents("php://input");

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
    // $message = $event->getText();
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
