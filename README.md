# brain_dump

**brain_dump** is a lightweight PHP-based static site generator. It turns Markdown content with frontmatter into a structured, fast-loading static website. Ideal for blogs, pages, or any content-driven site.

---

## 🚀 Features
- Generates static HTML from Markdown with YAML-style frontmatter
- Supports blog posts, static pages, and paginated blog listings
- Templating system with support for variables and conditional logic
- Generates RSS feeds and sitemaps
- Image processing with thumbnail and WebP conversion
- Only writes updated files to reduce deployment overhead

---

## 📂 Project Structure
```
brain_dump/
├── build.php             # The build script
├── config.yaml           # Configuration settings
├── content/              # Your content.md files (structure defines site paths)
├── src/                  # PHP class files
├── templates/            # HTML templates and partials
├── static/               # Final full site output (excluded from Git)
├── updates/              # Only newly changed or added files (excluded from Git)
├── vendor/               # Composer dependencies (excluded from Git)
├── .gitignore            # Keeps repo clean
└── README.md             # This file
```

---

## ⚙️ Installation

1. Clone the repository:
```bash
git clone git@github.com:Troglobitten/brain_dump.git
cd brain_dump
```

2. Install PHP dependencies:
```bash
composer install
```

3. Make sure PHP is available:
```bash
php -v
```

---

## 🔨 Building the Site
Run the build script:
```bash
php build.php
```

- The full website goes to `static/`
- Changed/new files are in `updates/`

---

## 📤 Deployment
Deploy just the updated files:
```bash
rsync -av updates/ yourserver:/var/www/html/
```

Or deploy the full site:
```bash
rsync -av static/ yourserver:/var/www/html/
```

---

## 🧠 Notes
- Content files must be named `content.md`
- Frontmatter fields: `title`, `date`, `pagetype`, `tags`
- Tags in frontmatter: comma-separated values (`tags: php, linux, two words`)

---

## 🔗 License
MIT License — free to use and modify.

