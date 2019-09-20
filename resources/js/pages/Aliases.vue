<template>
  <div>
    <div class="flex flex-wrap flex-row items-center justify-between mb-8 md:px-2 lg:px-6">
      <div
        class="w-full md:w-1/2 lg:w-1/3 xl:w-1/6 md:-mx-2 lg:-mx-6 rounded overflow-hidden shadow-md bg-white mb-4 lg:mb-4 xl:mb-0"
      >
        <div class="p-4 flex items-center justify-between relative">
          <icon
            name="check-circle"
            class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
          />
          <div class="font-bold text-xl md:text-3xl text-indigo-800">
            {{ totalActive }}
            <p class="text-grey-200 text-sm tracking-wide uppercase">
              Active
            </p>
          </div>
        </div>
      </div>
      <div
        class="w-full md:w-1/2 lg:w-1/3 xl:w-1/6 md:-mx-2 lg:-mx-6 rounded overflow-hidden shadow-md bg-white mb-4 lg:mb-4 xl:mb-0"
      >
        <div class="p-4 flex items-center justify-between relative">
          <icon
            name="cross-circle"
            class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
          />
          <div class="font-bold text-xl md:text-3xl text-indigo-800">
            {{ totalInactive }}
            <p class="text-grey-200 text-sm tracking-wide uppercase">
              Inactive
            </p>
          </div>
        </div>
      </div>
      <div
        class="w-full md:w-1/2 lg:w-1/3 xl:w-1/6 md:-mx-2 lg:-mx-6 rounded overflow-hidden shadow-md bg-white mb-4 lg:mb-4 xl:mb-0"
      >
        <div class="p-4 flex items-center justify-between relative">
          <icon
            name="send"
            class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
          />
          <div class="font-bold text-xl md:text-3xl text-indigo-800">
            {{ totalForwarded }}
            <p class="text-grey-200 text-sm tracking-wide uppercase">
              Emails Forwarded
            </p>
          </div>
        </div>
      </div>
      <div
        class="w-full md:w-1/2 lg:w-1/3 xl:w-1/6 md:-mx-2 lg:-mx-6 rounded overflow-hidden shadow-md bg-white mb-4 lg:mb-0"
      >
        <div class="p-4 flex items-center justify-between relative">
          <icon
            name="blocked"
            class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
          />
          <div class="font-bold text-xl md:text-3xl text-indigo-800">
            {{ totalBlocked }}
            <p class="text-grey-200 text-sm tracking-wide uppercase">
              Emails Blocked
            </p>
          </div>
        </div>
      </div>
      <div
        class="w-full md:w-1/2 lg:w-1/3 xl:w-1/6 md:-mx-2 lg:-mx-6 rounded overflow-hidden shadow-md bg-white mb-4 md:mb-0"
      >
        <div class="p-4 flex items-center justify-between relative">
          <icon
            name="corner-up-left"
            class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
          />
          <div class="font-bold text-xl md:text-3xl text-indigo-800">
            {{ totalReplies }}
            <p class="text-grey-200 text-sm tracking-wide uppercase">
              Email Replies
            </p>
          </div>
        </div>
      </div>
      <div
        class="w-full md:w-1/2 lg:w-1/3 xl:w-1/6 md:-mx-2 lg:-mx-6 rounded overflow-hidden shadow-md bg-white"
      >
        <div class="p-4 flex items-center justify-between relative">
          <icon
            name="inbox"
            class="inline-block w-16 h-16 text-indigo-50 stroke-current absolute top-0 right-0"
          />
          <div class="font-bold text-xl md:text-3xl text-indigo-800">
            {{ bandwidthMb }}<span class="text-sm tracking-wide uppercase">MB</span>
            <p class="text-grey-200 text-sm tracking-wide uppercase">Bandwidth ({{ month }})</p>
          </div>
        </div>
      </div>
    </div>
    <div class="mb-6 flex flex-col md:flex-row justify-between md:items-center">
      <div class="relative">
        <input
          v-model="search"
          @keyup.esc="search = ''"
          tabindex="0"
          type="text"
          class="w-full md:w-64 appearance-none shadow bg-white text-grey-700 focus:outline-none rounded py-3 pl-3 pr-8"
          placeholder="Search Aliases"
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
          @click="generateAliasModalOpen = true"
          class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto"
        >
          Generate New Alias
        </button>
      </div>
    </div>

    <vue-good-table
      v-if="initialAliases.length"
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
      :pagination-options="{
        enabled: true,
        mode: 'pages',
        perPage: 25,
        perPageDropdown: [25, 50, 100],
        rowsPerPageLabel: 'Aliases per page',
      }"
      styleClass="vgt-table"
    >
      <div slot="emptystate" class="flex items-center justify-center h-24 text-lg text-grey-700">
        No aliases found for that search!
      </div>
      <template slot="table-row" slot-scope="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none text-sm"
          :data-tippy-content="props.row.created_at | formatDate"
          >{{ props.row.created_at | timeAgo }}
        </span>
        <span v-else-if="props.column.field == 'email'" class="block">
          <span
            class="text-grey-400 tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            v-clipboard="() => getAliasEmail(props.row)"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            ><span class="font-semibold text-indigo-800">{{
              getAliasLocalPart(props.row) | truncate(60)
            }}</span
            ><span v-if="getAliasLocalPart(props.row).length <= 60">{{
              ('@' + props.row.domain) | truncate(60 - getAliasLocalPart(props.row).length)
            }}</span>
          </span>
          <div v-if="aliasIdToEdit === props.row.id" class="flex items-center">
            <input
              @keyup.enter="editAlias(rows[props.row.originalIndex])"
              @keyup.esc="aliasIdToEdit = aliasDescriptionToEdit = ''"
              v-model="aliasDescriptionToEdit"
              type="text"
              class="flex-grow text-sm appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
              :class="aliasDescriptionToEdit.length > 100 ? 'border-red-500' : 'border-transparent'"
              placeholder="Add description"
              tabindex="0"
              autofocus
            />
            <icon
              name="close"
              class="inline-block w-6 h-6 text-red-300 fill-current cursor-pointer"
              @click.native="aliasIdToEdit = aliasDescriptionToEdit = ''"
            />
            <icon
              name="save"
              class="inline-block w-6 h-6 text-cyan-500 fill-current cursor-pointer"
              @click.native="editAlias(rows[props.row.originalIndex])"
            />
          </div>
          <div v-else-if="props.row.description" class="flex items-center">
            <span class="inline-block text-grey-400 text-sm py-1 border border-transparent">
              {{ props.row.description | truncate(60) }}
            </span>
            <icon
              name="edit"
              class="inline-block w-6 h-6 ml-2 text-grey-200 fill-current cursor-pointer"
              @click.native="
                ;(aliasIdToEdit = props.row.id), (aliasDescriptionToEdit = props.row.description)
              "
            />
          </div>
          <div v-else>
            <span
              class="inline-block text-grey-200 text-sm cursor-pointer py-1 border border-transparent"
              @click=";(aliasIdToEdit = props.row.id), (aliasDescriptionToEdit = '')"
              >Add description</span
            >
          </div>
        </span>
        <span
          v-else-if="props.column.field == 'recipients'"
          class="flex items-center justify-center"
        >
          <span
            v-if="props.row.recipients.length && props.row.id !== recipientsAliasToEdit.id"
            class="inline-block tooltip outline-none font-semibold text-indigo-800"
            :data-tippy-content="recipientsTooltip(props.row.recipients)"
          >
            {{ props.row.recipients.length }}
          </span>
          <span v-else-if="props.row.id === recipientsAliasToEdit.id">{{
            aliasRecipientsToEdit.length ? aliasRecipientsToEdit.length : '1'
          }}</span>
          <span
            v-else
            class="py-1 px-2 text-sm bg-yellow-200 text-yellow-900 rounded-full tooltip outline-none"
            :data-tippy-content="defaultRecipient.email"
            >default</span
          >
          <icon
            name="edit"
            class="ml-2 inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
            @click.native="openAliasRecipientsModal(props.row)"
          />
        </span>
        <span
          v-else-if="props.column.field == 'emails_forwarded'"
          class="font-semibold text-indigo-800"
        >
          {{ props.row.emails_forwarded }}
        </span>
        <span
          v-else-if="props.column.field == 'emails_blocked'"
          class="font-semibold text-indigo-800"
        >
          {{ props.row.emails_blocked }}
        </span>
        <span
          v-else-if="props.column.field == 'emails_replied'"
          class="font-semibold text-indigo-800"
        >
          {{ props.row.emails_replied }}
        </span>
        <span v-else-if="props.column.field === 'active'" class="flex items-center">
          <Toggle
            v-model="rows[props.row.originalIndex].active"
            @on="activateAlias(props.row.id)"
            @off="deactivateAlias(props.row.id)"
          />
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
        <h1 class="mb-6 text-2xl text-indigo-800 font-semibold">
          It doesn't look like you have any aliases yet!
        </h1>
        <div class="mx-auto mb-6 w-24 border-b-2 border-grey-200"></div>
        <p class="mb-4">
          There are two ways to create new aliases.
        </p>
        <h3 class="mb-4 text-xl text-indigo-800 font-semibold">
          Option 1: Create aliases on the fly
        </h3>
        <p class="mb-4">
          To create aliases on the fly all you have to do is make up any new alias and give that out
          instead of your real email address.
        </p>
        <p class="mb-4">
          Let's say you're signing up to <b>example.com</b> you could enter
          <b>example@{{ subdomain }}</b> as your email address.
        </p>
        <p class="mb-4">
          The alias will show up here automatically as soon as it has forwarded its first email.
        </p>
        <p class="mb-4">
          If you start receiving spam to the alias you can simply deactivate it or delete it all
          together!
        </p>
        <p class="mb-4">
          Try it out now by sending an email to <b>first@{{ subdomain }}</b> and then refresh this
          page.
        </p>
        <h3 class="mb-4 text-xl text-indigo-800 font-semibold">
          Option 2: Generate a unique random alias
        </h3>
        <p class="mb-4">
          You can click the button above to generate a random UUID alias that will look something
          like this:
        </p>
        <p class="mb-4">
          <b>86064c92-da41-443e-a2bf-5a7b0247842f@{{ domain }}</b>
        </p>
        <p>
          Useful if you do not wish to include your username in the email as a potential link
          between aliases.
        </p>
      </div>
    </div>

    <Modal :open="generateAliasModalOpen" @close="generateAliasModalOpen = false">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Generate new UUID alias
        </h2>
        <p class="mt-4 text-grey-700">
          This will generate a new unique alias in the form of
          <span class="text-sm block mt-2 font-semibold"
            >86064c92-da41-443e-a2bf-5a7b0247842f@{{ domain }}</span
          >
        </p>
        <p class="mt-2 text-grey-700">
          Other aliases e.g. alias@{{ subdomain }} are created automatically when they receive their
          first email.
        </p>
        <label for="banner_location" class="block text-grey-700 text-sm my-2">
          Alias Domain:
        </label>
        <div class="block relative w-full">
          <select
            v-model="generateAliasDomain"
            class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:shadow-outline"
            required
          >
            <option v-for="domainOption in allDomains" :key="domainOption" :value="domainOption">{{
              domainOption
            }}</option>
          </select>
          <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
          >
            <svg
              class="fill-current h-4 w-4"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
            >
              <path
                d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
              />
            </svg>
          </div>
        </div>

        <div class="mt-6">
          <button
            @click="generateNewAlias"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="generateAliasLoading ? 'cursor-not-allowed' : ''"
            :disabled="generateAliasLoading"
          >
            Generate Alias
            <loader v-if="generateAliasLoading" />
          </button>
          <button
            @click="generateAliasModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="editAliasRecipientsModalOpen" @close="closeAliasRecipientsModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Update Alias Recipients
        </h2>
        <p class="my-4 text-grey-700">
          Select the recipients for this alias. You can choose multiple recipients. Leave it empty
          if you would like to use the default recipient.
        </p>
        <multiselect
          v-model="aliasRecipientsToEdit"
          :options="recipientOptions"
          :multiple="true"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="true"
          :max="10"
          placeholder="Select recipients"
          label="email"
          track-by="email"
          :preselect-first="false"
          :show-labels="false"
        >
        </multiselect>
        <div class="mt-6">
          <button
            type="button"
            @click="editAliasRecipients()"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none"
            :class="editAliasRecipientsLoading ? 'cursor-not-allowed' : ''"
            :disabled="editAliasRecipientsLoading"
          >
            Update Recipients
            <loader v-if="editAliasRecipientsLoading" />
          </button>
          <button
            @click="closeAliasRecipientsModal()"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="deleteAliasModalOpen" @close="closeDeleteModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Delete alias
        </h2>
        <p class="mt-4 text-grey-700">
          Are you sure you want to delete this alias? This action cannot be undone.
        </p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteAlias(aliasIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteAliasLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteAliasLoading"
          >
            Delete alias
            <loader v-if="deleteAliasLoading" />
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
    defaultRecipient: {
      type: Object,
      required: true,
    },
    initialAliases: {
      type: Array,
      required: true,
    },
    recipientOptions: {
      type: Array,
      required: true,
    },
    totalForwarded: {
      type: Number,
      required: true,
    },
    totalBlocked: {
      type: Number,
      required: true,
    },
    totalReplies: {
      type: Number,
      required: true,
    },
    domain: {
      type: String,
      required: true,
    },
    subdomain: {
      type: String,
      required: true,
    },
    bandwidthMb: {
      type: Number,
      required: true,
    },
    month: {
      type: String,
      required: true,
    },
    allDomains: {
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
      search: '',
      aliasIdToEdit: '',
      aliasDescriptionToEdit: '',
      aliasIdToDelete: '',
      deleteAliasLoading: false,
      deleteAliasModalOpen: false,
      editAliasRecipientsLoading: false,
      editAliasRecipientsModalOpen: false,
      generateAliasModalOpen: false,
      generateAliasLoading: false,
      generateAliasDomain: this.domain,
      recipientsAliasToEdit: {},
      aliasRecipientsToEdit: [],
      columns: [
        {
          label: 'Created',
          field: 'created_at',
          globalSearchDisabled: true,
        },
        {
          label: 'Alias',
          field: 'email',
        },
        {
          label: 'Recipients',
          field: 'recipients',
          tdClass: 'text-center',
          sortable: true,
          sortFn: this.sortRecipients,
          globalSearchDisabled: true,
        },
        {
          label: 'Description',
          field: 'description',
          sortable: false,
          hidden: true,
        },
        {
          label: 'Forwarded',
          field: 'emails_forwarded',
          type: 'number',
          tdClass: 'text-center',
          globalSearchDisabled: true,
        },
        {
          label: 'Blocked',
          field: 'emails_blocked',
          type: 'number',
          tdClass: 'text-center',
          globalSearchDisabled: true,
        },
        {
          label: 'Replies',
          field: 'emails_replied',
          type: 'number',
          tdClass: 'text-center',
          globalSearchDisabled: true,
        },
        {
          label: 'Active',
          field: 'active',
          type: 'boolean',
          globalSearchDisabled: true,
        },
        {
          label: '',
          field: 'actions',
          sortable: false,
          globalSearchDisabled: true,
        },
      ],
      rows: this.initialAliases,
    }
  },
  watch: {
    aliasIdToEdit: _.debounce(function() {
      this.addTooltips()
    }, 50),
    editAliasRecipientsModalOpen: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  computed: {
    activeUuidAliases() {
      return _.filter(this.rows, alias => alias.id === alias.local_part && alias.active)
    },
    totalActive() {
      return _.filter(this.rows, 'active').length
    },
    totalInactive() {
      return _.reject(this.rows, 'active').length
    },
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
    recipientsTooltip(recipients) {
      return _.reduce(recipients, (list, recipient) => list + `${recipient.email}<br>`, '')
    },
    openDeleteModal(id) {
      this.deleteAliasModalOpen = true
      this.aliasIdToDelete = id
    },
    closeDeleteModal() {
      this.deleteAliasModalOpen = false
      this.aliasIdToDelete = ''
    },
    deleteAlias(id) {
      this.deleteAliasLoading = true

      axios
        .delete(`/aliases/${id}`)
        .then(response => {
          this.rows = _.reject(this.rows, alias => alias.id === id)
          this.deleteAliasModalOpen = false
          this.deleteAliasLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteAliasModalOpen = false
          this.deleteAliasLoading = false
        })
    },
    openAliasRecipientsModal(alias) {
      this.editAliasRecipientsModalOpen = true
      this.recipientsAliasToEdit = alias
      this.aliasRecipientsToEdit = alias.recipients
    },
    closeAliasRecipientsModal() {
      this.editAliasRecipientsModalOpen = false
      this.recipientsAliasToEdit = {}
      this.aliasRecipientsToEdit = []
    },
    editAliasRecipients() {
      this.editAliasRecipientsLoading = true

      axios
        .post(
          '/alias-recipients',
          JSON.stringify({
            alias_id: this.recipientsAliasToEdit.id,
            recipient_ids: _.map(this.aliasRecipientsToEdit, recipient => recipient.id),
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          let alias = _.find(this.rows, ['id', this.recipientsAliasToEdit.id])
          alias.recipients = this.aliasRecipientsToEdit

          this.editAliasRecipientsModalOpen = false
          this.editAliasRecipientsLoading = false
          this.recipientsAliasToEdit = {}
          this.aliasRecipientsToEdit = []
          this.success('Alias recipients updated')
        })
        .catch(error => {
          this.editAliasRecipientsModalOpen = false
          this.editAliasRecipientsLoading = false
          this.recipientsAliasToEdit = {}
          this.aliasRecipientsToEdit = []
          this.error()
        })
    },
    generateNewAlias() {
      this.generateAliasLoading = true

      axios
        .post(
          '/aliases',
          JSON.stringify({
            domain: this.generateAliasDomain,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.generateAliasLoading = false
          this.rows.push(data.data)
          this.generateAliasModalOpen = false
          this.success('New alias generated successfully')
        })
        .catch(error => {
          this.generateAliasLoading = false
          if (error.response.status === 429) {
            this.error('You have reached your hourly limit for creating new aliases')
          } else {
            this.error()
          }
        })
    },
    editAlias(alias) {
      if (this.aliasDescriptionToEdit.length > 100) {
        return this.error('Description cannot be more than 100 characters')
      }

      axios
        .patch(
          `/aliases/${alias.id}`,
          JSON.stringify({
            description: this.aliasDescriptionToEdit,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          alias.description = this.aliasDescriptionToEdit
          this.aliasIdToEdit = ''
          this.aliasDescriptionToEdit = ''
          this.success('Alias description updated')
        })
        .catch(error => {
          this.aliasIdToEdit = ''
          this.aliasDescriptionToEdit = ''
          this.error()
        })
    },
    activateAlias(id) {
      axios
        .post(
          `/active-aliases`,
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
    deactivateAlias(id) {
      axios
        .delete(`/active-aliases/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    getAliasEmail(alias) {
      return alias.extension
        ? `${alias.local_part}+${alias.extension}@${alias.domain}`
        : alias.email
    },
    getAliasLocalPart(alias) {
      return alias.extension ? `${alias.local_part}+${alias.extension}` : alias.local_part
    },
    sortRecipients(x, y) {
      return x.length < y.length ? -1 : x.length > y.length ? 1 : 0
    },
    clipboardSuccess() {
      this.success('Copied to clipboard')
    },

    clipboardError() {
      this.error('Could not copy to clipboard')
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

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
