<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page: any = usePage();

const sedes = computed(() => page.props.sedes ?? []);
const campus = computed(() => page.props.campus ?? []);
const areas = computed(() => page.props.areas ?? []);
const modalidades = computed(() => page.props.modalidades ?? []);
const periodos = computed(() => page.props.periodos ?? []);
const tipos = computed(() => page.props.tipos_evidencia ?? []);
const bloques = computed(() => page.props.bloques ?? []);
const requisitos = computed(() => page.props.requisitos ?? []);

const activeTab = ref<'sedes' | 'campus' | 'modalidades' | 'areas' | 'periodos' | 'tipos' | 'requisitos'>('sedes');

function submit(url: string, method: 'post' | 'put' | 'delete', data: any = {}) {
  const options: any = { preserveScroll: true };
  if (method === 'post') router.post(url, data, options);
  else if (method === 'put') router.put(url, data, options);
  else router.delete(url, options);
}
</script>

<template>
  <AppLayout>
    <div class="p-8 min-h-screen bg-background text-foreground">
      <h1 class="text-2xl font-bold mb-6">Catálogos</h1>

      <div class="mb-4 flex flex-wrap gap-2">
        <button
          v-for="tab in [
            ['sedes', 'Sedes'],
            ['campus', 'Campus'],
            ['modalidades', 'Modalidades'],
            ['areas', 'Áreas'],
            ['periodos', 'Períodos'],
            ['tipos', 'Tipos de evidencia'],
            ['requisitos', 'Requisitos'],
          ]"
          :key="tab[0]"
          @click="activeTab = tab[0] as any"
          :class="[
            'px-3 py-1 rounded-full text-xs',
            activeTab === tab[0] ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
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
              <th class="text-left py-1 pr-2">Código</th>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Activo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sedes" :key="s.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ s.codigo }}</td>
              <td class="py-1 pr-2">{{ s.nombre }}</td>
              <td class="py-1 pr-2">{{ s.activo ? 'Sí' : 'No' }}</td>
            </tr>
          </tbody>
        </table>
        <form
          class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/sedes', 'post', {
              codigo: ($event.target as HTMLFormElement).codigo.value,
              nombre: ($event.target as HTMLFormElement).nombre.value,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="codigo" placeholder="Código" class="border border-border rounded px-2 py-1" />
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-3 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar sede
            </button>
          </div>
        </form>
      </div>

      <!-- Campus -->
      <div
        v-else-if="activeTab === 'campus'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Campus</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Código</th>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Sede</th>
              <th class="text-left py-1 pr-2">Activo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in campus" :key="c.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ c.codigo }}</td>
              <td class="py-1 pr-2">{{ c.nombre }}</td>
              <td class="py-1 pr-2">{{ c.sede?.nombre ?? '—' }}</td>
              <td class="py-1 pr-2">{{ c.activo ? 'Sí' : 'No' }}</td>
            </tr>
          </tbody>
        </table>
        <form
          class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/campus', 'post', {
              codigo: ($event.target as HTMLFormElement).codigo.value,
              nombre: ($event.target as HTMLFormElement).nombre.value,
              sede_id: ($event.target as HTMLFormElement).sede_id.value,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <select name="sede_id" class="border border-border rounded px-2 py-1">
            <option value="">Sede…</option>
            <option v-for="s in sedes" :key="s.id" :value="s.id">{{ s.nombre }}</option>
          </select>
          <input name="codigo" placeholder="Código" class="border border-border rounded px-2 py-1" />
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-4 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar campus
            </button>
          </div>
        </form>
      </div>

      <!-- Modalidades -->
      <div
        v-else-if="activeTab === 'modalidades'"
        class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Modalidades</h2>
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Código</th>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Área</th>
              <th class="text-left py-1 pr-2">Semanas</th>
              <th class="text-left py-1 pr-2">Activo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in modalidades" :key="m.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ m.codigo }}</td>
              <td class="py-1 pr-2">{{ m.nombre }}</td>
              <td class="py-1 pr-2">{{ m.area?.nombre ?? '—' }}</td>
              <td class="py-1 pr-2">{{ m.duracion_semanas }}</td>
              <td class="py-1 pr-2">{{ m.activo ? 'Sí' : 'No' }}</td>
            </tr>
          </tbody>
        </table>
        <form
          class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/modalidades', 'post', {
              codigo: ($event.target as HTMLFormElement).codigo.value,
              nombre: ($event.target as HTMLFormElement).nombre.value,
              area_id: ($event.target as HTMLFormElement).area_id.value,
              duracion_semanas: ($event.target as HTMLFormElement).duracion_semanas.value,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="codigo" placeholder="Código" class="border border-border rounded px-2 py-1" />
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <select name="area_id" class="border border-border rounded px-2 py-1">
            <option value="">Área…</option>
            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
          <input
            name="duracion_semanas"
            type="number"
            min="1"
            max="32"
            placeholder="Semanas"
            class="border border-border rounded px-2 py-1"
          />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-5 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar modalidad
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
              <th class="text-left py-1 pr-2">Código</th>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Activo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in areas" :key="a.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ a.codigo }}</td>
              <td class="py-1 pr-2">{{ a.nombre }}</td>
              <td class="py-1 pr-2">{{ a.activo ? 'Sí' : 'No' }}</td>
            </tr>
          </tbody>
        </table>
        <form
          class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/areas', 'post', {
              codigo: ($event.target as HTMLFormElement).codigo.value,
              nombre: ($event.target as HTMLFormElement).nombre.value,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="codigo" placeholder="Código" class="border border-border rounded px-2 py-1" />
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-3 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar área
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
              <th class="text-left py-1 pr-2">Código</th>
              <th class="text-left py-1 pr-2">Inicio</th>
              <th class="text-left py-1 pr-2">Fin</th>
              <th class="text-left py-1 pr-2">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in periodos" :key="p.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ p.codigo }}</td>
              <td class="py-1 pr-2">{{ p.inicio }}</td>
              <td class="py-1 pr-2">{{ p.fin }}</td>
              <td class="py-1 pr-2">{{ p.estado }}</td>
            </tr>
          </tbody>
        </table>
        <form
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
          <input name="codigo" placeholder="Código" class="border border-border rounded px-2 py-1" />
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
              <th class="text-left py-1 pr-2">Código</th>
              <th class="text-left py-1 pr-2">Nombre</th>
              <th class="text-left py-1 pr-2">Cuenta en avance</th>
              <th class="text-left py-1 pr-2">Activo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in tipos" :key="t.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ t.codigo }}</td>
              <td class="py-1 pr-2">{{ t.nombre }}</td>
              <td class="py-1 pr-2">{{ t.cuenta_en_avance ? 'Sí' : 'No' }}</td>
              <td class="py-1 pr-2">{{ t.activo ? 'Sí' : 'No' }}</td>
            </tr>
          </tbody>
        </table>
        <form
          class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/tipos-evidencia', 'post', {
              codigo: ($event.target as HTMLFormElement).codigo.value,
              nombre: ($event.target as HTMLFormElement).nombre.value,
              cuenta_en_avance: ($event.target as HTMLFormElement).cuenta_en_avance.checked,
              activo: ($event.target as HTMLFormElement).activo.checked,
            })
          "
        >
          <input name="codigo" placeholder="Código" class="border border-border rounded px-2 py-1" />
          <input name="nombre" placeholder="Nombre" class="border border-border rounded px-2 py-1" />
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="cuenta_en_avance" type="checkbox" checked class="border-border" />
            Cuenta en avance
          </label>
          <label class="inline-flex items-center gap-1 text-[11px]">
            <input name="activo" type="checkbox" checked class="border-border" />
            Activo
          </label>
          <div class="sm:col-span-4 flex justify-end mt-2">
            <button
              type="submit"
              class="px-3 py-1 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
            >
              Guardar tipo
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
        <table class="min-w-full text-xs mb-4">
          <thead class="text-muted-foreground">
            <tr>
              <th class="text-left py-1 pr-2">Modalidad</th>
              <th class="text-left py-1 pr-2">Tipo evidencia</th>
              <th class="text-left py-1 pr-2">Bloque</th>
              <th class="text-left py-1 pr-2">Mínimo</th>
              <th class="text-left py-1 pr-2">Peso</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in requisitos" :key="r.id" class="border-t border-border/40">
              <td class="py-1 pr-2">{{ r.modalidad?.nombre ?? '—' }}</td>
              <td class="py-1 pr-2">{{ r.tipo?.nombre ?? '—' }}</td>
              <td class="py-1 pr-2">{{ r.bloque?.codigo ?? '—' }}</td>
              <td class="py-1 pr-2">{{ r.minimo }}</td>
              <td class="py-1 pr-2">{{ r.peso }}</td>
            </tr>
          </tbody>
        </table>
        <form
          class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs"
          @submit.prevent="
            submit('/catalogos/requisitos', 'post', {
              modalidad_id: ($event.target as HTMLFormElement).modalidad_id.value,
              tipo_evidencia_id: ($event.target as HTMLFormElement).tipo_evidencia_id.value,
              bloque_id: ($event.target as HTMLFormElement).bloque_id.value || null,
              minimo: ($event.target as HTMLFormElement).minimo.value,
              peso: ($event.target as HTMLFormElement).peso.value,
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
          <select name="bloque_id" class="border border-border rounded px-2 py-1">
            <option value="">Bloque (opcional)</option>
            <option v-for="b in bloques" :key="b.id" :value="b.id">
              {{ b.codigo }}
            </option>
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

