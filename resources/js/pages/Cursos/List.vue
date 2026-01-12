<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import type { BreadcrumbItem } from '@/types';
const page: any = usePage();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Cursos', href: '/cursos' }];

const cursosProp = page.props.cursos ?? [];
const docentes = page.props.docentes ?? [];
const areas = page.props.areas ?? [];
const modalidadesCatalog = page.props.modalidadesCatalog ?? [];
const periodosCatalog = page.props.periodosCatalog ?? [];
const sedesCatalog = page.props.sedesCatalog ?? [];
const currentUserRole = page.props.currentUserRole ?? page.props.auth?.user?.role;
const currentDocenteId = page.props.currentDocenteId ?? null;

const vista = ref<'tabla' | 'tarjetas'>('tabla');
const mostrarFormulario = ref(false);

const periodoSeleccionado = ref(page.props.periodo ?? '2025-2');
const filtroModalidad = ref(page.props.filters?.modalidad ?? '');
const filtroSedeId = ref(page.props.filters?.sede_id ?? '');
const sort = ref(page.props.filters?.sort ?? 'nombre');
const dir = ref(page.props.filters?.dir ?? 'asc');

const nuevoCurso = ref({
  nombre: '',
  codigo: '',
  descripcion: '',
  creditos: '',
  nivel: 'pregrado',
  modalidad: '',
  modalidad_id: '',
  area_id: '',
  docente_id: '',
  sede_id: '',
  drive_url: '',
  periodo_academico: '',
  docentes_ids: [] as number[],
});

onMounted(() => {
  if (currentUserRole === 'docente' && currentDocenteId) {
    nuevoCurso.value.docente_id = String(currentDocenteId);
  }
});

watch(periodoSeleccionado, (value) => {
  if (!nuevoCurso.value.periodo_academico) {
    nuevoCurso.value.periodo_academico = value;
  }
});

watch(
  () => nuevoCurso.value.area_id,
  () => {
    nuevoCurso.value.modalidad_id = '';
    nuevoCurso.value.modalidad = '';
  },
);

watch(
  () => nuevoCurso.value.modalidad_id,
  (value) => {
    const allModalidades = (areas ?? []).flatMap((area: any) => area.modalidades ?? []);
    const selected = allModalidades.find((m: any) => String(m.id) === String(value));
    nuevoCurso.value.modalidad = selected?.nombre ?? '';
  },
);

const cursosList = computed(() => (cursosProp?.data ? cursosProp.data : cursosProp) || []);
const cursosLinks = computed(() => cursosProp?.links ?? []);
const cursosSinModalidad = computed(() => cursosList.value.filter((c: any) => !c.modalidad_id));
const modalidadOptions = computed(() => {
  const fromCatalog = (areas ?? [])
    .flatMap((area: any) => area.modalidades ?? [])
    .map((m: any) => String(m?.nombre ?? m).trim())
    .filter((m: string) => m.length > 0);
  const seen = new Set<string>();
  return fromCatalog.filter((value) => {
    const key = value.toLowerCase();
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
});
const areaSeleccionada = computed(() => {
  return (areas ?? []).find((area: any) => String(area.id) === String(nuevoCurso.value.area_id)) ?? null;
});
const modalidadesPorArea = computed(() => {
  const direct = areaSeleccionada.value?.modalidades ?? [];
  if (direct.length) return direct;
  return (modalidadesCatalog ?? []).filter(
    (m: any) => String(m.area_id ?? '') === String(nuevoCurso.value.area_id),
  );
});

const puedeTraerCursos = computed(() => {
  if (!(currentUserRole === 'admin' || currentUserRole === 'responsable')) {
    return false;
  }
  return cursosList.value.length === 0;
});

function verCurso(id: number) {
  router.get(`/cursos/${id}`);
}

function editarCurso(id: number) {
  router.get(`/cursos/${id}/edit`);
}

function abrirGoogleDrive(url: string) {
  if (url) {
    window.open(url, '_blank');
  }
}

function eliminarCurso(id: number) {
  if (confirm('Eliminar este curso?')) {
    router.delete(`/cursos/${id}`,
      {
        onSuccess: () => router.reload(),
      },
    );
  }
}

function aplicarFiltros(extra: Record<string, any> = {}) {
  const params: Record<string, any> = {
    periodo: periodoSeleccionado.value,
    sort: sort.value,
    dir: dir.value,
    ...extra,
  };

  if (filtroModalidad.value) {
    params.modalidad = filtroModalidad.value;
  }
  if (filtroSedeId.value) {
    params.sede_id = filtroSedeId.value;
  }

  router.get('/cursos', params, { preserveState: true, preserveScroll: true });
}

function ordenar(col: string) {
  if (sort.value === col) {
    dir.value = dir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sort.value = col;
    dir.value = 'asc';
  }
  aplicarFiltros({ page: 1 });
}

function cambiarPagina(url: string | null) {
  if (!url) return;
  router.get(url, {}, { preserveState: true, preserveScroll: true });
}

function agregarCurso() {
  const payload: any = {
    ...nuevoCurso.value,
    periodo: periodoSeleccionado.value,
  };

  router.post('/cursos', payload, {
    onSuccess: () => {
      mostrarFormulario.value = false;
      Swal.fire({
        icon: 'success',
        title: 'Curso creado',
        text: 'El curso ha sido creado exitosamente.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#6d28d9',
      }).then(() => {
        router.reload();
      });
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error al crear curso',
        text: 'Revisa los campos e intentalo nuevamente.',
        confirmButtonColor: '#6d28d9',
      });
    },
  });
}

function traerCursosDesdePeriodoAnterior() {
  router.post(
    '/cursos/traer',
    { periodo: periodoSeleccionado.value },
    {
      preserveScroll: true,
      onSuccess: () => router.reload(),
    },
  );
}

function firstRegistro(curso: any) {
  if (curso && Array.isArray(curso.registro_notas) && curso.registro_notas.length) {
    return curso.registro_notas[0];
  }
  return null;
}

function sedeLabel(curso: any) {
  if (curso?.sede?.nombre) return curso.sede.nombre;
  const registro = firstRegistro(curso);
  if (!registro) return 'N/A';
  if (registro.campus) return registro.campus;
  return 'N/A';
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
<div class="p-8 min-h-screen bg-background text-foreground">
      <div class="flex flex-wrap items-center gap-3 mb-6">
        <h1 class="text-2xl font-bold">Asignaturas</h1>

        <select
          v-model="periodoSeleccionado"
          class="border border-border bg-background text-foreground p-2 rounded"
        >
          <option v-if="!periodosCatalog.length" value="2025-2">2025-2</option>
          <option v-if="!periodosCatalog.length" value="2026-0">2026-0</option>
          <option v-if="!periodosCatalog.length" value="2026-1">2026-1</option>
          <option v-for="p in periodosCatalog" :key="p.id" :value="p.codigo">
            {{ p.codigo }}
          </option>
        </select>

        <select
          v-model="filtroModalidad"
          class="border border-border bg-background text-foreground p-2 rounded text-sm max-w-[180px]"
        >
          <option value="">Modalidad (todas)</option>
          <option v-if="!modalidadOptions.length" value="" disabled>Sin modalidades</option>
          <option v-for="mod in modalidadOptions" :key="mod" :value="mod">
            {{ mod }}
          </option>
        </select>

        <select
          v-model="filtroSedeId"
          class="border border-border bg-background text-foreground p-2 rounded text-sm max-w-[180px]"
        >
          <option value="">Sede (todas)</option>
          <option v-for="s in sedesCatalog" :key="s.id" :value="s.id">
            {{ s.nombre }}
          </option>
        </select>

        <button
          @click="aplicarFiltros({ page: 1 })"
          class="px-3 py-2 rounded border bg-emerald-600 text-white hover:bg-emerald-700 transition"
          title="Aplicar filtros"
        >
          Aplicar filtros
        </button>

        <div class="ml-auto inline-flex items-center rounded border border-slate-400 overflow-hidden">
          <button
            @click="vista = 'tabla'"
            :class="[
              'px-3 py-1 border-r border-slate-400 text-slate-700 hover:bg-slate-100',
              vista === 'tabla' ? 'bg-slate-700 text-white' : '',
            ]"
          >
            Tabla
          </button>
          <button
            @click="vista = 'tarjetas'"
            :class="[
              'px-3 py-1 text-slate-700 hover:bg-slate-100',
              vista === 'tarjetas' ? 'bg-slate-700 text-white' : '',
            ]"
          >
            Tarjetas
          </button>
        </div>
        <button
          v-if="currentUserRole === 'admin'"
          @click="mostrarFormulario = true"
          class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded ml-3"
        >
          Agregar curso
        </button>
      </div>

      <!-- Modal nuevo curso -->
      <div v-if="mostrarFormulario" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <form
          @submit.prevent="agregarCurso"
          class="w-[90vw] max-w-3xl grid grid-cols-1 md:grid-cols-2 gap-4 p-6 rounded-xl border bg-card shadow-xl"
        >
          <div class="md:col-span-2">
            <h2 class="text-lg font-semibold text-foreground">Nuevo curso</h2>
          </div>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Nombre del curso</span>
            <input
              v-model="nuevoCurso.nombre"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
              required
            />
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">NRC</span>
            <input
              v-model="nuevoCurso.codigo"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
              required
            />
          </label>
          <label class="md:col-span-2 flex flex-col gap-1">
            <span class="text-sm text-gray-600">Descripcion</span>
            <input
              v-model="nuevoCurso.descripcion"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Creditos</span>
            <input
              v-model="nuevoCurso.creditos"
              type="number"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
              required
            />
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Nivel</span>
            <select
              v-model="nuevoCurso.nivel"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
              required
            >
              <option value="pregrado">Pregrado</option>
              <option value="postgrado">Postgrado</option>
            </select>
          </label>

          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Area</span>
            <select
              v-model="nuevoCurso.area_id"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="">Seleccionar</option>
              <option v-for="area in areas" :key="area.id" :value="area.id">
                {{ area.nombre }}
              </option>
            </select>
          </label>

          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Modalidad</span>
            <select
              v-model="nuevoCurso.modalidad_id"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
              :disabled="!nuevoCurso.area_id"
            >
              <option value="">Seleccionar</option>
              <option v-for="m in modalidadesPorArea" :key="m.id" :value="m.id">
                {{ m.nombre }}
              </option>
            </select>
          </label>

          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Docente del curso</span>
            <span class="text-[11px] text-muted-foreground">El responsable se asigna en Responsabilidades.</span>
            <select
              v-model="nuevoCurso.docente_id"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
              required
            >
              <option value="">Seleccionar</option>
              <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
                {{ docente.nombre }} {{ docente.apellido }}
              </option>
            </select>
          </label>

          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Sede</span>
            <select
              v-model="nuevoCurso.sede_id"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="">Seleccionar</option>
              <option v-for="s in sedesCatalog" :key="s.id" :value="s.id">
                {{ s.nombre }}
              </option>
            </select>
          </label>

          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Docentes que dictan</span>
            <select
              v-model="nuevoCurso.docentes_ids"
              multiple
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
                {{ docente.nombre }} {{ docente.apellido }}
              </option>
            </select>
          </label>

          <label class="md:col-span-2 flex flex-col gap-1">
            <span class="text-sm text-gray-600">URL de Google Drive</span>
            <input
              v-model="nuevoCurso.drive_url"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </label>
          <label class="md:col-span-2 flex flex-col gap-1">
            <span class="text-sm text-gray-600">Periodo academico</span>
            <select
              v-model="nuevoCurso.periodo_academico"
              class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="">Seleccionar</option>
              <option v-if="!periodosCatalog.length" :value="periodoSeleccionado">
                {{ periodoSeleccionado }}
              </option>
              <option v-for="p in periodosCatalog" :key="p.id" :value="p.codigo">
                {{ p.codigo }}
              </option>
            </select>
          </label>

          <div class="md:col-span-2 flex justify-end gap-2">
            <button type="button" @click="mostrarFormulario = false" class="px-4 py-2 rounded border">
              Cancelar
            </button>
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
              Guardar
            </button>
          </div>
        </form>
      </div>

      <div v-if="vista === 'tabla'">
        <div class="rounded-lg border overflow-x-auto mb-8">
        <div
          v-if="puedeTraerCursos"
          class="w-full flex justify-center py-3 bg-muted/40 border-b border-border/60"
        >
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-border bg-background/60 text-xs font-medium text-foreground/80 hover:bg-background hover:text-foreground transition"
            @click="traerCursosDesdePeriodoAnterior"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="none"
              stroke="currentColor"
              class="w-4 h-4 opacity-70"
            >
              <path d="M10 4v12M4 10h12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>Traer cursos desde el periodo anterior</span>
          </button>
        </div>
        <table class="min-w-full text-sm">
          <thead class="bg-muted text-foreground/80">
            <tr>
              <th class="text-left p-3">
                <button @click="ordenar('codigo')" class="underline">NRC</button>
              </th>
              <th class="text-left p-3">
                <button @click="ordenar('nombre')" class="underline">Nombre</button>
              </th>
              <th class="text-left p-3">Area</th>
              <th class="text-left p-3">Modalidad</th>
              <th class="text-left p-3">Sede</th>
              <th class="text-left p-3">Docente</th>
              <th class="text-left p-3">Responsable</th>
              <th class="text-left p-3">Avance</th>
              <th class="text-right p-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="curso in cursosList" :key="curso.id" class="border-t">
              <td class="p-3">
                {{ curso.codigo }}
              </td>
              <td class="p-3 font-medium">
                {{ curso.nombre }}
              </td>
              <td class="p-3">
                {{ curso.modalidad_rel?.area?.nombre ?? '-' }}
              </td>
              <td class="p-3">
                {{ curso.modalidad_rel?.nombre ?? '-' }}
              </td>
              <td class="p-3">
                {{ sedeLabel(curso) }}
              </td>
              <td class="p-3">
                {{ curso.docente?.nombre ?? '-' }}
              </td>
              <td class="p-3">
                {{ curso.responsable?.name ?? '-' }}
              </td>
              <td class="p-3">
                <div class="w-40">
                  <div class="text-xs mb-1">{{ curso.avance ?? 0 }}%</div>
                  <div class="h-2 bg-muted rounded">
                    <div
                      class="h-2 bg-green-600 rounded"
                      :style="{ width: `${curso.avance ?? 0}%` }"
                    />
                  </div>
                </div>
              </td>
              <td class="p-3 text-right whitespace-nowrap">
                <button class="p-1" title="Abrir" @click="verCurso(curso.id)">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="w-5 h-5 text-primary"
                  >
                    <path
                      d="M12 5C5 5 2 12 2 12s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"
                    />
                  </svg>
                </button>
                <button class="p-1" title="Drive" @click="abrirGoogleDrive(curso.drive_url)">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="w-5 h-5"
                  >
                    <path
                      d="M7.71 3h8.58l4.3 7.45-4.29 7.55H7.7L3.41 10.45 7.7 3Zm9.67 8.45 2.5 4.4H9.55l-2.5-4.4h10.33ZM8.12 4.8 5.62 9.2h5.03L13.15 4.8H8.12Zm7.76 0h-2.98l-2.5 4.4h2.98l2.5-4.4Z"
                    />
                  </svg>
                </button>
                  <button
                    v-if="currentUserRole === 'admin'"
                    class="p-1"
                    title="Editar"
                    @click="editarCurso(curso.id)"
                  >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="w-5 h-5 text-yellow-600"
                  >
                    <path
                      d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm3.92 2.33H5v-1.92l8.06-8.06 1.92 1.92-8.06 8.06ZM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z"
                    />
                  </svg>
                </button>
                  <button
                    v-if="currentUserRole === 'admin'"
                    class="p-1"
                    title="Eliminar"
                    @click="eliminarCurso(curso.id)"
                  >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="w-5 h-5 text-red-600"
                  >
                    <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z" />
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex gap-1 items-center mt-2">
        <template v-for="l in cursosLinks" :key="l.label">
          <button
            v-if="l.url"
            @click="cambiarPagina(l.url)"
            :class="['px-2 py-1 rounded border', l.active ? 'bg-primary text-primary-foreground' : '']"
            v-html="l.label"
          />
          <span v-else class="px-2 py-1 text-muted-foreground" v-html="l.label" />
        </template>
      </div>

      </div>
      <div v-else>
        <!-- Vista tarjetas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <div
          v-for="curso in cursosList"
          :key="curso.id"
          class="rounded-xl shadow bg-card border border-border overflow-hidden flex flex-col"
        >
          <div class="h-16 bg-gradient-to-r from-green-600 to-blue-600 px-4 flex items-center text-white font-semibold">
            {{ curso.nombre }}
          </div>
          <div class="p-4 flex-1">
            <div class="text-sm text-muted-foreground mb-1">
              {{ curso.codigo }} - {{ curso.modalidad_rel?.nombre ?? '-' }}
            </div>
            <div class="text-sm text-muted-foreground mb-1">
              Area: {{ curso.modalidad_rel?.area?.nombre ?? 'N/A' }}
            </div>
            <div class="text-sm mb-3">
              Docente: {{ curso.docente?.nombre ?? 'N/A' }}
            </div>
            <div class="text-xs mb-1">Avance: {{ curso.avance ?? 0 }}%</div>
            <div class="h-2 bg-muted rounded">
              <div class="h-2 bg-green-600 rounded" :style="{ width: `${curso.avance ?? 0}%` }" />
            </div>
          </div>
          <div class="p-3 border-t flex gap-3 justify-end">
            <button class="p-1" title="Abrir" @click="verCurso(curso.id)">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="w-5 h-5 text-primary"
              >
                <path
                  d="M12 5C5 5 2 12 2 12s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"
                />
              </svg>
            </button>
            <button class="p-1" title="Drive" @click="abrirGoogleDrive(curso.drive_url)">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="w-5 h-5"
              >
                <path
                  d="M7.71 3h8.58l4.3 7.45-4.29 7.55H7.7L3.41 10.45 7.7 3Zm9.67 8.45 2.5 4.4H9.55l-2.5-4.4h10.33ZM8.12 4.8 5.62 9.2h5.03L13.15 4.8H8.12Zm7.76 0h-2.98l-2.5 4.4h2.98l2.5-4.4Z"
                />
              </svg>
            </button>
            <button
              v-if="currentUserRole === 'admin'"
              class="p-1"
              title="Editar"
              @click="editarCurso(curso.id)"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="w-5 h-5 text-yellow-600"
              >
                <path
                  d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm3.92 2.33H5v-1.92l8.06-8.06 1.92 1.92-8.06 8.06ZM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z"
                />
              </svg>
            </button>
            <button
              v-if="currentUserRole === 'admin'"
              class="p-1"
              title="Eliminar"
              @click="eliminarCurso(curso.id)"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="w-5 h-5 text-red-600"
              >
                <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
      </div>
      <div v-if="cursosSinModalidad.length" class="mt-6">
        <h2 class="text-lg font-semibold mb-2">Cursos pendientes de Area/modalidad</h2>
        <div class="rounded-lg border divide-y">
          <div v-for="curso in cursosSinModalidad" :key="curso.id" class="p-3 flex items-center justify-between">
            <div class="truncate">
              <div class="font-medium truncate">{{ curso.nombre }}</div>
              <div class="text-xs text-muted-foreground">
                Docente: {{ curso.docente?.nombre ?? 'N/A' }} - NRC: {{ curso.codigo }}
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button class="underline" @click="verCurso(curso.id)">Abrir</button>
              <button
                v-if="currentUserRole === 'admin'"
                class="underline text-yellow-600"
                @click="editarCurso(curso.id)"
              >
                Completar datos
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
