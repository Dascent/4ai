# 🎨 Quote Image Maker

**Quote Image Maker** is a lightweight, responsive, client-side web application that allows you to create beautiful quote images for social media (Facebook posts, covers, etc.).  

It runs entirely in the browser — no server, no database, no external uploads. Everything stays on your device.

<h3>Links</h3>

* [Documentation](https://wiki.dascent-sigils.com/index.php/Pocket_WebApps/Quote_Image_Maker)

* [Online Demo](https://dascent.github.io/4ai/source/projects/quote-it/)


## ✨ Features

- **Canvas presets**  
  - Facebook Post (1080×1080)  
  - Facebook Cover (820×312)

- **Background options**  
  - Built-in gradient and pattern library (Aurora, Sunset, Midnight, Mesh, Grid, etc.)  
  - Upload your own background image (never leaves the browser session)

- **Quote editor**  
  - Type and position text anywhere on the canvas  
  - Align left, center, or right  
  - Choose from multiple **Google Fonts** (Montserrat, Playfair Display, Roboto Slab)  
  - Adjust font size and color  
  - Drag and drop positioning, with **Shift-drag for axis lock**  
  - Text shadow for readability on busy backgrounds

- **Stickers / Icons**  
  - Built-in icon library (heart, glasses, star, sparkles)  
  - Upload custom icons (SVG/PNG)  
  - Drag to reposition  
  - Scale and rotate with sliders  
  - Delete selected icon  
  - Add unlimited icons — easy to extend the library by editing a single array

- **Export**  
  - Generate crisp PNG images at exact pixel sizes  
  - Safe local download (no cloud services involved)  

- **Controls**  
  - Reset app state  
  - Clear text  
  - Delete selected icon  
  - Download final design  

- **Responsive UI**  
  - On desktop: options on the left, preview on the right  
  - On mobile: preview on top, options below  

---

## 🚀 How it works

1. Select a **canvas size** (FB Post or FB Cover).  
2. Pick a **background** from the library or upload your own.  
3. Enter your **quote text** and style it (font, size, color, alignment).  
4. Drag the text box anywhere in the preview.  
   - Hold **Shift** to lock dragging to X or Y axis.  
5. Add **icons**:  
   - Choose from the dropdown library and click *Add icon*.  
   - Or upload your own SVG/PNG sticker.  
   - Click an icon to select it, then use sliders to resize/rotate.  
   - Drag it freely across the canvas.  
   - Press *Delete Selected Icon* to remove it.  
6. When happy with the design, press **Download PNG** to save it locally.  

---

## 🛠️ Example Customizations

- **Classic Quote Card**  
  - Canvas: *Facebook Post*  
  - Background: *Aurora Gradient*  
  - Font: *Playfair Display* at 72px  
  - Text: centered, white color  
  - Sticker: small golden star in bottom corner  

- **Playful Moodboard**  
  - Canvas: *Facebook Cover*  
  - Background: *Custom photo upload*  
  - Font: *Montserrat* bold, 60px  
  - Text: aligned left, light pink color  
  - Stickers: glasses icon near top, heart icon in corner, rotated 20°  

- **Minimalist Design**  
  - Canvas: *Post*  
  - Background: *Subtle Grid* pattern  
  - Font: *Roboto Slab* at 48px  
  - Text: aligned right, dark gray color  
  - No icons  

---

## 🔧 Extending the Icon Library

The app keeps a simple array in the JavaScript:

```js
const ICONS = [
  { id:'heart', name:'Heart', src: svgURI(`<svg ...>...</svg>`) },
  { id:'glasses', name:'Glasses', src: svgURI(`<svg ...>...</svg>`) }
];

```

**Add a new entry with:**

`id: unique identifier`

`name: label shown in dropdown`

`src: inline SVG or Base64-encoded PNG/WebP`

**Example:**

`{ id:'leaf', name:'Leaf', src: svgURI(`<svg xmlns='http://www.w3.org/2000/svg' ...></svg>`) }`



📱 Tech Stack

Pure HTML5, CSS3, and JavaScript

No build tools, no frameworks

Uses Canvas API for rendering

Google Fonts integration

Entirely client-side (safe, private, offline-friendly)

📸 Screenshots

| Quote | WebApp|
|----------|----------|
| [![Banner 1](https://raw.githubusercontent.com/Dascent/4ai/refs/heads/main/source/projects/quote-it/img/sample.png)](https://wiki.dascent-sigils.com/index.php/Pocket_WebApps/Quote_Image_Maker) <br/> *Classic Quote Card with gradient background and golden star* | [![Banner 2](https://raw.githubusercontent.com/Dascent/4ai/refs/heads/main/source/projects/quote-it/img/screenshot-webapp.png)](https://dascent.github.io/4ai/source/projects/quote-it/) <br/> *WebApp Screenshot* |



📄 License

MIT License — feel free to use, modify, and share. Contributions welcome!







