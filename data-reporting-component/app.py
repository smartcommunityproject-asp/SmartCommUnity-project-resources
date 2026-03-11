import streamlit as st
import requests
from bs4 import BeautifulSoup
from PIL import Image
import re

# Chatbase configuration
API_URL = 'https://www.chatbase.co/api/v1/chat'
API_HEADERS = {
    'Authorization': st.secrets["AUTH"],
    'Content-Type': 'application/json'
}
CHATBOT_ID = st.secrets["ID"]


def fetch_text(url: str) -> str:
    """Fetch and return text content from a URL."""
    try:
        resp = requests.get(url, timeout=15)
        resp.raise_for_status()
    except Exception as e:
        return f"[Error fetching {url}: {e}]"

    soup = BeautifulSoup(resp.text, 'html.parser')
    paragraphs = soup.find_all('p')
    texts = [p.get_text(strip=True) for p in paragraphs if p.get_text(strip=True)]
    return "\n\n".join(texts)


def scrape_sections() -> dict:
    """Scrape content from predefined site sections."""
    sections = {
        'Homepage': 'https://smart-alps.eu/',
        'Insights': 'https://smart-alps.eu/insights/',
        'Forums': 'https://smart-alps.eu/forums/',
        'Blog': 'https://smart-alps.eu/blog/'
    }
    return {name: fetch_text(url) for name, url in sections.items()}


def extract_stats(text: str) -> dict:
    """Extract numeric statistics from text content."""
    stats = {}
    patterns = {
        'Active Users': r'([\d,]+)\s+active users',
        'Threads': r'([\d,]+)\s+threads',
        'Posts': r'([\d,]+)\s+posts',
        'Discussions': r'([\d,]+)\s+discussions'
    }
    for key, pat in patterns.items():
        match = re.search(pat, text, re.IGNORECASE)
        if match:
            stats[key] = int(match.group(1).replace(',', ''))
    return stats


def send_to_chatbase(context: str, prompt: str) -> str:
    """Send context and prompt to Chatbase, return the generated report."""
    payload = {
        'messages': [
            {'role': 'system', 'content': 'You are a senior data analyst providing clear and actionable community insights.'},
            {'role': 'user', 'content': context},
            {'role': 'user', 'content': prompt}
        ],
        'chatbotId': CHATBOT_ID,
        'stream': False
    }
    resp = requests.post(API_URL, json=payload, headers=API_HEADERS, timeout=30)
    resp.raise_for_status()
    return resp.json().get('text', '')


def main():
    st.set_page_config(
        page_title="Smart Alps Community Status",
        layout="wide"
    )

    # Display GIF logo
    try:
        logo = Image.open('logo.gif')
        st.image(logo, width=200)
    except Exception:
        pass

    st.title("Smart Alps Community Status Report")
    st.write(
        "This tool helps to generate reports to know the current state of the SmartCommUnity ecosystem, including the main platform as well as the satellite tools."  
    )

    prompt = st.text_area(
        "Customize report focus:",
        value=(
            "Please produce a detailed, text-based analytical report on all the user activity presenting actual numbers and insights. Please be as much complete as possible."
        ),
        height=120
    )

    if st.button("Generate Report"):
        with st.spinner("Scraping site content..."):
            sections = scrape_sections()
            combined_text = "\n\n".join(
                f"### {name}\n{text}" for name, text in sections.items()
            )
            stats = extract_stats(combined_text)

        # Append extracted stats to context if present
        if stats:
            stats_lines = "\n".join(f"{k}: {v:,}" for k, v in stats.items())
            combined_text += "\n\nKey Metrics:\n" + stats_lines

        with st.spinner("Generating report via Chatbase..."):
            report = send_to_chatbase(combined_text, prompt)

        st.subheader("Community Status Report")
        st.markdown(report)
        st.download_button(
            label="Download Report as TXT",
            data=report,
            file_name="community_status_report.txt",
            mime="text/plain"
        )

if __name__ == "__main__":
    main()
