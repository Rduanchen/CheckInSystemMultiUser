<?php
$csv_file = './record.csv'; // Replace with the actual path to your CSV file

if (!file_exists($csv_file)) {
    echo 'File does not exist: ' . realpath($csv_file);
    exit;
}

if (!is_readable($csv_file)) {
    echo 'File is not readable: ' . realpath($csv_file);
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="record.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate'); // No cache
header('Pragma: no-cache'); // No cache
header('Expires: 0'); // No cache

if (readfile($csv_file) === false) {
    echo 'Error reading the file.';
}
exit;
?>