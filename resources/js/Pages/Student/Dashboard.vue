<template>
    <div
        class="portal-root min-h-screen relative overflow-hidden transition-colors duration-300"
        :class="isDarkMode ? 'dark' : ''"
    >
        <!-- Ambient background: paper grain + soft ink blobs -->
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[var(--page-bg)]">
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

                    <div class="mt-2 px-1">
                        <label class="block text-[11px] text-white/45 mb-1" for="section-select">Section</label>
                        <div class="relative">
                            <select
                                id="section-select"
                                v-model="activeSectionId"
                                @change="currentIndex = 0; activeView = 'rankings'"
                                class="sidebar-select"
                            >
                                <option v-for="s in sections" :key="s.id" :value="s.id">
                                    {{ s.subject ? `${s.subject} · ${s.name}` : s.name }}
                                </option>
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-white/60" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
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
            <div class="max-w-[1200px] mx-auto px-5 lg:px-10 py-6 lg:h-screen">

                <!-- Class ledger: top 1 (left) + top 10 (right) -->
                <div class="ink-panel rounded-[1.75rem] p-5 lg:p-7 h-full flex flex-col">
                    <div class="text-sm font-semibold text-white mb-5" style="font-family:var(--font-display);">Class ledger · {{ activeSectionLabel }}</div>

                    <p v-if="filteredStudents.length === 0" class="text-xs text-white/35 text-center py-8">
                        No grades have been recorded yet for this section.
                    </p>

                    <div v-else class="flex-1 min-h-0 grid grid-cols-1 md:grid-cols-5 gap-8">
                        <!-- Photo slideshow: top 10 -->
                        <div class="md:col-span-2 flex flex-col items-center text-center min-h-0">
                            <div class="rank-ribbon" :class="rankRibbonClass(slideIndex)">TOP {{ slideIndex + 1 }}</div>

                            <div
                                class="relative w-full flex-1 min-h-[12rem] rounded-2xl overflow-hidden ring-4 ring-[var(--gold)] shadow-lg"
                                @mouseenter="slidePaused = true"
                                @mouseleave="slidePaused = false"
                            >
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

                                <button type="button" @click="step(-1)" class="slide-btn absolute left-3 top-1/2 -translate-y-1/2" aria-label="Previous photo">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 18l-6-6 6-6"/></svg>
                                </button>
                                <button type="button" @click="step(1)" class="slide-btn absolute right-3 top-1/2 -translate-y-1/2" aria-label="Next photo">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
                                </button>

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

                        <!-- Right column: post wall for the current top, then the top 10 list -->
                        <div class="md:col-span-3 w-full flex flex-col gap-4 min-h-0">

                            <!-- Messages for the current top (changes with each slide) -->
                            <div class="msg-panel shrink-0 rounded-2xl p-4">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-white truncate" style="font-family:var(--font-display);">Messages from {{ currentSlide.name }}</div>
                                        <div class="text-[11px] text-white/55">Only shown once they sign in and post it themselves</div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openMessageModal()"
                                        class="slide-btn shrink-0"
                                        title="Post a message"
                                        aria-label="Post a message for this classmate"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                </div>

                                <div class="max-h-56 overflow-y-auto space-y-3 pr-1">
                                    <p v-if="(currentMessages.status === 'loading' || currentMessages.status === 'idle') && !currentMessages.items.length" class="text-xs text-white/55">Loading messages...</p>
                                    <p v-else-if="currentMessages.status === 'error' && !currentMessages.items.length" class="text-xs text-white/55">Couldn't load messages. Switch photos to retry.</p>
                                    <p v-else-if="!currentMessages.items.length" class="text-xs text-white/55">No messages yet. Tap the pencil to post one.</p>
                                    <div v-for="m in currentMessages.items" :key="m.id" class="msg-bubble">
                                        <div class="text-[11px] font-semibold mb-1" style="color:var(--gold);">{{ m.sender_name }}</div>
                                        <div class="text-sm text-white/90 leading-relaxed italic quote-text">{{ m.body }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top 10 list (click a row to jump to that photo) -->
                            <div class="flex-1 min-h-0 flex flex-col justify-between overflow-y-auto">
                                <button
                                    v-for="(s, i) in slides"
                                    :key="s.name"
                                    type="button"
                                    @click="goToSlide(i)"
                                    class="ledger-row ledger-row--btn w-full py-2 px-2 rounded-lg text-left"
                                    :class="i === slideIndex ? 'ledger-row--current' : ''"
                                >
                                    <span class="rank-num">{{ i + 1 }}</span>
                                    <div class="w-6 h-6 rounded-full overflow-hidden shrink-0 flex items-center justify-center text-[10px] font-semibold" :style="!s.photo_url ? { background: 'var(--gold)', color: 'var(--navy-deep)' } : {}">
                                        <img v-if="s.photo_url" :src="s.photo_url" :alt="s.name" class="w-full h-full object-cover" />
                                        <span v-else>{{ initials(s.name) }}</span>
                                    </div>
                                    <div class="text-sm text-white/90 shrink-0">{{ s.name }}</div>
                                    <span class="leader"></span>
                                    <div class="text-xs font-semibold shrink-0 tabular-nums" style="font-family:var(--font-mono); color:var(--gold);">{{ s.grade }}</div>
                                </button>
                            </div>
                        </div>
                    </div>
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
    currentIndex, initials,

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
} = useClassmateMessages();

// Photo slideshow (top 10 of the active section)
const slides = computed(() => filteredStudents.value.slice(0, 10));
const slideIndex = computed(() =>
    slides.value.length ? Math.min(currentIndex.value, slides.value.length - 1) : 0
);
const currentSlide = computed(() => slides.value[slideIndex.value] ?? null);

// Messages under the photo: load whenever the slide changes
const currentMessages = computed(() => messageState(currentSlide.value));
// Debounced so quick slide changes don't queue up requests (php artisan serve is single-threaded)
let messagesTimer = null;
watch(currentSlide, (student) => {
    clearTimeout(messagesTimer);
    if (!student) return;
    messagesTimer = setTimeout(() => loadMessages(student), 400);
}, { immediate: true });
const slidePaused = ref(false);
let slideTimer = null;

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
        if (slidePaused.value || msgModalOpen.value || gradesModalOpen.value || cpModalOpen.value) return;
        nextSlide();
    }, 8000); // pinabagal mula 5000 -> 8000ms
}

function step(dir) {
    const n = slides.value.length;
    if (!n) return;
    currentIndex.value = (slideIndex.value + dir + n) % n;
    startSlideshow(); // restart the timer after a manual change
}

function goToSlide(i) {
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
.blob-a { top: -6rem; left: -6rem; animation: float-a 16s ease-in-out infinite; }
.blob-b { bottom: -6rem; right: -6rem; animation: float-b 18s ease-in-out infinite; }
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
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    box-shadow: inset 3px 0 0 var(--gold);
}
.nav-item--sub { font-size: 0.8rem; padding: 0.5rem 0.65rem; }

.sidebar-select {
    width: 100%;
    appearance: none;
    font-size: 0.8rem;
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0.65rem;
    padding: 0.5rem 2rem 0.5rem 0.75rem;
}
.sidebar-select:focus-visible { outline: 2px solid var(--gold); outline-offset: 1px; }
.sidebar-select option { background: var(--navy-deep); color: #fff; }

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
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
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
</style>