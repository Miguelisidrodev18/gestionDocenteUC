<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import type { BreadcrumbItem } from '@/types';
const page = usePage<any>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Responsabilidades', href: '/responsabilidades' }];

const cursos = computed(() => page.props.cursos ?? []);
const responsables = computed(() => page.props.responsables ?? []);
const docentes = computed(() => page.props.docentes ?? []);
const filters = computed(() => page.props.filters ?? {});
const periodosCatalog = computed(() => page.props.periodosCatalog ?? []);
const sedesCatalog = computed(() => page.props.sedesCatalog ?? []);
const currentUserId = computed<number | null>(() => page.props.auth?.user?.id ?? null);
const currentUserRole = computed<string | null>(() => page.props.currentUserRole ?? page.props.auth?.user?.role ?? null);

const periodo = ref<string>(filters.value.periodo ?? '2025-2');

const modalOpen = ref(false);
const cursoSeleccionado = ref<any | null>(null);
const responsableSeleccionado = ref<string>('');
const sedeId = ref<string>('');
const modalidadDocente = ref<string>('');
const motivo = ref<string>('');
const cursoAsignacion = ref<string>('');
const docenteAsignacion = ref<string>('');
const sedeAsignacion = ref<string>('');
const modalidadAsignacion = ref<string>('');
const motivoAsignacion = ref<string>('');

const responsablesOtros = computed(() => {
  const docenteIds = new Set(docentes.value.map((d: any) => String(d.user_id)));
  return responsables.value.filter((r: any) => !docenteIds.has(String(r.id)));
});

const cursoSeleccionNuevo = computed(() => {
  return cursos.value.find((c: any) => String(c.id) === String(cursoAsignacion.value)) ?? null;
});

const extraerModalidades = (curso: any): string[] => {
  if (!curso) return [];
  const raw: string[] = [];
  if (curso.modalidad_rel?.nombre) raw.push(String(curso.modalidad_rel.nombre));
  if (curso.modalidadRel?.nombre) raw.push(String(curso.modalidadRel.nombre));
  if (curso.modalidad) raw.push(String(curso.modalidad));
  if (Array.isArray(curso.modalidades)) {
    raw.push(...curso.modalidades.map((m: any) => String(m?.nombre ?? m)));
  }
  const parts = raw
    .flatMap((value) => value.split(/[;,/|]+/))
    .map((value) => value.trim())
    .filter((value) => value.length > 0);
  return Array.from(new Set(parts));
};

const modalidadesCurso = computed(() => {
  return extraerModalidades(cursoSeleccionNuevo.value);
});

const modalidadesCursoSeleccionado = computed(() => {
  return extraerModalidades(cursoSeleccionado.value);
});

watch(
  cursoAsignacion,
  () => {
    if (!modalidadAsignacion.value || !modalidadesCurso.value.includes(modalidadAsignacion.value)) {
      modalidadAsignacion.value = modalidadesCurso.value[0] ?? '';
    }
  },
  { immediate: true },
);

function abrirModal(curso: any) {
  cursoSeleccionado.value = curso;
  responsableSeleccionado.value = String(curso.assignment?.responsable_user_id ?? curso.responsable?.id ?? '');
  sedeId.value = String(curso.assignment?.sede_id ?? '');
  const opciones = extraerModalidades(curso);
  const actual = String(curso.assignment?.modalidad_docente ?? '');
  modalidadDocente.value = actual && opciones.includes(actual) ? actual : (opciones[0] ?? actual);
  motivo.value = '';
  modalOpen.value = true;
}

function cerrarModal() {
  modalOpen.value = false;
  cursoSeleccionado.value = null;
}

function guardar() {
  if (!cursoSeleccionado.value || !responsableSeleccionado.value) return;
  router.patch(
    `/responsabilidades/${cursoSeleccionado.value.id}`,
    {
      responsable_user_id: responsableSeleccionado.value,
      sede_id: sedeId.value || undefined,
      modalidad_docente: modalidadDocente.value || undefined,
      reason: motivo.value || undefined,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        cerrarModal();
      },
    },
  );
}

function asignarResponsable() {
  if (!cursoAsignacion.value || !docenteAsignacion.value) return;
  router.patch(
    `/responsabilidades/${cursoAsignacion.value}`,
    {
      responsable_user_id: docenteAsignacion.value,
      sede_id: sedeAsignacion.value || undefined,
      modalidad_docente: modalidadAsignacion.value || undefined,
      reason: motivoAsignacion.value || undefined,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        cursoAsignacion.value = '';
        docenteAsignacion.value = '';
        sedeAsignacion.value = '';
        modalidadAsignacion.value = '';
        motivoAsignacion.value = '';
      },
    },
  );
}

function aceptarAsignacion(curso: any) {
  if (!curso.assignment) return;
  router.post(
    `/responsabilidades/${curso.assignment.id}/aceptar`,
    {},
    { preserveScroll: true },
  );
}

function rechazarAsignacion(curso: any) {
  if (!curso.assignment) return;
  router.post(
    `/responsabilidades/${curso.assignment.id}/rechazar`,
    {},
    { preserveScroll: true },
  );
}

const estadoLabel = (curso: any) => {
  if (!curso.assignment) return 'Pendiente';
  const status = curso.assignment.status ?? 'aceptado';
  if (status === 'invitado') return 'Pendiente de aceptación';
  if (status === 'rechazado') return 'Rechazado';
  return 'Aceptado';
};

const estadoClase = (curso: any) => {
  if (!curso.assignment) return 'bg-gray-300 text-gray-800';
  const status = curso.assignment.status ?? 'aceptado';
  if (status === 'invitado') return 'bg-amber-500 text-white';
  if (status === 'rechazado') return 'bg-red-500 text-white';
  return 'bg-emerald-600 text-white';
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
<Head title="Responsabilidades de cursos" />
    <div class="p-6 space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Responsables de cursos</h1>
        <div class="flex items-center gap-2">
          <label class="text-sm flex items-center gap-2">
            <span class="text-muted-foreground">Periodo:</span>
            <select
              v-model="periodo"
              class="border rounded px-2 py-1 text-sm bg-background text-foreground"
              @change="router.get('/responsabilidades', { periodo }, { preserveScroll: true })"
            >
              <option v-if="!periodosCatalog.length" value="2025-2">2025-2</option>
              <option v-if="!periodosCatalog.length" value="2026-0">2026-0</option>
              <option v-if="!periodosCatalog.length" value="2026-1">2026-1</option>
              <option v-for="p in periodosCatalog" :key="p.id" :value="p.codigo">
                {{ p.codigo }}
              </option>
            </select>
          </label>
        </div>
      </div>

      <div v-if="currentUserRole === 'admin'" class="border rounded-xl bg-card p-4">
        <h2 class="text-base font-semibold mb-2">Asignar responsable a un curso</h2>
        <p class="text-xs text-muted-foreground mb-4">
          Selecciona el curso, el docente responsable y la modalidad docente para registrar la asignacion.
        </p>
        <div class="grid gap-3 md:grid-cols-2">
          <label class="text-sm">
            <span class="text-muted-foreground">Curso</span>
            <select
              v-model="cursoAsignacion"
              class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
            >
              <option value="">Seleccione curso</option>
              <option v-for="curso in cursos" :key="curso.id" :value="curso.id">
                {{ curso.codigo }} - {{ curso.nombre }}
              </option>
            </select>
          </label>
          <label class="text-sm">
            <span class="text-muted-foreground">Docente responsable</span>
            <select
              v-model="docenteAsignacion"
              class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
            >
              <option value="">Seleccione docente</option>
              <option v-for="doc in docentes" :key="doc.id" :value="doc.user_id">
                {{ doc.nombre }} {{ doc.apellido }}
              </option>
            </select>
          </label>
          <label class="text-sm">
            <span class="text-muted-foreground">Modalidad docente</span>
            <select
              v-if="modalidadesCurso.length"
              v-model="modalidadAsignacion"
              class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
            >
              <option value="">Seleccione modalidad</option>
              <option v-for="mod in modalidadesCurso" :key="mod" :value="mod">
                {{ mod }}
              </option>
            </select>
            <input
              v-else
              v-model="modalidadAsignacion"
              class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              placeholder="Presencial, Virtual, etc."
            />
          </label>
          <label class="text-sm">
            <span class="text-muted-foreground">Sede (opcional)</span>
            <select
              v-model="sedeAsignacion"
              class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
            >
              <option value="">Seleccione sede</option>
              <option v-for="s in sedesCatalog" :key="s.id" :value="s.id">
                {{ s.nombre }}
              </option>
            </select>
          </label>
          <label class="text-sm md:col-span-2">
            <span class="text-muted-foreground">Motivo / comentario (opcional)</span>
            <textarea
              v-model="motivoAsignacion"
              rows="2"
              class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
            />
          </label>
        </div>
        <div class="mt-3 flex justify-end">
          <button
            type="button"
            class="px-3 py-1 text-sm rounded bg-primary text-primary-foreground hover:opacity-90"
            :disabled="!cursoAsignacion || !docenteAsignacion"
            @click="asignarResponsable"
          >
            Asignar
          </button>
        </div>
      </div>

      <div v-if="cursos.length === 0" class="text-sm text-muted-foreground">
        No hay cursos para el periodo seleccionado.
      </div>

      <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="curso in cursos"
          :key="curso.id"
          class="border rounded-lg bg-card p-4 flex flex-col justify-between"
        >
          <div class="space-y-1">
            <div class="font-semibold text-sm md:text-base">
              {{ curso.codigo }} - {{ curso.nombre }}
            </div>
            <div class="text-xs text-muted-foreground">
              Modalidad:
              <span class="font-medium text-foreground">
                {{ curso.modalidad_rel?.nombre ?? curso.modalidadRel?.nombre ?? '-' }}
              </span>
            </div>
            <div class="text-xs text-muted-foreground">
              Docente principal:
              <span class="font-medium text-foreground">
                {{ curso.docente?.nombre ?? 'Sin asignar' }}
              </span>
            </div>
            <div class="text-xs text-muted-foreground">
              Responsable actual:
              <span class="font-medium text-foreground">
                {{ curso.assignment?.responsable?.name ?? curso.responsable?.name ?? 'Sin asignar' }}
              </span>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between">
            <span
              class="px-3 py-1 rounded-full text-xs font-semibold"
              :class="estadoClase(curso)"
            >
              {{ estadoLabel(curso) }}
            </span>
            <div class="flex items-center gap-2">
              <button
                v-if="curso.assignment && curso.assignment.status === 'invitado' && currentUserId && curso.assignment.responsable_user_id === currentUserId"
                type="button"
                class="px-3 py-1 text-xs rounded border bg-emerald-600 text-white hover:bg-emerald-700"
                @click="aceptarAsignacion(curso)"
              >
                Aceptar
              </button>
              <button
                v-if="curso.assignment && curso.assignment.status === 'invitado' && currentUserId && curso.assignment.responsable_user_id === currentUserId"
                type="button"
                class="px-3 py-1 text-xs rounded border bg-red-600 text-white hover:bg-red-700"
                @click="rechazarAsignacion(curso)"
              >
                Rechazar
              </button>
              <button
                type="button"
                class="px-3 py-1 text-xs rounded border bg-primary text-primary-foreground hover:opacity-90"
                v-if="currentUserRole === 'admin'"
                @click="abrirModal(curso)"
              >
                Editar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal edición -->
      <div
        v-if="modalOpen && cursoSeleccionado"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      >
        <div class="bg-card border rounded-xl shadow-xl max-w-2xl w-full p-4 space-y-4">
          <div class="flex justify-between items-start gap-2">
            <div>
              <h2 class="text-lg font-semibold">Editar responsable</h2>
              <p class="text-xs text-muted-foreground">
                {{ cursoSeleccionado.codigo }} - {{ cursoSeleccionado.nombre }}
              </p>
            </div>
            <button
              type="button"
              class="text-muted-foreground hover:text-foreground"
              @click="cerrarModal"
            >
              x
            </button>
          </div>

          <div class="space-y-2">
            <label class="block text-sm">
              <span class="text-muted-foreground">Docente responsable</span>
              <select
                v-model="responsableSeleccionado"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              >
                <option value="">Seleccione docente</option>
                <optgroup label="Docentes">
                  <option
                    v-for="doc in docentes"
                    :key="doc.id"
                    :value="doc.user_id"
                  >
                    {{ doc.nombre }} {{ doc.apellido }}
                  </option>
                </optgroup>
                <optgroup v-if="responsablesOtros.length" label="Responsables / Admin">
                  <option
                    v-for="resp in responsablesOtros"
                    :key="resp.id"
                    :value="resp.id"
                  >
                    {{ resp.name }}
                  </option>
                </optgroup>
              </select>
            </label>

            <label class="block text-sm">
              <span class="text-muted-foreground">Sede</span>
              <select
                v-model="sedeId"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              >
                <option value="">Seleccione sede</option>
                <option v-for="s in sedesCatalog" :key="s.id" :value="s.id">
                  {{ s.nombre }}
                </option>
              </select>
            </label>

            <label class="block text-sm">
              <span class="text-muted-foreground">Modalidad docente</span>
              <select
                v-if="modalidadesCursoSeleccionado.length"
                v-model="modalidadDocente"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              >
                <option value="">Seleccione modalidad</option>
                <option v-for="mod in modalidadesCursoSeleccionado" :key="mod" :value="mod">
                  {{ mod }}
                </option>
              </select>
              <input
                v-else
                v-model="modalidadDocente"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
                placeholder="Presencial, Virtual, etc."
              />
            </label>

            <label class="block text-sm">
              <span class="text-muted-foreground">Motivo / comentario</span>
              <textarea
                v-model="motivo"
                rows="3"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              />
            </label>
          </div>

          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="px-3 py-1 text-sm rounded border"
              @click="cerrarModal"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-3 py-1 text-sm rounded bg-primary text-primary-foreground hover:opacity-90"
              :disabled="!responsableSeleccionado"
              @click="guardar"
            >
              Guardar
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
