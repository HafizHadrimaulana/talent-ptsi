@extends('layouts.app')

@section('title','Dashboard')

@section('content')

<div class="container px-6 bg-white rounded-xl shadow-lg p-6 w-full">

<!-- ===================== USER ===================== -->
<div class="p-3">
  <p class="text-2xl font-bold mb-4 text-primary">
    Hai, <span class="font-semibold" id="userName">John Doe</span>!
  </p>

  <div class="flex flex-col gap-3 mt-2">
    <span class="text-base font-medium text-gray-900" id="userInfo">
      Superadmin, SI Head Office.
    </span>
  </div>
</div>

<!-- ===================== BOX FILTER (TIME FILTER) ===================== -->
<div class="mt-4">

  <div class="mb-4 flex flex-wrap items-center gap-3">
    <label class="font-semibold text-gray-700">Period:</label>

    <select id="boxTimeFilter" class="border rounded-lg px-4 py-2 text-sm bg-gray-50 shadow-sm cursor-pointer">
      <option value="monthly">Monthly</option>
      <option value="yearly">Yearly</option>
      <option value="semester">Semester</option>
      <option value="quartile">Quartile</option>
      <option value="custom">Custom Range</option>
    </select>

    <input type="date" id="customStart" class="border px-2 py-1 rounded-lg hidden">
    <input type="date" id="customEnd" class="border px-2 py-1 rounded-lg hidden">
  </div>

  <div id="boxContainer" class="flex flex-wrap gap-4"></div>
</div>

<!-- ===================== DATA OVERVIEW ===================== -->
<div class="mt-10 bg-white rounded-xl shadow-lg p-6 w-full">

  <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-3">

    <!-- Dataset Filter -->
    <select id="tableFilter" class="border rounded-lg px-3 py-2 text-sm bg-gray-50 cursor-pointer shadow-sm"></select>

    <!-- Time Filter -->
    <select id="overviewTimeFilter" class="border rounded-lg px-3 py-2 text-sm bg-gray-50 cursor-pointer shadow-sm">
      <option value="monthly">Monthly</option>
      <option value="quarterly">Quarterly</option>
      <option value="semester">Semester</option>
      <option value="yearly">Yearly</option>
      <option value="custom">Custom Range</option>
    </select>

  </div>

  <div class="flex gap-3 mb-4">
    <input type="date" id="overviewStart" class="border px-2 py-1 rounded-lg hidden">
    <input type="date" id="overviewEnd" class="border px-2 py-1 rounded-lg hidden">
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- TABLE -->
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white border rounded-lg overflow-hidden border-gray-300">
        <thead class="border-b border-gray-300">
          <tr class="bg-gradient-to-r from-[#1F337E] to-[#49D4A9] text-white">
            <th class="px-4 py-2 border-r border-gray-200 rounded-tl-lg">Period</th>
            <th class="px-4 py-2 rounded-tr-lg">Value</th>
          </tr>
        </thead>
        <tbody id="dataTableBody"></tbody>
      </table>
    </div>

    <!-- CHART -->
    <div>
      <canvas id="dataChart" class="w-full h-64"></canvas>
    </div>

  </div>
</div>

<!-- ===================== RECENT ACTIVITY ===================== -->
<div class="mt-10 bg-white rounded-xl shadow-lg p-6 max-h-[360px] overflow-y-auto">
  <h2 class="text-xl font-semibold mb-4">Recent Activity</h2>
  <ul class="space-y-4 text-gray-700" id="activityList"></ul>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {

  // ================================
  // USER
  // ================================
  document.getElementById("userName").textContent = "Super Admin";
  document.getElementById("userInfo").textContent = "Superadmin, SI Head Office";

  // ================================
  // BOX DATA
  // ================================
  const boxData = [
    { label: "Total Karyawan", value: 1240, color: "bg-blue-600" },
    { label: "Izin Prinsip Pending", value: 20, color: "bg-purple-500" },
    { label: "Izin Prinsip Diterima", value: 5, color: "bg-green-600" },
    { label: "Izin Prinsip Ditolak", value: 5, color: "bg-red-600" },
    { label: "Kontrak baru", value: 17, color: "bg-indigo-500" },
    { label: "Kontrak ongoing", value: 12, color: "bg-teal-500" },
    { label: "Kontrak pending", value: 9, color: "bg-amber-600" },
    { label: "Kontrak selesai", value: 10, color: "bg-teal-600" },
    { label: "Jumlah Kuis", value: 8, color: "bg-pink-500" },
    { label: "Peserta training", value: 560, color: "bg-sky-500" }
  ];

  const boxContainer = document.getElementById("boxContainer");
  function renderBoxCards() {
    boxContainer.innerHTML = boxData.map(card => `
      <div class="w-40 sm:w-48 md:w-52 h-25 ${card.color} text-white rounded-xl shadow-lg p-4 flex flex-col">
        <p class="text-sm font-semibold opacity-90 mb-1">${card.label}</p>
        <p class="text-3xl font-bold leading-tight">${card.value}</p>
      </div>
    `).join('');
  }
  renderBoxCards();

  // ================================
  // DATASET FOR TABLE FILTER
  // ================================
  const dummyMetrics = {
    "Total Karyawan":      [1200, 1210, 1225, 1230, 1240, 1260],
    "Izin Prinsip Pending":[10, 15, 20, 18, 19, 20],
    "Izin Prinsip Diterima":[2, 3, 4, 4, 5, 5],
    "Kontrak baru":[10, 14, 12, 16, 17, 17],
    "Izin Prinsip Ditolak" : [2,6,10,6,7,5],
    
  };

  const tableFilter = document.getElementById("tableFilter");
  Object.keys(dummyMetrics).forEach(label => {
    tableFilter.innerHTML += `<option value="${label}">${label}</option>`;
  });

  // ================================
  // TIME PERIOD LABELS
  // ================================
  const timeLabels = {
    monthly:  ["Jan","Feb","Mar","Apr","May","Jun"],
    quarterly:["Q1","Q2","Q3","Q4"],
    semester: ["Semester 1","Semester 2"],
    yearly:   ["2020","2021","2022","2023","2024"]
  };

  // ================================
  // CHART
  // ================================
  const ctx = document.getElementById("dataChart").getContext("2d");
  let dataChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: timeLabels.monthly,
      datasets: [{
        label: "Value",
        data: dummyMetrics["Total Karyawan"],
        borderColor: "#2563EB",
        backgroundColor: "rgba(37,99,235,0.1)",
        borderWidth: 3,
        tension: 0.35
      }]
    }
  });

  // ================================
  // TABLE + CHART UPDATE FUNCTION
  // ================================
  const tableBody = document.getElementById("dataTableBody");
  const overviewTimeFilter = document.getElementById("overviewTimeFilter");

  function updateOverview() {
    const datasetLabel = tableFilter.value;
    const timeMode = overviewTimeFilter.value;

    let rawValues = dummyMetrics[datasetLabel];
    let labels = [];
    let values = [];

    switch(timeMode){
      case "monthly":
        labels = timeLabels.monthly;
        values = rawValues;
        break;

      case "quarterly":
        labels = timeLabels.quarterly;
        values = [
          avg(rawValues.slice(0,3)),
          avg(rawValues.slice(3,6)),
          avg(rawValues.slice(6,9) || []),
          avg(rawValues.slice(9,12) || [])
        ].slice(0,4);
        break;

      case "semester":
        labels = timeLabels.semester;
        values = [
          avg(rawValues.slice(0,6)),
          avg(rawValues.slice(6,12))
        ];
        break;

      case "yearly":
        labels = timeLabels.yearly;
        values = labels.map(()=>avg(rawValues));
        break;

      case "custom":
        labels = timeLabels.monthly;
        values = rawValues;
        break;
    }

    tableBody.innerHTML = labels.map((l,i)=>`
      <tr>
        <td class="border px-4 py-2">${l}</td>
        <td class="border px-4 py-2">${values[i] ?? "-"}</td>
      </tr>
    `).join("");

    dataChart.data.labels = labels;
    dataChart.data.datasets[0].data = values;
    dataChart.update();
  }

  function avg(arr){
    if(!arr.length) return 0;
    return arr.reduce((a,b)=>a+b,0) / arr.length;
  }

  // Event listeners
  tableFilter.addEventListener("change", updateOverview);
  overviewTimeFilter.addEventListener("change", updateOverview);

  updateOverview();

  // ================================
  // RECENT ACTIVITY
  // ================================
  const activities = [
    { color: "indigo-500", text: "John Doe approved a leave request", time: "2 hours ago" },
    { color: "yellow-500", text: "New employee added: Sarah Williams", time: "Yesterday" },
    { color: "orange-500", text: "3 pending requests require approval", time: "1 day ago" },
    { color: "purple-500", text: "Contract expiring: Michael Chen", time: "3 days ago" },
  ];

  document.getElementById("activityList").innerHTML =
    activities.map(a => `
      <li class="flex gap-3">
        <div class="w-2 h-2 bg-${a.color} rounded-full mt-2"></div>
        <div>
          <p class="font-semibold">${a.text}</p>
          <p class="text-sm opacity-60">${a.time}</p>
        </div>
      </li>
    `).join("");

});
</script>

@endsection
