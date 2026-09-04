<?php

test('select items and select options do not use empty string values to prevent Reka UI crash', function () {
    $jsDir = resource_path('js');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jsDir));
    $violations = [];

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array($file->getExtension(), ['vue', 'ts', 'js'])) {
            continue;
        }

        // Skip node_modules or vendor if present
        if (str_contains($file->getPathname(), 'node_modules') || str_contains($file->getPathname(), 'vendor')) {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", $content);

        foreach ($lines as $lineIndex => $line) {
            $lineNumber = $lineIndex + 1;

            // Pattern 1: Direct <SelectItem value="" /> or <SelectItem :value="''" />
            if (preg_match('/<SelectItem[^>]+:?value=["\'](?:["\']\s*["\']|["\'])/', $line)) {
                $violations[] = sprintf(
                    '%s:%d - Direct <SelectItem> has empty string value attribute: %s',
                    $file->getRelativePathname(),
                    $lineNumber,
                    trim($line)
                );
            }

            // Pattern 2: Select option object with label and empty string value: { label: '...', value: '' }
            if (preg_match('/\{\s*label\s*:\s*[\'"][^\'"]*[\'"]\s*,\s*value\s*:\s*[\'"]\s*[\'"]/', $line)
                || preg_match('/\{\s*value\s*:\s*[\'"]\s*[\'"]\s*,\s*label\s*:\s*[\'"][^\'"]*[\'"]/', $line)) {
                $violations[] = sprintf(
                    '%s:%d - Select option has empty string value: %s (use a semantic value like "all" or "default" instead)',
                    $file->getRelativePathname(),
                    $lineNumber,
                    trim($line)
                );
            }
        }
    }

    expect($violations)->toBeEmpty(
        "Found empty string Select options which cause Reka UI runtime crashes:\n".implode("\n", $violations)
    );
});
