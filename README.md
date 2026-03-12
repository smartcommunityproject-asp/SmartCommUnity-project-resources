# SmartCommUnity Project Resources

Welcome to the technical resources repository for the **Interreg Alpine Space SmartCommUnity project (2022-2025)**. 

The SmartCommUnity project aims to support Alpine rural areas by using the benefits of digitalization, embracing smart transitions, and creating a functional transnational community within the EUSALP AG5 Smart Alps network. 

This repository contains the source code for the interactive digital tools, dashboards, and analytical components developed to foster the "smart transition" of Alpine areas. The tools consist of **WordPress Plugins** for community platform integration and **Standalone Python Applications** (prepared for Streamlit) for data analysis and assessment.

## 📂 Repository Structure & Components

Below is the list of technical folders in this repository, along with a brief description of their architecture and identifiable features:

### 1. `assessment-remastered`[Smartness Assessment - SmartVillages]
* **Type:** Standalone Python application (prepared for Streamlit)
* **Description:** This tool powers the "Smartness Assessment" interactive guide. It allows Lighthouse and Follower test areas to evaluate their communities across the six core dimensions of smartness (Smart Living, Smart Governance, Smart Mobility, Smart Economy, Smart Environment, and Smart People). 

### 2. `ccd-test`[Comprehensive Community Dashboard]
* **Type:** WordPress Plugin
* **Description:** A testing and staging component for the Comprehensive Community Dashboard (CCD). It contains test scripts, environmental checks, and validation logic for dashboard shortcodes (like `[ccd_dashboard]`) before public deployment.

### 3. `community-dashboard` [Community Analytics]
* **Type:** WordPress Plugin
* **Description:** A Data Analytics Component designed for Community Managers. It offers an all-in-one panel to monitor online discussions and content-driven communities. 
* **Identifiable Features:** Includes chart generation (via QuickChart.io integration), user sign-up tracking, and CSV data export capabilities for offline audits.

### 4. `comprehensive-community-dashboard` [Community Analytics extended]
* **Type:** WordPress Plugin
* **Description:** The full, modular version of the DAC plugin. It integrates multiple facets of community monitoring into the WordPress backend and frontend. 
* **Identifiable Features:** Tracks gamification metrics (rankings based on user activity), server environment details, WordPress database overviews, and content performance analytics. It heavily utilizes PHP, JS, and CSS.

### 5. `data-community` [User Data Comparison Plugin]
* **Type:** Standalone Python application / Integration Scripts
* **Description:** Handles data aggregation and processing for the Alpine Community network. It facilitates the collection of needs, requirements, and profile configurations for the 18 test areas connected to the project while measuring environmental, social and economiv impacts against benchmarks. Uses time-point based data.

### 6. `data-reporting-component` [Data Reporting Component]
* **Type:** Standalone Python application (prepared for Streamlit)
* **Description:** A smart transition reporting tool. It processes periodically collected data and presents visual progress reports for regional stakeholders, helping them understand what is happening within their community demographics.

### 7. `forum-sentiment-analysis` [Forum Sentiment]
* **Type:** Standalone Python application (prepared for Streamlit / NLP Backend)
* **Description:** An automated NLP (Natural Language Processing) analysis tool. It parses recent forum topics and discussions to assign sentiment scores.
* **Identifiable Features:** Acts as an early signal and notification system for moderators to detect changes in participation or flag discussions requiring follow-up.

### 8. `good_practices_map` [Display Good practices on Maps - SmartVillages]
* **Type:** WordPress Plugin
* **Description:** A visual mapping component that powers the "Good Practices Catalogue". 
* **Identifiable Features:** Integrates map libraries to dynamically display successful smart transition actions, "pop-up services", and local use cases across the participating Alpine countries (Austria, France, Germany, Italy, Slovenia, and Switzerland).

### 9. `test-area-analyzer` [Test Area Analyser]
* **Type:** Standalone Python application (prepared for Streamlit)
* **Description:** Also known internally as *tanalyzer*. A data-driven application that maps out amenities and smartness dimensions within each specific test area.
* **Identifiable Features:** Ingests and visually maps open-source datasets (such as OpenStreetMap data) to highlight coverage, gaps, and geographical capabilities of local rural networks.

---

## 💻 Tech Stack Overview
* **WordPress Ecosystem (70%+):** The dashboard and mapping components are built using PHP, JavaScript, and CSS, functioning as modular WP plugins ready to be dropped into `wp-content/plugins/`.
* **Data & Analytics (13%+):** The analytical tools, sentiment analyzers, and assessment calculators are built entirely in Python, making them perfect for deployment as interactive Streamlit web apps.

## 🔗 More Information
* **Official Platform:** [Smart Alps Network](https://smart-alps.eu)
* **Project Info:** [Alpine Space Programme - SmartCommUnity](https://www.alpine-space.eu/projects/smartcommunity/)
