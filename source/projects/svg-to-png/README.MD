# SVG to PNG Converter

This is a simple, one-page web application that allows you to convert Scalable Vector Graphics (SVG) code into a downloadable PNG image. You can either paste your SVG code directly into a text area or provide a URL to an SVG file. The app features a live preview and is built entirely in a single HTML file with no external dependencies.


### Issues
* CORS - feathing svg image via URL doesn't work due to CORS policy.

### Demo
* [SVG to PNG Converter](https://dascent.github.io/4ai/source/projects/svg-to-png/)
* [Wiki / Dicussion](https://wiki.dascent-sigils.com/index.php/Pocket_WebApps/SVG_to_PNG)

---

### Features

* **Live Preview:** See a real-time rendering of your SVG code as you type or after loading it from a URL.
* **Paste SVG Code:** Directly paste your SVG markup into the text area.
* **Load from URL:** Fetch and display an SVG from a given URL.
* **Download as PNG:** Convert the SVG to a PNG and download it to your computer with a single click.

---

### How It Works

The magic of this converter happens in the browser, all thanks to a few powerful Web APIs. When you click the "Convert & Download as PNG" button:

1.  **SVG to Image:** The SVG code is read from the input field. It's then used to create a JavaScript `Blob`, which is then used as the source for an `Image` object. This effectively treats your SVG code as a complete image.
2.  **Image to Canvas:** The app uses a hidden `<canvas>` element. Once the `Image` object has fully loaded the SVG data, it is drawn onto this canvas.
3.  **Canvas to PNG:** The `canvas.toDataURL('image/png')` method is called. This is the key step that takes the content of the canvas and encodes it as a PNG image in a Base64-encoded string.
4.  **Download:** A temporary `<a>` link element is created programmatically. The PNG data URL is set as the link's `href`, and the `download` attribute is set to a filename (e.g., `svg-image.png`). Finally, the link is "clicked" to trigger the download, and then it's removed from the page.

---

### Customization

This single-file application is very easy to modify. Here are a few ways you can customize it:

* **Change the Styles:** All of the CSS is located in the `<style>` tag in the `<head>`. You can easily change colors, fonts, spacing, and layout directly in this section.
* **Change the Default Size:** The default canvas dimensions are set to `500x500` pixels in the JavaScript. You can modify these values in the `downloadButton.addEventListener` function by changing `500`.
* **Change the Filename:** The default filename for the downloaded image is `svg-image.png`. You can change this on line 219 by editing the `link.download` property.

* **Use a Different Image Format:** You can easily change the output format by modifying the `toDataURL()` method. For example, to save as a JPEG, you can change `canvas.toDataURL('image/png')` to `canvas.toDataURL('image/jpeg')`.


