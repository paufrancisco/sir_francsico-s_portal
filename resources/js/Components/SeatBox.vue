<template>
    <button
        @click="$emit('click')"
        class="w-full h-full rounded-lg border-2 flex flex-col items-center justify-center p-2 gap-1 transition hover:border-[#003399]/50"
        :class="seat?.student ? 'border-[#003399]/20 bg-[#E6F1FB]' : 'border-slate-200 bg-slate-50'"
    >
        <template v-if="seat?.student">
            <div class="w-12 h-12 rounded-full overflow-hidden border border-white bg-slate-200 shrink-0">
                <img v-if="seat.student.photo_url" :src="seat.student.photo_url" class="w-full h-full object-cover" />
                <span v-else class="w-full h-full flex items-center justify-center text-slate-400 text-xs font-medium">
                    {{ initials(seat.student.full_name) }}
                </span>
            </div>
            <div class="text-xs font-semibold text-slate-800 leading-tight text-center break-words w-full px-1">
                {{ surname(seat.student.full_name) }}
            </div>
            <div class="text-[11px] text-slate-500 leading-tight text-center break-words w-full px-1 -mt-1">
                {{ firstName(seat.student.full_name) }}
            </div>
            <div class="text-[10px] text-[#003399] font-semibold">{{ seat.student.aura_points }} pts</div>
        </template>
        <span v-else class="text-slate-300 text-xs">Empty</span>
    </button>
</template>

<script setup>
defineProps({ seat: Object });
defineEmits(['click']);

const initials = (name) => {
    if (!name) return '?';
    return name.split(' ').filter(Boolean).slice(0, 2).map((n) => n[0].toUpperCase()).join('');
};

// full_name ay naka-store bilang "Apelido, Pangalan" — kinukuha ang bahaging
// bago ang comma bilang apelido/surname.
const surname = (name) => (name ? name.split(',')[0].trim() : '');

// Kinukuha ang bahaging pagkatapos ng comma bilang first name.
// May fallback kung walang comma sa laman ng full_name.
const firstName = (name) => {
    if (!name) return '';
    const parts = name.split(',');
    return parts.length > 1 ? parts[1].trim() : '';
};
</script>