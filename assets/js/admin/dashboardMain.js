  // Chart Initialization and Data Loading
  let myChart;
  const ctx = document.getElementById("myChart");

  function initializeChart(labels, data) {
    myChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: labels,
        datasets: [
          {
            label: "# of Votes",
            data: data,
            borderWidth: 1,
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
          },
        ],
      },
      options: {
        scales: {
          y: { beginAtZero: true },
        },
      },
    });
  }

  function loadChartData() {
    const scrollPosition = window.scrollY;

    const xhr = new XMLHttpRequest();
    xhr.open(
      "GET",
      "../../includes/functions/admin/getDashboardCharts.php",
      true
    );
    xhr.onload = function () {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);

        if (myChart) {
          // Update the existing chart
          myChart.data.labels = response.labels;
          myChart.data.datasets[0].data = response.data;
          myChart.update();
        } else {
          // Initialize the chart if it's not loaded yet
          initializeChart(response.labels, response.data);
        }

        window.scrollTo(0, scrollPosition);
      } else {
        console.error("Error loading chart data");
      }
    };
    xhr.send();
  }

  // Initial load and periodic refresh
  loadChartData();
  setInterval(loadChartData, 30000);
