/**
 * AJAX utility functions for server-metrics plugin
 */

const Ajax = {
  /**
   * Load website metrics data
   *
   * @param {string} uuid - Website UUID
   * @param {string} period - Time period (hour, day, week, month)
   * @param {string} time_range - Time range (15min, 30min, 1hour, 2hours)
   * @param {Function} successCallback - Success callback function
   * @param {Function} errorCallback - Error callback function
   */
  loadWebsiteMetrics: function (
    uuid,
    period,
    time_range,
    successCallback,
    errorCallback
  ) {
    jQuery.ajax({
      url: serverMetricsData.ajaxUrl,
      data: {
        action: "get_website_metrics",
        nonce: serverMetricsData.nonce,
        uuid: uuid,
        period: period,
        time_range: time_range || "15min",
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          successCallback(response.data);
        } else {
          errorCallback(serverMetricsData.strings.failedToLoadData);
        }
      },
      error: function () {
        errorCallback(serverMetricsData.strings.serverError);
      },
    });
  },

  /**
   * Load live metrics updates
   *
   * @param {string} uuid - Website UUID
   * @param {string} lastTimestamp - Last timestamp for incremental updates
   * @param {Function} successCallback - Success callback function
   * @param {Function} errorCallback - Error callback function
   */
  loadLiveMetrics: function (
    uuid,
    lastTimestamp,
    successCallback,
    errorCallback
  ) {
    jQuery.ajax({
      url: serverMetricsData.ajaxUrl,
      data: {
        action: "get_website_metrics_live",
        nonce: serverMetricsData.nonce,
        uuid: uuid,
        last_timestamp: lastTimestamp,
      },
      dataType: "json",
      success: function (response) {
        if (response.success && response.data && response.data.length > 0) {
          successCallback(response.data);
        } else {
          console.log("Žádná nová data pro aktualizaci", response);
        }
      },
      error: function (xhr, status, error) {
        console.error("Chyba při aktualizaci živých grafů:", error);
        if (errorCallback) errorCallback(error);
      },
    });
  },

  /**
   * Load sparkline data for graphs
   *
   * @param {string} uuid Website UUID
   * @param {string} period Časový rozsah (30min, hour, 6hours, atd.)
   * @param {Function} successCallback Success callback function
   * @param {Function} errorCallback Error callback function
   */
  loadSparklineData: function (uuid, period, successCallback, errorCallback) {
    jQuery.ajax({
      url: serverMetricsData.ajaxUrl,
      data: {
        action: "get_sparkline_data",
        nonce: serverMetricsData.nonce,
        uuid: uuid,
        period: period || "day", // Výchozí hodnota je den
      },
      dataType: "json",
      success: function (response) {
        if (response.success && response.data && response.data.length > 0) {
          successCallback(response.data);
        } else {
          console.warn(`Žádná data pro sparkline ${uuid}:`, response);
          if (errorCallback) errorCallback(response);
        }
      },
      error: function (xhr, status, error) {
        console.error(`Chyba při načítání dat pro sparkline ${uuid}:`, error);
        if (errorCallback) errorCallback(error);
      },
    });
  },

  /**
   * Load average historical data
   *
   * @param {string} period - Time period
   * @param {Function} successCallback - Success callback function
   * @param {Function} errorCallback - Error callback function
   */
  loadAverageData: function (period, successCallback, errorCallback) {
    jQuery.ajax({
      url: serverMetricsData.ajaxUrl,
      data: {
        action: "get_average_historical_data",
        nonce: serverMetricsData.nonce,
        period: period,
      },
      dataType: "json",
      success: function (response) {
        if (response.success && response.data && response.data.length > 0) {
          successCallback(response.data);
        } else {
          console.warn("Nelze načíst průměrná data:", response);
          if (errorCallback) errorCallback(response);
        }
      },
      error: function (xhr, status, error) {
        console.error("Ajax chyba při načítání průměrných dat:", error);
        if (errorCallback) errorCallback(error);
      },
    });
  },
};

export default Ajax;
