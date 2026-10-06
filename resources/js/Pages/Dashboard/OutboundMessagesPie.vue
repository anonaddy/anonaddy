<template>
  <Doughnut :data="chartData" :options="chartOptions" style="max-height: 350px" />
</template>

<script setup>
import { computed } from 'vue'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'
import { CHART_CYAN, CHART_PERIWINKLE, useChartTheme } from './chartTheme'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  totals: {
    type: Array,
    required: true,
  },
})

const { textColor, navy } = useChartTheme()

const chartData = computed(() => {
  return {
    labels: ['Forwards', 'Replies', 'Sends'],
    datasets: [
      {
        label: 'Total',
        backgroundColor: [CHART_CYAN, navy.value, CHART_PERIWINKLE],
        hoverOffset: 4,
        data: props.totals,
      },
    ],
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: true,
  color: textColor.value,
  plugins: {
    legend: {
      labels: {
        color: textColor.value,
      },
    },
  },
}))
</script>
