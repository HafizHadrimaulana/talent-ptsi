export function bindExternalSearch({ searchSelector, buttonSelector = null, tableSelector = '[data-dt]', delay = 400 }) {
    const input = document.querySelector(searchSelector);
    console.log('input', input)
    if (!input) {
        console.warn("Search input not found:", searchSelector);
        return;
    }

    const dt = getDTFromElOrJq(tableSelector);
    if (!dt) {
        console.warn("DataTable not found for:", tableSelector);
        return;
    }

    const performSearch = () => {
        const term = input.value.trim();
        try {
            if (typeof dt.search === "function") dt.search(term).draw();
            else if (dt.api) dt.api().search(term).draw();
            else {
                dt.search(term);
                dt.draw();
            }
        } catch (e) {
            console.error("Search error:", e);
        }
    };

    input.addEventListener("input", debounce(performSearch, delay));

    if (buttonSelector) {
        const button = document.querySelector(buttonSelector);
        if (button)
            button.addEventListener("click", (e) => {
                e.preventDefault();
                performSearch();
            });
    }

    input.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            input.value = "";
            performSearch();
            input.blur();
        }
    });
}
