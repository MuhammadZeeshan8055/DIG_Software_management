/**
 * Scale the hotel voucher to one A4 page and pin the address footer
 * flush to the bottom edge of the page.
 */
(function () {
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

        // Let flex layout size the scale box to the remaining page height
        scaleBox.style.height = '';
        page.style.setProperty('--hv-print-scale', '1');
        page.style.setProperty('--hv-document-height', 'auto');
        void page.offsetHeight;

        var available = Math.floor(scaleBox.clientHeight);
        if (available <= 0) {
            available = Math.floor(page.clientHeight - 1);
        }
        if (available <= 0) {
            return;
        }

        // Measure natural content height (unscaled, unstretched)
        void doc.offsetHeight;
        var naturalHeight = Math.max(doc.scrollHeight, doc.offsetHeight);
        var scale = 1;

        if (naturalHeight > available) {
            scale = Math.max(MIN_SCALE, available / naturalHeight);
        }

        // Iterate once more after applying width expansion from scale
        for (var i = 0; i < 6; i++) {
            page.style.setProperty('--hv-print-scale', String(scale));
            page.style.setProperty('--hv-document-height', 'auto');
            void doc.offsetHeight;
            naturalHeight = Math.max(doc.scrollHeight, doc.offsetHeight);
            if (naturalHeight * scale <= available + 0.5) {
                break;
            }
            scale = Math.max(MIN_SCALE, available / naturalHeight);
        }

        // Stretch document so footer (margin-top: auto) sits on the bottom edge.
        // After CSS transform, visual height === available.
        page.style.setProperty('--hv-print-scale', String(scale));
        page.style.setProperty('--hv-document-height', (available / scale) + 'px');
        scaleBox.style.height = available + 'px';
        void doc.offsetHeight;
    }

    function runFit(thenPrint) {
        fitToA4();
        requestAnimationFrame(function () {
            fitToA4();
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
