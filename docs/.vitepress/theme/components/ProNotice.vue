<script setup lang="ts">
// Shown on Pro pages and next to free features that Pro extends.
defineProps<{ feature?: string }>()

const url = 'https://odiseo.io/en/products/sylius-brevo-plugin?utm_source=docs&utm_medium=notice&utm_campaign=sylius-brevo-plugin'
</script>

<template>
  <div class="pro-notice">
    <span class="pro-badge">Pro</span>
    <p>
      <template v-if="feature">{{ feature }} comes with <strong>Brevo Pro</strong>, the paid add-on of this plugin.</template>
      <template v-else>This page is about <strong>Brevo Pro</strong>, the paid add-on of this plugin.</template>
      One price, perpetual license, every Pro module included.
      <a :href="url" target="_blank" rel="noopener">See Brevo Pro →</a>
    </p>
  </div>
</template>
