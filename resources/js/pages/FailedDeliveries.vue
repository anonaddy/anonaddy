<template>
  <div>
    <div class="mb-6 flex flex-col md:flex-row justify-between md:items-center">
      <div class="relative">
        <input
          v-model="search"
          @keyup.esc="search = ''"
          tabindex="0"
          type="text"
          class="w-full md:w-64 appearance-none shadow bg-white text-grey-700 focus:outline-none rounded py-3 pl-3 pr-8"
          placeholder="Search Failed Deliveries"
        />
        <icon
          v-if="search"
          @click.native="search = ''"
          name="close-circle"
          class="absolute right-0 inset-y-0 w-5 h-full text-grey-300 fill-current mr-2 flex items-center cursor-pointer"
        />
        <icon
          v-else
          name="search"
          class="absolute right-0 inset-y-0 w-5 h-full text-grey-300 fill-current pointer-events-none mr-2 flex items-center"
        />
      </div>
    </div>

    <vue-good-table
      v-if="initialFailedDeliveries.length"
      @on-search="debounceToolips"
      :columns="columns"
      :rows="rows"
      :search-options="{
        enabled: true,
        skipDiacritics: true,
        externalQuery: search,
      }"
      :sort-options="{
        enabled: true,
        initialSortBy: { field: 'created_at', type: 'desc' },
      }"
      styleClass="vgt-table"
    >
      <div slot="emptystate" class="flex items-center justify-center h-24 text-lg text-grey-700">
        No failed deliveries found for that search!
      </div>
      <template slot="table-row" slot-scope="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none text-sm"
          :data-tippy-content="rows[props.row.originalIndex].created_at | formatDate"
          >{{ props.row.created_at | timeAgo }}
        </span>
        <span v-else-if="props.column.field == 'recipient'">
          <span
            class="tooltip cursor-pointer outline-none text-sm"
            data-tippy-content="Click to copy"
            v-clipboard="
              () =>
                rows[props.row.originalIndex].recipient
                  ? rows[props.row.originalIndex].recipient.email
                  : ''
            "
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            >{{ props.row.recipient ? props.row.recipient.email : '' }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'alias'">
          <span
            class="tooltip cursor-pointer outline-none text-sm"
            data-tippy-content="Click to copy"
            v-clipboard="
              () =>
                rows[props.row.originalIndex].alias ? rows[props.row.originalIndex].alias.email : ''
            "
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            >{{ props.row.alias ? props.row.alias.email : '' }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'bounce_type'" class="text-sm">
          {{ props.row.bounce_type }}
        </span>
        <span v-else-if="props.column.field == 'remote_mta'" class="text-sm">
          {{ props.row.remote_mta }}
        </span>
        <span v-else-if="props.column.field == 'sender'" class="text-sm">
          <span
            class="tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            v-clipboard="() => rows[props.row.originalIndex].sender"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            >{{ props.row.sender }}</span
          >
        </span>
        <!-- <span v-else-if="props.column.field == 'email_type'" class="text-sm">
          {{ props.row.email_type }}
        </span>
        <span v-else-if="props.column.field == 'status'" class="text-sm">
          {{ props.row.status }}
        </span> -->
        <span v-else-if="props.column.field == 'code'" class="text-sm">
          {{ props.row.code }}
        </span>
        <span
          v-else-if="props.column.field == 'attempted_at'"
          class="tooltip outline-none text-sm"
          :data-tippy-content="rows[props.row.originalIndex].attempted_at | formatDateTime"
          >{{ props.row.attempted_at | timeAgo }}
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <icon
            name="trash"
            class="block w-6 h-6 text-grey-300 fill-current cursor-pointer"
            @click.native="openDeleteModal(props.row.id)"
          />
        </span>
      </template>
    </vue-good-table>

    <div v-else class="bg-white rounded shadow overflow-x-auto">
      <div class="p-8 text-center text-lg text-grey-700">
        <h1 class="mb-6 text-xl text-indigo-800 font-semibold">
          This is where you can see failed email delivery attempts
        </h1>
        <div class="mx-auto mb-6 w-24 border-b-2 border-grey-200"></div>
        <p class="mb-4">
          Sometimes when AnonAddy attempts to send an email, the delivery is not successful. This is
          often referred to as a "bounced email".
        </p>
        <p>
          This page allows you to see any failed deliveries relating to your account and the reason
          why they failed.
        </p>
      </div>
    </div>

    <Modal :open="deleteFailedDeliveryModalOpen" @close="closeDeleteModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Delete Failed Delivery
        </h2>
        <p class="mt-4 text-grey-700">Are you sure you want to delete this failed delivery?</p>
        <p class="mt-4 text-grey-700">
          Failed deliveries are automatically removed when they are more than 3 days old.
        </p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteFailedDelivery(failedDeliveryIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteFailedDeliveryLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteFailedDeliveryLoading"
          >
            Delete failed delivery
            <loader v-if="deleteFailedDeliveryLoading" />
          </button>
          <button
            @click="closeDeleteModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script>
import Modal from './../components/Modal.vue'
import { roundArrow } from 'tippy.js'
import 'tippy.js/dist/svg-arrow.css'
import 'tippy.js/dist/tippy.css'
import tippy from 'tippy.js'

export default {
  props: {
    initialFailedDeliveries: {
      type: Array,
      required: true,
    },
  },
  components: {
    Modal,
  },
  data() {
    return {
      search: '',
      errors: {},
      deleteFailedDeliveryLoading: false,
      deleteFailedDeliveryModalOpen: false,
      failedDeliveryIdToDelete: null,
      columns: [
        {
          label: 'Created',
          field: 'created_at',
          globalSearchDisabled: true,
        },
        {
          label: 'Recipient',
          field: 'recipient',
          globalSearchDisabled: true,
        },
        {
          label: 'Alias',
          field: 'alias',
          globalSearchDisabled: true,
        },
        {
          label: 'Type',
          field: 'bounce_type',
        },
        {
          label: 'Remote MTA',
          field: 'remote_mta',
        },
        {
          label: 'Sender',
          field: 'sender',
        },
        /* {
          label: 'Email Type',
          field: 'email_type',
        }, */
        /* {
          label: 'Status',
          field: 'status',
        }, */
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
      ],
      rows: this.initialFailedDeliveries,
      tippyInstance: null,
    }
  },
  methods: {
    addTooltips() {
      if (this.tippyInstance) {
        _.each(this.tippyInstance, instance => instance.destroy())
      }

      this.tippyInstance = tippy('.tooltip', {
        arrow: roundArrow,
        allowHTML: true,
      })
    },
    debounceToolips: _.debounce(function () {
      this.addTooltips()
    }, 50),
    openDeleteModal(id) {
      this.deleteFailedDeliveryModalOpen = true
      this.failedDeliveryIdToDelete = id
    },
    closeDeleteModal() {
      this.deleteFailedDeliveryModalOpen = false
      this.failedDeliveryIdToDelete = null
    },
    deleteFailedDelivery(id) {
      this.deleteFailedDeliveryLoading = true

      axios
        .delete(`/api/v1/failed-deliveries/${id}`)
        .then(response => {
          this.rows = _.reject(this.rows, delivery => delivery.id === id)
          this.deleteFailedDeliveryModalOpen = false
          this.deleteFailedDeliveryLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteFailedDeliveryLoading = false
          this.deleteFailedDeliveryModalOpen = false
        })
    },
    clipboardSuccess() {
      this.success('Copied to clipboard')
    },
    clipboardError() {
      this.error('Could not copy to clipboard')
    },
    warn(text = '') {
      this.$notify({
        title: 'Information',
        text: text,
        type: 'warn',
      })
    },
    success(text = '') {
      this.$notify({
        title: 'Success',
        text: text,
        type: 'success',
      })
    },
    error(text = 'An error has occurred, please try again later') {
      this.$notify({
        title: 'Error',
        text: text,
        type: 'error',
      })
    },
  },
}
</script>
