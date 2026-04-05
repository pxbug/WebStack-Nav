<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-sheet" @click.stop>
      <div class="sheet-header">
        <h2 class="sheet-title">选择游戏场次</h2>
        <button type="button" class="sheet-close" @click="$emit('close')" aria-label="关闭">✕</button>
      </div>
      <div class="sheet-body">
        <button
          v-for="room in rooms"
          :key="room.id"
          type="button"
          class="sheet-row"
          @click="selectRoom(room)"
        >
          <div class="row-ico" aria-hidden="true">
            <span class="ico-line">加拿大</span>
            <span class="ico-num">28</span>
          </div>
          <div class="row-title">加拿大28 - {{ room.label }}</div>
          <div class="row-meta">
            有 <span class="row-count">{{ room.players }}</span> 人正在玩
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const emit = defineEmits(['close', 'select'])

const rooms = [
  { id: 1, name: '1.88场', odds: '×1.88', label: '1.88', players: 2622 },
  { id: 2, name: '2.0场', odds: '×2.0', label: '2.0', players: 2423 },
  { id: 3, name: '2.88场', odds: '×2.88', label: '2.88', players: 1507 },
  { id: 4, name: '3.2场', odds: '×3.2', label: '3.2', players: 2360 },
]

function selectRoom(room) {
  emit('select', room)
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: overlayIn 0.25s ease forwards;
}

@keyframes overlayIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-sheet {
  width: 100%;
  max-width: 480px;
  background: #fff;
  border-radius: 20px 20px 0 0;
  box-shadow: 0 -8px 40px rgba(0, 0, 0, 0.12);
  animation: sheetUp 0.32s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  padding-bottom: env(safe-area-inset-bottom, 0px);
}

@keyframes sheetUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.sheet-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 18px 12px;
  padding-left: calc(18px + env(safe-area-inset-left, 0px));
  padding-right: calc(18px + env(safe-area-inset-right, 0px));
  border-bottom: 1px solid #ede9fe;
}

.sheet-title {
  font-size: 17px;
  font-weight: 700;
  color: #334155;
}

.sheet-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  font-size: 18px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.1s ease, transform 0.1s ease;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0.06);
}

.sheet-close:active {
  background: #e2e8f0;
  transform: scale(0.94);
}

.sheet-body {
  padding: 0 0 calc(8px + env(safe-area-inset-bottom, 0px));
}

.sheet-row {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  padding-left: calc(18px + env(safe-area-inset-left, 0px));
  padding-right: calc(18px + env(safe-area-inset-right, 0px));
  border: none;
  border-bottom: 1px solid #e9d5ff;
  background: #fff;
  cursor: pointer;
  text-align: left;
  transition: background 0.1s ease, transform 0.1s ease;
  -webkit-tap-highlight-color: rgba(109, 40, 217, 0.08);
}

.sheet-row:last-child {
  border-bottom: none;
}

.sheet-row:active {
  background: #faf5ff;
  transform: scale(0.99);
}

.row-ico {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(145deg, #8b5cf6, #6d28d9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(109, 40, 217, 0.35);
}

.ico-line {
  font-size: 8px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.95);
  line-height: 1;
  letter-spacing: 0.5px;
}

.ico-num {
  font-size: 15px;
  font-weight: 800;
  color: #fff;
  line-height: 1.1;
  margin-top: 2px;
}

.row-title {
  flex: 1;
  min-width: 0;
  font-size: 15px;
  font-weight: 600;
  color: #64748b;
}

.row-meta {
  flex-shrink: 0;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  white-space: nowrap;
}

.row-count {
  color: #2563eb;
  font-weight: 700;
}
</style>
