// export function initDataTable(tableSelector) {
//     console.log("tableSelector", tableSelector);
//     const searchForm = document.getElementById(tableSelector);
//     const searchInput = document.getElementById(`${tableSelector}-input`);
//     const searchBtn = document.getElementById(`${tableSelector}-btn`);
//     const clearBtn = document.getElementById(`${tableSelector}-clear`);

//     const searchHandler = {
//         init: function () {
//             this.bindSearch();
//         },

//         bindSearch: function () {
//             if (searchForm) {
//                 searchForm.addEventListener("submit", (e) => {
//                     e.preventDefault();
//                     this.performSearch();
//                 });
//             }

//             if (searchInput) {
//                 searchInput.addEventListener(
//                     "input",
//                     this.debounce(() => {
//                         this.performSearch();
//                     }, 300)
//                 );
//             }

//             if (clearBtn) {
//                 clearBtn.addEventListener("click", () => {
//                     searchInput.value = "";
//                     this.performSearch();
//                 });
//             }
//         },

//         performSearch: function () {
//             const searchTerm = document
//                 .getElementById("empSearchInput")
//                 .value.toLowerCase();
//             const tableRows = document.querySelectorAll(
//                 `#${tableSelector} tbody tr`
//             );

//             tableRows.forEach((row) => {
//                 const rowText = row.textContent.toLowerCase();
//                 if (rowText.includes(searchTerm)) {
//                     row.style.display = "";
//                 } else {
//                     row.style.display = "none";
//                 }
//             });
//         },

//         debounce: function (func, wait) {
//             let timeout;
//             return function executedFunction(...args) {
//                 const later = () => {
//                     clearTimeout(timeout);
//                     func(...args);
//                 };
//                 clearTimeout(timeout);
//                 timeout = setTimeout(later, wait);
//             };
//         },
//     };

//     searchHandler.init();
// }
