/**
 * Sparkline chart functionality for server-metrics plugin
 */
import Ajax from "../utils/ajax.js";

const Sparklines = {
  /**
   * Load and draw all sparklines
   *
   * @param {string} period - Časový rozsah (30min, hour, 6hours, atd.)
   */
  loadAll: function (period) {
    console.log("Načítání sparkline grafů pro období:", period);

    // Nejprve zrušit všechny existující grafy
    jQuery(".sparkline").each(function () {
      try {
        const chart = Chart.getChart(this);
        if (chart) {
          chart.destroy();
        }
      } catch (e) {
        // Ignorovat chyby
      }
    });

    // Nyní načíst nové grafy pro každý canvas
    jQuery(".sparkline").each(function () {
      const sparkline = this;
      const uuid = jQuery(sparkline).data("uuid");
      const type = jQuery(sparkline).data("type");

      if (!uuid || !type) {
        console.warn("Chybí UUID nebo typ pro sparkline:", sparkline);
        return;
      }

      // Nastavit výšku a šířku přímo v HTML
      sparkline.style.width = "100%";
      sparkline.style.height = "100%";
      sparkline.style.display = "block";

      // Nastavit výšku a šířku také pro container
      jQuery(sparkline).parent(".sparkline-container").css({
        height: "30px",
        width: "100%",
        display: "block",
      });

      // Načíst data pro graf s aktuálním časovým rozsahem
      Ajax.loadSparklineData(
        uuid,
        period, // Předáváme časový rozsah
        function (data) {
          Sparklines.create(sparkline, data, type);
        },
        function (error) {
          console.warn(`Chyba při načítání dat pro sparkline ${uuid}:`, error);
        }
      );
    });
  },

  /**
   * Create sparkline chart
   *
   * @param {HTMLElement} canvas - Canvas element
   * @param {Array} data - Sparkline data
   * @param {string} type - Chart type (cpu, memory, io_read, io_write)
   */
  create: function (canvas, data, type) {
    // Otočíme pole dat před vykreslením (nejnovější vpravo)
    data.reverse();
    let values = [];
    let color = "";

    // Určit barvu grafu
    switch (type) {
      case "cpu":
        values = data.map((item) => parseFloat(item.cpu_usage));
        color = "#36a2eb"; // modrá
        break;
      case "memory":
        values = data.map((item) => parseFloat(item.mem_usage));
        color = "#ff6384"; // červená
        break;
      case "io_read":
        values = data.map((item) => parseFloat(item.io_read_rate));
        color = "#4bc0c0"; // tyrkysová
        break;
      case "io_write":
        values = data.map((item) => parseFloat(item.io_write_rate));
        color = "#9966ff"; // fialová
        break;
      default:
        console.warn("Neznámý typ grafu:", type);
        return;
    }

    if (values.length === 0) {
      console.warn("Žádné hodnoty pro typ:", type);
      return;
    }

    try {
      // Vytvořit nový graf
      const ctx = canvas.getContext("2d");
      new Chart(ctx, {
        type: "line",
        data: {
          labels: Array(values.length).fill(""),
          datasets: [
            {
              data: values,
              borderColor: color,
              backgroundColor: color.replace("1)", "0.1)"),
              borderWidth: 2,
              fill: false,
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
            tooltip: { enabled: false },
          },
          scales: {
            x: { display: false, grid: { display: false } },
            y: { display: false, grid: { display: false }, min: 0 },
          },
          elements: {
            line: { tension: 0.4 },
          },
          animation: { duration: 0 },
        },
      });
    } catch (e) {
      console.error("Chyba při vytváření sparkline grafu:", e);
    }
  },
};

export default Sparklines;
