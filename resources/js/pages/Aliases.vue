<template>
  <div class="aliases">
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
            <p class="text-grey-300 text-sm tracking-wide uppercase">Active</p>
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
            <p class="text-grey-300 text-sm tracking-wide uppercase">Inactive</p>
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
            <p class="text-grey-300 text-sm tracking-wide uppercase">Emails Forwarded</p>
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
            <p class="text-grey-300 text-sm tracking-wide uppercase">Emails Blocked</p>
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
            <p class="text-grey-300 text-sm tracking-wide uppercase">Email Replies</p>
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
            <p class="text-grey-300 text-sm tracking-wide uppercase">Bandwidth ({{ month }})</p>
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
      <div class="flex flex-wrap mt-4 md:mt-0">
        <div class="block relative mr-4">
          <select
            v-model="showAliases"
            class="block appearance-none w-full text-grey-700 bg-white p-3 pr-8 rounded shadow focus:ring"
            required
          >
            <option value="without">Hide Deleted</option>
            <option value="with">Show Deleted</option>
            <option value="only">Deleted Only</option>
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
        <div>
          <button
            @click="generateAliasModalOpen = true"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto"
          >
            Create New Alias
          </button>
        </div>
      </div>
    </div>

    <vue-good-table
      v-if="initialAliases.length"
      v-on:search="debounceToolips"
      v-on:page-change="debounceToolips"
      v-on:per-page-change="debounceToolips"
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
      <template #emptystate class="flex items-center justify-center h-24 text-lg text-grey-700">
        No aliases found for that search!
      </template>
      <template #table-row="props">
        <span v-if="props.column.field == 'created_at'" class="flex items-center">
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
            class="tooltip outline-none text-sm whitespace-nowrap"
            :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
            >{{ $filters.timeAgo(props.row.created_at) }}
          </span>
        </span>
        <span v-else-if="props.column.field == 'email'" class="block">
          <span
            class="text-grey-400 tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            v-clipboard="() => getAliasEmail(rows[props.row.originalIndex])"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            ><span class="font-semibold text-indigo-800">{{
              $filters.truncate(getAliasLocalPart(props.row), 60)
            }}</span
            ><span v-if="getAliasLocalPart(props.row).length <= 60">{{
              $filters.truncate('@' + props.row.domain, 60 - getAliasLocalPart(props.row).length)
            }}</span>
          </span>
          <div v-if="aliasIdToEdit === props.row.id" class="flex items-center">
            <input
              @keyup.enter="editAlias(rows[props.row.originalIndex])"
              @keyup.esc="aliasIdToEdit = aliasDescriptionToEdit = ''"
              v-model="aliasDescriptionToEdit"
              type="text"
              class="grow text-sm appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
              :class="aliasDescriptionToEdit.length > 200 ? 'border-red-500' : 'border-transparent'"
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
              @click="editAlias(rows[props.row.originalIndex])"
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
                ;(aliasIdToEdit = props.row.id), (aliasDescriptionToEdit = props.row.description)
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
          <span v-else-if="props.row.id === recipientsAliasToEdit.id">{{
            aliasRecipientsToEdit.length ? aliasRecipientsToEdit.length : '1'
          }}</span>
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
            :data-tippy-content="defaultRecipientEmail"
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
          <more-options>
            <div role="none">
              <MenuItem>
                <span
                  @click="openSendFromModal(props.row)"
                  class="group cursor-pointer flex items-center px-4 py-3 text-sm text-grey-700 hover:bg-grey-100 hover:text-grey-900"
                  role="menuitem"
                >
                  <icon name="send" class="block mr-3 w-5 h-5 text-grey-300 outline-none" />
                  Send From
                </span>
              </MenuItem>
            </div>
            <MenuItem v-if="props.row.deleted_at">
              <span
                @click="openRestoreModal(props.row.id)"
                class="group cursor-pointer flex items-center px-4 py-3 text-sm text-grey-700 hover:bg-grey-100 hover:text-grey-900"
                role="menuitem"
              >
                <icon
                  name="undo"
                  class="block mr-3 w-5 h-5 text-grey-300 fill-current outline-none"
                />
                Restore
              </span>
            </MenuItem>
            <MenuItem v-else>
              <span
                @click="openDeleteModal(props.row)"
                class="group cursor-pointer flex items-center px-4 py-3 text-sm text-grey-700 hover:bg-grey-100 hover:text-grey-900"
                role="menuitem"
              >
                <icon
                  name="trash"
                  class="block mr-3 w-5 h-5 text-grey-300 fill-current outline-none"
                />
                Delete
              </span>
            </MenuItem>
            <MenuItem>
              <span
                @click="openForgetModal(props.row)"
                class="group cursor-pointer flex items-center px-4 py-3 text-sm text-grey-700 hover:bg-grey-100 hover:text-grey-900"
                role="menuitem"
              >
                <icon
                  name="rubber"
                  class="block mr-3 w-5 h-5 text-grey-300 fill-current outline-none"
                />
                Forget
              </span>
            </MenuItem>
          </more-options>
        </span>
      </template>
    </vue-good-table>

    <div v-else class="bg-white rounded shadow overflow-x-auto">
      <div class="p-8 text-center text-lg text-grey-700">
        <h1 class="mb-6 text-2xl text-indigo-800 font-semibold">
          It doesn't look like you have any aliases yet!
        </h1>
        <div class="mx-auto mb-6 w-24 border-b-2 border-grey-200"></div>
        <p class="mb-4">There are two ways to create new aliases.</p>
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
          You can click the button above to generate a random alias that will look something like
          this:
        </p>
        <p class="mb-4">
          <b>x481n904@{{ domain }}</b>
        </p>
        <p>
          Useful if you do not wish to include your username in the email as a potential link
          between aliases.
        </p>
      </div>
    </div>

    <Modal :open="generateAliasModalOpen" @close="generateAliasModalOpen = false">
      <template v-slot:title> Create new alias </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Other aliases e.g. alias@{{ subdomain }} can also be created automatically when they
          receive their first email.
        </p>
        <label for="alias_domain" class="block text-grey-700 text-sm my-2"> Alias Domain: </label>
        <div class="block relative w-full mb-4">
          <select
            v-model="generateAliasDomain"
            id="alias_domain"
            class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring"
            required
          >
            <option v-for="domainOption in domainOptions" :key="domainOption" :value="domainOption">
              {{ domainOption }}
            </option>
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

        <label for="alias_format" class="block text-grey-700 text-sm mt-4 mb-2">
          Alias Format:
        </label>
        <div class="block relative w-full mb-4">
          <select
            v-model="generateAliasFormat"
            id="alias_format"
            class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring"
            required
          >
            <option
              v-for="formatOption in aliasFormatOptions"
              :key="formatOption.value"
              :value="formatOption.value"
            >
              {{ formatOption.label }}
            </option>
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

        <div v-if="generateAliasFormat === 'custom'">
          <label for="alias_local_part" class="block text-grey-700 text-sm my-2">
            Alias Local Part:
          </label>
          <p v-show="errors.generateAliasLocalPart" class="mb-3 text-red-500 text-sm">
            {{ errors.generateAliasLocalPart }}
          </p>
          <input
            v-model="generateAliasLocalPart"
            id="alias_local_part"
            type="text"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3"
            :class="errors.generateAliasLocalPart ? 'border-red-500' : ''"
            placeholder="Enter local part..."
            autofocus
          />
        </div>

        <label for="alias_description" class="block text-grey-700 text-sm my-2">
          Description:
        </label>
        <p v-show="errors.generateAliasDescription" class="mb-3 text-red-500 text-sm">
          {{ errors.generateAliasDescription }}
        </p>
        <input
          v-model="generateAliasDescription"
          id="alias_description"
          type="text"
          class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3"
          :class="errors.generateAliasDescription ? 'border-red-500' : ''"
          placeholder="Enter description (optional)..."
          autofocus
        />

        <label for="alias_recipient_ids" class="block text-grey-700 text-sm my-2">
          Recipients:
        </label>
        <p v-show="errors.generateAliasRecipientIds" class="mb-3 text-red-500 text-sm">
          {{ errors.generateAliasRecipientIds }}
        </p>
        <Multiselect
          id="alias_recipient_ids"
          v-model="generateAliasRecipientIds"
          mode="tags"
          value-prop="id"
          :options="recipientOptions"
          :multiple="true"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="true"
          :max="10"
          placeholder="Select recipient(s) (optional)..."
          label="email"
          track-by="email"
        >
        </Multiselect>

        <div class="mt-6">
          <button
            @click="generateNewAlias"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="generateAliasLoading ? 'cursor-not-allowed' : ''"
            :disabled="generateAliasLoading"
          >
            Create Alias
            <loader v-if="generateAliasLoading" />
          </button>
          <button
            @click="generateAliasModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
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
        <Multiselect
          v-model="aliasRecipientsToEdit"
          mode="tags"
          value-prop="id"
          :options="recipientOptions"
          :multiple="true"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="true"
          :max="10"
          placeholder="Select recipient(s)"
          label="email"
          track-by="email"
        >
        </Multiselect>
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
      </template>
    </Modal>

    <Modal :open="restoreAliasModalOpen" @close="closeRestoreModal">
      <template v-slot:title> Restore alias </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to restore this alias? Once restored it will be
          <b>able to receive emails again</b>.
        </p>
        <div class="mt-6">
          <button
            type="button"
            @click="restoreAlias(aliasIdToRestore)"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none"
            :class="restoreAliasLoading ? 'cursor-not-allowed' : ''"
            :disabled="restoreAliasLoading"
          >
            Restore alias
            <loader v-if="restoreAliasLoading" />
          </button>
          <button
            @click="closeRestoreModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
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
        <div class="mt-6">
          <button
            type="button"
            @click="deleteAlias(aliasToDelete.id)"
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
        <div class="mt-6">
          <button
            type="button"
            @click="forgetAlias(aliasToForget.id)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="forgetAliasLoading ? 'cursor-not-allowed' : ''"
            :disabled="forgetAliasLoading"
          >
            Forget alias
            <loader v-if="forgetAliasLoading" />
          </button>
          <button
            @click="closeForgetModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
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
        <label for="send_from_alias" class="block text-grey-700 text-sm my-2"> Alias: </label>
        <input
          v-model="aliasToSendFrom.email"
          id="send_from_alias"
          type="text"
          class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3"
          disabled
        />
        <label for="send_from_alias_destination" class="block text-grey-700 text-sm my-2">
          Email destination:
        </label>
        <p v-show="errors.sendFromAliasDestination" class="mb-3 text-red-500 text-sm">
          {{ errors.sendFromAliasDestination }}
        </p>
        <input
          v-model="sendFromAliasDestination"
          id="send_from_alias_destination"
          type="text"
          class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3"
          :class="errors.sendFromAliasDestination ? 'border-red-500' : ''"
          placeholder="Enter email..."
          autofocus
        />
        <div v-if="sendFromAliasEmailToSendTo">
          <p for="alias_domain" class="block text-grey-700 text-sm my-2">
            Send your message to this email:
          </p>
          <div
            v-clipboard="() => sendFromAliasEmailToSendTo"
            v-clipboard:success="setSendFromAliasCopied"
            class="flex items-center justify-between cursor-pointer text-xs border-t-4 rounded-sm text-green-800 border-green-600 bg-green-100 p-2 mb-3"
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
        <div class="mt-6">
          <button
            type="button"
            @click="displaySendFromAddress(aliasToSendFrom)"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus:outline-none"
            :class="sendFromAliasLoading ? 'cursor-not-allowed' : ''"
            :disabled="sendFromAliasLoading"
          >
            Show address
            <loader v-if="sendFromAliasLoading" />
          </button>
          <button
            @click="closeSendFromModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script>
import Modal from './../components/Modal.vue'
import Toggle from './../components/Toggle.vue'
import MoreOptions from './../components/MoreOptions.vue'
import { roundArrow } from 'tippy.js'
import 'tippy.js/dist/svg-arrow.css'
import 'tippy.js/dist/tippy.css'
import tippy from 'tippy.js'
import Multiselect from '@vueform/multiselect'
import { MenuItem } from '@headlessui/vue'

export default {
  props: {
    defaultRecipientEmail: {
      type: String,
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
  },
  components: {
    Modal,
    Toggle,
    Multiselect,
    MoreOptions,
    MenuItem,
  },
  data() {
    return {
      search: '',
      showAliases: 'without',
      aliasIdToEdit: '',
      aliasDescriptionToEdit: '',
      aliasToDelete: {},
      aliasToForget: {},
      aliasToSendFrom: {},
      sendFromAliasDestination: '',
      sendFromAliasEmailToSendTo: '',
      sendFromAliasCopied: false,
      aliasIdToRestore: '',
      deleteAliasLoading: false,
      forgetAliasLoading: false,
      deleteAliasModalOpen: false,
      forgetAliasModalOpen: false,
      sendFromAliasLoading: false,
      sendFromAliasModalOpen: false,
      restoreAliasLoading: false,
      restoreAliasModalOpen: false,
      editAliasRecipientsLoading: false,
      editAliasRecipientsModalOpen: false,
      generateAliasModalOpen: false,
      generateAliasLoading: false,
      generateAliasDomain: this.defaultAliasDomain ? this.defaultAliasDomain : this.domain,
      generateAliasLocalPart: '',
      generateAliasDescription: '',
      generateAliasRecipientIds: [],
      generateAliasFormat: this.defaultAliasFormat ? this.defaultAliasFormat : 'random_characters',
      aliasFormatOptions: [
        {
          value: 'random_characters',
          label: 'Random Characters',
        },
        {
          value: 'uuid',
          label: 'UUID',
        },
        {
          value: 'random_words',
          label: 'Random Words',
        },
        {
          value: 'custom',
          label: 'Custom',
        },
      ],
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
          label: 'Replies/Sent',
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
      tippyInstance: null,
      errors: {},
    }
  },
  watch: {
    showAliases() {
      this.updateAliases()
    },
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
    recipientsTooltip(recipients) {
      return _.reduce(recipients, (list, recipient) => list + `${recipient.email}<br>`, '')
    },
    openDeleteModal(alias) {
      this.deleteAliasModalOpen = true
      this.aliasToDelete = alias
    },
    closeDeleteModal() {
      this.deleteAliasModalOpen = false
      this.aliasToDelete = {}
    },
    openForgetModal(alias) {
      this.forgetAliasModalOpen = true
      this.aliasToForget = alias
    },
    closeForgetModal() {
      this.forgetAliasModalOpen = false
      this.aliasToForget = {}
    },
    openSendFromModal(alias) {
      this.sendFromAliasDestination = ''
      this.sendFromAliasEmailToSendTo = ''
      this.sendFromAliasCopied = false
      this.sendFromAliasModalOpen = true
      this.aliasToSendFrom = alias
    },
    closeSendFromModal() {
      this.sendFromAliasModalOpen = false
      this.aliasToSendFrom = {}
    },
    openRestoreModal(id) {
      this.restoreAliasModalOpen = true
      this.aliasIdToRestore = id
    },
    closeRestoreModal() {
      this.restoreAliasModalOpen = false
      this.aliasIdToRestore = ''
    },
    updateAliases() {
      axios
        .get(`/api/v1/aliases?deleted=${this.showAliases}`, {
          headers: { 'Content-Type': 'application/json' },
        })
        .then(response => {
          this.rows = response.data.data
        })
        .catch(error => {
          this.error()
        })
    },
    deleteAlias(id) {
      this.deleteAliasLoading = true

      axios
        .delete(`/api/v1/aliases/${id}`)
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
    forgetAlias(id) {
      this.forgetAliasLoading = true

      axios
        .delete(`/api/v1/aliases/${id}/forget`)
        .then(response => {
          this.rows = _.reject(this.rows, alias => alias.id === id)
          this.forgetAliasModalOpen = false
          this.forgetAliasLoading = false
        })
        .catch(error => {
          this.error()
          this.forgetAliasModalOpen = false
          this.forgetAliasLoading = false
        })
    },
    restoreAlias(id) {
      this.restoreAliasLoading = true

      axios
        .patch(`/api/v1/aliases/${id}/restore`, {
          headers: { 'Content-Type': 'application/json' },
        })
        .then(response => {
          this.updateAliases()
          this.restoreAliasModalOpen = false
          this.restoreAliasLoading = false
          this.success('Alias restored successfully')
        })
        .catch(error => {
          this.error()
          this.restoreAliasModalOpen = false
          this.restoreAliasLoading = false
        })
    },
    openAliasRecipientsModal(alias) {
      this.editAliasRecipientsModalOpen = true
      this.recipientsAliasToEdit = alias
      this.aliasRecipientsToEdit = _.map(alias.recipients, recipient => recipient.id)
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
          '/api/v1/alias-recipients',
          JSON.stringify({
            alias_id: this.recipientsAliasToEdit.id,
            recipient_ids: this.aliasRecipientsToEdit,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          let alias = _.find(this.rows, ['id', this.recipientsAliasToEdit.id])
          alias.recipients = _.filter(this.recipientOptions, recipient =>
            this.aliasRecipientsToEdit.includes(recipient.id)
          )

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
      this.errors = {}

      // Validate alias local part
      if (
        this.generateAliasFormat === 'custom' &&
        !this.validLocalPart(this.generateAliasLocalPart)
      ) {
        return (this.errors.generateAliasLocalPart = 'Valid local part required')
      }

      if (this.generateAliasDescription.length > 200) {
        return (this.errors.generateAliasDescription = 'Description cannot exceed 200 characters')
      }

      this.generateAliasLoading = true

      axios
        .post(
          '/api/v1/aliases',
          JSON.stringify({
            domain: this.generateAliasDomain,
            local_part: this.generateAliasLocalPart,
            description: this.generateAliasDescription,
            format: this.generateAliasFormat,
            recipient_ids: this.generateAliasRecipientIds,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.generateAliasLoading = false
          this.generateAliasLocalPart = ''
          this.generateAliasDescription = ''
          this.generateAliasRecipientIds = []
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
      if (this.aliasDescriptionToEdit.length > 200) {
        return this.error('Description cannot be more than 200 characters')
      }

      axios
        .patch(
          `/api/v1/aliases/${alias.id}`,
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
          `/api/v1/active-aliases`,
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
          alias.active = false
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    deactivateAlias(alias) {
      axios
        .delete(`/api/v1/active-aliases/${alias.id}`)
        .then(response => {
          //
        })
        .catch(error => {
          alias.active = true
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    displaySendFromAddress(alias) {
      this.errors = {}

      if (!this.validEmail(this.sendFromAliasDestination)) {
        this.errors.sendFromAliasDestination = 'Valid Email required'
        return
      }

      this.sendFromAliasEmailToSendTo = `${
        alias.local_part
      }+${this.sendFromAliasDestination.replace('@', '=')}@${alias.domain}`
    },
    setSendFromAliasCopied() {
      this.sendFromAliasCopied = true
    },
    getAliasEmail(alias) {
      return alias.extension
        ? `${alias.local_part}+${alias.extension}@${alias.domain}`
        : alias.email
    },
    getAliasLocalPart(alias) {
      return alias.extension ? `${alias.local_part}+${alias.extension}` : alias.local_part
    },
    getAliasStatus(alias) {
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
    },
    sortRecipients(x, y) {
      return x.length < y.length ? -1 : x.length > y.length ? 1 : 0
    },
    has(object, path) {
      return _.has(object, path)
    },
    validLocalPart(part) {
      let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))$/
      return re.test(part)
    },
    validEmail(email) {
      let re =
        /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
      return re.test(email)
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
