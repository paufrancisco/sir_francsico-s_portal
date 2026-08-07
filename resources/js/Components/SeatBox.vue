<template>
    <button
        @click="handleClick"
        class="w-full h-full rounded-lg border-2 flex flex-col items-center justify-center p-2 gap-1 transition hover:border-[#003399]/50 relative"
        :class="[
            seat?.student ? 'border-[#003399]/20 bg-[#E6F1FB]' : 'border-slate-200 bg-slate-50',
            selectMode && selected ? 'ring-2 ring-[#003399] ring-offset-1' : '',
        ]"
        :disabled="selectMode && !seat?.student"
    >
        <div
            v-if="selectMode && seat?.student"
            class="absolute top-1.5 left-1.5 w-4 h-4 rounded border flex items-center justify-center"
            :class="selected ? 'bg-[#003399] border-[#003399]' : 'bg-white border-slate-300'"
        >
            <svg v-if="selected" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
        </div>

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
const props = defineProps({
    seat: Object,
    selectMode: { type: Boolean, default: false },
    selected: { type: Boolean, default: false },
});
const emit = defineEmits(['click', 'toggle-select']);

const handleClick = () => {
    if (props.selectMode) {
        if (props.seat?.student) emit('toggle-select');
        return;
    }
    emit('click');
};

const initials = (name) => {
    if (!name) return '?';
    return name.split(' ').filter(Boolean).slice(0, 2).map((n) => n[0].toUpperCase()).join('');
};

const surname = (name) => (name ? name.split(',')[0].trim() : '');

const firstName = (name) => {
    if (!name) return '';
    const parts = name.split(',');
    return parts.length > 1 ? parts[1].trim() : '';
};
</script>