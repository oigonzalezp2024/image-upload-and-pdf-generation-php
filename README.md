# Image Upload and PDF Generation

This project consists of a PHP script designed to securely handle the upload of image files (a logo and a barcode) and use them to generate a customized PDF document. The PDF creation is powered by the **FPDF** library, while image processing and security enhancements are managed with the **GD** library.

-----

## Key Features

  * **Secure File Upload:** Implements stringent controls for image uploads, including strict validation of the file's actual MIME type and a file size limit to prevent Denial-of-Service (DoS) attacks.
  * **Threat Neutralization:** Uploaded images are re-processed by the GD library, which strips out any malicious metadata or embedded code. This sanitizes the file and ensures only the visual content of the image is stored.
  * **Temporary File Management:** Files are saved with unique names to prevent file collisions and overwrites. Once the PDF is generated and downloaded, the temporary files are automatically cleaned up, keeping the system tidy and secure.
  * **Dynamic PDF Generation:** Creates a PDF with a predefined layout, dynamically embedding the logo and a placeholder for a barcode, based on user input.
  * **Error Logging:** Utilizes `error_log` to record any failures during file upload or processing, which is crucial for auditing and debugging.

-----

## System Requirements

To run this script, your web server environment must include:

  * **PHP 7.4+** or newer.
  * The **GD extension** for PHP enabled.
  * The **Fileinfo extension** for PHP enabled.
  * The **FPDF** library (already included in the `fpdf` directory).

-----

## Project Structure

```
.
├── fpdf/             # FPDF library
│   ├── fpdf.php
│   └── ...
├── images/           # Static images directory
│   ├── logo.jpeg
│   └── ...
├── temp_uploads/     # Temporary directory for uploads (must not be publicly accessible)
├── generar.php       # Main PHP script
└── index.html        # User interface form
```

-----

## 🚀 How to Use

1.  Ensure all system requirements are installed and configured.
2.  Place the project files in the root directory of your web server.
3.  Ensure the `temp_uploads` directory has write permissions (we recommend `0755` or more restrictive permissions for enhanced security) so the server can save the uploaded files.
4.  Access the `index.html` file via your browser. The form on this page serves as the interface for uploading files. The form action is configured to send the data to the `generar.php` script for processing.

### Updated HTML Form (index.html)

The user interface form has been optimized for **improved usability and accessibility**. The use of `<label for="...">` and `<input id="...">` explicitly links form labels to their respective input fields, improving usability for all users. The binary `select` input was replaced with a cleaner `checkbox` for a more intuitive user experience.

```html
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ticket Generator</title>
        <link rel="stylesheet" href="./assets/css/style.css">
        <meta name="description" content="Optimize your business with our 5.5 × 9 cm Ticket Generator. Create professional, custom PDF tickets in seconds. Upload your logo and barcode to generate print-ready tickets.">
    </head>

    <body>
        <div class="card">
            <h1>Generate PDF Ticket</h1>
            <form action="generar.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="logo">Main Logo (Top):</label>
                    <input type="file" id="logo" name="logo" accept="image/jpeg, image/png, image/webp" required>
                </div>

                <div class="form-group">
                    <label for="barcode">Barcode (Optional):</label>
                    <input type="file" id="barcode" name="barcode" accept="image/jpeg, image/png, image/webp">
                </div>

                <div class="form-group">
                    <input type="checkbox" id="withBarcode" name="withBarcode" value="1">
                    <label for="withBarcode">Include Barcode</label>
                </div>

                <button type="submit" class="btn">Generate PDF</button>
            </form>
        </div>
    </body>
</html>
```

### 📜 MIT License: Implications

This project is under the **MIT License**, one of the most permissive open-source software licenses. This means you are free to:

* **Use** the software for any purpose, including commercial use.
* **Copy and modify** the source code.
* **Distribute** copies of the software, whether modified or not.

The only condition is that the original copyright notice and the full text of the license must be included in all copies or substantial portions of the software that you distribute.
