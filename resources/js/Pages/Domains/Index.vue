<template>
  <div>
    <Head title="Domains" />
    <h1 id="primary-heading" class="sr-only">Domains</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900">Domains</h1>
        <p class="mt-2 text-sm text-grey-700">
          A list of all the domains {{ search ? 'found for your search' : 'in your account' }}
          <button @click="moreInfoOpen = !moreInfoOpen">
            <InformationCircleIcon
              class="h-6 w-6 inline-block cursor-pointer text-grey-500"
              title="Click for more information"
            />
          </button>
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          type="button"
          @click="openAddDomainModal"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 font-bold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:w-auto"
        >
          Add Domain
        </button>
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
          class="tooltip outline-none text-sm text-grey-500"
          :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
          >{{ $filters.timeAgo(props.row.created_at) }}
        </span>
        <span v-else-if="props.column.field == 'domain'">
          <button
            class="tooltip cursor-pointer outline-none font-medium text-grey-700"
            data-tippy-content="Click to copy"
            @click="clipboard(rows[props.row.originalIndex].domain)"
          >
            {{ $filters.truncate(props.row.domain, 30) }}
          </button>
        </span>
        <span v-else-if="props.column.field == 'description'">
          <div v-if="domainIdToEdit === props.row.id" class="flex items-center">
            <input
              @keyup.enter="editDomain(rows[props.row.originalIndex])"
              @keyup.esc="domainIdToEdit = domainDescriptionToEdit = ''"
              v-model="domainDescriptionToEdit"
              type="text"
              class="grow appearance-none bg-grey-50 border text-grey-700 focus:outline-none rounded px-2 py-1"
              :class="
                domainDescriptionToEdit.length > 200 ? 'border-red-500' : 'border-transparent'
              "
              placeholder="Add description"
              tabindex="0"
              autofocus
            />
            <button @click="domainIdToEdit = domainDescriptionToEdit = ''">
              <icon name="close" class="inline-block w-6 h-6 text-red-300 fill-current" />
            </button>
            <button @click="editDomain(rows[props.row.originalIndex])">
              <icon name="save" class="inline-block w-6 h-6 text-cyan-500 fill-current" />
            </button>
          </div>
          <div v-else-if="props.row.description" class="flex items-centers">
            <span class="outline-none text-grey-500 mr-2">{{
              $filters.truncate(props.row.description, 60)
            }}</span>
            <button
              @click="
                ;(domainIdToEdit = props.row.id), (domainDescriptionToEdit = props.row.description)
              "
            >
              <icon name="edit" class="inline-block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
          <div v-else class="flex justify-center">
            <button @click=";(domainIdToEdit = props.row.id), (domainDescriptionToEdit = '')">
              <icon name="plus" class="block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
        </span>
        <span v-else-if="props.column.field === 'default_recipient'">
          <div v-if="props.row.default_recipient">
            <span
              class="tooltip cursor-pointer font-medium text-grey-500 mr-2"
              data-tippy-content="Click to copy"
              @click="clipboard(rows[props.row.originalIndex].default_recipient.email)"
            >
              {{ $filters.truncate(props.row.default_recipient.email, 30) }}
            </span>
            <button @click="openDomainDefaultRecipientModal(props.row)">
              <icon name="edit" class="inline-block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
          <div class="flex justify-center" v-else>
            <button @click="openDomainDefaultRecipientModal(props.row)">
              <icon name="plus" class="block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
        </span>
        <span v-else-if="props.column.field === 'aliases_count'">
          <span v-if="props.row.aliases_count" class="text-grey-500">
            <Link
              :href="route('aliases.index', { domain: props.row.id })"
              as="button"
              type="button"
              data-tippy-content="Click to view the aliases using this domain"
              class="text-indigo-600 hover:text-indigo-900 font-medium tooltip"
              >{{ props.row.aliases_count }}</Link
            >
          </span>
          <span v-else class="text-grey-500"> {{ props.row.aliases_count }}</span>
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
              class="h-5 w-5 inline-block tooltip focus:outline-none"
              data-tippy-content="Domain fully verified"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
            >
              <g fill="none" fill-rule="evenodd">
                <circle class="text-green-100 fill-current" cx="10" cy="10" r="10"></circle>
                <polyline
                  class="text-green-800 stroke-current"
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
              class="h-5 w-5 inline-block tooltip focus:outline-none"
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
              class="h-5 w-5 inline-block tooltip focus:outline-none"
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
              class="focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 text-sm ml-2 text-grey-500 rounded-sm"
            >
              Recheck
            </button>
          </div>
          <button
            v-else
            @click="openCheckRecordsModal(rows[props.row.originalIndex])"
            class="focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 text-sm text-grey-500 rounded-sm"
          >
            Check Records
          </button>
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <Link
            :href="route('domains.edit', props.row.id)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 font-medium"
            >Edit<span class="sr-only">, {{ props.row.domain }}</span></Link
          >
          <button
            @click="openDeleteModal(props.row.id)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 font-medium ml-4"
          >
            Delete<span class="sr-only">, {{ props.row.domain }}</span>
          </button>
        </span>
      </template>
    </vue-good-table>

    <div v-else-if="search" class="text-center">
      <GlobeAltIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">No Domains found for that search</h3>
      <p class="mt-1 text-md text-grey-500">Try entering a different search term.</p>
      <div class="mt-6">
        <Link
          :href="route('domains.index')"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          View All Domains
        </Link>
      </div>
    </div>

    <div v-else class="text-center">
      <GlobeAltIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">No Domains</h3>
      <p class="mt-1 text-md text-grey-500">Get started by creating a new domain.</p>
      <div class="mt-6">
        <button
          @click="openAddDomainModal"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
          Add a Domain
        </button>
      </div>
    </div>

    <Modal :open="addDomainModalOpen" @close="closeCheckRecordsModal" max-width="md:max-w-2xl">
      <template v-if="!domainToCheck" v-slot:title> Add new domain </template>
      <template v-else v-slot:title> Check DNS records </template>
      <template v-if="!domainToCheck" v-slot:content>
        <p class="mt-4 mb-2 text-grey-700">
          To verify ownership of the domain, please add the following TXT record and then click Add
          Domain below. Once you've added the domain you can safely remove this TXT record.
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
            <div class="table-cell py-2">
              <button
                @click="clipboard(`aa-verify=${aaVerify}`)"
                class="break-all focus-visible:outline-indigo-600"
                title="Copy"
              >
                aa-verify={{ aaVerify }}
              </button>
            </div>
          </div>
        </div>
        <div class="mt-6">
          <p v-show="errors.newDomain" class="mb-3 text-red-500 text-sm">
            {{ errors.newDomain }}
          </p>
          <input
            v-model="newDomain"
            type="text"
            class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 mb-6"
            :class="errors.newDomain ? 'ring-red-500' : ''"
            placeholder="example.com"
            autofocus
          />
          <button
            @click="validateNewDomain"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            :class="addDomainLoading ? 'cursor-not-allowed' : ''"
            :disabled="addDomainLoading"
          >
            Add Domain
            <loader v-if="addDomainLoading" />
          </button>
          <button
            @click="addDomainModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
        <div class="mt-2 text-sm">
          For <b>subdomains</b> you will need to change the host value, please read
          <a
            href="https://addy.io/help/adding-a-custom-domain/"
            class="text-indigo-700 font-bold"
            target="_blank"
            rel="nofollow noreferrer noopener"
            >this article</a
          >
        </div>
      </template>
      <template v-else v-slot:content>
        <p class="mt-4 mb-2 text-grey-700">
          Please set the following DNS records for your custom domain. <b>Note</b>: if you are
          already using your custom domain for emails elsewhere e.g. with ProtonMail, NameCheap etc.
          please
          <a
            href="https://addy.io/faq/#can-i-add-a-domain-if-im-already-using-it-for-email-somewhere-else"
            class="text-indigo-700 font-bold"
            target="_blank"
            rel="nofollow noreferrer noopener"
            >read this</a
          >.
        </p>
        <div class="table w-full">
          <div class="table-row">
            <div class="table-cell py-2 font-semibold">Type</div>
            <div class="table-cell py-2 px-4 font-semibold">Host</div>
            <div class="table-cell py-2 font-semibold">Value/Points to</div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">MX 10</div>
            <div class="table-cell py-2 px-4">
              <button title="Copy" @click="clipboard('@')" class="focus-visible:outline-indigo-600">
                @
              </button>
            </div>
            <div class="table-cell py-2 break-words">
              <button
                title="Copy"
                @click="clipboard(hostname)"
                class="focus-visible:outline-indigo-600"
              >
                {{ hostname }}.
              </button>
            </div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">TXT</div>
            <div class="table-cell py-2 px-4">
              <button title="Copy" @click="clipboard('@')" class="focus-visible:outline-indigo-600">
                @
              </button>
            </div>
            <div class="table-cell py-2 break-words">
              <button
                title="Copy"
                @click="clipboard('v=spf1 mx -all')"
                class="focus-visible:outline-indigo-600"
              >
                v=spf1 mx -all
              </button>
            </div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">CNAME</div>
            <div class="table-cell py-2 px-4">
              <button
                title="Copy"
                @click="clipboard(`${dkimSelector}._domainkey`)"
                class="focus-visible:outline-indigo-600"
              >
                {{ dkimSelector }}._domainkey
              </button>
            </div>
            <div class="table-cell py-2 break-words">
              <button
                title="Copy"
                @click="clipboard(`${dkimSelector}._domainkey.${domainName}.`)"
                class="focus-visible:outline-indigo-600"
              >
                {{ dkimSelector }}._domainkey.{{ domainName }}.
              </button>
            </div>
          </div>
          <div class="table-row">
            <div class="table-cell py-2">TXT</div>
            <div class="table-cell py-2 px-4">
              <button
                title="Copy"
                @click="clipboard('_dmarc')"
                class="focus-visible:outline-indigo-600"
              >
                _dmarc
              </button>
            </div>
            <div class="table-cell py-2 break-words">
              <button
                title="Copy"
                @click="clipboard('v=DMARC1; p=quarantine; adkim=s')"
                class="focus-visible:outline-indigo-600"
              >
                v=DMARC1; p=quarantine; adkim=s
              </button>
            </div>
          </div>
        </div>
        <div class="mt-6">
          <button
            @click="checkRecords(domainToCheck)"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            :class="checkRecordsLoading ? 'cursor-not-allowed' : ''"
            :disabled="checkRecordsLoading"
          >
            Check Records
            <loader v-if="checkRecordsLoading" />
          </button>
          <button
            @click="closeCheckRecordsModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
        <div class="mt-2 text-sm">
          For more information or if you are adding a <b>subdomain</b> please read
          <a
            href="https://addy.io/help/adding-a-custom-domain/"
            class="text-indigo-700 font-bold"
            target="_blank"
            rel="nofollow noreferrer noopener"
            >this article</a
          >
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
        <multiselect
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
        </multiselect>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="editDefaultRecipient()"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="editDefaultRecipientLoading"
          >
            Update Default Recipient
            <loader v-if="editDefaultRecipientLoading" />
          </button>
          <button
            @click="closeDomainDefaultRecipientModal()"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
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
          Are you sure you want to delete this domain? This will also
          <b>remove all aliases associated with this domain</b>. You will no longer be able to
          receive any emails at this domain.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteDomain(domainIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteDomainLoading"
          >
            Delete domain
            <loader v-if="deleteDomainLoading" />
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
          Adding a custom domain such as <b>example.com</b> will allow you to create unlimited
          aliases e.g. xyz@example.com. You can also add a subdomain such as
          <b>mail.example.com</b>.
        </p>
        <p class="mt-4 text-grey-700">
          To get started all you have to do is add a TXT record to your domain to verify ownership
          and then add the domain here by clicking the button above.
        </p>
        <p class="mt-4 text-grey-700">The TXT record needs to have the following values:</p>
        <p class="mt-4 text-grey-700">
          Type: <b>TXT</b><br />
          Host: <b>@</b><br />
          Value:
          <b
            class="break-words cursor-pointer"
            title="Copy"
            @click="clipboard(`aa-verify=${aaVerify}`)"
            >aa-verify={{ aaVerify }}</b
          ><br />
        </p>
        <p class="mt-4 text-grey-700">
          Once the DNS changes propagate and you have verified ownership of the domain you will need
          to add a few more records to be able to receive emails at your own domain.
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
import { ref, toRefs } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Modal from '../../Components/Modal.vue'
import Toggle from '../../Components/Toggle.vue'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import Multiselect from '@vueform/multiselect'
import { VueGoodTable } from 'vue-good-table-next'
import { notify } from '@kyvg/vue3-notification'
import { InformationCircleIcon, GlobeAltIcon } from '@heroicons/vue/24/outline'
import { PlusIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  initialRows: {
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
  dkimSelector: {
    type: String,
    required: true,
  },
  recipientOptions: {
    type: Array,
    required: true,
  },
  initialAaVerify: {
    type: String,
    required: true,
  },
  search: {
    type: String,
  },
})

const { recipientOptions } = toRefs(props)

const rows = ref(props.initialRows)
const aaVerify = ref(props.initialAaVerify)

const newDomain = ref('')
const addDomainLoading = ref(false)
const addDomainModalOpen = ref(false)
const domainIdToDelete = ref(null)
const domainIdToEdit = ref(null)
const domainDescriptionToEdit = ref('')
const domainToCheck = ref(null)
const deleteDomainLoading = ref(false)
const deleteDomainModalOpen = ref(false)
const checkRecordsLoading = ref(false)
const domainDefaultRecipientModalOpen = ref(false)
const moreInfoOpen = ref(false)
const defaultRecipientDomainToEdit = ref({})
const defaultRecipientId = ref(null)
const editDefaultRecipientLoading = ref(false)
const tippyInstance = ref(null)
const errors = ref({})

const columns = [
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
]

const addNewDomain = () => {
  addDomainLoading.value = true

  axios
    .post(
      '/api/v1/domains',
      JSON.stringify({
        domain: newDomain.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(({ data }) => {
      router.visit(route('domains.index'), {
        only: ['initialRows', 'search', 'initialAaVerify'],
        onSuccess: page => {
          successMessage('Domain added successfully')
        },
      })
    })
    .catch(error => {
      addDomainLoading.value = false
      if (error.response.status === 403) {
        errorMessage(error.response.data)
      } else if (error.response.status === 422) {
        errorMessage(error.response.data.errors.domain[0])
      } else if (error.response.status === 429) {
        errorMessage('You are making too many requests')
      } else if (error.response.status === 404) {
        warnMessage(
          'Verification TXT record not found, this could be due to DNS caching, please try again shortly.',
        )
      } else {
        errorMessage()
      }
    })
}

const editDomain = domain => {
  if (domainDescriptionToEdit.value.length > 200) {
    return errorMessage('Description cannot be more than 200 characters')
  }

  axios
    .patch(
      `/api/v1/domains/${domain.id}`,
      JSON.stringify({
        description: domainDescriptionToEdit.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      domain.description = domainDescriptionToEdit.value
      domainIdToEdit.value = null
      domainDescriptionToEdit.value = ''
      successMessage('Domain description updated')
    })
    .catch(error => {
      domainIdToEdit.value = null
      domainDescriptionToEdit.value = ''
      errorMessage()
    })
}
const editDefaultRecipient = () => {
  editDefaultRecipientLoading.value = true

  axios
    .patch(
      `/api/v1/domains/${defaultRecipientDomainToEdit.value.id}/default-recipient`,
      JSON.stringify({
        default_recipient: defaultRecipientId.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      let domain = _.find(rows.value, ['id', defaultRecipientDomainToEdit.value.id])
      domain.default_recipient = _.find(recipientOptions.value, ['id', defaultRecipientId.value])
      domain.default_recipient_id = defaultRecipientId.value

      domainDefaultRecipientModalOpen.value = false
      editDefaultRecipientLoading.value = false
      defaultRecipientId.value = null
      debounceToolips()
      successMessage("Domain's default recipient updated")
    })
    .catch(error => {
      domainDefaultRecipientModalOpen.value = false
      editDefaultRecipientLoading.value = false
      defaultRecipientId.value = null
      errorMessage()
    })
}

const checkRecords = domain => {
  checkRecordsLoading.value = true

  axios
    .get(`/domains/${domain.id}/check-sending`)
    .then(({ data }) => {
      checkRecordsLoading.value = false

      if (data.success === true) {
        closeCheckRecordsModal()
        successMessage(data.message)
        domain.domain_sending_verified_at = data.data.domain_sending_verified_at
        domain.domain_mx_validated_at = data.data.domain_mx_validated_at
      } else {
        warnMessage(data.message)
      }
    })
    .catch(error => {
      checkRecordsLoading.value = false
      if (error.response.status === 429) {
        errorMessage('Please wait a little while before checking the records again')
      } else {
        errorMessage()
      }
    })
}

const activateDomain = id => {
  axios
    .post(
      `/api/v1/active-domains`,
      JSON.stringify({
        id: id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      //
    })
    .catch(error => {
      errorMessage()
    })
}

const deactivateDomain = id => {
  axios
    .delete(`/api/v1/active-domains/${id}`)
    .then(response => {
      //
    })
    .catch(error => {
      errorMessage()
    })
}

const enableCatchAll = id => {
  axios
    .post(
      `/api/v1/catch-all-domains`,
      JSON.stringify({
        id: id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      //
    })
    .catch(error => {
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const disableCatchAll = id => {
  axios
    .delete(`/api/v1/catch-all-domains/${id}`)
    .then(response => {
      //
    })
    .catch(error => {
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const deleteDomain = id => {
  deleteDomainLoading.value = true

  axios
    .delete(`/api/v1/domains/${id}`)
    .then(({ data }) => {
      router.reload({
        only: ['initialRows', 'search', 'initialAaVerify'],
        onSuccess: page => {
          deleteDomainModalOpen.value = false
          deleteDomainLoading.value = false
          rows.value = props.initialRows
          aaVerify.value = props.initialAaVerify
          successMessage('Domain deleted successfully')
        },
      })
    })
    .catch(error => {
      errorMessage()
      deleteDomainLoading.value = false
      deleteDomainModalOpen.value = false
    })
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

const openAddDomainModal = () => {
  errors.value = {}
  newDomain.value = ''
  addDomainModalOpen.value = true
}

const openDeleteModal = id => {
  deleteDomainModalOpen.value = true
  domainIdToDelete.value = id
}

const closeDeleteModal = () => {
  deleteDomainModalOpen.value = false
  domainIdToDelete.value = null
}

const openDomainDefaultRecipientModal = domain => {
  domainDefaultRecipientModalOpen.value = true
  defaultRecipientDomainToEdit.value = domain
  defaultRecipientId.value = domain.default_recipient_id
}

const closeDomainDefaultRecipientModal = () => {
  domainDefaultRecipientModalOpen.value = false
  defaultRecipientDomainToEdit.value = {}
  defaultRecipientId.value = null
}

const openCheckRecordsModal = domain => {
  domainToCheck.value = domain
  addDomainModalOpen.value = true
}

const closeCheckRecordsModal = () => {
  addDomainModalOpen.value = false
  _.delay(() => (domainToCheck.value = null), 300)
}

const validDomain = domain => {
  let re = /(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{0,62}[a-zA-Z0-9]\.)+[a-zA-Z0-9-]{2,63}$)/
  return re.test(domain)
}

const validateNewDomain = e => {
  errors.value = {}

  if (!newDomain.value) {
    errors.value.newDomain = 'Domain name required'
  } else if (newDomain.value.length > 50) {
    errors.value.newDomain = 'That domain name is too long'
  } else if (!validDomain(newDomain.value)) {
    errors.value.newDomain = 'Please enter a valid domain name'
  }

  if (!errors.value.newDomain) {
    addNewDomain()
  }

  e.preventDefault()
}

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

const warnMessage = (text = '') => {
  notify({
    title: 'Information',
    text: text,
    type: 'warn',
  })
}
</script>
