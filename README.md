# AI Image Rename for WordPress

A lightweight, zero-bloat WordPress plugin that uses AI (Google Gemini, OpenAI, or Claude) to automatically analyze your images and generate SEO-optimized filenames and `alt` tags directly from the WordPress Media Library.

## 🚀 Features

* **Context-Aware AI:** Provide a global "Project Scope" in the settings (e.g., "High-end perfume online store") so the AI generates highly relevant SEO tags.
* **1-Click Execution:** No confirmation modals, no extra steps. Click the button, and the image is renamed instantly.
* **Dynamic Model Selection:** Automatically fetches and selects the best lightweight/fast model available (e.g., `gemini-flash-lite-latest`, `gpt-4o-mini`, or `claude-haiku-4-5`) during the API test phase.
* **Supports Multiple Providers:** Works with Google Gemini, OpenAI (ChatGPT), and Claude (Anthropic) APIs.
* **Instant UI Refresh:** Title, alt text, filename, and URL update live in the Media Library (List view, Grid view, and the Attachment Details modal) — no page reload needed.

## 📸 Screenshots

**1. Settings & API Connection**
Configure your AI provider, inject your project scope, and test your connection instantly.
![AI Image Rename Settings](https://guilhemf.com/ai-image-rename/screenshot-1.png)

**2. Before Optimization**
The new AI button sits seamlessly in your native WordPress Media Library.
![Media Library Before](https://guilhemf.com/ai-image-rename/screenshot-2.png)

**3. After Optimization**
In one click, the physical file is renamed and the SEO Alt text is generated based on your custom scope.
![Media Library After](https://guilhemf.com/ai-image-rename/screenshot-3.png)

---

## ⚙️ How It Works (Technical Flow)

### 1. Connection & Model Auto-Detection

When you enter your API key and click "Test API Connection" in the settings, the plugin securely pings the chosen provider (via WP AJAX with CSRF nonce protection). It parses the available models, selects the best fit for fast vision tasks, and stores the endpoint in the WordPress database. If the connection is successful, the AI buttons are injected into the Media Library (both List and Grid views).

### 2. The Renaming Process

When a user clicks the "AI Image Rename" button:

* **Base64 Encoding:** The physical file is encoded to Base64 and sent directly to the AI to bypass public URL requirements (works perfectly on local environments).
* **Strict JSON Parsing:** The AI is prompted to return a raw JSON object containing only a sanitized `title` and an `alt` description.
* **Database & File Update:** The plugin updates the WordPress attachment metadata (`post_title`, `post_name`, `_wp_attachment_image_alt`) and physically renames the base file on the server.
* **Conflict Resolution:** If a file with the new name already exists, the plugin automatically appends a timestamp (`Ymd_His`) to prevent overwriting.
* **Live UI Sync:** The new title, alt text, filename, and file URL are reflected instantly in the Media Library and in the open Attachment Details modal, without requiring a page refresh.

---

## 🧠 Design Choices & Limitations

To keep the codebase simple and the execution lightning-fast, a few deliberate architectural choices were made:

* **No User Confirmation:** The process executes immediately upon clicking. If the result is unsatisfactory, the user can manually edit the media details or run the AI again.
* **No Thumbnail Renaming:** WordPress generates multiple resized thumbnails for every uploaded image. This plugin **only renames the main physical file** and updates the database pointers. Renaming all existing thumbnails would require a heavy search-and-replace in the database and could break existing post content. *It is highly recommended to use this plugin immediately after uploading an image, before inserting it into posts.*

---

## 🛠 Installation & Setup

1. Download or clone this repository into your `/wp-content/plugins/` directory.
2. Activate the **AI Image Rename** plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > AI Image Rename**.
4. Select your AI Provider (Gemini, OpenAI, or Claude) and enter your API Key.
5. Write a short **Project Scope** to guide the AI.
6. Click **Save Settings**, then **Test API Connection**.
7. Go to your Media Library and click the new blue button on any image!

---

## 🤝 Contributing & Future Improvements

This plugin was built with a "Simplicity First" mindset. However, there is plenty of room for contributions! Here are some great starting points for future pull requests:

* **More AI Providers:** Upcoming support for Mistral AI models.
* **Preview Modal:** Add an optional toggle in the settings to show a preview modal allowing the user to edit the AI-generated JSON before applying the physical rename.
* **Bulk Actions:** Add support for WordPress bulk actions to process multiple images sequentially from the List View.
* **Regenerate Thumbnails Hook:** Trigger a native WordPress thumbnail regeneration after the main file is renamed.