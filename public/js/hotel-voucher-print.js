/**
 * Scale the hotel voucher so it fits on one A4 page when printing.
 */
(function () {
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
        var available = Math.floor(page.clientHeight - padding) - 1;
        if (available <= 0) {
            return;
        }

        // Reset, measure, then shrink if content is taller than the page
        page.style.setProperty('--hv-document-height', '0px');
        var scale = 1;

        for (var i = 0; i < 8; i++) {
            page.style.setProperty('--hv-print-scale', scale);
            var height = Math.max(doc.offsetHeight, doc.scrollHeight);
            if (height * scale <= available + 0.5) {
                break;
            }
            scale = scale * (available / (height * scale));
        }

        page.style.setProperty('--hv-document-height', (available / scale) + 'px');
        scaleBox.style.height = available + 'px';
    }

    function runFit(thenPrint) {
        fitToA4();
        requestAnimationFrame(function () {
            fitToA4();
            if (thenPrint) {
                window.print();
            }
        });
    }

    function onReady() {
        var shouldAutoPrint = document.querySelector('[data-auto-print="true"]');
        runFit(!!shouldAutoPrint);
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
