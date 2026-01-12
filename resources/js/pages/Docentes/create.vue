<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, onBeforeUnmount, computed } from 'vue';
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
    cv_sunedu: File | null;
    linkedin: string;
    estado: string;
    cip: string;
    crear_usuario: boolean;
    user_password: string;
    user_password_confirmation: string;
};

const page: any = usePage();
const especialidades = computed(() => page.props.especialidades ?? []);

const form = ref<FormType>({
    nombre: '',
    apellido: '',
    dni: '',
    email: '',
    telefono: '',
    especialidad: '',
    cv_sunedu: null,
    linkedin: '',
    estado: 'activo',
    cip: '',
    crear_usuario: false,
    user_password: '',
    user_password_confirmation: '',
});

const errors = ref<Record<string, string[]>>({}); // Para almacenar los errores de v?lidaci?n

const cvSuneduUrl = ref<string | null>(null);

const cvSuneduInput = ref<HTMLInputElement | null>(null);

// Toast simple para feedback de archivos
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

const handleFileChange = (e: Event, key: keyof Pick<FormType, 'cv_sunedu'>) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        if (file.type !== 'application/pdf') {
            errors.value[key] = ['El archivo debe ser un PDF.'];
            return;
        }
        form.value[key] = file;
        const url = URL.createObjectURL(file);
        if (key === 'cv_sunedu') {
            if (cvSuneduUrl.value?.startsWith('blob:')) {
                URL.revokeObjectURL(cvSuneduUrl.value);
            }
            cvSuneduUrl.value = url;
            toast('CV Sunedu listo para guardar.', 'success');
        }
    }
};

const clearFile = (key: keyof Pick<FormType, 'cv_sunedu'>) => {
    if (key === 'cv_sunedu') {
        if (cvSuneduUrl.value?.startsWith('blob:')) {
            URL.revokeObjectURL(cvSuneduUrl.value);
        }
        cvSuneduUrl.value = null;
        form.value.cv_sunedu = null;
        delete errors.value.cv_sunedu;
        toast('CV Sunedu eliminado del formulario.', 'success');
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
        cv_sunedu: null,
        linkedin: '',
        estado: 'activo',
        cip: '',
        crear_usuario: false,
        user_password: '',
        user_password_confirmation: '',
    };
    if (cvSuneduUrl.value) URL.revokeObjectURL(cvSuneduUrl.value);
    cvSuneduUrl.value = null;
};

const submit = () => {
    if (form.value.crear_usuario) {
        const password = form.value.user_password ?? '';
        const confirmation = form.value.user_password_confirmation ?? '';
        delete errors.value.user_password;
        delete errors.value.user_password_confirmation;

        if (!password) {
            errors.value.user_password = ['La La contrase?a es obligatoria.'];
            Swal.fire({
                icon: 'error',
                title: 'Corrige los errores',
                text: 'Ingresa una contrase?a v?lida.',
            });
            return;
        }

        if (password !== confirmation) {
            errors.value.user_password_confirmation = ['Las contrase?as no coinciden.'];
            Swal.fire({
                icon: 'error',
                title: 'Corrige los errores',
                text: 'La confirmaci?n de contrase?a no coincide.',
            });
            return;
        }
    }
    const data = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
        if (key === 'crear_usuario') {
            data.append(key, value ? '1' : '0');
            return;
        }
        if (value !== null) {
            data.append(key, value as any);
        }
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
            const normalizedErrors = Object.fromEntries(
                Object.entries(formErrors).map(([key, value]) => [key, Array.isArray(value) ? value : [String(value)]])
            );
            errors.value = normalizedErrors;
            const firstError = Object.values(normalizedErrors).flat()[0];
            Swal.fire({
                icon: 'error',
                title: 'Corrige los errores',
                text: firstError ?? 'Revisa los campos marcados e intentalo nuevamente.',
            });
        },
    });
};

const confirmCancel = () => {
    Swal.fire({
        title: '?Est?s seguro?',
        text: 'Los cambios no guardados se perderan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'S?, cancelar',
        cancelButtonText: 'No',
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit('/docents');
        }
    });
};

onBeforeUnmount(() => {
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
        <form @submit.prevent="submit" class="space-y-6 max-w-lg bg-card text-foreground p-6 rounded-xl shadow-sm border border-border">
          <div>
            <label class="font-semibold">Nombre</label>
            <input v-model="form.nombre" type="text" placeholder="Nombre" class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring" />
            <p v-if="errors.nombre" class="text-red-500 text-sm">{{ errors.nombre[0] }}</p>
          </div>
          <div>
            <label class="font-semibold">Apellido</label>
            <input v-model="form.apellido" type="text" placeholder="Apellido" class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring" />
            <p v-if="errors.apellido" class="text-red-500 text-sm">{{ errors.apellido[0] }}</p>
          </div>
          <div>
            <label class="font-semibold">DNI</label>
            <input v-model="form.dni" type="text" placeholder="DNI" class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring" />
            <p v-if="errors.dni" class="text-red-500 text-sm">{{ errors.dni[0] }}</p>
          </div>
          <div>
            <label class="font-semibold">Email</label>
            <input v-model="form.email" type="email" placeholder="Email" class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring" />
            <p v-if="errors.email" class="text-red-500 text-sm">{{ errors.email[0] }}</p>
          </div>
          <div>
            <label class="font-semibold">Telefono</label>
            <input v-model="form.telefono" type="text" placeholder="Telefono" class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring" />
            <p v-if="errors.telefono" class="text-red-500 text-sm">{{ errors.telefono[0] }}</p>
          </div>
          <div>
            <label class="font-semibold">Especialidad</label>
            <input
              v-model="form.especialidad"
              list="especialidades-list"
              type="text"
              placeholder="Especialidad"
              class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <datalist id="especialidades-list">
              <option v-for="e in especialidades" :key="e.id" :value="e.nombre" />
            </datalist>
            <p class="text-xs text-muted-foreground mt-1">
              Si no existe, escribe una nueva y se agregara al catalogo.
            </p>
            <p v-if="errors.especialidad" class="text-red-500 text-sm">{{ errors.especialidad[0] }}</p>
          </div>
          <div>
            <label class="font-semibold">CV Sunedu (PDF)</label>
            <input
              ref="cvSuneduInput"
              type="file"
              accept="application/pdf"
              @change="e => handleFileChange(e, 'cv_sunedu')"
            />
            <div v-if="cvSuneduUrl" class="mt-3 flex items-start gap-3">
              <PdfFileCard :url="cvSuneduUrl" :name="form.cv_sunedu?.name ?? 'cv_sunedu.pdf'" />
              <div class="mt-1 flex gap-2 text-xs text-muted-foreground">
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  class="h-8 w-8"
                  title="Cambiar archivo"
                  @click="() => cvSuneduInput?.click()"
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
            </div>
          </div>
          <div>
            <label class="font-semibold">LinkedIn</label>
            <input v-model="form.linkedin" type="text" placeholder="LinkedIn" class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
          <div>
            <label class="font-semibold">Estado CIP</label>
            <select
              v-model="form.estado"
              class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="activo">Habilitado</option>
              <option value="inactivo">No habilitado</option>
            </select>
            <p v-if="errors.estado" class="text-red-500 text-sm">{{ errors.estado[0] }}</p>
          </div>
          <div>
            <label class="font-semibold">CIP</label>
            <input v-model="form.cip" type="text" placeholder="CIP" class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
          <div class="border border-border rounded-md p-3 space-y-2">
            <label class="font-semibold flex items-center gap-2">
              <input
                type="checkbox"
                v-model="form.crear_usuario"
                class="rounded border-border text-primary focus:ring-ring"
              />
              Crear cuenta de acceso para este docente
            </label>
            <p class="text-xs text-muted-foreground">
              Si se activa, se creará un usuario con el email del docente y la contraseña indicada a continuación.
            </p>
            <div v-if="form.crear_usuario" class="space-y-2 mt-2">
              <div>
                <label class="font-semibold text-sm">Contraseña inicial</label>
                <input
                  v-model="form.user_password"
                  type="password"
                  placeholder="Contraseña para el docente"
                  class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring"
                />
                <p v-if="errors.user_password" class="text-red-500 text-sm">{{ errors.user_password[0] }}</p>
              </div>
              <div>
                <label class="font-semibold text-sm">Confirmar contraseña</label>
                <input
                  v-model="form.user_password_confirmation"
                  type="password"
                  placeholder="Repetir contraseña"
                  class="w-full rounded-md border border-border bg-background text-foreground p-2 focus:outline-none focus:ring-2 focus:ring-ring"
                />
                <p v-if="errors.user_password_confirmation" class="text-red-500 text-sm">{{ errors.user_password_confirmation[0] }}</p>
              </div>
            </div>
          </div>
          <div class="flex gap-4">
            <Button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-600">
              Crear Docente
            </Button>
            <Link href="/docents" class="bg-destructive text-destructive-foreground px-4 py-2 rounded-md hover:opacity-90" @click.prevent="confirmCancel">
              Cancelar
            </Link>
          </div>
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
  </div>
  <div v-else>
    <p>Cargando...</p>
  </div>
</template>












