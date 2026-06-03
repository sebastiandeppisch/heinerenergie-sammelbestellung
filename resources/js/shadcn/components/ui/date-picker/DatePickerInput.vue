<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="
                    cn(
                        'justify-start text-left font-normal',
                        !modelValue && 'text-muted-foreground',
                    )
                "
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ modelValue ? formatDisplay(modelValue) : placeholder }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar
                :model-value="calendarValue"
                :min-value="calendarMin"
                :max-value="calendarMax"
                initial-focus
                @update:model-value="onSelect"
            />
        </PopoverContent>
    </Popover>
</template>

<script setup lang="ts">
import { CalendarDate, parseDate, today, getLocalTimeZone } from '@internationalized/date';
import type { DateValue } from 'reka-ui';
import { CalendarIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { cn } from '@/shadcn/utils';
import { Button } from '@/shadcn/components/ui/button';
import { Calendar } from '@/shadcn/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/shadcn/components/ui/popover';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        min?: string;
        max?: string;
        placeholder?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Datum wählen',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function toCalendar(iso: string | null | undefined): CalendarDate | undefined {
    if (!iso) return undefined;
    try {
        const d = parseDate(iso);
        return new CalendarDate(d.year, d.month, d.day);
    } catch {
        return undefined;
    }
}

const calendarValue = computed(() => toCalendar(props.modelValue));
const calendarMin = computed(() => toCalendar(props.min));
const calendarMax = computed(() => (props.max ? toCalendar(props.max) : today(getLocalTimeZone())));

function onSelect(date: DateValue | undefined) {
    if (date) {
        emit('update:modelValue', date.toString());
    }
}

function formatDisplay(iso: string): string {
    try {
        return new Intl.DateTimeFormat('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(
            new Date(iso + 'T00:00:00'),
        );
    } catch {
        return iso;
    }
}
</script>
