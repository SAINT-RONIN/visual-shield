<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'
import { getAuthToken } from '@/utils/api.js'

const props = defineProps({
  videoId: { type: Number, required: true },
  charts: { type: Object, required: true },
  duration: { type: Number, required: true },
})

const wrapperRef = ref(null)
const canvasRef = ref(null)
const videoRef = ref(null)
const canvasHeight = ref(0)
const canvasWidth = ref(0)

const videoSrc = computed(() => {
  const base = import.meta.env.VITE_API_BASE_URL
  const token = getAuthToken()
  return `${base}/videos/${props.videoId}/stream?token=${encodeURIComponent(token)}`
})

const graphs = ref([
  { key: 'flashFrequency', label: 'Flash Frequency', color: '#6366f1', visible: true },
  { key: 'motionIntensity', label: 'Motion Intensity', color: '#f59e0b', visible: true },
  { key: 'luminance', label: 'Luminance', color: '#22d3ee', visible: true },
])

function toggleGraph(index) {
  graphs.value[index].visible = !graphs.value[index].visible
  draw()
}

function getValues(key) {
  if (key === 'flashFrequency') {
    return props.charts.flashFrequency.map((d) => ({ time: d.time, value: d.frequency }))
  }
  if (key === 'motionIntensity') {
    return props.charts.motionIntensity.map((d) => ({ time: d.time, value: d.intensity }))
  }
  if (key === 'luminance') {
    return props.charts.luminance.map((d) => ({ time: d.time, value: d.luminance }))
  }
  return []
}

function draw() {
  const canvas = canvasRef.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  const w = canvasWidth.value
  const h = canvasHeight.value

  ctx.clearRect(0, 0, w, h)

  for (const graph of graphs.value) {
    if (!graph.visible) continue

    const points = getValues(graph.key)
    if (!points.length) continue

    const maxVal = Math.max(...points.map((p) => p.value), 1)
    const dur = props.duration || Math.max(...points.map((p) => p.time), 1)

    ctx.beginPath()
    ctx.strokeStyle = graph.color
    ctx.lineWidth = 2
    ctx.globalAlpha = 0.85

    for (let i = 0; i < points.length; i++) {
      const x = (points[i].time / dur) * w
      const y = h - (points[i].value / maxVal) * h * 0.85 - h * 0.05
      if (i === 0) ctx.moveTo(x, y)
      else ctx.lineTo(x, y)
    }
    ctx.stroke()

    // Semi-transparent fill
    ctx.lineTo((points[points.length - 1].time / dur) * w, h)
    ctx.lineTo((points[0].time / dur) * w, h)
    ctx.closePath()
    ctx.fillStyle = graph.color
    ctx.globalAlpha = 0.08
    ctx.fill()
    ctx.globalAlpha = 1
  }
}

let resizeObserver = null

function updateSize() {
  const wrapper = wrapperRef.value
  if (!wrapper) return

  const video = videoRef.value
  if (!video) return

  const rect = video.getBoundingClientRect()
  canvasWidth.value = rect.width
  canvasHeight.value = rect.height

  const canvas = canvasRef.value
  if (canvas) {
    const dpr = window.devicePixelRatio || 1
    canvas.width = rect.width * dpr
    canvas.height = rect.height * dpr
    canvas.style.width = rect.width + 'px'
    canvas.style.height = rect.height + 'px'
    const ctx = canvas.getContext('2d')
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
    draw()
  }
}

onMounted(() => {
  resizeObserver = new ResizeObserver(() => {
    updateSize()
  })
  if (wrapperRef.value) {
    resizeObserver.observe(wrapperRef.value)
  }
})

onBeforeUnmount(() => {
  if (resizeObserver) {
    resizeObserver.disconnect()
  }
})

watch(() => props.charts, () => draw(), { deep: true })

function onVideoLoaded() {
  updateSize()
}
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-6 border border-line">
    <h3 class="text-base md:text-lg font-semibold text-heading mb-4">Video Analysis Overlay</h3>

    <div ref="wrapperRef" class="video-wrapper">
      <video
        ref="videoRef"
        :src="videoSrc"
        controls
        preload="metadata"
        class="video-element"
        @loadedmetadata="onVideoLoaded"
        @resize="updateSize"
      >
        Your browser does not support the video tag.
      </video>
      <canvas
        ref="canvasRef"
        class="overlay-canvas"
      />
    </div>

    <div class="toggle-bar">
      <button
        v-for="(graph, index) in graphs"
        :key="graph.key"
        class="toggle-btn"
        :class="{ active: graph.visible }"
        :style="{
          '--graph-color': graph.color,
          borderColor: graph.visible ? graph.color : 'var(--color-line-strong)',
          backgroundColor: graph.visible ? graph.color + '22' : 'transparent',
          color: graph.visible ? graph.color : 'var(--color-muted)',
        }"
        @click="toggleGraph(index)"
      >
        <span
          class="toggle-dot"
          :style="{ backgroundColor: graph.visible ? graph.color : 'var(--color-muted)' }"
        />
        {{ graph.label }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.video-wrapper {
  position: relative;
  width: 100%;
  border-radius: 0.5rem;
  overflow: hidden;
  background: #000;
}

.video-element {
  display: block;
  width: 100%;
  height: auto;
}

.overlay-canvas {
  position: absolute;
  top: 0;
  left: 0;
  pointer-events: none;
}

.toggle-bar {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
  flex-wrap: wrap;
}

.toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border: 1px solid var(--color-line-strong);
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.toggle-btn:hover {
  border-color: var(--graph-color, var(--color-line-strong));
}

.toggle-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  flex-shrink: 0;
}
</style>
