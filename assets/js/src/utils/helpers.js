/**
 * Helper functions for server-metrics plugin
 */

const Helpers = {
  /**
   * Safely convert date string to ISO string format
   *
   * @param {string} dateString - Date string to convert
   * @returns {string|null} - ISO string or null if conversion fails
   */
  safeToISOString: function (dateString) {
    try {
      // Formát pro české datum (např. "27. 2. 2025 23:37:45")
      if (typeof dateString === "string" && dateString.includes(".")) {
        const parts = dateString.split(" ");
        if (parts.length >= 2) {
          const dateParts = parts[0].split(".");
          if (dateParts.length >= 3) {
            const day = parseInt(dateParts[0].trim());
            const month = parseInt(dateParts[1].trim()) - 1; // měsíce jsou 0-11
            const year = parseInt(dateParts[2].trim());

            const timeParts = parts[1].split(":");
            const hour = parseInt(timeParts[0]);
            const minute = parseInt(timeParts[1]);
            const second = parseInt(timeParts[2] || "0");

            const date = new Date(year, month, day, hour, minute, second);
            return date.toISOString();
          }
        }
      }

      // Pokus o standardní konverzi
      return new Date(dateString).toISOString();
    } catch (error) {
      console.log("Chyba při převodu data:", dateString, error);
      return null;
    }
  },

  /**
   * Adjust layout for mobile devices
   */
  adjustLayoutForMobile: function () {
    // Upravit rozvržení grafů v modálním okně
    if (window.innerWidth < 768) {
      jQuery(".detail-charts").css("grid-template-columns", "1fr");
    } else {
      jQuery(".detail-charts").css("grid-template-columns", "repeat(2, 1fr)");
    }
  },
};

export default Helpers;
