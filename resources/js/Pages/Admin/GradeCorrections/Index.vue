<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    corrections: { type: Array, default: () => [] },
});

const resolve = (id) => {
    router.patch(`/paulo/grade-corrections/${id}/resolve`, {}, { preserveScroll: true });
};
</script>

<template>
    <div class="p-6">
        <h1 class="text-lg font-semibold text-slate-800 mb-4">Grade Correction Requests</h1>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs">
                    <tr>
                        <th class="text-left px-4 py-3">Student</th>
                        <th class="text-left px-4 py-3">Section</th>
                        <th class="text-left px-4 py-3">Type</th>
                        <th class="text-left px-4 py-3">Notes</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in corrections" :key="c.id">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-700">{{ c.student_name }}</div>
                            <div class="text-xs text-slate-400">{{ c.student_number }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ c.section ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="c.type === 'confirmed' ? 'bg-[#EAF3DE] text-[#3B6D11]' : 'bg-[#FAEEDA] text-[#854F0B]'"
                            >
                                {{ c.type === 'confirmed' ? 'Confirmed' : 'Recheck' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 max-w-xs">{{ c.notes ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="c.status === 'resolved' ? 'bg-slate-100 text-slate-500' : 'bg-[#E6F1FB] text-[#003399]'"
                            >
                                {{ c.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">
                            {{ new Date(c.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) }}
                        </td>
                        <td class="px-4 py-3">
                            <button
                                v-if="c.status === 'pending'"
                                @click="resolve(c.id)"
                                class="text-xs text-[#003399] font-medium hover:underline"
                            >
                                Mark resolved
                            </button>
                        </td>
                    </tr>
                    <tr v-if="corrections.length === 0">
                        <td colspan="7" class="text-center text-slate-400 text-sm py-8">
                            Wala pang grade correction requests.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>