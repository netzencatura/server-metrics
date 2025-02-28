/**
 * Server Metrics Admin JS
 *
 * Main admin JavaScript file that loads appropriate module based on current page
 */

// Import CSS files
import "../../css/admin.css";

// Detect current admin page
function detectCurrentPage() {
  const path = window.location.pathname;
  const search = window.location.search;

  if (path.includes("wp-admin") && search.includes("page=server-metrics")) {
    // Main dashboard page
    if (
      search.includes("page=server-metrics") &&
      !search.includes("server-metrics-")
    ) {
      return "dashboard";
    }
    // Servers page
    else if (search.includes("page=server-metrics-servers")) {
      return "servers";
    }
    // Settings page
    else if (search.includes("page=server-metrics-settings")) {
      return "settings";
    }
    // Logs page (for future implementation)
    else if (search.includes("page=server-metrics-logs")) {
      return "logs";
    }
  }

  return "unknown";
}

// Load appropriate module based on current page
function loadAppropriateModule() {
  const currentPage = detectCurrentPage();
  console.log("Server Metrics: Detected page -", currentPage);

  // Dynamically import appropriate module
  switch (currentPage) {
    case "dashboard":
      import("./dashboard.js")
        .then((module) => {
          console.log("Dashboard module loaded");
        })
        .catch((error) => {
          console.error("Error loading dashboard module:", error);
        });
      break;

    case "servers":
      import("./servers.js")
        .then((module) => {
          console.log("Servers module loaded");
        })
        .catch((error) => {
          console.error("Error loading servers module:", error);
        });
      break;

    case "settings":
      // Settings page doesn't need special JS currently
      console.log("Settings page detected - no special module needed");
      break;

    case "logs":
      console.log("Logs page detected - module not implemented yet");
      break;

    default:
      console.log("Unknown page - no special module loaded");
  }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  console.log("Server Metrics Admin: Initializing");
  loadAppropriateModule();
});
