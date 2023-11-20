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

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend)

const props = defineProps({
  forwardsData: {
    type: Array,
    required: true,
  },
  repliesData: {
    type: Array,
    required: true,
  },
  sendsData: {
    type: Array,
    required: true,
  },
  labels: {
    type: Object,
    required: true,
  },
})

const chartData = computed(() => {
  return {
    labels: props.labels,
    datasets: [
      {
        label: 'Forwards',
        backgroundColor: 'rgba(28, 212, 212, 1)',
        borderColor: 'rgba(28, 212, 212, 1)',
        borderWidth: 4,
        pointBackgroundColor: 'rgba(28, 212, 212, 1)',
        pointBorderColor: '#fff',
        lineTension: 0.4,
        pointRadius: 5,
        pointBorderWidth: 2,
        pointHitRadius: 100,
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(28, 212, 212, 1)',
        data: props.forwardsData,
      },
      {
        label: 'Replies',
        backgroundColor: 'rgba(25, 33, 108, 1)',
        borderColor: 'rgba(25, 33, 108, 1)',
        borderWidth: 4,
        pointBackgroundColor: 'rgba(25, 33, 108, 1)',
        pointBorderColor: '#fff',
        lineTension: 0.4,
        pointRadius: 5,
        pointBorderWidth: 2,
        pointHitRadius: 100,
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(25, 33, 108, 1)',
        data: props.repliesData,
      },
      {
        label: 'Sends',
        backgroundColor: 'rgba(123, 147, 219, 1)',
        borderColor: 'rgba(123, 147, 219, 1)',
        borderWidth: 4,
        pointBackgroundColor: 'rgba(123, 147, 219, 1)',
        pointBorderColor: '#fff',
        lineTension: 0.4,
        pointRadius: 5,
        pointBorderWidth: 2,
        pointHitRadius: 100,
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(123, 147, 219, 1)',
        data: props.sendsData,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  //maintainAspectRatio: true,
  elements: {
    point: {
      radius: 6,
      hitRadius: 6,
      hoverRadius: 6,
    },
  },
  legend: {
    display: false,
  },
  interaction: {
    intersect: false,
    mode: 'index',
  },
  tooltips: {
    backgroundColor: 'rgba(0,0,0,0.7)',
    titleSpacing: 4,
    titleMarginBottom: 8,
    titleFontSize: 15,
    bodySpacing: 4,
    bodyFontSize: 14,
    xPadding: 10,
    yPadding: 10,
    displayColors: true,
  },
  hover: {
    mode: 'nearest',
    intersect: true,
  },
  legend: {
    display: false,
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
    },
    y: {
      beginAtZero: true,
      grid: {
        display: false,
      },
      ticks: {
        stepSize: 1,
      },
    },
  },
}
</script>
