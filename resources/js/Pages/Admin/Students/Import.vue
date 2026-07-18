<template>
    <AdminLayout>
        <main class="max-w-2xl mx-auto px-6 lg:px-10 py-8 space-y-4">

            <div>
                <div class="text-lg font-semibold text-slate-800">Import students — {{ section.name }}</div>
                <Link :href="`/paulo/sections/${section.id}`" class="text-xs text-slate-400 hover:text-[#003399]">← Back to section</Link>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-700 mb-2">Excel format</div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Columns: <code class="bg-slate-100 px-1 rounded">student_number</code>, <code class="bg-slate-100 px-1 rounded">full_name</code>.
                    Lahat ng ii-import dito ay mapupunta sa <strong>{{ section.name }}</strong>.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-700 mb-3">Upload file</div>
                <form @submit.prevent="submit" class="space-y-3">
                    <input type="file" accept=".xlsx,.xls,.csv" @change="form.file = $event.target.files[0]" class="text-sm border border-slate-200 rounded-lg px-3 py-2 w-full" />
                    <p v-if="form.errors.file" class="text-xs text-red-500">{{ form.errors.file }}</p>
                    <button type="submit" :disabled="form.processing || !form.file" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                        {{ form.processing ? 'Uploading...' : 'Import students' }}
                    </button>
                </form>
            </div>
        </main>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ section: Object });

const form = useForm({ file: null });

const submit = () => {
    form.post(`/paulo/sections/${props.section.id}/students/import`, { forceFormData: true });
};
</script>