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
    <div class="bg-white rounded shadow overflow-x-auto">
      <table v-if="initialAliases.length" class="w-full whitespace-no-wrap">
        <tr class="text-left font-semibold text-grey-500 text-sm tracking-wider">
          <th class="pl-4 pr-2 py-4">
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
          <th class="px-2 py-4">
            <div class="flex items-center">
              Alias
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('email', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('email', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('email', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('email', 'desc') }"
                />
              </div>
            </div>
          </th>
          <th class="px-2 py-4">
            <div class="flex items-center">
              Recipients
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('recipients', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('recipients', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('recipients', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('recipients', 'desc') }"
                />
              </div>
            </div>
          </th>
          <th class="px-2 py-4">
            <div class="flex items-center">
              Description
            </div>
          </th>
          <th class="px-2 py-4">
            <div class="flex items-center">
              Forwarded
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('emails_forwarded', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('emails_forwarded', 'asc'),
                  }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('emails_forwarded', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('emails_forwarded', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
          <th class="px-2 py-4 items-center">
            <div class="flex items-center">
              Blocked
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('emails_blocked', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('emails_blocked', 'asc'),
                  }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('emails_blocked', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('emails_blocked', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
          <th class="px-2 py-4 items-center">
            <div class="flex items-center">
              Replies
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('emails_replied', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('emails_replied', 'asc'),
                  }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('emails_replied', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('emails_replied', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
          <th class="px-2 py-4 items-center" colspan="2">
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
        </tr>
        <tr
          v-for="alias in queriedAliases"
          :key="alias.id"
          class="hover:bg-grey-50 focus-within:bg-grey-50 h-20"
        >
          <td class="border-grey-200 border-t">
            <div class="pl-4 pr-2 py-4 flex items-center">
              <span
                class="tooltip outline-none text-sm"
                :data-tippy-content="alias.created_at | formatDate"
                >{{ alias.created_at | timeAgo }}</span
              >
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="px-2 py-4 flex items-center">
              <span
                class="tooltip cursor-pointer outline-none"
                data-tippy-content="Click to copy"
                v-clipboard="() => getAliasEmail(alias)"
                v-clipboard:success="clipboardSuccess"
                v-clipboard:error="clipboardError"
              >
                <span class="font-semibold text-indigo-800">{{
                  alias.local_part | truncate(25)
                }}</span>
                <span class="block text-grey-400 text-sm">{{
                  getAliasEmail(alias) | truncate(40)
                }}</span>
              </span>
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="px-2 flex items-center">
              <span
                v-if="alias.recipients.length && alias.id !== recipientsAliasToEdit.id"
                class="tooltip outline-none"
                :data-tippy-content="recipientsTooltip(alias.recipients)"
                >{{ alias.recipients[0].email | truncate(25) }}
                <span
                  v-if="alias.recipients.length > 1"
                  class="block text-center text-grey-500 text-sm"
                >
                  + {{ alias.recipients.length - 1 }}</span
                >
              </span>
              <span v-else-if="alias.id === recipientsAliasToEdit.id">{{
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
                class="ml-2 block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                @click.native="openAliasRecipientsModal(alias)"
              />
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="px-2 py-4 text-sm">
              <div
                v-if="aliasIdToEdit === alias.id"
                class="w-full flex items-center justify-between"
              >
                <input
                  @keyup.enter="editAlias(alias)"
                  @keyup.esc="aliasIdToEdit = aliasDescriptionToEdit = ''"
                  v-model="aliasDescriptionToEdit"
                  type="text"
                  class="appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
                  :class="
                    aliasDescriptionToEdit.length > 100 ? 'border-red-500' : 'border-transparent'
                  "
                  placeholder="Add description"
                  tabindex="0"
                  autofocus
                />
                <icon
                  name="close"
                  class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="aliasIdToEdit = aliasDescriptionToEdit = ''"
                />
                <icon
                  name="save"
                  class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="editAlias(alias)"
                />
              </div>
              <div v-else-if="alias.description" class="flex items-center justify-around">
                <span
                  class="tooltip outline-none"
                  :data-tippy-content="alias.description"
                  v-clipboard="() => alias.description"
                  v-clipboard:success="clipboardSuccess"
                  v-clipboard:error="clipboardError"
                >
                  <icon
                    name="desc"
                    class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  />
                </span>
                <icon
                  name="edit"
                  class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="
                    ;(aliasIdToEdit = alias.id), (aliasDescriptionToEdit = alias.description)
                  "
                />
              </div>
              <div v-else class="w-full flex justify-center">
                <icon
                  name="plus"
                  class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="aliasIdToEdit = alias.id"
                />
              </div>
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="px-2 py-4 flex items-center justify-center font-semibold text-indigo-800">
              {{ alias.emails_forwarded }}
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="px-2 py-4 flex items-center justify-center font-semibold text-indigo-800">
              {{ alias.emails_blocked }}
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="px-2 py-4 flex items-center justify-center font-semibold text-indigo-800">
              {{ alias.emails_replied }}
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="px-2 py-4 flex items-center justify-center">
              <Toggle
                v-model="alias.active"
                @on="activateAlias(alias)"
                @off="deactivateAlias(alias)"
              />
            </div>
          </td>
          <td class="border-grey-200 border-t w-px">
            <div class="px-4 flex items-center justify-center outline-none" tabindex="-1">
              <icon
                name="trash"
                class="block w-6 h-6 text-grey-200 fill-current  cursor-pointer"
                @click.native="openDeleteModal(alias.id)"
              />
            </div>
          </td>
        </tr>
        <tr v-if="queriedAliases.length === 0">
          <td
            class="border-grey-200 border-t px-6 py-4 text-center h-24 text-lg text-grey-700"
            colspan="9"
          >
            No aliases found for that search!
          </td>
        </tr>
      </table>

      <div v-else class="p-8 text-center text-lg text-grey-700">
        <h1 class="mb-6 text-xl text-indigo-800 font-semibold">
          It doesn't look like you have any aliases yet!
        </h1>
        <div class="mx-auto mb-6 w-24 border-b-2 border-grey-200"></div>
        <p class="mb-4">
          To get started all you have to do is make up any new alias and give that out instead of
          your real email address.
        </p>
        <p class="mb-4">
          Let's say you're signing up to <b>example.com</b> you could enter
          <b>example@{{ domain }}</b> as your email address.
        </p>
        <p class="mb-4">
          The alias will show up here automatically as soon as it has forwarded its first email.
        </p>
        <p class="mb-4">
          If you start receiving spam to the alias you can simply deactivate it or delete it all
          together!
        </p>
        <p>
          Try it out now by sending an email to <b>first@{{ domain }}</b> and then refresh this
          page.
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
          This will generate a new unique alias in the form of<br /><br />
          86064c92-da41-443e-a2bf-5a7b0247842f@anonaddy.me<br /><br />
          Useful if you do not wish to include your username in the email as a potential link
          between aliases.<br /><br />
          Other aliases e.g. alias@{{ domain }} or .me are created automatically when they receive
          their first email.
        </p>
        <div class="mt-6">
          <button
            @click="generateNewAlias"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="generateAliasLoading ? 'cursor-not-allowed' : ''"
            :disabled="generateAliasLoading"
          >
            Generate Alias
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
    bandwidthMb: {
      type: Number,
      required: true,
    },
    month: {
      type: String,
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
      aliases: this.initialAliases,
      search: '',
      aliasIdToEdit: '',
      aliasDescriptionToEdit: '',
      aliasIdToDelete: '',
      deleteAliasLoading: false,
      deleteAliasModalOpen: false,
      currentSort: 'created_at',
      currentSortDir: 'desc',
      editAliasRecipientsLoading: false,
      editAliasRecipientsModalOpen: false,
      generateAliasModalOpen: false,
      generateAliasLoading: false,
      recipientsAliasToEdit: {},
      aliasRecipientsToEdit: [],
    }
  },
  watch: {
    queriedAliases: _.debounce(function() {
      this.addTooltips()
    }, 50),
    aliasIdToEdit: _.debounce(function() {
      this.addTooltips()
    }, 50),
    editAliasRecipientsModalOpen: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  computed: {
    queriedAliases() {
      return _.filter(this.aliases, alias => alias.email.includes(this.search))
    },
    totalActive() {
      return _.filter(this.aliases, 'active').length
    },
    totalInactive() {
      return _.reject(this.aliases, 'active').length
    },
  },
  methods: {
    addTooltips() {
      tippy('.tooltip', {
        arrow: true,
        arrowType: 'round',
      })
    },
    recipientsTooltip(recipients) {
      return _.reduce(recipients, (list, recipient) => list + `${recipient.email}<br>`, '')
    },
    isCurrentSort(col, dir) {
      return this.currentSort === col && this.currentSortDir === dir
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
          this.aliases = _.filter(this.aliases, aliases => aliases.id !== id)
          this.deleteAliasModalOpen = false
          this.deleteAliasLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteAliasModalOpen = false
          this.deleteAliasLoading = false
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

      this.aliases = _.orderBy(this.aliases, [this.currentSort], [this.currentSortDir])
    },
    reSort() {
      this.aliases = _.orderBy(this.aliases, [this.currentSort], [this.currentSortDir])
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
          let alias = _.find(this.aliases, ['id', this.recipientsAliasToEdit.id])
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
        .post('/aliases', JSON.stringify({}), {
          headers: { 'Content-Type': 'application/json' },
        })
        .then(({ data }) => {
          this.generateAliasLoading = false
          this.aliases.push(data.data)
          this.reSort()
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
    activateAlias(alias) {
      axios
        .post(
          `/active-aliases`,
          JSON.stringify({
            id: alias.id,
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
    deactivateAlias(alias) {
      axios
        .delete(`/active-aliases/${alias.id}`)
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
