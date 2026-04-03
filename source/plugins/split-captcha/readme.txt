=== Split CAPTCHA ===
Contributors: dascent
Tags: captcha, security, content locker, aes-gcm, anti-bot, login security, spam protection
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced anti-bot protection using AES-256-GCM encryption and split-character CAPTCHA. Includes Form Guard and Secure Content Gate.

== Description ==

**Split CAPTCHA** is a lightweight yet powerful security hybrid that protects your WordPress site from automated spam and unauthorized content access.

By combining server-side encryption with client-side execution, it ensures that sensitive content remains mathematically locked until a human verifies their presence.

### 🛡️ Why Split CAPTCHA?
Traditional CAPTCHAs are often bypassed by modern AI and OCR (Optical Character Recognition). This plugin introduces several layers of "friction" to stop bots:
* **AES-256-GCM Encryption:** Content inside the shortcode is encrypted on the server. Only an encrypted "blob" reaches the browser, making it invisible to scrapers.
* **DOM Noise Obfuscation:** CAPTCHA characters are injected into the DOM with invisible "noise" tags, preventing simple text-scrapers from reading the unlock code.
* **Honeypot Protection:** Includes transparent fields that catch automated bots instantly.
* **Web Crypto API:** Fast, native browser decryption that doesn't bloat your site's performance.

### 🚀 Key Features
1.  **Form Guard:** Shield your Comments, Login, and Registration forms from bot submissions.
2.  **Content Gate:** Protect premium text, download links, or web applications (like iFrames) using the `[split_captcha]` shortcode.
3.  **IP Lockout:** Automatically tracks failed attempts and blocks malicious IPs for 15 minutes.
4.  **Customizable UI:** Beautiful "Frosted Glass" overlay effect for locked content.

== Installation ==

1. Upload the `split-captcha` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your preferences under **Settings > Split CAPTCHA**.
4. To lock content, use the shortcode: `[split_captcha]Your secret content here[/split_captcha]`.

== Frequently Asked Questions ==

= Is the locked content SEO friendly? =
No. Content inside the `[split_captcha]` shortcode is encrypted. This is intentional to prevent bots and search engines from indexing private or premium data.

= Does it require any external APIs? =
No. Everything is handled locally on your server and the user's browser. No data is sent to third-party services.

= Can I use it on specific posts only? =
Yes. You can enable or disable the Form Guard globally or use the "Split CAPTCHA" meta box on the post/page editor to override global settings.

== Screenshots ==

1. The Content Gate overlay with the frosted glass effect.
2. Form Guard protecting the WordPress comment section.
3. Admin settings panel for global configuration.

== Changelog ==

= 2.2.0 =
* Major Security Update: Added DOM Noise obfuscation to prevent AI OCR scraping.
* Added Honeypot field for invisible bot detection.
* Enhanced AES-256-GCM encryption flow.
* Improved mobile responsiveness for input boxes.

= 2.0.0 =
* Initial stable release featuring Form Guard and Content Gate.

== Upgrade Notice ==

= 2.2.0 =
Highly recommended upgrade. This version introduces advanced anti-AI measures and strengthens the encryption handshake.