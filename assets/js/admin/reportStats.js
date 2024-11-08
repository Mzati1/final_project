// Function to make an AJAX request
function makeRequest(url, params, callback) {
  var xhr = new XMLHttpRequest();
  xhr.open("GET", url + "?" + new URLSearchParams(params).toString(), true);

  xhr.onreadystatechange = function () {
    if (xhr.readyState == 4 && xhr.status == 200) {
      var response = JSON.parse(xhr.responseText);
      callback(response);
    }
  };
  xhr.send();
}

// Function to update the Voting Activity report
function fetchVotingActivity() {
  makeRequest(
    "../../includes/functions/admin/getReportStatstics.php",
    { report_type: "voting_activity" },
    function (response) {
      document.getElementById("total-votes").textContent = response.total_votes;
      document.getElementById("votes-today").textContent = response.votes_today;
      document.getElementById("votes-in-progress").textContent =
        response.most_voted_election;
      document.getElementById("votes-by-position").textContent =
        response.most_voted_candidate;

      // Update recent voting activity table
      var recentVotingHTML = "";
      response.recent_voting_activity.forEach(function (vote) {
        recentVotingHTML += `
          <tr>
            <td>${vote.first_name} ${vote.last_name}</td>  <!-- Correct 'voter' field -->
            <td>${vote.election_name}</td>  <!-- Correct 'election' field -->
            <td>${vote.position_name}</td>  <!-- Correct 'position' field -->
            <td>${vote.candidate_first} ${vote.candidate_last}</td>  <!-- Correct 'candidate' field -->
            <td>${vote.vote_time}</td>  <!-- Correct 'vote_time' field -->
          </tr>
        `;
      });
      document.getElementById("recent-voting-activity-tbody").innerHTML =
        recentVotingHTML;
    }
  );
}

// Function to update the Login Activity report
function fetchLoginActivity() {
  makeRequest(
    "../../includes/functions/admin/getReportStatstics.php",
    { report_type: "login_activity" },
    function (response) {
      document.getElementById("logins-today").textContent =
        response.logins_today;
      document.getElementById("failed-logins-today").textContent =
        response.failed_logins_today;

      // Extract the device from the user agent string for Popular Devices
      var popularDevice = getDeviceFromUserAgent(response.popular_devices);
      document.getElementById("popular-devices").textContent = popularDevice;

      document.getElementById("last-login-ip").textContent =
        response.last_login_ip;

      // Update recent logins table
      var recentLoginsHTML = "";
      response.recent_logins.forEach(function (login) {
        // Extract the device from the user agent string
        var clientDevice = getDeviceFromUserAgent(login.client);
        recentLoginsHTML += `
          <tr>
            <td>${login.attempted_account}</td>  <!-- Correct 'account' field -->
            <td>${login.login_time}</td>
            <td>${login.login_status}</td>  <!-- Correct 'status' field -->
            <td>${login.ip_address}</td>
            <td>${login.client} - ${clientDevice}</td>  <!-- Combine client and extracted device -->
          </tr>
        `;
      });
      document.getElementById("recent-logins-tbody").innerHTML =
        recentLoginsHTML;
    }
  );
}

// Function to extract the device from the user agent string
function getDeviceFromUserAgent(userAgent) {
  var device = "Unknown Device";

  // Check for specific devices in the user agent string
  if (/iPhone|iPad|iPod/.test(userAgent)) {
    device = "iPhone/iPad/iPod";
  } else if (/Android/.test(userAgent)) {
    device = "Android";
  } else if (/Macintosh/.test(userAgent)) {
    device = "Mac";
  } else if (/Windows/.test(userAgent)) {
    device = "Windows";
  } else if (/Linux/.test(userAgent)) {
    device = "Linux";
  } else if (/Mobile/.test(userAgent)) {
    device = "Mobile Device";
  }

  // Return the device name
  return device;
}

// Function to update the Election Status report
function fetchElectionStatus() {
  makeRequest(
    "../../includes/functions/admin/getReportStatstics.php",
    { report_type: "election_report" },
    function (response) {
      document.getElementById("total-elections").textContent =
        response.total_elections;
      document.getElementById("open-elections").textContent =
        response.open_elections;
      document.getElementById("closed-elections").textContent =
        response.closed_elections;
      document.getElementById("upcoming-elections").textContent =
        response.upcoming_elections;

      // Update recent elections table
      var recentElectionsHTML = "";
      response.recent_elections.forEach(function (election) {
        recentElectionsHTML += `
          <tr>
            <td>${election.election_name}</td>  <!-- Correct 'name' field -->
            <td>${election.start_date} to ${election.end_date}</td>  <!-- Correct 'dates' field -->
            <td>${election.election_status}</td>  <!-- Correct 'status' field -->
          </tr>
        `;
      });
      document.getElementById("recent-elections-tbody").innerHTML =
        recentElectionsHTML;
    }
  );
}

// Function to update the User Voting Activity report
function fetchUserVotingActivity() {
  makeRequest(
    "../../includes/functions/admin/getReportStatstics.php",
    { report_type: "user_voting_activity" },
    function (response) {
      // Correcting the assignments
      document.getElementById("total-unregistered").textContent =
        response.users_not_voted; // Should be 'users_not_voted' here
      document.getElementById("total-registered").textContent =
        response.users_voted; // Should be 'users_voted' here
      document.getElementById("total-admins").textContent =
        response.registered_users;
      document.getElementById("total-mec-staff").textContent =
        response.voter_turnout_percentage + "%";

      // Update the recently joined users table
      var recentUsersHTML = "";
      response.recent_users.forEach(function (user) {
        recentUsersHTML += `
          <tr>
            <td>${user.first_name} ${user.last_name}</td>  <!-- Correct 'name' field -->
            <td>${user.email}</td>
            <td>${user.created_at}</td>  <!-- Correct 'registration_date' field -->
          </tr>
        `;
      });
      document.getElementById("recent-users-tbody").innerHTML = recentUsersHTML;
    }
  );
}

document.addEventListener("DOMContentLoaded", () => {
  fetchUserVotingActivity();
  fetchLoginActivity();
  fetchElectionStatus();
  fetchVotingActivity();
});

// Refresh data every 5 seconds
setInterval(function () {
  fetchUserVotingActivity();
  fetchLoginActivity();
  fetchElectionStatus();
  fetchVotingActivity();
}, 5000);

// Function to extract browser and device from the user agent string
function getClientAndDeviceFromUserAgent(userAgent) {
  var browser = "Unknown Browser";
  var device = "Unknown Device";

  // Check for browser
  if (/Chrome/.test(userAgent)) {
    browser = "Chrome";
  } else if (/Firefox/.test(userAgent)) {
    browser = "Firefox";
  } else if (/Safari/.test(userAgent) && !/Chrome/.test(userAgent)) {
    browser = "Safari";
  } else if (/Edge/.test(userAgent)) {
    browser = "Edge";
  } else if (/MSIE|Trident/.test(userAgent)) {
    browser = "Internet Explorer";
  }

  // Check for device
  if (/iPhone|iPad|iPod/.test(userAgent)) {
    device = "iPhone/iPad/iPod";
  } else if (/Android/.test(userAgent)) {
    device = "Android";
  } else if (/Macintosh/.test(userAgent)) {
    device = "Mac";
  } else if (/Windows/.test(userAgent)) {
    device = "Windows";
  } else if (/Linux/.test(userAgent)) {
    device = "Linux";
  } else if (/Mobile/.test(userAgent)) {
    device = "Mobile Device";
  }

  // Return browser and device as "browser - device"
  return `${browser} - ${device}`;
}
