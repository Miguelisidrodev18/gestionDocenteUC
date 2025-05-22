<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

type BreadcrumbItem = { title: string; href: string };
const Breadcrumbs: BreadcrumbItem[] = [
    { title: 'Docentes', href: '/docents' },
    { title: 'Crear Docente', href: '#' },
];

const form = ref({
    nombre: '',
    apellido: '',
    dni: '',
    email: '',
    telefono: '',
    especialidad: '',
    cv_personal: null,
    cv_sunedu: null,
    linkedin: '',
    estado: 'activo',
    cip: '',
});

const resetForm = () => {
    form.value = {
        nombre: '',
        apellido: '',
        dni: '',
        email: '',
        telefono: '',
        especialidad: '',
        cv_personal: null,
        cv_sunedu: null,
        linkedin: '',
        estado: 'activo',
        cip: '',
    };
};

const submit = () => {
    router.post('/docents', form.value, {
        onSuccess: () => {
            resetForm();
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
};
</script>

<template>
    <head title="Crear Docente" />
    <AppLayout :breadcrumbs="Breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-2xl font-bold">Crear Docente</h1>
            <form @submit.prevent="submit" class="space-y-6 max-w-lg">
                <!-- Campos del Formulario -->
                <div v-for="(label, key) in { 
                            nombre: 'Nombre', 
                            apellido: 'Apellido', 
                            dni: 'DNI', 
                            email: 'Email', 
                            telefono: 'Teléfono', 
                            especialidad: 'Especialidad', 
                            cv_personal: 'CV Personal', 
                            cv_sunedu: 'CV Sunedu', 
                            linkedin: 'LinkedIn', 
                            estado: 'Estado', 
                            cip: 'CIP' 
                        }" :key="key" class="space-y-2">
                    <label :for="key" class="font-semibold">{{ label }}</label>
                    <input
                        :id="key"
                        v-model="form[key]"
                        :type="key === 'email' ? 'email' : 'text'"
                        :placeholder="label"
                        class="w-full rounded-md border border-gray-300 p-2 focus:border-blue-500 focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Botones -->
                <div class="flex gap-4">
                    <Button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Crear Docente
                    </Button>
                    <Link href="/docentes" class="text-gray-500 hover:text-gray-700">
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
