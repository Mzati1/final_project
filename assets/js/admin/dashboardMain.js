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
          label: "# of Votes per Election",
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
    if (xhr.readyState === 4 && xhr.status === 200) {
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
// Function to delete a user
function deleteUser(userId) {
  if (confirm("Are you sure you want to delete this user?")) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../../includes/functions/admin/deleteUser.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          if (response.success) {
            alert("User deleted successfully.");
            document.getElementById("user-" + userId).remove();
          } else {
            alert("Error: " + response.message);
          }
        } else {
          alert("Request failed. Please try again.");
        }
      }
    };

    xhr.send("user_id=" + encodeURIComponent(userId));
  }
}

// Toggle between edit and save modes for election data
function toggleEdit(button) {
  const row = button.closest("tr");
  const isEditing = button.classList.contains("btn-save");
  const electionId = row.dataset.electionId;

  if (isEditing) {
    // Save Mode
    const electionData = {};

    row.querySelectorAll("input, select").forEach((input) => {
      const span = document.createElement("span");
      span.className = "editable-field";
      span.textContent = input.value;
      electionData[input.name] = input.value; // Collect data for AJAX request
      input.replaceWith(span);
    });

    // Send AJAX request to editElection.php
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../../includes/functions/admin/editElection.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        const response = JSON.parse(xhr.responseText);
        if (xhr.status === 200 && response.success) {
          alert("Election updated successfully.");
        } else {
          alert("Failed to update election: " + response.message);
        }
      }
    };
    xhr.send(
      `election_id=${encodeURIComponent(electionId)}&name=${encodeURIComponent(
        electionData.name
      )}&start_date=${encodeURIComponent(
        electionData.startDate
      )}&end_date=${encodeURIComponent(
        electionData.endDate
      )}&status=${encodeURIComponent(electionData.status)}`
    );

    button.textContent = "Edit";
    button.classList.remove("btn-save");
    button.classList.add("btn-edit");
  } else {
    // Edit Mode
    row.querySelectorAll(".editable-field").forEach((span) => {
      let input;

      if (span.parentNode.cellIndex === 1 || span.parentNode.cellIndex === 2) {
        // Date field
        input = document.createElement("input");
        input.type = "date";
        input.className = "editable-date";
        input.name = span.parentNode.cellIndex === 1 ? "startDate" : "endDate";
        input.value = span.textContent;
      } else if (span.parentNode.cellIndex === 3) {
        // Status dropdown
        input = document.createElement("select");
        input.className = "editable-select";
        input.name = "status";
        ["closed", "open"].forEach((status) => {
          const option = document.createElement("option");
          option.value = status;
          option.textContent = status;
          if (span.textContent === status) {
            option.selected = true;
          }
          input.appendChild(option);
        });
      } else {
        // Text input for other fields
        input = document.createElement("input");
        input.type = "text";
        input.className = "editable-input";
        input.name = "name";
        input.value = span.textContent;
      }

      span.replaceWith(input);
    });

    button.textContent = "Save";
    button.classList.remove("btn-edit");
    button.classList.add("btn-save");
  }
}

// Function to delete an election
function deleteElection(electionId) {
  if (confirm("Are you sure you want to delete this election?")) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../../includes/functions/admin/deleteElection.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        const response = JSON.parse(xhr.responseText);
        if (xhr.status === 200 && response.success) {
          document.getElementById("election-" + electionId).remove();
          alert("Election deleted successfully.");
        } else {
          alert("Failed to delete election: " + response.message);
        }
      }
    };

    xhr.send("election_id=" + encodeURIComponent(electionId));
  }
}

// Initial load and a periodic refresh
loadChartData();
setInterval(loadChartData, 30000);
