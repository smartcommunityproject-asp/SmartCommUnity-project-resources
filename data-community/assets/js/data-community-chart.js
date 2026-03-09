/**
 * Data Community Frontend Chart Initialization
 *
 * Uses Highcharts to render charts based on configuration passed from PHP.
 * Now supports multiple series (Key vs Baseline).
 */

// Global function to initialize all charts configured via wp_add_inline_script
function initDcAllCharts() {
    // Check if Highcharts library is loaded
    if (typeof Highcharts === 'undefined') {
        console.error('Data Community Error: Highcharts library not loaded.');
        return;
    }

    // Check if configuration object exists
    if (typeof window.dcChartConfigs === 'undefined' || window.dcChartConfigs === null || Object.keys(window.dcChartConfigs).length === 0) {
        // console.log('Data Community Info: No chart configurations found.');
        return;
    }

    // Loop through each chart configuration passed from PHP
    for (const configKey in window.dcChartConfigs) {
        if (window.dcChartConfigs.hasOwnProperty(configKey)) {
            const config = window.dcChartConfigs[configKey];

            // Find the container element for this chart
            const chartContainer = document.getElementById(config.chartId);

            if (!chartContainer) {
                console.error(`Data Community Error: Chart container element #${config.chartId} not found.`);
                continue; // Skip to the next chart config
            }

            // Check if series data is available (at least one series with data)
            let hasData = false;
            if (config.series && Array.isArray(config.series)) {
                 config.series.forEach(function(serie) {
                     if (serie.data && Array.isArray(serie.data) && serie.data.length > 0) {
                         hasData = true;
                     }
                 });
            }


            if (!hasData) {
                console.warn(`Data Community Warning: No data available for any series in chart #${config.chartId}.`);
                // Display a message in the container
                chartContainer.innerHTML = '<p style="text-align:center; color: #888;">No data available to display chart.</p>';
                continue; // Skip to next chart
            }

            // --- Initialize Highcharts ---
            try {
                Highcharts.chart(config.chartId, {
                    chart: {
                        type: 'line', // Line chart suitable for comparison
                        zoomType: 'x' // Allow zooming on the x-axis
                    },
                    title: {
                        text: config.title || 'Data Chart' // Use title from config or default
                    },
                    xAxis: {
                        type: 'datetime', // X-axis represents time
                        title: {
                            text: 'Date / Time'
                        },
                        dateTimeLabelFormats: { // Customize date formats
                            month: '%e. %b',
                            year: '%b'
                        }
                    },
                    yAxis: {
                        title: {
                            text: config.yAxisTitle || 'Value' // Y-axis label from config or default
                        }
                        // min: 0 // Optional: Keep auto-scaling unless 0 baseline is required
                    },
                    tooltip: {
                        shared: true, // Show tooltips for all series at the same x-point
                        crosshairs: true,
                        headerFormat: '<b>{point.key:%e. %b %Y %H:%M}</b><br>', // Show detailed time in header
                        pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: <b>{point.y:.2f}</b><br/>' // Format tooltip display for each series
                    },
                    plotOptions: {
                        line: {
                            marker: {
                                enabled: true,
                                radius: 3,
                                symbol: 'circle' // Ensure markers are visible
                            },
                            // Optional: Different line styles if not defined in series config
                            // lineWidth: 2
                        }
                        // Series-specific options like 'dashStyle' are set in the PHP config now
                    },
                    series: config.series, // Use the series array passed from PHP
                    legend: {
                        enabled: true // Ensure legend is shown to identify series
                    },
                    credits: {
                        enabled: false // Hide the Highcharts.com credits
                    }
                    // Optional: Add export features
                    /*
                    exporting: {
                        enabled: true,
                        buttons: {
                            contextButton: {
                                menuItems: ['viewFullscreen', 'printChart', 'separator', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG']
                            }
                        }
                    }
                    */
                });
            } catch (e) {
                console.error(`Data Community Error: Failed to initialize chart #${config.chartId}.`, e);
                chartContainer.innerHTML = '<p class="dc-error" style="text-align:center;">Error initializing chart.</p>';
            }
        }
    }

    // Optional: Clear the config object after use
    // window.dcChartConfigs = {};
}

// Note: The actual execution is triggered by the inline script added via wp_add_inline_script
// which calls initDcAllCharts() on DOMContentLoaded.