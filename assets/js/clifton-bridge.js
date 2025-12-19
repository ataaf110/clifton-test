/* ==========================================
 * CLIFTON Bridge (حفظ نام فایل برای سازگاری)
 * - mobile از ?m=... یا storage
 * - فراخوانی api.php?action=get_clifton_results
 * - ذخیره در localStorage
 * - صدا زدن window.displayResults() اگر موجود باشد
 * ========================================== */

(function bootstrapConfig() {
  function fallback(v, d) {
    return v === undefined || v === null || v === "" ? d : v;
  }

  // Guess base URL از مسیر اسکریپت
  var baseUrl = (function () {
    try {
      var s =
        document.currentScript ||
        (function () {
          var a = document.getElementsByTagName("script");
          return a[a.length - 1];
        })();
      var u = new URL(s.src, location.href);
      return u.origin + u.pathname.split("/assets/")[0];
    } catch (e) {
      return (
        location.origin +
        (location.pathname.split("/").slice(0, -1).join("/") || "")
      );
    }
  })();

  var cfg = (window.CLIFTON = window.CLIFTON || {});
  cfg.API = fallback(cfg.API, baseUrl + "/api/api.php");
  cfg.TEST_URL = fallback(cfg.TEST_URL, baseUrl + "/index.php");
  cfg.RESULTS_URL = fallback(cfg.RESULTS_URL, baseUrl + "/results.php");
  cfg.HOME_URL = fallback(cfg.HOME_URL, location.origin);
  cfg.TEST_ID = fallback(cfg.TEST_ID, "clifton-test-1");
  cfg.nonce = fallback(cfg.nonce, "standalone");

  // آبجکت سراسری برای فرانت
  window.cliftonAjax = {
    ajax_url: cfg.API,
    nonce: cfg.nonce,
    test_page_url: cfg.TEST_URL,
    results_page_url: cfg.RESULTS_URL,
    home_url: cfg.HOME_URL,
    test_id: cfg.TEST_ID,
  };

  // برای سازگاری با کد قدیمی:
  window.mbtiAjax = window.cliftonAjax;

  // helper
  window.cliftonFetch = function (action, data) {
    var url = window.cliftonAjax.ajax_url;
    url +=
      (url.indexOf("?") === -1 ? "?" : "&") +
      "action=" +
      encodeURIComponent(action);
    return fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data || {}),
      credentials: "include",
    }).then(function (res) {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    });
  };
})();

/* -------- Results loader (plain m) -------- */
(function () {
  function normalizeMobile(s) {
    if (!s) return "";
    return String(s)
      .trim()
      .replace(/^(\+98|0)/, "")
      .replace(/\D+/g, "")
      .slice(-11);
  }
  function safeGet(k) {
    try {
      return sessionStorage.getItem(k) || localStorage.getItem(k) || "";
    } catch (e) {
      return "";
    }
  }
  function getMobile() {
    const path = window.location.pathname;
    if (path.split('/').filter(Boolean).pop() === "results-test") {
      return "test";
    }
    var m = new URLSearchParams(location.search).get("m");
    if (m) return normalizeMobile(m);
    m =
      safeGet("clifton_mobile") ||
      safeGet("cliftonMobile") ||
      safeGet("mbti_mobile") ||
      safeGet("mbtiMobile");
    if (m) return normalizeMobile(m);
    if (window.CLIFTON && CLIFTON.CURRENT_USER_LOGIN)
      return normalizeMobile(CLIFTON.CURRENT_USER_LOGIN);
    return "";
  }
  function minimalRender(obj) {
    var el = document.querySelector("#personality-type");
    if (el) el.textContent = obj.profile || "";
    var sd = document.querySelector("#summary-description");
    if (sd) sd.textContent = obj.description || "";
  }
  function onResultsReady(obj, mobile) {
    try {
      if (mobile) {
        sessionStorage.setItem("clifton_mobile", mobile);
        localStorage.setItem("clifton_mobile", mobile);
        localStorage.setItem("cliftonMobile", mobile);
      }
      localStorage.setItem("cliftonResults", JSON.stringify(obj));
    } catch (e) {}
    if (typeof window.displayResults === "function") window.displayResults();
    else minimalRender(obj);
  }
  function mergeForRender(server) {
    return {
      degrees: server.degrees || {},
      userInfo: server.user_info || {},
      selectedTestOption: server.test_option || "free",
      created_at: server.created_at || "",
    };
  }
  function loadResults() {
    var mobile = getMobile();
    if (!mobile) {
      console.error("شماره موبایل پیدا نشد.");
      return;
    }
    window
      .cliftonFetch("get_clifton_results", { mobile_number: mobile })
      .then(function (resp) {
        if (!resp || resp.success !== true)
          throw new Error((resp && resp.data) || "پاسخ نامعتبر از سرور");
        var pack = resp.data || {};
        var raw = pack.results || pack;
        if (!raw) throw new Error("نتیجه‌ای یافت نشد.");
        var merged = mergeForRender(raw);
        if (!merged.userInfo) merged.userInfo = {};
        if (!merged.userInfo.mobile_number)
          merged.userInfo.mobile_number = mobile;
        onResultsReady(merged, mobile);
      })
      .catch(function (err) {
        console.error(
          "get_clifton_results error:",
          err && err.message ? err.message : err
        );
      });
  }
  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", loadResults);
  else loadResults();
})();
