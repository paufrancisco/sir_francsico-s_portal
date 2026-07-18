<!-- resources/js/Pages/Admin/Students/Index.vue -->
<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
            <div class="max-w-4xl mx-auto px-6 lg:px-10 py-4 flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-[#003399]">Sir Francisco</div>
                    <div class="text-xs text-slate-400">Master list — Students</div>
                </div>
                <Link href="/paulo" class="text-xs text-slate-400 hover:text-[#003399]">← Back to admin</Link>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-6 lg:px-10 py-8 space-y-4">

            <div class="flex justify-end">
                <button
                    v-if="!revealed"
                    @click="showModal = true"
                    class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg"
                >
                    Show passwords
                </button>
                <Link v-else href="/paulo/students" class="text-xs text-slate-400 hover:underline">
                    Hide passwords
                </Link>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs text-slate-500">
                            <th class="px-4 py-2">Student number</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Section</th>
                            <th class="px-4 py-2">Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in students" :key="s.id" class="border-t border-slate-100">
                            <td class="px-4 py-2 text-slate-700">{{ s.student_number }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ s.full_name }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ s.section }}</td>
                            <td class="px-4 py-2 font-mono text-[#003399]">
                                {{ revealed ? s.password : '••••••' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <div v-if="showModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-5 w-80 shadow-xl">
                <div class="text-sm font-semibold text-slate-700 mb-3">Confirm admin password</div>
                <form @submit.prevent="submitReveal" class="space-y-3">
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="Your login password"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2"
                    />
                    <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg flex-1">
                            Confirm
                        </button>
                        <button type="button" @click="showModal = false" class="text-sm text-slate-500 px-3 py-2">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps({
    students: Array,
    revealed: Boolean,
});

const showModal = ref(false);
const form = useForm({ password: '' });

const submitReveal = () => {
    form.post('/paulo/students/reveal', {
        onSuccess: () => { showModal.value = false; form.reset(); },
    });
};
</script>