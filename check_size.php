<?php
/**
 * 指定ディレクトリ以下の全ファイルのサイズを再帰的に集計し、
 * バイト数とMB換算で結果を出力する簡易ユーティリティ。
 *
 * 利用方法:
 *   ファイル名を変更した場合は、「ファイル名を変更した場合は変更する」部分を変更してください。
 *   サーバーにアップロード後、ブラウザで以下の形式のURLへアクセスします。
 *   URLは環境に応じて適宜変換してください。
 *   http://example.com/check_size.php
 *
 * 実行環境:
 *   PHP 8.x 以降を推奨
 *
 * 注意:
 *   大規模ディレクトリでは処理時間が長くなる可能性があります。
 *   シンボリックリンクやアクセス権限のないファイルには対応していません。
 */

// ディレクトリの合計サイズ
function dirSize(string $dir): int {
  if (!is_dir($dir)) {
    return -1;
  }
  $size  = 0;
  $flags = FilesystemIterator::SKIP_DOTS;
  $it = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, $flags),
      RecursiveIteratorIterator::LEAVES_ONLY
  );
  foreach ($it as $file) {
      if ($file->isLink()) { continue; }
      if ($file->isFile()) { $size += $file->getSize(); }
  }
  return $size;
}

// ZIPファイルのサイズ
function zipSize(string $zip): int {
  if (!is_file($zip)) {
    return -1;
  }
  return filesize($zip);
}

$target = 'Abitopia';  // ファイル名を変更した場合は変更する

// 指定
$zipPath = __DIR__ . '\\' . $target . '.zip';
$dirPath = __DIR__ . '\\' . $target;

// 解凍前
$zipBytes = zipSize($zipPath);
echo "ZIPファイル： $zipPath <br>\n";
if ($zipBytes < 0) {
  echo "zip ファイルが存在しません。 <br>\n";
} else {
  echo "Total： " . number_format($zipBytes) . " bytes <br>\n";
  echo "Total： " . round($zipBytes / 1024 / 1024, 2) . " MB <br>\n";
}

echo "<br>\n";
echo "------------------------------------------------------------<br>\n";
echo "<br>\n";

// 解凍後
$dirBytes = dirSize($dirPath);
echo "調査対象フォルダ： $dirPath <br>\n";
if ($dirBytes < 0) {
  echo "指定フォルダが存在しません。 <br>\n";
} else {
  echo "Total： " . number_format($dirBytes) . " bytes <br>\n";
  echo "Total： " . round($dirBytes / 1024 / 1024, 2) . " MB <br>\n";
}

