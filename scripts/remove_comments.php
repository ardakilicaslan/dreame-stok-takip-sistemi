<?php

function remove_css_comments_from_file($filepath) {
    $content = file_get_contents($filepath);
    if ($content === false) {
        echo "Could not read file: $filepath\n";
        return;
    }

    // Regex to remove multi-line (/* ... */) comments from CSS
    $cleaned_content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
    
    // Normalize newlines and remove excessive blank lines
    $cleaned_content = preg_replace('/(?:\r\n|\r|\n){2,}/', "\n\n", $cleaned_content);

    file_put_contents($filepath, trim($cleaned_content));
    echo "Comments removed from: $filepath\n";
}

function find_css_files($dir) {
    $css_files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isDir()){
             if (strpos($file->getPathname(), 'vendor') !== false) {
                continue;
            }
            continue;
        }
        if (strtolower($file->getExtension()) == 'css') {
            $css_files[] = $file->getPathname();
        }
    }
    return $css_files;
}

$project_root = dirname(__DIR__);
$css_files = find_css_files($project_root);

foreach ($css_files as $file) {
    remove_css_comments_from_file($file);
}

echo "All CSS files have been processed.\n";
