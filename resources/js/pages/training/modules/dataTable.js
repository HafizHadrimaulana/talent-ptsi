export function initDataTable(tableSelector) {
    const table = document.querySelector(tableSelector);
    const searchInput = document.querySelector(`${tableSelector}-search`);
    const showSelect = document.querySelector(`${tableSelector}-show`);
    const paginationContainer = document.querySelector(`${tableSelector}-pagination`);
    
    if (!table) return;

    let rows = Array.from(table.querySelectorAll("tbody tr"));
    let currentPage = 1;
    let rowsPerPage = parseInt(showSelect?.value || 10);

    function renderTable() {
        const searchValue = searchInput?.value.toLowerCase() || "";
        const filteredRows = rows.filter(row =>
            row.textContent.toLowerCase().includes(searchValue)
        );

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        filteredRows.forEach((row, i) => {
            row.style.display = i >= start && i < end ? "" : "none";
        });

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = "";

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.textContent = i;
            btn.className = `u-btn u-btn--sm ${i === currentPage ? "u-btn--brand" : ""}`;
            btn.addEventListener("click", () => {
                currentPage = i;
                renderTable();
            });
            paginationContainer.appendChild(btn);
        }
    }

    // Event binding
    searchInput?.addEventListener("input", () => {
        currentPage = 1;
        renderTable();
    });

    showSelect?.addEventListener("change", (e) => {
        rowsPerPage = parseInt(e.target.value);
        currentPage = 1;
        renderTable();
    });

    // Sort by column
    table.querySelectorAll("th").forEach((th, index) => {
        th.style.cursor = "pointer";
        th.addEventListener("click", () => {
            const sorted = [...rows].sort((a, b) => {
                const aText = a.children[index].textContent.trim();
                const bText = b.children[index].textContent.trim();
                return aText.localeCompare(bText);
            });
            rows = sorted;
            table.querySelector("tbody").append(...rows);
            renderTable();
        });
    });

    renderTable();
}