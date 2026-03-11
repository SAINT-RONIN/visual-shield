<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, watch, computed } from 'vue'
import { formatTime } from '@/utils/formatters.js'

const props = defineProps({
  videoSrc: { type: String, required: true },
  charts: { type: Object, required: true },
  duration: { type: Number, required: true },
})

// ── Refs ──────────────────────────────────────────

const wrapperRef = ref(null)
const canvasRef = ref(null)
const videoRef = ref(null)
const progressRef = ref(null)
const volumeRef = ref(null)
const graphSliderRef = ref(null)
const gridSliderRef = ref(null)
const canvasHeight = ref(0)
const canvasWidth = ref(0)

// ── Video state ───────────────────────────────────

const playing = ref(false)
const currentTime = ref(0)
const videoDuration = ref(0)
const progress = ref(0)
const volume = ref(1)
const muted = ref(false)
const hoveringControls = ref(false)
const controlsVisible = ref(true)
const seekingProgress = ref(false)
const seekingVolume = ref(false)
const fullscreen = ref(false)
let hideTimeout = null
let rafId = null

// ── Overlay layer state ───────────────────────────

const graphOpacity = ref(0.8)
const gridOpacity = ref(0.3)
const graphsOn = ref(true)
const gridOn = ref(true)
let seekingGraphSlider = false
let seekingGridSlider = false

// Thresholds mirrored from AnalysisConfig.php
const FLASH_FREQUENCY_DANGER = 3   // flashes/sec (WCAG 2.3.1)
const MOTION_THRESHOLD = 30        // pixel difference for significant motion
const LUMINANCE_MAX = 255          // fixed 0–255 scale

const graphs = reactive([
  { key: 'flashFrequency', label: 'Flash', color: '#818cf8', visible: true,
    threshold: FLASH_FREQUENCY_DANGER, fixedScale: null },
  { key: 'motionIntensity', label: 'Motion', color: '#fbbf24', visible: true,
    threshold: MOTION_THRESHOLD, fixedScale: null },
  { key: 'luminance', label: 'Luminance', color: '#22d3ee', visible: true,
    threshold: null, fixedScale: LUMINANCE_MAX },
])

// ── Equalizer state ──────────────────────────────

const EQ_SEGMENT_COUNT = 72
const eqLevels = reactive([0, 0, 0])

const eqBars = computed(() => {
  return graphs.map((graph, barIdx) => {
    const litSegs = Math.round(eqLevels[barIdx] * EQ_SEGMENT_COUNT)

    const segments = []
    for (let segIdx = EQ_SEGMENT_COUNT - 1; segIdx >= 0; segIdx--) {
      const isLit = segIdx < litSegs
      segments.push({
        bg: isLit ? eqSegmentColor(segIdx, barIdx) : eqSegmentDimColor(segIdx, barIdx),
      })
    }

    return { key: graph.key, segments }
  })
})

// ── Graph data helpers ────────────────────────────

function getValues(key) {
  if (key === 'flashFrequency')
    return props.charts.flashFrequency.map((d) => ({ time: d.time, value: d.frequency }))
  if (key === 'motionIntensity')
    return props.charts.motionIntensity.map((d) => ({ time: d.time, value: d.intensity }))
  if (key === 'luminance')
    return props.charts.luminance.map((d) => ({ time: d.time, value: d.luminance }))
  return []
}

/**
 * Compute the Y-axis scale for a graph so that threshold-based metrics
 * are scaled relative to their danger threshold, not auto-normalized.
 *
 * - Flash/Motion: scale = max(dataMax, threshold * 2) so the threshold
 *   sits at or below the 50% mark and values below it stay visually low.
 * - Luminance: fixed 0–255 scale (informational, no danger threshold).
 */
function getScaleMax(graphIndex) {
  const graph = graphs[graphIndex]
  const points = getValues(graph.key)
  const dataMax = Math.max(...points.map((p) => p.value), 1)

  if (graph.fixedScale) return graph.fixedScale
  if (graph.threshold) return Math.max(dataMax, graph.threshold * 2)
  return dataMax
}

function toggleGraph(index) {
  graphs[index].visible = !graphs[index].visible
  draw()
}

function toggleGridOnOff() {
  gridOn.value = !gridOn.value
  if (!gridOn.value) gridOpacity.value = 0
  else if (gridOpacity.value === 0) gridOpacity.value = 0.3
  draw()
}

function toggleGraphsOnOff() {
  graphsOn.value = !graphsOn.value
  if (!graphsOn.value) graphOpacity.value = 0
  else if (graphOpacity.value === 0) graphOpacity.value = 0.8
  draw()
}

// ── Full-height pixel coordinate conversion ───────
// Graphs use full canvas: 4% top padding, baseline at 96% height

const PAD_TOP = 0.04
const PAD_BOT = 0.04

function toPixelPoints(points, w, h, maxVal, dur) {
  const usableH = h * (1 - PAD_TOP - PAD_BOT)
  const bottom = h * (1 - PAD_BOT)
  return points.map((p) => ({
    x: (p.time / dur) * w,
    y: bottom - (p.value / maxVal) * usableH,
  }))
}

// ── Smooth Bézier control points ──────────────────

function computeControlPoints(pts, tension = 0.2) {
  const segments = []
  for (let i = 0; i < pts.length - 1; i++) {
    const p0 = pts[i - 1] || pts[i]
    const p1 = pts[i]
    const p2 = pts[i + 1]
    const p3 = pts[i + 2] || p2
    segments.push({
      cp1x: p1.x + (p2.x - p0.x) * tension,
      cp1y: p1.y + (p2.y - p0.y) * tension,
      cp2x: p2.x - (p3.x - p1.x) * tension,
      cp2y: p2.y - (p3.y - p1.y) * tension,
      x: p2.x,
      y: p2.y,
    })
  }
  return segments
}

function buildCurvePath(pixelPoints) {
  const path = new Path2D()
  if (pixelPoints.length < 2) return path
  const segs = computeControlPoints(pixelPoints)
  path.moveTo(pixelPoints[0].x, pixelPoints[0].y)
  for (const s of segs) {
    path.bezierCurveTo(s.cp1x, s.cp1y, s.cp2x, s.cp2y, s.x, s.y)
  }
  return path
}

// ── Interpolate Y on the Bézier curve at a given X ─

function interpolateYAtX(pixelPoints, targetX) {
  if (pixelPoints.length === 0) return 0
  if (targetX <= pixelPoints[0].x) return pixelPoints[0].y
  if (targetX >= pixelPoints[pixelPoints.length - 1].x)
    return pixelPoints[pixelPoints.length - 1].y

  let idx = 0
  for (let i = 0; i < pixelPoints.length - 1; i++) {
    if (targetX >= pixelPoints[i].x && targetX <= pixelPoints[i + 1].x) {
      idx = i
      break
    }
  }

  const segs = computeControlPoints(pixelPoints)
  if (!segs[idx]) return pixelPoints[idx].y

  const p0 = pixelPoints[idx]
  const seg = segs[idx]

  let lo = 0, hi = 1
  for (let iter = 0; iter < 20; iter++) {
    const mid = (lo + hi) / 2
    const x = cubicBezier(mid, p0.x, seg.cp1x, seg.cp2x, seg.x)
    if (x < targetX) lo = mid
    else hi = mid
  }
  const t = (lo + hi) / 2
  return cubicBezier(t, p0.y, seg.cp1y, seg.cp2y, seg.y)
}

function cubicBezier(t, p0, p1, p2, p3) {
  const u = 1 - t
  return u * u * u * p0 + 3 * u * u * t * p1 + 3 * u * t * t * p2 + t * t * t * p3
}

// ── Draw a single graph with played/unplayed split ─

function drawGraph(ctx, pixelPoints, color, w, h, playheadX) {
  if (pixelPoints.length < 2) return

  const curvePath = buildCurvePath(pixelPoints)

  const fillPath = new Path2D(curvePath)
  const last = pixelPoints[pixelPoints.length - 1]
  fillPath.lineTo(last.x, h)
  fillPath.lineTo(pixelPoints[0].x, h)
  fillPath.closePath()

  // Strong gradient fills
  const brightGrad = ctx.createLinearGradient(0, 0, 0, h)
  brightGrad.addColorStop(0, color + '70')
  brightGrad.addColorStop(0.4, color + '40')
  brightGrad.addColorStop(0.8, color + '18')
  brightGrad.addColorStop(1, color + '00')

  const dimGrad = ctx.createLinearGradient(0, 0, 0, h)
  dimGrad.addColorStop(0, color + '28')
  dimGrad.addColorStop(0.4, color + '14')
  dimGrad.addColorStop(0.8, color + '08')
  dimGrad.addColorStop(1, color + '00')

  // ── Played portion (left of playhead) — bright ──
  ctx.save()
  ctx.beginPath()
  ctx.rect(0, 0, playheadX, h)
  ctx.clip()

  ctx.fillStyle = brightGrad
  ctx.fill(fillPath)

  ctx.shadowColor = color + '66'
  ctx.shadowBlur = 6
  ctx.strokeStyle = color + 'dd'
  ctx.lineWidth = 4
  ctx.stroke(curvePath)

  ctx.shadowColor = 'transparent'
  ctx.shadowBlur = 0
  ctx.strokeStyle = color
  ctx.lineWidth = 3
  ctx.stroke(curvePath)
  ctx.restore()

  // ── Unplayed portion (right of playhead) — dim ──
  ctx.save()
  ctx.beginPath()
  ctx.rect(playheadX, 0, w - playheadX, h)
  ctx.clip()

  ctx.fillStyle = dimGrad
  ctx.fill(fillPath)

  ctx.strokeStyle = color + '55'
  ctx.lineWidth = 3
  ctx.stroke(curvePath)
  ctx.restore()
}

// ── Draw playhead indicators on each curve ────────

function drawPlayheadIndicators(ctx, graphDataList, w, h, playheadX) {
  ctx.save()
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)'
  ctx.lineWidth = 1
  ctx.setLineDash([4, 4])
  ctx.beginPath()
  ctx.moveTo(playheadX, 0)
  ctx.lineTo(playheadX, h)
  ctx.stroke()
  ctx.setLineDash([])
  ctx.restore()

  for (const { pixelPoints, color, visible } of graphDataList) {
    if (!visible || pixelPoints.length < 2) continue

    const y = interpolateYAtX(pixelPoints, playheadX)

    ctx.beginPath()
    ctx.arc(playheadX, y, 8, 0, Math.PI * 2)
    ctx.fillStyle = color + '33'
    ctx.fill()

    ctx.beginPath()
    ctx.arc(playheadX, y, 5, 0, Math.PI * 2)
    ctx.fillStyle = color
    ctx.fill()

    ctx.beginPath()
    ctx.arc(playheadX, y, 2, 0, Math.PI * 2)
    ctx.fillStyle = '#ffffff'
    ctx.fill()
  }
}

// ── Grid drawing (full width & height) ────────────

function pickTimeInterval(dur) {
  const steps = [5, 10, 15, 30, 60, 120, 300, 600, 900, 1800]
  for (const step of steps) {
    if (dur / step <= 12) return step
  }
  return 3600
}

function drawGrid(ctx, w, h, dur) {
  if (gridOpacity.value <= 0) return

  ctx.save()
  ctx.globalAlpha = gridOpacity.value

  const lineColor = 'rgba(255, 255, 255, 0.55)'
  const labelColor = 'rgba(255, 255, 255, 0.7)'
  const labelFont = `${Math.max(9, Math.min(11, w * 0.018))}px sans-serif`

  ctx.lineWidth = 0.75
  ctx.font = labelFont

  // ── Horizontal lines (25%, 50%, 75%, 100%) — full width ──
  const usableH = h * (1 - PAD_TOP - PAD_BOT)
  const bottom = h * (1 - PAD_BOT)

  ctx.textBaseline = 'middle'
  ctx.textAlign = 'left'

  const hLevels = [0.25, 0.5, 0.75, 1.0]
  for (const pct of hLevels) {
    const y = bottom - pct * usableH

    ctx.strokeStyle = lineColor
    ctx.beginPath()
    ctx.moveTo(0, y)
    ctx.lineTo(w, y)
    ctx.stroke()

    ctx.fillStyle = labelColor
    ctx.fillText(`${Math.round(pct * 100)}%`, 4, y - 1)
  }

  // ── Vertical lines (time intervals) — full height ──
  const interval = pickTimeInterval(dur)
  ctx.textBaseline = 'top'
  ctx.textAlign = 'center'

  for (let t = interval; t < dur; t += interval) {
    const x = (t / dur) * w

    ctx.strokeStyle = lineColor
    ctx.beginPath()
    ctx.moveTo(x, 0)
    ctx.lineTo(x, h)
    ctx.stroke()

    const mins = Math.floor(t / 60)
    const secs = Math.floor(t % 60)
    const label = mins > 0 ? `${mins}:${secs.toString().padStart(2, '0')}` : `${secs}s`

    ctx.fillStyle = labelColor
    ctx.fillText(label, x, h * (1 - PAD_BOT) + 3)
  }

  ctx.globalAlpha = 1
  ctx.restore()
}

// ── Equalizer helpers ────────────────────────────

function getCurrentNormalizedValue(graphIndex) {
  const graph = graphs[graphIndex]

  const points = getValues(graph.key)
  if (points.length < 2) return 0

  const dur = props.duration || 1
  const time = progress.value * dur
  const scaleMax = getScaleMax(graphIndex)

  let i = 0
  for (; i < points.length - 1; i++) {
    if (points[i + 1].time >= time) break
  }

  if (i >= points.length - 1) return points[points.length - 1].value / scaleMax

  const p0 = points[i]
  const p1 = points[i + 1]
  const t = p1.time === p0.time ? 0 : (time - p0.time) / (p1.time - p0.time)
  const val = p0.value + (p1.value - p0.value) * t
  return Math.max(0, Math.min(1, val / scaleMax))
}

function updateEqLevels() {
  for (let i = 0; i < graphs.length; i++) {
    eqLevels[i] = getCurrentNormalizedValue(i)
  }
}

function eqSegmentColor(segIndex, graphIndex) {
  const graph = graphs[graphIndex]
  if (!graph.threshold) return graph.color

  const scaleMax = getScaleMax(graphIndex)
  const segValue = (segIndex / EQ_SEGMENT_COUNT) * scaleMax
  const t = graph.threshold

  if (segValue < t) return '#22c55e'
  if (segValue < t * 2) return '#fbbf24'
  return '#ef4444'
}

function eqSegmentDimColor(segIndex, graphIndex) {
  const graph = graphs[graphIndex]
  if (!graph.threshold) return graph.color.slice(0, 7) + '18'

  const scaleMax = getScaleMax(graphIndex)
  const segValue = (segIndex / EQ_SEGMENT_COUNT) * scaleMax
  const t = graph.threshold

  if (segValue < t) return 'rgba(34, 197, 94, 0.1)'
  if (segValue < t * 2) return 'rgba(251, 191, 36, 0.1)'
  return 'rgba(239, 68, 68, 0.1)'
}

// ── Main draw function ────────────────────────────

function draw() {
  const canvas = canvasRef.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  const w = canvasWidth.value
  const h = canvasHeight.value
  if (w === 0 || h === 0) return

  ctx.clearRect(0, 0, w, h)

  const dur = props.duration || 1
  drawGrid(ctx, w, h, dur)

  const playheadX = progress.value * w
  const graphDataList = []
  const gAlpha = graphOpacity.value

  if (gAlpha > 0) {
    ctx.save()
    ctx.globalAlpha = gAlpha

    for (let gi = 0; gi < graphs.length; gi++) {
      const graph = graphs[gi]
      if (!graph.visible) continue

      const points = getValues(graph.key)
      if (points.length < 2) continue

      const maxVal = getScaleMax(gi)
      const gDur = props.duration || Math.max(...points.map((p) => p.time), 1)

      const normalized = toPixelPoints(points, w, h, maxVal, gDur)
      if (normalized[0].x > 0) normalized.unshift({ x: 0, y: normalized[0].y })
      if (normalized[normalized.length - 1].x < w)
        normalized.push({ x: w, y: normalized[normalized.length - 1].y })

      drawGraph(ctx, normalized, graph.color, w, h, playheadX)
      graphDataList.push({ pixelPoints: normalized, color: graph.color, visible: true })
    }

    ctx.globalAlpha = 1
    ctx.restore()
  }

  if (progress.value > 0 && progress.value < 1 && gAlpha > 0) {
    drawPlayheadIndicators(ctx, graphDataList, w, h, playheadX)
  }

  // Update equalizer levels (drives the reactive div-based EQ bars)
  updateEqLevels()
}

// ── Video control handlers ────────────────────────

function togglePlay() {
  const v = videoRef.value
  if (!v) return
  if (v.paused) v.play()
  else v.pause()
}

function onPlay() { playing.value = true; startAnimLoop() }
function onPause() { playing.value = false; stopAnimLoop() }

function onTimeUpdate() {
  const v = videoRef.value
  if (!v) return
  currentTime.value = v.currentTime
  videoDuration.value = v.duration || props.duration
  if (!seekingProgress.value) {
    progress.value = videoDuration.value > 0 ? v.currentTime / videoDuration.value : 0
  }
}

function onEnded() {
  playing.value = false
  stopAnimLoop()
  draw()
}

function startAnimLoop() {
  stopAnimLoop()
  function tick() {
    onTimeUpdate()
    draw()
    rafId = requestAnimationFrame(tick)
  }
  rafId = requestAnimationFrame(tick)
}

function stopAnimLoop() {
  if (rafId) {
    cancelAnimationFrame(rafId)
    rafId = null
  }
}

// ── Progress bar scrubbing ────────────────────────

function onProgressMouseDown(e) {
  seekingProgress.value = true
  scrubProgress(e)
  window.addEventListener('mousemove', scrubProgress)
  window.addEventListener('mouseup', onProgressMouseUp)
}

function scrubProgress(e) {
  const bar = progressRef.value
  if (!bar) return
  const rect = bar.getBoundingClientRect()
  const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  progress.value = pct
  draw()
}

function onProgressMouseUp() {
  seekingProgress.value = false
  window.removeEventListener('mousemove', scrubProgress)
  window.removeEventListener('mouseup', onProgressMouseUp)
  const v = videoRef.value
  if (v && videoDuration.value) {
    v.currentTime = progress.value * videoDuration.value
  }
}

// ── Volume control ────────────────────────────────

function toggleMute() {
  const v = videoRef.value
  if (!v) return
  muted.value = !muted.value
  v.muted = muted.value
}

function onVolumeMouseDown(e) {
  seekingVolume.value = true
  scrubVolume(e)
  window.addEventListener('mousemove', scrubVolume)
  window.addEventListener('mouseup', onVolumeMouseUp)
}

function scrubVolume(e) {
  const bar = volumeRef.value
  if (!bar) return
  const rect = bar.getBoundingClientRect()
  const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  volume.value = pct
  const v = videoRef.value
  if (v) {
    v.volume = pct
    if (pct > 0) muted.value = false
  }
}

function onVolumeMouseUp() {
  seekingVolume.value = false
  window.removeEventListener('mousemove', scrubVolume)
  window.removeEventListener('mouseup', onVolumeMouseUp)
}

// ── Opacity slider scrubbing ──────────────────────

function onGraphSliderDown(e) {
  seekingGraphSlider = true
  scrubGraphSlider(e)
  window.addEventListener('mousemove', scrubGraphSlider)
  window.addEventListener('mouseup', onGraphSliderUp)
}

function scrubGraphSlider(e) {
  const el = graphSliderRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  graphOpacity.value = pct
  graphsOn.value = pct > 0
  draw()
}

function onGraphSliderUp() {
  seekingGraphSlider = false
  window.removeEventListener('mousemove', scrubGraphSlider)
  window.removeEventListener('mouseup', onGraphSliderUp)
}

function onGridSliderDown(e) {
  seekingGridSlider = true
  scrubGridSlider(e)
  window.addEventListener('mousemove', scrubGridSlider)
  window.addEventListener('mouseup', onGridSliderUp)
}

function scrubGridSlider(e) {
  const el = gridSliderRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  gridOpacity.value = pct
  gridOn.value = pct > 0
  draw()
}

function onGridSliderUp() {
  seekingGridSlider = false
  window.removeEventListener('mousemove', scrubGridSlider)
  window.removeEventListener('mouseup', onGridSliderUp)
}

// ── Fullscreen ────────────────────────────────────

function toggleFullscreen() {
  const wrapper = wrapperRef.value
  if (!wrapper) return
  if (!document.fullscreenElement) {
    wrapper.requestFullscreen()
  } else {
    document.exitFullscreen()
  }
}

function onFullscreenChange() {
  fullscreen.value = !!document.fullscreenElement
  updateSize()
}

// ── Auto-hide controls ────────────────────────────

function showControls() {
  controlsVisible.value = true
  clearTimeout(hideTimeout)
  if (playing.value) {
    hideTimeout = setTimeout(() => {
      if (!hoveringControls.value && playing.value) {
        controlsVisible.value = false
      }
    }, 2500)
  }
}

function onControlsEnter() { hoveringControls.value = true; controlsVisible.value = true }
function onControlsLeave() { hoveringControls.value = false; showControls() }

// ── Canvas sizing ─────────────────────────────────

let resizeObserver = null

function updateSize() {
  const wrapper = wrapperRef.value
  if (!wrapper) return
  const video = videoRef.value
  if (!video) return

  const rect = video.getBoundingClientRect()
  canvasWidth.value = rect.width
  canvasHeight.value = rect.height

  const dpr = window.devicePixelRatio || 1

  const canvas = canvasRef.value
  if (canvas) {
    canvas.width = rect.width * dpr
    canvas.height = rect.height * dpr
    canvas.style.width = rect.width + 'px'
    canvas.style.height = rect.height + 'px'
    const ctx = canvas.getContext('2d')
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
  }

  draw()
}

function onVideoLoaded() {
  const v = videoRef.value
  if (v) videoDuration.value = v.duration || props.duration
  updateSize()
}

// ── Lifecycle ─────────────────────────────────────

onMounted(() => {
  resizeObserver = new ResizeObserver(() => updateSize())
  if (wrapperRef.value) resizeObserver.observe(wrapperRef.value)
  document.addEventListener('fullscreenchange', onFullscreenChange)
})

onBeforeUnmount(() => {
  if (resizeObserver) resizeObserver.disconnect()
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  stopAnimLoop()
  clearTimeout(hideTimeout)
})

watch(() => props.charts, () => draw(), { deep: true })
</script>

<template>
  <div class="overlay-card">
    <h3 class="overlay-title">Video Analysis Overlay</h3>

    <div class="video-with-eq">
      <!-- ── Equalizer bars (HTML/CSS) ───────────── -->
      <div class="eq-container">
        <div
          v-for="bar in eqBars"
          :key="bar.key"
          class="eq-bar"
        >
          <div
            v-for="(seg, si) in bar.segments"
            :key="si"
            class="eq-segment"
            :style="{ backgroundColor: seg.bg }"
          />
        </div>
      </div>

    <div
      ref="wrapperRef"
      class="video-wrapper"
      @mousemove="showControls"
      @mouseleave="onControlsLeave"
      @click.self="togglePlay"
    >
      <video
        ref="videoRef"
        :src="props.videoSrc"
        preload="metadata"
        class="video-element"
        @loadedmetadata="onVideoLoaded"
        @resize="updateSize"
        @play="onPlay"
        @pause="onPause"
        @timeupdate="onTimeUpdate"
        @ended="onEnded"
        @click="togglePlay"
      >
        Your browser does not support the video tag.
      </video>

      <canvas ref="canvasRef" class="overlay-canvas" />

      <!-- ── Custom Controls ─────────────────────── -->
      <div
        class="controls-bar"
        :class="{ visible: controlsVisible || !playing }"
        @mouseenter="onControlsEnter"
        @mouseleave="onControlsLeave"
      >
        <div
          ref="progressRef"
          class="progress-track"
          @mousedown.prevent="onProgressMouseDown"
        >
          <div class="progress-fill" :style="{ width: (progress * 100) + '%' }" />
          <div class="progress-dot" :style="{ left: (progress * 100) + '%' }" />
        </div>

        <div class="controls-row">
          <button class="ctrl-btn" @click.stop="togglePlay" aria-label="Play/Pause">
            <svg v-if="!playing" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
          </button>

          <button class="ctrl-btn" @click.stop="toggleMute" aria-label="Mute">
            <svg v-if="muted || volume === 0" viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
            <svg v-else-if="volume < 0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
          </button>

          <div
            ref="volumeRef"
            class="volume-track"
            @mousedown.prevent="onVolumeMouseDown"
          >
            <div class="volume-fill" :style="{ width: (muted ? 0 : volume * 100) + '%' }" />
            <div class="volume-dot" :style="{ left: (muted ? 0 : volume * 100) + '%' }" />
          </div>

          <span class="time-display">
            {{ formatTime(currentTime) }} / {{ formatTime(videoDuration) }}
          </span>

          <div class="ctrl-spacer" />

          <button class="ctrl-btn" @click.stop="toggleFullscreen" aria-label="Fullscreen">
            <svg v-if="!fullscreen" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="currentColor"><path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/></svg>
          </button>
        </div>
      </div>
    </div>
    </div>

    <!-- ── Toggle & slider bar ─────────────────── -->
    <div class="toggle-bar">
      <!-- Graph toggles -->
      <button
        v-for="(graph, index) in graphs"
        :key="graph.key"
        class="toggle-btn"
        :class="{ active: graph.visible }"
        :style="{ '--gc': graph.color }"
        @click="toggleGraph(index)"
      >
        <span class="toggle-dot" />
        {{ graph.label }}
      </button>

      <!-- Grid toggle -->
      <button
        class="toggle-btn"
        :class="{ active: gridOn }"
        :style="{ '--gc': '#94a3b8' }"
        @click="toggleGridOnOff"
      >
        <svg class="toggle-grid-icon" viewBox="0 0 16 16" fill="currentColor">
          <path d="M1 1h5v5H1V1zm9 0h5v5h-5V1zM1 10h5v5H1v-5zm9 0h5v5h-5v-5z" opacity="0.7"/>
        </svg>
        Grid
      </button>

      <!-- Separator -->
      <span class="toggle-sep" />

      <!-- Graphs opacity slider -->
      <div class="slider-group">
        <span class="slider-label">Graphs</span>
        <div
          ref="graphSliderRef"
          class="opacity-track"
          :style="{ '--sc': '#818cf8' }"
          @mousedown.prevent="onGraphSliderDown"
        >
          <div class="opacity-fill" :style="{ width: (graphOpacity * 100) + '%' }" />
          <div class="opacity-dot" :style="{ left: (graphOpacity * 100) + '%' }" />
        </div>
        <span class="slider-value">{{ Math.round(graphOpacity * 100) }}%</span>
      </div>

      <!-- Grid opacity slider -->
      <div class="slider-group">
        <span class="slider-label">Grid</span>
        <div
          ref="gridSliderRef"
          class="opacity-track"
          :style="{ '--sc': '#94a3b8' }"
          @mousedown.prevent="onGridSliderDown"
        >
          <div class="opacity-fill" :style="{ width: (gridOpacity * 100) + '%' }" />
          <div class="opacity-dot" :style="{ left: (gridOpacity * 100) + '%' }" />
        </div>
        <span class="slider-value">{{ Math.round(gridOpacity * 100) }}%</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overlay-card {
  background: var(--color-surface);
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
  border: 1px solid var(--color-line);
}

@media (min-width: 768px) {
  .overlay-card { padding: 1.5rem; }
}

.overlay-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-heading);
  margin-bottom: 0.875rem;
  letter-spacing: -0.01em;
}

/* ── Video + EQ layout ──────────────────────── */

.video-with-eq {
  display: flex;
  align-items: stretch;
  gap: 15px;
}

.eq-container {
  flex-shrink: 0;
  background: transparent;
  display: flex;
  gap: 3px;
  align-items: stretch;
}

.eq-bar {
  width: 14px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.eq-segment {
  flex: 1;
  border-radius: 1.5px;
  transition: background-color 80ms ease;
}

/* ── Video wrapper ───────────────────────────── */

.video-wrapper {
  position: relative;
  flex: 1;
  min-width: 0;
  border-radius: 15px;
  overflow: hidden;
  background: #000;
  cursor: pointer;
}

.video-element {
  display: block;
  width: 100%;
  height: auto;
}

.video-element::-webkit-media-controls { display: none !important; }

.overlay-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

/* ── Custom controls bar ─────────────────────── */

.controls-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0 0.75rem 0.5rem;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.7) 40%, rgba(0, 0, 0, 0.85));
  opacity: 0;
  transition: opacity 0.25s ease;
  cursor: default;
  z-index: 10;
}

.controls-bar.visible { opacity: 1; }

/* ── Progress bar ────────────────────────────── */

.progress-track {
  position: relative;
  width: 100%;
  height: 3px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 1.5px;
  cursor: pointer;
  margin-bottom: 0.375rem;
  transition: height 0.15s ease;
}

.progress-track:hover { height: 5px; }

.progress-fill {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: #e11d48;
  border-radius: 1.5px;
  pointer-events: none;
}

.progress-dot {
  position: absolute;
  top: 50%;
  width: 13px;
  height: 13px;
  background: #e11d48;
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(0);
  transition: transform 0.15s ease;
  pointer-events: none;
  box-shadow: 0 0 4px rgba(225, 29, 72, 0.5);
}

.progress-track:hover .progress-dot,
.controls-bar:has(.progress-track:active) .progress-dot {
  transform: translate(-50%, -50%) scale(1);
}

/* ── Controls row ────────────────────────────── */

.controls-row {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.ctrl-btn {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: #fff;
  cursor: pointer;
  border-radius: 0.25rem;
  padding: 0.25rem;
  opacity: 0.85;
  transition: opacity 0.15s ease, background 0.15s ease;
  flex-shrink: 0;
}

.ctrl-btn:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
}

.ctrl-btn svg {
  width: 1.25rem;
  height: 1.25rem;
}

/* ── Volume slider ───────────────────────────── */

.volume-track {
  position: relative;
  width: 60px;
  height: 3px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 1.5px;
  cursor: pointer;
  flex-shrink: 0;
}

.volume-fill {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: #fff;
  border-radius: 1.5px;
  pointer-events: none;
}

.volume-dot {
  position: absolute;
  top: 50%;
  width: 11px;
  height: 11px;
  background: #fff;
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(0);
  transition: transform 0.15s ease;
  pointer-events: none;
}

.volume-track:hover .volume-dot {
  transform: translate(-50%, -50%) scale(1);
}

/* ── Time display ────────────────────────────── */

.time-display {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.8);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  margin-left: 0.25rem;
  user-select: none;
}

.ctrl-spacer { flex: 1; }

/* ── Toggle & slider bar ─────────────────────── */

.toggle-bar {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
  flex-wrap: wrap;
  align-items: center;
}

.toggle-btn {
  --gc: #888;
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.3125rem 0.6875rem;
  border: 1px solid color-mix(in srgb, var(--gc) 25%, var(--color-line));
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0.01em;
  color: var(--color-muted);
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  border-color: color-mix(in srgb, var(--gc) 60%, var(--color-line));
  background: color-mix(in srgb, var(--gc) 6%, transparent);
}

.toggle-btn.active {
  border-color: color-mix(in srgb, var(--gc) 50%, transparent);
  background: color-mix(in srgb, var(--gc) 10%, transparent);
  color: var(--gc);
}

.toggle-dot {
  width: 0.4375rem;
  height: 0.4375rem;
  border-radius: 50%;
  flex-shrink: 0;
  background: var(--color-muted);
  transition: background 0.2s ease, box-shadow 0.2s ease;
}

.toggle-btn.active .toggle-dot {
  background: var(--gc);
  box-shadow: 0 0 5px color-mix(in srgb, var(--gc) 50%, transparent);
}

.toggle-grid-icon {
  width: 0.6875rem;
  height: 0.6875rem;
  flex-shrink: 0;
}

/* ── Separator ───────────────────────────────── */

.toggle-sep {
  width: 1px;
  height: 1.25rem;
  background: var(--color-line-strong);
  margin: 0 0.125rem;
  flex-shrink: 0;
}

/* ── Opacity slider groups ───────────────────── */

.slider-group {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  flex-shrink: 0;
}

.slider-label {
  font-size: 0.6875rem;
  font-weight: 500;
  color: var(--color-muted);
  user-select: none;
  white-space: nowrap;
}

.slider-value {
  font-size: 0.625rem;
  font-weight: 500;
  color: var(--color-muted);
  font-variant-numeric: tabular-nums;
  min-width: 2rem;
  user-select: none;
}

.opacity-track {
  --sc: #888;
  position: relative;
  width: 64px;
  height: 3px;
  background: var(--color-line-strong);
  border-radius: 1.5px;
  cursor: pointer;
  flex-shrink: 0;
}

.opacity-fill {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: var(--sc);
  border-radius: 1.5px;
  pointer-events: none;
  opacity: 0.7;
}

.opacity-dot {
  position: absolute;
  top: 50%;
  width: 11px;
  height: 11px;
  background: var(--sc);
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(0);
  transition: transform 0.15s ease;
  pointer-events: none;
}

.opacity-track:hover .opacity-dot {
  transform: translate(-50%, -50%) scale(1);
}
</style>
