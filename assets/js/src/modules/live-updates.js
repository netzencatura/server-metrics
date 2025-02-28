/**
 * Live updates functionality for server-metrics plugin
 */
import Ajax from "../utils/ajax.js";
import Charts from "./charts.js";
import Helpers from "../utils/helpers.js";

const LiveUpdates = {
  // Nastavení pro živé aktualizace
  liveUpdateInterval: null,
  currentUuid: null,
  maxPoints: 15,
  currentTimeRange: "15min",

  // Poslední známé timestamp pro inkrementální aktualizace
  lastTimestamps: {
    cpu: null,
    memory: null,
    ioRead: null,
    ioWrite: null,
  },

  /**
   * Setup live charts updates
   *
   * @param {string} uuid - Website UUID
   */
  setupLiveCharts: function (uuid) {
    console.log("Nastavení živých grafů pro UUID:", uuid);

    this.currentUuid = uuid;

    // Zastavit předchozí interval, pokud existuje
    if (this.liveUpdateInterval) {
      clearInterval(this.liveUpdateInterval);
    }

    // Resetovat poslední timestamp
    this.lastTimestamps = {
      cpu: null,
      memory: null,
      ioRead: null,
      ioWrite: null,
    };

    // Přidání kontrolních prvků pro živé aktualizace, pokud ještě neexistují
    this.setupLiveUIElements();

    // Načíst počáteční data
    Ajax.loadWebsiteMetrics(
      uuid,
      jQuery("#period-filter").val(),
      this.currentTimeRange,
      (data) => Charts.updateDetailCharts(data),
      (error) => alert(error)
    );

    // Nastavit interval pro aktualizaci (každých 10 sekund)
    this.liveUpdateInterval = setInterval(() => {
      this.updateLiveCharts();
    }, 10000); // 10 sekund
  },

  /**
   * Stop live chart updates
   */
  stopLiveCharts: function () {
    console.log("Zastavení živých aktualizací");

    if (this.liveUpdateInterval) {
      clearInterval(this.liveUpdateInterval);
      this.liveUpdateInterval = null;
    }
  },

  /**
   * Setup UI elements for live updates
   */
  setupLiveUIElements: function () {
    // Pokud již prvky existují, není třeba je znovu vytvářet
    if (jQuery(".live-indicator").length > 0) {
      jQuery(".live-indicator").show();
      jQuery("#toggle-live-updates")
        .text(serverMetricsData.strings.pauseUpdates)
        .removeClass("paused");
      return;
    }

    // Vytvoření hlavičky modálu
    if (jQuery("#detail-domain").parent().is("h2")) {
      // Zabalení existujícího nadpisu do hlavičky
      jQuery("#detail-domain").wrap('<div class="modal-header"></div>');

      // Přidání indikátoru a tlačítka
      jQuery(".modal-header").append(`
                <div class="modal-controls">
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        <span class="live-text">${serverMetricsData.strings.liveUpdates}</span>
                    </div>
                    <button id="toggle-live-updates" class="button">${serverMetricsData.strings.pauseUpdates}</button>
                </div>
            `);

      // Přesunout tlačítko zavřít do header
      jQuery(".close").prependTo(".modal-header");
    }
  },

  /**
   * Update live charts with new data
   */
  updateLiveCharts: function () {
    console.log("Aktualizace živých grafů...");

    if (!this.currentUuid) return;

    try {
      // Získat poslední timestamp z dat v grafech
      const lastCpuPoint =
        Charts.cpuChart && Charts.cpuChart.data.labels.length
          ? Charts.cpuChart.data.labels[Charts.cpuChart.data.labels.length - 1]
          : null;

      const lastMemPoint =
        Charts.memoryChart && Charts.memoryChart.data.labels.length
          ? Charts.memoryChart.data.labels[
              Charts.memoryChart.data.labels.length - 1
            ]
          : null;

      const lastIoReadPoint =
        Charts.ioReadChart && Charts.ioReadChart.data.labels.length
          ? Charts.ioReadChart.data.labels[
              Charts.ioReadChart.data.labels.length - 1
            ]
          : null;

      const lastIoWritePoint =
        Charts.ioWriteChart && Charts.ioWriteChart.data.labels.length
          ? Charts.ioWriteChart.data.labels[
              Charts.ioWriteChart.data.labels.length - 1
            ]
          : null;

      // Převést na časové značky pro server - BEZPEČNÁ KONVERZE
      this.lastTimestamps.cpu = lastCpuPoint
        ? Helpers.safeToISOString(lastCpuPoint)
        : null;
      this.lastTimestamps.memory = lastMemPoint
        ? Helpers.safeToISOString(lastMemPoint)
        : null;
      this.lastTimestamps.ioRead = lastIoReadPoint
        ? Helpers.safeToISOString(lastIoReadPoint)
        : null;
      this.lastTimestamps.ioWrite = lastIoWritePoint
        ? Helpers.safeToISOString(lastIoWritePoint)
        : null;

      // Použít nejnovější timestamp
      const allTimestamps = [
        this.lastTimestamps.cpu,
        this.lastTimestamps.memory,
        this.lastTimestamps.ioRead,
        this.lastTimestamps.ioWrite,
      ].filter(Boolean);

      const lastTimestamp = allTimestamps.length
        ? allTimestamps.sort().pop()
        : null;

      if (!lastTimestamp) {
        // Pokud nemáme timestamp, načteme kompletní data
        Ajax.loadWebsiteMetrics(
          this.currentUuid,
          jQuery("#period-filter").val(),
          this.currentTimeRange,
          (data) => Charts.updateDetailCharts(data),
          (error) => console.error("Chyba při načítání dat:", error)
        );
        return;
      }

      // Načtení nových dat od posledního bodu
      Ajax.loadLiveMetrics(
        this.currentUuid,
        lastTimestamp,
        (newData) => this.updateChartsWithNewData(newData),
        (error) => console.error("Chyba při aktualizaci živých grafů:", error)
      );
    } catch (error) {
      console.error("Chyba při aktualizaci grafů:", error);
    }
  },

  /**
   * Update charts with new data points
   *
   * @param {Array} newData - New data points
   */
  updateChartsWithNewData: function (newData) {
    if (!newData || newData.length === 0) return;

    console.log("Aktualizace grafů s novými daty:", newData.length, "bodů");

    // Připravit data pro grafy
    const labels = newData.map((item) => {
      const date = new Date(item.timestamp);
      return date.getHours() + ":" + date.getMinutes();
    });
    const cpuData = newData.map((item) => item.cpu_usage);
    const memoryData = newData.map((item) => item.mem_usage);
    const ioReadData = newData.map((item) => item.io_read_rate);
    const ioWriteData = newData.map((item) => item.io_write_rate);

    // Přidat nové body k existujícím grafům a posunout, pokud je potřeba
    Charts.addPointsToChart(Charts.cpuChart, labels, cpuData, this.maxPoints);
    Charts.addPointsToChart(
      Charts.memoryChart,
      labels,
      memoryData,
      this.maxPoints
    );
    Charts.addPointsToChart(
      Charts.ioReadChart,
      labels,
      ioReadData,
      this.maxPoints
    );
    Charts.addPointsToChart(
      Charts.ioWriteChart,
      labels,
      ioWriteData,
      this.maxPoints
    );
    Charts.cpuChart.update();
    Charts.memoryChart.update();
    Charts.ioReadChart.update();
    Charts.ioWriteChart.update();
  },

  /**
   * Set current time range and max points
   *
   * @param {string} timeRange - Time range (15min, 30min, 1hour, 2hours)
   */
  setTimeRange: function (timeRange) {
    // Odstraňte čárku z předchozího řádku
    this.currentTimeRange = timeRange;

    // Nastavit max_points podle časového intervalu
    switch (timeRange) {
      case "30min":
        this.maxPoints = 60; // pro 30 minut (2 body na minutu × 30 minut)
        break;
      case "hour":
        this.maxPoints = 120; // pro 1 hodinu (2 body na minutu × 60 minut)
        break;
      case "6hours":
        this.maxPoints = 360; // pro 6 hodin (1 bod na minutu × 360 minut)
        break;
      case "12hours":
        this.maxPoints = 360; // pro 12 hodin (0.5 bodu na minutu × 720 minut)
        break;
      case "day":
        this.maxPoints = 288; // pro 1 den (5-minutové intervaly)
        break;
      case "week":
        this.maxPoints = 336; // pro 1 týden (30-minutové intervaly)
        break;
      case "month":
        this.maxPoints = 360; // pro 1 měsíc (2-hodinové intervaly)
        break;
      default:
        this.maxPoints = 60; // výchozí
    }
  },

  /**
   * Get current UUID
   *
   * @returns {string} - Current UUID
   */
  getCurrentUuid: function () {
    return this.currentUuid;
  },

  /**
   * Get current time range
   *
   * @returns {string} - Current time range
   */
  getCurrentTimeRange: function () {
    return this.currentTimeRange;
  },
};

export default LiveUpdates;
