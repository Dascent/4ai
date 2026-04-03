# Split CAPTCHA 🛡️

**Split CAPTCHA** is a hybrid security plugin for WordPress designed to protect forms from spam and secure premium content using client-side **AES-256-GCM** encryption. 

Unlike traditional CAPTCHAs that rely on simple image recognition, Split CAPTCHA uses a multi-layered approach to baffle both automated scrapers and AI-driven OCR bots.

## 🚀 Key Features

* **Form Guard:** Protects Comments, Login, and Registration forms.
* **Content Gate:** Lock any content (text, iframes, web apps) using a simple shortcode.
* **AES-256-GCM Encryption:** Content is encrypted on the server and decrypted in the visitor's browser only when the correct code is entered.
* **Anti-Bot "Noise" DOM:** Invisible characters are injected into the CAPTCHA display to corrupt data for scrapers while remaining clean for humans.
* **Honeypot Protection:** Hidden fields that instantly disqualify bot submissions.
* **IP Lockout:** Prevents brute-force attacks by tracking failed attempts.

## 🛠 Installation

1.  Download the repository as a `.zip` file.
2.  In your WordPress Dashboard, go to **Plugins > Add New > Upload Plugin**.
3.  Choose the zip file and click **Install Now**.
4.  Activate the plugin.
5.  Configure your settings at **Settings > Split CAPTCHA**.

## 📖 Usage

### Content Locking
To lock content on any page or post, wrap it in the `[split_captcha]` shortcode:

```text
[split_captcha]
   <h3>This is locked content!</h3>
   <p>Only humans who enter the code can see this.</p>
[/split_captcha]
```
## Form Protection
Enable protection for Comments or Login forms globally in the settings page, or override it on a per-post basis using the sidebar meta box.

### 🧪 Technical Details
This plugin stands out by moving the decryption logic to the Web Crypto API.

### The Process: 

 1. Server generates a random code and a unique salt.
 2. Content is encrypted via PHP openssl_encrypt.
 3. Only the encrypted "blob", IV, and Salt are sent to the client.
 4. The user enters the CAPTCHA code.
 5. The browser derives the key and attempts decryption.

Why it's secure: Even if a bot scrapes your page source, they only find encrypted gibberish. Without the CAPTCHA code (which is obfuscated in the DOM), the content is mathematically inaccessible.


📄 License
This project is licensed under the GPL-2.0+ License.
---
Created by [Dascent](https://github.com/Dascent)
