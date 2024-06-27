<?php

function calculate_ssim($image1, $image2)
{
    $venv_python = escapeshellcmd("/Users/mac/PhpstormProjects/testScript/venv/bin/python3");
    $ssim_script = escapeshellcmd("/Users/mac/PhpstormProjects/testScript/scripts/python/ssim_calculator.py");
    $command = "$venv_python $ssim_script " . escapeshellarg($image1) . " " . escapeshellarg($image2);
    $output = shell_exec($command . " 2>&1");
    if ($output === null) {
        return "Error calculating SSIM.";
    }
    return trim($output);
}

function save_image($image, $path, $format, $quality)
{
    switch ($format) {
        case 'jpeg':
            imagejpeg($image, $path, $quality);
            break;
        case 'webp':
            imagewebp($image, $path, $quality);
            break;
        case 'avif':
            imageavif($image, $path, $quality);
            break;
    }
}

function load_image($path)
{
    $info = getimagesize($path);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/png':
            return imagecreatefrompng($path);
        case 'image/jpeg':
            return imagecreatefromjpeg($path);
        case 'image/webp':
            return imagecreatefromwebp($path);
        default:
            throw new Exception('Unsupported image format: ' . $mime);
    }
}

function convert_and_calculate_ssim($input_image_path)
{
    $original_image = load_image($input_image_path);
    $output_dir = __DIR__ . '/output/';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0777, true);
    }

    $qualities = range(50, 100);
    $formats = ['jpeg', 'webp', 'avif'];
    $csv_data = [];

    foreach ($qualities as $quality) {
        $ssim_values = [$quality];
        foreach ($formats as $format) {
            $output_path = $output_dir . "image_{$quality}.{$format}";
            save_image($original_image, $output_path, $format, $quality);

            $reconverted_path = $output_dir . "reconverted_image_{$quality}.png";
            $reconverted_image = imagecreatefromstring(file_get_contents($output_path));
            imagepng($reconverted_image, $reconverted_path);

            $ssim = calculate_ssim($input_image_path, $reconverted_path);
            $ssim_values[] = $ssim;

            unlink($output_path);
            unlink($reconverted_path);
        }
        $csv_data[] = $ssim_values;
    }
    imagedestroy($original_image);

    $csv_file = fopen($output_dir . "ssim_results.csv", "w");
    foreach ($csv_data as $row) {
        fputcsv($csv_file, $row, ";");
    }
    fclose($csv_file);
}

$input_image_path = $argv[1] ?? '/path/to/input_image.png';
convert_and_calculate_ssim($input_image_path);
