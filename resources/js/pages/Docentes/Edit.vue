<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

type BreadcrumbItem = { title: string; href: string };
const Breadcrumbs: BreadcrumbItem[] = [
    { title: 'Docentes', href: '/docents' },
    { title: 'Editar Docente', href: '#' },
];

const props = defineProps<{ docent: any }>();

// Usar ref para archivos y campos
type FormFields = {
    nombre: any;
    apellido: any;
    dni: any;
    email: any;
    telefono: any;
    especialidad: any;
    cv_personal: File | null;
    cv_sunedu: File | null;
    linkedin: any;
    estado: any;
    cip: any;
    [key: string]: any; // Add index signature
};

const form = ref<FormFields>({
    nombre: props.docent.nombre ?? '',
    apellido: props.docent.apellido ?? '',
    dni: props.docent.dni ?? '',
    email: props.docent.email ?? '',
    telefono: props.docent.telefono ?? '',
    especialidad: props.docent.especialidad ?? '',
    cv_personal: null,
    cv_sunedu: null,
    linkedin: props.docent.linkedin ?? '',
    estado: props.docent.estado ?? 'activo',
    cip: props.docent.cip ?? '',
});

// Manejar archivos
const handleFileChange = (e: Event, key: string) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.value[key] = target.files[0];
    }
};

const errors = ref({});

const submit = () => {
    const data = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
        if (value !== null) data.append(key, value as any);
    });

    console.log([...data.entries()]); // Verifica los datos que se están enviando


    router.put(`/docents/${props.docent.id}`, data, {
        forceFormData: true,
        onSuccess: () => {
            router.visit('/docents', { replace: true }); // Redirigir a la lista de docentes
        },
        onError: (err) => {
            errors.value = err; // Captura los errores de validación
            console.error(err);
        },
    });
};

console.log(props.docent);
</script>

<template>
    <head title="Editar Docente" />
    <AppLayout :breadcrumbs="Breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-2xl font-bold">Editar Docente</h1>
            <form @submit.prevent="submit" class="space-y-6 max-w-lg">
                <div>
                    <label>Nombre</label>
                    <input v-model="form.nombre" type="text" class="w-full border rounded px-2 py-1" />
                    <p v-if="errors.value.nombre" class="text-red-500 text-sm">{{ errors.value.nombre }}</p>
                </div>
                <div>
                    <label>Apellido</label>
                    <input v-model="form.apellido" type="text" class="w-full border rounded px-2 py-1" />
                    <p v-if="errors.value.apellido" class="text-red-500 text-sm">{{ errors.value.apellido }}</p>
                </div>
                <div>
                    <label>DNI</label>
                    <input v-model="form.dni" type="text" class="w-full border rounded px-2 py-1" />
                    <p v-if="errors.value.dni" class="text-red-500 text-sm">{{ errors.value.dni }}</p>
                </div>
                <div>
                    <label>Email</label>
                    <input v-model="form.email" type="email" class="w-full border rounded px-2 py-1" />
                </div>
                <div>
                    <label>Teléfono</label>
                    <input v-model="form.telefono" type="text" class="w-full border rounded px-2 py-1" />
                </div>
                <div>
                    <label>Especialidad</label>
                    <input v-model="form.especialidad" type="text" class="w-full border rounded px-2 py-1" />
                </div>
                <div>
                    <label>CV Personal (PDF)</label>
                    <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_personal')" />
                </div>
                <div>
                    <label>CV Sunedu (PDF)</label>
                    <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_sunedu')" />
                </div>
                <div>
                    <label>LinkedIn</label>
                    <input v-model="form.linkedin" type="text" class="w-full border rounded px-2 py-1" />
                </div>
                <div>
                    <label>Estado</label>
                    <select v-model="form.estado" class="w-full border rounded px-2 py-1">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div>
                    <label>CIP</label>
                    <input v-model="form.cip" type="text" class="w-full border rounded px-2 py-1" />
                </div>
                <Button type="submit" class="bg-blue-500 text-white">Guardar</Button>
            </form>
        </div>
    </AppLayout>
</template>