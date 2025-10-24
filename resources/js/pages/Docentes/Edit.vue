<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onBeforeUnmount } from 'vue';
import { Button } from '@/components/ui/button';
import PdfFileCard from '@/components/PdfFileCard.vue';

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
    cv_personal_nombre?: string;
    cv_sunedu_nombre?: string;
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
    cv_personal_nombre: '',
    cv_sunedu_nombre: '',
    linkedin: props.docent.linkedin ?? '',
    estado: props.docent.estado ?? 'activo',
    cip: props.docent.cip ?? '',
});

const cvPersonalUrl = ref<string | null>(props.docent.cv_personal ? `/storage/${props.docent.cv_personal}` : null);
const cvSuneduUrl = ref<string | null>(props.docent.cv_sunedu ? `/storage/${props.docent.cv_sunedu}` : null);

// Manejar archivos
const handleFileChange = (e: Event, key: string) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
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

const errors = ref<Record<string, string[]>>({});

const submit = () => {
    const data = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
        if (value !== null) data.append(key, value as any);
    });
    data.append('_method', 'put');




    router.post(`/docents/${props.docent.id}`, data, {
        forceFormData: true,
        onSuccess: () => {
            errors.value = {};
            router.visit('/docents', { replace: true }); // Redirigir a la lista de docentes
        },
        onError: (formErrors) => {
            errors.value = Object.fromEntries(
                Object.entries(formErrors).map(([key, value]) => [key, Array.isArray(value) ? value : [String(value)]])
            );
        },
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
  <Head title="Editar Docente" />
  <AppLayout :breadcrumbs="Breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
      <h1 class="text-2xl font-bold">Editar Docente</h1>
      <form @submit.prevent="submit" class="space-y-6 max-w-lg">
        <div>
          <label>Nombre</label>
          <input v-model="form.nombre" type="text" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
          <p v-if="errors.nombre" class="text-red-500 text-sm">{{ errors.nombre?.[0] }}</p>
        </div>
        <div>
          <label>Apellido</label>
          <input v-model="form.apellido" type="text" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
          <p v-if="errors.apellido" class="text-red-500 text-sm">{{ errors.apellido?.[0] }}</p>
        </div>
        <div>
          <label>DNI</label>
          <input v-model="form.dni" type="text" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
          <p v-if="errors.dni" class="text-red-500 text-sm">{{ errors.dni?.[0] }}</p>
        </div>
        <div>
          <label>Email</label>
          <input v-model="form.email" type="email" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
        <div>
          <label>Tel?fono</label>
          <input v-model="form.telefono" type="text" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
        <div>
          <label>Especialidad</label>
          <input v-model="form.especialidad" type="text" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
        <div>
          <label>CV Personal (PDF)</label>
          <div class="flex items-center gap-2">
            <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_personal')" />
            <input v-model="form.cv_personal_nombre" placeholder="Nombre (sin extensi?n)" class="rounded border border-border bg-background text-foreground px-2 py-1" />
          </div>
          <div v-if="cvPersonalUrl" class="mt-3">
            <PdfFileCard :url="cvPersonalUrl" :name="form.cv_personal?.name ?? props.docent.cv_personal?.split('/').pop() ?? 'cv_personal.pdf'" />
          <div class="text-xs text-muted-foreground mt-1">Se guardar? como: <span class="font-medium">{{ (form.cv_personal_nombre ? form.cv_personal_nombre : (form.cv_personal as any)?.name?.replace(/\.[^.]+$/, "") || "cv-personal") }}.pdf</span></div>
          </div>
        </div>
        <div>
          <label>CV Sunedu (PDF)</label>
          <div class="flex items-center gap-2">
            <input type="file" accept="application/pdf" @change="e => handleFileChange(e, 'cv_sunedu')" />
            <input v-model="form.cv_sunedu_nombre" placeholder="Nombre (sin extensi?n)" class="rounded border border-border bg-background text-foreground px-2 py-1" />
          </div>
          <div v-if="cvSuneduUrl" class="mt-3">
            <PdfFileCard :url="cvSuneduUrl" :name="form.cv_sunedu?.name ?? props.docent.cv_sunedu?.split('/').pop() ?? 'cv_sunedu.pdf'" />
          <div class="text-xs text-muted-foreground mt-1">Se guardar? como: <span class="font-medium">{{ (form.cv_sunedu_nombre ? form.cv_sunedu_nombre : (form.cv_sunedu as any)?.name?.replace(/\.[^.]+$/, "") || "cv-sunedu") }}.pdf</span></div>
          </div>
        </div>
        <div>
          <label>LinkedIn</label>
          <input v-model="form.linkedin" type="text" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
        <div>
          <label>Estado</label>
          <select v-model="form.estado" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring">
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
          </select>
        </div>
        <div>
          <label>CIP</label>
          <input v-model="form.cip" type="text" class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
      <Button type="submit" class="bg-primary text-primary-foreground">Guardar</Button>
      </form>
    </div>
  </AppLayout>
</template>





