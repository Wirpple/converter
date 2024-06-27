<?php

function calculate_ssim($image1, $image2)
{
    $venv_python = escapeshellcmd("/Users/mac/PhpstormProjects/testScript/venv/bin/python3");
    $ssim_script = escapeshellcmd("/Users/mac/PhpstormProjects/testScript/src/ssim_calculator.py");
    $command = "$venv_python $ssim_script " . escapeshellarg($image1) . " " . escapeshellarg($image2);
    $output = shell_exec($command . " 2>&1");
    if ($output === null) {
        return "Error calculating SSIM.";
    }
    return trim($output);
}

function validate_image_path($path)
{
    if (!file_exists($path)) {
        throw new Exception("File does not exist: $path");
    }
    if (!is_readable($path)) {
        throw new Exception("File is not readable: $path");
    }
    return realpath($path);
}

if (php_sapi_name() == 'cli') {
    // Обработка через командную строку
    if ($argc < 3 || $argc % 2 == 0) {
        echo "Usage: php calculate_ssim.php <image1> <image2> [<image3> <image4> ...]" . PHP_EOL;
        exit(1);
    }

    $results = [];
    for ($i = 1; $i < $argc; $i += 2) {
        try {
            $image1 = validate_image_path($argv[$i]);
            $image2 = validate_image_path($argv[$i + 1]);

            $ssim = calculate_ssim($image1, $image2);
            $results[] = "Pair " . (($i + 1) / 2) . ' ' . $ssim;
        } catch (Exception $e) {
            $results[] = "Pair " . (($i + 1) / 2) . ": Error: " . $e->getMessage();
        }
    }

    foreach ($results as $result) {
        echo $result . PHP_EOL;
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Обработка через веб-интерфейс
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imagePairs = [];
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($key % 2 === 0) {
                $image1Path = $uploadDir . basename($_FILES['images']['name'][$key]);
                move_uploaded_file($tmpName, $image1Path);
                $imagePairs[$key / 2]['image1'] = realpath($image1Path);
            } else {
                $image2Path = $uploadDir . basename($_FILES['images']['name'][$key]);
                move_uploaded_file($tmpName, $image2Path);
                $imagePairs[($key - 1) / 2]['image2'] = realpath($image2Path);
            }
        }

        $results = [];
        foreach ($imagePairs as $pair) {
            try {
                $ssim = calculate_ssim($pair['image1'], $pair['image2']);
                $results[] = $ssim;
            } catch (Exception $e) {
                $results[] = "Error: " . $e->getMessage();
            }
        }

        header('Content-Type: application/json');
        echo json_encode($results);
    }
}
