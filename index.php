#!/usr/local/php/7.4/lib/php
<?php

// Token
$channel_access_token = 'un5fQF1s/oPd3HelK21sobV8u2p+sa008J/Mj8CTR8wECt153FRFhkew+5mCFBdSFfhAj4NIDIZbKqrXz6C8DhgAtDPdxUpCW2JcykN+Tm5JmkxJhO2gXm3kz9EToBqW59DYlhtFD4C6zcPWS6KcggdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'd582103b27c7ad1074d534b1dc925f3f';

//LINESDKの読み込み
require_once('vendor/autoload.php');
require_once('lib/FuncFile.php');

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
// $dateFormatCronLog = 'Y/m/d H:i:s';
date_default_timezone_set('Asia/Tokyo');
$yesterdayYMD = date($dateFormat, strtotime('-1 day'));
$todayYMD = date($dateFormat);
$tomorrowYMD = date($dateFormat, strtotime('+1 day'));
$dayAfterTomorrowYMD = date($dateFormat, strtotime('+1 day'));

// ファイル処理
// $fileSend = new FuncFile("logSend.txt");
// $fileCron = new FuncFile("logCron.txt");


// test
// $tomorrowYMD = '2021-04-01';
// $dayAfterTomorrowYMD = '2021-04-02';

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
$message .= "ごみ】の日です";

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


// 今日受信した対象メールを取得
// $client = getClient();
// $service = new Google_Service_Gmail($client);

// $user = 'me';
// $optParams = [];

// 昨日の対象メール数を取得
// $optParams['q'] = 'from:'.$targetMailAddress.' after:'.$yesterdayYMD.' before:'.$todayYMD;
// $results = $service->users_messages->listUsersMessages($user, $optParams);
// $cntTargetRecievedYesterday = $results['resultSizeEstimate'];
//
// // 今日の対象メール数を取得
// $optParams['q'] = 'from:'.$targetMailAddress.' after:'.$todayYMD.' before:'.$tomorrowYMD;
// $results = $service->users_messages->listUsersMessages($user, $optParams);
// $cntTargetRecievedToday = $results['resultSizeEstimate'];
//
// $cntTargetRecievedTotal = $cntTargetRecievedYesterday
//                         + $cntTargetRecievedToday;
//
// // 対象メールがなければ終了
// echo '昨日〜今日の通知対象メール数：'.$cntTargetRecievedTotal.'件<br>';
// if ($cntTargetRecievedTotal == 0)
// {
//   echo "今日は通知対象のメールがありません。";
//   return;
// }
//
// // 通知ロク取得
// $fileSendArray = $fileSend->getFileArray();
// // 通知ログから昨日の通知数を取得
// $cntSendedYesterday = is_null($fileSendArray) ? 0 : countifArray($fileSendArray, $yesterdayYMD);
// // 通知ログから今日の通知数を取得
// $cntSendedToday = is_null($fileSendArray) ? 0 : countifArray($fileSendArray, $todayYMD);
//
// $cntSendedTotal = $cntSendedYesterday
//                 + $cntSendedToday;
//
// echo '昨日〜今日の送信済みの通知数：'.$cntSendedTotal.'件<br>';
// echo '↓<br>';
//
// // 新規対象メールがあるかチェック
// if($cntTargetRecievedTotal > $cntSendedTotal)
// {
//   // 未通知あり
//   broadcast($msgNotification);
//   echo 'LINE通知を送信しました。';
// } else {
//   // 全て通知済み
//   echo '昨日〜今日は全て通知済みです。';
//   return;
// }
//
// // 未通知が昨日のメールの場合、通知ログに昨日の日付を追記
// if($cntTargetRecievedYesterday > $cntSendedYesterday)
// {
//   writeSendLog($yesterdayYMD);
//   return;
// }
//
// // 未通知が今日のメールの場合、通知ログに今日に日付を追記
// if($cntTargetRecievedToday > $cntSendedToday)
// {
//   writeSendLog($todayYMD);
//   return;
// }

// -----------------------------------------------------------------------------

// function writeSendLog($message)
// {
//   global $fileSend;
//
//   // cronログ書き出し
//   $fileSend->writeFileAdd($message);
// }

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

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
// function getClient()
// {
//     $client = new Google_Client();
//     $client->setApplicationName('Gmail API PHP Quickstart');
//     $client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
//     $client->setAuthConfig('credentials.json');
//     $client->setAccessType('offline');
//     $client->setPrompt('select_account consent');
//
//     // Load previously authorized token from a file, if it exists.
//     // The file token.json stores the user's access and refresh tokens, and is
//     // created automatically when the authorization flow completes for the first
//     // time.
//     $tokenPath = 'token.json';
//     if (file_exists($tokenPath)){
//         $accessToken = json_decode(file_get_contents($tokenPath), true);
//         $client->setAccessToken($accessToken);
//     }
//
//     // If there is no previous token or it's expired.
//     if ($client->isAccessTokenExpired()) {
//         // Refresh the token if possible, else fetch a new one.
//         if ($client->getRefreshToken()) {
//             $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
//         } else {
//             // Request authorization from the user.
//             $authUrl = $client->createAuthUrl();
//             printf("Open the following link in your browser:\n%s\n", $authUrl);
//             print 'Enter verification code: ';
//             $authCode = trim(fgets(STDIN));
//
//             // Exchange authorization code for an access token.
//             $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
//             $client->setAccessToken($accessToken);
//
//             // Check to see if there was an error.
//             if (array_key_exists('error', $accessToken)) {
//                 throw new Exception(join(', ', $accessToken));
//             }
//         }
//         // Save the token to a file.
//         if (!file_exists(dirname($tokenPath))) {
//             mkdir(dirname($tokenPath), 0700, true);
//         }
//         file_put_contents($tokenPath, json_encode($client->getAccessToken()));
//     }
//     return $client;
// }

// function countifArray($array, $find)
// {
//   $cntMatch = 0;
//   foreach($array as $elem)
//   {
//     if(trim($elem) == $find) $cntMatch++;
//   }
//   return $cntMatch;
// }
