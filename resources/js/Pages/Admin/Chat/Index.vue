<template>
    <AdminLayout>
        <main class="max-w-3xl mx-auto px-6 lg:px-10 py-8 space-y-4">
            <div class="text-lg font-semibold text-slate-800">Student chats</div>

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <p v-if="students.length === 0" class="text-xs text-slate-400 px-4 py-6">
                    Wala pang nagtatanong.
                </p>
                <Link
                    v-for="s in students"
                    :key="s.id"
                    :href="`/paulo/chat/${s.id}`"
                    class="flex items-center justify-between px-4 py-3 border-t border-slate-100 first:border-t-0 hover:bg-slate-50"
                >
                    <div>
                        <div class="text-sm font-medium text-slate-800 flex items-center gap-2">
                            {{ s.name }}
                            <span v-if="s.needs_review" class="text-[10px] font-medium bg-[#FAEEDA] text-[#854F0B] px-2 py-0.5 rounded-full">
                                kailangan sagutin
                            </span>
                        </div>
                        <div class="text-xs text-slate-400 truncate max-w-xs">{{ s.last_message }}</div>
                    </div>
                    <div class="text-[11px] text-slate-400">{{ formatDate(s.last_at) }}</div>
                </Link>
            </div>
        </main>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ students: Array });

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
};
</script>