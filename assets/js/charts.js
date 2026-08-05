/* ============================================================
   Harmonised dashboard charts
   City of Kigali - Digital Inspection Platform

   Loaded AFTER app.js. Redefines the three chart renderers so
   they share one visual language. app.js itself is untouched -
   remove the script tag and the originals come back.
   ============================================================ */

(function () {
    'use strict';

    var PLOT_HEIGHT = 190;

    function el(id) { return document.getElementById(id); }

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /** Compliance colour band, used identically in every chart. */
    function band(pct) {
        if (pct >= 70) return 'green';
        if (pct >= 50) return 'gold';
        return 'red';
    }

    /** Pick a round axis maximum so gridlines land on sensible numbers. */
    function niceMax(value) {
        if (value <= 5) return 5;
        var mag = Math.pow(10, Math.floor(Math.log10(value)));
        var n = value / mag;
        var step = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10;
        return step * mag;
    }

    function gridlines(max, opts) {
        opts = opts || {};
        var steps = opts.steps || 4;
        var suffix = opts.suffix || '';
        var threshold = opts.threshold;
        var out = '<div class="chart-grid">';
        for (var i = 0; i <= steps; i++) {
            var val = Math.round((max / steps) * i);
            var top = 100 - (i / steps) * 100;
            out += '<span style="top:' + top + '%" data-v="' + val + suffix + '"></span>';
        }
        if (threshold) {
            out += '<span class="threshold" style="top:' +
                   (100 - (threshold / max) * 100) + '%" data-v="' +
                   threshold + suffix + '"></span>';
        }
        return out + '</div>';
    }

    function empty(title, note) {
        return '<div class="chart-empty"><strong>' + esc(title) + '</strong><span>' +
               esc(note) + '</span></div>';
    }

    function height(value, max) {
        if (!value) return 0;
        return Math.max(3, Math.round((value / max) * PLOT_HEIGHT));
    }

    /* --------------------------------------------------------
       1. Monthly volume - grouped columns, building vs petrol
       -------------------------------------------------------- */
    window.renderMonthlyBreakdown = function (breakdown) {
        var c = el('monthly-breakdown');
        if (!c) return;

        if (!breakdown || !breakdown.length) {
            c.innerHTML = empty('No inspection volume yet',
                                'Data appears once inspections are recorded');
            return;
        }

        var months = {};
        breakdown.forEach(function (item) {
            var m = item.month;
            if (!months[m]) months[m] = { building: 0, petrol: 0 };
            var n = parseInt(item.total, 10) || 0;
            if (item.entity_type === 'building') months[m].building += n;
            else if (item.entity_type === 'petrol') months[m].petrol += n;
        });

        var keys = Object.keys(months).sort().slice(-12);
        var peak = 1;
        keys.forEach(function (m) {
            peak = Math.max(peak, months[m].building, months[m].petrol);
        });
        var max = niceMax(peak);

        var bars = '', labels = '';
        keys.forEach(function (m) {
            var b = months[m].building, p = months[m].petrol;
            bars += '<div class="chart-col">' +
                '<div class="chart-bar blue"  style="height:' + height(b, max) + 'px"' +
                    (b ? ' data-v="' + b + '"' : '') + ' title="Building: ' + b + '"></div>' +
                '<div class="chart-bar green" style="height:' + height(p, max) + 'px"' +
                    (p ? ' data-v="' + p + '"' : '') + ' title="Petrol: ' + p + '"></div>' +
                '</div>';
            labels += '<span>' + esc(m) + '</span>';
        });

        c.innerHTML =
            '<div class="chart">' +
                '<div class="chart-plot">' + gridlines(max) +
                    '<div class="chart-bars">' + bars + '</div>' +
                '</div>' +
                '<div class="chart-labels">' + labels + '</div>' +
                '<div class="chart-legend">' +
                    '<span><i class="blue"></i>Building</span>' +
                    '<span><i class="green"></i>Petrol station</span>' +
                '</div>' +
            '</div>';
    };

    /* --------------------------------------------------------
       2. Compliance trend - columns banded by score, 70% target
       -------------------------------------------------------- */
    window.renderMonthlyCompliance = function (compliance) {
        var c = el('monthly-compliance');
        if (!c) return;

        if (!compliance || !compliance.length) {
            c.innerHTML = empty('No compliance data yet',
                                'Trends appear once inspections are scored');
            return;
        }

        var rows = compliance.slice(-12);
        var bars = '', labels = '';

        rows.forEach(function (item) {
            var rate = Math.round(parseFloat(item.compliance_rate) || 0);
            bars += '<div class="chart-col">' +
                '<div class="chart-bar ' + band(rate) + '" style="height:' +
                height(rate, 100) + 'px" data-v="' + rate + '%" title="' + rate + '%"></div>' +
                '</div>';
            labels += '<span>' + esc(item.month) + '</span>';
        });

        c.innerHTML =
            '<div class="chart">' +
                '<div class="chart-plot">' +
                    gridlines(100, { suffix: '%', threshold: 70 }) +
                    '<div class="chart-bars">' + bars + '</div>' +
                '</div>' +
                '<div class="chart-labels">' + labels + '</div>' +
                '<div class="chart-legend">' +
                    '<span><i class="green"></i>70% and above</span>' +
                    '<span><i class="gold"></i>50 to 69%</span>' +
                    '<span><i class="red"></i>Below 50%</span>' +
                '</div>' +
            '</div>';
    };

    /* --------------------------------------------------------
       3. District performance - horizontal ranking
       -------------------------------------------------------- */
    window.renderDistrictPerformance = function (districts) {
        var c = el('district-performance');
        if (!c) return;

        districts = Array.isArray(districts) ? districts.slice() : [];

        // Always show all three districts, even at zero.
        ['Gasabo', 'Kicukiro', 'Nyarugenge'].forEach(function (name) {
            if (!districts.some(function (d) { return d.district === name; })) {
                districts.push({ district: name, total_inspections: 0, avg_compliance: 0 });
            }
        });

        districts.sort(function (a, b) {
            return (parseFloat(b.avg_compliance) || 0) - (parseFloat(a.avg_compliance) || 0);
        });

        var html = '';
        districts.forEach(function (d) {
            var total = parseInt(d.total_inspections, 10) || 0;
            var comp  = Math.round(parseFloat(d.avg_compliance) || 0);
            var cls   = total ? band(comp) : 'gold';

            html +=
                '<div class="rank-row">' +
                    '<div class="rank-name">' + esc(d.district) +
                        '<small>' + total + (total === 1 ? ' inspection' : ' inspections') + '</small>' +
                    '</div>' +
                    '<div class="rank-val ' + cls + '">' + (total ? comp + '%' : '&mdash;') + '</div>' +
                    '<div class="rank-track">' +
                        '<div class="rank-fill ' + cls + '" style="width:' + (total ? comp : 0) + '%"></div>' +
                    '</div>' +
                '</div>';
        });

        c.innerHTML = html;
    };

})();