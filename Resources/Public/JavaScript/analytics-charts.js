(function () {
    'use strict';

    const tooltipId = 'mai-seo-analytics-tooltip';

    function tooltip() {
        let element = document.getElementById(tooltipId);
        if (!element) {
            element = document.createElement('div');
            element.id = tooltipId;
            element.className = 'tx-analytics-traffic-graph-tooltip';
            element.hidden = true;
            document.body.appendChild(element);
        }

        return element;
    }

    function show(rect) {
        const raw = rect.getAttribute('data-tooltip');
        if (!raw) {
            return;
        }

        let data;
        try {
            data = JSON.parse(raw);
        } catch (error) {
            return;
        }

        const box = tooltip();
        const rows = (data.metrics || []).map(function (metric) {
            return '<div class="tx-analytics-traffic-graph-tooltip-row">'
                + '<span class="tx-analytics-traffic-graph-tooltip-dot" data-tone="' + metric.tone + '"></span>'
                + '<span>' + metric.label + '</span>'
                + '<span class="tx-analytics-traffic-graph-tooltip-value">' + metric.value + '</span>'
                + '</div>';
        }).join('');
        box.innerHTML = '<div class="tx-analytics-traffic-graph-tooltip-date">' + data.date + '</div>' + rows;
        box.hidden = false;

        const host = rect.getBoundingClientRect();
        const width = box.offsetWidth;
        const left = Math.min(window.innerWidth - width - 8, host.left + host.width / 2);
        box.style.left = Math.max(8, left) + 'px';
        box.style.top = Math.max(8, host.top - box.offsetHeight - 8) + 'px';

        const svg = rect.closest('svg');
        const line = svg ? svg.querySelector('.tx-analytics-sparkline-hover-line') : null;
        if (line) {
            line.setAttribute('x1', rect.getAttribute('data-x'));
            line.setAttribute('x2', rect.getAttribute('data-x'));
            line.setAttribute('visibility', 'visible');
        }

        const area = svg ? svg.closest('.tx-analytics-traffic-graph-chart-area') : null;
        if (!area || !svg) {
            return;
        }

        let dots = area.querySelector('.tx-analytics-hover-dot-layer');
        if (!dots) {
            dots = document.createElement('div');
            dots.className = 'tx-analytics-hover-dot-layer';
            area.appendChild(dots);
        }
        const bounds = svg.getBoundingClientRect();
        const tones = (svg.getAttribute('data-tones') || '').split(',');
        dots.innerHTML = tones.map(function (tone, index) {
            const y = parseFloat(rect.getAttribute('data-y-' + index) || '0');
            const x = parseFloat(rect.getAttribute('data-x') || '0');
            return '<span class="tx-analytics-sparkline-hover-dot" data-tone="' + tone + '" style="left:'
                + ((x / 100) * bounds.width) + 'px;top:' + ((y / 32) * bounds.height) + 'px"></span>';
        }).join('');
    }

    function hide() {
        const box = document.getElementById(tooltipId);
        if (box) {
            box.hidden = true;
        }
        document.querySelectorAll('.tx-analytics-sparkline-hover-line').forEach(function (line) {
            line.setAttribute('visibility', 'hidden');
        });
        document.querySelectorAll('.tx-analytics-hover-dot-layer').forEach(function (layer) {
            layer.innerHTML = '';
        });
    }

    document.addEventListener('pointerover', function (event) {
        const rect = event.target.closest('.tx-analytics-sparkline-point-tooltip');
        if (rect) {
            show(rect);
        }
    });

    document.addEventListener('pointerout', function (event) {
        const rect = event.target.closest('.tx-analytics-sparkline-point-tooltip');
        if (rect) {
            hide();
        }
    });
}());
