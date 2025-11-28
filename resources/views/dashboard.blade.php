@extends('layouts.app')

@section('title','Dashboard')

@section('content')
<div>
    <p class="text-2xl font-bold mb-4 text-primary">
        Hai, <span class="font-semibold">{{ auth()->user()->name ?? '-' }}!</span>
    </p>

    <div class="flex flex-col gap-3 mt-2">
        <div class="flex flex-col">
            <span class="text-base font-medium text-gray-900">
                {{ auth()->user()?->getRoleNames()->implode(', ') ?: '-' }},
                {{ optional(auth()->user()?->unit)->name ?? '-' }}.
            </span>
        </div>
    </div>
</div>
<!-- Box Container -->
<div class="!mt-4">
    <div class="flex flex-wrap !gap-4">

        @php
            $cards = [
                ['label' => 'Total Employees', 'value' => 1260, 'color' => 'bg-blue-500'],
                ['label' => 'Requests', 'value' => 20, 'color' => 'bg-gray-700'],
                ['label' => 'In Progress', 'value' => 10, 'color' => 'bg-cyan-500'],
                ['label' => 'Pending Approval', 'value' => 17, 'color' => 'bg-orange-500'],
                ['label' => 'Approved', 'value' => 5, 'color' => 'bg-green-500'],
                ['label' => 'Rejected', 'value' => 5, 'color' => 'bg-red-500'],
                ['label' => 'New Contracts', 'value' => 15, 'color' => 'bg-yellow-500'],
                ['label' => 'Completed Contracts', 'value' => 10, 'color' => 'bg-blue-700'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="!w-48 !h-26 {{ $card['color'] }} !text-white !rounded-xl !shadow-lg !p-4 
                        flex flex-col justify-start">

                <!-- Title -->
                <p class="!text-base !font-semibold !opacity-90 !mb-1">
                    {{ $card['label'] }}
                </p>

                <!-- Value -->
                <p class="!text-4xl !font-bold !leading-none !tracking-tight">
                    {{ $card['value'] }}
                </p>

            </div>
        @endforeach

    </div>
</div>
<!-- KPI SECTION -->
<div class="!mt-10 bg-white !rounded-xl !shadow-lg !p-6 w-full">

    <!-- HEADER -->
    <h2 class="text-xl font-semibold mb-4">Key Performance Indicators</h2>

    <!-- TOP KPI BOXES -->
    <div class="grid grid-cols-4 gap-4 mb-6">

        <!-- KPI #1 -->
        <div class="bg-green-50 !p-4 !rounded-lg shadow flex flex-col items-center text-center">
            <p class="font-semibold">Total Active Employees</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-3xl font-bold text-green-600">1260</span>
                <span class="text-green-600 text-xl">▲</span>
            </div>
            <p class="text-green-600 font-semibold mt-1">+7.5%</p>
        </div>

        <!-- KPI #2 -->
        <div class="bg-green-50 !p-4 !rounded-lg shadow flex flex-col items-center text-center">
            <p class="font-semibold">New Hires</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-3xl font-bold text-green-600">172</span>
                <span class="text-green-600 text-xl">▲</span>
            </div>
            <p class="text-green-600 font-semibold mt-1">+7.5%</p>
        </div>

        <!-- KPI #3 -->
        <div class="bg-red-50 !p-4 !rounded-lg shadow flex flex-col items-center text-center">
            <p class="font-semibold">Terminations</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-3xl font-bold text-red-600">-29</span>
                <span class="text-red-600 text-xl">▼</span>
            </div>
            <p class="text-red-600 font-semibold mt-1">-7.4%</p>
        </div>

        <!-- KPI #4 -->
        <div class="bg-gray-50 !p-4 !rounded-lg shadow flex flex-col items-center text-center">
            <p class="font-semibold">Employee Absenteeism</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-3xl font-bold text-gray-800">0.0%</span>
            </div>
            <p class="text-gray-700 font-semibold mt-1">No changes</p>
        </div>

    </div>
<!-- KPI Filter -->
<div class="flex flex-wrap items-center gap-4 mb-6 !mt-4 !p-4 bg-white shadow-md rounded-xl !border !border-gray-200">

    <!-- Filter Label -->
    <span class="text-gray-700 font-semibold text-sm !mr-2">Filter by:</span>

    <!-- Filter Dropdown -->
    <select id="kpiFilter"
        class="border !border-gray-300 rounded-lg px-4 py-2 text-sm font-medium
               bg-gray-50 hover:bg-gray-100 transition-all cursor-pointer
               shadow-sm !important">
        <option value="monthly">📅 Monthly</option>
        <option value="yearly">📆 Yearly</option>
        <option value="custom">🔍 Custom Range</option>
    </select>

    <!-- Custom Range Container -->
    <div id="customRange" class="flex items-center gap-3 hidden">

        <input type="date" id="startDate"
            class="border !border-gray-300 rounded-lg px-3 py-2 text-sm bg-white shadow-sm !important cursor-pointer">

        <span class="text-gray-500">to</span>

        <input type="date" id="endDate"
            class="border !border-gray-300 rounded-lg px-3 py-2 text-sm bg-white shadow-sm !important cursor-pointer">

        <button id="applyRange"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium shadow-md
                   hover:bg-blue-700 active:scale-95 transition-all !important cursor-pointer">
            Apply
        </button>
    </div>
</div>

    <!-- KPI TABLE -->
    <div class="!overflow-x-auto !mt-5">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b bg-gray-100">
                    <th class="p-3 font-semibold">Indicator</th>
                    <th class="p-3 font-semibold">Period</th>
                    <th class="p-3 font-semibold">Current</th>
                    <th class="p-3 font-semibold">Previous</th>
                    <th class="p-3 font-semibold">Change</th>
                </tr>
            </thead>

            <tbody>
                <!-- Row 1 -->
                <tr class="border-b">
                    <td class="p-3 font-semibold">Total Active Employees</td>
                    <td class="p-3" data-period>This Month vs Last Month</td>
                    <td class="p-3">1210</td>
                    <td class="p-3">1109</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 2.7%
                    </td>
                </tr>

                <!-- Row 2 -->
                <tr class="border-b">
                    <td class="p-3 font-semibold">Total Active Female Employees</td>
                    <td class="p-3" data-period>This Month vs Last Month</td>
                    <td class="p-3">470</td>
                    <td class="p-3">490</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 2.8%
                    </td>
                </tr>

                <!-- Row 3 -->
                <tr class="border-b">
                    <td class="p-3 font-semibold">Total Active Male Employees</td>
                    <td class="p-3" data-period>This Month vs Last Month</td>
                    <td class="p-3">700</td>
                    <td class="p-3">710</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 3.0%
                    </td>
                </tr>

                <!-- Row 4 -->
                <tr class="border-b">
                    <td class="p-3 font-semibold">New Hires</td>
                    <td class="p-3" data-period >This Month vs Last Month</td>
                    <td class="p-3">172</td>
                    <td class="p-3">160</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 7.5%
                    </td>
                </tr>

                <!-- Row 5 -->
                <tr>
                    <td class="p-3 font-semibold">Terminations</td>
                    <td class="p-3" data-period>This Month vs Last Month</td>
                    <td class="p-3">-29</td>
                    <td class="p-3">-27</td>
                    <td class="p-3 text-red-600 font-semibold flex items-center gap-1">
                        ▼ 7.4%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<!----Headcount + Recent Activity (Both Inside Same Container) --->
<div class="!mt-10 !flex !gap-6 !flex-nowrap">

    <!-- HEADCOUNT CHART (Left) -->
    <div class="bg-white !rounded-xl !shadow-lg !p-6 
                !w-[900px] !h-[350px] flex-grow">

        <!-- Title + Dropdown (aligned horizontally) -->
        <div class="!flex !items-center !justify-between !mb-4">
            <h2 class="text-xl font-semibold !ml-3">Headcount Overview</h2>

            <!-- Dropdown -->
            <div class="flex items-center gap-3">
                <select id="headcountFilter" 
                        class="!border !border-gray-300 !rounded-md !p-2 !text-sm !cursor-pointer">
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom Range</option>
                </select>

                <div id="headcountCustomRange" class="hidden items-center gap-2">
                    <input type="date" id="hcStart" class="border p-2 rounded">
                    <input type="date" id="hcEnd" class="border p-2 rounded">
                    <button id="hcApply" class="px-3 py-2 bg-blue-600 text-white rounded">Apply</button>
                </div>
            </div>
        </div>

        <div class="w-full !h-[260px]">
            <canvas id="headcountChart"></canvas>
        </div>

    </div>

    <!-- LATEST ACTIVITY -->
    <div class="bg-white !rounded-xl !shadow-lg !p-6 
                !w-[350px] !h-[350px] flex-none overflow-y-auto">

        <h2 class="text-xl font-semibold !mb-4 !ml-2">Recent Activity</h2>

        <ul class="space-y-4 text-gray-700">

            <!-- Dummy Activity Items -->
            <li class="flex items-start gap-3">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                <div>
                    <p class="font-semibold">John Doe approved a leave request</p>
                    <p class="text-sm opacity-60">2 hours ago</p>
                </div>
            </li>

            <li class="flex items-start gap-3">
                <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                <div>
                    <p class="font-semibold">New employee added: Sarah Williams</p>
                    <p class="text-sm opacity-60">Yesterday</p>
                </div>
            </li>

            <li class="flex items-start gap-3">
                <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2"></div>
                <div>
                    <p class="font-semibold">3 pending requests require approval</p>
                    <p class="text-sm opacity-60">1 day ago</p>
                </div>
            </li>

            <li class="flex items-start gap-3">
                <div class="w-2 h-2 bg-red-500 rounded-full mt-2"></div>
                <div>
                    <p class="font-semibold">Contract expiring: Michael Chen</p>
                    <p class="text-sm opacity-60">3 days ago</p>
                </div>
            </li>

            <li class="flex items-start gap-3">
                <div class="w-2 h-2 bg-purple-500 rounded-full mt-2"></div>
                <div>
                    <p class="font-semibold">Performance review submitted</p>
                    <p class="text-sm opacity-60">5 days ago</p>
                </div>
            </li>

            <li class="flex items-start gap-3">
                <div class="w-2 h-2 bg-pink-500 rounded-full mt-2"></div>
                <div>
                    <p class="font-semibold">Contract expiring: John McCormick</p>
                    <p class="text-sm opacity-60">Just Now</p>
                </div>
            </li>

        </ul>
    </div>

</div>


</div>
<!-- L&D Chart Container -->
<div class="bg-white !rounded-xl !shadow-lg !p-6 
                !w-auto !h-[400px] flex-grow !mt-10">
             <!-- Title + Dropdown (aligned horizontally) -->
        <div class="!flex !items-center !justify-between !mb-4">
            <h2 class="text-xl font-semibold !ml-3">Employee Learning Progress</h2>

            <!-- Dropdown -->
            <div class="flex items-center gap-3">
                <select id="trainingFilter" 
                        class="!border !border-gray-300 !rounded-md !p-2 !text-sm !cursor-pointer">
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom Range</option>
                </select>

                <div id="trainingCustomRange" class="hidden items-center gap-2">
                    <input type="date" id="trStart" class="border p-2 rounded">
                    <input type="date" id="trEnd" class="border p-2 rounded">
                    <button id="trApply" class="px-3 py-2 bg-blue-600 text-white rounded">Apply</button>
                </div>
            </div>
        </div>
    <canvas id="trainingChart" class=" !w-full !h-[320px]"></canvas>
</div>

<!-- Chart.js (single include) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Combined Chart + Filter JS -->
<script>
// ====== BASE DATA (replace with real data or fetch from API) ======
const MONTH_LABELS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
const BASE_HEADCOUNT = [1100,1120,1135,1150,1165,1180,1190,1200,1215,1230,1240,1250];
const BASE_TRAINING = [40,55,60,75,90,100,120,115,130,140,125,150];

// Chart instances
let headcountChartInstance = null;
let trainingChartInstance = null;

function buildHeadcountChart(labels, data) {
    const ctx = document.getElementById('headcountChart').getContext('2d');
    if (headcountChartInstance) headcountChartInstance.destroy();

    headcountChartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [{ label: 'Headcount', data, borderWidth: 3, borderColor: "rgba(59,130,246,0.9)", backgroundColor: "rgba(59,130,246,0.3)", tension: 0.4, fill: true }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: false, grid: { color: '#eee' } }, x: { grid: { display: false } } } }
    });
}

function buildTrainingChart(labels, data) {
    const ctx = document.getElementById('trainingChart').getContext('2d');
    if (trainingChartInstance) trainingChartInstance.destroy();

    trainingChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Completed Trainings', data, backgroundColor: "rgba(59,130,246,0.7)", borderColor: "rgba(59,130,246,1)", borderWidth: 2, borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#eee' }, ticks: { stepSize: 20 } }, x: { grid: { display: false } } } }
    });
}

// Utility: clamp month range within 0..11 even if dates cross years
function clampMonthRange(startDateStr, endDateStr) {
    const s = new Date(startDateStr);
    const e = new Date(endDateStr);
    // If invalid dates return full range
    if (isNaN(s) || isNaN(e)) return { start: 0, end: 11 };

    // If start > end, swap
    if (s > e) {
        const tmp = s; s = e; e = tmp;
    }

    const startMonth = s.getMonth();
    const endMonth = e.getMonth();
    return { start: startMonth, end: endMonth };
}

function sliceByMonthRange(arr, start, end) {
    // If start <= end normal slice, else - wrap around across year boundary
    if (start <= end) return arr.slice(start, end + 1);
    // wrap
    return arr.slice(start).concat(arr.slice(0, end + 1));
}

// KPI table updater (keeps previous behavior but safe)
function updateKPITable(type, range = null) {
    // Keep original dummy behavior but with safer parsing
    const rows = document.querySelectorAll("tbody tr");

    rows.forEach(row => {
        const current = row.children[2];
        const previous = row.children[3];
        const change = row.children[4];

        if (type === "monthly") {
            current.textContent = random(950, 1250);
            previous.textContent = random(900, 1150);
        } else if (type === "yearly") {
            current.textContent = random(10000, 16000);
            previous.textContent = random(9000, 15000);
        } else if (type === "custom") {
            current.textContent = random(500, 2000);
            previous.textContent = random(300, 1800);
        }

        const c = parseFloat(current.textContent) || 0;
        const p = parseFloat(previous.textContent) || 1; // avoid div by zero
        const diff = (((c - p) / p) * 100).toFixed(1);

        change.textContent = (diff >= 0 ? "▲ " : "▼ ") + Math.abs(diff) + "%";
        change.className =
            "p-3 font-semibold flex items-center gap-1 " +
            (diff >= 0 ? "text-green-600" : "text-red-600");
    });
}

function random(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

// Update period cells in KPI table
function updateKPIPeriodText(text) {
    const periodCells = document.querySelectorAll('td[data-period]');
    periodCells.forEach(cell => cell.textContent = text);
}

// DOM ready single listener
document.addEventListener('DOMContentLoaded', () => {
    // Initial chart render (full 12 months)
    buildHeadcountChart(MONTH_LABELS, BASE_HEADCOUNT);
    buildTrainingChart(MONTH_LABELS, BASE_TRAINING);

    // === KPI filter behavior ===
    const kpiFilter = document.getElementById('kpiFilter');
    const kpiCustomRange = document.getElementById('customRange');
    const kpiStart = document.getElementById('startDate');
    const kpiEnd = document.getElementById('endDate');
    const kpiApply = document.getElementById('applyRange');

    kpiFilter.addEventListener('change', () => {
        if (kpiFilter.value === 'custom') {
            kpiCustomRange.classList.remove('hidden');
        } else {
            kpiCustomRange.classList.add('hidden');
            updateKPITable(kpiFilter.value);
            updateKPIPeriodText(kpiFilter.value === 'monthly' ? 'This Month vs Last Month' : 'This Year vs Last Year');
        }
    });

    kpiApply.addEventListener('click', () => {
        const s = kpiStart.value;
        const e = kpiEnd.value;
        if (!s || !e) return alert('Please select both dates.');
        updateKPITable('custom', { start: s, end: e });
        updateKPIPeriodText(`${s} to ${e}`);
    });

    // === Headcount filter ===
    const headcountFilter = document.getElementById('headcountFilter');
    const headcountCustomRange = document.getElementById('headcountCustomRange');
    const hcStart = document.getElementById('hcStart');
    const hcEnd = document.getElementById('hcEnd');
    const hcApply = document.getElementById('hcApply');

    headcountFilter.addEventListener('change', () => {
        if (headcountFilter.value === 'custom') {
            headcountCustomRange.classList.remove('hidden');
        } else {
            headcountCustomRange.classList.add('hidden');
            if (headcountFilter.value === 'monthly') {
                buildHeadcountChart(MONTH_LABELS, BASE_HEADCOUNT);
            } else if (headcountFilter.value === 'yearly') {
                // Aggregate yearly (sum) example
                const sum = BASE_HEADCOUNT.reduce((a,b) => a + b, 0);
                buildHeadcountChart(['This Year'], [sum]);
            }
        }
    });

    hcApply.addEventListener('click', () => {
        const s = hcStart.value;
        const e = hcEnd.value;
        if (!s || !e) return alert('Please select both start and end dates');
        // compute month indices and slice arrays accordingly
        const { start, end } = clampMonthRange(s, e);
        const labels = start <= end ? MONTH_LABELS.slice(start, end + 1) : MONTH_LABELS.slice(start).concat(MONTH_LABELS.slice(0, end + 1));
        const headcount = sliceByMonthRange(BASE_HEADCOUNT, start, end);
        buildHeadcountChart(labels, headcount);
    });

    // === Training filter ===
    const trainingFilter = document.getElementById('trainingFilter');
    const trainingCustomRange = document.getElementById('trainingCustomRange');
    const trStart = document.getElementById('trStart');
    const trEnd = document.getElementById('trEnd');
    const trApply = document.getElementById('trApply');

    trainingFilter.addEventListener('change', () => {
        if (trainingFilter.value === 'custom') {
            trainingCustomRange.classList.remove('hidden');
        } else {
            trainingCustomRange.classList.add('hidden');
            if (trainingFilter.value === 'monthly') {
                buildTrainingChart(MONTH_LABELS, BASE_TRAINING);
            } else if (trainingFilter.value === 'yearly') {
                const sum = BASE_TRAINING.reduce((a,b) => a + b, 0);
                buildTrainingChart(['This Year'], [sum]);
            }
        }
    });

    trApply.addEventListener('click', () => {
        const s = trStart.value;
        const e = trEnd.value;
        if (!s || !e) return alert('Please select both start and end dates');
        const { start, end } = clampMonthRange(s, e);
        const labels = start <= end ? MONTH_LABELS.slice(start, end + 1) : MONTH_LABELS.slice(start).concat(MONTH_LABELS.slice(0, end + 1));
        const training = sliceByMonthRange(BASE_TRAINING, start, end);
        buildTrainingChart(labels, training);
    });

});
</script>

@endsection
