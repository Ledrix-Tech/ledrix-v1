@extends('upwork.layout.layout')

@section('title', 'Upwork | Dashboard')

@section('upwork-content')

    <div class="d-xl-flex justify-content-between align-items-start">
        <h1 class="fw-bold" style="color: #003C51;">Dashboard
            <span class="mx-3"><img src="https://cdn-icons-png.flaticon.com/128/9823/9823663.png" alt=""
                    style="width: 60px;"></span>
        </h1>
        <div class="d-sm-flex justify-content-xl-between align-items-center mb-2">

            <div class="dropdownTime ml-0 ml-md-4 mt-2 mt-lg-0" style="box-shadow: 0px 6px 30px rgba(1, 170, 156, 0.521);">
                <button class="btn bg-white p-3 d-flex align-items-center" type="button" id="dropdownMenuButton1">
                    <i class="mdi mdi-calendar mr-1 mx-3 text-success" id="current-date">{{ now()->format('Y-m-d') }}</i>
                    <span id="current-time" class="text-danger fw-bolder">{{ now()->format('H:i:s') }}</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function updateTime() {
            var currentTime = new Date();
            var seconds = currentTime.getSeconds().toString().padStart(2, '0');
            document.getElementById('current-time').textContent = currentTime.getHours() + ':' + currentTime.getMinutes() +
                ':' + seconds;
        }
        setInterval(updateTime, 1000);
        document.querySelector('.dropdownTime').classList.add('animate-light');
    </script>


    <div class="row">
        <div class="col-md-12">
            <hr>
            <div class="tab-content tab-transparent-content">
                <div class="tab-pane fade show active" id="business-1" role="tabpanel" aria-labelledby="business-tab">
                    <div class="row">
                        <div class="col-xl-8 col-lg-8 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <canvas id="orderPaymentsChart"></canvas>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-6 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <a href="javascript:void(0);" style="text-decoration:none;">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Revenue</h5>
                                        <div
                                            class="dashboard-progress dashboard-progress-3 d-flex align-items-center justify-content-center item-parent my-5 ">
                                            <img src="https://cdn-icons-png.flaticon.com/128/5412/5412646.png"
                                                alt="" style="width: 60px;">
                                        </div>
                                        <h2 class="mb-4 text-danger font-weight-bold  ">
                                            {{ money_cents($revenue ?? 0) }}
                                            <div class="my-3">
                                                <h6 class="text-muted">Paid : {{ money_cents($paymentPaid ?? 0) }}</h6>
                                                <h6 class="text-sm text-muted">Dues : {{ money_cents($paymentDue ?? 0) }}
                                                </h6>
                                            </div>
                                        </h2>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <a href="javascript:void(0);" style="text-decoration:none;">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Brands</h5>
                                        <div
                                            class="dashboard-progress dashboard-progress-3 d-flex align-items-center justify-content-center item-parent my-5 ">
                                            <img src="https://cdn-icons-png.flaticon.com/128/7991/7991055.png"
                                                alt="" style="width: 60px;">
                                        </div>
                                        <h2 class="mb-4 text-danger font-weight-bold  ">{{ $brands ?? 0 }}</h2>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-6 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <a href="javascript:void(0);" style="text-decoration:none;">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Orders</h5>
                                        <div
                                            class="dashboard-progress dashboard-progress-3 d-flex align-items-center justify-content-center item-parent my-5 ">
                                            <img src="https://cdn-icons-png.flaticon.com/128/5530/5530389.png"
                                                alt="" style="width: 60px;">
                                        </div>
                                        <h2 class="mb-4 text-danger font-weight-bold  ">
                                            {{ $orders ?? 0 }}
                                        </h2>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <a href="javascript:void(0);" style="text-decoration:none;">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Payments</h5>
                                        <div
                                            class="dashboard-progress dashboard-progress-3 d-flex align-items-center justify-content-center item-parent my-5">
                                            <img src="https://cdn-icons-png.flaticon.com/128/2059/2059129.png"
                                                alt="" style="width: 60px;">
                                        </div>
                                        <h2 class="mb-4 text-danger font-weight-bold">{{ $payments ?? 0 }}

                                        </h2>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart (Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxOrder = document.getElementById('orderPaymentsChart').getContext('2d');
        let gradient = ctxOrder.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.4)');
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

        new Chart(ctxOrder, {
            type: 'line',
            data: {
                labels: @json($months), // ["January", "February", ...]
                datasets: [{
                    label: 'Payments ($)',
                    data: @json($totals), // Monthly totals
                    fill: true,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: gradient,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgba(54, 162, 235, 1)',
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: 'rgba(54, 162, 235, 1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value; // format as money
                            }
                        }
                    }
                }
            }
        });
    </script>

@endsection
