// Object to store all chart instances by their canvas ID
const chartInstances = {};

// Function to initialize or update a chart
function initializeOrUpdateChart(
  canvasId,
  labels,
  data,
  chartType = "bar",
  title = "",
  isResponsive = true // Add isResponsive parameter to control responsiveness
) {
  const ctx = document.getElementById(canvasId).getContext("2d");

  // If chart already exists, update its data
  if (chartInstances[canvasId]) {
    chartInstances[canvasId].data.labels = labels;
    chartInstances[canvasId].data.datasets[0].data = data;
    chartInstances[canvasId].update();
  } else {
    // Define options based on the chart type
    const chartOptions = {
      responsive: isResponsive, // Use the isResponsive parameter to control responsiveness
      plugins: {
        legend: { display: chartType !== "bar" }, // Display legend for pie/doughnut
        title: {
          display: !!title,
          text: title,
        },
      },
    };

    // Conditionally add scales for charts that need them
    if (chartType === "bar" || chartType === "line") {
      chartOptions.scales = {
        y: { beginAtZero: true },
      };
    } else {
      chartOptions.scales = {}; // No scales for pie, doughnut, etc.
    }

    // Create new chart if it doesn't exist
    const chart = new Chart(ctx, {
      type: chartType,
      data: {
        labels: labels,
        datasets: [
          {
            label: title || `Data for ${canvasId}`,
            data: data,
            backgroundColor: [
              "rgba(255, 99, 132, 0.2)",
              "rgba(54, 162, 235, 0.2)",
              "rgba(255, 206, 86, 0.2)",
              "rgba(75, 192, 192, 0.2)",
              "rgba(153, 102, 255, 0.2)",
              "rgba(255, 159, 64, 0.2)",
            ],
            borderColor: [
              "rgba(255, 99, 132, 1)",
              "rgba(54, 162, 235, 1)",
              "rgba(255, 206, 86, 1)",
              "rgba(75, 192, 192, 1)",
              "rgba(153, 102, 255, 1)",
              "rgba(255, 159, 64, 1)",
            ],
            borderWidth: 1,
          },
        ],
      },
      options: chartOptions,
    });

    // Store chart instance
    chartInstances[canvasId] = chart;
  }
}

// Function to load data for a specific chart
function loadChartData(
  canvasId,
  chartType,
  title = "",
  endpoint = "",
  isResponsive = true
) {
  const xhr = new XMLHttpRequest();

  // Custom endpoint
  const url = endpoint;

  xhr.open("GET", url, true);

  xhr.onload = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      const response = JSON.parse(xhr.responseText);
      initializeOrUpdateChart(
        canvasId,
        response.labels,
        response.data,
        chartType,
        title,
        isResponsive // Pass the isResponsive parameter to the chart initialization
      );
    } else {
      console.error(`Error loading data for chart ${canvasId}`);
    }
  };
  xhr.send();
}

// Example usage to load multiple charts with unique endpoints
document.addEventListener("DOMContentLoaded", () => {
  //main dashboard chart
  loadChartData(
    "myChart",
    "bar",
    "Votes per Election",
    "../../includes/functions/admin/getDashboardCharts.php",
    true
  );

  // user reg chart
  loadChartData(
    "userRegistrationPieChart",
    "pie",
    "Reg. Users vs Students",
    "../../includes/functions/admin/getUserRegistrationChart.php",
    false
  );

  //login activity chart
  loadChartData(
    "loginActivityLineChart",
    "pie",
    "Logins Per Device",
    "../../includes/functions/admin/getLoginActivityChart.php",
    false
  );

  //voting activity chart
  loadChartData(
    "votingActivityPieChart",
    "line",
    "Vote Distribution",
    "../../includes/functions/admin/getVotingActivityChart.php",
    true
  );

  //election status chart
  loadChartData(
    "electionStatusPieChart",
    "pie",
    "Votes per election",
    "../../includes/functions/admin/getElectionStatusChart.php",
    false
  );
});

// refreshing every 30s

setInterval(() => {
  // Call each loadChartData function

  loadChartData(
    "myChart",
    "bar",
    "Votes per Election",
    "../../includes/functions/admin/getDashboardCharts.php",
    true
  );

  loadChartData(
    "userRegistrationPieChart",
    "pie",
    "Reg. Users vs Students",
    "../../includes/functions/admin/getUserRegistrationChart.php",
    true
  );

  loadChartData(
    "loginActivityLineChart",
    "line",
    "",
    "../../includes/functions/admin/getLoginActivityChart.php",
    false
  );

  loadChartData(
    "electionStatusPieChart",
    "pie",
    "Votes per election",
    "../../includes/functions/admin/getElectionStatusChart.php",
    false
  );

  loadChartData(
    "votingActivityPieChart",
    "line",
    "Vote Distribution",
    "../../includes/functions/admin/getVotingActivityChart.php",
    true
  );
}, 30000); // 30 seconds
