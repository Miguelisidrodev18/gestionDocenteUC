<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import type { BreadcrumbItem } from '@/types';
const page: any = usePage();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Catalogos', href: '/catalogos' }];

const sedes = computed(() => page.props.sedes ?? []);
const areas = computed(() => page.props.areas ?? []);
const modalidades = computed(() => page.props.modalidades ?? []);
const periodos = computed(() => page.props.periodos ?? []);
const tipos = computed(() => page.props.tipos_evidencia ?? []);
const bloques = computed(() => page.props.bloques ?? []);
const requisitos = computed(() => page.props.requisitos ?? []);
const especialidades = computed(() => page.props.especialidades ?? []);
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const errors = computed(() => page.props.errors ?? {});
const errorList = computed(() => Object.values(errors.value).flat());

const activeTab = ref<'sedes' | 'modalidades' | 'areas' | 'periodos' | 'tipos' | 'bloques' | 'especialidades' | 'requisitos'>('sedes');

const pesoTotal = computed(() => requisitos.value.reduce((acc: number, r: any) => acc + Number(r.peso || 0), 0));
const pesoPorModalidad = computed(() => {
  const map: Record<string, { nombre: string; total: number }> = {};
  requisitos.value.forEach((r: any) => {
    const id = String(r.modalidad_id ?? r.modalidad?.id ?? '');
    if (!id) return;
    if (!map[id]) {
      map[id] = { nombre: r.modalidad?.nombre ?? 'Modalidad', total: 0 };
    }
    map[id].total += Number(r.peso || 0);
  });
  return Object.values(map);
});

function submit(url: string, method: 'post' | 'put' | 'delete', data: any = {}) {
  const options: any = { preserveScroll: true };
  if (method === 'post') router.post(url, data, options);
  else if (method === 'put') router.put(url, data, options);
  else router.delete(url, options);
}

function renameEspecialidad(especialidad: any) {
  const nombre = window.prompt('Nuevo nombre de especialidad', especialidad?.nombre ?? '');
  if (!nombre) return;
  submit(`/catalogos/especialidades/${especialidad.id}`, 'put', { nombre });
}

function deleteEspecialidad(especialidad: any) {
  if (!window.confirm('Eliminar esta especialidad?')) return;
  submit(`/catalogos/especialidades/${especialidad.id}`, 'delete');
}

function deleteCatalogItem(url: string, label: string) {
  if (!window.confirm(`Eliminar ${label}?`)) return;
  submit(url, 'delete');
}

function cleanCatalogos() {
  if (!window.confirm('Esta acción eliminará todos los catálogos. ¿Deseas continuar?')) return;
  submit('/catalogos/limpiar', 'post');
}

function cleanModalidades() {
  if (!window.confirm('Esta acción eliminará todas las modalidades. ¿Deseas continuar?')) return;
  submit('/catalogos/limpiar-modalidades', 'post');
}

function formatDate(value?: string | null) {
  if (!value) return '-';
  const base = value.includes('T') ? value.slice(0, 10) : value;
  const parts = base.split('-');
  if (parts.length !== 3) return value;
  const [year, month, day] = parts;
  if (!year || !month || !day) return value;
  return `${day}/${month}/${year}`;
}

function toDateValue(value?: string | null) {
  if (!value) return null;
  return value.includes('T') ? value.slice(0, 10) : value;
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
<div class="p-8 min-h-screen bg-background text-foreground">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Catálogos</h1>
        <button
          v-if="isAdmin"
          type="button"
          class="px-3 py-1 rounded border text-xs text-muted-foreground hover:text-foreground"
          @click="cleanCatalogos"
        >
          Limpiar catalogos
        </button>
      </div>

      <div v-if="!isAdmin" class="mb-4 text-xs text-muted-foreground">
        Solo admin puede crear, editar o eliminar catalogos.
      </div>
      <div v-if="errorList.length" class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
        <div class="font-semibold mb-1">No se pudo guardar:</div>
        <div v-for="(err, idx) in errorList" :key="idx">{{ err }}</div>
      </div>

      <div class="mb-4 flex flex-wrap gap-2">
        <button
          v-for="tab in [
            ['sedes', 'Sedes'],
            ['areas', 'Areas'],
            ['bloques', 'Bloques'],
            ['modalidades', 'Modalidades'],
            ['periodos', 'Periodos'],
            ['tipos', 'Tipos de evidencia'],
            ['especialidades', 'Especialidades'],
            ['requisitos', 'Requisitos'],
          ]"
          :key="tab[0]"
          @click="activeTab = tab[0] as any"
          :class="[
            'px-3 py-1 rounded-full text-xs font-medium shadow-sm ring-1 ring-inset transition-colors',
            activeTab === tab[0]
              ? 'bg-primary text-primary-foreground ring-primary/60'
              : 'bg-muted text-foreground ring-border/60 hover:bg-muted/80',
          ]"
        >
          {{ tab[1] }}
        </button>
      </div>

      <!-- Sedes -->
      <div
        v-if="activeTab === 'sedes'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Sedes</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
                        <tr>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Activo</th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sedes" :key="s.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ s.nombre }}</td>
              <td class="py-1 pr-2">{{ s.activo ? 'Si' : 'No' }}</td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteCatalogItem(`/catalogos/sedes/${s.id}`, 'esta sede')"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/sedes', 'post', {
              nombre: ($event.target as HTMLFormElement).nombre.value,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-2 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar sede
            </button>
          </div>
        </form>
      </div>

      <!-- Modalidades -->
      <div
        v-else-if="activeTab === 'modalidades'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-semibold">Modalidades</h2>
          <button
            v-if="isAdmin"
            type="button"
            class="px-2 py-1 rounded border text-[11px] text-muted-foreground hover:text-foreground"
            @click="cleanModalidades"
          >
            Limpiar modalidades
          </button>
        </div>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Semanas</th>
              <th class="text-left py-1 pr-2">Area</th>
              <th class="text-left py-1 pr-2">Bloque</th>
              <th v-if="isAdmin" class="text-left py-1 pr-2">Activo</th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in modalidades" :key="m.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ m.nombre }}</td>
              <td class="py-1 pr-2">
                {{ m.estructura_duracion === 'BLOQUES' ? (m.semanas_por_bloque ?? m.duracion_semanas ?? 16) : (m.duracion_semanas ?? 16) }}
              </td>
              <td class="py-1 pr-2">{{ m.area?.nombre ?? '-' }}</td>
              <td class="py-1 pr-2">{{ m.bloque?.nombre ?? '-' }}</td>
              <td v-if="isAdmin" class="py-1 pr-2">{{ m.activo ? 'Si' : 'No' }}</td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteCatalogItem(`/catalogos/modalidades/${m.id}`, 'esta modalidad')"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs"
          @submit.prevent="
            (() => {
              const form = $event.target as HTMLFormElement;
              const base = form.nombre_base.value;
              const bloqueSelect = form.bloque_id as HTMLSelectElement;
              const bloqueNombreRaw = bloqueSelect?.selectedOptions?.[0]?.dataset?.nombre ?? '';
              const bloqueNombre = bloqueNombreRaw.replace(/^bloque\s+/i, '').trim();
              const nombre = bloqueNombre ? `${base} bloque ${bloqueNombre}` : base;
              submit('/catalogos/modalidades', 'post', {
                nombre,
                duracion_semanas: form.semanas.value,
                bloque_id: bloqueSelect.value || null,
                area_id: form.area_id.value || null,
                activo: form.activo?.checked,
              });
            })()
          "
        >
          <select name="nombre_base" class="border border-border rounded px-2 py-1">
            <option value="Presencial">Presencial</option>
            <option value="Semipresencial">Semipresencial</option>
          </select>
          <input
            name="semanas"
            type="number"
            min="1"
            max="32"
            placeholder="Semanas"
            class="border border-border rounded px-2 py-1"
          />
          <select name="area_id" class="border border-border rounded px-2 py-1">
            <option value="">Area</option>
            <option v-for="a in areas" :key="a.id" :value="a.id">
              {{ a.nombre }}
            </option>
          </select>
          <select name="bloque_id" class="border border-border rounded px-2 py-1">
            <option value="">Bloque (opcional)</option>
            <option v-for="b in bloques" :key="b.id" :value="b.id" :data-nombre="b.nombre">
              {{ b.nombre }}
            </option>
          </select>
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-4 text-[11px] text-muted-foreground">
            Semanas: total si es Presencial. Si seleccionas bloque, se usa como semanas por bloque.
          </div>
          <div v-if="!areas.length" class="sm:col-span-4 text-[11px] text-amber-600">
            Debes crear al menos un area antes de registrar modalidades.
          </div>
          <div class="sm:col-span-4 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
              :disabled="!areas.length"
            >
              Guardar modalidad
            </button>
          </div>
        </form>
      </div>

<!-- Bloques -->
      <div
        v-else-if="activeTab === 'bloques'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Bloques</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
                        <tr>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Semanas</th>
              <th class="text-left py-1 pr-2">Activo</th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in bloques" :key="b.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ b.nombre }}</td>
              <td class="py-1 pr-2">{{ b.semanas }}</td>
              <td class="py-1 pr-2">{{ b.activo ? 'Si' : 'No' }}</td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteCatalogItem(`/catalogos/bloques/${b.id}`, 'este bloque')"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/bloques', 'post', {
              nombre: ($event.target as HTMLFormElement).nombre.value,
              semanas: ($event.target as HTMLFormElement).semanas.value,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <input
            name="semanas"
            type="number"
            min="1"
            max="20"
            placeholder="Semanas"
            class="border border-border rounded px-2 py-1"
          />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-3 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar bloque
            </button>
          </div>
        </form>
      </div>

      <!-- Áreas -->
      <div
        v-else-if="activeTab === 'areas'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Áreas</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Activo</th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in areas" :key="a.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ a.nombre }}</td>
              <td class="py-1 pr-2">{{ a.activo ? 'Si' : 'No' }}</td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteCatalogItem(`/catalogos/areas/${a.id}`, 'esta area')"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/areas', 'post', {
              nombre: ($event.target as HTMLFormElement).nombre.value,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-2 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar A?rea
            </button>
          </div>
        </form>
      </div>

      <!-- Períodos -->
      <div
        v-else-if="activeTab === 'periodos'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Períodos académicos</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Periodo</th>
              <th class="text-left py-1 pr-2">Inicio</th>
              <th class="text-left py-1 pr-2">Fin</th>
              <th class="text-left py-1 pr-2">Estado</th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in periodos" :key="p.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ p.codigo }}</td>
              <td class="py-1 pr-2">{{ formatDate(p.inicio) }}</td>
              <td class="py-1 pr-2">{{ formatDate(p.fin) }}</td>
              <td class="py-1 pr-2">
                <form
                  v-if="isAdmin"
                  class="flex items-center gap-2"
                  @submit.prevent="
                    submit(`/catalogos/periodos/${p.id}`, 'put', {
                      codigo: ($event.target as HTMLFormElement).codigo.value,
                      inicio: toDateValue(p.inicio),
                      fin: toDateValue(p.fin),
                      estado: ($event.target as HTMLFormElement).estado.value,
                    })
                  "
                >
                  <input name="codigo" class="border border-border rounded px-2 py-1 text-[11px]" :value="p.codigo" />
                  <select name="estado" class="border border-border rounded px-2 py-1 text-[11px]" :value="p.estado">
                    <option value="ABIERTO">ABIERTO</option>
                    <option value="CERRADO">CERRADO</option>
                  </select>
                  <button
                    type="submit"
                    class="px-2 py-1 rounded bg-primary text-primary-foreground text-[11px] hover:opacity-90"
                  >
                    Actualizar
                  </button>
                </form>
                <span v-else>{{ p.estado }}</span>
              </td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteCatalogItem(`/catalogos/periodos/${p.id}`, 'este periodo')"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/periodos', 'post', {
              codigo: ($event.target as HTMLFormElement).codigo.value,
              inicio: ($event.target as HTMLFormElement).inicio.value,
              fin: ($event.target as HTMLFormElement).fin.value,
              estado: ($event.target as HTMLFormElement).estado.value,
            })
          "
        >
          <input name="codigo" placeholder="Periodo (ej: 2025-II)" class="border border-border rounded px-2 py-1" />
          <input name="inicio" type="date" class="border border-border rounded px-2 py-1" />
          <input name="fin" type="date" class="border border-border rounded px-2 py-1" />
          <select name="estado" class="border border-border rounded px-2 py-1">
            <option value="ABIERTO">ABIERTO</option>
            <option value="CERRADO">CERRADO</option>
          </select>
          <div class="sm:col-span-4 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar período
            </button>
          </div>
        </form>
      </div>

      <!-- Tipos de evidencia -->
      <div
        v-else-if="activeTab === 'tipos'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Tipos de evidencia</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
                        <tr>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Cuenta en avance</th>
              <th class="text-left py-1 pr-2">Activo</th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in tipos" :key="t.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ t.nombre }}</td>
              <td class="py-1 pr-2">{{ t.cuenta_en_avance ? 'Si' : 'No' }}</td>
              <td class="py-1 pr-2">{{ t.activo ? 'Si' : 'No' }}</td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteCatalogItem(`/catalogos/tipos-evidencia/${t.id}`, 'este tipo')"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-[1.6fr_1fr_0.6fr_0.4fr] gap-2 text-xs items-center"
          @submit.prevent="
            submit('/catalogos/tipos-evidencia', 'post', {
              nombre: ($event.target as HTMLFormElement).nombre.value,
              cuenta_en_avance: ($event.target as HTMLFormElement).cuenta_en_avance.checked,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="cuenta_en_avance" type="checkbox" checked class="border-border" />
            Cuenta en avance
          </label>
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="flex justify-end">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar tipo
            </button>
          </div>
        </form>
      </div>

      <!-- Especialidades -->
      <div
        v-else-if="activeTab === 'especialidades'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Especialidades</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in especialidades" :key="e.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ e.nombre }}</td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-primary underline mr-2"
                  @click="renameEspecialidad(e)"
                  title="Editar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-primary"><path d="M5 18.75V21h2.25l9.67-9.67-2.25-2.25L5 18.75Zm13.71-11.04a.996.996 0 0 0 0-1.41l-2.01-2.01a.996.996 0 0 0-1.41 0l-1.5 1.5 3.75 3.75 1.17-1.83Z"/></svg>
                </button>
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteEspecialidad(e)"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs"
          @submit.prevent="submit('/catalogos/especialidades', 'post', {
            nombre: ($event.target as HTMLFormElement).nombre.value,
          })"
        >
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <div class="sm:col-span-2 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar especialidad
            </button>
          </div>
        </form>
      </div>

      <!-- Requisitos por modalidad -->
      <div
        v-else
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Requisitos por modalidad</h2>
        <div class="mb-3 text-xs text-muted-foreground">
          Total peso configurado: <span class="font-medium text-foreground">{{ pesoTotal }}</span>
          <span v-if="pesoTotal !== 100" class="ml-2 text-amber-600">
            ⚠️ El total no es 100.
          </span>
        </div>
        <div v-if="pesoPorModalidad.length" class="mb-3 text-xs">
          <div v-for="m in pesoPorModalidad" :key="m.nombre" class="text-muted-foreground">
            {{ m.nombre }}: <span class="font-medium text-foreground">{{ m.total }}</span>
            <span v-if="m.total !== 100" class="ml-1 text-amber-600">⚠️</span>
          </div>
        </div>
        <div class="mb-4 rounded border border-dashed border-border/60 bg-muted/30 p-3 text-xs">
          <div class="font-medium mb-1">Ejemplo de cálculo</div>
          <div>Si un requisito tiene Mínimo 4 y Peso 30, y entregas 2:</div>
          <div class="mt-1">cumplimiento = min(2/4, 1) = 0.5</div>
          <div>aporte = 0.5 x 30 = 15</div>
          <div class="mt-1 text-muted-foreground">
            Si aplica a POR_BLOQUE, se promedia el cumplimiento de Bloque A y Bloque B.
          </div>
        </div>
        <div v-if="!requisitos.length" class="mb-4 text-xs text-muted-foreground">
          Configura requisitos para que el avance sea automático.
        </div>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Modalidad</th>
              <th class="text-left py-1 pr-2">Tipo evidencia</th>
              <th class="text-left py-1 pr-2">
                Mínimo
                <span class="ml-1 cursor-help text-muted-foreground" title="Cantidad mínima de entregables requeridos.">ℹ️</span>
              </th>
              <th class="text-left py-1 pr-2">
                Peso
                <span class="ml-1 cursor-help text-muted-foreground" title="Contribución (%) de este requisito al avance total.">ℹ️</span>
              </th>
              <th class="text-left py-1 pr-2">
                Aplica a
                <span class="ml-1 cursor-help text-muted-foreground" title="CICLO = todo el curso. POR_BLOQUE = promedio entre bloques.">ℹ️</span>
              </th>
              <th v-if="isAdmin" class="text-right py-1 pr-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in requisitos" :key="r.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ r.modalidad?.nombre ?? '—' }}</td>
              <td class="py-1 pr-2">{{ r.tipo?.nombre ?? '—' }}</td>
              <td class="py-1 pr-2">{{ r.minimo }}</td>
              <td class="py-1 pr-2">{{ r.peso }}</td>
              <td class="py-1 pr-2">{{ r.aplica_a ?? 'CICLO' }}</td>
              <td v-if="isAdmin" class="py-1 pr-2 text-right">
                <button
                  type="button"
                  class="text-[11px] text-red-600 underline"
                  @click="deleteCatalogItem(`/catalogos/requisitos/${r.id}`, 'este requisito')"
                  title="Eliminar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <form
          v-if="isAdmin"
          class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/requisitos', 'post', {
              modalidad_id: ($event.target as HTMLFormElement).modalidad_id.value,
              tipo_evidencia_id: ($event.target as HTMLFormElement).tipo_evidencia_id.value,
              minimo: ($event.target as HTMLFormElement).minimo.value,
              peso: ($event.target as HTMLFormElement).peso.value,
              aplica_a: ($event.target as HTMLFormElement).aplica_a.value,
            })
          "
        >
          <select name="modalidad_id" class="border border-border rounded px-2 py-1">
            <option value="">Modalidad…</option>
            <option v-for="m in modalidades" :key="m.id" :value="m.id">
              {{ m.nombre }}
            </option>
          </select>
          <select name="tipo_evidencia_id" class="border border-border rounded px-2 py-1">
            <option value="">Tipo evidencia…</option>
            <option v-for="t in tipos" :key="t.id" :value="t.id">
              {{ t.nombre }}
            </option>
          </select>
          <select name="aplica_a" class="border border-border rounded px-2 py-1">
            <option value="CICLO">CICLO</option>
            <option value="POR_BLOQUE">POR_BLOQUE</option>
          </select>
          <input
            name="minimo"
            type="number"
            min="0"
            class="border border-border rounded px-2 py-1"
            placeholder="Mínimo"
          />
          <input
            name="peso"
            type="number"
            min="0"
            max="100"
            class="border border-border rounded px-2 py-1"
            placeholder="Peso %"
          />
          <div class="sm:col-span-5 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar requisito
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

