#!/usr/bin/env php
<?php

declare(strict_types=1);

$cloverPath = $argv[1] ?? 'build/logs/clover.xml';
$threshold = 97.0;

if (!file_exists($cloverPath)) {
    fwrite(STDERR, "Error: Clover XML file not found at: $cloverPath\n");
    exit(1);
}

$xml = simplexml_load_file($cloverPath);
if ($xml === false) {
    fwrite(STDERR, "Error: Failed to parse clover XML file\n");
    exit(1);
}

$metrics = $xml->project->metrics;
if ($metrics === null) {
    fwrite(STDERR, "Error: No project metrics found in clover XML\n");
    exit(1);
}

$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "Error: No statements found in coverage report\n");
    exit(1);
}

$coverage = ($coveredStatements / $statements) * 100;

printf("Coverage: %.3f%% (%d/%d statements)\n", $coverage, $coveredStatements, $statements);

if ($coverage < $threshold) {
    fwrite(STDERR, sprintf(
        "::error::Coverage %.3f%% is below the required threshold of %.1f%%\n",
        $coverage,
        $threshold
    ));
    exit(1);
}

echo "Coverage meets the required threshold of {$threshold}%\n";
