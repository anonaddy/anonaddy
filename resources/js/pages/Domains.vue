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
          placeholder="Search Domains"
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
      <div class="mt-4 md:mt-0">
        <button
          @click="addDomainModalOpen = true"
          class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto"
        >
          Add Custom Domain
        </button>
      </div>
    </div>

    <vue-good-table
      v-if="initialDomains.length"
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
        No domains found for that search!
      </div>
      <template slot="table-row" slot-scope="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none text-sm"
          :data-tippy-content="props.row.created_at | formatDate"
          >{{ props.row.created_at | timeAgo }}
        </span>
        <span v-else-if="props.column.field == 'domain'">
          <span
            class="tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            v-clipboard="() => props.row.domain"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            >{{ props.row.domain | truncate(30) }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'description'">
          <div v-if="domainIdToEdit === props.row.id" class="flex items-center">
            <input
              @keyup.enter="editDomain(rows[props.row.originalIndex])"
              @keyup.esc="domainIdToEdit = domainDescriptionToEdit = ''"
              v-model="domainDescriptionToEdit"
              type="text"
              class="flex-grow appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
              :class="
                domainDescriptionToEdit.length > 100 ? 'border-red-500' : 'border-transparent'
              "
              placeholder="Add description"
              tabindex="0"
              autofocus
            />
            <icon
              name="close"
              class="inline-block w-6 h-6 text-red-300 fill-current cursor-pointer"
              @click.native="domainIdToEdit = domainDescriptionToEdit = ''"
            />
            <icon
              name="save"
              class="inline-block w-6 h-6 text-cyan-500 fill-current cursor-pointer"
              @click.native="editDomain(rows[props.row.originalIndex])"
            />
          </div>
          <div v-else-if="props.row.description" class="flex items-centers">
            <span class="tooltip outline-none" :data-tippy-content="props.row.description">{{
              props.row.description | truncate(60)
            }}</span>
            <icon
              name="edit"
              class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer ml-2"
              @click.native="
                ;(domainIdToEdit = props.row.id), (domainDescriptionToEdit = props.row.description)
              "
            />
          </div>
          <div v-else class="flex justify-center">
            <icon
              name="plus"
              class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
              @click.native=";(domainIdToEdit = props.row.id), (domainDescriptionToEdit = '')"
            />
          </div>
        </span>
        <span v-else-if="props.column.field === 'default_recipient'">
          <div v-if="props.row.default_recipient">
            {{ props.row.default_recipient.email | truncate(30) }}
            <icon
              name="edit"
              class="ml-2 inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
              @click.native="openDomainDefaultRecipientModal(props.row)"
            />
          </div>
          <div class="flex justify-center" v-else>
            <icon
              name="plus"
              class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
              @click.native="openDomainDefaultRecipientModal(props.row)"
            />
          </div>
        </span>
        <span v-else-if="props.column.field === 'aliases_count'">
          {{ props.row.aliases.length }}
        </span>
        <span v-else-if="props.column.field === 'active'" class="flex items-center">
          <Toggle
            v-model="rows[props.row.originalIndex].active"
            @on="activateDomain(props.row.id)"
            @off="deactivateDomain(props.row.id)"
          />
        </span>
        <span v-else-if="props.column.field === 'domain_verified_at'">
          <span
            name="check"
            v-if="props.row.domain_verified_at"
            class="py-1 px-2 bg-green-200 text-green-900 rounded-full text-sm"
          >
            verified
          </span>
          <button
            v-else
            @click="recheckRecords(rows[props.row.originalIndex])"
            class="focus:outline-none text-sm"
            :class="recheckRecordsLoading ? 'cursor-not-allowed' : ''"
            :disabled="recheckRecordsLoading"
          >
            Recheck domain
          </button>
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <icon
            name="trash"
            class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
            @click.native="openDeleteModal(props.row.id)"
          />
        </span>
      </template>
    </vue-good-table>

    <div v-else class="bg-white rounded shadow overflow-x-auto">
      <div class="p-8 text-center text-lg text-grey-700">
        <h1 class="mb-6 text-xl text-indigo-800 font-semibold">
          This is where you can set up and view custom domains
        </h1>
        <div class="mx-auto mb-6 w-24 border-b-2 border-grey-200"></div>
        <p class="mb-4">
          To get started all you have to do is add an MX record to your domain and then add the
          domain here by clicking the button above.
        </p>
        <p class="mb-4">
          The new record needs to have the following values:
        </p>
        <p class="mb-4">
          Host: <b>@</b><br />
          Value: <b>{{ hostname }}</b
          ><br />
          Priority: <b>10</b><br />
          TTL: <b>3600</b>
        </p>
        <p>
          Once the DNS changes propagate you will be able to recieve emails at your own domain.
        </p>
      </div>
    </div>

    <Modal :open="addDomainModalOpen" @close="addDomainModalOpen = false">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Add new domain
        </h2>
        <p class="mt-4 text-grey-700">
          Make sure you add the following MX record to your domain first.<br /><br />
          Host: <b>@</b><br />
          Value: <b>{{ hostname }}</b
          ><br />
          Priority: <b>10</b><br />
          TTL: <b>3600</b><br /><br />
          Just include the domain/subdomain e.g. example.com without any http protocol.
        </p>
        <div class="mt-6">
          <p v-show="errors.newDomain" class="mb-3 text-red-500 text-sm">
            {{ errors.newDomain }}
          </p>
          <input
            v-model="newDomain"
            type="text"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 mb-6"
            :class="errors.newDomain ? 'border-red-500' : ''"
            placeholder="example.com"
            autofocus
          />
          <button
            @click="validateNewDomain"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="addDomainLoading ? 'cursor-not-allowed' : ''"
            :disabled="addDomainLoading"
          >
            Add Domain
            <loader v-if="addDomainLoading" />
          </button>
          <button
            @click="addDomainModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="domainDefaultRecipientModalOpen" @close="closeDomainDefaultRecipientModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Update Default Recipient
        </h2>
        <p class="my-4 text-grey-700">
          Select the default recipient for this domain. This overrides the default recipient in your
          account settings. Leave it empty if you would like to use the default recipient in your
          account settings.
        </p>
        <multiselect
          v-model="defaultRecipient"
          :options="recipientOptions"
          :multiple="false"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="false"
          :allow-empty="true"
          placeholder="Select recipient"
          label="email"
          track-by="email"
          :preselect-first="false"
          :show-labels="false"
        >
        </multiselect>
        <div class="mt-6">
          <button
            type="button"
            @click="editDefaultRecipient()"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none"
            :class="editDefaultRecipientLoading ? 'cursor-not-allowed' : ''"
            :disabled="editDefaultRecipientLoading"
          >
            Update Default Recipient
            <loader v-if="editDefaultRecipientLoading" />
          </button>
          <button
            @click="closeDomainDefaultRecipientModal()"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="deleteDomainModalOpen" @close="closeDeleteModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Delete domain
        </h2>
        <p class="mt-4 text-grey-700">
          Are you sure you want to delete this domain? You will no longer be able to receive any
          emails at this domain.
        </p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteDomain(domainIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteDomainLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteDomainLoading"
          >
            Delete domain
            <loader v-if="deleteDomainLoading" />
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
import Toggle from './../components/Toggle.vue'
import tippy from 'tippy.js'
import Multiselect from 'vue-multiselect'

export default {
  props: {
    initialDomains: {
      type: Array,
      required: true,
    },
    hostname: {
      type: String,
      required: true,
    },
    recipientOptions: {
      type: Array,
      required: true,
    },
  },
  components: {
    Modal,
    Toggle,
    Multiselect,
  },
  mounted() {
    this.addTooltips()
  },
  data() {
    return {
      newDomain: '',
      search: '',
      addDomainLoading: false,
      addDomainModalOpen: false,
      domainIdToDelete: null,
      domainIdToEdit: '',
      domainDescriptionToEdit: '',
      deleteDomainLoading: false,
      deleteDomainModalOpen: false,
      recheckRecordsLoading: false,
      domainDefaultRecipientModalOpen: false,
      defaultRecipientDomainToEdit: {},
      defaultRecipient: {},
      editDefaultRecipientLoading: false,
      errors: {},
      columns: [
        {
          label: 'Created',
          field: 'created_at',
          globalSearchDisabled: true,
        },
        {
          label: 'Domain',
          field: 'domain',
        },
        {
          label: 'Description',
          field: 'description',
        },
        {
          label: 'Default Recipient',
          field: 'default_recipient',
          sortable: false,
          globalSearchDisabled: true,
        },
        {
          label: 'Alias Count',
          field: 'aliases_count',
          type: 'number',
          globalSearchDisabled: true,
        },
        {
          label: 'Active',
          field: 'active',
          type: 'boolean',
          globalSearchDisabled: true,
        },
        {
          label: 'Verified',
          field: 'domain_verified_at',
          globalSearchDisabled: true,
        },
        {
          label: '',
          field: 'actions',
          sortable: false,
          globalSearchDisabled: true,
        },
      ],
      rows: this.initialDomains,
    }
  },
  watch: {
    domainIdToEdit: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  methods: {
    addTooltips() {
      tippy('.tooltip', {
        arrow: true,
        arrowType: 'round',
      })
    },
    debounceToolips: _.debounce(function() {
      this.addTooltips()
    }, 50),
    validateNewDomain(e) {
      this.errors = {}

      if (!this.newDomain) {
        this.errors.newDomain = 'Domain name required'
      } else if (!this.validDomain(this.newDomain)) {
        this.errors.newDomain = 'Please enter a valid domain name'
      }

      if (!this.errors.newDomain) {
        this.addNewDomain()
      }

      e.preventDefault()
    },
    addNewDomain() {
      this.addDomainLoading = true

      axios
        .post(
          '/api/v1/domains',
          JSON.stringify({
            domain: this.newDomain,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.addDomainLoading = false
          this.rows.push(data.data)
          this.newDomain = ''
          this.addDomainModalOpen = false
          this.success('Custom domain added')
        })
        .catch(error => {
          this.addDomainLoading = false
          if (error.response.status === 422) {
            this.error(error.response.data.errors.domain[0])
          } else {
            this.error()
          }
        })
    },
    recheckRecords(domain) {
      this.recheckRecordsLoading = true

      axios
        .get(`/domains/${domain.id}/recheck`)
        .then(({ data }) => {
          this.recheckRecordsLoading = false

          if (data.success === true) {
            this.success(data.message)
            domain.domain_verified_at = data.data.domain_verified_at
          } else {
            this.warn(data.message)
          }
        })
        .catch(error => {
          this.recheckRecordsLoading = false
          if (error.response.status === 429) {
            this.error('You can only recheck the records once per minute')
          } else {
            this.error()
          }
        })
    },
    openDeleteModal(id) {
      this.deleteDomainModalOpen = true
      this.domainIdToDelete = id
    },
    closeDeleteModal() {
      this.deleteDomainModalOpen = false
      this.domainIdToDelete = null
    },
    openDomainDefaultRecipientModal(domain) {
      this.domainDefaultRecipientModalOpen = true
      this.defaultRecipientDomainToEdit = domain
      this.defaultRecipient = domain.default_recipient
    },
    closeDomainDefaultRecipientModal() {
      this.domainDefaultRecipientModalOpen = false
      this.defaultRecipientDomainToEdit = {}
      this.defaultRecipient = {}
    },
    editDomain(domain) {
      if (this.domainDescriptionToEdit.length > 100) {
        return this.error('Description cannot be more than 100 characters')
      }

      axios
        .patch(
          `/api/v1/domains/${domain.id}`,
          JSON.stringify({
            description: this.domainDescriptionToEdit,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          domain.description = this.domainDescriptionToEdit
          this.domainIdToEdit = ''
          this.domainDescriptionToEdit = ''
          this.success('Domain description updated')
        })
        .catch(error => {
          this.domainIdToEdit = ''
          this.domainDescriptionToEdit = ''
          this.error()
        })
    },
    editDefaultRecipient() {
      this.editDefaultRecipientLoading = true

      axios
        .patch(
          `/api/v1/domains/${this.defaultRecipientDomainToEdit.id}/default-recipient`,
          JSON.stringify({
            default_recipient: this.defaultRecipient ? this.defaultRecipient.id : '',
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          let domain = _.find(this.rows, ['id', this.defaultRecipientDomainToEdit.id])
          domain.default_recipient = this.defaultRecipient

          this.domainDefaultRecipientModalOpen = false
          this.editDefaultRecipientLoading = false
          this.defaultRecipient = {}
          this.success("Domain's default recipient updated")
        })
        .catch(error => {
          this.domainDefaultRecipientModalOpen = false
          this.editDefaultRecipientLoading = false
          this.defaultRecipient = {}
          this.error()
        })
    },
    activateDomain(id) {
      axios
        .post(
          `/api/v1/active-domains`,
          JSON.stringify({
            id: id,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    deactivateDomain(id) {
      axios
        .delete(`/api/v1/active-domains/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    deleteDomain(id) {
      this.deleteDomainLoading = true

      axios
        .delete(`/api/v1/domains/${id}`)
        .then(response => {
          this.rows = _.reject(this.rows, domain => domain.id === id)
          this.deleteDomainModalOpen = false
          this.deleteDomainLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteDomainLoading = false
          this.deleteDomainModalOpen = false
        })
    },
    validDomain(domain) {
      let re = /(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{0,62}[a-zA-Z0-9]\.)+[a-zA-Z]{2,63}$)/
      return re.test(domain)
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
