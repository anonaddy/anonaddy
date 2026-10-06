<template>
  <Line :options="chartOptions" :data="chartData" />
</template>

<script setup>
import { computed } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Legend,
} from 'chart.js'
import { Line } from 'vue-chartjs'
import { CHART_CYAN, CHART_PERIWINKLE, useChartTheme } from './chartTheme'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend)

const props = defineProps({
  inboundRejectionsData: {
    type: [Array, Object],
    required: true,
  },
  inboundQuarantinedData: {
    type: [Array, Object],
    required: true,
  },
  outboundBouncesData: {
    type: [Array, Object],
    required: true,
  },
  labels: {
    type: Object,
    required: true,
  },
})

const { textColor, navy } = useChartTheme()

const chartData = computed(() => {
  return {
    labels: props.labels,
    datasets: [
      {
        label: 'Inbound rejections',
        backgroundColor: CHART_CYAN,
        borderColor: CHART_CYAN,
        borderWidth: 4,
        pointBackgroundColor: CHART_CYAN,
        pointBorderColor: '#fff',
        lineTension: 0.4,
        pointRadius: 5,
        pointBorderWidth: 2,
        pointHitRadius: 100,
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: CHART_CYAN,
        data: props.inboundRejectionsData,
      },
      {
        label: 'Inbound quarantined',
        backgroundColor: navy.value,
        borderColor: navy.value,
        borderWidth: 4,
        pointBackgroundColor: navy.value,
        pointBorderColor: '#fff',
        lineTension: 0.4,
        pointRadius: 5,
        pointBorderWidth: 2,
        pointHitRadius: 100,
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: navy.value,
        data: props.inboundQuarantinedData,
      },
      {
        label: 'Outbound bounces',
        backgroundColor: CHART_PERIWINKLE,
        borderColor: CHART_PERIWINKLE,
        borderWidth: 4,
        pointBackgroundColor: CHART_PERIWINKLE,
        pointBorderColor: '#fff',
        lineTension: 0.4,
        pointRadius: 5,
        pointBorderWidth: 2,
        pointHitRadius: 100,
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: CHART_PERIWINKLE,
        data: props.outboundBouncesData,
      },
    ],
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  color: textColor.value,
  elements: {
    point: {
      radius: 6,
      hitRadius: 6,
      hoverRadius: 6,
    },
  },
  plugins: {
    legend: {
      display: true,
      labels: {
        color: textColor.value,
      },
    },
  },
  interaction: {
    intersect: false,
    mode: 'index',
  },
  hover: {
    mode: 'nearest',
    intersect: true,
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        color: textColor.value,
      },
    },
    y: {
      beginAtZero: true,
      grid: {
        display: false,
      },
      ticks: {
        stepSize: 1,
        color: textColor.value,
        callback: value => (Number.isInteger(value) ? value : undefined),
      },
    },
  },
}))
</script>
