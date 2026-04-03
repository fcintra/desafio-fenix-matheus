<script setup>
import { ref, reactive } from 'vue'
import { api } from '../api.js'

const emit = defineEmits(['login'])

const mode   = ref('login') // 'login' | 'register'
const loading = ref(false)
const error   = ref('')

const form = reactive({
    name: '', email: '', password: '', password_confirmation: '',
})

function resetError() { error.value = '' }

async function doLogin() {
    loading.value = true
    error.value   = ''
    try {
        const { data } = await api.post('/api/login', {
            email: form.email, password: form.password,
        })
        emit('login', data)
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Erro ao fazer login.'
    } finally {
        loading.value = false
    }
}

async function doRegister() {
    loading.value = true
    error.value   = ''
    try {
        const { data } = await api.post('/api/register', {
            name: form.name,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation,
        })
        emit('login', data)
    } catch (e) {
        const errors = e.response?.data?.errors
        error.value  = errors
            ? Object.values(errors).flat()[0]
            : (e.response?.data?.message ?? 'Erro ao registrar.')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <!-- Brand -->
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-blue-600 rounded-2xl mx-auto mb-4 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">F</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Fenix Exams</h1>
                    <p class="text-gray-500 text-sm mt-1">Sistema de Provas Online</p>
                </div>

                <!-- Tab switch -->
                <div class="flex rounded-xl overflow-hidden border border-gray-200 mb-6 text-sm">
                    <button
                        @click="mode = 'login'; resetError()"
                        class="flex-1 py-2.5 font-semibold transition-colors"
                        :class="mode === 'login' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50'"
                    >Login</button>
                    <button
                        @click="mode = 'register'; resetError()"
                        class="flex-1 py-2.5 font-semibold transition-colors border-l border-gray-200"
                        :class="mode === 'register' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50'"
                    >Registrar</button>
                </div>

                <!-- Login form -->
                <form v-if="mode === 'login'" @submit.prevent="doLogin" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">E-mail</label>
                        <input v-model="form.email" type="email" required autocomplete="email"
                               placeholder="seu@email.com"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none
                                      focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Senha</label>
                        <input v-model="form.password" type="password" required autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none
                                      focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <p v-if="error" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ error }}</p>
                    <button type="submit" :disabled="loading"
                            class="w-full py-3 bg-blue-600 text-white rounded-lg font-semibold text-sm
                                   hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        {{ loading ? 'Entrando…' : 'Entrar' }}
                    </button>
                </form>

                <!-- Register form -->
                <form v-else @submit.prevent="doRegister" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nome</label>
                        <input v-model="form.name" type="text" required autocomplete="name"
                               placeholder="Seu nome completo"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none
                                      focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">E-mail</label>
                        <input v-model="form.email" type="email" required autocomplete="email"
                               placeholder="seu@email.com"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none
                                      focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Senha</label>
                        <input v-model="form.password" type="password" required autocomplete="new-password"
                               placeholder="Mínimo 8 caracteres"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none
                                      focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Senha</label>
                        <input v-model="form.password_confirmation" type="password" required
                               placeholder="Repita a senha"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none
                                      focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <p v-if="error" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ error }}</p>
                    <button type="submit" :disabled="loading"
                            class="w-full py-3 bg-blue-600 text-white rounded-lg font-semibold text-sm
                                   hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        {{ loading ? 'Registrando…' : 'Criar Conta' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-gray-400 mt-4">Fenix Exams © {{ new Date().getFullYear() }}</p>
        </div>
    </div>
</template>
