<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onBeforeUnmount } from 'vue';
import PdfFileCard from '@/components/PdfFileCard.vue';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';

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

const errors = ref<Record<string, string[]>>({}); // Para almacenar los errores de validacion

const cvPersonalUrl = ref<string | null>(null);
const cvSuneduUrl = ref<string | null>(null);

const handleFileChange = (e: Event, key: keyof Pick<FormType, 'cv_personal' | 'cv_sunedu'>) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        if (file.type !== 'application/pdf') {
            errors.value[key] = ['El archivo debe ser un PDF.'];
            return;
        }
        form.value[key] = file;
        const url = URL.createObjectURL(file);
        if (key === 'cv_personal') {
            if (cvPersonalUrl.value?.startsWith('blob:')) {
                URL.revokeObjectURL(cvPersonalUrl.value);
            }
            cvPersonalUrl.value = url;
        } else if (key === 'cv_sunedu') {
            if (cvSuneduUrl.value?.startsWith('blob:')) {
                URL.revokeObjectURL(cvSuneduUrl.value);
            }
            cvSuneduUrl.value = url;
        }
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
    if (cvPersonalUrl.value) URL.revokeObjectURL(cvPersonalUrl.value);
    if (cvSuneduUrl.value) URL.revokeObjectURL(cvSuneduUrl.value);
    cvPersonalUrl.value = null;
    cvSuneduUrl.value = null;
};

const submit = () => {
    const data = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
        if (value !== null) data.append(key, value as any);
    });

    console.log(form.value); // Verifica que form.value esta inicializado correctamente

    router.post('/docents', data, {
        forceFormData: true,
        onSuccess: () => {
            errors.value = {};
            Swal.fire({
                icon: 'success',
                title: 'Docente creado',
                text: 'El docente ha sido creado exitosamente.',
            }).then(() => {
                router.visit('/docents'); // Redirigir al inicio de docentes
            });
        },
        onError: (formErrors) => {
            errors.value = Object.fromEntries(
                Object.entries(formErrors).map(([key, value]) => [key, Array.isArray(value) ? value : [String(value)]])
            );
            Swal.fire({
                icon: 'error',
                title: 'Corrige los errores',
                text: 'Revisa los campos marcados e intentalo nuevamente.',
            });
        },
    });
};

const confirmCancel = () => {
    Swal.fire({
        title: 'Estas seguro?',
        text: 'Los cambios no guardados se perderan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, cancelar',
        cancelButtonText: 'No',
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit('/docents');
        }
    });
};

onBeforeUnmount(() => {
    if (cvPersonalUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(cvPersonalUrl.value);
    }
    if (cvSuneduUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(cvSuneduUrl.value);
    }
});
</script>

<template>
    <div v-if="form">
        <Head title="Crear Docente" />
        <AppLayout :breadcrumbs="Breadcrumbs">
            <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 class="text-2xl font-bold">Crear Docente</h1>
                <form @submit.prevent="submit" class="space-y-6 max-w-lg">
                    <div>
                        <label class="font-semibold">Nombre</label>
                        <input v-model="form.nombre" type="text" placeholder="Nombre" class="w-full rounded-md border border-gray-300 p-2" />
                        <p v-if="errors.nombre" class="text-red-500 text-sm">{{ errors.nombre[0] }}</p>
                    </div>
                    <div>
                        <label class="font-semibold">Apellido</label>
                        <input v-model="form.apellido" type="text" placeholder="Apellido" class="w-full rounded-md border border-gray-300 p-2" />
                        <p v-if="errors.apellido" class="text-red-500 text-sm">{{ errors.apellido[0] }}</p>
                    </div>
                    <div>
                        <label class="font-semibold">DNI</label>
                        <input v-model="form.dni" type="text" placeholder="DNI" class="w-full rounded-md border border-gray-300 p-2" />
                        <p v-if="errors.dni" class="text-red-500 text-sm">{{ errors.dni[0] }}</p>
                    </div>
                    <div>
                        <label class="font-semibold">Email</label>
                        <input v-model="form.email" type="email" placeholder="Email" class="w-full rounded-md border border-gray-300 p-2" />
                        <p v-if="errors.email" class="text-red-500 text-sm">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="font-semibold">Telefono</label>
                        <input v-model="form.telefono" type="text" placeholder="Telefono" class="w-full rounded-md border border-gray-300 p-2" />
                        <p v-if="errors.telefono" class="text-red-500 text-sm">{{ errors.telefono[0] }}</p>
                    </div>
                    <div>
                        <label class="font-semibold">Especialidad</label>
                        <input v-model="form.especialidad" type="text" placeholder="Especialidad" class="w-full rounded-md border border-gray-300 p-2" />
                        <p v-if="errors.especialidad" class="text-red-500 text-sm">{{ errors.especialidad[0] }}</p>
                    </div>
                    <div>
                        <label class="font-semibold">CV Personal (PDF)</label>
                        <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_personal')" />
                        <div v-if="cvPersonalUrl" class="mt-3">
                            <PdfFileCard :url="cvPersonalUrl" :name="form.cv_personal?.name ?? 'cv_personal.pdf'" />
                        </div>
                    </div>
                    <div>
                        <label class="font-semibold">CV Sunedu (PDF)</label>
                        <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_sunedu')" />
                        <div v-if="cvSuneduUrl" class="mt-3">
                            <PdfFileCard :url="cvSuneduUrl" :name="form.cv_sunedu?.name ?? 'cv_sunedu.pdf'" />
                        </div>
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
                        <Link
                            href="/docents"
                            class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600"
                            @click.prevent="confirmCancel"
                        >
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    </div>
    <div v-else>
        <p>Cargando...</p>
    </div>
</template>










