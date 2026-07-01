<script setup>
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const form = useForm({
    name:  '',
    email: '',
    role:  'operator',
})

function submit() {
    form.post(route('portal.users.store'))
}
</script>

<template>
    <PortalLayout>
        <div class="max-w-lg">
            <div class="flex items-center gap-3 mb-6">
                <a :href="route('portal.users.index')" class="text-gray-400 hover:text-[#2d5294] transition-colors text-sm">← Usuários</a>
                <span class="text-gray-300">/</span>
                <h1 class="text-xl font-bold text-[#2d5294]">Convidar usuário</h1>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-6">
                    O usuário receberá um e-mail com um link para definir a senha e ativar a conta. O convite expira em <strong>48 horas</strong>.
                </p>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nome completo</label>
                        <input v-model="form.name" type="text" placeholder="Ex.: Maria Silva"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#2d5294]/30 focus:border-[#2d5294] transition" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">E-mail</label>
                        <input v-model="form.email" type="email" placeholder="maria@empresa.com.br"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#2d5294]/30 focus:border-[#2d5294] transition" />
                        <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Perfil de acesso</label>
                        <select v-model="form.role"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#2d5294]/30 focus:border-[#2d5294] transition">
                            <option value="admin">Administrador — gestão completa</option>
                            <option value="operator">Operador — emite boletos, consulta dados</option>
                            <option value="viewer">Visualizador — somente leitura</option>
                        </select>
                        <p v-if="form.errors.role" class="text-xs text-red-500 mt-1">{{ form.errors.role }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            :disabled="form.processing"
                            class="bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
                            {{ form.processing ? 'Enviando...' : 'Enviar convite' }}
                        </button>
                        <a :href="route('portal.users.index')"
                            class="text-sm text-gray-400 hover:text-gray-600 transition-colors">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </PortalLayout>
</template>
