<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/shadcn/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import Color from '@tiptap/extension-color';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import { TableKit } from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Baseline,
    Bold,
    Code,
    Columns,
    Eraser,
    Highlighter,
    Image as ImageIcon,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    Plus,
    Quote,
    Redo,
    Rows,
    Strikethrough,
    Table as TableIcon,
    Trash2,
    Underline as UnderlineIcon,
    Undo,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'change'): void;
}>();

const editor = useEditor({
    content: props.modelValue,
    editable: !props.readonly,
    extensions: [
        StarterKit,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        TextStyle,
        Color,
        Underline,
        Link.configure({ openOnClick: false }),
        Image,
        Highlight.configure({ multicolor: true }),
        TableKit,
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
        emit('change');
    },
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (editor.value && editor.value.getHTML() !== newValue) {
            editor.value.commands.setContent(newValue);
        }
    },
);

watch(
    () => props.readonly,
    (readonly) => {
        editor.value?.setEditable(!readonly);
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const headingLevel = computed(() => {
    if (!editor.value) return 'paragraph';
    for (let i = 1; i <= 5; i++) {
        if (editor.value.isActive('heading', { level: i })) return String(i);
    }
    return 'paragraph';
});

function setHeading(value: any) {
    if (!editor.value || !value) return;
    const strValue = String(value);
    if (strValue === 'paragraph') {
        editor.value.chain().focus().setParagraph().run();
    } else {
        editor.value
            .chain()
            .focus()
            .toggleHeading({ level: Number(strValue) as 1 | 2 | 3 | 4 | 5 })
            .run();
    }
}

function setLink() {
    const prevUrl = editor.value?.getAttributes('link').href ?? '';
    const url = window.prompt('URL eingeben', prevUrl);
    if (url === null) return;
    if (url === '') {
        editor.value?.chain().focus().unsetLink().run();
        return;
    }
    editor.value?.chain().focus().setLink({ href: url }).run();
}

function insertImage() {
    const url = window.prompt('Bild-URL eingeben');
    if (!url) return;
    editor.value?.chain().focus().setImage({ src: url }).run();
}

function applyColor(e: Event) {
    const color = (e.target as HTMLInputElement).value;
    editor.value?.chain().focus().setColor(color).run();
}

function applyHighlight(e: Event) {
    const color = (e.target as HTMLInputElement).value;
    editor.value?.chain().focus().toggleHighlight({ color }).run();
}
</script>

<template>
    <div class="rounded-md border" :class="{ 'opacity-60': readonly }">
        <!-- Toolbar -->
        <div v-if="!readonly" class="flex flex-wrap items-center gap-0.5 border-b bg-muted/30 p-1.5">
            <!-- Undo / Redo -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :disabled="!editor?.can().undo()"
                @click="editor?.chain().focus().undo().run()"
            >
                <Undo class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :disabled="!editor?.can().redo()"
                @click="editor?.chain().focus().redo().run()"
            >
                <Redo class="h-3.5 w-3.5" />
            </Button>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Bold / Italic / Underline / Strike -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('bold') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleBold().run()"
            >
                <Bold class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('italic') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('underline') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleUnderline().run()"
            >
                <UnderlineIcon class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('strike') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleStrike().run()"
            >
                <Strikethrough class="h-3.5 w-3.5" />
            </Button>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Text alignment -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive({ textAlign: 'left' }) ? 'bg-accent' : ''"
                @click="editor?.chain().focus().setTextAlign('left').run()"
            >
                <AlignLeft class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive({ textAlign: 'center' }) ? 'bg-accent' : ''"
                @click="editor?.chain().focus().setTextAlign('center').run()"
            >
                <AlignCenter class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive({ textAlign: 'right' }) ? 'bg-accent' : ''"
                @click="editor?.chain().focus().setTextAlign('right').run()"
            >
                <AlignRight class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive({ textAlign: 'justify' }) ? 'bg-accent' : ''"
                @click="editor?.chain().focus().setTextAlign('justify').run()"
            >
                <AlignJustify class="h-3.5 w-3.5" />
            </Button>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Lists -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('orderedList') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('bulletList') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleBulletList().run()"
            >
                <List class="h-3.5 w-3.5" />
            </Button>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Heading select -->
            <Select :model-value="headingLevel" @update:model-value="setHeading">
                <SelectTrigger class="h-7 w-32 text-xs">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="paragraph">Normal</SelectItem>
                    <SelectItem value="1">Überschrift 1</SelectItem>
                    <SelectItem value="2">Überschrift 2</SelectItem>
                    <SelectItem value="3">Überschrift 3</SelectItem>
                    <SelectItem value="4">Überschrift 4</SelectItem>
                    <SelectItem value="5">Überschrift 5</SelectItem>
                </SelectContent>
            </Select>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Font color (native color picker) -->
            <div class="relative" title="Textfarbe">
                <Button type="button" variant="ghost" size="icon" class="pointer-events-none h-7 w-7">
                    <Baseline class="h-3.5 w-3.5" />
                </Button>
                <input type="color" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" @input="applyColor" />
            </div>

            <!-- Background / Highlight color -->
            <div class="relative" title="Hintergrundfarbe">
                <Button type="button" variant="ghost" size="icon" class="pointer-events-none h-7 w-7">
                    <Highlighter class="h-3.5 w-3.5" />
                </Button>
                <input type="color" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" @input="applyHighlight" />
            </div>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Link / Image -->
            <Button type="button" variant="ghost" size="icon" class="h-7 w-7" :class="editor?.isActive('link') ? 'bg-accent' : ''" @click="setLink">
                <LinkIcon class="h-3.5 w-3.5" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="h-7 w-7" @click="insertImage">
                <ImageIcon class="h-3.5 w-3.5" />
            </Button>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Clear / CodeBlock / Blockquote -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                title="Formatierung entfernen"
                @click="editor?.chain().focus().clearNodes().unsetAllMarks().run()"
            >
                <Eraser class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('codeBlock') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleCodeBlock().run()"
            >
                <Code class="h-3.5 w-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="editor?.isActive('blockquote') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="h-3.5 w-3.5" />
            </Button>

            <div class="mx-0.5 h-5 w-px bg-border" />

            <!-- Table dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button type="button" variant="ghost" size="icon" class="h-7 w-7" :class="editor?.isActive('table') ? 'bg-accent' : ''">
                        <TableIcon class="h-3.5 w-3.5" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start">
                    <DropdownMenuItem @click="editor?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">
                        <Plus class="mr-2 h-4 w-4" />Tabelle einfügen
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="editor?.chain().focus().deleteTable().run()">
                        <Trash2 class="mr-2 h-4 w-4" />Tabelle löschen
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="editor?.chain().focus().addRowBefore().run()">
                        <Rows class="mr-2 h-4 w-4" />Zeile davor einfügen
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="editor?.chain().focus().addRowAfter().run()">
                        <Rows class="mr-2 h-4 w-4" />Zeile danach einfügen
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="editor?.chain().focus().deleteRow().run()">
                        <Minus class="mr-2 h-4 w-4" />Zeile löschen
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="editor?.chain().focus().addColumnBefore().run()">
                        <Columns class="mr-2 h-4 w-4" />Spalte links einfügen
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="editor?.chain().focus().addColumnAfter().run()">
                        <Columns class="mr-2 h-4 w-4" />Spalte rechts einfügen
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="editor?.chain().focus().deleteColumn().run()">
                        <Minus class="mr-2 h-4 w-4" />Spalte löschen
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Slot for extra items (e.g., save button) -->
            <div class="ml-auto flex items-center">
                <slot name="toolbar-end" />
            </div>
        </div>

        <!-- Editor content -->
        <div class="rich-text-editor-content">
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>

<style>
.rich-text-editor-content .ProseMirror {
    outline: none;
    min-height: 200px;
    padding: 1rem;
}

.rich-text-editor-content .ProseMirror p {
    margin: 0.25rem 0;
}

.rich-text-editor-content .ProseMirror h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0.5rem 0;
}
.rich-text-editor-content .ProseMirror h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
}
.rich-text-editor-content .ProseMirror h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0.5rem 0;
}
.rich-text-editor-content .ProseMirror h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0.5rem 0;
}
.rich-text-editor-content .ProseMirror h5 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0.5rem 0;
}

.rich-text-editor-content .ProseMirror ul {
    list-style-type: disc;
    padding-left: 1.5rem;
}
.rich-text-editor-content .ProseMirror ol {
    list-style-type: decimal;
    padding-left: 1.5rem;
}

.rich-text-editor-content .ProseMirror blockquote {
    border-left: 3px solid #e2e8f0;
    padding-left: 1rem;
    color: #64748b;
    margin: 0.5rem 0;
}

.rich-text-editor-content .ProseMirror code {
    background: #f1f5f9;
    padding: 0.1rem 0.3rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.875em;
}

.rich-text-editor-content .ProseMirror pre {
    background: #1e293b;
    color: #e2e8f0;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 0.5rem 0;
}

.rich-text-editor-content .ProseMirror pre code {
    background: none;
    padding: 0;
    color: inherit;
}

.rich-text-editor-content .ProseMirror table {
    border-collapse: collapse;
    width: 100%;
    margin: 0.5rem 0;
}

.rich-text-editor-content .ProseMirror table td,
.rich-text-editor-content .ProseMirror table th {
    border: 1px solid #e2e8f0;
    padding: 0.4rem 0.6rem;
    min-width: 3rem;
    vertical-align: top;
    position: relative;
}

.rich-text-editor-content .ProseMirror table th {
    background: #f8fafc;
    font-weight: 600;
}

.rich-text-editor-content .ProseMirror .selectedCell::after {
    background: rgba(59, 130, 246, 0.1);
    content: '';
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    pointer-events: none;
    position: absolute;
}

.rich-text-editor-content .ProseMirror img {
    max-width: 100%;
    height: auto;
}

.rich-text-editor-content .ProseMirror a {
    color: #3b82f6;
    text-decoration: underline;
}
</style>
