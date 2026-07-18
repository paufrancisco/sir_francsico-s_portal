<template>
    <AdminLayout>
        <main class="max-w-2xl mx-auto px-6 lg:px-10 py-8 space-y-4">

            <div>
                <div class="text-lg font-semibold text-slate-800">Import result — {{ section.name }}</div>
                <Link :href="`/paulo/sections/${section.id}`" class="text-xs text-slate-400 hover:text-[#003399]">← Back to section</Link>
            </div>

            <div class="bg-[#FAEEDA] text-[#854F0B] text-xs rounded-lg px-4 py-3">
                Nasave na yung passwords — makikita mo rin sila anytime sa master list ng section na ito (click "Show passwords").
            </div>

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs text-slate-500">
                            <th class="px-4 py-2">Student number</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in imported" :key="row.student_number" class="border-t border-slate-100">
                            <td class="px-4 py-2 text-slate-700">{{ row.student_number }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ row.full_name }}</td>
                            <td class="px-4 py-2 font-mono text-[#003399]">{{ row.password }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Link :href="`/paulo/sections/${section.id}/students/import`" class="text-xs text-[#003399] font-medium hover:underline">
                ← Mag-import ulit
            </Link>
        </main>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ imported: Array, section: Object });
</script>