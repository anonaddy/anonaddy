<template>
  <div>
    <Head title="Verify Email" />
    <h1 id="primary-heading" class="sr-only">Verify Email</h1>

    <div class="container py-8">
      <div class="flex flex-wrap justify-center">
        <div class="w-full max-w-md">
          <div
            class="px-6 py-8 md:px-10 flex flex-col break-words bg-white rounded-lg shadow-lg dark:bg-grey-900"
          >
            <h1 class="text-center font-bold text-2xl">Verify Your Email Address</h1>

            <div class="mx-auto mt-6 w-24 border-b-2 border-grey-200"></div>

            <div class="w-full flex flex-wrap mt-8">
              <p class="leading-normal mb-2 text-center">
                Before proceeding, please check your email <b>{{ $page.props.user.email }}</b> for a
                verification link. This link will expire after 1 hour.
              </p>
              <p class="leading-normal mb-2 text-center">
                If that email address is incorrect you can update it on the
                <Link :href="route('settings.show')" class="text-indigo-700 dark:text-indigo-400"
                  >settings page</Link
                >.
              </p>
              <p class="leading-normal mb-6 text-center">
                You must verify your email <b>within 30 days</b> or your account will be
                automatically deleted.
              </p>

              <form
                @submit.prevent="
                  resendForm.post(route('verification.resend'), { preserveScroll: true })
                "
                class="w-full"
              >
                <button
                  type="submit"
                  :disabled="resendForm.processing"
                  class="bg-cyan-400 w-full text-center hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:ring no-underline mx-auto disabled:cursor-not-allowed"
                >
                  Resend verification email
                  <loader v-if="resendForm.processing" />
                </button>
              </form>

              <p class="text-sm text-grey-600 mt-4 text-center w-full dark:text-grey-200">
                You can resend once per minute.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const resendForm = useForm({})
</script>
