document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('drop-area');
    const fileElem = document.getElementById('fileElem');
    const imageUrlInput = document.getElementById('image-url');
    const loadUrlBtn = document.getElementById('load-url-btn');
    const resultContainer = document.getElementById('result-container');
    const imagePreview = document.getElementById('image-preview');
    const fileNameSpan = document.getElementById('file-name');
    const removeMetadataBtn = document.getElementById('remove-metadata-btn');
    const imageMetadataTableBody = document.querySelector('#image-metadata-table tbody');
    const copyrightMetadataTableBody = document.querySelector('#copyright-metadata-table tbody');
    const fullMetadataTableBody = document.querySelector('#full-metadata-table tbody');
    const locationText = document.getElementById('location-text');
    const errorMessageDiv = document.getElementById('error-message');

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

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
    removeMetadataBtn.addEventListener('click', removeImageMetadata);

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
        resultContainer.classList.remove('hidden');

        // Display the image
        imagePreview.src = src;
        imagePreview.onload = () => {
            readMetadata(imagePreview, file);
            if (file) {
                fileNameSpan.textContent = file.name;
            } else {
                fileNameSpan.textContent = src.split('/').pop();
            }
        };
    }

    function readMetadata(imgElement, file) {
        // Clear previous table data
        imageMetadataTableBody.innerHTML = '';
        copyrightMetadataTableBody.innerHTML = '';
        fullMetadataTableBody.innerHTML = '';

        EXIF.getData(imgElement, function() {
            const exifTags = EXIF.getAllTags(this);

            const fileSizeBytes = file ? file.size : 'N/A';
            const fileSizeFormatted = file ? `${(file.size / 1024).toFixed(2)} KB (${file.size} bytes)` : 'N/A';

            // Image Metadata
            const imageMetadata = {
                'Name': file ? file.name : 'N/A',
                'File size': fileSizeFormatted,
                'File type': file ? file.type.split('/')[1] : 'N/A',
                'MIME type': file ? file.type : 'N/A',
                'Image size': `${imgElement.naturalWidth} x ${imgElement.naturalHeight} px`,
            };
            populateTable(imageMetadataTableBody, imageMetadata);

            // Copyright Metadata
            const copyrightMetadata = {
                'By-line': exifTags.Artist || 'N/A',
                'Copyright notice': exifTags.Copyright || 'N/A',
            };
            populateTable(copyrightMetadataTableBody, copyrightMetadata);

            // Location
            const gpsLatitude = exifTags.GPSLatitude ? formatGPS(exifTags.GPSLatitudeRef, exifTags.GPSLatitude) : null;
            const gpsLongitude = exifTags.GPSLongitude ? formatGPS(exifTags.GPSLongitudeRef, exifTags.GPSLongitude) : null;
            if (gpsLatitude && gpsLongitude) {
                locationText.textContent = `Latitude: ${gpsLatitude}, Longitude: ${gpsLongitude}`;
            } else {
                locationText.textContent = `We can't find where it was taken.`;
            }

            // Full Metadata
            if (Object.keys(exifTags).length > 0) {
                 for (const key in exifTags) {
                    if (exifTags.hasOwnProperty(key)) {
                        addRowToTable(fullMetadataTableBody, key.replace(/([A-Z])/g, ' $1'), exifTags[key]);
                    }
                }
            } else {
                addRowToTable(fullMetadataTableBody, 'Metadata', 'No EXIF data found.');
            }
        });
    }

    function populateTable(tableBody, data) {
        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                addRowToTable(tableBody, key, data[key]);
            }
        }
    }

    function addRowToTable(tableBody, label, value) {
        const row = tableBody.insertRow();
        const cell1 = row.insertCell(0);
        const cell2 = row.insertCell(1);
        cell1.textContent = label;
        cell2.textContent = value || 'N/A';
    }

    function formatGPS(ref, data) {
        if (!data) return 'N/A';
        const [d, m, s] = data;
        return `${d}° ${m}' ${s.toFixed(2)}" ${ref}`;
    }

    function removeImageMetadata() {
        // This is a placeholder function. In a real-world scenario,
        // you would need a server-side solution to strip EXIF data from the image.
        // For a client-side solution, you could create a new canvas, draw the image on it,
        // and export it without the metadata, but it's a more complex task.
        showError("Note: Removing metadata would require server-side processing or a more complex client-side library to create a new image without the metadata.");
    }

    function clearOutput() {
        resultContainer.classList.add('hidden');
        imagePreview.src = '';
        fileNameSpan.textContent = '';
        imageMetadataTableBody.innerHTML = '';
        copyrightMetadataTableBody.innerHTML = '';
        fullMetadataTableBody.innerHTML = '';
        locationText.textContent = '';
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