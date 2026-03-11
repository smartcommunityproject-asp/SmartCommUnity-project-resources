# SmartCommUnity - Data Reporting Component 🏔️📊

[![Streamlit](https://img.shields.io/badge/Streamlit-1.x-FF4B4B?logo=streamlit\&logoColor=white)](#)
[![Python](https://img.shields.io/badge/Python-3.10%2B-3776AB?logo=python\&logoColor=white)](#)
[![BeautifulSoup](https://img.shields.io/badge/BeautifulSoup-🍜-2b8a3e)](#)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](#license)

Generate clear, data-rich status reports for the **SmartCommUnity / Smart-Alps** ecosystem.
The app scrapes public pages, extracts basic activity stats, and asks **Chatbase** to produce an analyst-style write-up you can read online or download.

---

## ✨ Features

* **One-click report**: scrape + analyze + render in a single action.
* **Multi-section input**: Homepage, Insights, Forums, Blog.
* **Heuristic stats**: pulls numbers like *active users*, *threads*, *posts*, *discussions* from page text.
* **Analyst voice**: Chatbase prompt tuned for crisp, actionable output.
* **Export**: download the report as a `.txt`.
* **Branding**: optional animated logo (`logo.gif`) at the top.

---

## 🚀 Quick start

### 1) Clone and install

```bash
git clone https://github.com/your-org/smart-alps-community-status.git
cd smart-alps-community-status
python -m venv .venv
source .venv/bin/activate   # Windows: .venv\Scripts\activate
pip install -r requirements.txt
```

**`requirements.txt`**

```txt
streamlit>=1.36
requests>=2.31
beautifulsoup4>=4.12
pillow>=10.0
```

### 2) Add secrets

Create `.streamlit/secrets.toml`:

```toml
AUTH = "Bearer YOUR_CHATBASE_API_KEY"
ID = "YOUR_CHATBASE_CHATBOT_ID"
```

> The app reads `st.secrets["AUTH"]` and `st.secrets["ID"]`.

### 3) Run

```bash
streamlit run app.py
```

Open the local URL shown in the terminal.

---

## ⚙️ Configuration

* **Scraped sections** (inside `scrape_sections()`):

  ```python
  sections = {
      "Homepage": "https://smart-alps.eu/",
      "Insights": "https://smart-alps.eu/insights/",
      "Forums":   "https://smart-alps.eu/forums/",
      "Blog":     "https://smart-alps.eu/blog/"
  }
  ```

  Add, rename, or remove entries to shape the context.

* **Stats patterns** (inside `extract_stats()`):

  ```python
  patterns = {
      "Active Users": r"([\d,]+)\s+active users",
      "Threads":      r"([\d,]+)\s+threads",
      "Posts":        r"([\d,]+)\s+posts",
      "Discussions":  r"([\d,]+)\s+discussions"
  }
  ```

  Extend with new terms or translations as needed.

* **Prompt**: the text area in the UI accepts any custom instruction set. Keep it focused on the analysis you want.

---

## 🖱️ Usage

1. Optional: ensure `logo.gif` exists in the project folder.
2. Type or edit the **“Customize report focus”** prompt.
3. Click **Generate Report**.
4. Read the **Community Status Report**.
5. Click **Download Report as TXT** to save.

> The scraper reads public HTML and concatenates paragraph text. Binary assets, scripts, and hidden content are ignored.

---

## 📄 Sample output (trimmed)

```
Community Overview
- Activity across Homepage, Insights, Forums, Blog indicates steady engagement.
Key Numbers
- Active Users: 1,240
- Threads: 380
- Posts: 4,920
Observations
- Forums: growth in threads around partner tooling
- Blog: cadence improves visibility; top themes: funding calls, events
Next Actions
- Publish monthly digest; track post-to-thread ratio; tag forum Q&A
```

*(Numbers shown here are placeholders; actual values come from the page text.)*

---

## 🛡️ Security & privacy

* Uses **read-only** HTTP GET against public pages.
* Honors server timeouts (15s) and raises exceptions on failure.
* Avoid scraping pages that disallow crawlers; check `robots.txt` and site terms.
* Secrets stay in **Streamlit secrets** (not committed to Git).

---

## 🧪 Troubleshooting

* **Empty report or low detail**

  * Check that the target pages contain real paragraph text (`<p>`).
  * Tweak the prompt to request structure (sections, bullets, KPIs).

* **Stats not found**

  * Add new regex patterns matching the site’s wording.
  * Confirm that numbers aren’t inside images or JavaScript-rendered widgets.

* **Chatbase errors**

  * Verify `AUTH` and `ID` in `.streamlit/secrets.toml`.
  * Check API quota and status.

* **Image not showing**

  * Confirm `logo.gif` exists and is readable.

---

## 🧩 Project layout

```
.
├── app.py
├── logo.gif                  # optional
├── requirements.txt
└── .streamlit/
    └── secrets.toml          # AUTH, ID
```

---

## 🗺️ Roadmap

* Optional CSV export with page-level stats
* Caching layer for faster repeat runs
* Pluggable scrapers (Sitemap/XML, RSS)
* Chart blocks for trend snapshots
* Multi-language regex patterns

---

## 🤝 Contributing

* Open an issue with a short description and a minimal test case.
* Use feature branches and clear commit messages.
* Run basic checks: formatting, imports, and a quick manual run.

---

## 📜 License

MIT — see `LICENSE` for details.

---

## 🙌 Acknowledgements

* Streamlit team and community
* BeautifulSoup maintainers
* Chatbase API
