(function () {
    var meta = document.querySelector('meta[name="mai-seo-analytics"]');
    if (!meta || meta.getAttribute("data-enabled") === "0" || meta.getAttribute("data-enabled") === "false") {
        return;
    }
    if (navigator.webdriver) {
        return;
    }

    var pageId = parseInt(meta.getAttribute("data-page-id") || "0", 10);
    var endpoint = meta.getAttribute("data-endpoint") || "";
    if (!pageId || endpoint.indexOf("/mai-seo/analytics") === -1) {
        return;
    }

    var token = (window.crypto && crypto.randomUUID && crypto.randomUUID()) || (Date.now().toString(16) + Math.random().toString(16).slice(2, 10));
    var started = Date.now();
    var durationSent = false;

    function send(duration) {
        var body = JSON.stringify({
            pageId: pageId,
            languageId: parseInt(meta.getAttribute("data-language-id") || "0", 10),
            locale: meta.getAttribute("data-locale") || "",
            path: window.location.pathname + window.location.search,
            title: document.title || "",
            referrer: document.referrer || "",
            screen: window.screen && screen.width ? screen.width : 0,
            hitToken: token,
            duration: duration
        });
        if (navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, new Blob([body], { type: "application/json" }));
            return;
        }
        fetch(endpoint, {
            method: "POST",
            body: body,
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            keepalive: true
        });
    }

    function flush() {
        if (durationSent) {
            return;
        }
        durationSent = true;
        send(Math.min(1800, Math.max(0, Math.round((Date.now() - started) / 1000))));
    }

    send(0);
    document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === "hidden") {
            flush();
        }
    });
    window.addEventListener("pagehide", flush);
}());
