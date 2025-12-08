# 🛡️ DiffDefender Bundle — Local AI Code Reviewer for Symfony 🐘

DiffDefender is a **privacy‑first**, **local‑only** AI code review bundle built specifically for **Symfony** developers. It runs entirely on your machine using **Ollama**, ensuring that **your proprietary code never leaves your environment**.

It analyzes your **staged Git changes**, injects relevant Symfony context (security configs, routes, migrations, etc.), and uses a specialized AI model to detect:
- Security issues
- Bad practices
- Missing migrations
- Architectural mistakes
- Forgotten debug functions

---

## ✨ Key Features

### 🔒 100% Private & Free
Runs on **Ollama locally**. No API keys, no subscriptions, no cloud.

### 🧠 Context‑Aware Engine
Understands Symfony internals—including routes, security rules, Doctrine entities, and migrations.

### 🕵️ Schema Watchdog
Detects if you modified a Doctrine Entity without generating a Migration.

### 🚫 Clean Code Enforcer
Flags `dump()`, `dd()`, `var_dump()`, and other debugging leftovers.

### 🎨 Developer‑Friendly CLI
Readable terminal output with line numbers and clear fix suggestions.

---

## ⚙️ Prerequisites

- **PHP 8.1+**
- **Symfony 6.0 / 7.0 / 8.0+**
- **Ollama installed and running**

---

## 🦙 Setting up Ollama & Networking

### 1. Pull a Coding Model
```bash
ollama pull deepseek-coder
```

### 2. Start Ollama Server (Accessible from Docker)
```bash
OLLAMA_HOST=0.0.0.0 ollama serve
```

---

## 📦 Installation (3 Steps)

### **Step 1: Install the Bundle**
```bash
composer require busanstu/diff-defender-bundle:dev-master --dev
```

### **Step 2: Configure the API Endpoint**
Add to `.env` or `.env.local`:

| Environment | Variable | Value |
|------------|----------|-------|
| Mac/Windows (Docker) | `OLLAMA_API_URL` | `http://host.docker.internal:11434/api/chat` |
| Linux (Docker) | `OLLAMA_API_URL` | `http://172.17.0.1:11434/api/chat` |
| Host machine (no Docker) | `OLLAMA_API_URL` | `http://localhost:11434/api/chat` |
| Any | `OLLAMA_MODEL` | `deepseek-coder` |

### **Step 3: Verify Installation**
```bash
php bin/console list diff
```
Expected output:
```
diff:review
```

---

## 🚀 Usage
DiffDefender analyzes **only staged changes**.

### 1. Stage Changes
```bash
git add .
```

### 2. Run Review
```bash
php bin/console diff:review
```

---

## 📌 Example Output
If the staged code contains:
```php
dump($data);
```

You may see:
```
🛡️ DiffDefender: Local AI Code Review
===================================

b/src/Controller/ExampleController.php (+1 / -0)
-----------------------------------------------

   🔗 Context Injected: Security & Routing rules applied.

--- Analyzed Code Snippet ---
 15 |        dump($data);
-----------------------------

[CRITICAL] CLEANLINESS (NO DEBUGGING)
 📍 Line: 15
 💡 Fix: Remove this debugging function before committing.

Issues detected. Please review before pushing.
```

---

## 👨‍💻 Development & Contributing

### 🧩 Architecture Overview
| Component | Responsibility |
|----------|----------------|
| **GitWrapper** | Extracts staged changes (`git diff --cached`) |
| **DiffParser** | Converts raw diff text into structured DTOs |
| **ContextProvider** | Reads config/migration files and enriches prompts |
| **OllamaClient** | Sends prompts to local Ollama & enforces strict JSON output |
| **ReviewCommand** | Orchestrates the entire review process |

### 🔧 Publishing Updates
1. Commit your changes:
```bash
git commit -m "..."
```
2. Push to GitHub:
```bash
git push
```
3. Create a **tag** on GitHub (e.g., `v1.0.1`).

Users can update via:
```bash
composer update busanstu/diff-defender-bundle
```

---

## 📝 License
This project is licensed under the **MIT License**.
See the `LICENSE` file for details.
