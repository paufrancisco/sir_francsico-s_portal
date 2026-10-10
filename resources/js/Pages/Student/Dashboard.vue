<template>
    <div
        class="portal-root min-h-screen relative overflow-hidden transition-colors duration-300"
        :class="isDarkMode ? 'dark' : ''"
    >
        <!-- Ambient background: paper grain + soft ink blobs -->
        <div class="pointer-events-none fixed inset-0 -z-10 aurora-bg">
            <div class="absolute inset-0 grain-layer"></div>
            <div class="absolute w-[28rem] h-[28rem] rounded-full blur-3xl opacity-[0.16] blob-a" style="background:#8A8D91;"></div>
            <div class="absolute w-[26rem] h-[26rem] rounded-full blur-3xl opacity-[0.18] blob-b" style="background:var(--gold);"></div>
        </div>

        <!-- Mobile top bar (hidden on lg, sidebar takes over) -->
        <header class="lg:hidden relative z-20 flex items-center justify-between px-5 pt-5">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0" style="background:var(--gold); color:var(--navy-deep); font-family:var(--font-display);">SF</div>
                <div class="leading-tight">
                    <div class="text-[15px] font-semibold tracking-tight text-[var(--text-heading)]" style="font-family:var(--font-display);">Sir Francisco</div>
                    <div class="text-[10px] text-[var(--text-muted)]">Class portal</div>
                </div>
            </div>
            <button
                @click="sidebarOpen = true"
                class="w-9 h-9 rounded-full flex items-center justify-center border text-[var(--text-heading)]"
                style="background:var(--surface); border-color:var(--surface-border);"
                aria-label="Open menu"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
        </header>

        <!-- Backdrop for mobile drawer -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="lg:hidden fixed inset-0 bg-black/40 z-30"></div>

        <!-- Sidebar -->
        <aside
            class="sidebar fixed inset-y-0 left-0 w-64 z-40 flex flex-col p-5 transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0" style="background:var(--gold); color:var(--navy-deep); font-family:var(--font-display);">SF</div>
                    <div class="leading-tight">
                        <div class="text-[15px] font-semibold tracking-tight text-white" style="font-family:var(--font-display);">Sir Francisco</div>
                        <div class="text-[10px] text-white/45">Class portal</div>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-white/60 hover:text-white" aria-label="Close menu">✕</button>
            </div>

            <nav class="flex-1 space-y-1.5">

                <!-- View rankings + section dropdown -->
                <div>
                    <button
                        @click="activeView = 'rankings'; sidebarOpen = false"
                        class="nav-item"
                        :class="activeView === 'rankings' ? 'nav-item--active' : ''"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/></svg>
                        <span>View rankings</span>
                    </button>

                    <div class="mt-1">
                        <button type="button" @click="sectionMenuOpen = !sectionMenuOpen" class="nav-item" :aria-expanded="sectionMenuOpen">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                            <span class="flex-1 text-left">Section</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 transition-transform" :class="sectionMenuOpen ? 'rotate-180' : ''"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div v-if="sectionMenuOpen" class="mt-1 ml-3 pl-3 border-l border-white/10">
                            <button
                                v-for="s in sections"
                                :key="s.id"
                                type="button"
                                @click="selectSection(s.id)"
                                class="nav-item nav-item--sub"
                                :style="s.id === activeSectionId ? 'color:var(--gold);font-weight:600' : ''"
                            >
                                <span class="flex-1 text-left truncate">{{ s.subject ? `${s.subject} · ${s.name}` : s.name }}</span>
                                <svg v-if="s.id === activeSectionId" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="shrink-0"><path d="M20 6 9 17l-5-5"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- View grades (opens modal) -->
                <button @click="openGradesModal(); sidebarOpen = false" class="nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6Z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
                    <span>View grades</span>
                </button>

                <!-- Settings -> Change password (modal) -->
                <div>
                    <button @click="settingsOpen = !settingsOpen" class="nav-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                        <span class="flex-1 text-left">Settings</span>
                        <svg
                            width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            class="transition-transform"
                            :class="settingsOpen ? 'rotate-180' : ''"
                        ><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div v-if="settingsOpen" class="mt-1 ml-3 pl-3 border-l border-white/10">
                        <button @click="openChangePasswordModal(); sidebarOpen = false" class="nav-item nav-item--sub">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>Change password</span>
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Sidebar footer: clock + dark mode -->
            <div class="pt-4 mt-4 border-t border-white/10 flex items-center justify-between">
                <div class="text-[11px] font-semibold tabular-nums text-white/60" style="font-family:var(--font-mono);">{{ liveClock }}</div>
                <button
                    @click="toggleDarkMode"
                    class="w-9 h-9 rounded-full flex items-center justify-center transition hover:bg-white/10"
                    style="color:var(--gold);"
                    aria-label="Toggle dark mode"
                >
                    <svg v-if="isDarkMode" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                </button>
            </div>
        </aside>

        <!-- Main content (offset for sidebar on lg) -->
        <main class="lg:pl-64 relative z-10">
            <div class="max-w-[1500px] mx-auto px-5 lg:px-10 py-6 lg:min-h-screen lg:flex lg:flex-col lg:justify-center">

                <h1 class="typewriter-line" aria-label="Welcome to Francisco's Portal"><span class="typewriter">Welcome to Francisco's Portal</span></h1>
                <div class="w-full flex flex-col lg:flex-row gap-5">
                <!-- Class ledger: top 1 (left) + top 10 (right) -->
                <div v-show="activeView === 'rankings'" class="ink-panel rounded-[1.75rem] p-5 lg:p-7 flex flex-col lg:flex-1 min-w-0">
                    <div class="text-sm font-semibold text-white mb-5" style="font-family:var(--font-display);">Class ledger · {{ activeSectionLabel }}</div>

                    <p v-if="filteredStudents.length === 0" class="text-xs text-white/35 text-center py-8">
                        No grades have been recorded yet for this section.
                    </p>

                    <div v-else class="flex-1 min-h-0 grid grid-cols-1 md:grid-cols-5 gap-8" @click="slideStopped = true">
                        <!-- Photo slideshow: top 10 -->
                        <div class="md:col-span-2 flex flex-col items-center text-center min-h-0">
                            <div class="rank-ribbon" :class="rankRibbonClass(slideIndex)">TOP {{ currentSlide.rank }}</div>
                            <div
                                class="relative w-full max-w-[17rem] h-[26rem] mx-auto shrink-0 rounded-2xl shadow-lg fx-wrap"
                                :class="'fx-' + frameFor(currentSlide)"
                                @mouseenter="slidePaused = true"
                                @mouseleave="slidePaused = false"
                            >
                                <div class="absolute inset-0 rounded-2xl overflow-hidden">
                                <Transition name="slide">
                                    <div
                                        :key="slideIndex"
                                        class="absolute inset-0 flex items-center justify-center text-[8rem] font-bold"
                                        style="font-family:var(--font-display);"
                                        :style="!currentSlide.photo_url ? { background: 'var(--gold)', color: 'var(--navy-deep)' } : {}"
                                    >
                                        <img
                                            v-if="currentSlide.photo_url"
                                            :src="currentSlide.photo_url"
                                            :alt="currentSlide.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else>{{ initials(currentSlide.name) }}</span>
                                    </div>
                                </Transition>
                                </div>

                                <button type="button" @click="step(-1)" class="slide-btn absolute left-3 top-1/2 -translate-y-1/2 z-20" aria-label="Previous photo">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 18l-6-6 6-6"/></svg>
                                </button>
                                <button type="button" @click="step(1)" class="slide-btn absolute right-3 top-1/2 -translate-y-1/2 z-20" aria-label="Next photo">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
                                </button>

                                <!-- Frame decoration (overlaps the photo edge) -->
                                <div class="fx-deco" aria-hidden="true">
                                    <b></b>
                                    <u class="wing wl"></u><u class="wing wr"></u><u class="gem"></u>
                                    <i class="c1"></i><i class="c2"></i><i class="c3"></i><i class="c4"></i>
                                    <i class="s1"></i><i class="s2"></i>
                                </div>

                                <!-- Change photo frame -->
                                <button type="button" @click.stop="openFrameModal()" class="slide-btn absolute right-3 top-3 z-20" title="Change photo frame" aria-label="Change photo frame">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
                                </button>

                                <!-- Messages for the current top (changes with each slide) -->
                                <div class="msg-panel absolute left-3 right-3 bottom-11 z-10 rounded-xl text-left" :class="currentMessages.items.length ? 'p-3 shadow-lg' : 'p-0 pointer-events-none'" :style="currentMessages.items.length ? 'background: rgba(6,22,38,0.94); border: 1px solid rgba(255,255,255,0.16);' : ''">
                                    <div class="flex items-center justify-end gap-3" :class="currentMessages.items.length ? 'mb-2' : ''">
                                        <button
                                            type="button"
                                            @click="openMessageModal()"
                                            class="slide-btn shrink-0 pointer-events-auto"
                                            title="Post a message"
                                            aria-label="Post a message for this classmate"
                                        >
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>
                                    </div>

                                    <div class="space-y-2">

                                        <div v-for="m in currentMessages.items.slice(0, 2)" :key="m.id" class="msg-bubble">
                                            <div class="flex items-start justify-between gap-2">
                                                <div></div>
                                                <div v-if="editingMessageId !== m.id" class="msg-actions shrink-0">
                                                    <button
                                                        type="button"
                                                        @click="openEditForm(m)"
                                                        class="msg-action-btn"
                                                        title="Edit"
                                                        aria-label="Edit message"
                                                    >
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="openDeleteForm(m)"
                                                        class="msg-action-btn"
                                                        title="Delete"
                                                        aria-label="Delete message"
                                                    >
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Normal display -->
                                            <div v-if="editingMessageId !== m.id" class="text-[15px] text-white leading-snug quote-text">{{ m.body }}</div>

                                            <!-- Inline edit/delete confirm form -->
                                            <div v-else class="mt-1 space-y-1.5">
                                                <textarea
                                                    v-if="editAction === 'edit'"
                                                    v-model="editForm.body"
                                                    rows="2"
                                                    :maxlength="MESSAGE_MAX_LENGTH"
                                                    class="portal-input text-xs"
                                                    style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #fff;"
                                                ></textarea>
                                                <p v-else class="text-sm text-white/90">Delete this message? Confirm your login to proceed.</p>

                                                <input v-model="editForm.student_number" type="text" placeholder="Student number" class="portal-input text-xs" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #fff;" />
                                                <input v-model="editForm.password" type="password" placeholder="Password" class="portal-input text-xs" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #fff;" />

                                                <p v-if="editError" class="text-xs text-red-400">{{ editError }}</p>

                                                <div class="flex gap-2">
                                                    <button
                                                        type="button"
                                                        @click="editAction === 'edit' ? submitEditMessage(m, currentSlide) : submitDeleteMessage(m, currentSlide)"
                                                        :disabled="editLoading"
                                                        class="flex-1 text-xs font-semibold py-1.5 rounded-lg disabled:opacity-50"
                                                        :style="editAction === 'delete' ? 'background:#cf222e; color:#fff;' : 'background:var(--gold); color:var(--navy-deep);'"
                                                    >
                                                        {{ editLoading ? 'Processing...' : (editAction === 'edit' ? 'Save' : 'Delete') }}
                                                    </button>
                                                    <button type="button" @click="cancelEditForm" class="text-xs text-white/85 px-2">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1.5 rounded-full bg-black/30 px-2.5 py-1.5">
                                    <button
                                        v-for="(s, i) in slides"
                                        :key="s.name"
                                        type="button"
                                        @click="goToSlide(i)"
                                        class="h-1.5 rounded-full transition-all"
                                        :class="i === slideIndex ? 'w-4 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/80'"
                                        :aria-label="'Show rank ' + (i + 1)"
                                    ></button>
                                </div>
                            </div>

                            <div class="text-base font-semibold mt-3 text-white" style="font-family:var(--font-display);">{{ currentSlide.name }}</div>
                            <div class="text-2xl font-semibold mt-1 tabular-nums" style="font-family:var(--font-mono); color:var(--gold);">{{ currentSlide.grade }}</div>
                        </div>

                        <!-- Right column: top 10 list -->
                        <div class="md:col-span-3 w-full flex flex-col justify-center gap-4 min-h-0">

                            <!-- Top 10 list (click a row to jump to that photo) -->
                            <div class="flex-1 min-h-0 flex flex-col justify-between gap-0.5">
                                <button
                                    v-for="(s, i) in slides"
                                    :key="s.name"
                                    type="button"
                                    @click="goToSlide(i)"
                                    class="ledger-row ledger-row--btn w-full py-1.5 px-2 rounded-lg text-left"
                                    :class="i === slideIndex ? 'ledger-row--current' : ''"
                                >
                                    <span class="rank-num">{{ s.rank }}</span>
                                    <div class="w-6 h-6 rounded-full overflow-hidden shrink-0 flex items-center justify-center text-[10px] font-semibold" :style="!s.photo_url ? { background: 'var(--gold)', color: 'var(--navy-deep)' } : {}">
                                        <img v-if="s.photo_url" :src="s.photo_url" :alt="s.name" class="w-full h-full object-cover" />
                                        <span v-else>{{ initials(s.name) }}</span>
                                    </div>
                                    <div class="text-xs text-white/90 shrink-0">{{ s.name }}</div>
                                    <span class="leader"></span>
                                    <div class="text-xs font-semibold shrink-0 tabular-nums" style="font-family:var(--font-mono); color:var(--gold);">{{ s.grade }}</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Freedom wall: separate panel on the right -->
                <aside class="wall-aside ink-panel rounded-[1.75rem] p-5 lg:w-[22rem] xl:w-[25rem] shrink-0 flex flex-col gap-3 min-h-[22rem]">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-white" style="font-family:var(--font-display);">Freedom Wall</div>
                            <div class="text-[11px] text-white/60 truncate">{{ activeSectionLabel }} · anonymous notes</div>
                        </div>
                        <button type="button" @click.stop="openWallModal()" class="text-xs font-semibold px-3 py-1.5 rounded-lg shrink-0" style="background:var(--gold);color:var(--navy-deep);">+ Add a note</button>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto rounded-xl">
                        <div class="wall-board h-full" :style="{ minHeight: Math.max(320, Math.ceil(wallPosts.length / 2) * 150 + 90) + 'px' }">
                            <p v-if="!wallPosts.length" class="absolute inset-0 flex items-center justify-center text-xs text-white/80 text-center px-4">
                                {{ wallStatus === 'error' ? "Couldn't load the wall." : wallStatus === 'loading' ? 'Loading notes...' : 'No notes yet. Be the first to post.' }}
                            </p>
                            <div v-for="(p, i) in wallPosts" :key="p.id" class="wall-note" :style="stickerStyle(p, i, wallPosts.length)">
                                <span class="wall-tape"></span>
                                <p class="wall-text">{{ p.body }}</p>
                                <div class="wall-foot">
                                    <button type="button" class="wall-like" :class="p.liked ? 'wall-like--on' : ''" @click.stop="toggleLike(p)" :aria-label="p.liked ? 'Unlike' : 'Like'">
                                        <svg width="14" height="14" viewBox="0 0 24 24" :fill="p.liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                        <span>{{ p.likes_count || 0 }}</span>
                                    </button>
                                    <button v-if="p.can_delete" type="button" class="wall-del" title="Delete note" aria-label="Delete note" @click.stop="deletePost(p)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                                    </button>
                                    <div class="wall-date">{{ new Date(p.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
                </div>
            </div>
        </main>

        <div class="fixed bottom-6 right-6 z-50">
            <div
                v-if="chatOpen"
                class="mb-3 w-72 surface-card overflow-hidden flex flex-col shadow-xl"
                style="max-height: 420px; border-radius: 1.5rem;"
            >
                <div class="text-white text-sm font-semibold px-4 py-3 flex items-center justify-between shrink-0" style="background:var(--navy); font-family:var(--font-display);">
                    Ask Sir Francisco
                    <button @click="chatOpen = false" class="text-white/70 hover:text-white">✕</button>
                </div>

                <!-- Force change password (first login) -->
                <div v-if="chatMustChangePassword" class="p-4 space-y-2">
                    <p class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Change your password first</p>
                    <p class="text-xs text-[var(--text-muted)]">This is your first login — you need to change your password before you can ask a question.</p>
                    <div class="relative">
                        <input v-model="newPasswordForm.new_password" :type="showNewPassword ? 'text' : 'password'" placeholder="New password" class="portal-input pr-9" />
                        <button type="button" @click="showNewPassword = !showNewPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                            <svg v-if="showNewPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="relative">
                        <input v-model="newPasswordForm.confirm_password" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Confirm new password" class="portal-input pr-9" />
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                            <svg v-if="showConfirmPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p v-if="passwordChangeError" class="text-xs text-red-500">{{ passwordChangeError }}</p>
                    <div class="flex gap-2">
                        <button @click="submitPasswordChange" :disabled="passwordChangeLoading" class="flex-1 text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50" style="background:var(--navy);">
                            {{ passwordChangeLoading ? 'Updating...' : 'Update password' }}
                        </button>
                        <button @click="cancelPasswordChange" class="text-xs text-[var(--text-muted)] px-3">Cancel</button>
                    </div>
                </div>

                <!-- Sign-in -->
                <div v-else-if="!chatStudent" class="p-4 space-y-2">
                    <p class="text-xs text-[var(--text-muted)]">You need to sign in first to ask a question.</p>
                    <input v-model="chatLogin.student_number" placeholder="Student number" class="portal-input" />
                    <div class="relative">
                        <input v-model="chatLogin.password" :type="showChatLoginPassword ? 'text' : 'password'" placeholder="Password" class="portal-input pr-9" />
                        <button type="button" @click="showChatLoginPassword = !showChatLoginPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                            <svg v-if="showChatLoginPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p v-if="chatLoginError" class="text-xs text-red-500">{{ chatLoginError }}</p>
                    <button @click="signInChat" :disabled="chatLoginLoading" class="w-full text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50" style="background:var(--navy);">
                        {{ chatLoginLoading ? 'Checking...' : 'Sign in to chat' }}
                    </button>
                </div>

                <!-- Chat -->
                <template v-else>
                    <div ref="chatScrollEl" class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
                        <div
                            v-for="m in chatMessages"
                            :key="m.id"
                            :class="m.sender === 'student' ? 'ml-auto text-white' : 'mr-auto'"
                            :style="m.sender === 'student' ? { background: 'var(--navy)' } : { background: 'var(--chip-bg)', color: 'var(--text-body)' }"
                            class="max-w-[85%] text-xs rounded-xl px-3 py-2 whitespace-pre-wrap"
                        >
                            <div v-if="m.sender !== 'student'" class="text-[10px] font-medium mb-0.5" :style="{ color: m.sender === 'admin' ? 'var(--navy)' : 'var(--text-muted)' }">
                                {{ m.sender === 'admin' ? 'Sir Francisco' : 'AI Assistant' }}
                            </div>
                            {{ m.body }}
                        </div>
                        <p v-if="chatMessages.length === 0" class="text-xs text-[var(--text-muted)] text-center py-4">
                            Go ahead and ask — the AI assistant will respond right away.
                        </p>
                        <div v-if="chatSending" class="mr-auto max-w-[85%] text-xs rounded-xl px-3 py-2.5 flex items-center gap-1" style="background:var(--chip-bg);">
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                        </div>
                    </div>
                    <form @submit.prevent="sendChatMessage" class="p-3 border-t border-[var(--surface-border-soft)] flex gap-2 shrink-0">
                        <input
                            v-model="chatInput"
                            type="text"
                            placeholder="Type your question..."
                            class="portal-input flex-1"
                            :disabled="chatSending"
                        />
                        <button type="submit" :disabled="chatSending || !chatInput.trim()" class="text-white text-sm font-semibold px-3 py-2 rounded-xl disabled:opacity-50" style="background:var(--navy);">
                            Send
                        </button>
                    </form>
                </template>
            </div>

            <button
                @click="chatOpen = !chatOpen"
                class="w-14 h-14 rounded-full text-white shadow-lg hover:shadow-xl hover:scale-105 transition transform flex items-center justify-center"
                style="background:var(--navy);"
            >
                <svg v-if="!chatOpen" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-4-1L3 20l1-5.5A8.38 8.38 0 0 1 3 11.5 8.5 8.5 0 0 1 11.5 3 8.38 8.38 0 0 1 21 11.5Z"/></svg>
                <svg v-else width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>

            <!-- View my grades modal -->
            <div v-if="gradesModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
                <div class="surface-card w-full max-w-sm shadow-xl p-5" style="border-radius: 1.5rem;">

                    <!-- Force change password (first login) -->
                    <template v-if="gradesMustChangePassword">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Change your password first</div>
                            <button @click="closeGradesModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]">✕</button>
                        </div>
                        <p class="text-xs text-[var(--text-muted)] mb-3">This is your first login — you need to change your password before you can view your grades.</p>
                        <div class="space-y-3">
                            <div class="relative">
                                <input v-model="newPasswordForm.new_password" :type="showNewPassword ? 'text' : 'password'" placeholder="New password" class="portal-input pr-9" />
                                <button type="button" @click="showNewPassword = !showNewPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                    <svg v-if="showNewPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="relative">
                                <input v-model="newPasswordForm.confirm_password" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Confirm new password" class="portal-input pr-9" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                    <svg v-if="showConfirmPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <p v-if="passwordChangeError" class="text-xs text-red-500">{{ passwordChangeError }}</p>
                            <div class="flex gap-2">
                                <button
                                    @click="submitPasswordChange"
                                    :disabled="passwordChangeLoading"
                                    class="flex-1 text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50"
                                    style="background:var(--navy);"
                                >
                                    {{ passwordChangeLoading ? 'Updating...' : 'Update password' }}
                                </button>
                                <button @click="cancelPasswordChange" class="text-xs text-[var(--text-muted)] px-3">Cancel</button>
                            </div>
                        </div>
                    </template>

                    <!-- Sign-in form -->
                    <template v-else-if="!gradesResult">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">View my grades</div>
                            <button @click="closeGradesModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]">✕</button>
                        </div>
                        <form @submit.prevent="submitGradesLogin" class="space-y-3">
                            <input
                                v-model="gradesForm.student_number"
                                type="text"
                                placeholder="Student number"
                                class="portal-input"
                            />
                            <div class="relative">
                                <input
                                    v-model="gradesForm.password"
                                    :type="showGradesLoginPassword ? 'text' : 'password'"
                                    placeholder="Password"
                                    class="portal-input pr-9"
                                />
                                <button type="button" @click="showGradesLoginPassword = !showGradesLoginPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                    <svg v-if="showGradesLoginPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <p v-if="gradesError" class="text-xs text-red-500">{{ gradesError }}</p>
                            <button
                                type="submit"
                                :disabled="gradesLoading"
                                class="w-full text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50"
                                style="background:var(--navy);"
                            >
                                {{ gradesLoading ? 'Checking...' : 'Sign in' }}
                            </button>
                        </form>
                    </template>

                    <!-- Grades result -->
                    <template v-else>
                        <div class="flex items-center justify-between mb-1">
                            <div>
                                <div class="text-[10px] uppercase tracking-[0.14em] text-[var(--text-muted)]">Report of grades</div>
                                <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">{{ gradesResult.name }}</div>
                            </div>
                            <button @click="closeGradesModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]">✕</button>
                        </div>

                        <!-- Period tabs + loading indicator -->
                        <div class="flex items-center gap-2 mb-3">
                            <div class="flex items-center gap-1 rounded-lg p-1 w-fit" style="background: rgba(255,255,255,0.08);">
                                <button
                                    v-for="p in periods"
                                    :key="p.value"
                                    @click="switchGradesPeriod(p.value)"
                                    :disabled="gradesLoading"
                                    class="text-xs font-medium px-3 py-1 rounded-md transition disabled:opacity-50"
                                    :style="gradesPeriod === p.value
                                        ? 'background: rgba(255,255,255,0.15); color: #ffffff !important;'
                                        : 'color: rgba(255,255,255,0.5) !important;'"
                                >
                                    {{ p.label }}
                                </button>
                            </div>
                            <svg
                                v-if="gradesLoading"
                                width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin"
                                style="color: rgba(255,255,255,0.6);"
                            >
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <!-- Content area with fade + spinner overlay while loading -->
                        <div class="relative" style="min-height: 80px;">
                            <div :class="gradesLoading ? 'opacity-30 pointer-events-none transition' : 'transition'">
                                <p v-if="gradesResult.items.length === 0" class="text-xs text-[var(--text-muted)] py-4">
                                    You don't have any recorded grades yet.
                                </p>

                                <div v-else class="mt-3 border-t border-[var(--surface-border-soft)]">
                                    <div
                                        v-for="item in gradesResult.items"
                                        :key="item.category + item.title"
                                        class="ledger-row py-2 text-sm border-b border-[var(--surface-border-soft)]"
                                    >
                                        <span class="text-[var(--text-body)] shrink-0">{{ item.title }}</span>
                                        <span class="leader leader--light"></span>

                                        <template v-if="gradesResult.scores[item.category + '|' + item.title]">
                                            <!-- inline editing this row -->
                                            <template v-if="showRecheckForm && editingItemKey === item.category + '|' + item.title">
                                                <input
                                                    v-model="editDraftScore"
                                                    type="number" step="0.01" min="0"
                                                    class="w-14 text-xs text-right rounded px-1.5 py-0.5"
                                                    style="border: 1px solid var(--surface-border); background: var(--surface); color: var(--text-body);"
                                                />
                                                <span class="text-xs text-[var(--text-muted)] shrink-0">/{{ gradesResult.scores[item.category + '|' + item.title].max_score }}</span>
                                                <button type="button" @click="confirmEditItem(item)" class="shrink-0 hover:opacity-70" style="color: var(--teal, #1a7f37);" title="Confirm">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                                </button>
                                                <button type="button" @click="cancelEditItem" class="shrink-0 text-[var(--text-muted)] hover:opacity-70" title="Cancel">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                </button>
                                            </template>

                                            <!-- already has a pending edit — show original → proposal comparison -->
                                            <template v-else-if="showRecheckForm && editedItems[item.category + '|' + item.title]">
                                                <span class="text-xs text-[var(--text-muted)] line-through shrink-0" style="font-family:var(--font-mono);">
                                                    {{ gradesResult.scores[item.category + '|' + item.title].score }}
                                                </span>
                                                <span class="text-xs text-[var(--text-muted)] shrink-0">→</span>
                                                <span class="font-medium text-xs shrink-0 tabular-nums" style="font-family:var(--font-mono); color: #cf222e;">
                                                    {{ editedItems[item.category + '|' + item.title].claimed_score }}/{{ gradesResult.scores[item.category + '|' + item.title].max_score }}
                                                </span>
                                                <button type="button" @click="startEditItem(item)" class="shrink-0 text-[var(--text-muted)] hover:text-[var(--text-body)]" title="Edit again">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                </button>
                                                <button type="button" @click="removeEditedItem(item)" class="shrink-0 text-[var(--text-muted)] hover:opacity-70" title="Undo">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                </button>
                                            </template>

                                            <!-- normal display -->
                                            <template v-else>
                                                <span class="font-medium text-[var(--text-body)] shrink-0 tabular-nums" style="font-family:var(--font-mono);">
                                                    {{ gradesResult.scores[item.category + '|' + item.title].score }}/{{ gradesResult.scores[item.category + '|' + item.title].max_score }}
                                                </span>
                                                <button v-if="showRecheckForm" type="button" @click="startEditItem(item)" class="shrink-0 text-[var(--text-muted)] hover:text-[var(--text-body)]" title="Edit">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                </button>
                                            </template>
                                        </template>

                                        <span v-else class="text-[var(--text-muted)] shrink-0">—</span>
                                    </div>
                                </div>


                                <div v-if="gradesResult.breakdown" class="mt-3 pt-3 border-t border-[var(--surface-border-soft)] space-y-1.5">
                                    <div
                                        v-for="cat in gradesResult.breakdown"
                                        :key="cat.category"
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="text-[var(--text-secondary)]">{{ cat.label }} ({{ cat.weight_percent }}%)</span>
                                        <span class="font-medium text-[var(--text-body)] tabular-nums" style="font-family:var(--font-mono);">
                                            <template v-if="cat.avg_percent !== null">
                                                {{ cat.avg_percent }}% → {{ cat.contribution }} pts
                                            </template>
                                            <span v-else class="text-[var(--text-muted)]">no grade</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="gradesLoading" class="absolute inset-0 flex items-center justify-center">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin" style="color: rgba(255,255,255,0.7);">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                            <span class="text-sm font-semibold" style="color: #3b82f6 !important;">Total</span>
                            <span class="text-lg font-bold" style="color: #3b82f6 !important;">{{ gradesResult.total_percentage }}%</span>
                        </div>

                        <!-- Confirm / Recheck actions -->
                        <div class="mt-4 pt-3 border-t border-[var(--surface-border-soft)]">

                            <!-- deadline notice -->
                            <div v-if="gradesResult.correction_locked && correctionUiState !== 'pending'" class="text-xs mb-3" style="color:#cf222e;">
                                The deadline for grade correction requests has passed{{ gradesResult.correction_deadline ? ' (' + formatEventDate(gradesResult.correction_deadline) + ')' : '' }}. Please get in touch with Sir Francisco directly.
                            </div>

                            <!-- pending correction: summary + cancel/edit -->
                            <template v-if="correctionUiState === 'pending'">
                                <div class="text-xs font-semibold mb-2" style="color: var(--gold);">You have a pending recheck request</div>
                                <div class="space-y-1 text-xs mb-2">
                                    <div v-for="edit in gradesResult.pending_correction.edited_items" :key="edit.category + edit.title" class="flex items-center justify-between">
                                        <span class="text-[var(--text-secondary)]">{{ edit.title }}</span>
                                        <span class="tabular-nums" style="font-family:var(--font-mono);">
                                            <span class="text-[var(--text-muted)] line-through">{{ gradesResult.scores[edit.category + '|' + edit.title]?.score }}</span>
                                            <span class="text-[var(--text-muted)]">→</span>
                                            <span class="font-medium" style="color:var(--text-body);">{{ edit.claimed_score }}</span>
                                        </span>
                                    </div>
                                </div>
                                <p v-if="gradesResult.pending_correction.notes" class="text-xs text-[var(--text-secondary)] mb-2">{{ gradesResult.pending_correction.notes }}</p>
                                <a v-if="gradesResult.pending_correction.attachment_url" :href="gradesResult.pending_correction.attachment_url" target="_blank" class="text-xs hover:underline" style="color:var(--gold);">View attachment</a>
                                <div class="flex gap-2 mt-3">
                                    <button
                                        v-if="!gradesResult.correction_locked"
                                        @click="startEditExistingCorrection"
                                        class="flex-1 border text-[var(--text-body)] text-xs font-semibold py-2 rounded-xl"
                                        style="border-color:var(--surface-border);"
                                    >
                                        Edit again
                                    </button>
                                    <button
                                        @click="cancelCorrection"
                                        :disabled="correctionLoading"
                                        class="flex-1 text-xs font-semibold py-2 rounded-xl disabled:opacity-50"
                                        style="color:#cf222e; border:1px solid rgba(207,34,46,0.4);"
                                    >
                                        {{ correctionLoading ? 'Cancelling...' : 'Cancel' }}
                                    </button>
                                </div>
                            </template>

                            <!-- idle: no active correction for this period -->
                            <div v-else-if="correctionUiState === 'idle' && !gradesResult.correction_locked" class="flex gap-2">
                                <button
                                    @click="submitCorrection('confirmed')"
                                    :disabled="correctionLoading"
                                    class="flex-1 text-white text-xs font-semibold py-2 rounded-xl disabled:opacity-50"
                                    style="background:var(--navy);"
                                >
                                    My grades are correct
                                </button>
                                <button
                                    @click="showRecheckForm = true"
                                    class="flex-1 border text-[var(--text-body)] text-xs font-semibold py-2 rounded-xl"
                                    style="border-color:var(--surface-border);"
                                >
                                    Something's wrong, recheck
                                </button>
                            </div>

                            <!-- editing: composing / updating a recheck request -->
                            <div v-else-if="correctionUiState === 'editing'" class="space-y-2">
                                <p class="text-xs text-[var(--text-muted)]">Click the pencil icon on the incorrect item to edit the score.</p>
                                <textarea
                                    v-model="recheckNotes"
                                    rows="2"
                                    placeholder="Additional explanation (optional)"
                                    class="portal-input text-xs"
                                ></textarea>

                                <div>
                                    <label class="text-xs text-[var(--text-secondary)] block mb-1">
                                        Proof (image, max 10MB) <span v-if="!hasExistingAttachment">*</span>
                                    </label>
                                    <input type="file" accept="image/*" @change="onAttachmentChange" class="text-xs" />
                                    <p v-if="correctionAttachment" class="text-xs text-[var(--text-muted)] mt-1">{{ correctionAttachment.name }}</p>
                                    <p v-else-if="hasExistingAttachment" class="text-xs text-[var(--text-muted)] mt-1">You already have an existing attachment — you can replace it or leave it as is.</p>
                                    <p v-if="correctionAttachmentError" class="text-xs text-red-500 mt-1">{{ correctionAttachmentError }}</p>
                                </div>

                                <div v-if="Object.keys(editedItems).length > 0" class="p-2.5 rounded-xl text-xs" style="background: rgba(207,34,46,0.08); border: 1px solid rgba(207,34,46,0.25);">
                                    <div class="font-semibold mb-1" style="color: #cf222e;">Changes for this recheck request:</div>
                                    <div v-for="edit in Object.values(editedItems)" :key="edit.category + edit.title" class="flex items-center justify-between py-0.5">
                                        <span class="text-[var(--text-secondary)]">{{ edit.title }}</span>
                                        <span class="tabular-nums" style="font-family:var(--font-mono);">
                                            <span class="text-[var(--text-muted)] line-through">{{ gradesResult.scores[edit.category + '|' + edit.title]?.score }}</span>
                                            <span class="text-[var(--text-muted)]">→</span>
                                            <span class="font-medium" style="color:var(--text-body);">{{ edit.claimed_score }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        @click="submitCorrection('recheck')"
                                        :disabled="correctionLoading"
                                        class="flex-1 text-white text-xs font-semibold py-2 rounded-xl disabled:opacity-50"
                                        style="background:var(--navy);"
                                    >
                                        {{ correctionLoading ? 'Processing...' : 'Submit recheck' }}
                                    </button>
                                    <button
                                        @click="cancelEditingRecheckForm"
                                        class="text-xs text-[var(--text-muted)] px-3"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <p v-if="correctionSuccessMessage" class="text-xs font-medium mt-2" style="color: var(--teal, #1a7f37);">{{ correctionSuccessMessage }}</p>
                            <p v-if="correctionError" class="text-xs text-red-500 mt-2">{{ correctionError }}</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Freedom wall modal (sign-in required) -->
            <div v-if="wallModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
                <div class="surface-card w-full max-w-sm shadow-xl p-5" style="border-radius: 1.5rem;">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Add a note</div>
                        <button @click="closeWallModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]" aria-label="Close">✕</button>
                    </div>
                    <p class="text-xs text-[var(--text-muted)] mb-3">Sign in to post. Your name is never shown on the wall.</p>
                    <form @submit.prevent="submitWallPost(activeSectionId)" class="space-y-3">
                        <input v-model="wallForm.student_number" type="text" placeholder="Student number" class="portal-input" />
                        <div class="flex items-center gap-2">
                            <input v-model="wallForm.password" :type="showWallPassword ? 'text' : 'password'" placeholder="Password" class="portal-input" />
                            <button type="button" @click="showWallPassword = !showWallPassword" class="text-xs text-[var(--text-muted)] shrink-0">{{ showWallPassword ? 'Hide' : 'Show' }}</button>
                        </div>
                        <textarea v-model="wallForm.body" :maxlength="WALL_MAX_LENGTH" rows="4" placeholder="Write your note..." class="portal-input"></textarea>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button v-for="c in WALL_COLORS" :key="c.key" type="button" @click="wallForm.color = c.key" :title="c.key" :aria-label="c.key" class="w-6 h-6 rounded-md" :class="wallForm.color === c.key ? 'ring-2 ring-[var(--navy)] ring-offset-1' : ''" :style="{ background: c.bg }"></button>
                            </div>
                            <span class="text-[11px] text-[var(--text-muted)]">{{ wallForm.body.length }} / {{ WALL_MAX_LENGTH }}</span>
                        </div>
                        <p v-if="wallError" class="text-xs" style="color: var(--coral);">{{ wallError }}</p>
                        <p v-if="wallSuccess" class="text-xs font-medium" style="color: var(--teal, #1a7f37);">{{ wallSuccess }}</p>
                        <div class="flex gap-2">
                            <button type="submit" :disabled="wallLoading" class="flex-1 text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-60" style="background:var(--navy);">{{ wallLoading ? 'Posting...' : 'Post note' }}</button>
                            <button type="button" @click="closeWallModal" class="px-4 text-sm rounded-xl border border-[var(--text-muted)]/30 text-[var(--text-body)]">Close</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Photo frame modal (sign-in required) -->
            <div v-if="frameModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
                <div class="surface-card w-full max-w-sm shadow-xl p-5" style="border-radius: 1.5rem;">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Choose your photo frame</div>
                        <button @click="closeFrameModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]" aria-label="Close">✕</button>
                    </div>

                    <form v-if="frameStep === 'login'" @submit.prevent="signInForFrame" class="space-y-3">
                        <p class="text-xs text-[var(--text-muted)] mb-3">Sign in first. The frame applies to your own photo card.</p>
                        <input v-model="frameForm.student_number" type="text" placeholder="Student number" class="portal-input" />
                        <div class="flex items-center gap-2">
                            <input v-model="frameForm.password" :type="showFramePassword ? 'text' : 'password'" placeholder="Password" class="portal-input" />
                            <button type="button" @click="showFramePassword = !showFramePassword" class="text-xs text-[var(--text-muted)] shrink-0">{{ showFramePassword ? 'Hide' : 'Show' }}</button>
                        </div>
                        <p v-if="frameError" class="text-xs" style="color: var(--coral);">{{ frameError }}</p>
                        <button type="submit" :disabled="frameLoading" class="w-full text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-60" style="background:var(--navy);">
                            {{ frameLoading ? 'Signing in...' : 'Continue' }}
                        </button>
                    </form>

                    <div v-else class="space-y-3">
                        <p class="text-xs text-[var(--text-muted)]">Hi {{ frameOwner?.name }}. Pick a frame, then save.</p>
                        <div class="grid grid-cols-4 gap-2">
                            <button v-for="f in FRAME_OPTIONS" :key="f.key" type="button" @click="selectedFrame = f.key" class="flex flex-col items-center gap-1">
                                <span class="block w-full h-12 rounded-xl frame-ring" :class="['frame-' + f.key, selectedFrame === f.key ? 'frame-picked' : '']"></span>
                                <span class="text-[11px] text-[var(--text-muted)]">{{ f.label }}</span>
                            </button>
                        </div>
                        <p v-if="frameError" class="text-xs" style="color: var(--coral);">{{ frameError }}</p>
                        <p v-if="frameSuccess" class="text-xs font-medium" style="color: var(--teal, #1a7f37);">{{ frameSuccess }}</p>
                        <div class="flex gap-2">
                            <button type="button" @click="saveFrame" :disabled="frameLoading" class="flex-1 text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-60" style="background:var(--navy);">
                                {{ frameLoading ? 'Saving...' : 'Save frame' }}
                            </button>
                            <button type="button" @click="closeFrameModal" class="px-4 text-sm rounded-xl border border-[var(--text-muted)]/30 text-[var(--text-body)]">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message a classmate modal (sign-in required) -->
            <div v-if="msgModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
                <div class="surface-card w-full max-w-sm shadow-xl p-5" style="border-radius: 1.5rem;">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Post a message</div>
                        <button @click="closeMessageModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]" aria-label="Close">✕</button>
                    </div>
                    <p class="text-xs text-[var(--text-muted)] mb-3">Sign in first — your post will appear on your own photo card, tagged with your name.</p>

                    <template v-if="msgSuccess">
                        <p class="text-sm font-medium py-4 text-center" style="color: var(--teal, #1a7f37);">{{ msgSuccess }}</p>
                        <button @click="closeMessageModal" class="w-full text-white text-sm font-semibold py-2 rounded-xl" style="background:var(--navy);">
                            Close
                        </button>
                    </template>

                    <form v-else @submit.prevent="submitMessage" class="space-y-3">
                        <input v-model="msgForm.student_number" type="text" placeholder="Student number" class="portal-input" />

                        <div class="relative">
                            <input v-model="msgForm.password" :type="showMsgPassword ? 'text' : 'password'" placeholder="Password" class="portal-input pr-9" />
                            <button type="button" @click="showMsgPassword = !showMsgPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                <svg v-if="showMsgPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>

                        <div>
                            <textarea
                                v-model="msgForm.body"
                                rows="4"
                                :maxlength="MESSAGE_MAX_LENGTH"
                                placeholder="Write your post..."
                                class="portal-input"
                            ></textarea>
                            <div class="text-[11px] text-right text-[var(--text-muted)] mt-1 tabular-nums">{{ msgForm.body.length }}/{{ MESSAGE_MAX_LENGTH }}</div>
                        </div>

                        <p v-if="msgError" class="text-xs text-red-500">{{ msgError }}</p>

                        <button
                            type="submit"
                            :disabled="msgLoading"
                            class="w-full text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50"
                            style="background:var(--navy);"
                        >
                            {{ msgLoading ? 'Posting...' : 'Post message' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change password modal -->
            <div v-if="cpModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
                <div class="surface-card w-full max-w-sm shadow-xl p-5" style="border-radius: 1.5rem;">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Change password</div>
                        <button @click="closeChangePasswordModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]">✕</button>
                    </div>

                    <template v-if="cpSuccess">
                        <p class="text-sm font-medium py-4 text-center" style="color: var(--teal, #1a7f37);">{{ cpSuccess }}</p>
                        <button @click="closeChangePasswordModal" class="w-full text-white text-sm font-semibold py-2 rounded-xl" style="background:var(--navy);">
                            Close
                        </button>
                    </template>

                    <form v-else @submit.prevent="submitChangePassword" class="space-y-3">
                        <input
                            v-model="cpForm.student_number"
                            type="text"
                            placeholder="Student number"
                            class="portal-input"
                        />

                        <div class="relative">
                            <input
                                v-model="cpForm.current_password"
                                :type="showCpCurrentPassword ? 'text' : 'password'"
                                placeholder="Current password"
                                class="portal-input pr-9"
                            />
                            <button type="button" @click="showCpCurrentPassword = !showCpCurrentPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                <svg v-if="showCpCurrentPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>

                        <div class="relative">
                            <input
                                v-model="cpForm.new_password"
                                :type="showCpNewPassword ? 'text' : 'password'"
                                placeholder="New password"
                                class="portal-input pr-9"
                            />
                            <button type="button" @click="showCpNewPassword = !showCpNewPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                <svg v-if="showCpNewPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>

                        <div class="relative">
                            <input
                                v-model="cpForm.confirm_password"
                                :type="showCpConfirmPassword ? 'text' : 'password'"
                                placeholder="Confirm new password"
                                class="portal-input pr-9"
                            />
                            <button type="button" @click="showCpConfirmPassword = !showCpConfirmPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                <svg v-if="showCpConfirmPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>

                        <p v-if="cpError" class="text-xs text-red-500">{{ cpError }}</p>

                        <button
                            type="submit"
                            :disabled="cpLoading"
                            class="w-full text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50"
                            style="background:var(--navy);"
                        >
                            {{ cpLoading ? 'Updating...' : 'Update password' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useDashboardState } from '../../composables/useDashboardState';
import { useClassmateMessages, MESSAGE_MAX_LENGTH } from '../../composables/useClassmateMessages';
import { useStudentFrame, frameFor, FRAME_OPTIONS } from '../../composables/useStudentFrame';
import { useFreedomWall, stickerStyle, WALL_COLORS, WALL_MAX_LENGTH } from '../../composables/useFreedomWall';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    announcementsBySection: { type: Object, default: () => ({}) },
    topicsBySection: { type: Object, default: () => ({}) },
    globalCalendarEvents: { type: Array, default: () => [] },
    calendarEventsBySection: { type: Object, default: () => ({}) },
    top10BySection: { type: Object, default: () => ({}) },
    lastCalendarUpdate: { type: String, default: null },
});

// Sidebar-only UI state (local, not in the composable)
const sidebarOpen = ref(false);   // mobile drawer
const settingsOpen = ref(false);  // Settings submenu
const activeView = ref('rankings'); // TODO: add more views here if needed

const {
    // dark mode
    isDarkMode, toggleDarkMode,

    // sections / snapshot
    activeSectionId, activeSectionLabel,
    filteredStudents,
    latestAnnouncement, formattedUpdate, todayFormatted,
    isSyncing, syncNow,

    // class ledger
    currentIndex, autoStopped, initials,

    // chat widget
    chatOpen, chatStudent, chatLogin, chatLoginError, chatLoginLoading,
    showChatLoginPassword, chatMustChangePassword, chatMessages, chatInput,
    chatSending, chatScrollEl, signInChat, sendChatMessage,

    // grades modal
    gradesModalOpen, gradesForm, gradesError, gradesLoading,
    showGradesLoginPassword, gradesResult, gradesPeriod, gradesMustChangePassword,
    periods, switchGradesPeriod, openGradesModal, closeGradesModal, submitGradesLogin,

    // grade correction / recheck
    showRecheckForm, recheckNotes, correctionLoading, correctionError, correctionSuccessMessage,
    editedItems, editingItemKey, editDraftScore, startEditItem, confirmEditItem, cancelEditItem, removeEditedItem,
    correctionAttachment, correctionAttachmentError, onAttachmentChange, hasExistingAttachment,
    correctionUiState, cancelEditingRecheckForm, startEditExistingCorrection, cancelCorrection, submitCorrection,

    // standalone change password
    cpModalOpen, cpForm, cpError, cpSuccess, cpLoading,
    showCpCurrentPassword, showCpNewPassword, showCpConfirmPassword,
    openChangePasswordModal, closeChangePasswordModal, submitChangePassword,

    // forced password change
    passwordChangeContext, newPasswordForm, passwordChangeError, passwordChangeLoading,
    showNewPassword, showConfirmPassword, cancelPasswordChange, submitPasswordChange,


    // misc
    liveClock, formatEventDate, formatPostedDate,
} = useDashboardState(props);

// Message-a-classmate modal
const {
    msgModalOpen, msgForm, msgError, msgSuccess, msgLoading, showMsgPassword,
    lastPostedSender,
    openMessageModal, closeMessageModal, submitMessage,
    messageState, loadMessages,

    // edit / delete
    editingMessageId, editAction, editForm, editError, editLoading,
    openEditForm, openDeleteForm, cancelEditForm, submitEditMessage, submitDeleteMessage,
} = useClassmateMessages();

// Photo frame picker modal
const {
    frameModalOpen, frameStep, frameLoading, frameError, frameSuccess,
    showFramePassword, frameForm, selectedFrame, frameOwner,
    openFrameModal, closeFrameModal, signInForFrame, saveFrame,
} = useStudentFrame();

// Custom section dropdown (replaces native <select> so we control styling fully)
const sectionMenuOpen = ref(false);
const activeSection = computed(() =>
    props.sections.find((s) => s.id === activeSectionId.value) ?? null
);

function selectSection(id) {
    activeSectionId.value = id;
    currentIndex.value = 0;
    activeView.value = 'rankings';
}

// Photo slideshow (top 10 of the active section)
const slides = computed(() => filteredStudents.value);
const slideIndex = computed(() =>
    slides.value.length ? Math.min(currentIndex.value, slides.value.length - 1) : 0
);
const currentSlide = computed(() => slides.value[slideIndex.value] ?? null);

// Messages under the photo: load whenever the slide changes
const currentMessages = computed(() => messageState(currentSlide.value));

// After a successful post, jump to the sender's own card (posts always land there)
watch(lastPostedSender, (sender) => {
    if (!sender) return;
    const idx = slides.value.findIndex((s) =>
        String(s.id ?? s.student_id) === String(sender.id) || s.name === sender.name
    );
    if (idx !== -1) currentIndex.value = idx;
});
// Debounced so quick slide changes don't queue up requests (php artisan serve is single-threaded)
let messagesTimer = null;
watch(currentSlide, (student) => {
    clearTimeout(messagesTimer);
    if (!student) return;
    messagesTimer = setTimeout(() => loadMessages(student), 400);
}, { immediate: true });
const slidePaused = ref(false);
const slideStopped = ref(false);

// Editing/deleting a message stops the slideshow (the card would change mid-edit)
watch(editingMessageId, (id) => {
    if (id !== null) slideStopped.value = true;
});
let slideTimer = null;

// Keep the built-in autoplay in useDashboardState in sync (it has its own timer)
watch(slideStopped, (v) => {
    autoStopped.value = v;
});

function nextSlide() {
    if (!slides.value.length) return;
    currentIndex.value = (slideIndex.value + 1) % slides.value.length;
}

function stopSlideshow() {
    clearInterval(slideTimer);
    slideTimer = null;
}

function startSlideshow() {
    stopSlideshow();
    // Skip auto-advance if the user prefers reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    slideTimer = setInterval(() => {
        // Don't move on while hovering or while any modal is open
        if (slidePaused.value || slideStopped.value || msgModalOpen.value || gradesModalOpen.value || cpModalOpen.value) return;
        nextSlide();
    }, 8000); // pinabagal mula 5000 -> 8000ms
}

function step(dir) {
    slideStopped.value = true; // manual nav stops autoplay
    const n = slides.value.length;
    if (!n) return;
    currentIndex.value = (slideIndex.value + dir + n) % n;
    startSlideshow(); // restart the timer after a manual change
}

function goToSlide(i) {
    slideStopped.value = true; // manual nav stops autoplay
    currentIndex.value = i;
    startSlideshow();
}

function rankRibbonClass(i) {
    if (i === 0) return 'rank-ribbon--gold';
    if (i === 1) return 'rank-ribbon--silver';
    if (i === 2) return 'rank-ribbon--bronze';
    return 'rank-ribbon--plain';
}

onMounted(startSlideshow);
onBeforeUnmount(() => {
    stopSlideshow();
    clearTimeout(messagesTimer);
});
// Freedom wall
const {
    wallPosts, wallStatus, wallModalOpen, wallLoading, wallError, wallSuccess,
    showWallPassword, wallForm,
    loadWall, openWallModal, closeWallModal, submitWallPost, toggleLike, deletePost,
} = useFreedomWall();

watch([activeView, activeSectionId], ([view, id]) => {
    if (view === 'wall' || view === 'rankings') loadWall(id);
}, { immediate: true });
</script>

<style scoped>
.portal-root {
    font-family: var(--font-body);

    --font-display: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --font-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --font-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;

    /* Custom palette — Light mode */
    --navy: #154D71;        /* deep navy ink panel */
    --navy-deep: #0F3A56;   /* deeper shade for gradients */
    --gold: #33A1E0;        /* accent — CTAs, highlights, links */
    --teal: #1a7f37;        /* success green */
    --coral: #cf222e;       /* danger red */

    --page-bg: #F3F8FC;
    --surface: #ffffff;
    --surface-border: #C9DCE8;
    --surface-border-soft: #DAE8F0;
    --text-heading: #154D71;
    --text-body: #1C3A4D;
    --text-secondary: #1C6EA4;   /* medium blue accent for secondary text */
    --text-muted: #6E8A9C;
    --chip-bg: #E4EFF6;
}
.portal-root.dark {
    /* Custom palette — Dark mode */
    --navy: #1C6EA4;
    --navy-deep: #0D2B3D;
    --gold: #FFF9AF;        /* pale yellow pops well on dark navy */
    --teal: #3fb950;
    --coral: #f85149;

    --page-bg: #0B1F2C;
    --surface: #12293A;
    --surface-border: #234A61;
    --surface-border-soft: #1B3A4E;
    --text-heading: #EAF4FA;
    --text-body: #CFE3EE;
    --text-secondary: #7FBEDE;
    --text-muted: #5E8AA0;
    --chip-bg: #1B3A4E;
}

/* ---- background texture ---- */
.grain-layer {
    background-image: radial-gradient(rgba(20, 33, 61, 0.05) 1px, transparent 1px);
    background-size: 18px 18px;
}
.portal-root.dark .grain-layer {
    background-image: radial-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px);
}
.blob-a { top: -6rem; left: -6rem; }
.blob-b { bottom: -6rem; right: -6rem; }
@keyframes float-a { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(50px, 35px); } }
@keyframes float-b { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-40px, -25px); } }

/* ---- sidebar ---- */
.sidebar {
    background: linear-gradient(180deg, var(--navy), var(--navy-deep));
    box-shadow: 8px 0 30px -18px rgba(10, 18, 48, 0.5);
}
.nav-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.6rem 0.75rem;
    border-radius: 0.75rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.72);
    transition: background 0.15s ease, color 0.15s ease;
}
.nav-item:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
.nav-item:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }
.nav-item--active {
    background: var(--navy);
    color: #fff;
}
.nav-item--sub { font-size: 0.8rem; padding: 0.5rem 0.65rem; }

/* ---- custom section dropdown (replaces native <select>) ---- */
.section-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0.65rem;
    padding: 0.55rem 0.75rem;
    transition: background 0.15s ease, border-color 0.15s ease;
}
.section-trigger:hover { background: rgba(255, 255, 255, 0.12); }
.section-trigger:focus-visible { outline: 2px solid var(--gold); outline-offset: 1px; }

.section-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    z-index: 50;
    max-height: 15rem;
    overflow-y: auto;
    overscroll-behavior: contain;
    background: linear-gradient(180deg, rgba(14, 40, 66, 0.97), rgba(8, 24, 42, 0.97));
    border: 1px solid rgba(255, 215, 90, 0.35);
    border-radius: 1rem;
    padding: 0.4rem;
    box-shadow: 0 24px 50px -16px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.04) inset;
    scrollbar-width: none;
}
.section-menu::-webkit-scrollbar { display: none; }

.section-option {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.72);
    padding: 0.6rem 0.75rem;
    border-radius: 0.65rem;
    cursor: pointer;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}
.section-option + .section-option { margin-top: 2px; }
.section-option:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}
.section-option--active {
    background: linear-gradient(90deg, rgba(255, 215, 90, 0.18), rgba(255, 215, 90, 0.06));
    color: var(--gold);
    font-weight: 600;
}
.section-option--active:hover { transform: none; }
.section-option--active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 22%;
    bottom: 22%;
    width: 3px;
    border-radius: 999px;
    background: var(--gold);
}
.section-option svg { color: var(--gold); }

.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }

/* ---- panels & cards ---- */
.ink-panel {
    background: linear-gradient(160deg, var(--navy), var(--navy-deep));
    box-shadow: 0 20px 45px -20px rgba(10, 18, 48, 0.45);
}
.surface-card {
    background: var(--surface);
    border: 1px solid var(--surface-border);
    border-radius: 1.5rem;
    box-shadow: 0 1px 2px rgba(20,33,61,0.03);
    transition: box-shadow 0.2s ease;
}
.surface-card:hover { box-shadow: 0 8px 24px -12px rgba(20,33,61,0.12); }
.card-heading {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-heading);
}

.ledger-tile {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 1.1rem;
    padding: 1rem;
}
.ledger-tile--white {
    background: var(--chip-bg);
    border: 1px solid var(--surface-border);
}
.ledger-tile__icon {
    width: 2rem; height: 2rem;
    border-radius: 0.6rem;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 0.75rem;
}

/* ---- ledger leader-line rows ---- */
.ledger-row { display: flex; align-items: baseline; gap: 0.5rem; }
.leader {
    flex: 1;
    border-bottom: 1px dotted rgba(255,255,255,0.22);
    height: 0;
    transform: translateY(-4px);
    min-width: 0.5rem;
}
.leader--light { border-bottom-color: var(--surface-border); }

/* ---- ledger rows (clickable) ---- */
.rank-num {
    width: 1.5rem;
    flex-shrink: 0;
    font-family: var(--font-mono);
    font-size: 0.8rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.85);
}
.ledger-row--btn { transition: background 0.15s ease; }
.ledger-row--btn:hover { background: rgba(255, 255, 255, 0.06); }
.ledger-row--btn:focus-visible { outline: 2px solid var(--gold); outline-offset: 1px; }
.ledger-row--current { background: rgba(255, 255, 255, 0.1); }
.ledger-row--current .rank-num { color: var(--gold); }

/* ---- message panel above the top 10 ---- */
.msg-panel {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.msg-bubble {
    position: relative;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
}
.msg-actions {
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.15s ease;
}
.msg-bubble:hover .msg-actions {
    opacity: 1;
}
.msg-action-btn {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.08);
    transition: background 0.15s ease, color 0.15s ease;
}
.msg-action-btn:hover {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
}

/* Quote-styled message body: bigger, italic, with curly quotation marks */
.quote-text {
    position: relative;
    padding-left: 1.1rem;
}
.quote-text::before {
    content: '\201C';
    position: absolute;
    left: -0.15rem;
    top: -0.2rem;
    font-size: 1.4em;
    font-style: normal;
    color: var(--gold);
    opacity: 0.7;
}
.quote-text::after {
    content: '\201D';
    font-style: normal;
    color: var(--gold);
    opacity: 0.7;
    margin-left: 0.1rem;
}

/* ---- slideshow ---- */
.slide-btn {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.3);
    color: #fff;
    transition: background 0.15s ease;
}
.slide-btn:hover { background: rgba(0, 0, 0, 0.5); }
.slide-btn:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
.slide-enter-active,
.slide-leave-active { transition: opacity 0.35s ease, transform 0.35s ease; }
.slide-enter-from { opacity: 0; transform: translateX(24px); }
.slide-leave-to { opacity: 0; transform: translateX(-24px); }

/* ---- rank ribbon ---- */
.rank-ribbon {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 0.3rem 0.9rem;
    margin-bottom: 0.9rem;
    clip-path: polygon(6% 0, 94% 0, 100% 50%, 94% 100%, 6% 100%, 0 50%);
}
.rank-ribbon--gold { background: var(--gold); color: var(--navy-deep); }
.rank-ribbon--silver { background: #C7CEDA; color: #2B3550; }
.rank-ribbon--bronze { background: #C08A4E; color: #2E1B08; }
.rank-ribbon--plain { background: rgba(255, 255, 255, 0.18); color: #fff; }

/* ---- inputs ---- */
.portal-input {
    width: 100%;
    font-size: 0.875rem;
    border: 1px solid var(--surface-border);
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: var(--surface);
    color: var(--text-body);
}
.portal-input:focus {
    outline: 2px solid var(--gold);
    outline-offset: 1px;
}

/* ---- typing dots ---- */
.typing-dot {
    width: 6px; height: 6px;
    border-radius: 9999px;
    background: var(--text-muted);
    animation: typing-bounce 1.2s infinite ease-in-out;
}
.typing-dot:nth-child(2) { animation-delay: 0.15s; }
.typing-dot:nth-child(3) { animation-delay: 0.3s; }
@keyframes typing-bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
    30% { transform: translateY(-4px); opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .blob-a, .blob-b { animation: none; }
    .slide-enter-active, .slide-leave-active { transition: none; }
    .sidebar { transition: none; }
}
/* ---- Photo frames v2 ---- */
.frame-ring {
    position: relative;
    overflow: hidden;
    border: 4px solid transparent;
    background:
        linear-gradient(var(--navy-deep), var(--navy-deep)) padding-box,
        var(--frame-fill) border-box;
    background-size: 100% 100%, 300% 300%;
}
.frame-picked { outline: 2px solid var(--text-heading, #111); outline-offset: 2px; }

@keyframes frame-pulse {
    from { box-shadow: 0 0 8px var(--glow), 0 0 18px var(--glow2); }
    to { box-shadow: 0 0 18px var(--glow), 0 0 40px var(--glow2); }
}
@keyframes frame-shift {
    0% { background-position: 0 0, 0% 50%; }
    100% { background-position: 0 0, 300% 50%; }
}
@keyframes frame-rise {
    0% { background-position: 0 0, 50% 0%; }
    100% { background-position: 0 0, 50% 300%; }
}
@keyframes frame-sweep {
    0% { background-position: 0 0; }
    60%, 100% { background-position: 100% 0; }
}

/* Light sweep across the photo */
.frame-gold::after, .frame-ice::after, .frame-royal::after,
.frame-aurora::after, .frame-rainbow::after {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 5;
    pointer-events: none;
    background: linear-gradient(115deg, transparent 40%, rgba(255, 255, 255, 0.28) 50%, transparent 60%);
    background-size: 250% 100%;
    background-repeat: no-repeat;
    animation: frame-sweep 3.5s ease-in-out infinite;
}

/* Flame: rising border, flickering glow, fire tongues, embers */
@keyframes flame-lick {
    0% { transform: scaleY(0.8) skewX(-3deg); opacity: 0.85; }
    100% { transform: scaleY(1.15) skewX(3deg); opacity: 1; }
}
@keyframes ember-rise {
    to { background-position: 10px -90px, 40px -130px, 0 -10px; }
}
.frame-flame {
    --frame-fill: linear-gradient(0deg, #ff3d00, #ff9100, #ffd600, #ff9100, #ff3d00);
    --glow: rgba(255, 120, 0, 0.9);
    --glow2: rgba(255, 50, 0, 0.55);
    animation: frame-rise 1.6s linear infinite, frame-pulse 0.55s ease-in-out infinite alternate;
}
.frame-flame::after {
    content: '';
    position: absolute;
    left: -5%;
    right: -5%;
    bottom: -6px;
    height: 45%;
    z-index: 5;
    pointer-events: none;
    filter: blur(5px);
    mix-blend-mode: screen;
    transform-origin: 50% 100%;
    animation: flame-lick 0.7s ease-in-out infinite alternate;
    background:
        radial-gradient(ellipse 8% 85% at 6% 100%, #ffe45c 0%, rgba(255, 120, 0, 0.85) 40%, rgba(255, 50, 0, 0) 75%),
        radial-gradient(ellipse 10% 100% at 20% 100%, #ffe45c 0%, rgba(255, 120, 0, 0.85) 40%, rgba(255, 50, 0, 0) 75%),
        radial-gradient(ellipse 9% 80% at 34% 100%, #ffe45c 0%, rgba(255, 120, 0, 0.85) 40%, rgba(255, 50, 0, 0) 75%),
        radial-gradient(ellipse 11% 100% at 48% 100%, #ffe45c 0%, rgba(255, 120, 0, 0.85) 40%, rgba(255, 50, 0, 0) 75%),
        radial-gradient(ellipse 9% 90% at 62% 100%, #ffe45c 0%, rgba(255, 120, 0, 0.85) 40%, rgba(255, 50, 0, 0) 75%),
        radial-gradient(ellipse 10% 95% at 76% 100%, #ffe45c 0%, rgba(255, 120, 0, 0.85) 40%, rgba(255, 50, 0, 0) 75%),
        radial-gradient(ellipse 8% 85% at 90% 100%, #ffe45c 0%, rgba(255, 120, 0, 0.85) 40%, rgba(255, 50, 0, 0) 75%),
        linear-gradient(0deg, rgba(255, 80, 0, 0.55), transparent 60%);
}
.frame-flame::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 5;
    pointer-events: none;
    opacity: 0.85;
    background-image:
        radial-gradient(circle, #ffd600 0 2px, transparent 3px),
        radial-gradient(circle, #ff9100 0 1.5px, transparent 2.5px),
        radial-gradient(circle, #ffe08a 0 1px, transparent 2px);
    background-size: 70px 90px, 110px 130px, 50px 70px;
    background-position: 10px 0, 40px 30px, 0 60px;
    -webkit-mask-image: linear-gradient(to top, #000 0%, transparent 85%);
    mask-image: linear-gradient(to top, #000 0%, transparent 85%);
    animation: ember-rise 3s linear infinite;
}

/* Other frames */
.frame-gold {
    --frame-fill: linear-gradient(135deg, var(--gold), var(--gold));
    --glow: var(--gold);
    --glow2: transparent;
    animation: frame-pulse 2.4s ease-in-out infinite alternate;
}
.frame-aurora {
    --frame-fill: linear-gradient(135deg, #00f5a0, #00d9f5, #7b61ff, #00f5a0);
    --glow: rgba(0, 245, 160, 0.6);
    --glow2: rgba(123, 97, 255, 0.4);
    animation: frame-shift 5s linear infinite, frame-pulse 3s ease-in-out infinite alternate;
}
.frame-neon {
    --frame-fill: linear-gradient(135deg, #39ff14, #39ff14);
    --glow: #39ff14;
    --glow2: rgba(57, 255, 20, 0.45);
    animation: frame-pulse 0.9s ease-in-out infinite alternate;
}
.frame-ice {
    --frame-fill: linear-gradient(135deg, #e0f7ff, #7fd8ff, #e0f7ff);
    --glow: rgba(127, 216, 255, 0.8);
    --glow2: rgba(224, 247, 255, 0.4);
    animation: frame-pulse 2.6s ease-in-out infinite alternate;
}
.frame-rose {
    --frame-fill: linear-gradient(135deg, #ff7eb3, #ffc1d9, #ff4d94);
    --glow: rgba(255, 126, 179, 0.75);
    --glow2: rgba(255, 77, 148, 0.4);
    animation: frame-pulse 1.8s ease-in-out infinite alternate;
}
.frame-royal {
    --frame-fill: linear-gradient(135deg, #6a11cb, #d4af37, #6a11cb);
    --glow: rgba(212, 175, 55, 0.7);
    --glow2: rgba(106, 17, 203, 0.5);
    animation: frame-shift 6s linear infinite, frame-pulse 2.4s ease-in-out infinite alternate;
}
.frame-rainbow {
    --frame-fill: linear-gradient(135deg, #ff0000, #ff9a00, #ffee00, #2bd600, #00b3ff, #7a00ff, #ff0000);
    --glow: rgba(255, 255, 255, 0.5);
    --glow2: rgba(122, 0, 255, 0.4);
    animation: frame-shift 3s linear infinite, frame-pulse 2s ease-in-out infinite alternate;
}

@media (prefers-reduced-motion: reduce) {
    .frame-ring, .frame-ring::before, .frame-ring::after { animation: none !important; }
}

/* ---- Photo frames v3 (ornate overlay) ---- */
.fx-wrap { margin-top: 16px; --fx-glow: rgba(255, 255, 255, 0.6); --fx-shape: polygon(50% 0, 100% 55%, 50% 100%, 0 55%); }
.fx-deco { position: absolute; inset: 0; z-index: 30; pointer-events: none; filter: drop-shadow(0 0 5px var(--fx-glow)) drop-shadow(0 0 14px var(--fx-glow)); }
.fx-deco b {
    position: absolute; inset: -9px; padding: 11px; border-radius: 25px;
    background: var(--fx-fill); background-size: 300% 300%; background-position: 0% 50%;
    -webkit-mask-image: linear-gradient(#000 0 0), linear-gradient(#000 0 0);
    -webkit-mask-clip: content-box, border-box;
    -webkit-mask-composite: xor;
    mask-image: linear-gradient(#000 0 0), linear-gradient(#000 0 0);
    mask-clip: content-box, border-box;
    mask-composite: exclude;
    animation: fx-shift 5s linear infinite;
}
.fx-deco i, .fx-deco u { position: absolute; display: block; text-decoration: none; }
.fx-deco i { width: 40px; height: 56px; background: var(--fx-orn, var(--fx-fill)); clip-path: var(--fx-shape); transform-origin: 50% 100%; }
.fx-deco .gem { top: -24px; left: calc(50% - 14px); width: 28px; height: 28px; clip-path: polygon(50% 0, 100% 50%, 50% 100%, 0 50%); background: radial-gradient(circle at 35% 30%, #fff 0, #fff 14%, var(--fx-glow) 70%); }
.fx-deco .wing { top: -13px; width: 70px; height: 22px; clip-path: polygon(0 20%, 55% 0, 100% 65%, 45% 100%, 0 80%); background: var(--fx-fill); background-size: 300% 300%; }
.fx-deco .wl { left: calc(50% - 84px); }
.fx-deco .wr { left: calc(50% + 14px); }
.fx-deco .c1 { left: -20px; bottom: -28px; rotate: -28deg; }
.fx-deco .c2 { right: -20px; bottom: -28px; rotate: 28deg; }
.fx-deco .c3 { left: -12px; top: -20px; width: 26px; height: 36px; rotate: -35deg; }
.fx-deco .c4 { right: -12px; top: -20px; width: 26px; height: 36px; rotate: 35deg; }
.fx-deco .s1 { left: -6px; top: 48%; width: 30px; height: 42px; rotate: -80deg; }
.fx-deco .s2 { right: -6px; top: 48%; width: 30px; height: 42px; rotate: 80deg; }
@keyframes fx-shift { to { background-position: 300% 50%; } }
@keyframes fx-rise { from { background-position: 50% 0%; } to { background-position: 50% 300%; } }
@keyframes fx-pulse { from { opacity: 0.85; } to { opacity: 1; } }
@keyframes fx-flick { from { scale: 1 0.88; } to { scale: 1.06 1.14; } }
.fx-gold { --fx-fill: linear-gradient(135deg, var(--gold), var(--gold)); --fx-glow: transparent; }
.fx-gold .fx-deco { filter: none; animation: none; }
.fx-gold .fx-deco b { inset: -4px; padding: 4px; border-radius: 20px; animation: none; }
.fx-gold .fx-deco i, .fx-gold .fx-deco u { display: none; }
.fx-aurora { --fx-fill: linear-gradient(135deg, #00f5a0, #00d9f5, #7b61ff, #00f5a0); --fx-glow: rgba(0, 245, 200, 0.75); }
.fx-neon { --fx-fill: linear-gradient(135deg, #39ff14, #b6ff9e, #39ff14); --fx-glow: rgba(57, 255, 20, 0.85); }
.fx-ice { --fx-fill: linear-gradient(135deg, #e0f7ff, #7fd8ff, #e0f7ff); --fx-glow: rgba(127, 216, 255, 0.85); }
.fx-rose { --fx-fill: linear-gradient(135deg, #ff7eb3, #ffc1d9, #ff4d94, #ff7eb3); --fx-glow: rgba(255, 126, 179, 0.8); }
.fx-royal { --fx-fill: linear-gradient(135deg, #6a11cb, #d4af37, #6a11cb); --fx-glow: rgba(212, 175, 55, 0.85); }
.fx-rainbow { --fx-fill: linear-gradient(135deg, #ff0000, #ff9a00, #ffee00, #2bd600, #00b3ff, #7a00ff, #ff0000); --fx-glow: rgba(255, 255, 255, 0.7); }
.fx-flame {
    --fx-fill: linear-gradient(0deg, #8a1200, #ff4d00, #ff9100, #ffd23f, #ff9100, #ff4d00, #8a1200);
    --fx-orn: linear-gradient(0deg, #c2180a, #ff6a00 45%, #ffd23f);
    --fx-glow: rgba(255, 110, 0, 0.9);
    --fx-shape: path('M20 0 C23 14 38 22 36 38 C35 50 27 56 20 56 C13 56 5 50 4 38 C3 28 10 24 13 14 C15 20 17 9 20 0 Z');
}
.fx-flame .fx-deco b { background-size: 100% 300%; animation: fx-rise 1.6s linear infinite; }
.fx-flame .fx-deco i { animation: fx-flick 0.6s ease-in-out infinite alternate; }
.fx-flame .fx-deco .c2 { animation-delay: -0.2s; }
.fx-flame .fx-deco .c3 { animation-delay: -0.35s; }
.fx-flame .fx-deco .c4 { animation-delay: -0.1s; }
.fx-flame .fx-deco .s1 { animation-delay: -0.45s; }
.fx-flame .fx-deco .s2 { animation-delay: -0.25s; }
@media (prefers-reduced-motion: reduce) {
    .fx-deco, .fx-deco b, .fx-deco i { animation: none !important; }
}
/* ---- Freedom wall ---- */
.wall-board {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.4rem 1rem;
    padding: 1.4rem 1rem;
    align-content: start;
    border-radius: 1rem;
    background:
        radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.07) 0 2px, transparent 3px) 0 0 / 22px 22px,
        radial-gradient(circle at 70% 60%, rgba(0, 0, 0, 0.08) 0 2px, transparent 3px) 0 0 / 31px 31px,
        #9c7348;
}
.wall-note {
    position: relative;
    min-height: 7rem;
    padding: 1.15rem 0.9rem 0.8rem;
    border-radius: 3px;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
    transition: scale 0.2s ease;
    animation: wall-pop 0.35s ease-out;
}
.wall-note:hover { scale: 1.07; z-index: 20 !important; }
.wall-board > .wall-note:nth-child(even) { margin-top: 1.1rem; }
.wall-tape {
    position: absolute;
    top: -9px;
    left: calc(50% - 1.5rem);
    width: 3rem;
    height: 1.1rem;
    background: rgba(255, 255, 255, 0.55);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    transform: rotate(-3deg);
}
.wall-text { font-size: 0.85rem; line-height: 1.35; word-break: break-word; white-space: pre-wrap; }
.wall-foot { margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
.wall-like { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 999px; background: rgba(0, 0, 0, 0.08); transition: transform 0.15s ease, background 0.15s ease; }
.wall-like:hover { background: rgba(0, 0, 0, 0.16); transform: scale(1.06); }
.wall-like--on { color: #d6243c; background: rgba(214, 36, 60, 0.14); }
.wall-del { display: inline-flex; align-items: center; justify-content: center; padding: 0.25rem; border-radius: 999px; color: #b42318; background: rgba(180, 35, 24, 0.12); transition: background 0.15s ease; }
.wall-del:hover { background: rgba(180, 35, 24, 0.28); }
.wall-date { margin-top: 0; font-size: 0.65rem; opacity: 0.6; text-align: right; }
@keyframes wall-pop { from { opacity: 0; scale: 0.6; } to { opacity: 1; scale: 1; } }
@media (prefers-reduced-motion: reduce) { .wall-note { animation: none; } }

/* ---- Typewriter heading ---- */
.typewriter-line {
    margin-bottom: 1rem;
    font-family: var(--font-mono);
    font-size: clamp(1.1rem, 2.2vw, 1.6rem);
    font-weight: 600;
    color: var(--text-heading, #fff);
}
.typewriter {
    display: inline-block;
    max-width: 100%;
    width: 29ch;
    overflow: hidden;
    white-space: nowrap;
    vertical-align: bottom;
    border-right: 3px solid var(--gold);
    animation: tw-type 2.6s steps(29, end) 0.3s both, tw-caret 0.8s step-end infinite;
}
@keyframes tw-type { from { width: 0; } to { width: 29ch; } }
@keyframes tw-caret { 50% { border-color: transparent; } }
</style>