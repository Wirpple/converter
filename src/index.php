<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSIM Calculator</title>
</head>
<body>
<h1>Calculate SSIM Between Multiple Pairs of Images</h1>
<form id="ssimForm" enctype="multipart/form-data">
    <div id="imagePairs">
        <div class="imagePair">
            <label for="image1">Image 1:</label>
            <input type="file" name="images[]" required><br><br>
            <label for="image2">Image 2:</label>
            <input type="file" name="images[]" required><br><br>
        </div>
    </div>
    <button type="button" onclick="addImagePair()">Add Another Pair</button>
    <br><br>
    <input type="submit" value="Calculate SSIM">
</form>
<div id="result"></div>

<script>
    function addImagePair() {
        const imagePairDiv = document.createElement('div');
        imagePairDiv.classList.add('imagePair');
        imagePairDiv.innerHTML = `
                <label>Image 1:</label>
                <input type="file" name="images[]" required><br><br>
                <label>Image 2:</label>
                <input type="file" name="images[]" required><br><br>
            `;
        document.getElementById('imagePairs').appendChild(imagePairDiv);
    }

    document.getElementById('ssimForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(document.getElementById('ssimForm'));

        fetch('calculate_ssim.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                const resultDiv = document.getElementById('result');
                resultDiv.innerHTML = '';
                result.forEach((ssim, index) => {
                    resultDiv.innerHTML += `Pair ${index + 1}: ${ssim}<br>`;
                });
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });
</script>
</body>
</html>
