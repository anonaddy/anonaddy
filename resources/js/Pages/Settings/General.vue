<template>
  <SettingsLayout>
    <div class="divide-y divide-grey-200">
      <div class="py-10" v-if="!$page.props.usesExternalAuthentication">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">Update Email</h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            This is your account's default recipient email address, it is used for all general email
            notifications. You'll need to verify the new email address by clicking the link in the
            email notification before it is updated.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              emailForm.post(route('settings.edit_default_recipient'), {
                preserveScroll: true,
                onSuccess: () => emailForm.reset(),
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <div class="mb-4">
                  <label
                    for="current_email"
                    class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                    >Current Email</label
                  >
                  <div class="relative mt-2">
                    <input
                      type="email"
                      name="current_email"
                      id="current_email"
                      :value="$page.props.user.email"
                      disabled=""
                      class="block w-full rounded-md border-0 py-1.5 text-grey-900 shadow-sm ring-1 ring-grey-300 placeholder:text-grey-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:cursor-not-allowed disabled:bg-grey-50 disabled:dark:bg-grey-900 disabled:text-grey-500 disabled:ring-grey-200 sm:text-sm sm:leading-6"
                    />
                  </div>
                </div>

                <div class="mb-4">
                  <label
                    for="email"
                    class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                    >New Email</label
                  >
                  <div class="relative mt-2">
                    <input
                      v-model="emailForm.email"
                      type="email"
                      name="email"
                      id="email"
                      required
                      autocomplete="email"
                      class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:bg-white/5"
                      :class="
                        emailForm.errors.email
                          ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                          : 'text-grey-900 dark:text-white ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                      "
                      placeholder="johndoe@example.com"
                      :aria-invalid="emailForm.errors.email ? 'true' : undefined"
                      :aria-describedby="emailForm.errors.email ? 'email-error' : undefined"
                    />
                    <div
                      v-if="emailForm.errors.email"
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                  </div>
                  <p
                    v-if="emailForm.errors.email"
                    class="mt-2 text-sm text-red-600"
                    id="email-error"
                  >
                    {{ emailForm.errors.email }}
                  </p>
                </div>

                <div>
                  <label
                    for="current"
                    class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                    >Current Password</label
                  >
                  <div class="relative mt-2">
                    <input
                      v-model="emailForm.current"
                      type="password"
                      name="current"
                      id="current"
                      required
                      class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:bg-white/5"
                      :class="
                        emailForm.errors.current
                          ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                          : 'text-grey-900 dark:text-white ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                      "
                      placeholder="********"
                      :aria-invalid="emailForm.errors.current ? 'true' : undefined"
                      :aria-describedby="
                        emailForm.errors.current ? 'current-password-error' : undefined
                      "
                    />
                    <div
                      v-if="emailForm.errors.current"
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                  </div>
                  <p
                    v-if="emailForm.errors.current"
                    class="mt-2 text-sm text-red-600"
                    id="current-password-error"
                  >
                    {{ emailForm.errors.current }}
                  </p>
                </div>
              </div>
            </div>

            <button
              type="submit"
              :disabled="emailForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Email
              <loader v-if="emailForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">Theme</h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            Choose your preferred theme for the web application.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              themeForm.post(route('settings.dark_mode'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="theme"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Theme</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="theme"
                    v-model="themeForm.theme"
                    name="theme"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      themeForm.errors.theme
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="themeForm.errors.theme ? 'true' : undefined"
                    :aria-describedby="themeForm.errors.theme ? 'theme-error' : undefined"
                  >
                    <option value="system" class="dark:bg-grey-900">System</option>
                    <option value="light" class="dark:bg-grey-900">Light</option>
                    <option value="dark" class="dark:bg-grey-900">Dark</option>
                  </select>
                  <div
                    v-if="themeForm.errors.theme"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p v-if="themeForm.errors.theme" class="mt-2 text-sm text-red-600" id="theme-error">
                  {{ themeForm.errors.theme }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="themeForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Theme
              <loader v-if="themeForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Update Default Alias Domain
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            The default alias domain is the domain you'd like to be selected by default in the drop
            down options when generating a new alias on the site or the browser extension. This will
            save you needing to select your preferred domain from the dropdown each time.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              defaultAliasDomainForm.post(route('settings.default_alias_domain'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="default-alias-domain"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Select Default Domain</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="default-alias-domain"
                    v-model="defaultAliasDomainForm.domain"
                    name="domain"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      defaultAliasDomainForm.errors.domain
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="defaultAliasDomainForm.errors.domain ? 'true' : undefined"
                    :aria-describedby="
                      defaultAliasDomainForm.errors.domain
                        ? 'default-alias-domain-error'
                        : undefined
                    "
                  >
                    <option
                      v-for="domain in domainOptions"
                      v-bind:key="domain"
                      :selected="defaultAliasDomain === domain ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      {{ domain }}
                    </option>
                  </select>
                  <div
                    v-if="defaultAliasDomainForm.errors.domain"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="defaultAliasDomainForm.errors.domain"
                  class="mt-2 text-sm text-red-600"
                  id="default-alias-domain-error"
                >
                  {{ defaultAliasDomainForm.errors.domain }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="defaultAliasDomainForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Default Alias Domain
              <loader v-if="defaultAliasDomainForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Alias Domain Picker
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            Choose the domains that appear when you create an alias. The website, the browser
            extension, and the mobile apps use this list. A hidden domain stays on your account, and
            you can still receive email for aliases on that domain. It just makes your list of
            domains shorter and easier to manage.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              hiddenAliasDomainsForm.post(route('settings.hidden_alias_domains'), {
                preserveScroll: true,
              })
            "
          >
            <p
              v-if="aliasDomainPickerError"
              class="mb-4 text-sm text-red-600"
              id="hidden-alias-domains-error"
            >
              {{ aliasDomainPickerError }}
            </p>
            <div
              class="mb-6 divide-y divide-grey-200 border-y border-grey-200 dark:divide-white/10 dark:border-white/10"
            >
              <div v-for="group in aliasDomainPickerGroups" :key="group.key" class="py-3">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                  <button
                    type="button"
                    class="flex items-center gap-2 text-left"
                    :aria-expanded="aliasDomainGroupIsOpen(group.key)"
                    :aria-controls="`alias-domain-group-${group.key}`"
                    @click="toggleAliasDomainGroup(group.key)"
                  >
                    <ChevronDownIcon
                      class="h-4 w-4 text-grey-500 transition dark:text-grey-300"
                      :class="aliasDomainGroupIsOpen(group.key) ? '' : '-rotate-90'"
                      aria-hidden="true"
                    />
                    <span class="text-sm font-medium leading-6 text-grey-900 dark:text-white">
                      {{ group.label }}
                    </span>
                    <span class="text-sm text-grey-500 dark:text-grey-300">
                      {{ shownAliasDomainCount(group.domains) }} of {{ group.domains.length }} shown
                    </span>
                  </button>
                  <div class="flex gap-3 text-sm">
                    <button
                      type="button"
                      class="font-medium text-indigo-700 hover:text-indigo-500 dark:text-indigo-200"
                      @click="showAliasDomainGroup(group.domains)"
                    >
                      Show all
                    </button>
                    <button
                      type="button"
                      class="font-medium text-indigo-700 hover:text-indigo-500 dark:text-indigo-200"
                      @click="hideAliasDomainGroup(group.domains)"
                    >
                      Hide all
                    </button>
                  </div>
                </div>
                <div
                  v-if="aliasDomainGroupIsOpen(group.key)"
                  :id="`alias-domain-group-${group.key}`"
                  class="mt-2 max-h-48 overflow-y-auto pl-6"
                >
                  <template v-if="group.key === 'username'">
                    <div
                      v-for="section in usernameAliasDomainSections(group.domains)"
                      :key="section.username"
                      class="mb-3"
                    >
                      <label
                        class="flex items-center gap-3 text-sm font-medium text-grey-900 dark:text-white"
                      >
                        <input
                          type="checkbox"
                          class="h-4 w-4 rounded border-grey-300 text-indigo-600 focus:ring-indigo-500 dark:bg-grey-950 dark:text-indigo-400"
                          :checked="
                            shownAliasDomainCount(section.domains) === section.domains.length
                          "
                          :ref="element => setAliasDomainSectionCheckbox(element, section.domains)"
                          @change="setAliasDomainSectionShown(section.domains, $event)"
                        />
                        {{ section.username }}
                      </label>
                      <ul class="mt-2 space-y-2 pl-7">
                        <li v-for="domain in section.domains" :key="domain">
                          <label
                            class="flex items-start gap-3 text-sm text-grey-700 dark:text-grey-200"
                          >
                            <input
                              type="checkbox"
                              class="mt-0.5 h-4 w-4 rounded border-grey-300 text-indigo-600 focus:ring-indigo-500 dark:bg-grey-950 dark:text-indigo-400 disabled:opacity-60"
                              :checked="aliasDomainIsShown(domain)"
                              :disabled="domain === defaultAliasDomain"
                              @change="setAliasDomainShown(domain, $event)"
                            />
                            <span>
                              {{ domain }}
                              <span
                                v-if="domain === defaultAliasDomain"
                                class="block text-grey-500 dark:text-grey-300"
                              >
                                Default alias domain. This domain stays in the picker.
                              </span>
                            </span>
                          </label>
                        </li>
                      </ul>
                    </div>
                  </template>
                  <ul v-else class="space-y-2">
                    <li v-for="domain in group.domains" :key="domain">
                      <label
                        class="flex items-start gap-3 text-sm text-grey-700 dark:text-grey-200"
                      >
                        <input
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 rounded border-grey-300 text-indigo-600 focus:ring-indigo-500 dark:bg-grey-950 dark:text-indigo-400 disabled:opacity-60"
                          :checked="aliasDomainIsShown(domain)"
                          :disabled="domain === defaultAliasDomain"
                          @change="setAliasDomainShown(domain, $event)"
                        />
                        <span>
                          {{ domain }}
                          <span
                            v-if="domain === defaultAliasDomain"
                            class="block text-grey-500 dark:text-grey-300"
                          >
                            Default alias domain. This domain stays in the picker.
                          </span>
                        </span>
                      </label>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <button
              type="submit"
              :disabled="hiddenAliasDomainsForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Alias Domain Picker
              <loader v-if="hiddenAliasDomainsForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Update Default Alias Format
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            The default alias format is the format you'd like to be selected by default in the drop
            down options when generating a new alias on the site or the browser extension. This will
            save you needing to select your preferred format from the dropdown each time.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              defaultAliasFormatForm.post(route('settings.default_alias_format'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="default-alias-format"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Select Default Format</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="default-alias-format"
                    v-model="defaultAliasFormatForm.format"
                    name="format"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      defaultAliasFormatForm.errors.format
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="defaultAliasFormatForm.errors.format ? 'true' : undefined"
                    :aria-describedby="
                      defaultAliasFormatForm.errors.format
                        ? 'default-alias-format-error'
                        : undefined
                    "
                  >
                    <option
                      value="random_characters"
                      :selected="defaultAliasFormat === 'random_characters' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Random Characters
                    </option>
                    <option
                      value="uuid"
                      :selected="defaultAliasFormat === 'uuid' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      UUID
                    </option>
                    <option
                      value="random_words"
                      :selected="defaultAliasFormat === 'random_words' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Random Words
                    </option>
                    <option
                      value="random_male_name"
                      :selected="defaultAliasFormat === 'random_male_name' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Random Male Name
                    </option>
                    <option
                      value="random_female_name"
                      :selected="defaultAliasFormat === 'random_female_name' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Random Female Name
                    </option>
                    <option
                      value="random_noun"
                      :selected="defaultAliasFormat === 'random_noun' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Random Noun
                    </option>
                    <option
                      value="custom"
                      :selected="defaultAliasFormat === 'custom' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Custom
                    </option>
                  </select>
                  <div
                    v-if="defaultAliasFormatForm.errors.format"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="defaultAliasFormatForm.errors.format"
                  class="mt-2 text-sm text-red-600"
                  id="default-alias-format-error"
                >
                  {{ defaultAliasFormatForm.errors.format }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="defaultAliasFormatForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Default Alias Format
              <loader v-if="defaultAliasFormatForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Alias Separator
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            The character used between words in aliases with the following formats: Random Words,
            Random Male/Female Name, Random Noun and Custom Shared Domain. For example, with period:
            <code class="rounded bg-grey-100 dark:bg-grey-800 px-1"
              >word.word123@{{ defaultAliasDomain }}</code
            >; with underscore:
            <code class="rounded bg-grey-100 dark:bg-grey-800 px-1"
              >word_word123@{{ defaultAliasDomain }}</code
            >; with hyphen:
            <code class="rounded bg-grey-100 dark:bg-grey-800 px-1"
              >word-word123@{{ defaultAliasDomain }}</code
            >.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              aliasSeparatorForm.post(route('settings.alias_separator'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="alias-separator"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Select Separator</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="alias-separator"
                    v-model="aliasSeparatorForm.separator"
                    name="separator"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      aliasSeparatorForm.errors.separator
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="aliasSeparatorForm.errors.separator ? 'true' : undefined"
                    :aria-describedby="
                      aliasSeparatorForm.errors.separator ? 'alias-separator-error' : undefined
                    "
                  >
                    <option value="." class="dark:bg-grey-900">Period (.)</option>
                    <option value="_" class="dark:bg-grey-900">Underscore (_)</option>
                    <option value="-" class="dark:bg-grey-900">Hyphen (-)</option>
                    <option value="random" class="dark:bg-grey-900">
                      Random (varies per alias)
                    </option>
                  </select>
                  <div
                    v-if="aliasSeparatorForm.errors.separator"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="aliasSeparatorForm.errors.separator"
                  class="mt-2 text-sm text-red-600"
                  id="alias-separator-error"
                >
                  {{ aliasSeparatorForm.errors.separator }}
                </p>
              </div>
            </div>
            <button
              type="submit"
              :disabled="aliasSeparatorForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Alias Separator
              <loader v-if="aliasSeparatorForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Update Page to Display After Login
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            The login redirect determines which page you should be redirected to after logging in to
            your account. If you select "Aliases" then you will be shown the aliases page after you
            login to your account.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              loginRedirectForm.post(route('settings.login_redirect'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="login-redirect"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Select Login Redirect</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="login-redirect"
                    v-model="loginRedirectForm.redirect"
                    name="redirect"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      loginRedirectForm.errors.redirect
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="loginRedirectForm.errors.redirect ? 'true' : undefined"
                    :aria-describedby="
                      loginRedirectForm.errors.redirect ? 'login-redirect-error' : undefined
                    "
                  >
                    <option
                      v-for="redirect in loginRedirectOptions"
                      :key="redirect.value"
                      :value="redirect.value"
                      class="dark:bg-grey-900"
                    >
                      {{ redirect.label }}
                    </option>
                  </select>
                  <div
                    v-if="loginRedirectForm.errors.redirect"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="loginRedirectForm.errors.redirect"
                  class="mt-2 text-sm text-red-600"
                  id="login-redirect-error"
                >
                  {{ loginRedirectForm.errors.redirect }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="loginRedirectForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Login Redirect
              <loader v-if="loginRedirectForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Update Display From Format
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            The display from format is used when forwarding message to you. If one of your aliases
            receives an email from <b>John Doe &lt;johndoe@example.com&gt;</b> then you can choose
            how this will be displayed in your inbox.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              displayFromFormatForm.post(route('settings.display_from_format'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="display-from-format"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Select Display From Format</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="display-from-format"
                    v-model="displayFromFormatForm.format"
                    name="format"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      displayFromFormatForm.errors.format
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="displayFromFormatForm.errors.format ? 'true' : undefined"
                    :aria-describedby="
                      displayFromFormatForm.errors.format ? 'display-from-format-error' : undefined
                    "
                  >
                    <option
                      v-for="format in displayFromFormatOptions"
                      :key="format.value"
                      :value="format.value"
                      class="dark:bg-grey-900"
                    >
                      {{ format.label }}
                    </option>
                  </select>
                  <div
                    v-if="displayFromFormatForm.errors.format"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="displayFromFormatForm.errors.format"
                  class="mt-2 text-sm text-red-600"
                  id="display-from-format-error"
                >
                  {{ displayFromFormatForm.errors.format }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="displayFromFormatForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Display From Format
              <loader v-if="displayFromFormatForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Use Reply-To Header For Replying
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            This will determine if forwarded emails use the From header or the Reply-To header for
            sending replies. Some users may find it easier to set up inbox filters having the From:
            header set as just the alias.
          </p>
          <p class="text-base text-grey-700 dark:text-grey-200">
            If enabled, then the <b>From:</b> header will be set as the alias email e.g.
            <b>alias{{ '@' + $page.props.user.username + '.' + defaultAliasDomain }}</b> instead of
            the default
            <b class="break-words"
              >alias+sender=example.com{{
                '@' + $page.props.user.username + '.' + defaultAliasDomain
              }}</b
            >
            (this will be set as the Reply-To header instead)
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              useReplyToForm.post(route('settings.use_reply_to'), { preserveScroll: true })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="use-reply-to"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Use Reply-To</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="use-reply-to"
                    v-model="useReplyToForm.use_reply_to"
                    name="format"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      useReplyToForm.errors.use_reply_to
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="useReplyToForm.errors.use_reply_to ? 'true' : undefined"
                    :aria-describedby="
                      useReplyToForm.errors.use_reply_to ? 'use-reply-to-error' : undefined
                    "
                  >
                    <option
                      :value="true"
                      :selected="useReplyTo ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Enabled
                    </option>
                    <option
                      :value="false"
                      :selected="!useReplyTo ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Disabled
                    </option>
                  </select>
                  <div
                    v-if="useReplyToForm.errors.use_reply_to"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="useReplyToForm.errors.use_reply_to"
                  class="mt-2 text-sm text-red-600"
                  id="use-reply-to-error"
                >
                  {{ useReplyToForm.errors.use_reply_to }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="useReplyToForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Use Reply-To
              <loader v-if="useReplyToForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Store Failed Deliveries
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            This setting allows you to choose whether or not this instance should
            <b>temporarily store</b> failed delivery attempts, this ensures that
            <b>emails are not lost</b> if they are rejected by your recipients as they can be
            downloaded from the failed deliveries page. Failed deliveries are
            <b>automatically deleted after 7 days</b>.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              storeFailedDeliveriesForm.post(route('settings.store_failed_deliveries'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="store-failed-deliveries"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Store Failed Deliveries</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="store-failed-deliveries"
                    v-model="storeFailedDeliveriesForm.store_failed_deliveries"
                    name="format"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      storeFailedDeliveriesForm.errors.store_failed_deliveries
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="
                      storeFailedDeliveriesForm.errors.store_failed_deliveries ? 'true' : undefined
                    "
                    :aria-describedby="
                      storeFailedDeliveriesForm.errors.store_failed_deliveries
                        ? 'store-failed-deliveries-error'
                        : undefined
                    "
                  >
                    <option
                      :value="true"
                      :selected="storeFailedDeliveries ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Enabled
                    </option>
                    <option
                      :value="false"
                      :selected="!storeFailedDeliveries ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Disabled
                    </option>
                  </select>
                  <div
                    v-if="storeFailedDeliveriesForm.errors.store_failed_deliveries"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="storeFailedDeliveriesForm.errors.store_failed_deliveries"
                  class="mt-2 text-sm text-red-600"
                  id="store-failed-deliveries-error"
                >
                  {{ storeFailedDeliveriesForm.errors.store_failed_deliveries }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="storeFailedDeliveriesForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Store Failed Deliveries
              <loader v-if="storeFailedDeliveriesForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Show Intentional Failed Deliveries
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            Choose whether failed deliveries appear when you have already stopped the email. This
            includes a deactivated alias, username, or custom domain, a deleted alias, and a sender
            on your blocklist. This instance still rejects these emails. This setting only changes
            the Failed Deliveries page, the API list, and the dashboard chart.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              showIntentionalFailedDeliveriesForm.post(
                route('settings.show_intentional_failed_deliveries'),
                {
                  preserveScroll: true,
                },
              )
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="show-intentional-failed-deliveries"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Show Intentional Failed Deliveries</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="show-intentional-failed-deliveries"
                    v-model="showIntentionalFailedDeliveriesForm.show_intentional_failed_deliveries"
                    name="show_intentional_failed_deliveries"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      showIntentionalFailedDeliveriesForm.errors.show_intentional_failed_deliveries
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="
                      showIntentionalFailedDeliveriesForm.errors.show_intentional_failed_deliveries
                        ? 'true'
                        : undefined
                    "
                    :aria-describedby="
                      showIntentionalFailedDeliveriesForm.errors.show_intentional_failed_deliveries
                        ? 'show-intentional-failed-deliveries-error'
                        : undefined
                    "
                  >
                    <option :value="true" class="dark:bg-grey-900">Shown</option>
                    <option :value="false" class="dark:bg-grey-900">Hidden</option>
                  </select>
                  <div
                    v-if="
                      showIntentionalFailedDeliveriesForm.errors.show_intentional_failed_deliveries
                    "
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="
                    showIntentionalFailedDeliveriesForm.errors.show_intentional_failed_deliveries
                  "
                  class="mt-2 text-sm text-red-600"
                  id="show-intentional-failed-deliveries-error"
                >
                  {{
                    showIntentionalFailedDeliveriesForm.errors.show_intentional_failed_deliveries
                  }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="showIntentionalFailedDeliveriesForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Show Intentional Failed Deliveries
              <loader v-if="showIntentionalFailedDeliveriesForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Failed Delivery Notifications
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            Choose which failed delivery notifications you want to receive. You can receive
            notifications for both normal failed deliveries and inbound quarantined emails, either
            one, or turn both off.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              failedDeliveryNotificationPreferenceForm.post(
                route('settings.failed_delivery_notification_preference'),
                {
                  preserveScroll: true,
                },
              )
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="failed-delivery-notification-preference"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Notification preference</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="failed-delivery-notification-preference"
                    v-model="
                      failedDeliveryNotificationPreferenceForm.failed_delivery_notification_preference
                    "
                    name="failed_delivery_notification_preference"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      failedDeliveryNotificationPreferenceForm.errors
                        .failed_delivery_notification_preference
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="
                      failedDeliveryNotificationPreferenceForm.errors
                        .failed_delivery_notification_preference
                        ? 'true'
                        : undefined
                    "
                    :aria-describedby="
                      failedDeliveryNotificationPreferenceForm.errors
                        .failed_delivery_notification_preference
                        ? 'failed-delivery-notification-preference-error'
                        : undefined
                    "
                  >
                    <option
                      v-for="option in failedDeliveryNotificationPreferenceOptions"
                      :key="option.value"
                      :value="option.value"
                      class="dark:bg-grey-900"
                    >
                      {{ option.label }}
                    </option>
                  </select>
                  <div
                    v-if="
                      failedDeliveryNotificationPreferenceForm.errors
                        .failed_delivery_notification_preference
                    "
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="
                    failedDeliveryNotificationPreferenceForm.errors
                      .failed_delivery_notification_preference
                  "
                  class="mt-2 text-sm text-red-600"
                  id="failed-delivery-notification-preference-error"
                >
                  {{
                    failedDeliveryNotificationPreferenceForm.errors
                      .failed_delivery_notification_preference
                  }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="failedDeliveryNotificationPreferenceForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update failed delivery notifications
              <loader v-if="failedDeliveryNotificationPreferenceForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Save Alias 'Last Used At'
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            This setting allows you to choose whether or not this instance should save the dates for
            <b>last forwarded at</b>, <b>last replied at</b> and <b>last sent at</b> for your
            aliases. You can view this information by hovering over the relevant count of each of
            these on the
            <Link
              :href="route('aliases.index')"
              class="text-indigo-500 hover:text-indigo-800 dark:text-indigo-200 dark:hover:text-indigo-300 font-medium"
              >aliases page</Link
            >. You can also sort your list of aliases by "Last Forwarded At" etc.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              saveAliasLastUsedForm.post(route('settings.save_alias_last_used'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="save-alias-last-used"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Save Alias Last Used At</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="save-alias-last-used"
                    v-model="saveAliasLastUsedForm.save_alias_last_used"
                    name="format"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      saveAliasLastUsedForm.errors.save_alias_last_used
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="
                      saveAliasLastUsedForm.errors.save_alias_last_used ? 'true' : undefined
                    "
                    :aria-describedby="
                      saveAliasLastUsedForm.errors.save_alias_last_used
                        ? 'save-alias-last-used-error'
                        : undefined
                    "
                  >
                    <option
                      :value="true"
                      :selected="saveAliasLastUsed ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Enabled
                    </option>
                    <option
                      :value="false"
                      :selected="!saveAliasLastUsed ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Disabled
                    </option>
                  </select>
                  <div
                    v-if="saveAliasLastUsedForm.errors.save_alias_last_used"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="saveAliasLastUsedForm.errors.save_alias_last_used"
                  class="mt-2 text-sm text-red-600"
                  id="save-alias-last-used-error"
                >
                  {{ saveAliasLastUsedForm.errors.save_alias_last_used }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="saveAliasLastUsedForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Save Alias Last Used At
              <loader v-if="saveAliasLastUsedForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Update Global 'From Name'
          </h3>
          <div>
            <p class="text-base text-grey-700 dark:text-grey-200">
              The 'From Name' is shown when you send an email from an alias or reply anonymously to
              a forwarded email. If left blank, then the email alias itself will be used as the
              'From Name' e.g. "example@{{ $page.props.user.username }}.{{ defaultAliasDomain }}".
            </p>
            <div class="text-base text-grey-700 dark:text-grey-200 my-3">
              The 'From Name' that is used for an alias is determined by the following
              <b>priority</b>:

              <ul class="list-decimal list-inside text-grey-700 dark:text-grey-200 text-base mt-2">
                <li>Alias 'From Name'</li>
                <li>Username or Custom Domain 'From Name'</li>
                <li><b>Global 'From Name'</b> from the settings page</li>
              </ul>
            </div>
            <p class="text-base text-grey-700 dark:text-grey-200">
              If you set the 'From Name' for a specific alias, it will override the other settings.
            </p>
          </div>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              fromNameForm.post(route('settings.from_name'), { preserveScroll: true })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="from-name"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Global From Name</label
                >
                <div class="relative mt-2">
                  <input
                    v-model="fromNameForm.from_name"
                    type="text"
                    name="from_name"
                    id="from-name"
                    class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:bg-white/5 text-grey-900 dark:text-white"
                    :class="
                      fromNameForm.errors.from_name
                        ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                        : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                    "
                    placeholder="John Doe"
                    :aria-invalid="fromNameForm.errors.from_name ? 'true' : undefined"
                    :aria-describedby="
                      fromNameForm.errors.from_name ? 'from-name-error' : undefined
                    "
                  />
                  <div
                    v-if="fromNameForm.errors.from_name"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="fromNameForm.errors.from_name"
                  class="mt-2 text-sm text-red-600"
                  id="from-name-error"
                >
                  {{ fromNameForm.errors.from_name }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="fromNameForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Global From Name
              <loader v-if="fromNameForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Update Email Banner Location
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            This is the information displayed in forwarded emails letting you know who the email was
            from and which alias it was sent to. You can choose for it to be displayed at the top or
            bottom of the email or just turn if off altogether.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              bannerLocationForm.post(route('settings.banner_location'), { preserveScroll: true })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="banner-location"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Update Location</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="banner-location"
                    v-model="bannerLocationForm.banner_location"
                    name="banner_location"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      bannerLocationForm.errors.format
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="bannerLocationForm.errors.banner_location ? 'true' : undefined"
                    :aria-describedby="
                      bannerLocationForm.errors.banner_location
                        ? 'banner-location-error'
                        : undefined
                    "
                  >
                    <option
                      value="top"
                      :selected="bannerLocation === 'top' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Top
                    </option>
                    <option
                      value="bottom"
                      :selected="bannerLocation === 'bottom' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Bottom
                    </option>
                    <option
                      value="off"
                      :selected="bannerLocation === 'off' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Off
                    </option>
                  </select>
                  <div
                    v-if="bannerLocationForm.errors.banner_location"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="bannerLocationForm.errors.banner_location"
                  class="mt-2 text-sm text-red-600"
                  id="banner-location-error"
                >
                  {{ bannerLocationForm.errors.banner_location }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="bannerLocationForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Banner Location
              <loader v-if="bannerLocationForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Spam / DMARC Warning
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            When a forwarded email is flagged as spam or fails DMARC authentication, you can choose
            how you are notified: show a warning banner in the email body (default), prepend a
            warning tag to the subject line (e.g. [DMARC FAIL] or [SPAM]), or turn off the warning
            altogether.
          </p>
          <p class="text-base text-grey-700 dark:text-grey-200 mt-4!">
            <b>Please note</b> that turning the warning off completely may increase your risk of
            falling for <b>phishing or spoofed</b> emails. If unsure, always check the
            "<b>X-AnonAddy-Authentication-Results</b>" header.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              spamWarningBehaviourForm.post(route('settings.spam_warning_behaviour'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="spam-warning-behaviour"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Warning behaviour</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="spam-warning-behaviour"
                    v-model="spamWarningBehaviourForm.spam_warning_behaviour"
                    name="spam_warning_behaviour"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      spamWarningBehaviourForm.errors.spam_warning_behaviour
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="
                      spamWarningBehaviourForm.errors.spam_warning_behaviour ? 'true' : undefined
                    "
                    :aria-describedby="
                      spamWarningBehaviourForm.errors.spam_warning_behaviour
                        ? 'spam-warning-behaviour-error'
                        : undefined
                    "
                  >
                    <option
                      value="banner"
                      :selected="spamWarningBehaviour === 'banner' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Show warning banner in email
                    </option>
                    <option
                      value="subject"
                      :selected="spamWarningBehaviour === 'subject' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      Prepend [SPAM] / [DMARC FAIL] to subject
                    </option>
                    <option
                      value="off"
                      :selected="spamWarningBehaviour === 'off' ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      No warning
                    </option>
                  </select>
                  <div
                    v-if="spamWarningBehaviourForm.errors.spam_warning_behaviour"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="spamWarningBehaviourForm.errors.spam_warning_behaviour"
                  class="mt-2 text-sm text-red-600"
                  id="spam-warning-behaviour-error"
                >
                  {{ spamWarningBehaviourForm.errors.spam_warning_behaviour }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="spamWarningBehaviourForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update spam / DMARC warning
              <loader v-if="spamWarningBehaviourForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            List-Unsubscribe Behaviour
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            Control how List-Unsubscribe and List-Unsubscribe-Post headers are set on forwarded
            emails. On email clients that support it, clicking on it will perform one of these
            actions.
          </p>
          <p class="text-base text-grey-700 dark:text-grey-200 mt-4!">
            Where an original List-Unsubscribe header is present and contains a mailto: email
            address, this instance will rewrite it so that the email is sent from your alias and not
            your real email address.
          </p>
          <p class="text-base text-grey-700 dark:text-grey-200 mt-4!">
            If you use only the original List-Unsubscribe header, this instance does not add a
            header when the original email has none. The email client then shows no unsubscribe
            button.
          </p>
          <p class="text-base text-grey-700 dark:text-grey-200 mt-4!">
            One-click deactivate, delete, block sender email and block sender domain links in
            forwarded emails expire after 30 days for security.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              listUnsubscribeBehaviourForm.post(route('settings.list_unsubscribe_behaviour'), {
                preserveScroll: true,
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="list-unsubscribe-behaviour"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Behaviour</label
                >
                <div class="block relative w-full mt-2">
                  <select
                    id="list-unsubscribe-behaviour"
                    v-model="listUnsubscribeBehaviourForm.list_unsubscribe_behaviour"
                    name="list_unsubscribe_behaviour"
                    required
                    class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 dark:text-white dark:bg-white/5 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      listUnsubscribeBehaviourForm.errors.list_unsubscribe_behaviour
                        ? 'ring-red-300 focus:ring-red-500'
                        : 'ring-grey-300 focus:ring-indigo-600'
                    "
                    :aria-invalid="
                      listUnsubscribeBehaviourForm.errors.list_unsubscribe_behaviour
                        ? 'true'
                        : undefined
                    "
                    :aria-describedby="
                      listUnsubscribeBehaviourForm.errors.list_unsubscribe_behaviour
                        ? 'list-unsubscribe-behaviour-error'
                        : undefined
                    "
                  >
                    <option
                      v-for="option in listUnsubscribeBehaviourOptions"
                      :key="option.value"
                      :value="option.value"
                      :selected="listUnsubscribeBehaviour === option.value ? 'selected' : ''"
                      class="dark:bg-grey-900"
                    >
                      {{ option.label }}
                    </option>
                  </select>
                  <div
                    v-if="listUnsubscribeBehaviourForm.errors.list_unsubscribe_behaviour"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="listUnsubscribeBehaviourForm.errors.list_unsubscribe_behaviour"
                  class="mt-2 text-sm text-red-600"
                  id="list-unsubscribe-behaviour-error"
                >
                  {{ listUnsubscribeBehaviourForm.errors.list_unsubscribe_behaviour }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="listUnsubscribeBehaviourForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update List-Unsubscribe behaviour
              <loader v-if="listUnsubscribeBehaviourForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">
            Replace Email Subject
          </h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            This is useful if you are <b>using encryption</b>. After you add your public GPG/OpenPGP
            key for a recipient the body of forwarded emails will be encrypted (this includes email
            attachments). Unfortunately the email subject cannot be encrypted as it is one of the
            headers. To prevent revealing the contents of emails you can replace the subject with
            something generic below e.g. "The subject" or "Hello".
          </p>
          <p class="text-base text-grey-700 dark:text-grey-200 mt-4!">
            If set to empty then the email's original subject will be used.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              emailSubjectForm.post(route('settings.email_subject'), { preserveScroll: true })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="email-subject"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Email Subject</label
                >
                <div class="relative mt-2">
                  <input
                    v-model="emailSubjectForm.email_subject"
                    type="text"
                    name="email_subject"
                    id="email-subject"
                    class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:bg-white/5 text-grey-900 dark:text-white"
                    :class="
                      emailSubjectForm.errors.email_subject
                        ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                        : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                    "
                    placeholder="The subject"
                    :aria-invalid="emailSubjectForm.errors.email_subject ? 'true' : undefined"
                    :aria-describedby="
                      emailSubjectForm.errors.email_subject ? 'email-subject-error' : undefined
                    "
                  />
                  <div
                    v-if="emailSubjectForm.errors.email_subject"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="emailSubjectForm.errors.email_subject"
                  class="mt-2 text-sm text-red-600"
                  id="email-subject-error"
                >
                  {{ emailSubjectForm.errors.email_subject }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="emailSubjectForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Email Subject
              <loader v-if="emailSubjectForm.processing" />
            </button>
          </form>
        </div>
      </div>
    </div>
  </SettingsLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import SettingsLayout from './../../Layouts/SettingsLayout.vue'
import { ChevronDownIcon, ExclamationCircleIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  defaultAliasDomain: {
    type: String,
    required: true,
  },
  defaultAliasFormat: {
    type: String,
    required: true,
  },
  aliasSeparator: {
    type: String,
    required: true,
  },
  loginRedirect: {
    type: Number,
    required: true,
  },
  displayFromFormat: {
    type: Number,
    required: true,
  },
  domainOptions: {
    type: Array,
    required: true,
  },
  aliasDomainPickerGroups: {
    type: Array,
    required: true,
  },
  hiddenAliasDomains: {
    type: Array,
    required: true,
  },
  useReplyTo: {
    type: Boolean,
    required: true,
  },
  storeFailedDeliveries: {
    type: Boolean,
    required: true,
  },
  showIntentionalFailedDeliveries: {
    type: Boolean,
    required: true,
  },
  failedDeliveryNotificationPreference: {
    type: Number,
    required: true,
  },
  theme: {
    type: String,
    required: true,
  },
  saveAliasLastUsed: {
    type: Boolean,
    required: true,
  },
  fromName: {
    type: String,
    required: true,
  },
  bannerLocation: {
    type: String,
    required: true,
  },
  spamWarningBehaviour: {
    type: String,
    required: true,
  },
  listUnsubscribeBehaviour: {
    type: Number,
    required: true,
  },
  emailSubject: {
    type: String,
    required: true,
  },
})

const displayFromFormatOptions = [
  {
    value: 0,
    label: "John Doe 'johndoe at example.com'",
  },
  {
    value: 7,
    label: "John Doe 'johndoe@example.com'",
  },
  {
    value: 1,
    label: 'John Doe - johndoe(a)example.com',
  },
  {
    value: 2,
    label: 'John Doe - example.com',
  },
  {
    value: 3,
    label: 'John Doe',
  },
  {
    value: 4,
    label: 'johndoe at example.com',
  },
  {
    value: 6,
    label: 'example.com',
  },
  {
    value: 5,
    label: 'No name - just the alias',
  },
]

const loginRedirectOptions = [
  {
    value: 0,
    label: 'Dashboard',
  },
  {
    value: 1,
    label: 'Aliases',
  },
  {
    value: 2,
    label: 'Recipients',
  },
  {
    value: 3,
    label: 'Usernames',
  },
  {
    value: 4,
    label: 'Domains',
  },
]

const listUnsubscribeBehaviourOptions = [
  {
    value: 0,
    label: 'Use original List-Unsubscribe, fallback to one-click deactivate if none is present',
  },
  {
    value: 5,
    label: 'Use original List-Unsubscribe, do not add a header if none is present',
  },
  {
    value: 1,
    label: 'Always use one-click deactivate',
  },
  { value: 2, label: 'Always use one-click delete' },
  {
    value: 3,
    label: 'Always use one-click block sender email',
  },
  {
    value: 4,
    label: 'Always use one-click block sender domain',
  },
]

const failedDeliveryNotificationPreferenceOptions = [
  {
    value: 0,
    label: 'All failed delivery notifications',
  },
  {
    value: 1,
    label: 'Normal failed deliveries only',
  },
  {
    value: 2,
    label: 'Quarantined emails only',
  },
  {
    value: 3,
    label: 'None',
  },
]

const emailForm = useForm({
  email: '',
  current: '',
})

const defaultAliasDomainForm = useForm({
  domain: props.defaultAliasDomain,
})

const aliasPickerDomains = props.aliasDomainPickerGroups.flatMap(group => group.domains)

const hiddenAliasDomainsForm = useForm({
  hidden_domains: props.hiddenAliasDomains.filter(domain => domain !== props.defaultAliasDomain),
})

const localAliasDomainPickerError = ref('')

const openAliasDomainGroups = ref({})

const aliasDomainGroupIsOpen = key => Boolean(openAliasDomainGroups.value[key])

const toggleAliasDomainGroup = key => {
  openAliasDomainGroups.value[key] = !openAliasDomainGroups.value[key]
}

const usernameAliasDomainSections = domains => {
  const sections = new Map()

  domains.forEach(domain => {
    const username = domain.slice(0, domain.indexOf('.'))

    if (!sections.has(username)) {
      sections.set(username, [])
    }

    sections.get(username).push(domain)
  })

  return [...sections.entries()].map(([username, sectionDomains]) => ({
    username,
    domains: sectionDomains,
  }))
}

const aliasDomainPickerError = computed(
  () =>
    hiddenAliasDomainsForm.errors.hidden_domains ||
    Object.entries(hiddenAliasDomainsForm.errors).find(([key]) =>
      key.startsWith('hidden_domains'),
    )?.[1] ||
    localAliasDomainPickerError.value,
)

const visibleAliasDomainCount = hiddenDomains =>
  aliasPickerDomains.filter(
    domain => domain === props.defaultAliasDomain || !hiddenDomains.includes(domain),
  ).length

const aliasDomainIsShown = domain => {
  if (domain === props.defaultAliasDomain) {
    return true
  }

  return !hiddenAliasDomainsForm.hidden_domains.includes(domain)
}

const shownAliasDomainCount = domains => domains.filter(domain => aliasDomainIsShown(domain)).length

const setAliasDomainSectionCheckbox = (element, domains) => {
  if (!element) {
    return
  }

  const shown = shownAliasDomainCount(domains)
  element.indeterminate = shown > 0 && shown < domains.length
}

const setAliasDomainSectionShown = (domains, event) => {
  if (event.target.checked) {
    showAliasDomainGroup(domains)
    return
  }

  hideAliasDomainGroup(domains)
  event.target.checked = shownAliasDomainCount(domains) === domains.length
  setAliasDomainSectionCheckbox(event.target, domains)
}

const setAliasDomainShown = (domain, event) => {
  if (domain === props.defaultAliasDomain) {
    event.target.checked = true
    return
  }

  localAliasDomainPickerError.value = ''
  const hiddenDomains = hiddenAliasDomainsForm.hidden_domains.filter(item => item !== domain)

  if (!event.target.checked) {
    const nextHiddenDomains = [...hiddenDomains, domain]

    if (visibleAliasDomainCount(nextHiddenDomains) === 0) {
      event.target.checked = true
      localAliasDomainPickerError.value = 'Leave at least one domain visible in the alias picker.'
      return
    }

    hiddenAliasDomainsForm.hidden_domains = nextHiddenDomains
    return
  }

  hiddenAliasDomainsForm.hidden_domains = hiddenDomains
}

const showAliasDomainGroup = domains => {
  localAliasDomainPickerError.value = ''
  const shownDomains = new Set(domains)
  hiddenAliasDomainsForm.hidden_domains = hiddenAliasDomainsForm.hidden_domains.filter(
    domain => !shownDomains.has(domain),
  )
}

const hideAliasDomainGroup = domains => {
  localAliasDomainPickerError.value = ''
  const nextHiddenDomains = [
    ...new Set([
      ...hiddenAliasDomainsForm.hidden_domains,
      ...domains.filter(domain => domain !== props.defaultAliasDomain),
    ]),
  ]

  if (visibleAliasDomainCount(nextHiddenDomains) === 0) {
    const keepVisible = aliasPickerDomains.find(domain => domain !== props.defaultAliasDomain)
    hiddenAliasDomainsForm.hidden_domains = nextHiddenDomains.filter(
      domain => domain !== keepVisible,
    )
    localAliasDomainPickerError.value = 'Leave at least one domain visible in the alias picker.'
    return
  }

  hiddenAliasDomainsForm.hidden_domains = nextHiddenDomains
}

const defaultAliasFormatForm = useForm({
  format: props.defaultAliasFormat,
})

const aliasSeparatorForm = useForm({
  separator: props.aliasSeparator,
})

const loginRedirectForm = useForm({
  redirect: props.loginRedirect,
})

const displayFromFormatForm = useForm({
  format: props.displayFromFormat,
})

const useReplyToForm = useForm({
  use_reply_to: props.useReplyTo,
})

const storeFailedDeliveriesForm = useForm({
  store_failed_deliveries: props.storeFailedDeliveries,
})

const showIntentionalFailedDeliveriesForm = useForm({
  show_intentional_failed_deliveries: props.showIntentionalFailedDeliveries,
})

const failedDeliveryNotificationPreferenceForm = useForm({
  failed_delivery_notification_preference: props.failedDeliveryNotificationPreference,
})

const themeForm = useForm({
  theme: props.theme,
})

const saveAliasLastUsedForm = useForm({
  save_alias_last_used: props.saveAliasLastUsed,
})

const fromNameForm = useForm({
  from_name: props.fromName,
})

const bannerLocationForm = useForm({
  banner_location: props.bannerLocation,
})

const spamWarningBehaviourForm = useForm({
  spam_warning_behaviour: props.spamWarningBehaviour,
})

const listUnsubscribeBehaviourForm = useForm({
  list_unsubscribe_behaviour: props.listUnsubscribeBehaviour,
})

const emailSubjectForm = useForm({
  email_subject: props.emailSubject,
})
</script>
