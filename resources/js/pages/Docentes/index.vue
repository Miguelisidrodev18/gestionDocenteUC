
<script setup lang="ts">            
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import {Docente, type BreadcrumbItem, type SharedData } from '@/types';    
import { Table,TableBody, TableCaption , TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
// Iconos
import { Pencil, Trash, CirclePlus } from 'lucide-vue-next';
import { computed } from 'vue';

interface DocentPageProps extends SharedData{
    docents: Docente[];    
}

const props = usePage<DocentPageProps>();
const docents= computed(()=> props.props.docents);
//breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{title:"Docentes", href:'/docents'}]
//método para eliminar

</script>

<template>  
    <head title="Docents" />
    <AppLayout> 
        <div class="flex h-full flex-1 flex-col  gap-4 rounded-xl p-4">
            <div class="flex">
                <button as-child size="sa" class="bg-indigo-500 text-white hover:bg-indigo-700" >
                    <Link href="/docents/create"><CirclePlus/> Create</link>
                    </Link>
                </button>
            </div>
        </div>
        
        <div class="relative min-h-screen flex-1 overflow-hidden rounded-xl border bg-background p-4 shadow-sm">
            <Table class="w-f ull h-full overflow-hidden rounded-xl border bg-background p-4 shadow-sm">
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[100px]">ID</TableHead>
                        <TableHead class="text-center">Nombre</TableHead>
                        <TableHead class="text-center">Apellido</TableHead>
                        <TableHead class="text-center">DNI</TableHead>
                        <TableHead class="text-center">Email</TableHead>
                        <TableHead class="text-center">Telefono</TableHead>
                        <TableHead class="text-center">Especialidad</TableHead>
                        <TableHead class="text-center">cv_sunedu</TableHead>
                        <TableHead class="text-center">cv_personal</TableHead>
                        <TableHead class="text-center">Linkedln</TableHead>
                        <TableHead class="text-center">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                        <TableRow v-for="(docent, index) in docents" :key="index" class="hover:bg-gray-100 cursor-pointer">
                            <TableCell class="font-medium">{{ docent.id }}</TableCell>
                            <TableCell>{{ docent.id }}</TableCell>
                            <TableCell>{{ docent.nombre }}</TableCell>
                            <TableCell>{{ docent.apellido }}</TableCell>
                            <TableCell>{{ docent.dni }}</TableCell>
                            <TableCell>{{ docent.email ?? 'N/A'}}</TableCell>
                            <TableCell>{{ docent.telefono }}</TableCell>
                            <TableCell>{{ docent.especialidad }}</TableCell>
                            <TableCell>{{ docent.cv_sunedu }}</TableCell>
                            <TableCell>{{ docent.cv_personal }}</TableCell>
                            <TableCell>{{ docent.linkedin }}</TableCell>
                            <TableCell class="flex gap-2">
                                <Link :href="'/docents/' + docent.id + '/show'"><Pencil class="w-6 h-6 text-blue-500"/></Link>
                                <Link :href="'/docents/' + docent.id + '/edit'"><Pencil class="w-6 h-6 text-blue-500"/></Link>
                                <Link :href="'/docents/' + docent.id + '/delete'"><Trash class="w-6 h-6 text-red-500"/></Link>
                            </TableCell>

                            
                        </TableRow>
                    
                </TableBody> 
            </Table>
        </div>
    </AppLayout>
</template>