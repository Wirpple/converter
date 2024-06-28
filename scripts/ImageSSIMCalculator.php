<?php

class ImageSSIMCalculator
{
    private $inputImagePath;
    private $inputDir;
    private $outputDir;

    public function __construct($inputImagePath)
    {
        $this->inputImagePath = $inputImagePath;
        $this->inputDir = dirname($inputImagePath);
        $this->outputDir = realpath(__DIR__ . '/../output');
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }

    private function saveImage($image, $path, $format, $quality): void
    {
        switch ($format) {
            case 'jpeg':
                $result = imagejpeg($image, $path, $quality);
                break;
            case 'webp':
                if (!function_exists('imagewebp')) {
                    throw new Exception('Поддержка WebP не включена в конфигурации PHP.');
                }
                $result = imagewebp($image, $path, $quality);
                break;
            case 'avif':
                if (!function_exists('imageavif')) {
                    throw new Exception('Поддержка AVIF не включена в конфигурации PHP.');
                }
                $result = imageavif($image, $path, $quality);
                break;
            default:
                throw new Exception("Неподдерживаемый формат: $format");
        }
        if (!$result) {
            throw new Exception("Не удалось сохранить изображение: $path");
        }
        echo "Сохраненное изображение: $path, Формат: $format, Качество: $quality, Размер: " . filesize($path) . " bytes\n";
    }

    private function loadImage($path)
    {
        $info = getimagesize($path);
        if (!$info) {
            throw new Exception("Не удалось прочитать информацию об изображении: $path");
        }
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) {
                    throw new Exception('Поддержка WebP не включена в конфигурации PHP.');
                }
                return imagecreatefromwebp($path);
            case 'image/avif':
                if (!function_exists('imagecreatefromavif')) {
                    throw new Exception('Поддержка AVIF не включена в конфигурации PHP.');
                }
                return imagecreatefromavif($path);
            default:
                throw new Exception('Неподдерживаемый формат: ' . $mime);
        }
    }

    private function convertToPng($inputPath, $outputPath): void
    {
        $command = "magick $inputPath $outputPath";
        echo "Конвертация $inputPath на $outputPath используя команду: $command\n";
        $output = shell_exec($command);
        if (!file_exists($outputPath)) {
            throw new Exception("Не удалось преобразовать изображение в PNG: $inputPath. Вывод команды: $output");
        }
        echo "Конвертация $inputPath на $outputPath успешно. Размер: " . filesize($outputPath) . " bytes\n";
    }

    private function calculateSSIM($image1, $image2): string
    {
        $dockerImageName = "ssimulacra2-image";
        $image1Basename = basename($image1);
        $image2Basename = basename($image2);

        $dockerCommand = [
            "docker",
            "run",
            "--rm",
            "-v",
            $this->inputDir . ":/input",
            "-v",
            $this->outputDir . ":/output",
            $dockerImageName,
            "/input/" . $image1Basename,
            "/output/" . $image2Basename
        ];

        $commandString = implode(' ', $dockerCommand);
        echo "Выполняем команду: $commandString\n";
        $output = shell_exec($commandString . " 2>&1");
        if (!$output) {
            echo "Ошибка при выполнении команды.\n";
            return "Ошибка при расчете SSIM.";
        }

        echo "Вывод команды: $output\n";

        if (preg_match('/(\d+\.\d+)\s*$/m', $output, $matches)) {
            return trim($matches[1]);
        } else {
            return "Ошибка при расчете SSIM.";
        }
    }

    public function process(): void
    {
        try {
            $originalImage = $this->loadImage($this->inputImagePath);
            $qualities = range(50, 100);
            $formats = ['jpeg', 'webp', 'avif'];
            $csvData = [];

            foreach ($qualities as $quality) {
                $ssimValues = [$quality];
                foreach ($formats as $format) {
                    $outputPath = $this->outputDir . "/image_{$quality}.{$format}";
                    try {
                        $this->saveImage($originalImage, $outputPath, $format, $quality);
                    } catch (Exception $e) {
                        echo "Ошибка сохранения изображения: " . $e->getMessage() . "\n";
                        $ssimValues[] = "Ошибка";
                        continue;
                    }

                    if (!file_exists($outputPath)) {
                        echo "Файл не создан: $outputPath\n";
                        $ssimValues[] = "Ошибка";
                        continue;
                    }

                    $reconvertedPath = $this->outputDir . "/reconverted_image_{$quality}.png";
                    try {
                        $this->convertToPng($outputPath, $reconvertedPath);
                        if (!file_exists($reconvertedPath)) {
                            echo "Файл не создан: $reconvertedPath\n";
                            $ssimValues[] = "Ошибка";
                            continue;
                        }

                        $ssim = $this->calculateSSIM($this->inputImagePath, $reconvertedPath);
                        $ssimValues[] = $ssim;
                    } catch (Exception $e) {
                        echo "Исключение: " . $e->getMessage() . "\n";
                        $ssimValues[] = "Ошибка";
                    }

                    unlink($outputPath);
                    unlink($reconvertedPath);
                }
                $csvData[] = $ssimValues;
            }
            imagedestroy($originalImage);

            $csvFile = fopen($this->outputDir . "/ssim_results.csv", "w");
            foreach ($csvData as $row) {
                $line = array_shift($row) . ': ' . implode(';', $row);
                fwrite($csvFile, $line . "\n");
            }
            fclose($csvFile);
            echo "Обработка завершена. Результаты сохранены в " . $this->outputDir . "/ssim_results.csv\n";
        } catch (Exception $e) {
            echo "Ошибка: " . $e->getMessage() . "\n";
        }
    }
}

// Выполнение главного скрипта
$inputImagePath = $argv[1] ?? '';
$calculator = new ImageSSIMCalculator($inputImagePath);
$calculator->process();
