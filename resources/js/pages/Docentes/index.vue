<script setup lang="ts">            
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { Docente, type BreadcrumbItem, type SharedData } from '@/types';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import PdfFileCard from '@/components/PdfFileCard.vue';
// Iconos
import { CirclePlus, Pencil, Trash } from 'lucide-vue-next';
import { computed } from 'vue';

interface DocentPageProps extends SharedData{
    docents: Docente[];    
}

const props = usePage<DocentPageProps>();
const docents = computed(() => props.props.docents);
const auth = computed(() => props.props.auth);
//breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{title:"Docentes", href:'/docents'}]
//mÃ©todo para eliminar

const deleteDocente = (id: number) => {
    if (confirm('Â¿EstÃ¡s seguro de que deseas eliminar este docente?')) {
        router.delete(`/docents/${id}`, {
            onSuccess: () => {
                // AquÃ­ puedes manejar la respuesta despuÃ©s de eliminar el docente
                console.log('Docente eliminado con Ã©xito');
                router.visit('/docents', {replace: true}); // Redirigir a la lista de docentes despuÃ©s de eliminar
            },
            onError: () => {
                // AquÃ­ puedes manejar el error si la eliminaciÃ³n falla
                console.error('Error al eliminar el docente');
            },
        });
    }
};
</script>

<template>  
  <Head title="Docentes" />
  <AppLayout :breadcrumbs="breadcrumbs"> 
    <!-- Mostrar mensaje de Ã©xito -->
    <div v-if="$page.props.flash && $page.props.flash.success" class="bg-green-100 text-green-800 p-2 rounded mb-4">
      {{ $page.props.flash.success }}
    </div>

    <div class="flex h-full flex-1 flex-col gap-9 rounded-xl p-4">
      <div class="flex">
        <Button v-if="auth.user.role !== 'docente'" asChild class="bg-indigo-500 text-white hover:bg-indigo-700 px-4 py-2">
          <Link href="/docents/create">
            <CirclePlus /> Crear Docente
          </Link>
        </Button>
      </div>
    </div> 

    <div class="relative min-h-screen flex-1 overflow-hidden rounded-xl border bg-background p-4 shadow-sm">
      <Table>
        <TableCaption>Lista de Docentes</TableCaption>
        <TableHeader>
          <TableRow>
            <TableHead class="w-[100px]">ID</TableHead>
            <TableHead class="text-center">Nombre</TableHead>
            <TableHead class="text-center">Apellido</TableHead>
            <TableHead class="text-center">DNI</TableHead>
            <TableHead class="text-center">Email</TableHead>
            <TableHead class="text-center">TelÃ©fono</TableHead>
            <TableHead class="text-center">Especialidad</TableHead>
            <TableHead class="text-center">CV Sunedu</TableHead>
            <TableHead class="text-center">CV Personal</TableHead>
            <TableHead class="text-center">LinkedIn</TableHead>
            <TableHead class="text-center">Estado</TableHead>
            <TableHead class="text-center">CIP</TableHead>
            <TableHead class="text-center">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="(docent, index) in docents" :key="index" class="hover:bg-muted/60 dark:hover:bg-muted/40 cursor-pointer">
            <TableCell class="font-medium">{{ index + 1 }}</TableCell>
            <TableCell>{{ docent.nombre }}</TableCell>
            <TableCell>{{ docent.apellido }}</TableCell>
            <TableCell>{{ docent.dni }}</TableCell>
            <TableCell>{{ docent.email ?? 'N/A' }}</TableCell>
            <TableCell>{{ docent.telefono }}</TableCell>
            <TableCell>{{ docent.especialidad }}</TableCell>
            <TableCell class="text-center">
              <template v-if="docent.cv_personal">
                <PdfFileCard
                  :url="`/storage/${docent.cv_personal}`"
                  :name="docent.cv_personal.split('/').pop() ?? 'cv_personal.pdf'"
                />
              </template>
              <template v-else>
                No disponible
              </template>
            </TableCell>
            <TableCell class="text-center">
              <template v-if="docent.cv_sunedu">
                <PdfFileCard
                  :url="`/storage/${docent.cv_sunedu}`"
                  :name="docent.cv_sunedu.split('/').pop() ?? 'cv_sunedu.pdf'"
                />
              </template>
              <template v-else>
                No disponible
              </template>
            </TableCell>
            <TableCell>{{ docent.linkedin ?? 'N/A' }}</TableCell>
            <TableCell>{{ docent.estado ? (docent.estado === 'activo' ? 'Activo' : 'Inactivo') : 'N/A' }}</TableCell>
            <TableCell>{{ docent.cip }}</TableCell>
            <TableCell class="flex justify-center gap-2">
                <!-- BotÃ³n para Editar -->
                <Button v-if="auth.user.role !== 'docente' || docent.user_id === auth.user.id" asChild class="bg-blue-500 text-white hover:bg-blue-700">
                  <Link :href="`/docents/${docent.id}/edit`">
                    <Pencil />
                  </Link>
                </Button>
                <!-- BotÃ³n para Eliminar -->
                <Button v-if="auth.user.role !== 'docente'" class="bg-red-500 text-white hover:bg-red-700" @click="deleteDocente(docent.id)">
                  <Trash />
                </Button>
                
            </TableCell>
          </TableRow>
        </TableBody> 
      </Table>
    </div>
  </AppLayout>
</template>
<!-- No extra code needed here. You can safely remove $SELECTION_PLACEHOLDER$ or leave it empty. -->


