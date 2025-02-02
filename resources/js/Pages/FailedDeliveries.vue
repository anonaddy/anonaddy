<template>
  <div>
    <Head title="Failed Deliveries" />
    <h1 id="primary-heading" class="sr-only">Failed Deliveries</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900">Failed Deliveries</h1>
        <p class="mt-2 text-sm text-grey-700">
          A list of all the failed deliveries
          {{ search ? 'found for your search' : 'in your account' }}
          <button @click="moreInfoOpen = !moreInfoOpen">
            <InformationCircleIcon
              class="h-6 w-6 inline-block cursor-pointer text-grey-500"
              title="Click for more information"
            />
          </button>
        </p>
      </div>
    </div>

    <vue-good-table
      v-if="rows.length"
      v-on:sort-change="debounceToolips"
      :columns="columns"
      :rows="rows"
      :sort-options="{
        enabled: true,
        initialSortBy: { field: 'created_at', type: 'desc' },
      }"
      styleClass="vgt-table"
    >
      <template #table-row="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none cursor-default text-sm text-grey-500"
          :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
          >{{ $filters.timeAgo(props.row.created_at) }}
        </span>
        <span v-else-if="props.column.field == 'email_type'" class="text-sm text-grey-500">
          {{ props.row.email_type }}
        </span>
        <span v-else-if="props.column.field == 'recipient'">
          <span
            class="tooltip cursor-pointer outline-none text-sm font-medium text-grey-700"
            data-tippy-content="Click to copy"
            @click="
              clipboard(
                rows[props.row.originalIndex].recipient
                  ? rows[props.row.originalIndex].recipient.email
                  : rows[props.row.originalIndex].destination,
              )
            "
            >{{
              props.row.recipient
                ? props.row.recipient.email
                : rows[props.row.originalIndex].destination
            }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'alias'">
          <span
            class="tooltip cursor-pointer outline-none text-sm font-medium text-grey-700"
            data-tippy-content="Click to copy"
            @click="
              clipboard(
                rows[props.row.originalIndex].alias
                  ? rows[props.row.originalIndex].alias.email
                  : '',
              )
            "
            >{{ props.row.alias ? props.row.alias.email : '' }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'sender'" class="text-sm font-medium text-grey-700">
          <span
            class="tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            @click="clipboard(rows[props.row.originalIndex].sender)"
            >{{ props.row.sender }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'remote_mta'" class="text-sm text-grey-500">
          {{ props.row.remote_mta }}
        </span>
        <span v-else-if="props.column.field == 'code'" class="text-sm text-grey-500">
          {{ props.row.code }}
        </span>
        <span
          v-else-if="props.column.field == 'attempted_at'"
          class="tooltip outline-none text-sm text-grey-500"
          :data-tippy-content="$filters.formatDateTime(rows[props.row.originalIndex].attempted_at)"
          >{{ $filters.timeAgo(props.row.attempted_at) }}
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <a
            v-if="props.row.is_stored"
            :href="'api/v1/failed-deliveries/' + props.row.id + '/download'"
            class="mr-4 text-indigo-500 hover:text-indigo-800 font-medium"
          >
            Download
          </a>
          <button
            @click="openDeleteModal(props.row.id)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 font-medium"
          >
            Delete
          </button>
        </span>
      </template>
    </vue-good-table>

    <div v-else-if="search" class="text-center">
      <ExclamationTriangleIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">
        No Failed Deliveries found for that search
      </h3>
      <p class="mt-1 text-md text-grey-500">Try entering a different search term.</p>
      <div class="mt-6">
        <Link
          :href="route('failed_deliveries.index')"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          View All Failed Deliveries
        </Link>
      </div>
    </div>

    <div v-else class="text-center">
      <ExclamationTriangleIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">No Failed Deliveries</h3>
      <p class="mt-1 text-md text-grey-500">
        You don't have any failed delivery attempts to display.
      </p>
    </div>

    <Modal :open="deleteFailedDeliveryModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete Failed Delivery </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">Are you sure you want to delete this failed delivery?</p>
        <p class="mt-4 text-grey-700">
          Failed deliveries are <b>automatically removed</b> when they are more than
          <b>7 days old</b>. Deleting a failed delivery also deletes the email if it has been
          stored.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteFailedDelivery(failedDeliveryIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteFailedDeliveryLoading"
          >
            Delete failed delivery
            <loader v-if="deleteFailedDeliveryLoading" />
          </button>
          <button
            @click="closeDeleteModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="moreInfoOpen" @close="moreInfoOpen = false">
      <template v-slot:title> More information </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Sometimes when addy.io attempts to send an email, the delivery is not successful. This is
          often referred to as a "bounced email".
        </p>
        <p class="mt-4 text-grey-700">
          This page allows you to see any failed deliveries relating to your account and the reason
          why they failed.
        </p>
        <p class="mt-4 text-grey-700">
          Only failed delivery attempts from the addy.io servers to your recipients (or reply/send
          attempts from your aliases) will be shown here. It will not show messages that failed to
          reach the addy.io server from some other sender.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row">
          <button
            @click="moreInfoOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Modal from '../Components/Modal.vue'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import { notify } from '@kyvg/vue3-notification'
import { VueGoodTable } from 'vue-good-table-next'
import { InformationCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  initialRows: {
    type: Array,
    required: true,
  },
  search: {
    type: String,
  },
})

const rows = ref(props.initialRows)

const deleteFailedDeliveryLoading = ref(false)
const deleteFailedDeliveryModalOpen = ref(false)
const moreInfoOpen = ref(false)
const failedDeliveryIdToDelete = ref(null)
const tippyInstance = ref(null)
const errors = ref({})

const columns = [
  {
    label: 'Created',
    field: 'created_at',
    globalSearchDisabled: true,
  },
  {
    label: 'Email Type',
    field: 'email_type',
  },
  {
    label: 'Destination',
    field: 'recipient',
    globalSearchDisabled: true,
  },
  {
    label: 'Alias',
    field: 'alias',
    globalSearchDisabled: true,
  },
  {
    label: 'Sender',
    field: 'sender',
  },
  {
    label: 'Mail Server',
    field: 'remote_mta',
  },
  {
    label: 'Code',
    field: 'code',
    sortable: false,
  },
  {
    label: 'First Attempted',
    field: 'attempted_at',
    globalSearchDisabled: true,
  },
  {
    label: '',
    field: 'actions',
    sortable: false,
    globalSearchDisabled: true,
  },
]

const deleteFailedDelivery = id => {
  deleteFailedDeliveryLoading.value = true

  axios
    .delete(`/api/v1/failed-deliveries/${id}`)
    .then(response => {
      rows.value = _.reject(rows.value, delivery => delivery.id === id)
      deleteFailedDeliveryModalOpen.value = false
      deleteFailedDeliveryLoading.value = false
    })
    .catch(error => {
      errorMessage()
      deleteFailedDeliveryLoading.value = false
      deleteFailedDeliveryModalOpen.value = false
    })
}

const openDeleteModal = id => {
  deleteFailedDeliveryModalOpen.value = true
  failedDeliveryIdToDelete.value = id
}

const closeDeleteModal = () => {
  deleteFailedDeliveryModalOpen.value = false
  failedDeliveryIdToDelete.value = null
}

const addTooltips = () => {
  if (tippyInstance.value) {
    _.each(tippyInstance.value, instance => instance.destroy())
  }

  tippyInstance.value = tippy('.tooltip', {
    arrow: roundArrow,
    allowHTML: true,
  })
}

const debounceToolips = _.debounce(function () {
  addTooltips()
}, 50)

const clipboard = (str, success, error) => {
  // Needed as v-clipboard doesn't work inside modals!
  navigator.clipboard.writeText(str).then(
    () => {
      successMessage('Copied to clipboard')
    },
    () => {
      errorMessage('Could not copy to clipboard')
    },
  )
}

const successMessage = (text = '') => {
  notify({
    title: 'Success',
    text: text,
    type: 'success',
  })
}

const errorMessage = (text = 'An error has occurred, please try again later') => {
  notify({
    title: 'Error',
    text: text,
    type: 'error',
  })
}
</script>
