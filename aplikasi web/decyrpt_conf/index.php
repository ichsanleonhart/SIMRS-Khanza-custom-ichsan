<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>:)</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">XD</h2>
        <div class="form-group">
            <label for="inputText">Text to Encrypt/Decrypt:</label>
            <input type="text" id="inputText" class="form-control">
        </div>
        <button id="encryptBtn" class="btn btn-primary">Encrypt</button>
        <button id="decryptBtn" class="btn btn-secondary">Decrypt</button>
        <div class="form-group mt-3">
            <label for="outputText">Output:</label>
            <textarea id="outputText" class="form-control" rows="4" ></textarea>
        </div>
    </div>

    <script>
        const key = CryptoJS.enc.Utf8.parse('Bar12345Bar12345');
        const iv = CryptoJS.enc.Utf8.parse('sayangsamakhanza');

        document.getElementById('encryptBtn').addEventListener('click', function() {
            const plaintext = document.getElementById('inputText').value;
            const encrypted = CryptoJS.AES.encrypt(plaintext, key, {
                iv: iv,
                padding: CryptoJS.pad.Pkcs7,
                mode: CryptoJS.mode.CBC
            });
            document.getElementById('outputText').value = encrypted.toString();
        });

        document.getElementById('decryptBtn').addEventListener('click', function() {
            const ciphertext = document.getElementById('inputText').value;
            const decrypted = CryptoJS.AES.decrypt(ciphertext, key, {
                iv: iv,
                padding: CryptoJS.pad.Pkcs7,
                mode: CryptoJS.mode.CBC
            });
            document.getElementById('outputText').value = decrypted.toString(CryptoJS.enc.Utf8);
        });
    </script>
</body>
</html>
