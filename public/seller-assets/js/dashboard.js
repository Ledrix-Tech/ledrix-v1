/**
 * CRM Dashboard — payments trend chart (brand colors)
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var canvas = document.getElementById('orderPaymentsChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var months = [];
        var totals = [];

        try {
            months = JSON.parse(canvas.dataset.months || '[]');
            totals = JSON.parse(canvas.dataset.totals || '[]');
        } catch (e) {
            return;
        }

        var ctx = canvas.getContext('2d');
        var gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, 'rgba(139, 82, 254, 0.35)');
        gradient.addColorStop(0.5, 'rgba(68, 56, 201, 0.12)');
        gradient.addColorStop(1, 'rgba(68, 56, 201, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Paid orders',
                    data: totals,
                    fill: true,
                    borderColor: '#4438c9',
                    backgroundColor: gradient,
                    tension: 0.38,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#8b52fe',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#8b52fe',
                    pointHoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1a1647',
                        titleFont: { size: 12 },
                        bodyFont: { size: 13, weight: '600' },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                var val = ctx.parsed.y;
                                return '$' + Number(val).toLocaleString(undefined, {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            font: { size: 11 }
                        },
                        border: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(226, 232, 240, 0.8)'
                        },
                        ticks: {
                            color: '#64748b',
                            font: { size: 11 },
                            callback: function (value) {
                                return '$' + Number(value).toLocaleString();
                            }
                        },
                        border: { display: false }
                    }
                }
            }
        });
    });
})();
