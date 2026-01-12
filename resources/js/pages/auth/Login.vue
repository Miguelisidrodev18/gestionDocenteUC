<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showToast = ref(false);
const toastMsg = ref('');
const showPassword = ref(false);

const toast = (msg: string) => {
    toastMsg.value = msg;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 2800);
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: () => {
            if (form.errors.email || form.errors.password) {
                toast('Credenciales incorrectas.');
            }
        },
    });
};
</script>

<template>
    <AuthBase title="Hola!" description="Inicia sesion para continuar">
        <Head title="Iniciar sesion" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-5">
            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="email" class="sr-only">Email address</Label>
                    <div class="relative">
                        <Mail class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="Correo electronico"
                            class="h-11 rounded-full border-slate-200 bg-white pl-10 text-sm text-slate-700 placeholder:text-slate-400 focus-visible:ring-[#0b72e7]/30"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="sr-only">Password</Label>
                    <div class="relative">
                        <Lock class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Contrasena"
                            class="h-11 rounded-full border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-700 placeholder:text-slate-400 focus-visible:ring-[#0b72e7]/30"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                        >
                            <Eye v-if="!showPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>
            </div>

            <div class="flex items-center justify-between" :tabindex="3">
                <Label for="remember" class="flex items-center space-x-3 text-sm text-slate-500">
                    <Checkbox id="remember" v-model:checked="form.remember" :tabindex="4" />
                    <span>Recordarme</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="h-11 w-full rounded-full bg-[#0b72e7] text-white hover:bg-[#0a62c6]"
                :tabindex="4"
                :disabled="form.processing"
            >
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Ingresar
            </Button>

            <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm text-slate-500">
                Olvidaste tu contrasena
            </TextLink>

            <div class="relative flex items-center gap-2">
                <div class="h-px flex-1 bg-slate-200"></div>
                <span class="text-xs text-slate-400">o</span>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>

            <Button as-child variant="outline" class="h-11 w-full rounded-full border-slate-200 text-slate-600">
                <a :href="route('google.redirect')">Continuar con Google</a>
            </Button>
        </form>

        <div v-if="showToast" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-sm rounded-2xl bg-card text-foreground shadow-xl border border-border p-5 text-center">
                <div class="text-lg font-semibold mb-2">Acceso denegado</div>
                <div class="text-sm text-muted-foreground mb-4">{{ toastMsg }}</div>
                <div class="flex justify-center gap-2">
                    <Button type="button" variant="ghost" class="px-4" @click="showToast = false">Cerrar</Button>
                </div>
            </div>
        </div>
    </AuthBase>
</template>
