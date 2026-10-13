<template>
  <Head title="Bulk grade import" />

  <main class="page">
    <div class="page__inner">
      <BulkImportTab />
    </div>
  </main>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import BulkImportTab from '@/Components/BulkImportTab.vue'; // reminder: '@' alias = resources/js (Breeze/Vite default)
</script>

<style scoped>
.page {
  min-height: 100vh;
  padding: 32px 16px;
  background:
    radial-gradient(1200px 600px at 20% -10%, rgba(56, 130, 200, 0.35), transparent 60%),
    linear-gradient(160deg, #0b1d33 0%, #0d2744 55%, #0a1626 100%);
}
.page__inner {
  max-width: 1100px;
  margin: 0 auto;
}
</style>
