<template>
  <BetRoom v-if="selectedRoom" :room="selectedRoom" @leave="selectedRoom = null" @quick-bet="showQuickBet = true" />
  <div v-else class="lobby">
    <main class="main-scroll">
      <div class="main-inner">
        <article class="game-banner banner-ca28" @click="openRoomModal">
          <div class="banner-art" aria-hidden="true">
            <div class="ball b1">5</div>
            <div class="ball b2">9</div>
            <div class="ball b3">2</div>
            <div class="coin c1"></div>
            <div class="coin c2"></div>
          </div>
          <div class="banner-body">
            <h2 class="banner-title">加拿大28</h2>
            <p class="banner-sub">- CANADA 28 -</p>
            <button type="button" class="enter-btn" @click.stop="openRoomModal">
              进入游戏
              <span class="enter-arr">›</span>
            </button>
          </div>
        </article>
      </div>
    </main>

    <RoomModal v-if="showRoomModal" @close="showRoomModal = false" @select="onSelectRoom" />
  </div>

  <!-- 快投弹窗（放在 BetRoom 同级，确保 BetRoom 渲染时也能显示） -->
  <Teleport to="body">
    <div v-if="showQuickBet" class="qb-overlay" @click.self="showQuickBet = false">
      <div class="qb-sheet">
        <div class="qb-handle" @click="showQuickBet = false"></div>
        <div class="qb-head">
          <span class="qb-title">快速投注</span>
          <button type="button" class="qb-close" @click="showQuickBet = false">✕</button>
        </div>
        <div class="qb-body">
          <div class="qb-input-row">
            <input class="qb-input" type="text" readonly :value="qbDraft" placeholder="请填入内容" />
            <button type="button" class="qb-clear-line" @click="qbClear">清空</button>
          </div>
          <div class="qb-num-scroll-wrap">
            <button type="button" class="qb-scroll-hint" @click="qbScroll(-1)">‹</button>
            <div ref="numScrollRef" class="qb-num-scroll">
              <button
                v-for="n in QB_NUMBERS"
                :key="n.label"
                type="button"
                class="qb-num-chip"
                @click="qbAppend(n.label + ' ')"
              >
                <span class="qb-num-chip-l">{{ n.label }}</span>
                <span class="qb-num-chip-o">1:{{ n.odds }}</span>
              </button>
            </div>
            <button type="button" class="qb-scroll-hint" @click="qbScroll(1)">›</button>
          </div>
          <div class="qb-spec-row">
            <button type="button" class="qb-spec-btn qb-spec-muted" @click="qbClear">取消下注</button>
            <button type="button" class="qb-spec-btn" @click="qbAppend('豹子')"><span>豹子</span><small>1:66</small></button>
            <button type="button" class="qb-spec-btn" @click="qbAppend('顺子')"><span>顺子</span><small>1:14</small></button>
            <button type="button" class="qb-spec-btn" @click="qbAppend('对子')"><span>对子</span><small>1:3.2</small></button>
            <button type="button" class="qb-backspace" title="退格" @click="qbBackspace">⌫</button>
          </div>
          <div class="qb-keypad">
            <div class="qb-col qb-col-side">
              <button v-for="t in QB_SIDE_L" :key="t.k" type="button" class="qb-side-btn" @click="qbAppend(t.k)">
                {{ t.k }}<small v-if="t.o">{{ t.o }}</small>
              </button>
              <button type="button" class="qb-side-btn qb-space" @click="qbAppend(' ')">空格</button>
            </div>
            <div class="qb-col qb-col-mid">
              <div class="qb-numpad">
                <button v-for="d in [1,2,3,4,5,6,7,8,9]" :key="d" type="button" class="qb-numkey" @click="qbAppend(String(d))">{{ d }}</button>
              </div>
              <div class="qb-mid-bottom">
                <button type="button" class="qb-numkey qb-ext" @click="qbAppend('极大')">极大<small>1:15</small></button>
                <button type="button" class="qb-numkey qb-zero" @click="qbAppend('0')">0</button>
                <button type="button" class="qb-numkey qb-ext" @click="qbAppend('极小')">极小<small>1:15</small></button>
              </div>
              <div class="qb-dragon-row">
                <button type="button" class="qb-dtb" @click="qbAppend('龙')">龙<small>1:2.85</small></button>
                <button type="button" class="qb-dtb" @click="qbAppend('虎')">虎<small>1:2.85</small></button>
                <button type="button" class="qb-dtb" @click="qbAppend('豹')">豹<small>1:2.85</small></button>
              </div>
            </div>
            <div class="qb-col qb-col-side qb-col-right">
              <button v-for="t in QB_SIDE_R" :key="t.k" type="button" class="qb-side-btn qb-combo" @click="qbAppend(t.k)">
                {{ t.k }}<small>{{ t.o }}</small>
              </button>
            </div>
          </div>
          <button type="button" class="qb-submit" :disabled="!qbDraft.trim()" @click="confirmQuickBet">确认下注</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import RoomModal from './components/RoomModal.vue'
import BetRoom from './components/BetRoom.vue'

/** 2.0 场数字赔率（与 28游戏规则说明 一致） */
function oddsForNumberLabel(label) {
  const n = parseInt(label, 10)
  const table = [
    [[0, 27], 488], [[1, 26], 128], [[2, 25], 88], [[3, 24], 58], [[4, 23], 48],
    [[5, 22], 38], [[6, 21], 28], [[7, 20], 18], [[8, 19], 15], [[9, 18], 15],
    [[10, 17], 14], [[11, 16], 13], [[12, 15], 12], [[13, 14], 11],
  ]
  for (const [pair, o] of table) {
    if (pair.includes(n)) return o
  }
  return 11
}

const QB_NUMBERS = Array.from({ length: 28 }, (_, i) => {
  const label = String(i).padStart(2, '0')
  return { label, odds: oddsForNumberLabel(label) }
})

const QB_SIDE_L = [
  { k: '大', o: '1:2' }, { k: '小', o: '1:2' }, { k: '单', o: '1:2' }, { k: '双', o: '1:2' },
]
const QB_SIDE_R = [
  { k: '大单', o: '1:4.2' }, { k: '大双', o: '1:4.6' }, { k: '小单', o: '1:4.6' }, { k: '小双', o: '1:4.2' },
]

const showRoomModal = ref(false)
const selectedRoom = ref(null)
const showQuickBet = ref(false)
const qbDraft = ref('')
const numScrollRef = ref(null)

watch(showQuickBet, (open) => {
  if (open) qbDraft.value = ''
})

function qbScroll(dir) {
  const el = numScrollRef.value
  if (!el) return
  el.scrollBy({ left: dir * 120, behavior: 'smooth' })
}

function qbAppend(s) {
  qbDraft.value += s
}

function qbBackspace() {
  qbDraft.value = qbDraft.value.slice(0, -1)
}

function qbClear() {
  qbDraft.value = ''
}

function openRoomModal() {
  showRoomModal.value = true
}

function onSelectRoom(room) {
  showRoomModal.value = false
  selectedRoom.value = room
}

function confirmQuickBet() {
  const text = qbDraft.value.trim()
  if (!text) return
  if (selectedRoom.value) {
    selectedRoom.value._quickBet = { text }
  }
  showQuickBet.value = false
}
</script>

<style scoped>
.lobby {
  min-height: 100dvh;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #eef0f4;
  max-width: 480px;
  margin: 0 auto;
  position: relative;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.main-scroll {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.main-inner {
  padding: 10px 10px calc(16px + env(safe-area-inset-bottom, 0px));
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.game-banner {
  position: relative;
  border-radius: 18px;
  min-height: 108px;
  display: flex;
  align-items: center;
  padding: 14px 14px 14px 8px;
  overflow: hidden;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  -webkit-tap-highlight-color: transparent;
}

.game-banner:active {
  transform: scale(0.985);
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
}

.banner-ca28 {
  background: linear-gradient(115deg, #10b981 0%, #34d399 35%, #c4b5fd 100%);
}

.banner-art {
  width: 42%;
  min-width: 120px;
  height: 100px;
  position: relative;
  flex-shrink: 0;
}

.ball {
  position: absolute;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 16px;
  color: #fff;
  box-shadow: inset -4px -4px 8px rgba(0, 0, 0, 0.15), 2px 4px 10px rgba(0, 0, 0, 0.2);
}

.ball.b1 {
  background: linear-gradient(145deg, #3b82f6, #1d4ed8);
  left: 8px;
  top: 8px;
  z-index: 3;
}

.ball.b2 {
  background: linear-gradient(145deg, #f97316, #ea580c);
  left: 44px;
  top: 36px;
  z-index: 2;
}

.ball.b3 {
  background: linear-gradient(145deg, #22c55e, #15803d);
  left: 18px;
  top: 52px;
  z-index: 1;
  width: 34px;
  height: 34px;
  font-size: 14px;
}

.coin {
  position: absolute;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: linear-gradient(145deg, #fde047, #eab308);
  right: 12px;
  top: 12px;
  box-shadow: 0 2px 6px rgba(234, 179, 8, 0.5);
}

.coin.c2 {
  width: 16px;
  height: 16px;
  right: 28px;
  top: 36px;
}

.banner-body {
  flex: 1;
  padding-left: 4px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 2px;
  z-index: 1;
}

.banner-title {
  font-size: 18px;
  font-weight: 800;
  color: #fff;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}

.banner-title.dark {
  color: #1e293b;
  text-shadow: none;
}

.banner-sub {
  font-size: 11px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 1px;
  margin-bottom: 6px;
}

.banner-sub.dark {
  color: #64748b;
}

.enter-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 8px 16px;
  border-radius: 999px;
  border: none;
  background: rgba(15, 23, 42, 0.88);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: transform 0.1s ease, box-shadow 0.1s ease;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0.15);
}

.enter-btn:active {
  transform: scale(0.95);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.enter-arr {
  font-size: 16px;
  font-weight: 400;
  opacity: 0.9;
}

.enter-btn.dark-outline {
  background: rgba(255, 255, 255, 0.95);
  color: #c2410c;
  border: 1px solid rgba(234, 88, 12, 0.3);
}

.enter-btn.dark-solid {
  background: #1e293b;
  color: #fff;
}

/* 快投弹窗（参考比特28全键盘布局） */
.qb-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 200;
  display: flex;
  align-items: flex-end;
}

.qb-sheet {
  background: #f8fafc;
  border-radius: 16px 16px 0 0;
  width: 100%;
  max-width: 480px;
  margin: 0 auto;
  max-height: min(88vh, 640px);
  display: flex;
  flex-direction: column;
  padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 12px);
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.12);
}

.qb-handle {
  width: 36px;
  height: 4px;
  border-radius: 2px;
  background: #cbd5e1;
  margin: 8px auto 0;
  flex-shrink: 0;
}

.qb-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 14px 6px;
  flex-shrink: 0;
}

.qb-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.qb-close {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: none;
  background: #e2e8f0;
  color: #64748b;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qb-body {
  padding: 0 10px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.qb-input-row {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 8px;
}

.qb-input {
  flex: 1;
  min-width: 0;
  height: 40px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 15px;
  color: #0f172a;
}

.qb-input::placeholder {
  color: #94a3b8;
}

.qb-clear-line {
  padding: 0 14px;
  height: 40px;
  border: none;
  border-radius: 8px;
  background: #e2e8f0;
  color: #475569;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  flex-shrink: 0;
}

.qb-num-scroll-wrap {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 8px;
}

.qb-scroll-hint {
  width: 22px;
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 18px;
  padding: 0;
  flex-shrink: 0;
  opacity: 0.6;
}

.qb-num-scroll {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  flex: 1;
  padding: 4px 0;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.qb-num-scroll::-webkit-scrollbar {
  display: none;
}

.qb-num-chip {
  flex: 0 0 auto;
  min-width: 52px;
  padding: 6px 8px;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  background: linear-gradient(180deg, #fff 0%, #eff6ff 100%);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.qb-num-chip-l {
  font-size: 15px;
  font-weight: 800;
  color: #1d4ed8;
}

.qb-num-chip-o {
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
}

.qb-spec-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
}

.qb-spec-btn {
  flex: 1;
  min-width: 56px;
  padding: 8px 6px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.qb-spec-btn small {
  font-size: 10px;
  font-weight: 600;
  color: #94a3b8;
}

.qb-spec-muted {
  color: #64748b;
}

.qb-backspace {
  width: 44px;
  height: 48px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 18px;
  color: #64748b;
  cursor: pointer;
  flex-shrink: 0;
}

.qb-keypad {
  display: flex;
  gap: 6px;
  margin-bottom: 10px;
}

.qb-col {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.qb-col-side {
  width: 52px;
  flex-shrink: 0;
}

.qb-col-right {
  width: 58px;
}

.qb-col-mid {
  flex: 1;
  min-width: 0;
}

.qb-side-btn {
  flex: 1;
  min-height: 36px;
  padding: 4px 2px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
  color: #fff;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  line-height: 1.1;
  box-shadow: 0 2px 0 #1e40af;
}

.qb-side-btn small {
  font-size: 9px;
  font-weight: 600;
  opacity: 0.9;
}

.qb-side-btn.qb-combo {
  font-size: 11px;
  min-height: 40px;
}

.qb-side-btn.qb-space {
  background: linear-gradient(180deg, #64748b 0%, #475569 100%);
  box-shadow: 0 2px 0 #334155;
  font-size: 11px;
  min-height: 32px;
}

.qb-numpad {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 5px;
}

.qb-numkey {
  aspect-ratio: 1.35;
  border: none;
  border-radius: 8px;
  background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
  color: #fff;
  font-size: 18px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 2px 0 #1e40af;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  line-height: 1.1;
}

.qb-numkey small {
  font-size: 9px;
  font-weight: 600;
  opacity: 0.95;
}

.qb-mid-bottom {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 5px;
  margin-top: 5px;
}

.qb-numkey.qb-ext {
  font-size: 11px;
  aspect-ratio: auto;
  min-height: 40px;
}

.qb-numkey.qb-zero {
  font-size: 20px;
}

.qb-dragon-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 5px;
  margin-top: 5px;
}

.qb-dtb {
  min-height: 38px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(180deg, #60a5fa 0%, #3b82f6 100%);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 0 #1d4ed8;
}

.qb-dtb small {
  font-size: 9px;
  font-weight: 600;
  opacity: 0.95;
}

.qb-submit {
  display: block;
  width: 100%;
  margin: 0 0 4px;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(180deg, #fb923c 0%, #ea580c 100%);
  color: #fff;
  font-size: 16px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 3px 0 #c2410c;
  transition: opacity 0.1s ease;
}

.qb-submit:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  box-shadow: none;
}

.qb-submit:not(:disabled):active {
  opacity: 0.92;
}

@media (min-width: 481px) {
  .lobby {
    min-height: 100vh;
    border-radius: 0;
  }
}
</style>
