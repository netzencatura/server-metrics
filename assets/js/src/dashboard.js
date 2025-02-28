/**
 * Dashboard specific JavaScript functionality
 */
import DarkMode from "./modules/dark-mode.js";
import Charts from "./modules/charts.js";
import Sparklines from "./modules/sparklines.js";
import LiveUpdates from "./modules/live-updates.js";
import Ajax from "./utils/ajax.js";
import Helpers from "./utils/helpers.js";

jQuery(document).ready(function ($) {
  console.log("Server Metrics Dashboard: Inicializace");

  // Kontrola, zda je načtena Chart.js
  if (typeof Chart === "undefined") {
    console.error("Chyba: Chart.js knihovna není načtena!");
    alert("Některé funkce nebudou fungovat správně - chybí knihovna Chart.js");
    return;
  }

  /********************************************
   * INICIALIZACE PŘI NAČTENÍ STRÁNKY
   ********************************************/
  console.log("Spouštění inicializačních funkcí...");

  // Nastavení tmavého/světlého režimu
  DarkMode.init();

  // Načtení souhrnných grafů v kartách
  loadAverageCharts();

  // Načtení malých grafů v tabulce
  // Přidáno zpoždění pro stabilnější načítání
  setTimeout(function () {
    Sparklines.loadAll();
  }, 300);

  // Optimalizace pro mobilní zařízení
  Helpers.adjustLayoutForMobile();

  /********************************************
   * UDÁLOSTI (EVENT LISTENERS)
   ********************************************/

  // Zobrazení detailu webu po kliknutí
  $(".show-detail-btn").on("click", function (e) {
    e.preventDefault();
    const uuid = $(this).data("uuid");
    const domain = $(this).closest("tr").find("td:first").text();

    LiveUpdates.currentUuid = uuid;
    $("#detail-domain").text(
      serverMetricsData.strings.websiteDetail + ": " + domain
    );

    // Namísto jednorázového načtení nastavíme živé grafy
    LiveUpdates.setupLiveCharts(uuid);

    // Zobrazení modálního okna
    $("#website-detail-modal").show();
  });

  // Zavření modálního okna
  $(".close").on("click", function () {
    $("#website-detail-modal").hide();
    // Zastavit živé aktualizace při zavření modálního okna
    LiveUpdates.stopLiveCharts();
  });

  // Změna časového období
  $("#period-filter").on("change", function () {
    // Zastavit aktualizace před změnou periody
    LiveUpdates.stopLiveCharts();

    const selectedPeriod = $(this).val();

    if (LiveUpdates.getCurrentUuid()) {
      Ajax.loadWebsiteMetrics(
        LiveUpdates.getCurrentUuid(),
        selectedPeriod,
        LiveUpdates.getCurrentTimeRange(),
        (data) => Charts.updateDetailCharts(data),
        (error) => alert(error)
      );
    }

    // Aktualizace souhrnných grafů pro nové období
    loadAverageCharts();

    // Aktualizace sparklines pro nové období
    Sparklines.loadAll(selectedPeriod);

    // Pokud je otevřený modální dialog, znovu spustit živé aktualizace
    if ($("#website-detail-modal").is(":visible")) {
      LiveUpdates.setupLiveCharts(LiveUpdates.getCurrentUuid());
    }
  });

  // Přepínání živých aktualizací
  $(document).on("click", "#toggle-live-updates", function () {
    const $button = $(this);

    if (LiveUpdates.liveUpdateInterval) {
      // Pokud běží, zastavíme aktualizace
      LiveUpdates.stopLiveCharts();
      $button.text(serverMetricsData.strings.resumeUpdates).addClass("paused");
      $(".live-indicator").hide();
    } else {
      // Pokud neběží, spustíme aktualizace
      LiveUpdates.setupLiveCharts(LiveUpdates.getCurrentUuid());
      $button
        .text(serverMetricsData.strings.pauseUpdates)
        .removeClass("paused");
      $(".live-indicator").show();
    }
  });

  // Sledování změny velikosti okna
  $(window).on("resize", function () {
    Helpers.adjustLayoutForMobile();
  });

  // Reakce na změnu dark mode
  document.addEventListener("darkModeChanged", function () {
    // Obnovit grafy s mírným zpožděním
    setTimeout(function () {
      refreshAllCharts();
    }, 300);
  });

  /********************************************
   * POMOCNÉ FUNKCE
   ********************************************/

  /**
   * Reload current website data
   */
  function reloadCurrentWebsite() {
    const uuid = LiveUpdates.getCurrentUuid();
    if (uuid) {
      LiveUpdates.stopLiveCharts();

      Ajax.loadWebsiteMetrics(
        uuid,
        $("#period-filter").val(),
        LiveUpdates.getCurrentTimeRange(),
        (data) => {
          Charts.updateDetailCharts(data);

          if ($("#website-detail-modal").is(":visible")) {
            LiveUpdates.setupLiveCharts(uuid);
          }
        },
        (error) => alert(error)
      );
    }
  }

  /**
   * Load average charts data
   */
  function loadAverageCharts() {
    Ajax.loadAverageData(
      $("#period-filter").val(),
      (data) => Charts.drawAverageCharts(data),
      (error) => console.warn("Nelze načíst průměrná data:", error)
    );
  }

  /**
   * Refresh all charts
   */
  function refreshAllCharts() {
    console.log("Obnovování všech grafů");

    // Obnovit průměrné grafy
    loadAverageCharts();

    // Obnovit detail, pokud je otevřený
    if (LiveUpdates.getCurrentUuid()) {
      Ajax.loadWebsiteMetrics(
        LiveUpdates.getCurrentUuid(),
        $("#period-filter").val(),
        LiveUpdates.getCurrentTimeRange(),
        (data) => Charts.updateDetailCharts(data),
        (error) => console.error("Chyba při načítání dat:", error)
      );
    }

    // Obnovit sparklines - úplné nové načtení
    Sparklines.loadAll();
  }
});
