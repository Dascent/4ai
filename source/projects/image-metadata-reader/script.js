document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('drop-area');
    const fileElem = document.getElementById('fileElem');
    const imageUrlInput = document.getElementById('image-url');
    const loadUrlBtn = document.getElementById('load-url-btn');
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const metadataOutput = document.getElementById('metadata-output');
    const metadataTableBody = document.getElementById('metadata-table-body');
    const errorMessageDiv = document.getElementById('error-message');

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
    });

    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);
    fileElem.addEventListener('change', handleFileSelect, false);
    loadUrlBtn.addEventListener('click', handleUrlLoad);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    function handleFileSelect(e) {
        const files = e.target.files;
        handleFiles(files);
    }

    async function handleFiles(files) {
        if (files.length === 0) return;

        const file = files[0];
        if (!file.type.startsWith('image/')) {
            showError('Please select an image file.');
            return;
        }

        try {
            const fileUrl = URL.createObjectURL(file);
            await processImage(fileUrl, file);
        } catch (error) {
            showError('An error occurred while processing the file: ' + error.message);
        }
    }

    async function handleUrlLoad() {
        const url = imageUrlInput.value.trim();
        if (!url) {
            showError('Please enter a valid image URL.');
            return;
        }

        try {
            await processImage(url);
        } catch (error) {
            showError('Failed to load image from URL. This might be due to a Cross-Origin Resource Sharing (CORS) policy. ' + error.message);
        }
    }

    async function processImage(src, file = null) {
        hideError();
        clearOutput();

        // Display the image
        imagePreview.src = src;
        imagePreview.onload = () => {
            imagePreviewContainer.classList.remove('hidden');
            metadataOutput.classList.remove('hidden');
            readMetadata(imagePreview, file);
        };
    }

    function readMetadata(imgElement, file) {
        // Clear previous table data
        metadataTableBody.innerHTML = '';

        EXIF.getData(imgElement, function() {
            const exifTags = EXIF.getAllTags(this);

            const metadata = {
                // General Information
                'Name': file ? file.name : 'N/A',
                'File Size': file ? `${(file.size / 1024).toFixed(2)} KB` : 'N/A',
                'File Type': file ? file.type.split('/')[1] : 'N/A',
                'MIME Type': file ? file.type : 'N/A',
                'Image Size': `${imgElement.naturalWidth} x ${imgElement.naturalHeight} pixels`,
                // Copyright
                'By-line': exifTags.Artist || 'N/A',
                'Copyright Notice': exifTags.Copyright || 'N/A',
                // Location
                'Latitude': exifTags.GPSLatitude ? formatGPS(exifTags.GPSLatitudeRef, exifTags.GPSLatitude) : 'N/A',
                'Longitude': exifTags.GPSLongitude ? formatGPS(exifTags.GPSLongitudeRef, exifTags.GPSLongitude) : 'N/A'
            };

            // Populate the table
            for (const key in metadata) {
                if (metadata.hasOwnProperty(key)) {
                    const row = metadataTableBody.insertRow();
                    const cell1 = row.insertCell(0);
                    const cell2 = row.insertCell(1);
                    cell1.textContent = key;
                    cell2.textContent = metadata[key];
                }
            }

            // Optional: Add other available EXIF data
            const otherExifData = {
                'Make': exifTags.Make,
                'Model': exifTags.Model,
                'DateTime Original': exifTags.DateTimeOriginal,
                'Exposure Time': exifTags.ExposureTime,
                'F-Number': exifTags.FNumber,
                'ISO Speed Ratings': exifTags.ISOSpeedRatings,
                'Focal Length': exifTags.FocalLength,
            };

            for (const key in otherExifData) {
                if (otherExifData[key]) {
                    const row = metadataTableBody.insertRow();
                    const cell1 = row.insertCell(0);
                    const cell2 = row.insertCell(1);
                    cell1.textContent = key.replace(/([A-Z])/g, ' $1'); // Add spaces for readability
                    cell2.textContent = otherExifData[key];
                }
            }
        });
    }

    function formatGPS(ref, data) {
        // Simple formatting for GPS data
        if (!data) return 'N/A';
        const [d, m, s] = data;
        return `${d}° ${m}' ${s.toFixed(2)}" ${ref}`;
    }

    function clearOutput() {
        imagePreviewContainer.classList.add('hidden');
        metadataOutput.classList.add('hidden');
        imagePreview.src = '';
        metadataTableBody.innerHTML = '';
    }

    function showError(message) {
        errorMessageDiv.textContent = message;
        errorMessageDiv.classList.remove('hidden');
    }

    function hideError() {
        errorMessageDiv.classList.add('hidden');
        errorMessageDiv.textContent = '';
    }
});