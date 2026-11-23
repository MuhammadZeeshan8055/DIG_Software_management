(function () {
    const pages = document.getElementById('hv-print-pages');
    if (!pages) {
        return;
    }

    const sourcePage = pages.firstElementChild;
    const source = sourcePage.firstElementChild;
    const header = source.querySelector('.ticket-doc__header');
    const details = source.querySelector('.ticket-doc__meta-row');
    const blocks = source.querySelectorAll(':scope > .ticket-doc__section');
    const guidance = source.querySelector('.ticket-doc__notice');
    const footer = source.querySelector('.ticket-doc__footer');

    function paginate() {
        pages.replaceChildren();
        let content;

        function addPage() {
            const page = sourcePage.cloneNode(false);
            page.classList.add('hotel-voucher-doc-page--paginated');

            const voucher = source.cloneNode(false);
            content = document.createElement('div');
            content.className = 'hotel-voucher-doc-content';

            const pageFooter = footer.cloneNode(true);
            voucher.append(header.cloneNode(true), details.cloneNode(true), content, pageFooter);
            page.append(voucher);
            pages.append(page);
        }

        function fits() {
            const bottom = content.getBoundingClientRect().bottom;
            const lastBlock = content.lastElementChild;
            const bottomMargin = parseFloat(getComputedStyle(lastBlock).marginBottom);
            return lastBlock.getBoundingClientRect().bottom + bottomMargin <= bottom + 0.1;
        }

        function placeBlock(block) {
            content.append(block);
            if (fits()) {
                return true;
            }

            addPage();
            content.append(block);
            return fits();
        }

        function placeInstructions(section, instructions) {
            const lines = instructions.textContent.split('\n');
            let start = 0;

            while (start < lines.length) {
                const copy = section.cloneNode(true);
                const text = copy.querySelector('[data-voucher-instructions]');
                content.append(copy);

                // Lines may wrap, so measure how many fit on this sheet.
                let low = start;
                let high = lines.length;
                while (low < high) {
                    const end = Math.ceil((low + high) / 2);
                    text.textContent = lines.slice(start, end).join('\n');
                    if (fits()) {
                        low = end;
                    } else {
                        high = end - 1;
                    }
                }

                if (low === start) {
                    copy.remove();
                    if (!content.childElementCount) {
                        return false;
                    }
                } else {
                    text.textContent = lines.slice(start, low).join('\n');
                    start = low;
                }

                if (start < lines.length) {
                    addPage();
                }
            }

            return true;
        }

        function placeTable(section, table) {
            const [columns, ...rows] = table.children;
            let fragment;
            let body;

            function startTable() {
                fragment = section.cloneNode(false);
                body = table.cloneNode(false);
                body.append(columns.cloneNode(true));
                fragment.append(section.firstElementChild.cloneNode(true), body);
                content.append(fragment);
            }

            startTable();
            for (const row of rows) {
                const copy = row.cloneNode(true);
                body.append(copy);
                if (fits()) {
                    continue;
                }

                copy.remove();
                if (body.childElementCount === 1) {
                    fragment.remove();
                }

                addPage();
                startTable();
                body.append(copy);
                if (!fits()) {
                    return false;
                }
            }

            return true;
        }

        addPage();
        for (const block of blocks) {
            const table = block.querySelector('.hv-doc-table');
            const instructions = block.querySelector('[data-voucher-instructions]');
            let placed;
            if (table) {
                placed = placeTable(block, table);
            } else if (instructions) {
                placed = placeInstructions(block, instructions);
            } else {
                placed = placeBlock(block.cloneNode(true));
            }
            if (!placed) {
                // Exceptionally tall fields can use the browser's normal page breaks.
                pages.replaceChildren(sourcePage);
                return;
            }
        }

        const closing = document.createElement('div');
        closing.className = 'hotel-voucher-doc-closing';
        closing.append(guidance.cloneNode(true));
        if (!placeBlock(closing)) {
            pages.replaceChildren(sourcePage);
            return;
        }

        // Signatures stay with the address on the last page only.
        const sheets = pages.querySelectorAll('.hotel-voucher-doc-page');
        sheets.forEach(function (sheet, index) {
            if (index < sheets.length - 1) {
                const block = sheet.querySelector('.ticket-doc__footer-signatures');
                if (block) {
                    block.remove();
                }
            }
        });
    }

    // Lock the on-screen sizes so print cannot reflow flex or fonts.
    function lockBox(el) {
        if (!el) {
            return;
        }
        const height = el.getBoundingClientRect().height;
        el.style.setProperty('height', height + 'px', 'important');
        el.style.setProperty('min-height', height + 'px', 'important');
        el.style.setProperty('max-height', height + 'px', 'important');
        el.style.setProperty('flex', '0 0 ' + height + 'px', 'important');
    }

    function freezePages() {
        pages.querySelectorAll('.hotel-voucher-doc-page--paginated').forEach(function (page) {
            page.style.setProperty('break-after', 'avoid', 'important');
            page.style.setProperty('page-break-after', 'avoid', 'important');
            // Lock the tables only. Leave the signature block and address bar free
            // so the names are never clipped onto the blue bar.
            page.querySelectorAll('.ticket-doc__section, .hv-doc-table__row, .hv-doc-table__head').forEach(lockBox);
        });
    }

    async function onReady() {
        if (document.fonts) {
            await document.fonts.ready;
        }

        paginate();
        freezePages();
        if (pages.dataset.autoPrint === 'true') {
            window.print();
        }
    }

    if (document.readyState === 'complete') {
        onReady();
    } else {
        window.addEventListener('load', onReady, { once: true });
    }
})();
