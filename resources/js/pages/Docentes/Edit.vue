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

type FormFields = {
  nombre: any;
  apellido: any;
  dni: any;
  email: any;
  telefono: any;
  especialidad: any;
  cv_personal: File | null;
  cv_sunedu: File | null;
  cul: File | null;
  cv_personal_nombre?: string;
  cv_sunedu_nombre?: string;
  cul_nombre?: string;
  linkedin: any;
  estado: any;
  cip: any;
  [key: string]: any;
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
  cul: null,
  cv_personal_nombre: '',
  cv_sunedu_nombre: '',
  cul_nombre: '',
  linkedin: props.docent.linkedin ?? '',
  estado: props.docent.estado ?? 'activo',
  cip: props.docent.cip ?? '',
});

const cvPersonalUrl = ref<string | null>(
  props.docent.cv_personal ? `/storage/${props.docent.cv_personal}` : null,
);
const cvSuneduUrl = ref<string | null>(
  props.docent.cv_sunedu ? `/storage/${props.docent.cv_sunedu}` : null,
);
const culUrl = ref<string | null>(
  props.docent.cul ? `/storage/${props.docent.cul}` : null,
);

const cvPersonalInput = ref<HTMLInputElement | null>(null);
const cvSuneduInput = ref<HTMLInputElement | null>(null);
const culInput = ref<HTMLInputElement | null>(null);
const cvPersonalDelete = ref(false);
const cvSuneduDelete = ref(false);
const culDelete = ref(false);

// Flujo de CV docente (archivo firmado)
const cvUploadFile = ref<File | null>(null);
const cvUploadError = ref<string | null>(null);

// Toast simple
const showToast = ref(false);
const toastMsg = ref('');
const toastType = ref<'success' | 'error'>('success');

const toast = (msg: string, type: 'success' | 'error' = 'success') => {
  toastMsg.value = msg;
  toastType.value = type;
  showToast.value = true;
  setTimeout(() => {
    showToast.value = false;
  }, 2500);
};

const handleFileChange = (e: Event, key: string) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    form.value[key] = file;
    const url = URL.createObjectURL(file);
    if (key === 'cv_personal') {
      cvPersonalDelete.value = false;
      if (cvPersonalUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(cvPersonalUrl.value);
      }
      cvPersonalUrl.value = url;
      toast('CV Personal listo para guardar.', 'success');
    } else if (key === 'cv_sunedu') {
      cvSuneduDelete.value = false;
      if (cvSuneduUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(cvSuneduUrl.value);
      }
      cvSuneduUrl.value = url;
      toast('CV Sunedu listo para guardar.', 'success');
    } else if (key === 'cul') {
      culDelete.value = false;
      if (culUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(culUrl.value);
      }
      culUrl.value = url;
      toast('CUL listo para guardar.', 'success');
    }
  }
};

const errors = ref<Record<string, string[]>>({});

const handleCvUploadChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    cvUploadFile.value = target.files[0];
    cvUploadError.value = null;
    toast('Archivo seleccionado. Haz clic en "Subir CV".', 'success');
  }
};

const submitCvUpload = () => {
  if (!cvUploadFile.value) {
    cvUploadError.value = 'Selecciona un archivo primero.';
    return;
  }

  const data = new FormData();
  data.append('cv', cvUploadFile.value);

  router.post(`/docentes/${props.docent.id}/cv/upload`, data, {
    forceFormData: true,
    onSuccess: () => {
      cvUploadError.value = null;
      cvUploadFile.value = null;
      toast('CV subido correctamente.', 'success');
      router.reload();
    },
    onError: () => {
      cvUploadError.value = 'Error al subir el CV.';
      toast('Error al subir el CV.', 'error');
    },
  });
};

const triggerFileInput = (key: 'cv_personal' | 'cv_sunedu' | 'cul') => {
  if (key === 'cv_personal') cvPersonalInput.value?.click();
  else if (key === 'cv_sunedu') cvSuneduInput.value?.click();
  else culInput.value?.click();
};

const clearFile = (key: 'cv_personal' | 'cv_sunedu' | 'cul') => {
  if (key === 'cv_personal') {
    if (cvPersonalUrl.value?.startsWith('blob:')) {
      URL.revokeObjectURL(cvPersonalUrl.value);
    }
    cvPersonalUrl.value = null;
    form.value.cv_personal = null as any;
    form.value.cv_personal_nombre = '';
    cvPersonalDelete.value = true;
    toast('CV Personal se eliminar� al guardar cambios.', 'success');
  } else if (key === 'cv_sunedu') {
    if (cvSuneduUrl.value?.startsWith('blob:')) {
      URL.revokeObjectURL(cvSuneduUrl.value);
    }
    cvSuneduUrl.value = null;
    form.value.cv_sunedu = null as any;
    form.value.cv_sunedu_nombre = '';
    cvSuneduDelete.value = true;
    toast('CV Sunedu se eliminar� al guardar cambios.', 'success');
  } else {
    if (culUrl.value?.startsWith('blob:')) {
      URL.revokeObjectURL(culUrl.value);
    }
    culUrl.value = null;
    form.value.cul = null as any;
    form.value.cul_nombre = '';
    culDelete.value = true;
    toast('CUL se eliminar� al guardar cambios.', 'success');
  }
};

const submit = () => {
  const data = new FormData();
  Object.entries(form.value).forEach(([key, value]) => {
    if (value !== null) data.append(key, value as any);
  });
  data.append('cv_personal_delete', cvPersonalDelete.value ? '1' : '0');
  data.append('cv_sunedu_delete', cvSuneduDelete.value ? '1' : '0');
  data.append('cul_delete', culDelete.value ? '1' : '0');
  data.append('_method', 'put');

  router.post(`/docents/${props.docent.id}`, data, {
    forceFormData: true,
    onSuccess: () => {
      errors.value = {};
      router.visit('/docents', { replace: true });
    },
    onError: (formErrors) => {
      errors.value = Object.fromEntries(
        Object.entries(formErrors).map(([key, value]) => [
          key,
          Array.isArray(value) ? value : [String(value)],
        ]),
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
  if (culUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(culUrl.value);
  }
});
</script>

<template>
  <Head title="Editar Docente" />
  <AppLayout :breadcrumbs="Breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
      <h1 class="text-2xl font-bold">Editar Docente</h1>

      <!-- Flujo de CV docente -->
      <div class="mb-4 space-x-2">
        <Button asChild variant="outline">
          <a href="/docentes/cv/plantilla" target="_blank" rel="noopener noreferrer">
            Descargar plantilla vacía
          </a>
        </Button>
        <Button asChild variant="outline">
          <a :href="`/docentes/${props.docent.id}/cv/generar`" target="_blank" rel="noopener noreferrer">
            Generar CV automático
          </a>
        </Button>
      </div>

      <div class="mb-6 border rounded-md p-4 bg-muted/40">
        <h2 class="font-semibold mb-2 text-sm">Subir CV firmado / actualizado</h2>
        <div class="flex items-center gap-2">
          <input type="file" accept=".doc,.docx,.pdf" @change="handleCvUploadChange" class="text-sm" />
          <Button type="button" variant="outline" @click="submitCvUpload"> Subir CV </Button>
        </div>
        <p v-if="cvUploadError" class="text-xs text-red-500 mt-1">{{ cvUploadError }}</p>
      </div>

      <!-- Formulario de edici�n -->
      <form @submit.prevent="submit" class="space-y-6 max-w-lg">
        <div>
          <label>Nombre</label>
          <input
            v-model="form.nombre"
            type="text"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <p v-if="errors.nombre" class="text-red-500 text-sm">{{ errors.nombre?.[0] }}</p>
        </div>
        <div>
          <label>Apellido</label>
          <input
            v-model="form.apellido"
            type="text"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <p v-if="errors.apellido" class="text-red-500 text-sm">{{ errors.apellido?.[0] }}</p>
        </div>
        <div>
          <label>DNI</label>
          <input
            v-model="form.dni"
            type="text"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <p v-if="errors.dni" class="text-red-500 text-sm">{{ errors.dni?.[0] }}</p>
        </div>
        <div>
          <label>Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div>
          <label>Teléfono</label>
          <input
            v-model="form.telefono"
            type="text"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div>
          <label>Especialidad</label>
          <input
            v-model="form.especialidad"
            type="text"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>

        <!-- CV Personal -->
        <div>
          <label>CV Personal (PDF)</label>
          <div class="flex items-center gap-2">
            <input
              ref="cvPersonalInput"
              type="file"
              accept="application/pdf"
              @change="e => handleFileChange(e, 'cv_personal')"
            />
            <input
              v-model="form.cv_personal_nombre"
              placeholder="Nombre (sin extensi�n)"
              class="rounded border border-border bg-background text-foreground px-2 py-1"
            />
          </div>
          <div v-if="cvPersonalUrl" class="mt-3">
            <PdfFileCard
              :url="cvPersonalUrl"
              :name="form.cv_personal?.name ?? props.docent.cv_personal?.split('/').pop() ?? 'cv_personal.pdf'"
            />
            <div class="mt-2 flex gap-2">
              <Button
                type="button"
                variant="outline"
                size="icon"
                class="h-8 w-8"
                title="Cambiar archivo"
                @click="() => triggerFileInput('cv_personal')"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                  <path
                    d="M4 20h4l10.5-10.5-4-4L4 16v4Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </Button>
              <Button
                type="button"
                variant="outline"
                size="icon"
                class="h-8 w-8 text-red-600"
                title="Eliminar archivo"
                @click="() => clearFile('cv_personal')"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                  <path
                    d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </Button>
            </div>
            <div class="text-xs text-muted-foreground mt-1">
              Se guardar� como:
              <span class="font-medium">
                {{
                  (form.cv_personal_nombre
                    ? form.cv_personal_nombre
                    : (form.cv_personal as any)?.name?.replace(/\.[^.]+$/, '') || 'cv-personal') + '.pdf'
                }}
              </span>
            </div>
            <p v-if="cvPersonalDelete" class="text-[11px] text-red-500 mt-1">
              El CV Personal se eliminará al guardar los cambios.
            </p>
          </div>
        </div>

        <!-- CV Sunedu -->
        <div>
          <label>CV Sunedu (PDF)</label>
          <div class="flex items-center gap-2">
            <input
              ref="cvSuneduInput"
              type="file"
              accept="application/pdf"
              @change="e => handleFileChange(e, 'cv_sunedu')"
            />
            <input
              v-model="form.cv_sunedu_nombre"
              placeholder="Nombre (sin extensi�n)"
              class="rounded border border-border bg-background text-foreground px-2 py-1"
            />
          </div>
          <div v-if="cvSuneduUrl" class="mt-3">
            <PdfFileCard
              :url="cvSuneduUrl"
              :name="form.cv_sunedu?.name ?? props.docent.cv_sunedu?.split('/').pop() ?? 'cv_sunedu.pdf'"
            />
            <div class="mt-2 flex gap-2">
              <Button
                type="button"
                variant="outline"
                size="icon"
                class="h-8 w-8"
                title="Cambiar archivo"
                @click="() => triggerFileInput('cv_sunedu')"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                  <path
                    d="M4 20h4l10.5-10.5-4-4L4 16v4Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </Button>
              <Button
                type="button"
                variant="outline"
                size="icon"
                class="h-8 w-8 text-red-600"
                title="Eliminar archivo"
                @click="() => clearFile('cv_sunedu')"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                  <path
                    d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </Button>
            </div>
            <div class="text-xs text-muted-foreground mt-1">
              Se guardará como:
              <span class="font-medium">
                {{
                  (form.cv_sunedu_nombre
                    ? form.cv_sunedu_nombre
                    : (form.cv_sunedu as any)?.name?.replace(/\.[^.]+$/, '') || 'cv-sunedu') + '.pdf'
                }}
              </span>
            </div>
            <p v-if="cvSuneduDelete" class="text-[11px] text-red-500 mt-1">
              El CV Sunedu se eliminará al guardar los cambios.
            </p>
          </div>
        </div>

        <!-- CUL -->
        <div>
          <label>Certificado Único Laboral (CUL)</label>
          <div class="flex items-center gap-2">
            <input
              ref="culInput"
              type="file"
              accept="application/pdf"
              @change="e => handleFileChange(e, 'cul')"
            />
            <input
              v-model="form.cul_nombre"
              placeholder="Nombre (sin extensi�n)"
              class="rounded border border-border bg-background text-foreground px-2 py-1"
            />
          </div>
          <div v-if="culUrl" class="mt-3">
            <PdfFileCard
              :url="culUrl"
              :name="form.cul?.name ?? props.docent.cul?.split('/').pop() ?? 'cul.pdf'"
            />
            <div class="mt-2 flex gap-2">
              <Button
                type="button"
                variant="outline"
                size="icon"
                class="h-8 w-8"
                title="Cambiar archivo"
                @click="() => triggerFileInput('cul')"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                  <path
                    d="M4 20h4l10.5-10.5-4-4L4 16v4Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </Button>
              <Button
                type="button"
                variant="outline"
                size="icon"
                class="h-8 w-8 text-red-600"
                title="Eliminar archivo"
                @click="() => clearFile('cul')"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                  <path
                    d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </Button>
            </div>
            <div class="text-xs text-muted-foreground mt-1">
              Se guardar� como:
              <span class="font-medium">
                {{
                  (form.cul_nombre
                    ? form.cul_nombre
                    : (form.cul as any)?.name?.replace(/\.[^.]+$/, '') || 'cul') + '.pdf'
                }}
              </span>
            </div>
            <p v-if="culDelete" class="text-[11px] text-red-500 mt-1">
              El CUL se eliminar� al guardar los cambios.
            </p>
          </div>
        </div>

        <div>
          <label>LinkedIn</label>
          <input
            v-model="form.linkedin"
            type="text"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div>
          <label>Estado CIP</label>
          <select
            v-model="form.estado"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="activo">Habilitado</option>
            <option value="inactivo">No habilitado</option>
          </select>
        </div>
        <div>
          <label>N° CIP</label>
          <input
            v-model="form.cip"
            type="text"
            class="w-full rounded-md border border-border bg-background text-foreground px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <Button type="submit" class="bg-primary text-primary-foreground"> Guardar </Button>
      </form>
    </div>
    <div
      v-if="showToast"
      class="fixed bottom-4 right-4 px-4 py-2 rounded shadow text-sm"
      :class="toastType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
    >
      {{ toastMsg }}
    </div>
  </AppLayout>
</template>
