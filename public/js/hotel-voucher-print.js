/**
 * Scale the hotel voucher to one A4 page and pin the address footer
 * to the bottom of the page.
 */
(function () {
    var SAFETY_PX = 2;
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

        // 1) Measure natural height (no stretch, scale = 1)
        page.style.setProperty('--hv-print-scale', '1');
        page.style.setProperty('--hv-document-height', 'auto');
        scaleBox.style.height = available + 'px';
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

        page.style.setProperty('--hv-print-scale', String(scale));
        void doc.offsetHeight;
        var naturalHeight = Math.max(doc.scrollHeight, doc.offsetHeight);
        if (naturalHeight * scale > available) {
            scale = Math.max(MIN_SCALE, available / naturalHeight);
            page.style.setProperty('--hv-print-scale', String(scale));
        }

        // 2) Always stretch the document to exactly fill the page so the
        //    footer (margin-top: auto) sticks to the bottom edge.
        var targetHeight = available / scale;
        page.style.setProperty('--hv-document-height', targetHeight + 'px');
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
