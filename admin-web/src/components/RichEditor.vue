<script setup lang="ts">
import '@wangeditor/editor/dist/css/style.css'
import { computed, onBeforeUnmount, shallowRef, watch } from 'vue'
import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import type { IDomEditor, IEditorConfig, IToolbarConfig } from '@wangeditor/editor'
import type { UploadFileRow } from '../api/file'
import FileSelector from './FileSelector.vue'

const props = withDefaults(defineProps<{
  modelValue: string
  disabled?: boolean
  height?: number
  placeholder?: string
  scene?: string
}>(), {
  disabled: false,
  height: 320,
  placeholder: '请输入内容',
  scene: 'richtext',
})
const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const editorRef = shallowRef<IDomEditor>()
const editorValue = shallowRef(props.modelValue || '')
const selectorVisible = shallowRef(false)
let insertImage: ((url: string, alt?: string, href?: string) => void) | null = null
const mode = 'default'
const toolbarConfig: Partial<IToolbarConfig> = {
  excludeKeys: [
    'fullScreen',
    'group-video',
    'insertVideo',
    'uploadVideo',
  ],
}
const editorConfig: Partial<IEditorConfig> = {
  placeholder: props.placeholder,
  scroll: true,
  MENU_CONF: {
    uploadImage: {
      customBrowseAndUpload(insertFn: (url: string, alt?: string, href?: string) => void) {
        insertImage = insertFn
        selectorVisible.value = true
      },
    },
  },
}
const editorStyle = computed(() => ({
  height: `${props.height}px`,
  overflowY: 'hidden',
}))

watch(() => props.modelValue, (nextValue) => {
  const value = nextValue || ''
  if (value !== editorValue.value) {
    editorValue.value = value
  }
})

watch(editorValue, (nextValue) => {
  emit('update:modelValue', nextValue || '')
})

watch(() => props.disabled, (disabled) => {
  const editor = editorRef.value
  if (!editor) {
    return
  }

  disabled ? editor.disable() : editor.enable()
})

function handleCreated(editor: IDomEditor) {
  editorRef.value = editor

  if (props.disabled) {
    editor.disable()
  }
}

function handleImageSelected(files: UploadFileRow[]) {
  if (!insertImage) {
    return
  }

  files.forEach((file) => {
    insertImage?.(file.url, file.original_name || file.url, file.url)
  })
  insertImage = null
}

onBeforeUnmount(() => {
  editorRef.value?.destroy()
})
</script>

<template>
  <div class="rich-editor" :class="{ disabled }">
    <Toolbar
      class="rich-editor-toolbar"
      :editor="editorRef"
      :default-config="toolbarConfig"
      :mode="mode"
    />
    <Editor
      v-model="editorValue"
      class="rich-editor-body"
      :default-config="editorConfig"
      :mode="mode"
      :style="editorStyle"
      @on-created="handleCreated"
    />
    <FileSelector
      v-model="selectorVisible"
      accept-type="image"
      multiple
      :scene="scene"
      @select="handleImageSelected"
    />
  </div>
</template>

<style scoped>
.rich-editor {
  overflow: hidden;
  border: 1px solid var(--el-border-color);
  border-radius: 4px;
  background: var(--el-bg-color);
}

.rich-editor-toolbar {
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.rich-editor-body {
  background: var(--el-bg-color);
}

.rich-editor.disabled {
  background: var(--el-disabled-bg-color);
}
</style>
