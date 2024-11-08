// Tab Switching Logic
const tabLinks = document.querySelectorAll(".tab-link");
const tabContents = document.querySelectorAll(".tab-content");

tabLinks.forEach((link) => {
  link.addEventListener("click", (e) => {
    const targetTab = e.target.getAttribute("data-tab");

    // Hide all tab contents and remove active class from all links
    tabContents.forEach((content) => content.classList.remove("active"));
    tabLinks.forEach((tabLink) => tabLink.classList.remove("active"));

    // Show the clicked tab content
    document.getElementById(targetTab).classList.add("active");

    // Add active class to the clicked link
    e.target.classList.add("active");
  });
});
// Function to switch between the login and vote audit tabs
function showAuditTab(tabId) {
  // Hide all the tab content sections
  const allTabs = document.querySelectorAll(".audit-tab-content");
  allTabs.forEach((tab) => tab.classList.remove("active"));

  // Remove active class from all tab buttons
  const allButtons = document.querySelectorAll(".audit-tab");
  allButtons.forEach((button) => button.classList.remove("active-tab"));

  // Show the selected tab
  document.getElementById(tabId).classList.add("active");

  // Add active class to the clicked tab button
  document.getElementById(tabId + "-tab").classList.add("active-tab");
}

// Initialize with the login-audit tab visible
document.addEventListener("DOMContentLoaded", () => {
  showAuditTab("login-audit");
});




// Function to switch between the report tabs
function showReportTab(tabId) {
  // Hide all the report tab content sections
  const allReportTabs = document.querySelectorAll(".report-tab-content");
  allReportTabs.forEach((tab) => tab.classList.remove("active-report-content"));

  // Remove active class from all report tab buttons
  const allReportButtons = document.querySelectorAll(".report-tab");
  allReportButtons.forEach((button) => button.classList.remove("active-report-tab"));

  // Show the selected report tab
  document.getElementById(tabId).classList.add("active-report-content");

  // Add active class to the clicked report tab button
  document.getElementById(tabId + "-tab").classList.add("active-report-tab");
}

// Initialize with the user-report tab visible
document.addEventListener("DOMContentLoaded", () => {
  showReportTab("user-report");
});
