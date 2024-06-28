# Image Conversion and SSIM Calculation Project

Этот проект предназначен для конвертации изображений в различные форматы (JPEG, WebP, AVIF) с разным качеством, а также для вычисления метрики структурного сходства (SSIM) между исходными и конвертированными изображениями.

## Установка

### Предварительные требования

- PHP 8.1 или старше с установленной библиотекой GD и поддержкой форматов WebP и AVIF.
- Python 3 с библиотекой `scikit-image`.
- Composer для управления зависимостями PHP.
- Git для управления версиями.

### Шаги по установке

1. Клонируйте репозиторий:
```bash
git clone https://github.com/Wirpple/converter.git
```

2. Установите зависимости PHP с помощью Composer:
 ```bash
composer install
```
3. Установите необходимые библиотеки Python:
```bash
python3 -m venv venv
source venv/bin/activate
pip install scikit-image
```
