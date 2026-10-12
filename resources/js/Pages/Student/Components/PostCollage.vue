<template>
    <div class="pp-collage" :class="{ 'pp-collage--edit': removable }" :data-l="resolved.l" :style="resolved.style">
        <div v-for="(img, i) in shown" :key="img.id ?? i" class="pp-cell" @click.stop="$emit('open', i)">
            <img :src="img.url" alt="" loading="lazy" draggable="false" />
            <div v-if="moreCount && i === shown.length - 1" class="pp-more">+{{ moreCount }}</div>
            <button v-if="removable" type="button" class="pp-x" aria-label="Remove photo" @click.stop="$emit('remove', img.id)">✕</button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    images: { type: Array, default: () => [] },
    layout: { type: String, default: 'auto' },
    removable: Boolean,
    maxShown: { type: Number, default: 5 },
});

defineEmits(['open', 'remove']);

const shown = computed(() => props.images.slice(0, props.maxShown));
const moreCount = computed(() => Math.max(0, props.images.length - props.maxShown));

// Pick the CSS layout from the photo count and the layout the author chose
const resolved = computed(() => {
    const n = shown.value.length;
    const lay = props.layout;
    if (n <= 1) return { l: 'single', style: {} };
    if (n === 2) return { l: lay === 'rows' || lay === 'feature-top' ? 'stack' : 'side', style: {} };
    if (lay === 'rows') return { l: 'stack', style: {} };
    if (n === 3 && lay === 'grid') return { l: 'cols3', style: {} };
    if (lay === 'feature-top') return { l: 'top', style: { '--cols': n - 1 } };
    if (lay === 'feature-left' || (lay === 'auto' && n === 3)) return { l: 'left', style: { '--rows': n - 1 } };
    if (n === 4) return { l: 'grid2', style: {} };
    if (n === 5) return { l: 'five', style: {} };
    return { l: 'grid3', style: {} };
});
</script>

<style scoped>
.pp-collage { display: grid; gap: 3px; overflow: hidden; border-radius: 12px; background: #0b1624; width: 100%; }
.pp-cell { position: relative; overflow: hidden; min-width: 0; min-height: 0; cursor: zoom-in; background: #13233a; }
.pp-cell img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
.pp-collage--edit .pp-cell { cursor: default; }
.pp-more { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.55); color: #fff; font-size: 1.6rem; font-weight: 700; }
.pp-x { position: absolute; top: 6px; right: 6px; width: 24px; height: 24px; border: 0; border-radius: 50%; background: rgba(0, 0, 0, 0.65); color: #fff; font-size: 11px; cursor: pointer; }
.pp-x:hover { background: rgba(207, 34, 46, 0.9); }

.pp-collage[data-l="single"] { grid-template-columns: 1fr; }
.pp-collage[data-l="single"] .pp-cell img { position: static; height: auto; max-height: 34rem; }

.pp-collage[data-l="side"] { grid-template-columns: 1fr 1fr; aspect-ratio: 16 / 10; }

.pp-collage[data-l="stack"] { grid-template-columns: 1fr; }
.pp-collage[data-l="stack"] .pp-cell { aspect-ratio: 16 / 8; }

.pp-collage[data-l="cols3"] { grid-template-columns: repeat(3, 1fr); aspect-ratio: 3 / 2; }

.pp-collage[data-l="left"] { grid-template-columns: 3fr 2fr; grid-template-rows: repeat(var(--rows), 1fr); aspect-ratio: 1 / 1; }
.pp-collage[data-l="left"] .pp-cell:first-child { grid-row: 1 / span var(--rows); }

.pp-collage[data-l="top"] { grid-template-columns: repeat(var(--cols), 1fr); grid-template-rows: 2fr 1fr; aspect-ratio: 1 / 1; }
.pp-collage[data-l="top"] .pp-cell:first-child { grid-column: 1 / -1; }

.pp-collage[data-l="grid2"] { grid-template-columns: 1fr 1fr; aspect-ratio: 1 / 1; }

.pp-collage[data-l="five"] { grid-template-columns: repeat(6, 1fr); grid-template-rows: 3fr 2fr; aspect-ratio: 1 / 1; }
.pp-collage[data-l="five"] .pp-cell:nth-child(-n+2) { grid-column: span 3; }
.pp-collage[data-l="five"] .pp-cell:nth-child(n+3) { grid-column: span 2; }

.pp-collage[data-l="grid3"] { grid-template-columns: repeat(3, 1fr); aspect-ratio: 3 / 2; }
</style>
