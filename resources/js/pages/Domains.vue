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
    <div class="bg-white rounded shadow overflow-x-auto">
      <table v-if="initialDomains.length" class="w-full whitespace-no-wrap">
        <tr class="text-left font-semibold text-grey-500 text-sm tracking-wider">
          <th class="p-4">
            <div class="flex items-center">
              Created
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('created_at', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('created_at', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('created_at', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('created_at', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
          <th class="p-4">
            <div class="flex items-center">
              Domain
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('domain', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('domain', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('domain', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('domain', 'desc') }"
                />
              </div>
            </div>
          </th>
          <th class="p-4">
            <div class="flex items-center">
              Description
            </div>
          </th>
          <th class="p-4 items-center">
            <div class="flex items-center">
              Alias Count
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('aliases', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('aliases', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('aliases', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('aliases', 'desc') }"
                />
              </div>
            </div>
          </th>
          <th class="p-4 items-center">
            <div class="flex items-center">
              Active
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('active', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('active', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('active', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('active', 'desc') }"
                />
              </div>
            </div>
          </th>
          <th class="p-4" colspan="2">
            <div class="flex items-center">
              Verified
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('domain_verified_at', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('domain_verified_at', 'asc'),
                  }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('domain_verified_at', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('domain_verified_at', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
        </tr>
        <tr
          v-for="domain in queriedDomains"
          :key="domain.id"
          class="hover:bg-grey-50 focus-within:bg-grey-50 h-20"
        >
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center">
              <span
                class="tooltip outline-none text-sm"
                :data-tippy-content="domain.created_at | formatDate"
                >{{ domain.created_at | timeAgo }}</span
              >
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center focus:text-indigo-500">
              <span
                class="tooltip cursor-pointer outline-none"
                data-tippy-content="Click to copy"
                v-clipboard="() => domain.domain"
                v-clipboard:success="clipboardSuccess"
                v-clipboard:error="clipboardError"
                >{{ domain.domain | truncate(30) }}</span
              >
            </div>
          </td>
          <td class="border-grey-200 border-t w-64">
            <div class="p-4 text-sm">
              <div
                v-if="domainIdToEdit === domain.id"
                class="w-full flex items-center justify-between"
              >
                <input
                  @keyup.enter="editDomain(domain)"
                  @keyup.esc="domainIdToEdit = domainDescriptionToEdit = ''"
                  v-model="domainDescriptionToEdit"
                  type="text"
                  class="appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
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
                  @click.native="editDomain(domain)"
                />
              </div>
              <div v-else-if="domain.description" class="flex items-center justify-between w-full">
                <span class="tooltip outline-none" :data-tippy-content="domain.description">{{
                  domain.description | truncate(25)
                }}</span>
                <icon
                  name="edit"
                  class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="
                    ;(domainIdToEdit = domain.id), (domainDescriptionToEdit = domain.description)
                  "
                />
              </div>
              <div v-else class="w-full flex justify-center">
                <icon
                  name="plus"
                  class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="domainIdToEdit = domain.id"
                />
              </div>
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center">
              {{ domain.aliases.length }}
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center">
              <Toggle
                v-model="domain.active"
                @on="activateDomain(domain)"
                @off="deactivateDomain(domain)"
              />
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center focus:text-indigo-500 text-sm">
              <span
                name="check"
                v-if="domain.domain_verified_at"
                class="py-1 px-2 bg-green-200 text-green-900 rounded-full"
              >
                verified
              </span>
              <button
                v-else
                @click="recheckRecords(domain)"
                class="focus:outline-none"
                :class="recheckRecordsLoading ? 'cursor-not-allowed' : ''"
                :disabled="recheckRecordsLoading"
              >
                Recheck domain
                <loader v-if="recheckRecordsLoading" />
              </button>
            </div>
          </td>
          <td class="border-grey-200 border-t w-px">
            <div
              class="px-4 flex items-center cursor-pointer outline-none focus:text-indigo-500"
              @click="openDeleteModal(domain.id)"
              tabindex="-1"
            >
              <icon name="trash" class="block w-6 h-6 text-grey-200 fill-current" />
            </div>
          </td>
        </tr>
        <tr v-if="queriedDomains.length === 0">
          <td
            class="border-grey-200 border-t p-4 text-center h-24 text-lg text-grey-700"
            colspan="6"
          >
            No domains found for that search!
          </td>
        </tr>
      </table>

      <div v-else class="p-8 text-center text-lg text-grey-700">
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
          Make sure you add the following MX record to your domain.<br /><br />
          Host: <b>@</b><br />
          Value: <b>{{ hostname }}</b
          ><br />
          Priority: <b>10</b><br />
          TTL: <b>3600</b><br /><br />
          Just include the domain/subdomain e.g. example.com without any http protocol.
        </p>
        <div class="mt-6">
          <p v-show="errors.newDomain" class="mb-3 text-red-500">
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
  },
  components: {
    Modal,
    Toggle,
  },
  mounted() {
    this.addTooltips()
  },
  data() {
    return {
      domains: this.initialDomains,
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
      currentSort: 'created_at',
      currentSortDir: 'desc',
      errors: {},
    }
  },
  watch: {
    queriedDomains: _.debounce(function() {
      this.addTooltips()
    }, 50),
    domainIdToEdit: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  computed: {
    queriedDomains() {
      return _.filter(this.domains, domain => domain.domain.includes(this.search))
    },
  },
  methods: {
    addTooltips() {
      tippy('.tooltip', {
        arrow: true,
        arrowType: 'round',
      })
    },
    isCurrentSort(col, dir) {
      return this.currentSort === col && this.currentSortDir === dir
    },
    validateNewDomain(e) {
      this.errors = {}

      if (!this.newDomain) {
        this.errors.newDomain = 'Domain name required'
      } else if (!this.validDomain(this.newDomain)) {
        this.errors.newDomain = 'Please enter a valid domain name without http:// or https://'
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
          '/domains',
          JSON.stringify({
            domain: this.newDomain,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.addDomainLoading = false
          this.domains.push(data.data)
          this.reSort()
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

          if (data.data.domain_verified_at === null) {
            this.warn('MX record not found, please try again later')
          } else {
            this.success('Domain verified successfully')
            domain.domain_verified_at = data.data.domain_verified_at
          }
        })
        .catch(error => {
          this.recheckRecordsLoading = false
          if (error.response.status === 429) {
            this.error('You can only recheck the records once a minute')
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
    editDomain(domain) {
      if (this.domainDescriptionToEdit.length > 100) {
        return this.error('Description cannot be more than 100 characters')
      }

      axios
        .patch(
          `/domains/${domain.id}`,
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
    activateDomain(domain) {
      axios
        .post(
          `/active-domains`,
          JSON.stringify({
            id: domain.id,
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
    deactivateDomain(domain) {
      axios
        .delete(`/active-domains/${domain.id}`)
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
        .delete(`/domains/${id}`)
        .then(response => {
          this.domains = _.filter(this.domains, domain => domain.id !== id)
          this.deleteDomainModalOpen = false
          this.deleteDomainLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteDomainLoading = false
          this.deleteDomainModalOpen = false
        })
    },
    sort(col, dir) {
      if (this.currentSort === col && this.currentSortDir === dir) {
        this.currentSort = 'created_at'
        this.currentSortDir = 'desc'
      } else {
        this.currentSort = col
        this.currentSortDir = dir
      }

      this.reSort()
    },
    reSort() {
      this.domains = _.orderBy(this.domains, [this.currentSort], [this.currentSortDir])
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
