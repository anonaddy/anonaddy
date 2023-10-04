<template>
  <Doughnut :data="chartData" :options="chartOptions" style="max-height: 350px" />
</template>

<script setup>
import { computed } from 'vue'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  totals: {
    type: Array,
    required: true,
  },
})

const chartData = computed(() => {
  return {
    labels: ['Forwards', 'Replies', 'Sends'],
    datasets: [
      {
        label: 'Total',
        backgroundColor: [
          'rgba(28, 212, 212, 1)',
          'rgba(25, 33, 108, 1)',
          'rgba(123, 147, 219, 1)',
        ],
        hoverOffset: 4,
        data: props.totals,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: true,
}
</script>
