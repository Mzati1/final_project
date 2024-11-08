// Function to fetch and display both login and vote audit logs
function fetchAuditLogs() {
  // Create a new XMLHttpRequest object
  var xhr = new XMLHttpRequest();
  xhr.open("GET", "../../includes/functions/admin/liveAuditLogs.php", true);

  // Define the response behavior
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      try {
        // Parse the JSON response
        var data = JSON.parse(xhr.responseText);

        // Check if there's an error in the response
        if (data.error) {
          console.error("Error:", data.error);
          return;
        }

        // Call the function to populate the login audit table
        populateLoginAuditTable(data.loginLogs);

        // Call the function to populate the vote audit table
        populateVoteAuditTable(data.voteLogs);
      } catch (error) {
        console.error("Error parsing JSON:", error);
      }
    }
  };

  // Handle network errors
  xhr.onerror = function () {
    console.error("Error fetching audit logs");
  };

  // Send the request
  xhr.send();
}

// Function to format date to a readable format (e.g., MM/DD/YYYY HH:MM:SS)
function formatDate(dateString) {
  const date = new Date(dateString);
  const options = {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
  };
  return date.toLocaleString("en-US", options);
}

// Function to get device and browser information
function getDeviceAndBrowserInfo() {
  const userAgent = navigator.userAgent;
  const platform = navigator.platform;
  let deviceType = "Unknown Device";
  let browser = "Unknown Browser";

  // Detecting the device type
  if (platform.includes("Win")) {
    deviceType = "Windows";
  } else if (platform.includes("Mac")) {
    deviceType = "MacOS";
  } else if (platform.includes("Linux") && !platform.includes("Android")) {
    deviceType = "Linux";
  } else if (
    platform.includes("iPhone") ||
    platform.includes("iPad") ||
    platform.includes("iPod")
  ) {
    deviceType = "iOS";
  } else if (platform.includes("Android")) {
    deviceType = "Android";
  } else if (platform.includes("CrOS")) {
    deviceType = "ChromeOS";
  } else {
    deviceType = "Unknown";
  }

  // Detecting the browser using the user agent
  if (userAgent.includes("Chrome") && userAgent.includes("Edg/")) {
    browser = "Edge";
  } else if (userAgent.includes("OPR") || userAgent.includes("Opera")) {
    browser = "Opera";
  } else if (userAgent.includes("Brave")) {
    browser = "Brave";
  } else if (userAgent.includes("Vivaldi")) {
    browser = "Vivaldi";
  } else if (userAgent.includes("YaBrowser")) {
    browser = "Yandex";
  } else if (userAgent.includes("SamsungBrowser")) {
    browser = "Samsung Internet";
  } else if (userAgent.includes("Chrome")) {
    browser = "Chrome";
  } else if (userAgent.includes("Firefox")) {
    browser = "Firefox";
  } else if (userAgent.includes("Safari") && !userAgent.includes("Chrome")) {
    browser = "Safari";
  } else if (userAgent.includes("MSIE") || userAgent.includes("Trident")) {
    browser = "Internet Explorer";
  } else {
    browser = "Unknown";
  }

  return `${deviceType} - ${browser}`;
}

// Function to populate the login audit table
function populateLoginAuditTable(loginLogs) {
  const tableBody = document.querySelector("#login-audit tbody");
  tableBody.innerHTML = ""; // Clear current table data

  // Loop through the login logs and add rows to the table
  loginLogs.forEach((log) => {
    const row = document.createElement("tr");

    // Conditionally display student or admin name based on account type
    const accountName =
      log.account_type === "student"
        ? log.student_first_name + " " + log.student_last_name
        : log.account_type === "admin"
        ? log.admin_first_name + " " + log.admin_last_name
        : log.attempted_account; // Default if it's 'unknown'

    // Determine the color for the login status bubble
    const statusClass =
      log.login_status.toLowerCase() === "successful"
        ? "green-bubble"
        : "red-bubble";

    // Create table cells for each log
    row.innerHTML = `
            <td>${accountName}</td>
            <td>${log.account_type}</td>
            <td>${formatDate(log.login_time)}</td> <!-- Format date -->
            <td>${log.ip_address}</td>
            <td>${getDeviceAndBrowserInfo()}</td> <!-- Show device and browser info -->
            <td><span class="${statusClass}">${
      log.login_status
    }</span></td> <!-- Colored bubble for status -->
        `;

    // Append the row to the table body
    tableBody.appendChild(row);
  });
}

// Function to populate the vote audit table
function populateVoteAuditTable(voteLogs) {
  const tableBody = document.querySelector("#vote-audit tbody");
  tableBody.innerHTML = "";

  // Loop through the vote logs and add rows to the table
  voteLogs.forEach((log) => {
    const row = document.createElement("tr");

    // Create table cells for each log
    row.innerHTML = `
            <td>${log.student_first_name} ${log.student_last_name}</td>
            <td>${log.candidate_first_name} ${log.candidate_last_name}</td>
            <td>${log.election_name}</td>
            <td>${log.position_name}</td>
            <td>${formatDate(log.vote_time)}</td> <!-- Format date -->
        `;

    // Append the row to the table body
    tableBody.appendChild(row);
  });
}

// Function to switch between audit tabs
function showAuditTab(tabId) {
  const tabs = document.querySelectorAll(".audit-tab-content");
  const buttons = document.querySelectorAll(".audit-tab");

  // Hide all tabs and remove active class from buttons
  tabs.forEach((tab) => {
    tab.classList.remove("active");
  });

  buttons.forEach((button) => {
    button.classList.remove("active-tab");
  });

  // Show the selected tab and add active class to the corresponding button
  document.getElementById(tabId).classList.add("active");
  document.getElementById(tabId + "-tab").classList.add("active-tab");
}

// Fetch audit logs every 30 seconds (adjust this interval as needed)
setInterval(fetchAuditLogs, 20000);

// Initial fetch on page load
document.addEventListener("DOMContentLoaded", () => {
  fetchAuditLogs();
});
