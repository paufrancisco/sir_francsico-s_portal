<script setup>
import { router } from '@inertiajs/vue3';

const logout = () => {
    router.post('/student/logout');
};
</script>

<template>
    <div style="padding: 40px;">
        <h1 style="color: #003399;">Naka-login ka na! Dashboard ito, gagawin pa natin.</h1>
        <button @click="logout" style="margin-top: 20px; background: #003399; color: #fff; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">Logout</button>
    </div>
</template>