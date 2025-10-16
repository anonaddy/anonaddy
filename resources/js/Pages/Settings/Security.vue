<template>
  <SettingsLayout>
    <div class="divide-y divide-grey-200">
      <div
        v-if="backupCode"
        class="text-base border-t-8 rounded text-yellow-800 border-yellow-600 bg-yellow-100 px-3 py-4 mt-4"
        role="alert"
      >
        <div class="flex items-center mb-2">
          <span class="rounded-full bg-yellow-400 uppercase px-2 py-1 text-xs font-bold mr-2"
            >Important</span
          >
          <div>
            2FA successfully enabled! Please <b>make a copy of your backup code below</b>. If you
            have an old backup code saved <b>you must update it with this one.</b> If you lose your
            2FA device you can use this backup code to disable 2FA on your account.
            <b>This is the only time this code will be displayed, so be sure not to lose it!</b>
          </div>
        </div>
        <pre
          @click="clipboard(backupCode)"
          class="flex p-3 text-grey-900 bg-white border rounded cursor-pointer"
          title="Copy To Clipboard"
        >
              <code class="break-all whitespace-normal">{{ backupCode }}</code>
          </pre>
      </div>
      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900">Update Password</h3>
          <p class="text-base text-grey-700">
            Ensure your account is using a long, random, unique password to stay secure. It is
            recommended to use a password manager such as Bitwarden. Updating your password will
            also logout your active sessions on other browsers and devices.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              updatePasswordForm.post(route('settings.password'), {
                preserveScroll: true,
                onSuccess: () => updatePasswordForm.reset(),
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <div class="mb-4">
                  <label for="current" class="block text-sm font-medium leading-6 text-grey-600"
                    >Current Password</label
                  >
                  <div class="relative mt-2">
                    <input
                      v-model="updatePasswordForm.current"
                      type="password"
                      name="current"
                      id="current"
                      required
                      class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                      :class="
                        updatePasswordForm.errors.current
                          ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                          : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                      "
                      placeholder="********"
                      :aria-invalid="updatePasswordForm.errors.current ? 'true' : undefined"
                      :aria-describedby="
                        updatePasswordForm.errors.current ? 'current-password-error' : undefined
                      "
                    />
                    <div
                      v-if="updatePasswordForm.errors.current"
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                  </div>
                  <p
                    v-if="updatePasswordForm.errors.current"
                    class="mt-2 text-sm text-red-600"
                    id="current-password-error"
                  >
                    {{ updatePasswordForm.errors.current }}
                  </p>
                </div>

                <div class="mb-4">
                  <label for="password" class="block text-sm font-medium leading-6 text-grey-600"
                    >New Password</label
                  >
                  <div class="relative mt-2">
                    <input
                      v-model="updatePasswordForm.password"
                      type="password"
                      name="password"
                      id="password"
                      required
                      class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                      :class="
                        updatePasswordForm.errors.password
                          ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                          : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                      "
                      placeholder="********"
                      :aria-invalid="updatePasswordForm.errors.password ? 'true' : undefined"
                      :aria-describedby="
                        updatePasswordForm.errors.password ? 'password-error' : undefined
                      "
                    />
                    <div
                      v-if="updatePasswordForm.errors.password"
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                  </div>
                  <p
                    v-if="updatePasswordForm.errors.password"
                    class="mt-2 text-sm text-red-600"
                    id="password-error"
                  >
                    {{ updatePasswordForm.errors.password }}
                  </p>
                </div>

                <div>
                  <label
                    for="password-confirm"
                    class="block text-sm font-medium leading-6 text-grey-600"
                    >Confirm New Password</label
                  >
                  <div class="relative mt-2">
                    <input
                      type="password"
                      name="password_confirmation"
                      id="password-confirm"
                      v-model="updatePasswordForm.password_confirmation"
                      class="block w-full rounded-md border-0 py-1.5 text-grey-900 shadow-sm ring-1 ring-grey-300 placeholder:text-grey-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-base sm:leading-6"
                      placeholder="********"
                      required
                    />
                  </div>
                </div>
              </div>
            </div>

            <button
              type="submit"
              :disabled="updatePasswordForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Update Password
              <loader v-if="updatePasswordForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900">Browser Sessions</h3>
          <p class="text-base text-grey-700">
            If necessary, you may logout of all of your other browser sessions across all of your
            devices. If you feel your account has been compromised, you should also update your
            password.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              browserSessionsForm.delete(route('settings.browser_sessions'), {
                preserveScroll: true,
                onSuccess: () => browserSessionsForm.reset(),
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="browser-sessions"
                  class="block text-sm font-medium leading-6 text-grey-600"
                  >Current Password</label
                >
                <div class="relative mt-2">
                  <input
                    v-model="browserSessionsForm.current"
                    type="password"
                    name="current"
                    id="browser-sessions"
                    required
                    class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      browserSessionsForm.errors.current
                        ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                        : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                    "
                    placeholder="********"
                    :aria-invalid="browserSessionsForm.errors.current ? 'true' : undefined"
                    :aria-describedby="
                      browserSessionsForm.errors.current ? 'browser-sessions-error' : undefined
                    "
                  />
                  <div
                    v-if="browserSessionsForm.errors.current"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="browserSessionsForm.errors.current"
                  class="mt-2 text-sm text-red-600"
                  id="browser-sessions-error"
                >
                  {{ browserSessionsForm.errors.current }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="browserSessionsForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Logout Other Browser Sessions
              <loader v-if="browserSessionsForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div class="py-10">
        <div class="space-y-1">
          <h2 class="text-xl font-medium leading-6 text-grey-900">Two-Factor Authentication</h2>
          <p class="text-base text-grey-700">
            Two-factor authentication, also known as 2FA or multi-factor, adds an extra layer of
            security to your account beyond your username and password. There are
            <b>multiple options for 2FA</b> - 1. Authentication App (e.g. Google Authenticator or
            another, Aegis, andOTP) 2. Hardware Security Key (e.g. YubiKey, SoloKey, Nitrokey) 3.
            Passkeys (which can be stored in a supported provider such as Bitwarden or 1Password).
          </p>
          <p class="text-base text-grey-700">
            When you login with 2FA enabled, you will be prompted to use a security key or enter a
            OTP (one time passcode) depending on which method you choose below. You can only have
            one method of 2nd factor authentication enabled at once.
          </p>
        </div>
      </div>

      <div v-if="twoFactorEnabled || webauthnEnabled" class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900">Generate New Backup Code</h3>
          <p class="text-base text-grey-700">
            The backup code can be used in a situation where you have lost your 2FA device to allow
            you to access your account. If you've forgotten or lost your backup code then you can
            generate a new one by clicking the button below.
            <b>This code will only be displayed once</b> so make sure you store it in a
            <b>secure place</b>. If you have an old backup code saved
            <b>you must update it with this one</b>.
          </p>
        </div>
        <div
          v-if="regeneratedBackupCode"
          class="text-base border-t-8 rounded text-yellow-800 border-yellow-600 bg-yellow-100 px-3 py-4 mt-4"
          role="alert"
        >
          <div class="flex items-center mb-2">
            <span class="rounded-full bg-yellow-400 uppercase px-2 py-1 text-xs font-bold mr-2"
              >Important</span
            >
            <div>
              Please <b>make a copy of your backup code below</b>. If you have an old backup code
              saved <b>you must update it with this one.</b> If you lose your 2FA device you can use
              this backup code to disable 2FA on your account.
              <b>This is the only time this code will be displayed, so be sure not to lose it!</b>
            </div>
          </div>
          <pre
            @click="clipboard(regeneratedBackupCode)"
            class="flex p-3 text-grey-900 bg-white border rounded cursor-pointer"
            title="Copy To Clipboard"
          >
                <code class="break-all whitespace-normal">{{ regeneratedBackupCode }}</code>
            </pre>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              newBackupCodeForm.post(route('settings.new_backup_code'), {
                preserveScroll: true,
                onSuccess: () => newBackupCodeForm.reset(),
              })
            "
          >
            <div class="mb-4">
              <label for="new-backup-code" class="block text-sm font-medium leading-6 text-grey-600"
                >Current Password</label
              >
              <div class="relative mt-2">
                <input
                  v-model="newBackupCodeForm.current"
                  type="password"
                  name="current"
                  id="new-backup-code"
                  required
                  class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                  :class="
                    newBackupCodeForm.errors.current
                      ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                      : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                  "
                  placeholder="********"
                  :aria-invalid="newBackupCodeForm.errors.current ? 'true' : undefined"
                  :aria-describedby="
                    newBackupCodeForm.errors.current ? 'new-backup-code-error' : undefined
                  "
                />
                <div
                  v-if="newBackupCodeForm.errors.current"
                  class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                >
                  <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                </div>
              </div>
              <p
                v-if="newBackupCodeForm.errors.current"
                class="mt-2 text-sm text-red-600"
                id="new-backup-code-error"
              >
                {{ newBackupCodeForm.errors.current }}
              </p>
            </div>
            <button
              type="submit"
              :disabled="newBackupCodeForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Generate New Backup Code
              <loader v-if="newBackupCodeForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div v-if="twoFactorEnabled" class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900">
            Disable Authentication App (TOTP)
          </h3>
          <p class="text-base text-grey-700">
            To disable TOTP authentication enter your password below. You can always enable it again
            later if you wish.
          </p>
        </div>
        <div class="mt-4">
          <form
            @submit.prevent="
              disableTwoFactorForm.post(route('settings.2fa_disable'), {
                preserveScroll: true,
                onSuccess: () => disableTwoFactorForm.reset(),
              })
            "
          >
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="disable-two-factor"
                  class="block text-sm font-medium leading-6 text-grey-600"
                  >Current Password</label
                >
                <div class="relative mt-2">
                  <input
                    v-model="disableTwoFactorForm.current"
                    type="password"
                    name="current"
                    id="disable-two-factor"
                    required
                    class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                    :class="
                      disableTwoFactorForm.errors.current
                        ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                        : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                    "
                    placeholder="********"
                    :aria-invalid="disableTwoFactorForm.errors.current ? 'true' : undefined"
                    :aria-describedby="
                      disableTwoFactorForm.errors.current ? 'disable-two-factor-error' : undefined
                    "
                  />
                  <div
                    v-if="disableTwoFactorForm.errors.current"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="disableTwoFactorForm.errors.current"
                  class="mt-2 text-sm text-red-600"
                  id="disable-two-factor-error"
                >
                  {{ disableTwoFactorForm.errors.current }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              :disabled="disableTwoFactorForm.processing"
              class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            >
              Disable TOTP Two-Factor Authentication
              <loader v-if="disableTwoFactorForm.processing" />
            </button>
          </form>
        </div>
      </div>

      <div v-if="!twoFactorEnabled" class="divide-y divide-grey-200">
        <div class="py-10">
          <div class="space-y-1">
            <h3 class="text-lg font-medium leading-6 text-grey-900">
              Enable Authentication App (TOTP)
            </h3>
            <p class="text-base text-grey-700">
              TOTP two-factor authentication requires the use of Google Authenticator or another
              compatible app such as Aegis or andOTP (both on F-droid) for Android. Alternatively,
              you can use the code below. Make sure that you write down your secret code in a safe
              place.
            </p>
          </div>
          <div class="mt-4">
            <span v-html="qrCode"></span>
            <p class="mb-2">Secret: {{ authSecret }}</p>
            <form
              @submit.prevent="
                regenerateTwoFactorForm.post(route('settings.2fa_regenerate'), {
                  preserveScroll: true,
                })
              "
            >
              <input
                type="submit"
                :disabled="regenerateTwoFactorForm.processing"
                class="text-indigo-900 bg-transparent cursor-pointer disabled:cursor-not-allowed"
                value="Click here to regenerate your secret key"
              />

              <p
                v-if="$page.props.errors.regenerate_2fa"
                class="mt-2 text-sm text-red-600"
                id="enable-two-factor-error"
              >
                {{ $page.props.errors.regenerate_2fa }}
              </p>
            </form>
            <form
              class="mt-6"
              @submit.prevent="
                enableTwoFactorForm.post(route('settings.2fa_enable'), {
                  preserveScroll: page => Object.keys(page.props.errors).length,
                  onSuccess: () => {
                    enableTwoFactorForm.reset()
                    twoFactorEnabled = true
                  },
                })
              "
            >
              <div class="grid grid-cols-1 mb-6">
                <div class="mb-4">
                  <label
                    for="enable-two-factor"
                    class="block text-sm font-medium leading-6 text-grey-600"
                    >Two-Factor Token</label
                  >
                  <div class="relative mt-2">
                    <input
                      v-model="enableTwoFactorForm.two_factor_token"
                      type="text"
                      name="two_factor_token"
                      id="enable-two-factor"
                      required
                      class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                      :class="
                        enableTwoFactorForm.errors.two_factor_token
                          ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                          : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                      "
                      placeholder="123456"
                      :aria-invalid="
                        enableTwoFactorForm.errors.two_factor_token ? 'true' : undefined
                      "
                      :aria-describedby="
                        enableTwoFactorForm.errors.two_factor_token
                          ? 'enable-two-factor-error'
                          : undefined
                      "
                    />
                    <div
                      v-if="enableTwoFactorForm.errors.two_factor_token"
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                  </div>
                  <p
                    v-if="enableTwoFactorForm.errors.two_factor_token"
                    class="mt-2 text-sm text-red-600"
                    id="enable-two-factor-error"
                  >
                    {{ enableTwoFactorForm.errors.two_factor_token }}
                  </p>
                </div>
                <div>
                  <label
                    for="enable-two-factor-current"
                    class="block text-sm font-medium leading-6 text-grey-600"
                    >Current Password</label
                  >
                  <div class="relative mt-2">
                    <input
                      v-model="enableTwoFactorForm.current"
                      type="password"
                      name="current"
                      id="enable-two-factor-current"
                      required
                      class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                      :class="
                        enableTwoFactorForm.errors.current
                          ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                          : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                      "
                      placeholder="********"
                      :aria-invalid="enableTwoFactorForm.errors.current ? 'true' : undefined"
                      :aria-describedby="
                        enableTwoFactorForm.errors.current
                          ? 'enable-two-factor-current-error'
                          : undefined
                      "
                    />
                    <div
                      v-if="enableTwoFactorForm.errors.current"
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                  </div>
                  <p
                    v-if="enableTwoFactorForm.errors.current"
                    class="mt-2 text-sm text-red-600"
                    id="enable-two-factor-current-error"
                  >
                    {{ enableTwoFactorForm.errors.current }}
                  </p>
                </div>
              </div>

              <button
                type="submit"
                :disabled="enableTwoFactorForm.processing"
                class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              >
                Verify and Enable
                <loader v-if="enableTwoFactorForm.processing" />
              </button>
            </form>
          </div>
        </div>
      </div>
      <div v-if="!keys.length" class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900">
            Enable Device/Passkey Authentication (WebAuthn)
          </h3>
          <p class="text-base text-grey-700">
            WebAuthn is a new W3C global standard for secure authentication. You can use any
            hardware key such as a Yubikey, Solokey, NitroKey etc.
          </p>
        </div>
        <div class="mt-4">
          <a
            type="button"
            :href="route('webauthn.create')"
            class="block bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 text-center"
          >
            Register New Key
          </a>
        </div>
      </div>
      <div v-else>
        <div class="py-10">
          <div class="space-y-1">
            <h3 class="text-lg font-medium leading-6 text-grey-900">
              Device/Passkey Authentication (WebAuthn)
            </h3>
            <p class="text-base text-grey-700">
              Hardware security keys and Passkeys that you have registered for 2nd factor
              authentication. To remove a key simply click the delete button next to it. Disabled
              keys cannot be used to login. If you disable all keys
              <b>Two-Factor Authentication</b> will
              {{ twoFactorEnabled ? 'still be enabled as you have TOTP 2FA' : 'be turned off' }} on
              your account.
            </p>
          </div>
          <div class="mt-4">
            <p class="mb-0" v-if="keys.length === 0">You have not registered any keys.</p>

            <div class="table w-full text-sm md:text-base" v-if="keys.length > 0">
              <div class="table-row">
                <div class="table-cell p-1 md:p-4 font-semibold">Name</div>
                <div class="table-cell p-1 md:p-4 font-semibold">Created</div>
                <div class="table-cell p-1 md:p-4 font-semibold">Enabled</div>
                <div class="table-cell p-1 md:p-4 text-right">
                  <a href="/webauthn/keys/create" class="text-indigo-700">Add New Key</a>
                </div>
              </div>
              <div v-for="key in keys" :key="key.id" class="table-row even:bg-grey-50 odd:bg-white">
                <div class="table-cell p-1 md:p-4">{{ key.name }}</div>
                <div class="table-cell p-1 md:p-4">{{ $filters.timeAgo(key.created_at) }}</div>
                <div class="table-cell p-1 md:p-4">
                  <svg
                    v-if="key.enabled"
                    class="h-5 w-5 inline-block mr-2"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                  >
                    <g fill="none" fill-rule="evenodd">
                      <circle cx="10" cy="10" r="10" fill="#91E697"></circle>
                      <polyline
                        stroke="#0E7817"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        points="6 10 8.667 12.667 14 7.333"
                      ></polyline>
                    </g>
                  </svg>
                  <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    class="h-5 w-5 inline-block mr-2"
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
                </div>
                <div class="table-cell p-1 md:p-4 text-right">
                  <button
                    v-if="key.enabled"
                    class="text-indigo-500 font-bold cursor-pointer rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    @click="showDisableKeyModal(key)"
                  >
                    Disable
                  </button>
                  <button
                    v-else
                    class="text-indigo-500 font-bold cursor-pointer rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    @click="enableKey(key)"
                  >
                    Enable
                    <loader v-if="key.enableKeyLoading" />
                  </button>
                  <button
                    class="text-red-500 font-bold cursor-pointer sm:ml-4 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    @click="showDeleteKeyModal(key)"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal :open="disableKeyModalOpen" @close="closeDisableKeyModal">
      <template v-slot:title> Disable Key </template>
      <template v-slot:content>
        <p
          v-if="enabledKeys.length <= 1 && !twoFactorEnabled"
          class="my-4 text-grey-700 dark:text-grey-200"
        >
          Once this key is disabled, <b>Two-Factor Authentication</b> will be disabled on your
          account as you do not have any other enabled keys.
        </p>
        <p v-else class="my-4 text-grey-700 dark:text-grey-200">
          Once this key is disabled, <b>Two-Factor Authentication</b> will still be enabled as you
          have {{ twoFactorEnabled ? 'TOTP 2FA' : 'other enabled keys' }} on your account.
        </p>
        <div class="mt-6">
          <label
            for="disable-key-current-password"
            class="block font-medium leading-6 text-grey-600 text-sm my-2"
          >
            Current Password
          </label>
          <p v-show="errors.disableKey" class="mb-3 text-red-500 text-sm">
            {{ errors.disableKey }}
          </p>
          <input
            v-model="disableKeyCurrentPassword"
            type="password"
            id="disable-key-current-password"
            class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 mb-6"
            :class="errors.disableKey ? 'ring-red-500' : ''"
            placeholder="********"
            autofocus
          />
          <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <button
              @click="disableKey"
              class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              :disabled="disableKeyLoading"
            >
              Disable
              <loader v-if="disableKeyLoading" />
            </button>
            <button
              @click="closeDisableKeyModal"
              class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Close
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteKeyModalOpen" @close="closeDeleteKeyModal">
      <template v-slot:title> Remove Key </template>
      <template v-slot:content>
        <p
          v-if="enabledKeys.length <= 1 && !twoFactorEnabled"
          class="my-4 text-grey-700 dark:text-grey-200"
        >
          Once this key is removed, <b>Two-Factor Authentication</b> will be disabled on your
          account as you do not have any other enabled keys.
        </p>
        <p v-else class="my-4 text-grey-700 dark:text-grey-200">
          Once this key is removed, <b>Two-Factor Authentication</b> will still be enabled as you
          have {{ twoFactorEnabled ? 'TOTP 2FA' : 'other enabled keys' }} on your account.
        </p>
        <div class="mt-6">
          <label
            for="delete-key-current-password"
            class="block font-medium leading-6 text-grey-600 text-sm my-2"
          >
            Current Password
          </label>
          <p v-show="errors.deleteKey" class="mb-3 text-red-500 text-sm">
            {{ errors.deleteKey }}
          </p>
          <input
            v-model="deleteKeyCurrentPassword"
            type="password"
            id="delete-key-current-password"
            class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 mb-6"
            :class="errors.deleteKey ? 'ring-red-500' : ''"
            placeholder="********"
            autofocus
          />
          <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <button
              @click="deleteKey"
              class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              :disabled="deleteKeyLoading"
            >
              Remove
              <loader v-if="deleteKeyLoading" />
            </button>
            <button
              @click="closeDeleteKeyModal"
              class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Close
            </button>
          </div>
        </div>
      </template>
    </Modal>
  </SettingsLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import SettingsLayout from './../../Layouts/SettingsLayout.vue'
import { notify } from '@kyvg/vue3-notification'
import { ExclamationCircleIcon } from '@heroicons/vue/20/solid'
import Modal from '../../Components/Modal.vue'

const props = defineProps({
  initialTwoFactorEnabled: {
    type: Boolean,
    required: true,
  },
  initialWebauthnEnabled: {
    type: Boolean,
    required: true,
  },
  backupCode: {
    type: String,
  },
  regeneratedBackupCode: {
    type: String,
  },
  authSecret: {
    type: String,
  },
  qrCode: {
    type: String,
  },
  initialKeys: {
    type: Array,
  },
})

const keys = ref(props.initialKeys)
const twoFactorEnabled = ref(props.initialTwoFactorEnabled)
const webauthnEnabled = ref(props.initialWebauthnEnabled)

const enabledKeys = computed(() => _.filter(keys.value, key => key.enabled))

const disableKeyModalOpen = ref(false)
const deleteKeyModalOpen = ref(false)
const disableKeyCurrentPassword = ref('')
const deleteKeyCurrentPassword = ref('')
const keyToDisable = ref(null)
const keyToDelete = ref(null)
const disableKeyLoading = ref(false)
const deleteKeyLoading = ref(false)
const errors = ref({})

const updatePasswordForm = useForm({
  current: '',
  password: '',
  password_confirmation: '',
})

const browserSessionsForm = useForm({
  current: '',
})

const newBackupCodeForm = useForm({
  current: '',
})

const disableTwoFactorForm = useForm({
  current: '',
})

const enableTwoFactorForm = useForm({
  two_factor_token: '',
  current: '',
})

const regenerateTwoFactorForm = useForm({})

const showDisableKeyModal = key => {
  keyToDisable.value = key
  disableKeyModalOpen.value = true
}

const closeDisableKeyModal = () => {
  disableKeyModalOpen.value = false
}

const showDeleteKeyModal = key => {
  keyToDelete.value = key
  deleteKeyModalOpen.value = true
}

const closeDeleteKeyModal = () => {
  deleteKeyModalOpen.value = false
}

const deleteKey = () => {
  errors.value = {}

  if (!deleteKeyCurrentPassword.value) {
    return (errors.value.deleteKey = 'Current password is required.')
  }

  deleteKeyLoading.value = true

  axios
    .post(
      `/webauthn/keys/${keyToDelete.value.id}`,
      JSON.stringify({
        current: deleteKeyCurrentPassword.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      router.reload({
        onSuccess: page => {
          deleteKeyLoading.value = false
          deleteKeyModalOpen.value = false
          keyToDelete.value = null
          deleteKeyCurrentPassword.value = ''
          // If no more keys then set webauthnEnabled to false
          if (!keys.length) {
            webauthnEnabled.value = false
          }

          keys.value = props.initialKeys
          successMessage('Key Successfully Removed')
        },
      })
    })
    .catch(error => {
      deleteKeyLoading.value = false
      if (error.response.status == 422) {
        errors.value.deleteKey = 'The password is incorrect.'
      } else if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const enableKey = key => {
  key.enableKeyLoading = true

  axios
    .post(
      `/webauthn/enabled-keys`,
      JSON.stringify({
        id: key.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      successMessage('Key Successfully Enabled')
      key.enableKeyLoading = false
      key.enabled = true
    })
    .catch(error => {
      key.enableKeyLoading = false
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const disableKey = () => {
  errors.value = {}

  if (!disableKeyCurrentPassword.value) {
    return (errors.value.disableKey = 'Current password is required.')
  }

  disableKeyLoading.value = true

  axios
    .post(
      `/webauthn/enabled-keys/${keyToDisable.value.id}`,
      JSON.stringify({
        current: disableKeyCurrentPassword.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      let key = _.find(keys.value, ['id', keyToDisable.value.id])
      key.enabled = false

      successMessage('Key Successfully Disabled')
      disableKeyLoading.value = false
      disableKeyModalOpen.value = false
      keyToDisable.value = null
      disableKeyCurrentPassword.value = ''
    })
    .catch(error => {
      disableKeyLoading.value = false
      if (error.response.status == 422) {
        errors.value.disableKey = 'The password is incorrect.'
      } else if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
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
