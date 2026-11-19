<?php

/**
 * php bin/patch-file.php FILE SEARCH REPLACE
 * Replaces SEARCH with REPLACE in FILE; fails unless SEARCH occurs exactly once,
 * so an upstream change stops the build instead of being silently skipped.
 */

declare(strict_types=1);

[, $file, $search, $replace] = $argv + [null, null, null, null];
$content = is_string($file) ? file_get_contents($file) : false;

if (false === $content || !is_string($search) || !is_string($replace) || 1 !== substr_count($content, $search)) {
    fwrite(STDERR, "patch-file: anchor not found exactly once in {$file}:\n{$search}\n");
    exit(1);
}

file_put_contents($file, str_replace($search, $replace, $content));
