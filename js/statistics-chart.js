// filepath: f:\Personal website\yhentertainment.com\js\statistics-chart.js
document.addEventListener('DOMContentLoaded', function () {
    function getRecentDates(n) {
        const arr = [];
        for (let i = n - 1; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            arr.push(`${d.getMonth() + 1}/${d.getDate()}`);
        }
        return arr;
    }
    const dailyData = window.dailyData || Array(15).fill(0);
    const labels = getRecentDates(15);
    const ctx = document.getElementById('myChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: window.chartLang.daily,
                data: dailyData,
                fill: true,
                backgroundColor: 'rgba(0,191,255,0.15)',
                borderColor: '#00bfae',
                tension: 0.4,
                pointBackgroundColor: '#00bfae',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            animation: {
                duration: 800,
                easing: 'easeOutQuart'
            },
            scales: {
                x: {
                    title: { display: true, text: window.chartLang.date },
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: window.chartLang.count }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});