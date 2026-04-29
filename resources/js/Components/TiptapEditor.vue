<script setup>
import { computed } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import CharacterCount from '@tiptap/extension-character-count';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBold,
    faItalic,
    faUnderline,
    faStrikethrough,
    faListUl,
    faListOl,
    faQuoteRight,
    faCode,
    faLink,
    faHeading,
} from '@fortawesome/free-solid-svg-icons';

const model = defineModel({ type: String });

const props = defineProps({
    placeholder: {
        type: String,
        default: 'Escribe el contenido del post...',
    },
});

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
        }),
        Underline,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { class: 'text-indigo-500 underline' },
        }),
        Image,
        CharacterCount,
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    onUpdate: ({ editor }) => {
        model.value = editor.getHTML();
    },
    editorProps: {
        attributes: {
            class: 'prose dark:prose-invert max-w-none min-h-[300px] px-4 py-3 focus:outline-none',
        },
    },
});

const characterCount = computed(() => {
    return editor.value?.storage.characterCount.characters() ?? 0;
});

const setLink = () => {
    const previousUrl = editor.value?.getAttributes('link').href;
    const url = window.prompt('URL del enlace:', previousUrl);

    if (url === null) return;

    if (url === '') {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

const tools = [
    { icon: faHeading, action: () => editor.value?.chain().focus().toggleHeading({ level: 1 }).run(), title: 'Título 1', isActive: () => editor.value?.isActive('heading', { level: 1 }) },
    { icon: faHeading, action: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(), title: 'Título 2', isActive: () => editor.value?.isActive('heading', { level: 2 }), label: 'H2' },
    { icon: faHeading, action: () => editor.value?.chain().focus().toggleHeading({ level: 3 }).run(), title: 'Título 3', isActive: () => editor.value?.isActive('heading', { level: 3 }), label: 'H3' },
    { icon: faBold, action: () => editor.value?.chain().focus().toggleBold().run(), title: 'Negrita', isActive: () => editor.value?.isActive('bold') },
    { icon: faItalic, action: () => editor.value?.chain().focus().toggleItalic().run(), title: 'Cursiva', isActive: () => editor.value?.isActive('italic') },
    { icon: faUnderline, action: () => editor.value?.chain().focus().toggleUnderline().run(), title: 'Subrayado', isActive: () => editor.value?.isActive('underline') },
    { icon: faStrikethrough, action: () => editor.value?.chain().focus().toggleStrike().run(), title: 'Tachado', isActive: () => editor.value?.isActive('strike') },
    { icon: faListUl, action: () => editor.value?.chain().focus().toggleBulletList().run(), title: 'Lista sin orden', isActive: () => editor.value?.isActive('bulletList') },
    { icon: faListOl, action: () => editor.value?.chain().focus().toggleOrderedList().run(), title: 'Lista ordenada', isActive: () => editor.value?.isActive('orderedList') },
    { icon: faQuoteRight, action: () => editor.value?.chain().focus().toggleBlockquote().run(), title: 'Cita', isActive: () => editor.value?.isActive('blockquote') },
    { icon: faCode, action: () => editor.value?.chain().focus().toggleCodeBlock().run(), title: 'Bloque de código', isActive: () => editor.value?.isActive('codeBlock') },
    { icon: faHeading, action: () => editor.value?.chain().focus().setHorizontalRule().run(), title: 'Línea horizontal', isActive: () => false, label: 'HR' },
    { divider: true },
    { icon: faLink, action: setLink, title: 'Enlace', isActive: () => editor.value?.isActive('link') },
];

const activeClass = (tool) => {
    if (tool.divider) return '';
    if (tool.isActive()) {
        return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-800 dark:text-indigo-300';
    }
    return 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700';
};
</script>

<template>
    <div class="tiptap-editor border border-gray-300 dark:border-gray-700 rounded-md overflow-hidden">
        <div
            class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600"
        >
            <template v-for="(tool, idx) in tools" :key="idx">
                <div v-if="tool.divider" class="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1" />
                <button
                    v-else
                    type="button"
                    :title="tool.title"
                    class="p-1.5 rounded text-sm transition-colors"
                    :class="activeClass(tool)"
                    @click="tool.action"
                >
                    {{ tool.label || '' }}
                    <FontAwesomeIcon :icon="tool.icon" class="w-3.5 h-3.5" />
                </button>
            </template>
        </div>

        <EditorContent :editor="editor" class="bg-white dark:bg-gray-900" />

        <div class="flex items-center justify-end px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
            {{ characterCount }} caracteres
        </div>
    </div>
</template>

<style>
.tiptap-editor .ProseMirror {
    min-height: 300px;
    outline: none;
}

.tiptap-editor .ProseMirror p.is-editor-empty:first-child::before {
    color: #9ca3af;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.tiptap-editor .ProseMirror h1 {
    font-size: 1.875rem;
    font-weight: 700;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.tiptap-editor .ProseMirror h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 0.75rem;
    margin-bottom: 0.5rem;
}

.tiptap-editor .ProseMirror h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.tiptap-editor .ProseMirror ul {
    list-style: disc;
    padding-left: 1.5rem;
}

.tiptap-editor .ProseMirror ol {
    list-style: decimal;
    padding-left: 1.5rem;
}

.tiptap-editor .ProseMirror blockquote {
    border-left: 3px solid #6366f1;
    padding-left: 1rem;
    font-style: italic;
    color: #6b7280;
    margin: 0.5rem 0;
}

:is(.dark .tiptap-editor .ProseMirror blockquote) {
    border-color: #818cf8;
    color: #9ca3af;
}

.tiptap-editor .ProseMirror pre {
    background: #1f2937;
    color: #f9fafb;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    font-family: monospace;
    margin: 0.5rem 0;
    overflow-x: auto;
}

.tiptap-editor .ProseMirror code {
    background: #f3f4f6;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

:is(.dark .tiptap-editor .ProseMirror code) {
    background: #374151;
}
</style>
