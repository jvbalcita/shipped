<script setup lang="ts">
import { Bold, Italic, Link as LinkIcon, List, ListOrdered, Quote } from '@lucide/vue';
import { Link } from '@tiptap/extension-link';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        // Curated set only: bold, italic, lists, blockquote, link, history.
        // Headings, strikethrough, code, code blocks, and rules are disabled.
        StarterKit.configure({
            heading: false,
            strike: false,
            code: false,
            codeBlock: false,
            horizontalRule: false,
        }),
        Link.configure({
            openOnClick: false,
            autolink: true,
            HTMLAttributes: { rel: 'noopener noreferrer nofollow' },
        }),
    ],
    onUpdate: ({ editor }) => {
        // Emit an empty string when the editor holds no text so `required`
        // validation (which expects a non-empty value) still works.
        emit('update:modelValue', editor.isEmpty ? '' : editor.getHTML());
    },
    editorProps: {
        attributes: {
            class: 'rich-text max-w-none px-3 py-2 min-h-32 focus:outline-none',
            'aria-label': 'Description',
        },
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value ?? '', false);
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function setLink(): void {
    if (!editor.value) {
        return;
    }

    const previous = editor.value.getAttributes('link').href as
        | string
        | undefined;
    const url = window.prompt('Link URL', previous ?? 'https://');

    if (url === null) {
        return;
    }

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();

        return;
    }

    editor.value
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run();
}
</script>

<template>
    <div
        v-if="editor"
        class="rich-text-wrapper border border-foreground bg-background"
    >
        <div
            class="flex flex-wrap items-center gap-px border-b border-foreground bg-foreground"
        >
            <button
                type="button"
                class="technical-label bg-background px-2.5 py-1.5 hover:bg-secondary"
                :class="{ 'text-primary': editor.isActive('bold') }"
                aria-label="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="size-4" />
            </button>
            <button
                type="button"
                class="technical-label bg-background px-2.5 py-1.5 hover:bg-secondary"
                :class="{ 'text-primary': editor.isActive('italic') }"
                aria-label="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-4" />
            </button>
            <button
                type="button"
                class="technical-label bg-background px-2.5 py-1.5 hover:bg-secondary"
                :class="{ 'text-primary': editor.isActive('bulletList') }"
                aria-label="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" />
            </button>
            <button
                type="button"
                class="technical-label bg-background px-2.5 py-1.5 hover:bg-secondary"
                :class="{ 'text-primary': editor.isActive('orderedList') }"
                aria-label="Ordered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" />
            </button>
            <button
                type="button"
                class="technical-label bg-background px-2.5 py-1.5 hover:bg-secondary"
                :class="{ 'text-primary': editor.isActive('blockquote') }"
                aria-label="Blockquote"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="size-4" />
            </button>
            <button
                type="button"
                class="technical-label bg-background px-2.5 py-1.5 hover:bg-secondary"
                :class="{ 'text-primary': editor.isActive('link') }"
                aria-label="Link"
                @click="setLink"
            >
                <LinkIcon class="size-4" />
            </button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
