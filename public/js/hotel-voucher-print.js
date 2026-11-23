/**
 * Scale the hotel voucher so guests, stays, transport AND the address footer
 * all fit on a single A4 page when printing.
 */
(function () {
    var SAFETY_PX = 4;
    var MIN_SCALE = 0.55;

    function fitToA4() {
        var page = document.getElementById('hv-print-page');
        var scaleBox = document.getElementById('hv-print-scale');
        if (!page || !scaleBox) {
            return;
        }

        var doc = scaleBox.querySelector('.hotel-voucher-doc');
        if (!doc) {
            return;
        }

        var styles = window.getComputedStyle(page);
        var padding = parseFloat(styles.paddingTop) + parseFloat(styles.paddingBottom);
        var available = Math.floor(page.clientHeight - padding) - SAFETY_PX;
        if (available <= 0) {
            return;
        }

        // Measure natural (unscaled) height — no stretch, no scale
        page.style.setProperty('--hv-print-scale', '1');
        page.style.setProperty('--hv-document-height', 'auto');
        scaleBox.style.height = 'auto';

        // Force layout so scrollHeight is accurate
        void doc.offsetHeight;

        var scale = 1;
        for (var i = 0; i < 10; i++) {
            page.style.setProperty('--hv-print-scale', String(scale));
            void doc.offsetHeight;

            var height = Math.max(doc.scrollHeight, doc.offsetHeight);
            if (height <= 0) {
                break;
            }

            if (height * scale <= available) {
                break;
            }

            scale = Math.max(MIN_SCALE, available / height);
        }

        // Final guard: if still slightly over (rounding / fonts), nudge down
        page.style.setProperty('--hv-print-scale', String(scale));
        void doc.offsetHeight;
        var finalHeight = Math.max(doc.scrollHeight, doc.offsetHeight);
        if (finalHeight * scale > available) {
            scale = Math.max(MIN_SCALE, available / finalHeight);
            page.style.setProperty('--hv-print-scale', String(scale));
            void doc.offsetHeight;
            finalHeight = Math.max(doc.scrollHeight, doc.offsetHeight);
        }

        // Only stretch short documents so the footer sits at the page bottom.
        // Dense (scaled) docs keep natural height so the address is never clipped.
        if (scale >= 0.999 && finalHeight < available) {
            page.style.setProperty('--hv-document-height', available / scale + 'px');
        } else {
            page.style.setProperty('--hv-document-height', 'auto');
        }

        scaleBox.style.height = available + 'px';
    }

    function runFit(thenPrint) {
        fitToA4();
        requestAnimationFrame(function () {
            fitToA4();
            // Fonts / QR can settle one frame later
            requestAnimationFrame(function () {
                fitToA4();
                if (thenPrint) {
                    window.print();
                }
            });
        });
    }

    function onReady() {
        var shouldAutoPrint = document.querySelector('[data-auto-print="true"]');
        var start = function () {
            runFit(!!shouldAutoPrint);
        };

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(start).catch(start);
        } else {
            start();
        }
    }

    if (document.readyState === 'complete') {
        onReady();
    } else {
        window.addEventListener('load', onReady);
    }

    window.addEventListener('beforeprint', fitToA4);
    window.addEventListener('afterprint', fitToA4);
    window.addEventListener('resize', fitToA4);
})();
