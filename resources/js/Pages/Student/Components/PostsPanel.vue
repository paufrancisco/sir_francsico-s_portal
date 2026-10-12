<template>
    <div class="ink-panel rounded-[1.75rem] p-5 lg:p-7 flex flex-col lg:flex-1 min-w-0">
        <div class="text-sm font-semibold text-white" style="font-family:var(--font-display);">Class posts · {{ sectionLabel }}</div>
        <p class="text-[11px] text-white/50 mb-4">Share photos and updates with your class.</p>

        <div class="mx-auto w-full max-w-xl space-y-4">
            <!-- Composer -->
            <div class="rounded-2xl p-3.5 bg-white/5 border border-white/10">
                <textarea
                    v-model="caption"
                    rows="2"
                    :maxlength="POST_MAX_CAPTION"
                    placeholder="What's on your mind?"
                    class="w-full resize-none rounded-xl px-3 py-2 text-sm text-white bg-[#121e30]/70 border border-white/15 focus:border-sky-400 focus:outline-none placeholder:text-white/40"
                ></textarea>

                <PostCollage
                    v-if="picked.length"
                    class="mt-2"
                    :images="picked"
                    :layout="effectiveLayout"
                    :max-shown="6"
                    removable
                    @remove="removeFile"
                />

                <div v-if="choices.length" class="flex flex-wrap items-center gap-1.5 mt-2">
                    <span class="text-[11px] text-white/50 mr-1">Layout</span>
                    <button
                        v-for="c in choices"
                        :key="c.key"
                        type="button"
                        class="px-2.5 py-1 rounded-full text-[11px] font-semibold transition"
                        :style="effectiveLayout === c.key
                            ? { background: 'var(--gold)', color: 'var(--navy-deep)' }
                            : { background: 'rgba(255,255,255,0.08)', color: 'rgba(255,255,255,0.7)' }"
                        @click="setLayout(c.key)"
                    >{{ c.label }}</button>
                </div>

                <p v-if="composeError" class="text-xs text-red-300 mt-2">{{ composeError }}</p>
                <p v-else-if="composeNotice" class="text-xs text-emerald-300 mt-2">{{ composeNotice }}</p>

                <div class="flex items-center gap-2 mt-3">
                    <label
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white cursor-pointer select-none"
                        :class="composing || picked.length >= POST_MAX_IMAGES ? 'opacity-50 pointer-events-none' : ''"
                    >
                        {{ composing ? 'Preparing...' : 'Add photos' }}
                        <input type="file" accept="image/*" multiple class="hidden" @change="onPick" />
                    </label>
                    <span class="text-[11px] text-white/40">{{ picked.length }}/{{ POST_MAX_IMAGES }}</span>
                    <span class="ml-auto text-[11px] text-white/40">{{ caption.length }}/{{ POST_MAX_CAPTION }}</span>
                    <button
                        type="button"
                        :disabled="posting || composing"
                        class="text-xs font-semibold px-4 py-1.5 rounded-lg disabled:opacity-50"
                        style="background:var(--gold);color:var(--navy-deep);"
                        @click="submitPost"
                    >{{ posting ? 'Posting...' : 'Post' }}</button>
                </div>
            </div>

            <!-- Feed -->
            <p v-if="status === 'loading' && !posts.length" class="text-xs text-white/50 text-center py-6">Loading posts...</p>
            <p v-else-if="status === 'error' && !posts.length" class="text-xs text-red-300 text-center py-6">Couldn't load the posts.</p>
            <p v-else-if="status === 'ready' && !posts.length" class="text-xs text-white/50 text-center py-6">No posts yet. Be the first to post.</p>

            <article v-for="p in posts" :key="p.id" class="rounded-2xl bg-white/5 border border-white/10">
                <header class="flex items-center gap-2.5 px-3.5 pt-3.5 pb-2">
                    <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center text-xs font-bold" style="background:var(--gold);color:var(--navy-deep);">
                        {{ (p.author || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1 leading-tight">
                        <div class="text-[13px] font-semibold text-white truncate">{{ p.author || 'Classmate' }}</div>
                        <div class="text-[11px] text-white/45">{{ timeAgo(p.created_at) }}</div>
                    </div>
                    <button v-if="(isTeacher || !p.is_teacher) && editing.id !== p.id" type="button" class="text-[11px] text-white/60 hover:text-white px-2 py-1" @click="startEdit(p)">Edit</button>
                    <button v-if="isTeacher" type="button" class="text-[11px] text-red-300 hover:text-red-200 px-2 py-1" @click="removePost(p)">Delete</button>
                </header>

                <div v-if="editing.id === p.id" class="px-3.5 pb-2">
                    <textarea
                        v-model="editing.text"
                        rows="3"
                        maxlength="500"
                        placeholder="Edit your caption"
                        class="w-full rounded-xl bg-white/10 border border-white/15 px-3 py-2 text-[13px] text-white placeholder-white/40 focus:outline-none focus:border-white/40"
                    ></textarea>
                    <p v-if="editing.error" class="text-[11px] text-red-300 mt-1">{{ editing.error }}</p>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" class="text-[11px] text-white/60 hover:text-white px-3 py-1" :disabled="editing.saving" @click="cancelEdit">Cancel</button>
                        <button type="button" class="text-[11px] font-semibold px-3 py-1 rounded-full disabled:opacity-50" style="background:var(--gold);color:var(--navy-deep);" :disabled="editing.saving" @click="saveEdit(p)">{{ editing.saving ? 'Saving...' : 'Save' }}</button>
                    </div>
                </div>
                <p v-else-if="p.caption" class="px-3.5 pb-2 text-[13px] text-white/90 whitespace-pre-line break-words">{{ p.caption }}</p>

                <div v-if="p.images.length" class="px-3.5 pb-2">
                    <PostCollage :images="p.images" :layout="p.layout" @open="(i) => openLightbox(p, i)" />
                </div>

                <footer class="px-3.5 pt-2 pb-3 mt-1 border-t border-white/10">
                    <div class="wall-react" :class="{ 'wall-react--open': pickerFor === p.id }" @mouseleave="closePicker">
                        <div class="wall-react__bar" role="menu">
                            <button
                                v-for="r in WALL_REACTIONS"
                                :key="r.key"
                                type="button"
                                class="wall-react__opt"
                                :class="{ 'wall-react__opt--on': p.my_reaction === r.key }"
                                :title="r.label"
                                :aria-label="r.label"
                                @click.stop="pickReaction(p, r.key)"
                            >{{ r.emoji }}</button>
                        </div>
                        <button
                            type="button"
                            class="pp-act"
                            :class="{ 'pp-act--on': p.liked }"
                            :title="likersTitle(p)"
                            :aria-label="p.liked ? 'Remove reaction' : 'React'"
                            @click.stop="onLikeClick(p)"
                            @touchstart.passive="pressStart(p)"
                            @touchend="pressEnd"
                            @touchcancel="pressEnd"
                            @contextmenu.prevent
                        >
                            <span v-if="p.liked">{{ reactionEmoji(p.my_reaction) }}</span>
                            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                            <span>{{ p.liked ? reactionLabel(p.my_reaction) : 'React' }}</span>
                            <span v-if="topReactions(p).length" class="pp-tops">{{ topReactions(p).join('') }}</span>
                            <span v-if="p.likes_count">{{ p.likes_count }}</span>
                        </button>
                    </div>
                </footer>
            </article>

            <button
                v-if="hasMore"
                type="button"
                :disabled="loadingMore"
                class="w-full text-xs font-semibold py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white disabled:opacity-50"
                @click="loadMore"
            >{{ loadingMore ? 'Loading...' : 'Load more' }}</button>
        </div>

        <!-- Photo viewer -->
        <Teleport to="body">
            <div v-if="lightbox.open" class="pp-lightbox" @click.self="closeLightbox">
                <button type="button" class="pp-lb-close" aria-label="Close" @click="closeLightbox">✕</button>
                <button v-if="lightbox.images.length > 1" type="button" class="pp-lb-nav pp-lb-prev" aria-label="Previous photo" @click.stop="stepLightbox(-1)">‹</button>
                <img :src="lightbox.images[lightbox.index]?.url" class="pp-lb-img" alt="" />
                <button v-if="lightbox.images.length > 1" type="button" class="pp-lb-nav pp-lb-next" aria-label="Next photo" @click.stop="stepLightbox(1)">›</button>
                <div v-if="lightbox.images.length > 1" class="pp-lb-count">{{ lightbox.index + 1 }} / {{ lightbox.images.length }}</div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { watch, onMounted, onBeforeUnmount } from 'vue';
import PostCollage from './PostCollage.vue';
import { WALL_REACTIONS, reactionEmoji, topReactions, likersTitle } from '../../../composables/useFreedomWall';
import { usePortalPosts, POST_MAX_CAPTION, POST_MAX_IMAGES, timeAgo } from '../../../composables/usePortalPosts';

const props = defineProps({
    active: Boolean,
    sectionId: { type: [Number, String], default: null },
    sectionLabel: { type: String, default: '' },
    isTeacher: Boolean,
    withAuth: { type: Function, required: true },
    creds: { type: Function, required: true },
    touchAuth: { type: Function, required: true },
});

const {
    posts, status, hasMore, loadingMore, reset, load, loadMore,
    caption, picked, composing, posting, composeError, composeNotice,
    choices, effectiveLayout, setLayout, addFiles, removeFile, submitPost,
    pickerFor, pressStart, pressEnd, closePicker, onLikeClick, pickReaction,
    lightbox, openLightbox, closeLightbox, stepLightbox, onKey,
    removePost, editing, startEdit, cancelEdit, saveEdit,
} = usePortalPosts({
    getSectionId: () => props.sectionId,
    withAuth: props.withAuth,
    creds: props.creds,
    touchAuth: props.touchAuth,
});

const reactionLabel = (key) => WALL_REACTIONS.find((r) => r.key === key)?.label ?? 'Love';

function onPick(e) {
    addFiles(e.target.files);
    e.target.value = '';
}

// A different section means a different feed
watch(() => props.sectionId, () => reset());

// Load when the tab is opened (and again when the section changes while it is open)
watch([() => props.active, () => props.sectionId], ([isActive]) => {
    if (isActive) load(true);
}, { immediate: true });

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<style scoped>
.pp-act {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 10px; border: 0; border-radius: 999px;
    font-size: 12px; font-weight: 600; color: rgba(255, 255, 255, 0.7);
    background: rgba(255, 255, 255, 0.06); cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.pp-act:hover { background: rgba(255, 255, 255, 0.12); color: #fff; }
.pp-act--on { color: var(--gold); }
.pp-tops { font-size: 12px; letter-spacing: -2px; }

.pp-lightbox {
    position: fixed; inset: 0; z-index: 90;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0, 0, 0, 0.88);
}
.pp-lb-img { max-width: 92vw; max-height: 88vh; object-fit: contain; border-radius: 8px; }
.pp-lb-close {
    position: absolute; top: 14px; right: 14px; width: 36px; height: 36px;
    border: 0; border-radius: 50%; background: rgba(255, 255, 255, 0.15); color: #fff; font-size: 14px; cursor: pointer;
}
.pp-lb-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 40px; height: 56px; border: 0; border-radius: 10px;
    background: rgba(255, 255, 255, 0.15); color: #fff; font-size: 28px; line-height: 1; cursor: pointer;
}
.pp-lb-prev { left: 12px; }
.pp-lb-next { right: 12px; }
.pp-lb-count {
    position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%);
    padding: 3px 10px; border-radius: 999px; background: rgba(0, 0, 0, 0.6); color: #fff; font-size: 12px;
}
</style>
