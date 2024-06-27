import sys
from skimage import io, img_as_float
from skimage.metrics import structural_similarity as ssim
from skimage.transform import resize

def calculate_ssim(image1_path, image2_path):
    image1 = img_as_float(io.imread(image1_path))
    image2 = img_as_float(io.imread(image2_path))

    # Проверка размеров изображений
    if image1.shape != image2.shape:
        # Изменение размера второго изображения до размера первого
        image2 = resize(image2, image1.shape, anti_aliasing=True)

    # Задание размера окна, оси цветового канала и диапазона данных
    ssim_index, _ = ssim(image1, image2, full=True, multichannel=True, win_size=7, channel_axis=-1, data_range=image1.max() - image1.min())
    return ssim_index

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python ssim_calculator.py <image1.png> <image2.png>")
        sys.exit(1)

    image1_path = sys.argv[1]
    image2_path = sys.argv[2]

    ssim_index = calculate_ssim(image1_path, image2_path)
    print(f"{ssim_index}")
