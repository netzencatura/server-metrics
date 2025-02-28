/**
 * Servers page specific JavaScript functionality
 */
import DarkMode from "./modules/dark-mode.js";

jQuery(document).ready(function ($) {
  console.log("Server Metrics Servers: Inicializace");

  // Inicializace tmavého režimu
  DarkMode.init();

  // Reakce na změnu dark mode
  document.addEventListener("darkModeChanged", function () {
    // Zde by bylo možné přidat speciální funkcionalitu pro servers.php
  });
});
