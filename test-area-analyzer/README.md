# 🌍 Test Area Analyzer

Repository for the software concerning Test Area Analysis in the frame of the SmartCommUnity project.


## 🚀 Overview

`tanalyzer` is a software tool designed to analyze test areas using OpenStreetMap (OSM) data. It helps assess the availability of amenities and smart entities in a given location, providing insights into digitalization, smartness, and rural development. The tool integrates data visualization through interactive maps and leverages AI-powered analysis to generate recommendations.

## 🛠 Features

- Fetch and visualize amenities around a given latitude/longitude.
- Count and categorize different types of amenities.
- Retrieve smart entities based on custom OSM tags.
- Add markers to an interactive map using Folium.
- Construct AI-driven insights based on selected entities.
- Provide an interactive interface using Streamlit.

## 📦 Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-repository/tanalyzer.git
   cd tanalyzer
   ```
2. Install the required dependencies:
   ```bash
   pip install -r requirements.txt
   ```
3. Run the application:
   ```bash
   streamlit run app.py
   ```

## 📝 Usage

1. Open the web application and select a test area.
2. Choose a category of interest (e.g., Smart Economy, Smart Mobility).
3. Retrieve and visualize amenities or entities on the map.
4. Use the AI Assistant to analyze and generate insights.
5. Download the analysis results as a text file.

## 🖥️ Technology Stack

- **Python** (Backend processing)
- **Streamlit** (UI framework)
- **OSMnx** (Fetching geospatial data from OpenStreetMap)
- **Folium** (Mapping and visualization)
- **Pandas** (Data handling)
- **Requests** (API interactions for AI analysis)

## 📚 Reference

For further reading, refer to our recent publications:

```
@article{martinez2025overview,
  title={An overview of civic engagement tools for rural communities},
  author={Martinez-Gil, Jorge and Pichler, Mario and Lechat, Noemi and Lentini, Gianluca and Cvar, Nina and Trilar, Jure and Bucchiarone, Antonio and Marconi, Annapaola},
  journal={Open Research Europe},
  volume={4},
  number={195},
  pages={195},
  year={2025},
  publisher={F1000 Research Limited}
}
```

## 📄 License

This project is licensed under the MIT License.
