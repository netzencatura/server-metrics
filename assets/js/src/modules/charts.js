/**
 * Chart functionality for server-metrics plugin
 */
const Charts = {
  // Referencované grafy pro pozdější aktualizace
  cpuChart: null,
  memoryChart: null,
  ioReadChart: null,
  ioWriteChart: null,
  avgCpuChart: null,
  avgRamChart: null,

  /**
   * Create a new chart
   *
   * @param {string} canvasId - Canvas element ID
   * @param {Array} labels - Chart labels
   * @param {Array} data - Chart data
   * @param {string} label - Chart label
   * @param {string} backgroundColor - Chart background color
   * @param {string} fontColor - Chart font color
   * @param {string} gridColor - Chart grid color
   * @returns {Chart} - Chart instance
   */
  createChart: function (
    canvasId,
    labels,
    data,
    label,
    backgroundColor,
    fontColor,
    gridColor
  ) {
    const ctx = document.getElementById(canvasId).getContext("2d");
    return new Chart(ctx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: label,
            data: data,
            backgroundColor: backgroundColor,
            borderColor: backgroundColor.replace("0.5", "1"),
            borderWidth: 1,
            fill: true,
            tension: 0.4,
          },
        ],
      },
      options: {
        responsive: true,
        animation: {
          duration: 500,
          easing: "linear",
        },
        scales: {
          x: {
            display: true,
            title: {
              display: true,
              text: this.getTimeRangeLabel(),
              color: fontColor,
            },
            grid: {
              color: gridColor,
            },
            ticks: {
              color: fontColor,
              maxRotation: 45,
              minRotation: 45,
              autoSkip: true,
              maxTicksLimit: 2, // Změněno z 10 na 2, aby se zobrazoval pouze první a poslední popisek
              callback: function (value, index, values) {
                // Zobrazit pouze první a poslední popisek
                if (index === 0 || index === values.length - 1) {
                  return this.getLabelForValue(value);
                }
                return "";
              },
            },
          },
          y: {
            display: true,
            title: {
              display: true,
              text: label,
              color: fontColor,
            },
            beginAtZero: true,
            grid: {
              color: gridColor,
            },
            ticks: {
              color: fontColor,
            },
          },
        },
        plugins: {
          legend: {
            labels: {
              color: fontColor,
            },
          },
          tooltip: {
            mode: "index",
            intersect: false,
          },
        },
      },
    });
  },

  /**
   * Update chart data with new points
   *
   * @param {Chart} chart - Chart instance
   * @param {Array} labels - New chart labels
   * @param {Array} data - New chart data
   * @param {number} maxPoints - Maximum number of points to show
   */
  addPointsToChart: function (chart, labels, data, maxPoints) {
    if (!chart || !labels || !data || labels.length === 0) return;

    // Přidat nové body
    for (let i = 0; i < labels.length; i++) {
      chart.data.labels.push(labels[i]);
      chart.data.datasets[0].data.push(data[i]);

      // Omezit počet bodů na maxPoints
      if (chart.data.labels.length > maxPoints) {
        chart.data.labels.shift(); // Odstranit první bod
        chart.data.datasets[0].data.shift();
      }
    }

    // Aktualizovat graf s animací
    chart.update({
      duration: 500,
      easing: "linear",
    });
  },

  /**
   * Update all detail charts with new data
   *
   * @param {Array} data - Chart data
   */
  updateDetailCharts: function (data) {
    if (!data || data.length === 0) return;

    // Oprava pro zobrazení dat zleva doprava (nejnovější vpravo)
    // Otočíme pole dat před vykreslením
    data.reverse();

    // Prepare data for charts
    const labels = data.map((item) =>
      new Date(item.timestamp).toLocaleString()
    );
    const cpuData = data.map((item) => item.cpu_usage);
    const memoryData = data.map((item) => item.mem_usage);
    const ioReadData = data.map((item) => item.io_read_rate);
    const ioWriteData = data.map((item) => item.io_write_rate);

    // Destroy existing charts
    if (this.cpuChart) this.cpuChart.destroy();
    if (this.memoryChart) this.memoryChart.destroy();
    if (this.ioReadChart) this.ioReadChart.destroy();
    if (this.ioWriteChart) this.ioWriteChart.destroy();

    // Zjistit, zda je aktivní tmavý režim
    const isDarkMode = jQuery("body").hasClass("dark-mode");
    const fontColor = isDarkMode ? "#e0e0e0" : "#666";
    const gridColor = isDarkMode
      ? "rgba(255, 255, 255, 0.1)"
      : "rgba(0, 0, 0, 0.1)";

    // Create new charts with appropriate options
    this.cpuChart = this.createChart(
      "cpu-chart",
      labels,
      cpuData,
      serverMetricsData.strings.cpuUsage,
      "rgba(54, 162, 235, 0.5)",
      fontColor,
      gridColor
    );
    this.memoryChart = this.createChart(
      "memory-chart",
      labels,
      memoryData,
      serverMetricsData.strings.memoryUsage,
      "rgba(255, 99, 132, 0.5)",
      fontColor,
      gridColor
    );
    this.ioReadChart = this.createChart(
      "io-read-chart",
      labels,
      ioReadData,
      serverMetricsData.strings.ioRead,
      "rgba(75, 192, 192, 0.5)",
      fontColor,
      gridColor
    );
    this.ioWriteChart = this.createChart(
      "io-write-chart",
      labels,
      ioWriteData,
      serverMetricsData.strings.ioWrite,
      "rgba(153, 102, 255, 0.5)",
      fontColor,
      gridColor
    );
  },

  /**
   * Create and update average charts
   *
   * @param {Array} data - Chart data
   */
  drawAverageCharts: function (data) {
    // Otočíme pole dat před vykreslením (nejnovější vpravo)
    data.reverse();

    // Destroy existing charts
    if (this.avgCpuChart) {
      this.avgCpuChart.destroy();
      this.avgCpuChart = null;
    }

    if (this.avgRamChart) {
      this.avgRamChart.destroy();
      this.avgRamChart = null;
    }

    // Kontrola dat
    if (!data || data.length === 0) {
      console.error("Žádná data pro průměrné grafy");
      return;
    }

    // Zpracování dat
    const cpuData = data.map((item) => parseFloat(item.avg_cpu));
    const ramData = data.map((item) => parseFloat(item.avg_mem));
    const labels = data.map((item) =>
      new Date(item.hour_timestamp).toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      })
    );

    // Zjistit, zda je aktivní tmavý režim
    const isDarkMode = jQuery("body").hasClass("dark-mode");
    const borderColor = isDarkMode ? "#64b5f6" : "#0073aa";
    const backgroundColor = isDarkMode
      ? "rgba(100, 181, 246, 0.2)"
      : "rgba(0, 115, 170, 0.1)";

    // CPU chart
    const cpuCanvas = document.getElementById("avg-cpu-chart");
    if (cpuCanvas) {
      const cpuCtx = cpuCanvas.getContext("2d");
      this.avgCpuChart = new Chart(cpuCtx, {
        type: "line",
        data: {
          labels: labels,
          datasets: [
            {
              data: cpuData,
              borderColor: borderColor,
              backgroundColor: backgroundColor,
              borderWidth: 2,
              fill: true,
              tension: 0.4,
              pointRadius: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              enabled: true,
              mode: "index",
              intersect: false,
              callbacks: {
                label: function (context) {
                  return context.raw.toFixed(1) + "%";
                },
              },
            },
          },
          scales: {
            x: { display: false },
            y: {
              display: false,
              suggestedMin: 0,
              suggestedMax: Math.max(...cpuData, 1) * 1.2,
            },
          },
          animation: { duration: 500 },
        },
      });
    } else {
      console.error("Canvas element 'avg-cpu-chart' not found");
    }

    // RAM chart
    const ramCanvas = document.getElementById("avg-ram-chart");
    if (ramCanvas) {
      const ramCtx = ramCanvas.getContext("2d");
      this.avgRamChart = new Chart(ramCtx, {
        type: "line",
        data: {
          labels: labels,
          datasets: [
            {
              data: ramData,
              borderColor: borderColor,
              backgroundColor: backgroundColor,
              borderWidth: 2,
              fill: true,
              tension: 0.4,
              pointRadius: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              enabled: true,
              mode: "index",
              intersect: false,
              callbacks: {
                label: function (context) {
                  return context.raw.toFixed(1) + "%";
                },
              },
            },
          },
          scales: {
            x: { display: false },
            y: {
              display: false,
              suggestedMin: 0,
              suggestedMax: Math.max(...ramData, 1) * 1.2,
            },
          },
          animation: { duration: 500 },
        },
      });
    } else {
      console.error("Canvas element 'avg-ram-chart' not found");
    }
  },

  /**
   * Get time range label based on selected filter
   *
   * @returns {string} - Formatted time range label
   */
  getTimeRangeLabel: function () {
    const selectedPeriod = jQuery("#period-filter").val() || "day";
    let periodText = "";

    // Převod hodnoty na uživatelsky přívětivý text
    switch (selectedPeriod) {
      case "30min":
        periodText =
          serverMetricsData.strings.time +
          " (" +
          serverMetricsData.strings.last30min +
          ")";
        break;
      case "hour":
        periodText =
          serverMetricsData.strings.time +
          " (" +
          serverMetricsData.strings.lastHour +
          ")";
        break;
      case "6hours":
        periodText =
          serverMetricsData.strings.time +
          " (" +
          serverMetricsData.strings.last6Hours +
          ")";
        break;
      case "12hours":
        periodText =
          serverMetricsData.strings.time +
          " (" +
          serverMetricsData.strings.last12Hours +
          ")";
        break;
      case "day":
        periodText =
          serverMetricsData.strings.time +
          " (" +
          serverMetricsData.strings.lastDay +
          ")";
        break;
      case "week":
        periodText =
          serverMetricsData.strings.time +
          " (" +
          serverMetricsData.strings.lastWeek +
          ")";
        break;
      case "month":
        periodText =
          serverMetricsData.strings.time +
          " (" +
          serverMetricsData.strings.lastMonth +
          ")";
        break;
      default:
        periodText = serverMetricsData.strings.time;
    }

    return periodText;
  },
};

export default Charts;
