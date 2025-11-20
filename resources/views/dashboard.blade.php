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
                    <td class="p-3">This Month vs Last Month</td>
                    <td class="p-3">1210</td>
                    <td class="p-3">1109</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 2.7%
                    </td>
                </tr>

                <!-- Row 2 -->
                <tr class="border-b">
                    <td class="p-3 font-semibold">Total Active Female Employees</td>
                    <td class="p-3">This Month vs Last Month</td>
                    <td class="p-3">470</td>
                    <td class="p-3">490</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 2.8%
                    </td>
                </tr>

                <!-- Row 3 -->
                <tr class="border-b">
                    <td class="p-3 font-semibold">Total Active Male Employees</td>
                    <td class="p-3">This Month vs Last Month</td>
                    <td class="p-3">700</td>
                    <td class="p-3">710</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 3.0%
                    </td>
                </tr>

                <!-- Row 4 -->
                <tr class="border-b">
                    <td class="p-3 font-semibold">New Hires</td>
                    <td class="p-3">This Month vs Last Month</td>
                    <td class="p-3">172</td>
                    <td class="p-3">160</td>
                    <td class="p-3 text-green-600 font-semibold flex items-center gap-1">
                        ▲ 7.5%
                    </td>
                </tr>

                <!-- Row 5 -->
                <tr>
                    <td class="p-3 font-semibold">Terminations</td>
                    <td class="p-3">This Month vs Last Month</td>
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

<!----Headcount + Recent Activity--->
<div class="!mt-10 !flex !flex-wrap !gap-6">

    <!-- HEADCOUNT CHART (Larger, Left) -->
    <div class="bg-white !rounded-xl !shadow-lg !p-6 
                !w-[900px] !h-[350px] flex-grow">

        <h2 class="text-xl font-semibold !mb-4 !ml-3">Headcount Overview</h2>

        <div class="w-full !h-[260px]">
            <canvas id="headcountChart"></canvas>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </div>

    <!-- LATEST ACTIVITY (Smaller, Right, More Elegant) -->
    <div class="bg-white !rounded-xl !shadow-lg !p-6 
                !w-[350px] !h-[350px] flex-none overflow-y-auto">

        <h2 class="text-xl font-semibold !mb-4 !ml-2">Latest Activity</h2>

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
<!-- L&D Chart Container -->
<div class="bg-white !rounded-xl !shadow-lg !p-6 
                !w-auto !h-[350px] flex-grow !mt-10">
    <h2 class="text-lg font-semibold mb-2 !ml-5">Learning & Development Progress</h2>
    <canvas id="trainingChart" class=" !w-full !h-auto"></canvas>
</div>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart Script -->
<script>
    const ctx = document.getElementById('headcountChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
            datasets: [{
                label: 'Headcount',
                data: [1100,1120,1135,1150,1165,1180,1190,1200,1215,1230,1240,1250],
                borderWidth: 3,
                borderColor: "rgba(59,130,246,0.9)",
                backgroundColor: "rgba(59,130,246,0.3)",
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }},
            scales: {
                y: { beginAtZero: false, grid: { color: "#eee" }},
                x: { grid: { display: false }}
            }
        }
    });

     const trainingCtx = document.getElementById('trainingChart').getContext('2d');

    new Chart(trainingCtx, {
        type: 'bar',
        data: {
            labels: [
                "Jan","Feb","Mar","Apr","May","Jun",
                "Jul","Aug","Sep","Oct","Nov","Dec"
            ],
            datasets: [{
                label: "Completed Trainings",
                data: [40, 55, 60, 75, 90, 100, 120, 115, 130, 140, 125, 150],
                backgroundColor: "rgba(59,130,246,0.7)",  // Tailwind blue-500
                borderColor: "rgba(59,130,246,1)",
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: "#eee" },
                    ticks: { stepSize: 20 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>



@endsection
