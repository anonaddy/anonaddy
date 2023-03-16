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
          @click="search = ''"
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
      v-on:search="debounceToolips"
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
      <template #emptystate class="flex items-center justify-center h-24 text-lg text-grey-700">
        No domains found for that search!
      </template>
      <template #table-row="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none text-sm"
          :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
          >{{ $filters.timeAgo(props.row.created_at) }}
        </span>
        <span v-else-if="props.column.field == 'domain'">
          <span
            class="tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            v-clipboard="() => rows[props.row.originalIndex].domain"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            >{{ $filters.truncate(props.row.domain, 30) }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'description'">
          <div v-if="domainIdToEdit === props.row.id" class="flex items-center">
            <input
              @keyup.enter="editDomain(rows[props.row.originalIndex])"
              @keyup.esc="domainIdToEdit = domainDescriptionToEdit = ''"
              v-model="domainDescriptionToEdit"
              type="text"
              class="grow appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
              :class="
                domainDescriptionToEdit.length > 200 ? 'border-red-500' : 'border-transparent'
              "
              placeholder="Add description"
              tabindex="0"
              autofocus
            />
            <icon
              name="close"
              class="inline-block w-6 h-6 text-red-300 fill-current cursor-pointer"
              @click="domainIdToEdit = domainDescriptionToEdit = ''"
            />
            <icon
              name="save"
              class="inline-block w-6 h-6 text-cyan-500 fill-current cursor-pointer"
              @click="editDomain(rows[props.row.originalIndex])"
            />
          </div>
          <div v-else-if="props.row.description" class="flex items-centers">
            <span class="outline-none">{{ $filters.truncate(props.row.description, 60) }}</span>
            <icon
              name="edit"
              class="inline-block w-6 h-6 text-grey-300 fill-current cursor-pointer ml-2"
              @click="
                ;(domainIdToEdit = props.row.id), (domainDescriptionToEdit = props.row.description)
              "
            />
          </div>
          <div v-else class="flex justify-center">
            <icon
              name="plus"
              class="block w-6 h-6 text-grey-300 fill-current cursor-pointer"
              @click=";(domainIdToEdit = props.row.id), (domainDescriptionToEdit = '')"
            />
          </div>
        </span>
        <span v-else-if="props.column.field === 'default_recipient'">
          <div v-if="props.row.default_recipient">
            {{ $filters.truncate(props.row.default_recipient.email, 30) }}
            <icon
              name="edit"
              class="ml-2 inline-block w-6 h-6 text-grey-300 fill-current cursor-pointer"
              @click="openDomainDefaultRecipientModal(props.row)"
            />
          </div>
          <div class="flex justify-center" v-else>
            <icon
              name="plus"
              class="block w-6 h-6 text-grey-300 fill-current cursor-pointer"
              @click="openDomainDefaultRecipientModal(props.row)"
            />
          </div>
        </span>
        <span v-else-if="props.column.field === 'aliases_count'">
          {{ props.row.aliases_count }}
        </span>
        <span v-else-if="props.column.field === 'active'" class="flex items-center">
          <Toggle
            v-model="rows[props.row.originalIndex].active"
            @on="activateDomain(props.row.id)"
            @off="deactivateDomain(props.row.id)"
          />
        </span>
        <span v-else-if="props.column.field === 'catch_all'" class="flex items-center">
          <Toggle
            v-model="rows[props.row.originalIndex].catch_all"
            @on="enableCatchAll(props.row.id)"
            @off="disableCatchAll(props.row.id)"
          />
        </span>
        <span v-else-if="props.column.field === 'domain_sending_verified_at'">
          <div v-if="props.row.domain_sending_verified_at || props.row.domain_mx_validated_at">
            <svg
              v-if="props.row.domain_sending_verified_at && props.row.domain_mx_validated_at"
              class="h-5 w-5 inline-block"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
            >
              <g fill="none" fill-rule="evenodd">
                <circle class="text-green-200 fill-current" cx="10" cy="10" r="10"></circle>
                <polyline
                  class="text-green-900 stroke-current"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  points="6 10 8.667 12.667 14 7.333"
                ></polyline>
              </g>
            </svg>
            <svg
              v-else-if="!props.row.domain_mx_validated_at"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              class="h-5 w-5 inline-block tooltip"
              data-tippy-content="MX records invalid"
            >
              <g fill="none" fill-rule="evenodd">
                <circle cx="10" cy="10" r="10" fill="#FF9B9B"></circle>
                <polyline
                  stroke="#AB091E"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  points="14 6 6 14"
                ></polyline>
                <polyline
                  stroke="#AB091E"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  points="6 6 14 14"
                ></polyline>
              </g>
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              class="h-5 w-5 inline-block tooltip"
              data-tippy-content="DNS records for sending invalid"
            >
              <g fill="none" fill-rule="evenodd">
                <circle cx="10" cy="10" r="10" fill="#FF9B9B"></circle>
                <polyline
                  stroke="#AB091E"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  points="14 6 6 14"
                ></polyline>
                <polyline
                  stroke="#AB091E"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  points="6 6 14 14"
                ></polyline>
              </g>
            </svg>
            <button
              @click="openCheckRecordsModal(rows[props.row.originalIndex])"
              class="focus:outline-none text-sm ml-2"
            >
              Recheck
            </button>
          </div>
          <button
            v-else
            @click="openCheckRecordsModal(rows[props.row.originalIndex])"
            class="focus:outline-none text-sm"
          >
            Check Records
          </button>
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <icon
            name="trash"
            class="block w-6 h-6 text-grey-300 fill-current cursor-pointer"
            @click="openDeleteModal(props.row.id)"
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
          To get started all you have to do is add a TXT record to your domain to verify ownership
          and then add the domain here by clicking the button above.
        </p>
        <p class="mb-4">The TXT record needs to have the following values:</p>
        <p class="mb-4">
          Type: <b>TXT</b><br />
          Host: <b>@</b><br />
          Value: <b>aa-verify={{ aaVerify }}</b
          ><br />
        </p>
        <p>
          Once the DNS changes propagate and you have verified ownership of the domain you will need
          to add a few more records to be able to receive emails at your own domain.
        </p>
      </div>
    </div>

    <Modal :open="addDomainModalOpen" @close="closeCheckRecordsModal">
      <template v-if="!domainToCheck" v-slot:title> Add new domain </template>
      <template v-else v-slot:title> Check DNS records </template>
      <template v-if="!domainToCheck" v-slot:content>
        <p class="mt-4 mb-2 text-grey-700">
          To verify ownership of the domain, please add the following TXT record and then click Add
          Domain below.
        </p>
        <div class="table w-full">
          <div class="table-row">
            <div class="table-cell py-2 font-semibold">Type</div>
            <div class="table-cell p-2 font-semibold">Host</div>
            <div class="table-cell py-2 font-semibold">Value/Points to</div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">TXT</div>
            <div class="table-cell p-2">@</div>
            <div class="table-cell py-2 break-all">aa-verify={{ aaVerify }}</div>
          </div>
        </div>
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
      </template>
      <template v-else v-slot:content>
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Check DNS records
        </h2>
        <p class="mt-4 mb-2 text-grey-700">
          Please set the following DNS records for your custom domain. If you have more than one MX
          record then the MX record below should have the lowest priority (e.g. 10).
        </p>
        <div class="table w-full">
          <div class="table-row">
            <div class="table-cell py-2 font-semibold">Type</div>
            <div class="table-cell py-2 px-4 font-semibold">Host</div>
            <div class="table-cell py-2 font-semibold">Value/Points to</div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">MX</div>
            <div class="table-cell py-2 px-4">@</div>
            <div class="table-cell py-2 break-words">{{ hostname }}</div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">TXT</div>
            <div class="table-cell py-2 px-4">@</div>
            <div class="table-cell py-2 break-words">v=spf1 mx -all</div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">CNAME</div>
            <div class="table-cell py-2 px-4">default._domainkey</div>
            <div class="table-cell py-2 break-words">default._domainkey.{{ domainName }}.</div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">TXT</div>
            <div class="table-cell py-2 px-4">_dmarc</div>
            <div class="table-cell py-2 break-words">v=DMARC1; p=quarantine; adkim=s</div>
          </div>
        </div>
        <div class="mt-6">
          <button
            @click="checkRecords(domainToCheck)"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="checkRecordsLoading ? 'cursor-not-allowed' : ''"
            :disabled="checkRecordsLoading"
          >
            Check Records
            <loader v-if="checkRecordsLoading" />
          </button>
          <button
            @click="closeCheckRecordsModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="domainDefaultRecipientModalOpen" @close="closeDomainDefaultRecipientModal">
      <template v-slot:title> Update Default Recipient </template>
      <template v-slot:content>
        <p class="my-4 text-grey-700">
          Select the default recipient for this domain. This overrides the default recipient in your
          account settings. Leave it empty if you would like to use the default recipient in your
          account settings.
        </p>
        <Multiselect
          v-model="defaultRecipientId"
          :options="recipientOptions"
          mode="single"
          value-prop="id"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="false"
          :allow-empty="true"
          placeholder="Select recipient"
          label="email"
          track-by="email"
        >
        </Multiselect>
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
      </template>
    </Modal>

    <Modal :open="deleteDomainModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete domain </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to delete this domain? This will also delete all aliases associated
          with this domain. You will no longer be able to receive any emails at this domain.
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
      </template>
    </Modal>
  </div>
</template>

<script>
import Modal from './../components/Modal.vue'
import Toggle from './../components/Toggle.vue'
import { roundArrow } from 'tippy.js'
import 'tippy.js/dist/svg-arrow.css'
import 'tippy.js/dist/tippy.css'
import tippy from 'tippy.js'
import Multiselect from '@vueform/multiselect'

export default {
  props: {
    initialDomains: {
      type: Array,
      required: true,
    },
    domainName: {
      type: String,
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
    aaVerify: {
      type: String,
      required: true,
    },
  },
  components: {
    Modal,
    Toggle,
    Multiselect,
  },
  data() {
    return {
      newDomain: '',
      search: '',
      addDomainLoading: false,
      addDomainModalOpen: false,
      domainIdToDelete: null,
      domainIdToEdit: null,
      domainDescriptionToEdit: '',
      domainToCheck: null,
      deleteDomainLoading: false,
      deleteDomainModalOpen: false,
      checkRecordsLoading: false,
      domainDefaultRecipientModalOpen: false,
      defaultRecipientDomainToEdit: {},
      defaultRecipientId: null,
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
          label: 'Catch-All',
          field: 'catch_all',
          type: 'boolean',
          globalSearchDisabled: true,
        },
        {
          label: 'Verified Records',
          field: 'domain_sending_verified_at',
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
      tippyInstance: null,
    }
  },
  watch: {
    domainIdToEdit: _.debounce(function () {
      this.addTooltips()
    }, 50),
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
          // In order to get new TXT verification value
          location.reload()
        })
        .catch(error => {
          this.addDomainLoading = false
          if (error.response.status === 422) {
            this.error(error.response.data.errors.domain[0])
          } else if (error.response.status === 429) {
            this.error('You are making too many requests')
          } else if (error.response.status === 404) {
            this.warn(
              'Verification TXT record not found, this could be due to DNS caching, please try again shortly.'
            )
          } else {
            this.error()
          }
        })
    },
    checkRecords(domain) {
      this.checkRecordsLoading = true

      axios
        .get(`/domains/${domain.id}/check-sending`)
        .then(({ data }) => {
          this.checkRecordsLoading = false

          if (data.success === true) {
            this.closeCheckRecordsModal()
            this.success(data.message)
            domain.domain_sending_verified_at = data.data.domain_sending_verified_at
            domain.domain_mx_validated_at = data.data.domain_mx_validated_at
          } else {
            this.warn(data.message)
          }
        })
        .catch(error => {
          this.checkRecordsLoading = false
          if (error.response.status === 429) {
            this.error('Please wait a little while before checking the records again')
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
      this.defaultRecipientId = domain.default_recipient_id
    },
    closeDomainDefaultRecipientModal() {
      this.domainDefaultRecipientModalOpen = false
      this.defaultRecipientDomainToEdit = {}
      this.defaultRecipientId = null
    },
    openCheckRecordsModal(domain) {
      this.domainToCheck = domain
      this.addDomainModalOpen = true
    },
    closeCheckRecordsModal() {
      this.addDomainModalOpen = false
      _.delay(() => (this.domainToCheck = null), 300)
    },
    editDomain(domain) {
      if (this.domainDescriptionToEdit.length > 200) {
        return this.error('Description cannot be more than 200 characters')
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
          this.domainIdToEdit = null
          this.domainDescriptionToEdit = ''
          this.success('Domain description updated')
        })
        .catch(error => {
          this.domainIdToEdit = null
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
            default_recipient: this.defaultRecipientId,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          let domain = _.find(this.rows, ['id', this.defaultRecipientDomainToEdit.id])
          domain.default_recipient = _.find(this.recipientOptions, ['id', this.defaultRecipientId])
          domain.default_recipient_id = this.defaultRecipientId

          this.domainDefaultRecipientModalOpen = false
          this.editDefaultRecipientLoading = false
          this.defaultRecipientId = null
          this.success("Domain's default recipient updated")
        })
        .catch(error => {
          this.domainDefaultRecipientModalOpen = false
          this.editDefaultRecipientLoading = false
          this.defaultRecipientId = null
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
    enableCatchAll(id) {
      axios
        .post(
          `/api/v1/catch-all-domains`,
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
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    disableCatchAll(id) {
      axios
        .delete(`/api/v1/catch-all-domains/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
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
