/**
 * Dark mode functionality for server-metrics plugin
 */

const DarkMode = {
  /**
   * Initialize dark mode
   */
  init: function () {
    console.log("Inicializace dark mode");

    // Zjistit uložené nastavení
    const savedDarkMode = localStorage.getItem("server_metrics_dark_mode");

    // Pokud není nastavení uloženo, zjistit systémové preference
    const shouldUseDarkMode = savedDarkMode === "true";

    if (shouldUseDarkMode) {
      jQuery("body").addClass("dark-mode");
      jQuery("#dark-mode-toggle").text("Light Mode");
    } else {
      jQuery("body").removeClass("dark-mode");
      jQuery("#dark-mode-toggle").text("Dark Mode");
    }

    console.log("Dark mode nastaven:", shouldUseDarkMode);

    // Nastavení click event listeneru
    jQuery("#dark-mode-toggle").on("click", this.toggle);
  },

  /**
   * Toggle dark mode
   */
  toggle: function () {
    console.log("Přepínání dark mode");

    // Přepnout třídu pomocí jQuery pro konzistenci
    jQuery("body").toggleClass("dark-mode");

    // Zjistit nový stav
    const isDarkMode = jQuery("body").hasClass("dark-mode");
    console.log("Nový stav dark mode:", isDarkMode);

    // Uložit nastavení
    localStorage.setItem("server_metrics_dark_mode", isDarkMode);

    // Aktualizovat text tlačítka
    jQuery("#dark-mode-toggle").text(isDarkMode ? "Light Mode" : "Dark Mode");

    // Vyvolat událost pro obnovení grafů
    const event = new CustomEvent("darkModeChanged", { detail: isDarkMode });
    document.dispatchEvent(event);
  },

  /**
   * Check if dark mode is enabled
   *
   * @returns {boolean} - True if dark mode is enabled
   */
  isEnabled: function () {
    return jQuery("body").hasClass("dark-mode");
  },
};

export default DarkMode;
