<?php

/**
 * @file
 * Merge PHPUnit shard coverage files into html, xml and clover reports.
 *
 * Usage: php .github/phpunit-merge-coverage.php <directory of .cov files> <output directory>
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Clover;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlReport;
use SebastianBergmann\CodeCoverage\Report\Xml\Facade as XmlReport;
use SebastianBergmann\CodeCoverage\Version;

require __DIR__ . '/../vendor/autoload.php';

[, $inputDir, $outputDir] = $argv + [NULL, NULL, NULL];
if (!$inputDir || !$outputDir) {
  fwrite(STDERR, "Usage: php phpunit-merge-coverage.php <input dir> <output dir>\n");
  exit(1);
}

$files = glob(rtrim($inputDir, '/') . '/*.cov');
if (!$files) {
  fwrite(STDERR, "No coverage files found in $inputDir\n");
  exit(1);
}

/** @var \SebastianBergmann\CodeCoverage\CodeCoverage|null $merged */
$merged = NULL;
foreach ($files as $file) {
  echo "Merging $file\n";
  $coverage = include $file;
  if (!$coverage instanceof CodeCoverage) {
    fwrite(STDERR, "$file is not a valid coverage file\n");
    exit(1);
  }
  $merged ? $merged->merge($coverage) : $merged = $coverage;
}

(new HtmlReport())->process($merged, "$outputDir/html");
(new XmlReport(Version::id()))->process($merged, "$outputDir/xml");
(new Clover())->process($merged, "$outputDir/clover.xml");
