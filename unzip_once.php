<?php
/**
 * 指定したZIPファイルを一度だけ展開するユーティリティ。
 * 1) 同じディレクトリの Abitopia.zip を展開
 * 2) ?t=トークン が一致しないと実行しない
 * 3) 一度成功すると .unzipped が作られて再実行できない
 *
 * 利用方法:
 *   サーバーの Abitopia.zip と同フォルダに、現 php をアップロード。
 *   TOKEN に設定した文字列を URL につけて、ブラウザで以下の形式のURLへアクセスします。
 *   https://example.com/unzip_once.php?t=あなたのトークン
 *   成功した場合、この「phpファイル」と「zipファイル」は【必ず削除】してください。
 *   再度実行する場合は .unzipped ファイルを削除するしてください。
 *
 * 実行環境:
 *   PHP 8.x 以降を推奨
 *   ZipArchive 拡張が必要
 *
 * 注意:
 *   大きなZIPファイルは処理に時間がかかる場合があります。
 *   .unzipped ファイルが既に存在する場合は上書きしません。
 */

declare(strict_types=1);

// ===== 設定 =====
const TOKEN = 'あなたのトークン';  // 必ず変更すること
$zipName   = 'Abitopia.zip';   // ファイル名を変更した場合は変更する
// ===== 設定 ここまで =====

$targetDir = __DIR__;

header('Content-Type: text/plain; charset=UTF-8');

// --- トークン確認 ---
if (!isset($_GET['t']) || hash_equals(TOKEN, (string)$_GET['t']) === false) {
  http_response_code(403);
  exit("エラー: トークンが正しくありません。\n");
}

$flag = $targetDir . DIRECTORY_SEPARATOR . '.unzipped';
if (file_exists($flag)) {
  exit("処理済み: すでに解凍されています。"
    . "再実行したい場合 .unzipped を削除してからもう一度アクセスしてください。\n");
}

$zipPath = $targetDir . DIRECTORY_SEPARATOR . $zipName;
if (!is_file($zipPath)) {
  http_response_code(404);
  exit("エラー: {$zipName} が見つかりません。\n");
}

@set_time_limit(0);
@ini_set('memory_limit', '1024M');

if (!is_writable($targetDir)) {
  http_response_code(500);
  exit("エラー: 展開先フォルダに書き込みできません。\n");
}

if (!class_exists('ZipArchive')) {
  http_response_code(500);
  exit("エラー: サーバーに ZipArchive が入っていないため解凍できません。\n");
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
  http_response_code(500);
  exit("エラー: ZIPファイルを開けませんでした。\n");
}

// 安全確認（想定しないパス形式の場合は排除）
for ($i = 0; $i < $zip->numFiles; $i++) {
  $entry = $zip->getNameIndex($i);
  if ($entry === false) { continue; }
  $norm = str_replace(['\\'], '/', $entry);
  if (strpos($norm, '../') !== false || str_starts_with($norm, '/')) {
    $zip->close();
    http_response_code(400);
    exit("エラー: ZIPの中に不正なパスが含まれています: $entry\n");
  }
}

if (!$zip->extractTo($targetDir)) {
  $zip->close();
  http_response_code(500);
  exit("エラー: 解凍に失敗しました。\n");
}
$zip->close();

// 再実行防止フラグ
file_put_contents($flag, (new DateTime())->format(DateTime::ATOM));

// ZIPを自動削除したい場合は下を有効化
// @unlink($zipPath);

echo "成功: {$zipName} を解凍しました。\n";
