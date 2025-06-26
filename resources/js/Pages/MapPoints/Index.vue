<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/shadcn/components/ui/table';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/shadcn/components/ui/dialog';

import Card from '@/shadcn/components/ui/card/Card.vue'
import { Pencil, Plus, Trash } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { toast } from 'vue-sonner';


const props = defineProps<{
  mapPoints: Array<App.Data.MapPointData>;
}>();

const searchQuery = ref('');

// Filter map points based on search query
const filteredMapPoints = computed(() => {
  if (!searchQuery.value) return props.mapPoints;

  const query = searchQuery.value.toLowerCase();
  return props.mapPoints.filter(point =>
    point.title.toLowerCase().includes(query) ||
    point.description.toLowerCase().includes(query) ||
    point.userReadablePointableType.toLowerCase().includes(query)
  );
});

// State for delete confirmation dialog
const showDeleteDialog = ref(false);
const pointIdToDelete = ref<string | null>(null);

function confirmDelete(id: string) {
  pointIdToDelete.value = id;
  showDeleteDialog.value = true;
}

function deleteMapPoint() {
  if (pointIdToDelete.value) {
    router.delete(route('mappoints.destroy', pointIdToDelete.value), {
      onSuccess: () => {
        showDeleteDialog.value = false;
        pointIdToDelete.value = null;
        toast('Der Kartenpunkte wurde gelöscht');
      }
    });
  }
}
</script>

<template>
  <div class="container mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Karten Punkte</h1>
      <div class="flex gap-4">
        <Input
          v-model="searchQuery"
          placeholder="Suche..."
          class="max-w-sm bg-white"
        />
        <Link :href="route('mappoints.create')">
          <Button><Plus />Neuen Punkt hinzufügen</Button>
        </Link>
      </div>
    </div>

    <Card class="p-4">
    <Table>
      <TableCaption>Liste aller Kartenpunkte</TableCaption>
      <TableHeader>
        <TableRow>
          <TableHead>Titel</TableHead>
          <TableHead>Ursprung</TableHead>
          <TableHead>Beschreibung</TableHead>
          <TableHead>Ort</TableHead>
          <TableHead>Status</TableHead>
          <TableHead>Erstellt am</TableHead>
          <TableHead>Aktionen</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="point in filteredMapPoints" :key="point.id" class="even:bg-gray-50 odd:bg-white">
          <TableCell class="font-medium">{{ point.title }}</TableCell>
          <TableCell>{{ point.userReadablePointableType }}</TableCell>
          <TableCell class="max-w-md truncate">{{ point.description }}</TableCell>
          <TableCell>{{ point.coordinate.lat.toFixed(6) }}, {{ point.coordinate.lng.toFixed(6) }}</TableCell>
          <TableCell>
            <span
              :class="{
                'px-2 py-1 rounded text-xs font-medium': true,
                'bg-green-100 text-green-800': point.published,
                'bg-red-100 text-red-800': !point.published
              }"
            >
              {{ point.published ? 'Öffentlich' : 'Eingereicht' }}
            </span>
          </TableCell>
          <TableCell>
            {{ point.created_at }}
          </TableCell>
          <TableCell>
            <div class="flex space-x-2">
              <Link :href="route('mappoints.edit', point.id)">
                <Button variant="outline" size="sm"><Pencil /></Button>
              </Link>
              <Button
                variant="destructive"
                size="sm"
                @click="confirmDelete(point.id)"
              >
                <Trash />
              </Button>
            </div>
          </TableCell>
        </TableRow>
        <TableRow v-if="filteredMapPoints.length === 0">
          <TableCell colspan="6" class="text-center py-8 text-gray-500">
            Keine Punkte gefunden
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
</Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Punkt wirklich löschen?</DialogTitle>
          <DialogDescription>
            Der Punkt wird unwiederbringlich gelöscht
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="showDeleteDialog = false">Abbrechen</Button>
          <Button variant="destructive" @click="deleteMapPoint">Löschen</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
