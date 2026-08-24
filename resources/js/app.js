import './bootstrap';
import {
    Chart,
    BarController,
    BarElement,
    LinearScale,
    CategoryScale,
    Tooltip,
} from 'chart.js';

Chart.register(BarController, BarElement, LinearScale, CategoryScale, Tooltip);

const OVER_COLOR = '#f43f5e'; // rose-500 — over threshold
const NORMAL_COLOR = '#6366f1'; // indigo-500 — within threshold

const thresholdLinePlugin = {
    id: 'thresholdLine',
    afterDraw(chart, args, opts) {
        const threshold = opts?.value;
        if (threshold === undefined || threshold === null) {
            return;
        }

        const { ctx, chartArea, scales } = chart;
        const x = scales.x.getPixelForValue(threshold);

        ctx.save();
        ctx.strokeStyle = '#dc2626';
        ctx.setLineDash([6, 4]);
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(x, chartArea.top);
        ctx.lineTo(x, chartArea.bottom);
        ctx.stroke();
        ctx.restore();
    },
};

Chart.register(thresholdLinePlugin);

window.Chart = Chart;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('workloadChart', (rows, threshold) => ({
        chart: null,
        showTable: false,

        init() {
            const labels = rows.map((row) => row.name);
            const data = rows.map((row) => row.score);
            const colors = rows.map((row) => (row.score > threshold ? OVER_COLOR : NORMAL_COLOR));

            this.chart = new Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Workload score',
                            data,
                            backgroundColor: colors,
                            borderRadius: 4,
                            maxBarThickness: 28,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `Workload score: ${ctx.raw} (threshold: ${threshold})`,
                            },
                        },
                        thresholdLine: { value: threshold },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: { display: true, text: 'Workload score' },
                        },
                        y: {
                            ticks: { autoSkip: false },
                        },
                    },
                },
            });
        },
    }));
});
