import streamlit as st
import osmnx as ox
import folium
import requests
import json
import pandas as pd
from streamlit_folium import folium_static
import textwrap
import math
from folium.plugins import MarkerCluster

# ---------------------------
# Hidden Settings (Constants)
# ---------------------------
RADIUS_DEFAULT = 1000
DEFAULT_COORDINATES = (48.36964, 14.5128)
MAP_WIDTH = 700
MAP_HEIGHT = 500
API_URL = 'https://www.chatbase.co/api/v1/chat'
API_HEADERS = {
    'Authorization': 'Bearer d1a408c0-5e75-40ca-99e5-424e830d26ed',
    'Content-Type': 'application/json'
}
CHATBOT_ID = 'X5mqGdkfYYzpPO2R7Q5Jv'

# Example coordinates for test areas (full dictionary as provided)
example_coordinates = {
    "Arpavon, France": (44.370914993, 5.27351034083),
    "Aubres, France": (44.3893044332, 5.1543771035),
    "Aulan, France": (44.2300209805, 5.42219540267),
    "Ballons, France": (44.2517233325, 5.64403556412),
    "Barret-de-Lioure, France": (44.1754211867, 5.51622125908),
    "Beauvoisin, France": (44.3007007366, 5.2126241299),
    "Bellecombe-Tarendol, France": (44.3570805263, 5.35605853142),
    "Benivay-Ollon, France": (44.3110239354, 5.18792865009),
    "Bésignan, France": (44.320135403, 5.32102321882),
    "Buis-les-Baronnies, France": (44.276084918, 5.27102720538),
    "Châteauneuf-de-Bordette, France": (44.3367876244, 5.17129087386),
    "Chaudebonne, France": (44.4749699502, 5.23878850401),
    "Chauvac-Laux-Montaux, France": (44.3222598989, 5.53474260728),
    "Condorcet, France": (44.4150216526, 5.17856915513),
    "Cornillac, France": (44.4397962744, 5.40427048802),
    "Cornillon-sur-l’Oule, France": (44.4553268167, 5.36026006175),
    "Curnier, France": (44.387658631, 5.23421106174),
    "Eygalayes, France": (44.2211440236, 5.59423968429),
    "Eygaliers, France": (44.2403379768, 5.28466764247),
    "Eyroles, France": (44.417094329, 5.24106129822),
    "Izon-la-Bruisse, France": (44.2626520875, 5.60226769891),
    "La Charce, France": (44.4668723535, 5.44466918876),
    "La Penne-sur-l’Ouvèze, France": (44.2503681815, 5.23252395755),
    "La Roche-sur-le-Buis, France": (44.2625812469, 5.34101183886),
    "La Rochette-du-Buis, France": (44.2610706065, 5.42166701119),
    "Le Poët-en-Percip, France": (44.2484174261, 5.39418333523),
    "Le Poët-Sigillat, France": (44.3631938216, 5.31860156663),
    "Lemps, France": (45.0972922386, 4.778993534),
    "Les Pilles, France": (44.3778726864, 5.19915828289),
    "Mérindol-les-Oliviers, France": (44.2725055479, 5.1684408091),
    "Mévouillon, France": (44.2346171686, 5.47885128912),
    "Mirabel-aux-Baronnies, France": (44.3146283758, 5.10177171848),
    "Montauban-sur-l’Ouvèze, France": (44.2773478714, 5.51851244253),
    "Montaulieu, France": (44.3578984951, 5.21799658575),
    "Montbrun-les-Bains, France": (44.1877148289, 5.43426917221),
    "Montferrand-la-Fare, France": (44.3419027323, 5.44998013402),
    "Montguers, France": (44.301845332, 5.47245831893),
    "Montréal-les-Sources, France": (44.4006997235, 5.30675272365),
    "Nyons, France": (44.3564879814, 5.12880832),
    "Pelonne, France": (44.3822334481, 5.38062257159),
    "Piegon, France": (44.298616176, 5.12425949306),
    "Pierrelongue, France": (44.2495510618, 5.21417355869),
    "Plaisians, France": (44.2251384838, 5.337199685),
    "Pommerol, France": (44.4372174843, 5.46497895783),
    "Propiac, France": (44.276584707, 5.20940872423),
    "Reilhanette, France": (44.1712233115, 5.40493973585),
    "Remuzat, France": (44.4038075452, 5.35604206561),
    "Rioms, France": (44.4038075452, 5.35604206561),
    "Rochebrune, France": (44.3264290154, 5.23698675047),
    "Roussieux, France": (44.3315645396, 5.47886581658),
    "Sahune, France": (44.4062765625, 5.27186320127),
    "Saint-Auban-sur-l’Ouvèze, France": (44.2973942019, 5.43012241748),
    "Saint-Ferréol-Trente-Pas, France": (44.4418058243, 5.22377937288),
    "Saint-Maurice-sur-Eygues, France": (44.3029325431, 5.00596735706),
    "Saint-May, France": (44.4246891734, 5.32934515143),
    "Saint-Sauveur-Gouvernet, France": (44.3303880901, 5.37614176075),
    "Sainte-Euphémie-sur-Ouvèze, France": (44.3011387229, 5.3913641468),
    "Sainte-Jalle, France": (44.3334508898, 5.27890908641),
    "Sederon, France": (44.1975046499, 5.55416093649),
    "Valouse, France": (44.4594883519, 5.19108928218),
    "Venterol, France": (44.3977805769, 5.09846513358),
    "Verclause, France": (44.3954497171, 5.40735314381),
    "Vercoiran, France": (44.2937794965, 5.35057643289),
    "Vers-sur-Méouge, France": (44.2358606593, 5.55110208603),
    "Villefranche-le-Château, France": (44.2152597669, 5.50831127895),
    "Villeperdrix, France": (44.4557494791, 5.29971413853),
    "Vinsobres, France": (44.3356176138, 5.0506163995),
    "Abriès-Ristolas, France": (44.8163462455, 6.94164442809),
    "Aiguilles, France": (44.7793442905, 6.87518098236),
    "Arvieux, France": (44.7638980345, 6.72230859416),
    "Ceillac, France": (44.6526516194, 6.80366189831),
    "Château-Ville-Vieille, France": (44.7677289666, 6.8000656504),
    "Eygliers, France": (44.6925244966, 6.66688741371),
    "Guillestre, France": (44.6680987436, 6.7010158705),
    "Molines-en-Queyras, France": (44.7166553487, 6.89190806069),
    "Mont-Dauphin, France": (44.6690562408, 6.62465091015),
    "Risoul, France": (44.6240380773, 6.62317402744),
    "Réotier, France": (44.6813566833, 6.56457884975),
    "Saint-Clément-sur-Durance, France": (44.6478034969, 6.56074041767),
    "Saint-Crépin, France": (44.7243260485, 6.63555027257),
    "Saint-Véran, France": (44.684530806, 6.8905743766),
    "Vars, France": (44.5925220925, 6.71351456892),
    "Elzach, Germany": (48.1757263, 8.0718474),
    "Katzenmoos, Germany": (48.1671494, 8.0339401),
    "Oberprechtal, Germany": (48.2142936, 8.1419332),
    "Prechtal, Germany": (48.2142936, 8.1419332),
    "Yach, Germany": (48.1592947, 8.0910055),
    "Oberwinden, Germany": (48.152948, 8.0450017),
    "Niederwinden, Germany": (48.1437659, 8.0227467),
    "Löffingen, Germany": (47.8510581, 8.3581352),
    "Bachheim, Germany": (47.8595312, 8.4015165),
    "Görschweiler, Germany": (47.8581847, 8.3117456),
    "Reiselfingen, Germany": (47.8510581, 8.3581352),
    "Seppenhofen, Germany": (47.8722497, 8.3493952),
    "Unadingen, Germany": (47.8807628, 8.4070082),
    "Lienz, Austria": (46.8294, 12.7687),
    "Ottenschlag, Austria": (48.42404288609467, 15.219451348902961),
    "Waidhofen an der Thaya, Austria": (48.815658288556946, 15.281663737168207),
    "Traismauer, Austria": (48.352465997812615, 15.743648288832958),
    "Aspang Markt, Austria": (47.55413804192899, 16.09238522102577),
    "Amstetten, Austria": (48.12503262268305, 14.867380540803904),
    "Euratsfeld, Austria": (48.08226280834668, 14.929916352815775),
    "St. Andrä-Wördern, Austria": (48.3417260403357, 16.241368646253243),
    "St. Veit an der Gölsen, Austria": (48.04470064214175, 15.67000830613681),
    "Bad Pirawarth, Austria": (48.45190245064155, 16.60252936507856),
    "Ober-Grafendorf, Austria": (48.14935380857714, 15.542813681153428),
    "Gföhl, Austria": (48.51669492174179, 15.489640708246656),
    "Groß Gerungs, Austria": (48.57441831065571, 14.960792633910886),
    "Retz, Austria": (48.75903530925255, 15.953293555334188),
    "Tulln an der Donau, Austria": (48.33183733546916, 16.061388229537908),
    "Korneuburg, Austria": (48.34498430211194, 16.330250320974198),
    "Ernstbrunn, Austria": (48.52600970281068, 16.36012249198592),
    "Mistelbach, Austria": (48.56872317085308, 16.569164715283126),
    "Laa an der Thaya, Austria": (48.71892203478325, 16.38682487011301),
    "Poysdorf, Austria": (48.667801475116505, 16.627739665226958),
    "Großschönau, Austria": (48.65037331744002, 14.942586526590183),
    "Mautern an der Donau, Austria": (48.39272810709832, 15.579415287384178),
    "Gaming, Austria": (47.92387741453361, 15.09109611689855),
    "Litschau, Austria": (48.94452058173408, 15.044568007002988),
    "Ebreichsdorf, Austria": (47.96296571408522, 16.40031771962582),
    "Hollabrunn, Austria": (48.562712315748264, 16.078411385981617),
    "Cogne, Italy": (45.610690, 7.362238),
    "Champdepraz, Italy": (45.683844, 7.613495),
    "Cogorno, Italy": (44.3315515, 9.3490907),
    "Pomurje region, Slovenia": (46.65816519569652, 16.163141737462734),
    "Podravje region, Slovenia": (46.553067690589025, 15.650944727735766)
}

# ---------------------------
# Custom CSS for a Stunning Look
# ---------------------------
st.markdown(
    """
    <style>
    /* Page background and font styles */
    body {
        background-color: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    /* Sidebar style */
    .css-1d391kg {  /* this class may change with Streamlit versions */
        background: linear-gradient(135deg, #6a11cb, #2575fc);
        color: white;
    }
    .css-1d391kg .sidebar-content {
        color: white;
    }
    /* Header banner styling */
    .header-banner {
        width: 100%;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    /* Title styling */
    .app-title {
        font-size: 2.5rem;
        font-weight: bold;
        color: #2575fc;
        text-align: center;
    }
    /* Footer styling */
    .footer {
        text-align: center;
        font-size: 0.8rem;
        color: #888888;
        margin-top: 30px;
    }
    </style>
    """,
    unsafe_allow_html=True,
)

# ---------------------------
# Helper Functions
# ---------------------------
def compute_zoom_level(radius: int) -> int:
    """Computes an appropriate zoom level based on the search radius."""
    if radius < 500:
        return 15
    zoom = 15 - int(math.log(radius / 500, 2))
    return max(10, min(zoom, 18))

def get_marker_color(entity_type: str) -> str:
    """Returns a color based on the entity type."""
    color_mapping = {
        "all": "blue",
        "restaurant": "red",
        "hospital": "green",
        "school": "purple",
        "bank": "orange",
        "cafe": "darkred",
        "pharmacy": "lightblue",
        "cinema": "cadetblue",
        "parking": "darkblue",
        "fuel": "lightgreen",
        "amenity=marketplace": "teal",
        "amenity=vending_machine": "gray",
        "building=commercial": "darkpurple",
        "man_made=offshore_platform": "pink",
        "man_made=petroleum_well": "black",
        "man_made=pipeline": "lightgray",
        "man_made=works": "beige",
        "office=company": "red",
        "office=coworking": "blue",
        "shop=all": "green",
        "tourism=alpine_hut": "purple",
        "tourism=attraction": "orange",
        "tourism=camp_pitch": "darkred",
        "tourism=camp_site": "cadetblue",
        "tourism=caravan_site": "blue",
        "building=chalet": "lightgreen",
        "building=guest_house": "darkblue",
        "building=hostel": "purple",
        "building=hotel": "red",
        "tourism=information": "orange",
        "tourism=motel": "green",
        "building=museum": "gray",
        "tourism=wilderness_hut": "pink",
        "amenity=townhall": "blue",
        "amenity=courthouse": "red",
        "amenity=police": "green",
        "amenity=fire_station": "purple",
        "building=government": "orange",
        "barrier=bump_gate": "darkred",
        "barrier=bus_trap": "cadetblue",
        "barrier=cycle_barrier": "blue",
        "barrier=motorcycle_barrier": "green",
        "barrier=sump_buster": "purple",
        "building=train_station": "red",
        "building=transportation": "orange",
        "highway=motorway": "darkblue",
        "public_transport=all": "green",
        "railway=all": "purple",
        "route=all": "blue",
        "amenity=recycling": "lightgreen",
        "boundary=forest": "darkgreen",
        "boundary=forest_compartment": "green",
        "boundary=hazard": "red",
        "boundary=national_park": "orange",
        "boundary=protected_area": "purple",
        "leisure=garden": "lightblue",
        "leisure=nature_reserve": "blue",
        "leisure=park": "green",
        "man_made=gasometer": "gray",
        "man_made=mineshaft": "black",
        "man_made=wastewater_plant": "cadetblue",
        "man_made=water_works": "blue",
        "natural=grass": "green",
        "water=river": "blue",
        "amenity=college": "purple",
        "amenity=kindergarten": "orange",
        "amenity=university": "red",
        "office=educational_institution": "darkred",
        "office=employment_agency": "blue",
        "amenity=refugee_site": "lightgreen",
        "amenity=internet_cafe": "purple",
        "amenity=public_bath": "orange",
        "amenity=water_point": "red",
        "amenity=place_of_worship": "darkblue",
        "amenity=toilets": "green"
    }
    return color_mapping.get(entity_type, "blue")

@st.cache_data(show_spinner=False)
def get_amenities(latitude: float, longitude: float, amenity_type: str = 'all', radius: int = RADIUS_DEFAULT) -> pd.DataFrame:
    """Fetches amenities around the given latitude and longitude using OSMnx."""
    tags = {'amenity': True} if amenity_type == 'all' else {'amenity': amenity_type}
    return ox.geometries_from_point((latitude, longitude), tags=tags, dist=radius)

def count_entities(entities: pd.DataFrame) -> dict:
    """Counts different types of entities in the provided GeoDataFrame."""
    if 'entity_type' in entities.columns:
        counts = entities['entity_type'].value_counts()
    else:
        counts = entities.index.value_counts()
    return counts.to_dict()

def count_amenities(latitude: float, longitude: float, radius: int = RADIUS_DEFAULT) -> dict:
    """Counts amenities around the given latitude and longitude."""
    amenities = get_amenities(latitude, longitude, amenity_type='all', radius=radius)
    return amenities['amenity'].value_counts().to_dict()

@st.cache_data(show_spinner=False)
def get_smart_entities(latitude: float, longitude: float, ent: str, radius: int = RADIUS_DEFAULT) -> pd.DataFrame:
    """
    Fetches entities of a specific type around the given latitude and longitude.
    The parameter 'ent' should be a string in the format 'key=value'.
    """
    try:
        key, value = ent.split('=')
    except ValueError:
        st.error("Invalid entity format. Expected 'key=value'.")
        return pd.DataFrame()
    tags = {key: value}
    entities = ox.geometries_from_point((latitude, longitude), tags=tags, dist=radius)
    if not entities.empty:
        entities['entity_type'] = ent
    return entities

def add_markers_to_map(m: folium.Map, entities: pd.DataFrame, entity_type: str) -> None:
    """
    Adds friendly markers for the given entities on the folium map.
    This version uses folium.Marker with a custom icon instead of circle markers.
    """
    marker_color = get_marker_color(entity_type)
    for _, row in entities.iterrows():
        if row.geometry is None:
            continue
        if row.geometry.geom_type == 'Point':
            point_location = [row.geometry.y, row.geometry.x]
        elif row.geometry.geom_type in ['Polygon', 'MultiPolygon']:
            point_location = [row.geometry.centroid.y, row.geometry.centroid.x]
        else:
            continue
        tooltip = f"{entity_type}: {row.get('name', 'N/A')}"
        icon = folium.Icon(color=marker_color, icon='info-sign')
        folium.Marker(
            location=point_location,
            popup=tooltip,
            icon=icon
        ).add_to(m)

def construct_message_content(lat: float, lon: float) -> str:
    """
    Constructs the AI assistant query based on the active entities.
    """
    if st.session_state.selected_entities:
        try:
            combined_entities = pd.concat(st.session_state.selected_entities, ignore_index=True)
            entity_counts = count_entities(combined_entities)
            if 'all' in entity_counts:
                amenities_count = count_amenities(lat, lon, RADIUS_DEFAULT)
                info = json.dumps(amenities_count, indent=2)
            else:
                filtered_counts = {k: v for k, v in entity_counts.items() if k != 'all'}
                info = "\n".join([f"{etype}: {count}" for etype, count in filtered_counts.items()])
            message = (
                "Given the following active facilities in the selected area:\n"
                f"{info}\n\n"
                "What is the level of digitalization, smartness, or rural development of this area? "
                "Do you have any suggestions to improve it?"
            )
            return message
        except Exception as e:
            st.error(f"Error constructing message content: {e}")
            return ""
    return ""

def call_ai_analysis(message: str) -> dict:
    """
    Calls the AI analysis API with the given message content.
    """
    data = {
        "messages": [{"content": message, "role": "user"}],
        "chatbotId": CHATBOT_ID,
        "stream": False,
        "temperature": 0
    }
    response = requests.post(API_URL, headers=API_HEADERS, data=json.dumps(data))
    if response.status_code == 200:
        return response.json()
    else:
        try:
            error_message = response.json().get('message', 'Unknown error')
        except Exception:
            error_message = response.text
        return {"error": error_message}

# ---------------------------
# Main App
# ---------------------------
def main():
    # Header Banner and Title
    st.markdown('<div class="app-title">Smart CommUnity - TA Analyzer</div>', unsafe_allow_html=True)

    # Sidebar: Test Area Selection and Clear Option
    example_choice = st.sidebar.selectbox("Choose a Test Area:", list(example_coordinates.keys()), key='example_choice')
    selected_coordinate = example_coordinates.get(example_choice, DEFAULT_COORDINATES)
    lat = st.sidebar.number_input("Latitude:", value=selected_coordinate[0], key="lat_input")
    lon = st.sidebar.number_input("Longitude:", value=selected_coordinate[1], key="lon_input")

    if st.sidebar.button("Clear Selected Entities"):
        st.session_state.selected_entities = []
        st.session_state.message_content = ""
        st.experimental_rerun()

    # Create folium map with dynamic zoom based on the default radius.
    zoom_level = compute_zoom_level(RADIUS_DEFAULT)
    m = folium.Map(location=[lat, lon], zoom_start=zoom_level, width=MAP_WIDTH, height=MAP_HEIGHT)

    # Note: We removed marker clustering for a cleaner marker display.
    
    # Define tabs for different analysis sections.
    tab_names = ["Default", "SmartEconomy", "SmartGovernance", "SmartMobility", "SmartEnvironment", "SmartPeople", "SmartLiving"]
    tabs = st.tabs(tab_names)

    # Default Tab: General Amenities
    with tabs[0]:
        st.subheader("General Amenities")
        amenity_options = ['all', 'restaurant', 'hospital', 'school', 'bank', 'cafe', 'pharmacy', 'cinema', 'parking', 'fuel']
        amenity_type = st.selectbox("Select Amenity Type:", amenity_options, key='amenity_type')
        if st.button('Show Amenities', key='amenity'):
            with st.spinner("Fetching amenities..."):
                try:
                    amenities = get_amenities(lat, lon, amenity_type, RADIUS_DEFAULT)
                    if amenities.empty:
                        st.warning(f"No {amenity_type} amenities found within the specified distance.")
                    else:
                        amenities['entity_type'] = amenity_type
                        add_markers_to_map(m, amenities, amenity_type)
                        st.session_state.selected_entities.append(amenities)
                        st.session_state.message_content = construct_message_content(lat, lon)
                except Exception as e:
                    st.error(f"An error occurred: {e}")

    # Smart Entity Tabs Configuration
    smart_entities_options = {
        "SmartEconomy": [
            'amenity=marketplace', 'amenity=vending_machine', 'building=commercial', 
            'man_made=offshore_platform', 'man_made=petroleum_well', 'man_made=pipeline', 'man_made=works', 'office=company',
            'office=coworking', 'shop=all', 'tourism=alpine_hut', 'tourism=attraction', 'tourism=camp_pitch', 'tourism=camp_site',
            'tourism=caravan_site', 'building=chalet', 'building=guest_house', 'building=hostel', 'building=hotel', 'tourism=information',
            'tourism=motel', 'building=museum', 'tourism=wilderness_hut'
        ],
        "SmartGovernance": ['amenity=townhall', 'amenity=courthouse', 'amenity=police', 'amenity=fire_station', 'building=government'],
        "SmartMobility": [
            'barrier=bump_gate', 'barrier=bus_trap', 'barrier=cycle_barrier', 'barrier=motorcycle_barrier',
            'barrier=sump_buster', 'building=train_station', 'building=transportation', 'building=parking',
            'highway=motorway', 'public_transport=all', 'railway=all', 'route=all'
        ],
        "SmartEnvironment": [
            "amenity=recycling", "boundary=forest", "boundary=forest_compartment", "boundary=hazard",
            "boundary=national_park", "boundary=protected_area", "leisure=garden", "leisure=nature_reserve",
            "leisure=park", "man_made=gasometer", "man_made=mineshaft", "man_made=wastewater_plant",
            "man_made=water_works", "natural=grass", "water=river"
        ],
        "SmartPeople": [
            "amenity=college", "amenity=kindergarten", "amenity=school", "amenity=university",
            "office=educational_institution", "office=employment_agency", "amenity=refugee_site"
        ],
        "SmartLiving": [
            "amenity=internet_cafe", "amenity=public_bath", "amenity=vending_machine",
            "amenity=water_point", "amenity=hospital", "amenity=museum",
            "amenity=place_of_worship", "amenity=fire_station", "amenity=toilets",
        ]
    }

    # Loop through Smart Entity Tabs
    for idx, tab_name in enumerate(tab_names[1:], start=1):
        with tabs[idx]:
            st.subheader(tab_name)
            selected_entity = st.selectbox(f"Select Entity Type for {tab_name}:", smart_entities_options[tab_name], key=f'{tab_name}_entity')
            if st.button(f'Show Entities for {tab_name}', key=f'tab{idx}'):
                with st.spinner(f"Fetching entities for {tab_name}..."):
                    try:
                        entities = get_smart_entities(lat, lon, selected_entity, RADIUS_DEFAULT)
                        if entities.empty:
                            st.warning(f"No entities found for {selected_entity} within the specified distance.")
                        else:
                            add_markers_to_map(m, entities, selected_entity)
                            st.session_state.selected_entities.append(entities)
                            st.session_state.message_content = construct_message_content(lat, lon)
                    except Exception as e:
                        st.error(f"An error occurred: {e}")

    # Display the interactive folium map.
    folium_static(m)

    # AI Assistant Section
    st.subheader("AI Assistant")
    if st.button('Analysis', key='ai_analysis'):
        if not st.session_state.message_content:
            st.warning("Please add some entities before running the analysis.")
        else:
            with st.spinner("Performing AI Analysis..."):
                result = call_ai_analysis(st.session_state.message_content)
                if "error" not in result:
                    response_text = result.get('text', 'No text in response')
                    st.write("Response:", response_text)
                    st.download_button(
                        "Download Analysis as TXT",
                        data=response_text,
                        file_name=f"AI_Analysis_{lat}_{lon}.txt",
                        mime="text/plain"
                    )
                else:
                    st.error(f"Error: {result['error']}")

    # Footer
    st.markdown('<div class="footer">© 2025 Smart CommUnity. Crafted with passion for smarter communities.</div>', unsafe_allow_html=True)
    
if __name__ == "__main__":
    if 'selected_entities' not in st.session_state:
        st.session_state.selected_entities = []
    if 'message_content' not in st.session_state:
        st.session_state.message_content = ""
    
    main()


