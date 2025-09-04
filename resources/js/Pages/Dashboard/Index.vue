<template>
  <div>
    <Head title="Dashboard" />
    <h1 id="primary-heading" class="sr-only">Dashboard</h1>

    <div
      v-if="bandwidthPercentage === 100"
      class="text-base border-t-8 rounded text-yellow-800 border-yellow-600 bg-yellow-100 px-3 py-4 mb-4"
      role="alert"
    >
      <div class="flex items-center mb-2">
        <span class="rounded-full bg-yellow-400 uppercase px-2 py-1 text-xs font-bold mr-2"
          >Warning</span
        >
        <div>
          Exceeded bandwidth limit for <b>{{ month }}</b
          >.
        </div>
      </div>
    </div>

    <h1 class="text-2xl font-semibold text-grey-900">Dashboard</h1>

    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
      <div
        v-for="item in stats"
        :key="item.id"
        class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6"
      >
        <dt>
          <div class="absolute rounded-md bg-indigo-500 p-3">
            <component :is="item.icon" class="h-6 w-6 text-white" aria-hidden="true" />
          </div>
          <p class="ml-16 truncate text-sm font-medium text-grey-500">{{ item.name }}</p>
        </dt>
        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
          <p class="text-2xl font-semibold text-indigo-800">{{ item.stat.toLocaleString() }}</p>
          <div class="absolute inset-x-0 bottom-0 bg-grey-50 px-4 py-4 sm:px-6">
            <div class="text-sm">
              <Link :href="item.url" class="font-medium text-indigo-600 hover:text-indigo-500">
                View all<span class="sr-only"> {{ item.name }} stats</span>
              </Link>
            </div>
          </div>
        </dd>
      </div>
    </dl>

    <h3 class="mt-6 text-base font-semibold leading-6 text-grey-600">Bandwidth ({{ month }})</h3>

    <div class="mt-6">
      <div class="overflow-hidden rounded-full bg-grey-100 relative">
        <div
          class="relative h-8 flex items-center justify-end rounded-full bg-gradient-to-r z-10 min-w-[25%] sm:min-w-fit"
          :class="bandwidthPercentageClass"
          :style="`width: ${bandwidthPercentage}%`"
        >
          <span class="text-white px-4 font-semibold">{{ bandwidthMb }}MB</span>
        </div>
        <span class="h-8 absolute top-0 right-0 flex items-center pr-4 text-grey-600 font-semibold"
          >{{ bandwidthLimit }}MB</span
        >
      </div>
    </div>

    <div class="mt-6">
      <h3 class="text-base font-semibold leading-6 text-grey-600">Aliases</h3>

      <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="item in aliasStats"
          :key="item.id"
          class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6"
        >
          <dt>
            <div class="absolute rounded-md bg-indigo-500 p-3">
              <component :is="item.icon" class="h-6 w-6 text-white" aria-hidden="true" />
            </div>
            <p class="ml-16 truncate text-sm font-medium text-grey-500">{{ item.name }}</p>
          </dt>
          <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
            <p class="text-2xl font-semibold text-indigo-800">{{ item.stat.toLocaleString() }}</p>
            <div class="absolute inset-x-0 bottom-0 bg-grey-50 px-4 py-4 sm:px-6">
              <div class="text-sm">
                <Link :href="item.url" class="font-medium text-indigo-600 hover:text-indigo-500">
                  {{ item.id === 1 ? 'View All' : `View ${item.name}`
                  }}<span class="sr-only"> {{ item.name }} stats</span>
                </Link>
              </div>
            </div>
          </dd>
        </div>
      </dl>

      <h3 class="mt-6 text-base font-semibold leading-6 text-grey-600">Stats</h3>

      <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative overflow-hidden rounded-lg bg-white p-4 shadow">
          <dt>
            <icon
              name="send"
              class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
            />
            <p class="truncate text-sm font-medium text-grey-400">Emails Forwarded</p>
          </dt>
          <dd class="flex items-baseline">
            <p class="text-3xl font-semibold text-indigo-800">
              {{ parseInt(totals.forwarded).toLocaleString() }}
            </p>
          </dd>
        </div>
        <div class="relative overflow-hidden rounded-lg bg-white p-4 shadow">
          <dt>
            <icon
              name="blocked"
              class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
            />
            <p class="truncate text-sm font-medium text-grey-400">Emails Blocked</p>
          </dt>
          <dd class="flex items-baseline">
            <p class="text-3xl font-semibold text-indigo-800">
              {{ parseInt(totals.blocked).toLocaleString() }}
            </p>
          </dd>
        </div>
        <div class="relative overflow-hidden rounded-lg bg-white p-4 shadow">
          <dt>
            <icon
              name="corner-up-left"
              class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
            />
            <p class="truncate text-sm font-medium text-grey-400">Email Replies</p>
          </dt>
          <dd class="flex items-baseline">
            <p class="text-3xl font-semibold text-indigo-800">
              {{ parseInt(totals.replies).toLocaleString() }}
            </p>
          </dd>
        </div>
        <div class="relative overflow-hidden rounded-lg bg-white p-4 shadow">
          <dt>
            <icon
              name="arrow-right"
              class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
            />
            <p class="truncate text-sm font-medium text-grey-400">Emails Sent</p>
          </dt>
          <dd class="flex items-baseline">
            <p class="text-3xl font-semibold text-indigo-800">{{ totals.sent.toLocaleString() }}</p>
          </dd>
        </div>
      </dl>
    </div>

    <h3 class="mt-6 text-base font-semibold leading-6 text-grey-600">
      Outbound Messages Last 7 Days <loader v-if="chartsLoading" />
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div
        class="mt-5 bg-white overflow-hidden shadow sm:rounded-lg p-4 flex justify-center max-h-80"
      >
        <outbound-messages-graph
          :forwards-data="forwardsData"
          :replies-data="repliesData"
          :sends-data="sendsData"
          :labels="labels"
        />
      </div>
      <div
        class="mt-5 bg-white overflow-hidden shadow sm:rounded-lg p-4 flex justify-center max-h-80"
      >
        <div v-if="!outboundMessageTotals" class="flex items-center justify-center">
          No data to display
        </div>
        <outbound-messages-pie v-else :totals="outboundMessageTotals" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  AtSymbolIcon,
  InboxArrowDownIcon,
  UsersIcon,
  GlobeAltIcon,
  FunnelIcon,
  CheckCircleIcon,
  XCircleIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import OutboundMessagesGraph from './OutboundMessagesGraph.vue'
import OutboundMessagesPie from './OutboundMessagesPie.vue'

const props = defineProps({
  totals: {
    type: Object,
    required: true,
  },
  bandwidthMb: {
    type: Number,
    required: true,
  },
  bandwidthLimit: {
    type: Number,
    required: true,
  },
  month: {
    type: String,
    required: true,
  },
  aliases: {
    type: Number,
    required: true,
  },
  recipients: {
    type: Number,
    required: true,
  },
  usernames: {
    type: Number,
    required: true,
  },
  domains: {
    type: Number,
    required: true,
  },
  rules: {
    type: Number,
    required: true,
  },
})

const chartsLoading = ref(true)
const forwardsData = ref([])
const repliesData = ref([])
const sendsData = ref([])
const labels = ref([])
const outboundMessageTotals = ref(null)

onMounted(() => {
  axios.get('/api/v1/chart-data').then(response => {
    forwardsData.value = response.data.forwardsData
    repliesData.value = response.data.repliesData
    sendsData.value = response.data.sendsData
    labels.value = response.data.labels
    outboundMessageTotals.value = response.data.outboundMessageTotals

    if (_.isEqual(outboundMessageTotals.value, [0, 0, 0])) {
      outboundMessageTotals.value = null
    }

    chartsLoading.value = false
  })
})

const bandwidthPercentage = computed(() => {
  if (props.bandwidthMb) {
    let percent = ((props.bandwidthMb / props.bandwidthLimit) * 100).toFixed(2)

    return percent > 100 ? 100 : percent
  } else {
    return 0
  }
})

const bandwidthPercentageClass = computed(() => {
  if (bandwidthPercentage.value === 100) {
    return 'from-red-200 to-red-500'
  }

  if (bandwidthPercentage.value > 80) {
    return 'from-yellow-200 to-yellow-600'
  }

  return 'from-cyan-500 to-indigo-500'
})

const stats = [
  {
    id: 1,
    name: 'Shared Domain Aliases',
    stat: props.aliases,
    icon: AtSymbolIcon,
    url: route('aliases.index', { shared_domain: 'true', active: 'true' }),
  },
  {
    id: 2,
    name: 'Recipients',
    stat: props.recipients,
    icon: InboxArrowDownIcon,
    url: route('recipients.index'),
  },
  {
    id: 3,
    name: 'Usernames',
    stat: props.usernames,
    icon: UsersIcon,
    url: route('usernames.index'),
  },
  {
    id: 4,
    name: 'Domains',
    stat: props.domains,
    icon: GlobeAltIcon,
    url: route('domains.index'),
  },
  {
    id: 5,
    name: `Rules`,
    stat: props.rules,
    icon: FunnelIcon,
    url: route('rules.index'),
  },
]

const aliasStats = [
  {
    id: 1,
    name: 'Total Aliases',
    stat: parseInt(props.totals.total),
    icon: AtSymbolIcon,
    url: route('aliases.index', { deleted: 'with' }),
  },
  {
    id: 2,
    name: 'Active',
    stat: parseInt(props.totals.active),
    icon: CheckCircleIcon,
    url: route('aliases.index', { active: 'true' }),
  },
  {
    id: 3,
    name: 'Inactive',
    stat: parseInt(props.totals.inactive),
    icon: XCircleIcon,
    url: route('aliases.index', { active: 'false' }),
  },
  {
    id: 4,
    name: 'Deleted',
    stat: parseInt(props.totals.deleted),
    icon: TrashIcon,
    url: route('aliases.index', { deleted: 'only' }),
  },
]
</script>
