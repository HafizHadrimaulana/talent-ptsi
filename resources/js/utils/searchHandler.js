export function createSearchHandler({ formId, inputId, tableId, clearId }) {
    return {
        init: function () {
            this.bindSearch();
        },

        bindSearch: function () {
            const searchForm = document.getElementById(formId);
            const searchInput = document.getElementById(inputId);
            const clearBtn = document.getElementById(clearId);

            if (searchForm) {
                searchForm.addEventListener("submit", (e) => {
                    e.preventDefault();
                    this.performSearch();
                });
            }

            if (searchInput) {
                searchInput.addEventListener(
                    "input",
                    this.debounce(() => {
                        this.performSearch();
                    }, 300)
                );
            }

            if (clearBtn) {
                clearBtn.addEventListener("click", () => {
                    searchInput.value = "";
                    this.performSearch();
                });
            }
        },

        performSearch: function () {
            const searchTerm = document
                .getElementById(inputId)
                .value.toLowerCase();
            const tableRows = document.querySelectorAll(`${tableId} tbody tr`);

            tableRows.forEach((row) => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchTerm) ? "" : "none";
            });
        },

        debounce: function (func, wait) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func(...args), wait);
            };
        },
    };
}
