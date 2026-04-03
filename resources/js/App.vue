<script setup>
import { ref, onMounted } from 'vue'
import { api, setAuthToken } from './api.js'
import LoginView from './views/LoginView.vue'
import ProfessorView from './views/ProfessorView.vue'
import StudentView from './views/StudentView.vue'

const user = ref(null)
const role = ref('student')

onMounted(() => {
    const token = localStorage.getItem('fenix_token')
    const saved  = localStorage.getItem('fenix_user')
    if (token && saved) {
        setAuthToken(token)
        user.value = JSON.parse(saved)
    }
})

function onLogin({ user: u, token }) {
    setAuthToken(token)
    localStorage.setItem('fenix_token', token)
    localStorage.setItem('fenix_user', JSON.stringify(u))
    user.value = u
    role.value = 'student'
}

async function onLogout() {
    try { await api.post('/api/logout') } catch {}
    setAuthToken(null)
    localStorage.removeItem('fenix_token')
    localStorage.removeItem('fenix_user')
    user.value = null
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 font-sans">
        <LoginView v-if="!user" @login="onLogin" />

        <template v-else>
            <!-- ── Header ── -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
                <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-sm leading-none">F</span>
                        </div>
                        <span class="font-bold text-gray-900 text-lg leading-none">Fenix Exams</span>
                    </div>

                    <!-- Role switcher + user -->
                    <div class="flex items-center gap-4">
                        <div class="flex rounded-lg border border-gray-200 text-sm overflow-hidden">
                            <button
                                @click="role = 'student'"
                                class="px-4 py-1.5 font-medium transition-colors"
                                :class="role === 'student'
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-500 hover:bg-gray-50'"
                            >Aluno</button>
                            <button
                                @click="role = 'professor'"
                                class="px-4 py-1.5 font-medium transition-colors border-l border-gray-200"
                                :class="role === 'professor'
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-500 hover:bg-gray-50'"
                            >Professor</button>
                        </div>

                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-gray-600 hidden sm:block font-medium">{{ user.name }}</span>
                            <button
                                @click="onLogout"
                                class="text-red-500 hover:text-red-700 font-medium transition-colors"
                            >Sair</button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ── Main ── -->
            <main class="max-w-5xl mx-auto px-4 py-8">
                <ProfessorView v-if="role === 'professor'" :user="user" />
                <StudentView   v-else                      :user="user" />
            </main>
        </template>
    </div>
</template>
