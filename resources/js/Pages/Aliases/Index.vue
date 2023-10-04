<template>
  <div>
    <Head title="Aliases" />
    <h1 id="primary-heading" class="sr-only">Aliases</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900">Aliases</h1>
        <p class="mt-2 text-sm text-grey-700">
          A list of all the aliases
          {{
            Object.keys(route().params).length
              ? 'found for your search or filters'
              : 'in your account'
          }}
          <InformationCircleIcon
            @click="moreInfoOpen = !moreInfoOpen"
            class="h-6 w-6 inline-block cursor-pointer text-grey-500"
            title="Click for more information"
          />
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center">
        <button
          type="button"
          @click="createAliasModalOpen = true"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 font-bold shadow-sm focus:outline-none sm:w-auto"
        >
          Create Alias
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div
      v-if="rows.length || Object.keys(route().params).length"
      class="flex flex-col sm:flex-row justify-between items-center mb-4 bg-white rounded-lg shadow"
    >
      <div class="relative py-4 flex items-center space-x-1.5 px-4 text-sm sm:px-6">
        <Listbox as="div" v-model="showAliasStatus">
          <div class="relative">
            <div>
              <ListboxButton
                class="inline-flex items-center text-sm text-grey-700 hover:text-grey-900 focus:outline-none"
              >
                <span class="sr-only">Change display</span>
                <ListboxLabel class="cursor-pointer">Display</ListboxLabel>
                <p class="ml-1 font-medium">{{ showAliasStatus.label }}</p>
                <ChevronDownIcon class="h-5 w-5 text-grey-700" aria-hidden="true" />
              </ListboxButton>
            </div>

            <transition
              leave-active-class="transition ease-in duration-100"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <ListboxOptions
                class="absolute z-20 mt-2 w-48 origin-top-left overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
              >
                <ListboxOption
                  as="template"
                  v-for="option in displayOptions"
                  :key="option.value"
                  :value="option"
                  v-slot="{ active, selected }"
                >
                  <li
                    :class="[
                      active ? 'text-white bg-indigo-500' : 'text-grey-900',
                      'cursor-pointer select-none p-2 text-sm',
                    ]"
                  >
                    <div class="flex flex-col">
                      <div class="flex justify-between">
                        <p :class="selected ? 'font-semibold' : 'font-normal'">
                          {{ option.label }}
                        </p>
                        <span v-if="selected" :class="active ? 'text-white' : 'text-indigo-500'">
                          <CheckIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                      </div>
                    </div>
                  </li>
                </ListboxOption>
              </ListboxOptions>
            </transition>
          </div>
        </Listbox>
        <span
          v-if="['all', 'active_inactive', 'active'].includes(showAliasStatus.value)"
          class="bg-green-100 tooltip outline-none h-4 w-4 rounded-full flex items-center justify-center"
          data-tippy-content="Active"
          tabindex="-1"
          ><span class="bg-green-400 h-2 w-2 rounded-full"></span
        ></span>
        <span
          v-if="['all', 'active_inactive', 'inactive'].includes(showAliasStatus.value)"
          class="bg-grey-100 tooltip outline-none h-4 w-4 rounded-full flex items-center justify-center"
          data-tippy-content="Inactive"
          tabindex="-1"
          ><span class="bg-grey-400 h-2 w-2 rounded-full"></span
        ></span>
        <span
          v-if="['all', 'deleted'].includes(showAliasStatus.value)"
          class="bg-red-100 tooltip outline-none h-4 w-4 rounded-full flex items-center justify-center"
          data-tippy-content="Deleted"
          tabindex="-1"
          ><span class="bg-red-400 h-2 w-2 rounded-full"></span
        ></span>
      </div>
      <div class="flex py-4 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center">
          <Listbox as="div" v-model="currentSort">
            <div class="relative">
              <div>
                <ListboxButton
                  class="inline-flex items-center text-sm text-grey-700 hover:text-grey-900 focus:outline-none"
                >
                  <span class="sr-only">Change sort by</span>
                  <ListboxLabel class="cursor-pointer">Sort By</ListboxLabel>
                  <p class="ml-1 font-medium">{{ currentSort.label }}</p>
                  <ChevronDownIcon class="h-5 w-5 text-grey-700" aria-hidden="true" />
                </ListboxButton>
              </div>

              <transition
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <ListboxOptions
                  class="absolute right-0 z-20 mt-2 w-48 origin-top-right overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                  <ListboxOption
                    as="template"
                    v-for="option in sortOptions"
                    :key="option.value"
                    :value="option"
                    v-slot="{ active, selected }"
                  >
                    <li
                      :class="[
                        active ? 'text-white bg-indigo-500' : 'text-grey-900',
                        'cursor-pointer select-none p-2 text-sm',
                      ]"
                    >
                      <div class="flex flex-col">
                        <div class="flex justify-between">
                          <p :class="selected ? 'font-semibold' : 'font-normal'">
                            {{ option.label }}
                          </p>
                          <span v-if="selected" :class="active ? 'text-white' : 'text-indigo-500'">
                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                          </span>
                        </div>
                      </div>
                    </li>
                  </ListboxOption>
                </ListboxOptions>
              </transition>
            </div>
          </Listbox>

          <button
            class="ml-3 disabled:cursor-not-allowed tooltip"
            :disabled="changeSortDirLoading"
            @click="changeSortDir()"
            :data-tippy-content="
              $page.props.sortDirection === 'desc' ? 'Change to ascending' : 'Change to descending'
            "
          >
            <BarsArrowDownIcon v-if="$page.props.sortDirection === 'desc'" class="h-5 w-5" />
            <BarsArrowUpIcon type="button" v-else class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="rows.length">
      <div class="relative">
        <div
          v-if="selectedRows.length > 0"
          id="bulk-actions"
          class="horizontal-scroll absolute px-0.5 top-0 left-12 flex flex-nowrap w-full h-12 items-center space-x-3 bg-gradient-to-r from-white z-10 overflow-x-auto"
          style="width: calc(100% - 3rem)"
        >
          <button
            type="button"
            class="ml-1 inline-flex items-center rounded border border-grey-300 bg-white px-2.5 py-1.5 text-xs font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30"
            :disabled="disabledBulkActivate() || bulkActivateAliasLoading"
            @click="bulkActivateAlias()"
          >
            Activate <loader v-if="bulkActivateAliasLoading" />
          </button>
          <button
            type="button"
            class="inline-flex items-center rounded border border-grey-300 bg-white px-2.5 py-1.5 text-xs font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30"
            :disabled="disabledBulkDeactivate() || bulkDeactivateAliasLoading"
            @click="bulkDeactivateAlias()"
          >
            Deactivate <loader v-if="bulkDeactivateAliasLoading" />
          </button>
          <button
            type="button"
            class="inline-flex items-center rounded border border-grey-300 bg-white px-2.5 py-1.5 text-xs font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30 whitespace-nowrap"
            :disabled="bulkEditAliasRecipientsLoading"
            @click="
              selectedRows.length === 1
                ? openAliasRecipientsModal(selectedRows[0])
                : openBulkAliasRecipientsModal()
            "
          >
            Edit Recipients <loader v-if="bulkEditAliasRecipientsLoading" />
          </button>
          <button
            type="button"
            class="inline-flex items-center rounded border border-grey-300 bg-white px-2.5 py-1.5 text-xs font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30"
            :disabled="disabledBulkDelete()"
            @click="
              selectedAliasesToDelete.length === 1
                ? openDeleteModal(selectedAliasesToDelete[0])
                : (bulkDeleteAliasModalOpen = true)
            "
          >
            Delete
          </button>
          <button
            type="button"
            class="inline-flex items-center rounded border border-grey-300 bg-white px-2.5 py-1.5 text-xs font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            @click="
              selectedRowIds.length === 1
                ? openForgetModal(selectedRows[0])
                : (bulkForgetAliasModalOpen = true)
            "
          >
            Forget
          </button>
          <button
            type="button"
            class="inline-flex items-center rounded border border-grey-300 bg-white px-2.5 py-1.5 text-xs font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30"
            :disabled="disabledBulkRestore()"
            @click="
              selectedAliasesToRestore.length === 1
                ? openRestoreModal(selectedAliasesToRestore[0].id)
                : (bulkRestoreAliasModalOpen = true)
            "
          >
            Restore
          </button>
          <span class="font-semibold text-indigo-800 hidden md:inline-block">{{
            selectedRows.length === 1
              ? `${selectedRows.length} alias`
              : `${selectedRows.length} aliases`
          }}</span>
        </div>
        <vue-good-table
          v-on:sort-change="debounceToolips"
          v-on:page-change="debounceToolips"
          v-on:per-page-change="debounceToolips"
          :columns="columns"
          :rows="rows"
          :sort-options="{
            enabled: false,
          }"
          styleClass="vgt-table"
          :row-style-class="rowStyleClassFn"
        >
          <template #table-column="props">
            <span v-if="props.column.field == 'select'">
              <input
                v-if="rows.length <= 25"
                type="checkbox"
                class="h-4 w-4 rounded border-grey-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                :checked="indeterminate || selectedRowIds.length === rows.length"
                :indeterminate="indeterminate"
                @change="selectedRowIds = $event.target.checked ? rows.map(r => r.id) : []"
              />
              <div
                v-else
                type="checkbox"
                class="h-4 w-4 rounded border-grey-300 bg-grey-100 text-indigo-600 focus:ring-indigo-500 sm:left-6 tooltip cursor-not-allowed"
                data-tippy-content="'Select All' is only available when the page size is 25"
              ></div>
            </span>
            <span v-else :class="selectedRows.length > 0 ? 'blur-sm' : ''">
              {{ props.column.label }}
            </span>
          </template>
          <template #table-row="props">
            <span v-if="props.column.field === 'select'" class="flex items-center">
              <div
                v-if="selectedRowIds.includes(props.row.id)"
                class="absolute inset-y-0 left-0 w-0.5 bg-indigo-600"
              ></div>
              <div
                v-if="selectedRowIds.length >= 25 && !selectedRowIds.includes(props.row.id)"
                type="checkbox"
                class="h-4 w-4 rounded border-grey-300 bg-grey-100 text-indigo-600 focus:ring-indigo-500 sm:left-6 cursor-not-allowed"
                title="You cannot select more than 25 aliases"
              ></div>
              <input
                v-else
                type="checkbox"
                class="h-4 w-4 rounded border-grey-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                title="Click to select'"
                :value="props.row.id"
                v-model="selectedRowIds"
              />
            </span>
            <span v-else-if="props.column.field == 'created_at'" class="flex items-center">
              <span
                :class="`bg-${getAliasStatus(props.row).colour}-100`"
                class="tooltip outline-none h-4 w-4 rounded-full flex items-center justify-center mr-2"
                :data-tippy-content="getAliasStatus(props.row).status"
                tabindex="-1"
              >
                <span
                  :class="`bg-${getAliasStatus(props.row).colour}-400`"
                  class="h-2 w-2 rounded-full"
                ></span>
              </span>
              <span
                class="tooltip outline-none text-sm whitespace-nowrap text-grey-500"
                :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
                >{{ $filters.timeAgo(props.row.created_at) }}
              </span>
            </span>
            <span v-else-if="props.column.field == 'email'" class="block">
              <span
                class="text-grey-400 tooltip cursor-pointer outline-none"
                data-tippy-content="Click to copy"
                @click="clipboard(getAliasEmail(rows[props.row.originalIndex]))"
                ><span class="font-semibold text-indigo-800">{{
                  $filters.truncate(getAliasLocalPart(props.row), 60)
                }}</span
                ><span
                  v-if="getAliasLocalPart(props.row).length <= 60"
                  class="font-semibold text-grey-500"
                  >{{
                    $filters.truncate(
                      '@' + props.row.domain,
                      60 - getAliasLocalPart(props.row).length,
                    )
                  }}</span
                >
              </span>
              <div v-if="aliasIdToEdit === props.row.id" class="flex items-center">
                <input
                  @keyup.enter="editAliasDescription(rows[props.row.originalIndex])"
                  @keyup.esc="aliasIdToEdit = aliasDescriptionToEdit = ''"
                  v-model="aliasDescriptionToEdit"
                  type="text"
                  class="grow text-sm appearance-none bg-grey-50 border text-grey-700 focus:outline-none rounded px-2 py-1"
                  :class="
                    aliasDescriptionToEdit.length > 200 ? 'border-red-500' : 'border-transparent'
                  "
                  placeholder="Add description"
                  tabindex="0"
                  autofocus
                />
                <icon
                  name="close"
                  class="inline-block w-6 h-6 text-red-300 fill-current cursor-pointer"
                  @click="aliasIdToEdit = aliasDescriptionToEdit = ''"
                />
                <icon
                  name="save"
                  class="inline-block w-6 h-6 text-cyan-500 fill-current cursor-pointer"
                  @click="editAliasDescription(rows[props.row.originalIndex])"
                />
              </div>
              <div v-else-if="props.row.description" class="flex items-center">
                <span class="inline-block text-grey-400 text-sm py-1 border border-transparent">
                  {{ $filters.truncate(props.row.description, 60) }}
                </span>
                <icon
                  name="edit"
                  class="inline-block w-6 h-6 ml-2 text-grey-300 fill-current cursor-pointer"
                  @click="
                    ;(aliasIdToEdit = props.row.id),
                      (aliasDescriptionToEdit = props.row.description)
                  "
                />
              </div>
              <div v-else>
                <span
                  class="inline-block text-grey-300 text-sm cursor-pointer py-1 border border-transparent"
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
              <span
                v-else-if="props.row.id === recipientsAliasToEdit.id"
                class="inline-block outline-none font-semibold text-indigo-800"
                >{{ aliasRecipientsToEdit.length ? aliasRecipientsToEdit.length : '1' }}</span
              >
              <span
                v-else-if="has(props.row.aliasable, 'default_recipient.email')"
                class="py-1 px-2 text-xs bg-yellow-200 text-yellow-900 rounded-full tooltip outline-none"
                :data-tippy-content="props.row.aliasable.default_recipient.email"
                >{{
                  props.row.aliasable_type === 'App\\Models\\Domain' ? 'domain' : 'username'
                }}'s</span
              >
              <span
                v-else
                class="py-1 px-2 text-xs bg-yellow-200 text-yellow-900 rounded-full tooltip outline-none"
                :data-tippy-content="$page.props.user.email"
                >default</span
              >
              <icon
                name="edit"
                class="ml-2 inline-block w-6 h-6 text-grey-300 fill-current cursor-pointer"
                @click="openAliasRecipientsModal(props.row)"
              />
            </span>
            <span
              v-else-if="props.column.field == 'emails_forwarded'"
              class="font-semibold text-indigo-800"
            >
              {{ props.row.emails_forwarded }} <span class="text-grey-300">/</span>
              {{ props.row.emails_blocked }}
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
              {{ props.row.emails_replied }} <span class="text-grey-300">/</span>
              {{ props.row.emails_sent }}
            </span>
            <span v-else-if="props.column.field === 'active'" class="flex items-center">
              <Toggle
                v-model="rows[props.row.originalIndex].active"
                @on="activateAlias(rows[props.row.originalIndex])"
                @off="deactivateAlias(rows[props.row.originalIndex])"
              />
            </span>
            <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
              <Link
                :href="route('aliases.edit', props.row.id)"
                as="button"
                type="button"
                class="text-indigo-500 hover:text-indigo-800 font-medium"
                >Edit<span class="sr-only">, {{ props.row.email }}</span></Link
              >
              <span
                @click="openSendFromModal(props.row)"
                class="group cursor-pointer flex items-center text-indigo-500 hover:text-indigo-800 font-medium ml-4 tooltip"
                data-tippy-content="Send an email from this alias"
              >
                Send
                <EnvelopeIcon class="ml-1 h-4 w-4" aria-hidden="true" />
              </span>
            </span>
          </template>
        </vue-good-table>

        <div
          class="mt-4 rounded-lg shadow flex items-center justify-between bg-white px-4 py-3 sm:px-6 overflow-x-auto horizontal-scroll"
        >
          <div class="flex flex-1 justify-between items-center md:hidden gap-x-3">
            <Link
              v-if="$page.props.initialRows.prev_page_url"
              :href="$page.props.initialRows.prev_page_url"
              as="button"
              class="relative inline-flex items-center rounded-md border border-grey-300 bg-white px-4 py-2 text-sm font-medium text-grey-700 hover:bg-grey-50"
            >
              Previous
            </Link>
            <span
              v-else
              class="relative inline-flex h-min items-center rounded-md border border-grey-300 px-4 py-2 text-sm font-medium text-grey-700 bg-grey-100"
              >Previous</span
            >
            <div class="flex flex-col items-center justify-center gap-y-2">
              <p class="text-sm text-grey-700 text-center">
                Showing
                {{ ' ' }}
                <span class="font-medium">{{ $page.props.initialRows.from.toLocaleString() }}</span>
                {{ ' ' }}
                to
                {{ ' ' }}
                <span class="font-medium">{{ $page.props.initialRows.to.toLocaleString() }}</span>
                {{ ' ' }}
                of
                {{ ' ' }}
                <span class="font-medium">{{
                  $page.props.initialRows.total.toLocaleString()
                }}</span>
                {{ ' ' }}
                {{ $page.props.initialRows.total === 1 ? 'result' : 'results' }}
              </p>
              <select
                v-model.number="pageSize"
                @change="updatePageSize"
                :disabled="updatePageSizeLoading"
                class="relative rounded border-0 bg-transparent py-1 pr-8 text-grey-900 text-sm ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset ring-grey-300 focus:ring-indigo-600 disabled:cursor-not-allowed"
              >
                <option v-for="size in pageSizeOptions" :value="size">{{ size }}</option>
              </select>
            </div>
            <Link
              v-if="$page.props.initialRows.next_page_url"
              :href="$page.props.initialRows.next_page_url"
              as="button"
              class="relative inline-flex h-min items-center rounded-md border border-grey-300 bg-white px-4 py-2 text-sm font-medium text-grey-700 hover:bg-grey-50"
            >
              Next
            </Link>
            <span
              v-else
              class="relative inline-flex items-center rounded-md border border-grey-300 px-4 py-2 text-sm font-medium text-grey-700 bg-grey-100"
              >Next</span
            >
          </div>
          <div class="hidden md:flex md:flex-1 md:items-center md:justify-between md:gap-x-2">
            <div class="flex items-center gap-x-2">
              <p class="text-sm text-grey-700">
                Showing
                {{ ' ' }}
                <span class="font-medium">{{ $page.props.initialRows.from.toLocaleString() }}</span>
                {{ ' ' }}
                to
                {{ ' ' }}
                <span class="font-medium">{{ $page.props.initialRows.to.toLocaleString() }}</span>
                {{ ' ' }}
                of
                {{ ' ' }}
                <span class="font-medium">{{
                  $page.props.initialRows.total.toLocaleString()
                }}</span>
                {{ ' ' }}
                {{ $page.props.initialRows.total === 1 ? 'result' : 'results' }}
              </p>
              <select
                v-model.number="pageSize"
                @change="updatePageSize"
                :disabled="updatePageSizeLoading"
                class="relative rounded border-0 bg-transparent py-1 pr-8 text-grey-900 text-sm ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset ring-grey-300 focus:ring-indigo-600 disabled:cursor-not-allowed"
              >
                <option v-for="size in pageSizeOptions" :value="size">{{ size }}</option>
              </select>
            </div>

            <nav
              class="isolate inline-flex -space-x-px rounded-md shadow-sm break-"
              aria-label="Pagination"
            >
              <Link
                v-if="$page.props.initialRows.prev_page_url"
                :href="$page.props.initialRows.prev_page_url"
                class="relative inline-flex items-center rounded-l-md border border-grey-300 bg-white px-2 py-2 text-sm font-medium text-grey-500 hover:bg-grey-50 focus:z-20"
              >
                <span class="sr-only">Previous</span>
                <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
              </Link>
              <span
                v-else
                class="disabled cursor-not-allowed relative inline-flex items-center rounded-l-md border border-grey-300 bg-white px-2 py-2 text-sm font-medium text-grey-500 hover:bg-grey-50 focus:z-20"
              >
                <span class="sr-only">Previous</span>
                <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
              </span>

              <div v-for="link in links" v-bind:key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  aria-current="page"
                  class="relative inline-flex items-center border z-10 px-4 py-2 text-sm font-medium focus:z-20"
                  :class="
                    link.active
                      ? 'border-indigo-500 bg-indigo-50 text-indigo-600'
                      : 'border-grey-300 bg-white text-grey-500 hover:bg-grey-50'
                  "
                  >{{ link.label }}</Link
                >
                <span
                  v-else
                  class="relative inline-flex items-center border border-grey-300 bg-white px-4 py-2 text-sm font-medium text-grey-700"
                  >...</span
                >
              </div>

              <Link
                v-if="$page.props.initialRows.next_page_url"
                :href="$page.props.initialRows.next_page_url"
                class="relative inline-flex items-center rounded-r-md border border-grey-300 bg-white px-2 py-2 text-sm font-medium text-grey-500 hover:bg-grey-50 focus:z-20"
              >
                <span class="sr-only">Next</span>
                <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
              </Link>
              <span
                v-else
                class="disabled cursor-not-allowed relative inline-flex items-center rounded-r-md border border-grey-300 bg-white px-2 py-2 text-sm font-medium text-grey-500 hover:bg-grey-50 focus:z-20"
              >
                <span class="sr-only">Next</span>
                <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
              </span>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="Object.keys(route().params).length" class="text-center">
      <AtSymbolIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">
        No Aliases found for that search or with those filters
      </h3>
      <p class="mt-1 text-md text-grey-500">
        Try entering a different search term or changing the filters.
      </p>
      <div class="mt-6">
        <Link
          :href="route('aliases.index')"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          View All Aliases
        </Link>
      </div>
    </div>

    <div v-else class="text-center">
      <AtSymbolIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">
        It doesn't look like you have any aliases yet!
      </h3>
      <p class="mb-4 text-md text-grey-700">There are two ways to create new aliases.</p>
      <h3 class="mb-2 text-lg text-indigo-800 font-semibold">
        Option 1: Create aliases on the fly
      </h3>
      <p class="mb-2 text-grey-700">
        To create aliases on the fly all you have to do is make up any new alias and give that out
        instead of your real email address.
      </p>
      <p class="mb-2 text-grey-700">
        Let's say you're signing up to <b>example.com</b> you could enter
        <b>example@{{ subdomain }}</b> as your email address.
      </p>
      <p class="mb-2 text-grey-700">
        The alias will show up here automatically as soon as it has forwarded its first email.
      </p>
      <p class="mb-2 text-grey-700">
        If you start receiving spam to the alias you can simply deactivate it or delete it all
        together!
      </p>
      <p class="mb-4 text-grey-700">
        Try it out now by sending an email to <b>first@{{ subdomain }}</b> and then refresh this
        page.
      </p>
      <h3 class="mb-2 text-lg text-indigo-800 font-semibold">
        Option 2: Create a unique random alias
      </h3>
      <p class="mb-2 text-grey-700">
        You can click the button above to create a random alias that will look something like this:
      </p>
      <p class="mb-2 text-grey-700">
        <b>x481n904@anonaddy.me</b>
      </p>
      <p clas="text-grey-700">
        This is useful if you do not wish to include your username in the email as a potential link
        between aliases.
      </p>
      <div class="mt-4">
        <button
          @click="createAliasModalOpen = true"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
          Create Your First Alias
        </button>
      </div>
    </div>

    <Modal :open="createAliasModalOpen" @close="createAliasModalOpen = false">
      <template v-slot:title> Create new alias </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Other aliases e.g. alias@{{ subdomain }} can also be created automatically when they
          receive their first email.
        </p>
        <label for="alias_domain" class="block font-medium leading-6 text-grey-600 text-sm my-2">
          Alias Domain
        </label>
        <div class="block relative w-full mb-4">
          <select
            v-model="createAliasDomain"
            id="alias_domain"
            class="block w-full rounded border-0 bg-transparent py-2 text-grey-900 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
            required
          >
            <option v-for="domainOption in domainOptions" :key="domainOption" :value="domainOption">
              {{ domainOption }}
            </option>
          </select>
        </div>

        <label
          for="alias_format"
          class="block font-medium leading-6 text-grey-600 text-sm mt-4 mb-2"
        >
          Alias Format
        </label>
        <div class="block relative w-full mb-4">
          <select
            v-model="createAliasFormat"
            id="alias_format"
            class="block w-full rounded border-0 bg-transparent py-2 text-grey-900 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
            required
          >
            <option
              v-for="formatOption in aliasFormatOptions"
              :key="formatOption.value"
              :value="formatOption.value"
              :disabled="createAliasDomainIsShared && formatOption.value === 'custom'"
            >
              {{ formatOption.label }}
              {{
                createAliasDomainIsShared && formatOption.value === 'custom'
                  ? '(Not available for shared domains)'
                  : ''
              }}
            </option>
          </select>
        </div>

        <div v-if="createAliasFormat === 'custom'">
          <label
            for="alias_local_part"
            class="block font-medium leading-6 text-grey-600 text-sm my-2"
          >
            Alias Local Part
          </label>
          <p v-show="errors.createAliasLocalPart" class="mb-3 text-red-500 text-sm">
            {{ errors.createAliasLocalPart }}
          </p>
          <input
            v-model="createAliasLocalPart"
            id="alias_local_part"
            type="text"
            class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
            :class="errors.createAliasLocalPart ? 'border-red-500' : ''"
            placeholder="Enter local part..."
            autofocus
          />
        </div>

        <label
          for="alias_description"
          class="block font-medium leading-6 text-grey-600 text-sm my-2"
        >
          Description
        </label>
        <p v-show="errors.createAliasDescription" class="mb-3 text-red-500 text-sm">
          {{ errors.createAliasDescription }}
        </p>
        <input
          v-model="createAliasDescription"
          id="alias_description"
          type="text"
          class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
          :class="errors.createAliasDescription ? 'ring-red-500' : ''"
          placeholder="Enter description (optional)..."
          autofocus
        />

        <label
          for="alias_recipient_ids"
          class="block font-medium leading-6 text-grey-600 text-sm my-2"
        >
          Recipients
        </label>
        <p v-show="errors.createAliasRecipientIds" class="mb-3 text-red-500 text-sm">
          {{ errors.createAliasRecipientIds }}
        </p>
        <multiselect
          id="alias_recipient_ids"
          v-model="createAliasRecipientIds"
          mode="tags"
          value-prop="id"
          :options="recipientOptions"
          :multiple="true"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="true"
          :max="10"
          class="p-0"
          placeholder="Select recipient(s) (optional)..."
          label="email"
          track-by="email"
        >
        </multiselect>

        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            @click="createNewAlias"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="createAliasLoading"
          >
            Create Alias
            <loader v-if="createAliasLoading" />
          </button>
          <button
            @click="createAliasModalOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="editAliasRecipientsModalOpen" @close="closeAliasRecipientsModal">
      <template v-slot:title> Update Alias Recipients </template>
      <template v-slot:content>
        <p class="my-4 text-grey-700">
          Select the recipients for this alias. You can choose multiple recipients. Leave it empty
          if you would like to use the default recipient.
        </p>
        <multiselect
          v-model="aliasRecipientsToEdit"
          mode="tags"
          value-prop="id"
          :options="recipientOptions"
          :multiple="true"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="true"
          :max="10"
          class="p-0"
          placeholder="Select recipient(s)"
          label="email"
          track-by="email"
        >
        </multiselect>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="editAliasRecipients()"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="editAliasRecipientsLoading"
          >
            Update Recipients
            <loader v-if="editAliasRecipientsLoading" />
          </button>
          <button
            @click="closeAliasRecipientsModal()"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="bulkEditAliasRecipientsModalOpen" @close="closeBulkAliasRecipientsModal()">
      <template v-slot:title> Update Recipients for Aliases </template>
      <template v-slot:content>
        <p class="my-4 text-grey-700">
          Select the recipients for these <b>{{ selectedRowIds.length }}</b> aliases. You can choose
          multiple recipients. Leave it empty if you would like to use the default recipient.
        </p>
        <multiselect
          v-model="aliasRecipientsToEdit"
          mode="tags"
          value-prop="id"
          :options="recipientOptions"
          :multiple="true"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="true"
          :max="10"
          class="p-0"
          placeholder="Select recipient(s)"
          label="email"
          track-by="email"
        >
        </multiselect>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="bulkEditAliasRecipients()"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="bulkEditAliasRecipientsLoading"
          >
            Update Recipients
            <loader v-if="bulkEditAliasRecipientsLoading" />
          </button>
          <button
            @click="closeBulkAliasRecipientsModal()"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="restoreAliasModalOpen" @close="closeRestoreModal">
      <template v-slot:title> Restore alias </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to restore this alias? Once restored it will be
          <b>able to receive emails again</b>.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="restoreAlias(aliasIdToRestore)"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="restoreAliasLoading"
          >
            Restore alias
            <loader v-if="restoreAliasLoading" />
          </button>
          <button
            @click="closeRestoreModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="bulkRestoreAliasModalOpen" @close="bulkRestoreAliasModalOpen = false">
      <template v-slot:title> Restore aliases </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to restore these
          <b>{{ selectedAliasesToRestore.length }}</b> aliases? Once restored they will be
          <b>able to receive emails again</b>.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="bulkRestoreAlias()"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="bulkRestoreAliasLoading"
          >
            Restore aliases
            <loader v-if="bulkRestoreAliasLoading" />
          </button>
          <button
            @click="bulkRestoreAliasModalOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteAliasModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete alias </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to delete <b class="break-words">{{ aliasToDelete.email }}</b
          >? You can restore it if you later change your mind. Once deleted,
          <b class="break-words">{{ aliasToDelete.email }}</b> will
          <b>reject any emails sent to it</b>.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteAlias(aliasToDelete.id)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="deleteAliasLoading"
          >
            Delete alias
            <loader v-if="deleteAliasLoading" />
          </button>
          <button
            @click="closeDeleteModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="bulkDeleteAliasModalOpen" @close="bulkDeleteAliasModalOpen = false">
      <template v-slot:title> Delete aliases </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to delete these
          <b>{{ selectedAliasesToDelete.length }}</b> aliases? You can restore them if you later
          change your mind. Once deleted, these aliases will <b>reject any emails sent to them</b>.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="bulkDeleteAlias()"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="bulkDeleteAliasLoading"
          >
            Delete aliases
            <loader v-if="bulkDeleteAliasLoading" />
          </button>
          <button
            @click="bulkDeleteAliasModalOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="forgetAliasModalOpen" @close="closeForgetModal">
      <template v-slot:title> Forget alias </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to forget <b class="break-words">{{ aliasToForget.email }}</b
          >? Forgetting an alias will disassociate it from your account.
        </p>
        <p class="mt-4 text-grey-700">
          <b>Note:</b> If this alias uses a shared domain then it can <b>never be restored</b> or
          used again so make sure you are certain. If it is a standard alias then it can be created
          again since it will be as if it never existed.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="forgetAlias(aliasToForget.id)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="forgetAliasLoading"
          >
            Forget alias
            <loader v-if="forgetAliasLoading" />
          </button>
          <button
            @click="closeForgetModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="bulkForgetAliasModalOpen" @close="bulkForgetAliasModalOpen = false">
      <template v-slot:title> Forget aliases </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to forget these
          <b>{{ selectedRowIds.length }}</b> aliases? Forgetting these aliases will disassociate
          them from your account.
        </p>
        <p class="mt-4 text-grey-700">
          <b>Note:</b> If the alias uses a shared domain then it can <b>never be restored</b> or
          used again so make sure you are certain. If it is a standard alias then it can be created
          again since it will be as if it never existed.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="bulkForgetAlias()"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="bulkForgetAliasLoading"
          >
            Forget aliases
            <loader v-if="bulkForgetAliasLoading" />
          </button>
          <button
            @click="bulkForgetAliasModalOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="sendFromAliasModalOpen" @close="closeSendFromModal">
      <template v-slot:title> Send from alias </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Use this to automatically create the correct address to send an email to in order to send
          an <b>email from this alias</b>.
        </p>
        <p class="mt-4 text-grey-700">
          To send from an alias you must send the email from a <b>verified recipient</b> on your
          addy.io account.
        </p>
        <label for="send_from_alias" class="block font-medium leading-6 text-grey-600 text-sm my-2">
          Alias to send from
        </label>
        <input
          v-model="aliasToSendFrom.email"
          id="send_from_alias"
          type="text"
          class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 bg-grey-50"
          disabled
        />
        <label
          for="send_from_alias_destination"
          class="block font-medium leading-6 text-grey-600 text-sm my-2"
        >
          To email destination
        </label>
        <p v-show="errors.sendFromAliasDestination" class="mb-3 text-red-500 text-sm">
          {{ errors.sendFromAliasDestination }}
        </p>
        <input
          v-model="sendFromAliasDestination"
          id="send_from_alias_destination"
          type="text"
          class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
          :class="errors.sendFromAliasDestination ? 'ring-red-500' : ''"
          placeholder="Enter email..."
          autofocus
        />
        <div v-if="sendFromAliasEmailToSendTo">
          <p for="alias_domain" class="block font-medium leading-6 text-grey-600 text-sm my-2">
            Send your message to this email
          </p>
          <div
            @click="clipboard(sendFromAliasEmailToSendTo), setSendFromAliasCopied()"
            class="flex items-center justify-between cursor-pointer text-sm font-medium border-t-4 rounded-sm text-green-800 border-green-600 bg-green-100 p-2 mb-3"
            role="alert"
          >
            <span>
              {{ sendFromAliasEmailToSendTo }}
            </span>
            <svg
              v-if="sendFromAliasCopied"
              viewBox="0 0 24 24"
              width="20"
              height="20"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polyline points="9 11 12 14 22 4"></polyline>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <svg
              v-else
              viewBox="0 0 24 24"
              width="20"
              height="20"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
          </div>
          <a
            :href="'mailto:' + sendFromAliasEmailToSendTo"
            class="flex items-center justify-between cursor-pointer text-sm border-t-4 rounded-sm text-green-800 border-green-600 bg-green-100 p-2 mb-4"
            role="alert"
            title="Click To Open Mail Application"
          >
            Click to open mail application
          </a>
        </div>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="displaySendFromAddress(aliasToSendFrom)"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none disabled:cursor-not-allowed"
            :disabled="sendFromAliasLoading"
          >
            Show address
            <loader v-if="sendFromAliasLoading" />
          </button>
          <button
            @click="closeSendFromModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>
    <Modal :open="moreInfoOpen" @close="moreInfoOpen = false">
      <template v-slot:title> More information </template>
      <template v-slot:content>
        <p class="mt-4 text-md text-grey-700">Aliases come under two different categories.</p>

        <p class="mt-4 text-grey-700">
          <b>Standard Aliases</b> - Standard aliases use a domain that is unique only to you, all
          aliases for your custom domains are classed as standard aliases. Standard aliases can be
          created automatically when they receive their first email (if catch-all is enabled for the
          domain). If you signed up with a username of johndoe and gave out the following alias -
          hello@johndoe.anonaddy.com then this would be a standard alias.
        </p>
        <p class="mt-4 text-grey-700">
          <b>Shared Domain Aliases</b> - A shared domain alias is any alias that has a domain name
          that is also shared with other users. For example anyone can generate an alias with the
          @anonaddy.me domain. Aliases with shared domain names must be pre-generated and cannot be
          created on-the-fly like standard aliases.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row">
          <button
            @click="moreInfoOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import Modal from '../../Components/Modal.vue'
import Toggle from '../../Components/Toggle.vue'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import { VueGoodTable } from 'vue-good-table-next'
import Multiselect from '@vueform/multiselect'
import { notify } from '@kyvg/vue3-notification'
import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import {
  InformationCircleIcon,
  AtSymbolIcon,
  BarsArrowDownIcon,
  BarsArrowUpIcon,
  EnvelopeIcon,
} from '@heroicons/vue/24/outline'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDownIcon,
  CheckIcon,
  PlusIcon,
} from '@heroicons/vue/20/solid'

const props = defineProps({
  initialRows: {
    type: Object,
    required: true,
  },
  recipientOptions: {
    type: Array,
    required: true,
  },
  subdomain: {
    type: String,
    required: true,
  },
  domainOptions: {
    type: Array,
    required: true,
  },
  defaultAliasDomain: {
    type: String,
    required: true,
  },
  defaultAliasFormat: {
    type: String,
    required: true,
  },
  search: {
    type: String,
  },
  initialPageSize: {
    type: Number,
    required: true,
  },
  sort: {
    type: String,
  },
  sortDirection: {
    type: String,
  },
  currentAliasStatus: {
    type: String,
  },
  sharedDomains: {
    type: Array,
    required: true,
  },
})

const rows = ref(props.initialRows.data)

const selectedRowIds = ref([])
const selectedRows = computed(() =>
  _.filter(rows.value, row => selectedRowIds.value.includes(row.id)),
)
const checked = ref(false)
const indeterminate = computed(
  () => selectedRows.value.length > 0 && selectedRows.value.length < rows.value.length,
)

const selectedAliasesToDelete = computed(() =>
  _.filter(selectedRows.value, row => row.deleted_at === null),
)
const selectedAliasesToRestore = computed(() =>
  _.filter(selectedRows.value, row => row.deleted_at !== null),
)

const createAliasDomainIsShared = computed(() =>
  props.sharedDomains.includes(createAliasDomain.value),
)

const links = ref(props.initialRows.links.slice(1, -1))

const aliasIdToEdit = ref('')
const aliasDescriptionToEdit = ref('')
const aliasToDelete = ref({})
const aliasToForget = ref({})
const aliasToSendFrom = ref({})
const sendFromAliasDestination = ref('')
const sendFromAliasEmailToSendTo = ref('')
const sendFromAliasCopied = ref(false)
const aliasIdToRestore = ref('')
const deleteAliasLoading = ref(false)
const forgetAliasLoading = ref(false)
const deleteAliasModalOpen = ref(false)
const forgetAliasModalOpen = ref(false)
const sendFromAliasLoading = ref(false)
const sendFromAliasModalOpen = ref(false)
const restoreAliasLoading = ref(false)
const restoreAliasModalOpen = ref(false)
const editAliasRecipientsLoading = ref(false)
const editAliasRecipientsModalOpen = ref(false)
const createAliasModalOpen = ref(false)
const createAliasLoading = ref(false)
const createAliasDomain = ref(props.defaultAliasDomain)
const createAliasLocalPart = ref('')
const createAliasDescription = ref('')
const createAliasRecipientIds = ref([])
const createAliasFormat = ref(props.defaultAliasFormat)
const moreInfoOpen = ref(false)
const recipientsAliasToEdit = ref({})
const aliasRecipientsToEdit = ref([])
const tippyInstance = ref(null)
const errors = ref({})
const bulkActivateAliasLoading = ref(false)
const bulkDeactivateAliasLoading = ref(false)
const bulkEditAliasRecipientsLoading = ref(false)
const bulkEditAliasRecipientsModalOpen = ref(false)
const bulkDeleteAliasLoading = ref(false)
const bulkDeleteAliasModalOpen = ref(false)
const bulkForgetAliasLoading = ref(false)
const bulkForgetAliasModalOpen = ref(false)
const bulkRestoreAliasLoading = ref(false)
const bulkRestoreAliasModalOpen = ref(false)
const changeSortDirLoading = ref(false)
const pageSize = ref(props.initialPageSize)
const updatePageSizeLoading = ref(false)

const pageSizeOptions = [25, 50, 100]

const displayOptions = [
  {
    value: 'all',
    label: 'All',
    params: {
      deleted: 'with',
    },
    omit: ['page', 'active'],
  },
  {
    value: 'active_inactive',
    label: 'Active and Inactive',
    params: {},
    omit: ['page', 'active', 'deleted'],
  },
  {
    value: 'active',
    label: 'Active only',
    params: {
      active: 'true',
    },
    omit: ['page', 'deleted'],
  },
  {
    value: 'inactive',
    label: 'Inactive only',
    params: {
      active: 'false',
    },
    omit: ['page', 'deleted'],
  },
  {
    value: 'deleted',
    label: 'Deleted only',
    params: {
      deleted: 'only',
    },
    omit: ['page', 'active'],
  },
]

const showAliasStatus = ref(_.find(displayOptions, ['value', props.currentAliasStatus]))

const sortOptions = [
  {
    value: 'active',
    label: 'Active',
  },
  {
    value: 'email',
    label: 'Alias',
  },
  {
    value: 'created_at',
    label: 'Created At',
  },
  {
    value: 'deleted_at',
    label: 'Deleted At',
  },
  {
    value: 'domain',
    label: 'Domain',
  },
  {
    value: 'emails_blocked',
    label: 'Emails Blocked',
  },
  {
    value: 'emails_forwarded',
    label: 'Emails Forwarded',
  },
  {
    value: 'emails_replied',
    label: 'Emails Replied',
  },
  {
    value: 'emails_sent',
    label: 'Emails Sent',
  },
  {
    value: 'updated_at',
    label: 'Updated At',
  },
]
const currentSort = ref(_.find(sortOptions, ['value', props.sort]))

const aliasFormatOptions = [
  {
    value: 'random_characters',
    label: 'Random Characters',
    paid: false,
  },
  {
    value: 'uuid',
    label: 'UUID',
    paid: false,
  },
  {
    value: 'random_words',
    label: 'Random Words',
    paid: true,
  },
  {
    value: 'custom',
    label: 'Custom',
    paid: false,
  },
]
const columns = [
  {
    label: '',
    field: 'select',
  },
  {
    label: 'Created',
    field: 'created_at',
  },
  {
    label: 'Alias',
    field: 'email',
  },
  {
    label: 'Recipients',
    field: 'recipients',
    tdClass: 'text-center',
  },
  {
    label: 'Forwards/Blocks',
    field: 'emails_forwarded',
    type: 'number',
    tdClass: 'text-center',
  },
  {
    label: 'Replies/Sends',
    field: 'emails_replied',
    type: 'number',
    tdClass: 'text-center',
  },
  {
    label: 'Active',
    field: 'active',
    type: 'boolean',
  },
  {
    label: '',
    field: 'actions',
  },
]

watch(
  () => showAliasStatus,
  function (status) {
    let params = Object.assign(route().params, status.value.params)

    router.visit(route('aliases.index', _.omit(params, status.value.omit)), {
      only: ['initialRows', 'search', 'sort', 'sortDirection', 'currentAliasStatus'],
    })
  },
  { deep: true },
)

watch(
  () => currentSort,
  function (sort) {
    let params = Object.assign(route().params, {
      sort: props.sortDirection === 'desc' ? '-' + sort.value.value : sort.value.value,
    })

    router.visit(route('aliases.index', _.omit(params, ['page'])), {
      only: ['initialRows', 'search', 'sort', 'sortDirection', 'currentAliasStatus'],
    })
  },
  { deep: true },
)

watch(createAliasDomainIsShared, isShared => {
  if (isShared) {
    createAliasFormat.value = 'random_characters'
  }
})

onMounted(() => {
  debounceToolips()
})

const createNewAlias = () => {
  errors.value = {}

  // Validate alias local part
  if (createAliasFormat.value === 'custom' && !validLocalPart(createAliasLocalPart.value)) {
    return (errors.value.createAliasLocalPart = 'Valid local part required')
  }

  if (createAliasDescription.value.length > 200) {
    return (errors.value.createAliasDescription = 'Description cannot exceed 200 characters')
  }

  createAliasLoading.value = true

  axios
    .post(
      '/api/v1/aliases',
      JSON.stringify({
        domain: createAliasDomain.value,
        local_part: createAliasLocalPart.value,
        description: createAliasDescription.value,
        format: createAliasFormat.value,
        recipient_ids: createAliasRecipientIds.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(({ data }) => {
      // Show active/inactive
      router.visit(route('aliases.index'), {
        only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
        onSuccess: page => {
          createAliasLoading.value = false
          createAliasLocalPart.value = ''
          createAliasDescription.value = ''
          createAliasRecipientIds.value = []
          createAliasModalOpen.value = false
          successMessage('New alias created successfully')
        },
      })
    })
    .catch(error => {
      createAliasLoading.value = false
      if ([429, 403].includes(error.response.status)) {
        errorMessage(error.response.data)
      } else if (error.response.status === 422) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const editAliasDescription = alias => {
  if (aliasDescriptionToEdit.value.length > 200) {
    return errorMessage('Description cannot be more than 200 characters')
  }

  axios
    .patch(
      `/api/v1/aliases/${alias.id}`,
      JSON.stringify({
        description: aliasDescriptionToEdit.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      alias.description = aliasDescriptionToEdit.value
      aliasIdToEdit.value = ''
      aliasDescriptionToEdit.value = ''
      successMessage('Alias description updated')
    })
    .catch(error => {
      aliasIdToEdit.value = ''
      aliasDescriptionToEdit.value = ''
      errorMessage()
    })
}

const editAliasRecipients = () => {
  editAliasRecipientsLoading.value = true

  axios
    .post(
      '/api/v1/alias-recipients',
      JSON.stringify({
        alias_id: recipientsAliasToEdit.value.id,
        recipient_ids: aliasRecipientsToEdit.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      let alias = _.find(rows.value, ['id', recipientsAliasToEdit.value.id])

      // JSON required to fix failed to execute 'replaceState' on 'History' error
      alias.recipients = JSON.parse(
        JSON.stringify(
          _.filter(props.recipientOptions, recipient =>
            aliasRecipientsToEdit.value.includes(recipient.id),
          ),
        ),
      )

      editAliasRecipientsLoading.value = false
      closeAliasRecipientsModal()
      successMessage('Alias recipients updated')
    })
    .catch(error => {
      editAliasRecipientsLoading.value = false
      closeAliasRecipientsModal()
      errorMessage()
    })
}

const bulkEditAliasRecipients = () => {
  bulkEditAliasRecipientsLoading.value = true
  // No need to filter
  let selectedAliasesToEditRecipients = selectedRows.value

  axios
    .post(
      '/api/v1/aliases/recipients/bulk',
      JSON.stringify({
        ids: selectedAliasesToEditRecipients.map(a => a.id),
        recipient_ids: aliasRecipientsToEdit.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      _.each(selectedAliasesToEditRecipients, alias => {
        // JSON required to fix failed to execute 'replaceState' on 'History' error
        alias.recipients = JSON.parse(
          JSON.stringify(
            _.filter(props.recipientOptions, recipient =>
              aliasRecipientsToEdit.value.includes(recipient.id),
            ),
          ),
        )
      })

      bulkEditAliasRecipientsLoading.value = false
      closeBulkAliasRecipientsModal()
      successMessage(response.data.message)
    })
    .catch(error => {
      bulkEditAliasRecipientsLoading.value = false
      closeBulkAliasRecipientsModal()
      errorMessage()
    })
}

const activateAlias = alias => {
  axios
    .post(
      `/api/v1/active-aliases`,
      JSON.stringify({
        id: alias.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      alias.active = true
      debounceToolips()
    })
    .catch(error => {
      alias.active = false
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const bulkActivateAlias = () => {
  bulkActivateAliasLoading.value = true
  // First filter selected rows to remove any that are already active or are currently deleted
  let selectedAliasesToActivate = _.filter(selectedRows.value, r => {
    return !r.active && r.deleted_at === null
  })

  axios
    .post(
      `/api/v1/aliases/activate/bulk`,
      JSON.stringify({
        ids: selectedAliasesToActivate.map(a => a.id),
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      _.each(selectedAliasesToActivate, r => {
        r.active = true
      })
      bulkActivateAliasLoading.value = false
      debounceToolips()
      successMessage(response.data.message)
    })
    .catch(error => {
      bulkActivateAliasLoading.value = false
      if (error.response.status === 429) {
        errorMessage('Too many bulk requests, please wait a little while before trying again')
      } else if (error.response.data.message !== undefined) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const deactivateAlias = alias => {
  axios
    .delete(`/api/v1/active-aliases/${alias.id}`)
    .then(response => {
      alias.active = false
    })
    .catch(error => {
      alias.active = true
      debounceToolips()
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const bulkDeactivateAlias = () => {
  bulkDeactivateAliasLoading.value = true
  // First filter selected rows to remove any that are already deactivated
  let selectedAliasesToDeactivate = _.filter(selectedRows.value, r => {
    return r.active
  })

  axios
    .post(
      `/api/v1/aliases/deactivate/bulk`,
      JSON.stringify({
        ids: selectedAliasesToDeactivate.map(a => a.id),
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      _.each(selectedAliasesToDeactivate, r => {
        r.active = false
      })
      bulkDeactivateAliasLoading.value = false
      debounceToolips()
      successMessage(response.data.message)
    })
    .catch(error => {
      bulkDeactivateAliasLoading.value = false
      if (error.response.status === 429) {
        errorMessage('Too many bulk requests, please wait a little while before trying again')
      } else if (error.response.data.message !== undefined) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const deleteAlias = id => {
  deleteAliasLoading.value = true

  axios
    .delete(`/api/v1/aliases/${id}`)
    .then(response => {
      // If showing deleted then set as deleted and inactive
      if (['all', 'deleted'].includes(props.currentAliasStatus)) {
        let alias = _.find(rows.value, ['id', id])

        alias.deleted_at = dayjs.utc().format()
        alias.active = false
        alias.recipients = []

        deleteAliasModalOpen.value = false
        deleteAliasLoading.value = false
        debounceToolips()
        successMessage('Alias deleted successfully')
      } else {
        router.reload({
          only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
          onSuccess: page => {
            deleteAliasModalOpen.value = false
            deleteAliasLoading.value = false
            rows.value = props.initialRows.data
            successMessage('Alias deleted successfully')
          },
        })
      }
    })
    .catch(error => {
      errorMessage()
      deleteAliasModalOpen.value = false
      deleteAliasLoading.value = false
    })
}

const bulkDeleteAlias = () => {
  bulkDeleteAliasLoading.value = true

  axios
    .post(
      `/api/v1/aliases/delete/bulk`,
      JSON.stringify({
        ids: selectedAliasesToDelete.value.map(a => a.id),
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      // If showing deleted then set as deleted and inactive
      if (['all', 'deleted'].includes(props.currentAliasStatus)) {
        _.each(selectedAliasesToDelete.value, r => {
          r.deleted_at = dayjs.utc().format()
          r.active = false
          r.recipients = []
        })
        bulkDeleteAliasLoading.value = false
        bulkDeleteAliasModalOpen.value = false
        debounceToolips()
        successMessage(response.data.message)
      } else {
        router.reload({
          only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
          onSuccess: page => {
            bulkDeleteAliasLoading.value = false
            bulkDeleteAliasModalOpen.value = false
            rows.value = props.initialRows.data
            successMessage(response.data.message)
          },
        })
      }
    })
    .catch(error => {
      bulkDeleteAliasLoading.value = false
      bulkDeleteAliasModalOpen.value = false
      if (error.response.status === 429) {
        errorMessage('Too many bulk requests, please wait a little while before trying again')
      } else if (error.response.data.message !== undefined) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const forgetAlias = id => {
  forgetAliasLoading.value = true

  axios
    .delete(`/api/v1/aliases/${id}/forget`)
    .then(response => {
      router.reload({
        only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
        onSuccess: page => {
          forgetAliasModalOpen.value = false
          forgetAliasLoading.value = false
          rows.value = props.initialRows.data
          successMessage('Alias forgotten successfully')
        },
      })
    })
    .catch(error => {
      errorMessage()
      forgetAliasModalOpen.value = false
      forgetAliasLoading.value = false
    })
}

const bulkForgetAlias = () => {
  bulkForgetAliasLoading.value = true
  // No need to filter
  let selectedAliasesToForget = selectedRows.value

  axios
    .post(
      `/api/v1/aliases/forget/bulk`,
      JSON.stringify({
        ids: selectedAliasesToForget.map(a => a.id),
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      router.reload({
        only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
        onSuccess: page => {
          bulkForgetAliasLoading.value = false
          bulkForgetAliasModalOpen.value = false
          rows.value = props.initialRows.data
          successMessage(response.data.message)
        },
      })
    })
    .catch(error => {
      bulkForgetAliasLoading.value = false
      bulkForgetAliasModalOpen.value = false
      if (error.response.status === 429) {
        errorMessage('Too many bulk requests, please wait a little while before trying again')
      } else if (error.response.data.message !== undefined) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const restoreAlias = id => {
  restoreAliasLoading.value = true

  axios
    .patch(`/api/v1/aliases/${id}/restore`, {
      headers: { 'Content-Type': 'application/json' },
    })
    .then(response => {
      // If showing only deleted then reload all aliases
      if (props.currentAliasStatus === 'deleted') {
        router.reload({
          only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
          onSuccess: page => {
            restoreAliasModalOpen.value = false
            restoreAliasLoading.value = false
            rows.value = props.initialRows.data
            successMessage('Alias restored successfully')
          },
        })
      } else {
        let alias = _.find(rows.value, ['id', id])
        alias.deleted_at = null
        alias.active = true

        restoreAliasModalOpen.value = false
        restoreAliasLoading.value = false
        successMessage('Alias restored successfully')
      }
    })
    .catch(error => {
      errorMessage()
      restoreAliasModalOpen.value = false
      restoreAliasLoading.value = false
    })
}

const bulkRestoreAlias = () => {
  bulkRestoreAliasLoading.value = true

  axios
    .post(
      `/api/v1/aliases/restore/bulk`,
      JSON.stringify({
        ids: selectedAliasesToRestore.value.map(a => a.id),
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      // If showing only deleted then reload all aliases
      if (props.currentAliasStatus === 'deleted') {
        router.reload({
          only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
          onSuccess: page => {
            bulkRestoreAliasLoading.value = false
            bulkRestoreAliasModalOpen.value = false
            rows.value = props.initialRows.data
            successMessage(response.data.message)
          },
        })
      } else {
        _.each(selectedAliasesToRestore.value, r => {
          r.deleted_at = null
          r.active = true
        })
        bulkRestoreAliasLoading.value = false
        bulkRestoreAliasModalOpen.value = false
        successMessage(response.data.message)
      }
    })
    .catch(error => {
      bulkRestoreAliasLoading.value = false
      bulkRestoreAliasModalOpen.value = false
      if (error.response.status === 429) {
        errorMessage('Too many bulk requests, please wait a little while before trying again')
      } else if (error.response.data.message !== undefined) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const changeSortDir = () => {
  changeSortDirLoading.value = true

  let params = Object.assign(route().params, {
    sort: props.sortDirection === 'desc' ? _.trimStart(props.sort, '-') : '-' + props.sort,
  })

  router.visit(route(route().current(), _.omit(params, ['page'])), {
    only: ['initialRows', 'search', 'currentAliasStatus', 'sort', 'sortDirection'],
    onSuccess: page => {
      changeSortDirLoading.value = false
    },
  })
}

const updatePageSize = () => {
  updatePageSizeLoading.value = true

  let params = Object.assign(route().params, {
    page_size: pageSize.value,
  })

  let omit = pageSize.value === 25 ? ['page', 'page_size'] : ['page']

  router.visit(route('aliases.index', _.omit(params, omit)), {
    only: [
      'initialRows',
      'search',
      'sort',
      'sortDirection',
      'currentAliasStatus',
      'initialPageSize',
    ],
    onSuccess: page => {
      updatePageSizeLoading.value = false
    },
  })
}

const openDeleteModal = alias => {
  deleteAliasModalOpen.value = true
  aliasToDelete.value = alias
}

const closeDeleteModal = () => {
  deleteAliasModalOpen.value = false
  _.delay(() => (aliasToDelete.value = {}), 300)
}

const openForgetModal = alias => {
  forgetAliasModalOpen.value = true
  aliasToForget.value = alias
}

const closeForgetModal = () => {
  forgetAliasModalOpen.value = false
  _.delay(() => (aliasToForget.value = {}), 300)
}

const openSendFromModal = alias => {
  sendFromAliasDestination.value = ''
  sendFromAliasEmailToSendTo.value = ''
  sendFromAliasCopied.value = false
  sendFromAliasModalOpen.value = true
  aliasToSendFrom.value = alias
}

const closeSendFromModal = () => {
  sendFromAliasModalOpen.value = false
  _.delay(() => (aliasToSendFrom.value = {}), 300)
}

const openRestoreModal = id => {
  restoreAliasModalOpen.value = true
  aliasIdToRestore.value = id
}

const closeRestoreModal = () => {
  restoreAliasModalOpen.value = false
  aliasIdToRestore.value = ''
}

const openAliasRecipientsModal = alias => {
  editAliasRecipientsModalOpen.value = true
  recipientsAliasToEdit.value = alias
  aliasRecipientsToEdit.value = _.map(alias.recipients, recipient => recipient.id)
}

const closeAliasRecipientsModal = () => {
  editAliasRecipientsModalOpen.value = false
  _.delay(() => (aliasRecipientsToEdit.value = []), 300)
  recipientsAliasToEdit.value = {}
  debounceToolips()
}

const openBulkAliasRecipientsModal = () => {
  bulkEditAliasRecipientsModalOpen.value = true
  aliasRecipientsToEdit.value = []

  // Leave preselected recipients as blank
  /* aliasRecipientsToEdit.value = _
    .chain(selectedRows.value)
    .flatMap(row => row.recipients.map(r => r.id))
    .uniq()
    .take(10)
    .value() */
}

const closeBulkAliasRecipientsModal = () => {
  bulkEditAliasRecipientsModalOpen.value = false
  _.delay(() => (aliasRecipientsToEdit.value = []), 300)
  debounceToolips()
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

const recipientsTooltip = recipients => {
  return _.reduce(recipients, (list, recipient) => list + `${recipient.email}<br>`, '')
}

const displaySendFromAddress = alias => {
  errors.value = {}

  if (!validEmail(sendFromAliasDestination.value)) {
    errors.value.sendFromAliasDestination = 'Valid Email required'
    return
  }

  sendFromAliasEmailToSendTo.value = `${alias.local_part}+${sendFromAliasDestination.value.replace(
    '@',
    '=',
  )}@${alias.domain}`
}

const setSendFromAliasCopied = () => {
  sendFromAliasCopied.value = true
}

const getAliasEmail = alias => {
  return alias.extension ? `${alias.local_part}+${alias.extension}@${alias.domain}` : alias.email
}

const getAliasLocalPart = alias => {
  return alias.extension ? `${alias.local_part}+${alias.extension}` : alias.local_part
}

const getAliasStatus = alias => {
  if (alias.deleted_at) {
    return {
      colour: 'red',
      status: 'Deleted',
    }
  } else {
    return {
      colour: alias.active ? 'green' : 'grey',
      status: alias.active ? 'Active' : 'Inactive',
    }
  }
}

const has = (object, path) => {
  return _.has(object, path)
}

const validLocalPart = part => {
  let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))$/
  return re.test(part)
}

const validEmail = email => {
  let re =
    /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  return re.test(email)
}

const disabledBulkActivate = () => {
  return !_.find(selectedRows.value, { active: false, deleted_at: null })
}

const disabledBulkDeactivate = () => {
  return !_.find(selectedRows.value, 'active')
}

const disabledBulkDelete = () => {
  return !_.find(selectedRows.value, r => {
    return r.deleted_at === null
  })
}

const disabledBulkRestore = () => {
  return !_.find(selectedRows.value, r => {
    return r.deleted_at !== null
  })
}

const rowStyleClassFn = row => {
  return selectedRowIds.value.includes(row.id) ? 'bg-grey-50' : ''
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
</script>
