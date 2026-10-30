<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Directory relative to assets/data/
$dir = "../newsletters/";
$pdfFiles = glob($dir . "*.{pdf,PDF}", GLOB_BRACE);
$newsletters = [];

if ($pdfFiles !== false && count($pdfFiles) > 0) {
    // Remove duplicates if case-insensitive filesystem returns same file twice
    $pdfFiles = array_unique($pdfFiles);

    // Sort newest files first based on file modification timestamp
    usort($pdfFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    foreach ($pdfFiles as $pdfPath) {
        $filename = basename($pdfPath);
        $fileBase = pathinfo($filename, PATHINFO_FILENAME);
        $txtPath = $dir . $fileBase . '.txt';

        // Default fallbacks if no matching .txt sidecar file exists
        $season = "Archive Edition";
        $title = ucwords(str_replace(['-', '_'], ' ', $fileBase));
        $description = "Download and read this community update from Bread of Life.";
        $date = date("F Y", filemtime($pdfPath));

        // Read sidecar .txt file if present: Line format -> Season|Title|Description
        if (file_exists($txtPath)) {
            $content = trim(file_get_contents($txtPath));
            // Limit split to 3 parts so pipes inside description are preserved
            $parts = explode('|', $content, 3);

            if (count($parts) >= 3) {
                $season = trim($parts[0]);
                $title = trim($parts[1]);
                $description = trim($parts[2]);
            } elseif (count($parts) == 2) {
                $season = trim($parts[0]);
                $title = trim($parts[1]);
            } elseif (count($parts) == 1 && !empty($parts[0])) {
                $title = trim($parts[0]);
            }
        }

        $newsletters[] = [
            "season" => $season,
            "title" => $title,
            "date" => $date,
            "description" => $description,
            "pdfUrl" => "/assets/newsletters/" . $filename
        ];
    }
}

echo json_encode($newsletters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
