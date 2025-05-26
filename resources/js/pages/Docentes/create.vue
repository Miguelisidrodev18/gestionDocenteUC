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

type FormType = {
    nombre: string;
    apellido: string;
    dni: string;
    email: string;
    telefono: string;
    especialidad: string;
    cv_personal: File | null;
    cv_sunedu: File | null;
    linkedin: string;
    estado: string;
    cip: string;
};

const form = ref<FormType>({
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

const handleFileChange = (e: Event, key: keyof Pick<FormType, 'cv_personal' | 'cv_sunedu'>) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.value[key] = target.files[0];
    }
};

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
    const data = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
        if (value !== null) data.append(key, value as any);
    });
    router.post('/docents', data, {
        forceFormData: true,
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
                <div>
                    <label class="font-semibold">Nombre</label>
                    <input v-model="form.nombre" type="text" placeholder="Nombre" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
                <div>
                    <label class="font-semibold">Apellido</label>
                    <input v-model="form.apellido" type="text" placeholder="Apellido" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
                <div>
                    <label class="font-semibold">DNI</label>
                    <input v-model="form.dni" type="text" placeholder="DNI" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
                <div>
                    <label class="font-semibold">Email</label>
                    <input v-model="form.email" type="email" placeholder="Email" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
                <div>
                    <label class="font-semibold">Teléfono</label>
                    <input v-model="form.telefono" type="text" placeholder="Teléfono" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
                <div>
                    <label class="font-semibold">Especialidad</label>
                    <input v-model="form.especialidad" type="text" placeholder="Especialidad" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
                <div>
                    <label class="font-semibold">CV Personal (PDF)</label>
                    <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_personal')" />
                </div>
                <div>
                    <label class="font-semibold">CV Sunedu (PDF)</label>
                    <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_sunedu')" />
                </div>
                <div>
                    <label class="font-semibold">LinkedIn</label>
                    <input v-model="form.linkedin" type="text" placeholder="LinkedIn" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
                <div>
                    <label class="font-semibold">Estado</label>
                    <select v-model="form.estado" class="w-full rounded-md border border-gray-300 p-2">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div>
                    <label class="font-semibold">CIP</label>
                    <input v-model="form.cip" type="text" placeholder="CIP" class="w-full rounded-md border border-gray-300 p-2" />
                </div>
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
