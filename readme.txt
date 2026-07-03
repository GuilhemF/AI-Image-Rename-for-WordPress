=== Imaxio SEO Image Renamer and Alt Text ===
Contributors: guilhemf
Tags: seo, images, ai, alt text, filename
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize your image SEO (Filenames and Alt texts) via AI (OpenAI, Google Gemini, or Claude) directly from the WordPress media library in one click.

== Description ==

**Imaxio SEO Image Renamer and Alt Text** is a lightweight, zero-bloat WordPress plugin that uses Artificial Intelligence to automatically analyze your uploaded images and generate highly relevant, SEO-optimized filenames and `alt` tags.

Unlike bloated alternatives, there are **no forced registrations, no monthly subscriptions, and no complicated setups**. You simply bring your own API key, and pay **less than $0.01 per image** directly to the provider for what you actually use. It's just one clean PHP file doing exactly what it promises: adding a simple settings page and a magic button in your Media Library.

Tired of manually thinking about SEO for every single image? Just provide a general "Project Scope" to the AI, and let it do the heavy lifting.

### 🚀 Key Features

* **Bring Your Own Key (BYOK):** Seamlessly connect your own API keys for Google Gemini, OpenAI (ChatGPT), or Claude (Anthropic). No middleman.
* **Context-Aware AI:** Provide a global "Project Scope" in the settings (e.g., "High-end perfume online store") so the AI generates tags and filenames tailored exactly to your niche and target audience.
* **1-Click Execution:** No confirmation modals, no friction. Click the blue button in the media library, and the physical file is renamed instantly.
* **Dynamic Model Selection:** Automatically detects and selects the fastest, and most cost-effective models available (like `gpt-4o-mini`, `gemini-2.5-flash`, or `claude-haiku-4-5`).
* **Safe File Renaming:** Includes a failsafe duplication check. If a file with the generated filename already exists, the plugin appends a timestamp to prevent overwriting.
* **Instant UI Refresh:** The new title, alt text, filename, and file URL update live in the Media Library and Attachment Details modal — no page reload required.

### ⚙️ How it works
The plugin encodes your physical image file into Base64 and sends it securely to the chosen AI provider. The AI is prompted to return a strictly formatted JSON containing a descriptive, hyphen-separated filename and an SEO alt text limited to 100 characters. The plugin then updates the WordPress database and physically renames the base file on your server.

*(Note: To keep the plugin fast and avoid breaking existing content, it only renames the main physical file, not the generated thumbnails. It is highly recommended to use this plugin right after uploading an image, before inserting it into a post).*

== External Services ==

This plugin relies on third-party AI services to analyze images and generate SEO-optimized filenames and alt texts. Images are converted to Base64 and sent to the chosen provider only when the user clicks the "AI Image Rename" button in the Media Library.

= Google Gemini =
Used to analyze images and generate SEO metadata.
Data sent: Base64-encoded image data and a text prompt.
When: Only upon manual user action in the Media Library.
- Terms of Service: https://ai.google.dev/terms
- Privacy Policy: https://policies.google.com/privacy

= OpenAI (ChatGPT) =
Used to analyze images and generate SEO metadata.
Data sent: Base64-encoded image data and a text prompt.
When: Only upon manual user action in the Media Library.
- Terms of Service: https://openai.com/policies/terms-of-use
- Privacy Policy: https://openai.com/policies/privacy-policy

= Claude (Anthropic) =
Used to analyze images and generate SEO metadata.
Data sent: Base64-encoded image data and a text prompt.
When: Only upon manual user action in the Media Library.
- Terms of Service: https://www.anthropic.com/legal/commercial-terms
- Privacy Policy: https://www.anthropic.com/legal/privacy

== Installation ==

1. Upload the `ai-image-rename` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Imaxio Image SEO**.
4. Select your AI Provider (Google Gemini, OpenAI, or Claude) and enter your API Key.
5. Provide a short **Project Scope** to guide the AI.
6. Click **Save Settings**, then **Test API Connection**.
7. Go to your Media Library and click the new "AI Image Rename" button on any image!

== Frequently Asked Questions ==

= Is this plugin free? =
The plugin itself is 100% free and open-source. However, you will need to provide your own API key from OpenAI, Google Gemini, or Claude. These AI providers charge **less than $0.01 per image** processed.

= Why does the Gemini test fail with "Quota exceeded (limit: 0)"? =
If your server is located in the EU, Google's free tier for the Gemini API might be restricted due to local regulations. You simply need to link a billing account in Google AI Studio to use the "Pay-as-you-go" tier (which remains incredibly cheap).

= Does it rename images already inserted in posts? =
The plugin renames the physical file and updates the database. However, if the image is already embedded in a published post, the HTML link inside that post might break. It is strongly advised to rename your images in the Media Library *before* inserting them into your content.

== Screenshots ==

1. The settings page where you configure your AI provider (OpenAI, Gemini, or Claude), API key, and Project Scope, featuring a live API connection test.
2. The WordPress Media Library attachment details view before optimization, showing the original file and the new "AI Image Rename" button.
3. The attachment details view after the AI has successfully generated and applied the SEO-optimized filename and Alternative Text.

== Changelog ==

= 1.0 =
* Initial release.