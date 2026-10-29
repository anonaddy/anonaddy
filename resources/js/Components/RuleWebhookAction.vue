<template>
  <span class="sm:ml-4 flex flex-col w-full gap-2">
    <input
      v-model="value.url"
      :id="urlInputId"
      type="url"
      inputmode="url"
      autocomplete="off"
      spellcheck="false"
      class="w-full appearance-none bg-white border border-transparent rounded text-grey-700 focus:outline-hidden p-2 dark:text-white dark:bg-white/5"
      placeholder="https://example.com/hooks/addy"
      required
    />
    <label :for="bodyInputId" class="flex items-center text-sm text-grey-700 dark:text-grey-200">
      <input
        v-model="value.include_body"
        :id="bodyInputId"
        type="checkbox"
        class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 dark:text-indigo-400 dark:bg-grey-950 border-grey-300 rounded"
      />
      <span class="ml-2">Include the message body</span>
    </label>
    <label :for="headersInputId" class="flex items-center text-sm text-grey-700 dark:text-grey-200">
      <input
        v-model="value.include_headers"
        :id="headersInputId"
        type="checkbox"
        class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 dark:text-indigo-400 dark:bg-grey-950 border-grey-300 rounded"
      />
      <span class="ml-2">Include the headers</span>
    </label>
    <p class="text-xs text-grey-500 dark:text-grey-300">
      addy sends a signed JSON POST when this rule matches an accepted email. Attachments are left
      out. The message body and the headers are included only when you select them, and each is
      limited to 1 MB. The signing secret is under More info on this page. If deliveries to this URL
      keep failing, addy pauses it for one hour and emails you. Other actions on the rule still run.
    </p>
  </span>
</template>

<script setup>
defineProps({
  value: {
    type: Object,
    required: true,
  },
  urlInputId: {
    type: String,
    required: true,
  },
  bodyInputId: {
    type: String,
    required: true,
  },
  headersInputId: {
    type: String,
    required: true,
  },
})
</script>
