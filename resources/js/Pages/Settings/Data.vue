<template>
  <SettingsLayout>
    <div class="divide-y divide-grey-200">
      <div class="py-10">
        <div>
          <div class="mb-6 text-base text-grey-700 dark:text-grey-200">
            <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
              Import Aliases
            </h3>

            <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

            <p class="mt-6">
              You can import aliases for <b>your custom domains</b> by uploading a CSV file. Please
              note this is <b>only available for custom domains</b>.
            </p>

            <p class="mt-4">Aliases that <b>already exist</b> will not be imported.</p>
            <p class="mt-4">
              The import is <b>limited to 1,000 rows (aliases)</b>. Please ensure you use multiple
              CSV files if you need to import more than this.
            </p>
            <p class="mt-4">
              Please use the template file provided below. Only CSV files are supported.
            </p>
            <p class="mt-4">
              The import will take a few minutes. You will <b>receive an email</b> once it is
              complete.
            </p>
            <p class="mt-4">
              <a
                href="/import-aliases-template.csv"
                rel="nofollow noopener noreferrer"
                class="text-indigo-700 cursor-pointer dark:text-indigo-400"
                >Click here to download the CSV import template</a
              >
            </p>
          </div>

          <form
            @submit.prevent="
              importAliasesForm.post(route('aliases.import'), {
                preserveScroll: true,
                onSuccess: () => clearForm(),
              })
            "
          >
            <div class="row">
              <input
                type="file"
                id="aliases-import"
                @input="importAliasesForm.aliases_import = $event.target.files[0]"
                required
                :disabled="!domainsCount ? 'disabled' : undefined"
              />

              <p v-if="importAliasesForm.errors.aliases_import" class="mt-2 text-sm text-red-600">
                {{ importAliasesForm.errors.aliases_import }}
              </p>

              <div class="mt-4">
                <div
                  v-if="!domainsCount"
                  class="bg-cyan-400 block w-full text-center hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 cursor-not-allowed"
                >
                  You don't have any custom domains
                </div>
                <button
                  v-else
                  type="submit"
                  class="bg-cyan-400 block w-full text-center hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                  Import Alias Data
                </button>
              </div>
            </div>
          </form>

          <div class="my-6">
            <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
              Export Aliases
            </h3>

            <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

            <p v-if="totalAliasesCount" class="mt-6 text-base text-grey-700 dark:text-grey-200">
              You can click the button below to export all the data for your
              <b>{{ totalAliasesCount }}</b> aliases as a .csv file.
            </p>
            <p v-else class="mt-6 text-base text-grey-700 dark:text-grey-200">
              You don't have any aliases to export.
            </p>
          </div>

          <p
            v-if="$page.props.errors.aliases_export"
            class="mt-2 text-sm text-red-600"
            id="enable-two-factor-error"
          >
            {{ $page.props.errors.aliases_export }}
          </p>
          <a
            v-if="totalAliasesCount"
            :href="route('aliases.export')"
            :class="!totalAliasesCount ? 'cursor-not-allowed' : ''"
            :disabled="!totalAliasesCount"
            class="bg-cyan-400 block w-full text-center hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Export Alias Data
          </a>
          <div
            v-else
            class="bg-cyan-400 block w-full text-center hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 cursor-not-allowed"
          >
            Export Alias Data
          </div>
        </div>
      </div>
    </div>
  </SettingsLayout>
</template>

<script setup>
import SettingsLayout from './../../Layouts/SettingsLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  totalAliasesCount: {
    type: Number,
    required: true,
  },
  domainsCount: {
    type: Number,
    required: true,
  },
})

const importAliasesForm = useForm({
  aliases_import: '',
})

const clearForm = () => {
  importAliasesForm.reset()
  document.getElementById('aliases-import').value = ''
}
</script>
