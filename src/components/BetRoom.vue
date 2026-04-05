<template>
  <div class="bet-room">
    <!-- Win overlay — 红封筒风格 + 金币粒子动画 -->
    <Teleport to="body">
      <div id="zjlcpt" :class="{ show: showWinOverlay }">
        <div id="tcbgbg">
          <canvas ref="animCanvasRef"></canvas>
          <div class="award-box" id="awardBox" :class="{ opened: showWinOverlay }">
            <div class="red-envelope">
              <div class="award-content">
                <div class="award-amount" id="winAmountDisplay">+{{ lastWinAmount.toFixed(2) }}<span>元</span></div>
              </div>
            </div>
          </div>
          <div class="win-close-btn" @click="closeWinAnimation">×</div>
        </div>
      </div>
    </Teleport>
    <header class="bet-header">
      <button type="button" class="header-logo" aria-label="返回大厅" @click="$emit('leave')">
        <span class="logo-crown" aria-hidden="true">👑</span>
      </button>
      <button type="button" class="header-title-btn">
        <span class="header-title-text">加拿大28-{{ roomLabel }}</span>
        <span class="header-caret" aria-hidden="true"></span>
      </button>
      <div class="header-actions">
        <button
          type="button"
          class="icon-btn"
          :aria-pressed="balanceHidden"
          aria-label="隐藏或显示余额"
          @click="balanceHidden = !balanceHidden"
        >
          <svg class="ico-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
        </button>
      </div>
    </header>

    <section class="panel panel-top">
      <div class="panel-half">
        <div class="panel-label">第<span class="period-num">{{ nextPeriod || '—' }}</span>期开奖</div>
        <div class="countdown-pill mono">{{ countdownDisplay }}</div>
      </div>
      <div class="panel-divider" aria-hidden="true"></div>
      <div class="panel-half panel-balance">
        <div class="panel-label row-label">
          账户余额
          <svg class="ico-refresh" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10" />
            <polyline points="1 20 1 14 7 14" />
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
          </svg>
        </div>
        <div class="balance-value" :class="{ masked: balanceHidden }">
          {{ balanceHidden ? '****' : balanceText }}
        </div>
      </div>
    </section>

    <section class="panel panel-result">
      <div class="result-card" @click="historyExpanded = !historyExpanded">
        <div class="result-head">
          <span class="result-title">第 <span class="period-num">{{ currentPeriod || '—' }}</span> 期开奖</span>
          <svg class="caret-svg" :class="{ rotated: historyExpanded }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </div>
        <div class="result-line">
          <div class="result-balls result-balls--main">
            <template v-for="(n, i) in resultNums" :key="i">
              <span v-if="i > 0" class="op">+</span>
              <span class="ball-blue">{{ n }}</span>
            </template>
            <span class="op">=</span>
            <span class="ball-red">{{ resultSum }}</span>
            <span class="result-tag">{{ resultTag }}</span>
          </div>
        </div>
      </div>
      <div v-if="historyExpanded" class="history-wrap">
        <div class="history-thead">
          <span>期号</span>
          <span>开奖时间</span>
          <span>开奖结果</span>
        </div>
        <div class="history-tbody">
          <div v-for="item in kjHistory.slice(1)" :key="item.nbr" class="history-tr">
            <span class="history-nbr">{{ item.nbr }}</span>
            <span class="history-time mono">{{ drawTimeShort(item.time) }}</span>
            <div class="history-result">
              <div class="result-balls result-balls--compact">
                <template v-for="(n, i) in parseResultNums(item.number)" :key="i">
                  <span v-if="i > 0" class="op">+</span>
                  <span class="ball-blue">{{ n }}</span>
                </template>
                <span class="op">=</span>
                <span class="ball-red">{{ item.num }}</span>
                <span class="result-tag">{{ item.combination }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div ref="chatRef" class="chat-area">
      <div
        v-for="msg in messages"
        :key="msg.id"
        class="chat-item"
        :class="{
          'chat-item--left':  msg.type === 'kj' || msg.type === 'betbill' || msg.type === 'system' || msg.type === 'bet-success' || msg.type === 'bet-fail',
          'chat-item--right': msg.type === 'user-text',
          'chat-item--self':  msg.type === 'user-text',
        }"
      >
        <template v-if="msg.type === 'kj' || msg.type === 'betbill' || msg.type === 'system' || msg.type === 'bet-success' || msg.type === 'bet-fail'">
          <div class="avatar avatar--bot" aria-hidden="true">
            <span v-if="msg.type === 'system' || msg.type === 'bet-success' || msg.type === 'bet-fail'" class="avatar-sys-text">SYS</span>
            <span v-else class="avatar-bot-text">C7</span>
          </div>
          <div class="chat-main">
            <div class="chat-meta chat-meta--bot">
              <span class="chat-name chat-name--bot">{{ msg.type === 'system' || msg.type === 'bet-success' || msg.type === 'bet-fail' ? '系统' : 'C7机器人' }}</span>
              <span v-if="msg.type === 'kj' || msg.type === 'betbill'" class="bot-badge" :class="msg.type === 'kj' ? 'bot-badge--check' : 'bot-badge--star'" aria-hidden="true"></span>
            </div>
            <div v-if="msg.type === 'betbill'" class="bot-card bot-card--bill">
              <div class="bot-bill-title">第{{ msg.period }}期玩家投注账单</div>
              <div v-if="getBillPlayers(msg)" class="bot-bill-body">
                <div v-for="(player, i) in getBillPlayers(msg)" :key="i" class="bot-bill-row">
                  <span class="bot-bill-name">{{ player.name }}</span>
                  <span class="bot-bill-right">
                    <span class="bot-bill-amount">(**{{ player.amount || '??' }})</span>
                    <span class="bot-bill-caret" aria-hidden="true"></span>
                  </span>
                </div>
              </div>
              <div v-else class="bot-bill-loading">
                <div v-for="i in 10" :key="i" class="bot-bill-skeleton"></div>
              </div>
            </div>
            <div v-else-if="msg.type === 'kj'" class="bot-card bot-card--kj">
              <div class="bot-kj-head">第{{ msg.period }}期开奖结果</div>
              <div class="bot-kj-body">
                <div class="result-balls result-balls--chat">
                  <template v-for="(n, i) in msg.nums" :key="i">
                    <span v-if="i > 0" class="op">+</span>
                    <span class="ball-blue">{{ n }}</span>
                  </template>
                  <span class="op">=</span>
                  <span class="ball-red">{{ msg.sum }}</span>
                  <span class="result-tag">{{ msg.tag }}</span>
                </div>
              </div>
              <div v-if="msg.footerTime" class="bot-kj-footer mono">{{ msg.footerTime }}</div>
            </div>
            <!-- system / bet-success / bet-fail 显示为普通气泡 -->
            <div v-else class="bubble" :class="{ 'bubble--system': msg.type === 'system', 'bubble--success': msg.type === 'bet-success', 'bubble--fail': msg.type === 'bet-fail' }">{{ msg.text }}</div>
          </div>
        </template>
        <template v-else>
          <div class="avatar" :style="{ background: msg.avatarBg }" aria-hidden="true"></div>
          <div class="chat-main">
            <div class="chat-meta">
              <span class="chat-name">{{ msg.name }}</span>
              <span class="chat-time mono">{{ msg.time }}</span>
            </div>
            <div class="bubble" :class="{ 'bubble--system': msg.type === 'system', 'bubble--success': msg.type === 'bet-success', 'bubble--fail': msg.type === 'bet-fail' }">{{ msg.text }}</div>
          </div>
        </template>
      </div>
    </div>

    <div class="compose-wrap">
      <div class="compose">
        <button type="button" class="quick-btn" @click="$emit('quick-bet')">
          <span aria-hidden="true">🚀</span>
          <span>快投</span>
        </button>
        <input
          v-model="draft"
          type="text"
          class="compose-input"
          placeholder="输入文字指令下注"
          enterkeyhint="send"
          @keyup.enter="send"
        />
        <button type="button" class="send-btn" @click="send">发送</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const BASE = ''

const props = defineProps({
  room: {
    type: Object,
    required: true,
  },
})

defineEmits(['leave', 'quick-bet'])

// 监听快投指令
watch(() => props.room?._quickBet, (qb) => {
  if (!qb) return
  const period = getBetPeriod()
  if (!period) return
  if (qb.text != null && String(qb.text).trim()) {
    draft.value = String(qb.text).trim()
  } else if (qb.type != null && qb.amount != null) {
    draft.value = `${qb.type}${qb.amount}`
  } else {
    props.room._quickBet = null
    return
  }
  props.room._quickBet = null
  nextTick(send)
})

const roomLabel = computed(() => props.room?.label ?? '2.8')

const balance = ref(200)
const balanceHidden = ref(false)
const balanceText = computed(() => balance.value.toFixed(2))

const resultNums = ref([0, 0, 0])
const resultTag = ref('')
const resultSum = ref(0)

const currentPeriod = ref('')
const nextPeriod = ref('')
const countdownDisplay = ref('')
const historyExpanded = ref(false)

const kjHistory = ref([])
let kjTimer = null
let countdownTimer = null
let localSeconds = 0

function ts() {
  return `&_t=${Date.now()}`
}

function parseResultNums(numberExpr) {
  if (!numberExpr) return [0, 0, 0]
  const parts = String(numberExpr).split('+')
  if (parts.length !== 3) return [0, 0, 0]
  return parts.map(n => {
    const v = parseInt(n.trim(), 10)
    return isNaN(v) ? 0 : v
  })
}

function drawTimeShort(t) {
  if (!t) return '—'
  const parts = String(t).split(':')
  return parts.length >= 2 ? `${parts[0]}:${parts[1]}` : t
}

/** 聊天开奖卡片底部时间，如 04-04 16:01 */
function chatFooterTime(t) {
  if (!t) return ''
  const s = String(t).trim()
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}:\d{2})/)
  if (m) {
    return `${m[2]}-${m[3]} ${m[4]}`
  }
  return drawTimeShort(s)
}

const messages = ref([])
const betBillData = ref({}) // period -> [{name, amount}, ...]
const userBets = ref({}) // period -> { amount: number }（当期用户投注总额）
const draft = ref('')
const chatRef = ref(null)

// 中奖逻辑
const pendingBets = ref({}) // period -> [{type, amount, odds}] 用户当期未结算的投注
const showWinOverlay = ref(false)
const lastWinAmount = ref(0)
const animCanvasRef = ref(null)

function getBillPlayers(msg) {
  const period = msg?.period
  return betBillData.value[period] || null
}

/** 各类玩法的赔率 */
const WIN_ODDS = {
  '大': 2.0, '小': 2.0,
  '单': 2.0, '双': 2.0,
  '大单': 4.2, '大双': 4.6,
  '小单': 4.2, '小双': 4.6,
  '极大': 15.0, '极小': 15.0,
  '对子': 3.2, '顺子': 14.0, '豹子': 66.0,
  '龙': 2.0, '虎': 2.0, '豹': 2.0,
}
for (let n = 0; n <= 27; n++) {
  const k = String(n).padStart(2, '0')
  WIN_ODDS[k] = 28
}

/**
 * 根据开奖结果判断某注是否中奖
 * nums: [n1, n2, n3]
 * type: 投注类型
 * return: boolean
 */
function betWins(nums, type) {
  const sum = nums.reduce((a, b) => a + b, 0)
  const isOdd = sum % 2 !== 0
  const isBig = sum >= 14
  switch (type) {
    case '大': return isBig
    case '小': return !isBig
    case '单': return isOdd
    case '双': return !isOdd
    case '大单': return isBig && isOdd
    case '小单': return !isBig && isOdd
    case '大双': return isBig && !isOdd
    case '小双': return !isBig && !isOdd
    case '极大': return sum >= 22
    case '极小': return sum <= 5
    case '对子': {
      const s = new Set(nums)
      return s.size === 2 || (s.size === 1 && nums.length === 3)
    }
    case '顺子': {
      const sorted = [...nums].sort((a, b) => a - b)
      return (sorted[2] - sorted[1] === 1) && (sorted[1] - sorted[0] === 1)
    }
    case '豹子': {
      return nums[0] === nums[1] && nums[1] === nums[2]
    }
    case '龙': case '虎': case '豹': {
      return type === '豹' ? nums[0] === nums[1] || nums[1] === nums[2] || nums[0] === nums[2] : false
    }
    default: {
      // 数字玩法
      const n = parseInt(type, 10)
      if (!isNaN(n) && n >= 0 && n <= 27) {
        return sum === n
      }
      return false
    }
  }
}

/**
 * 结算当期用户的所有投注
 * nums: [n1, n2, n3], period: 期号
 */
function settleBets(nums, period) {
  const bets = pendingBets.value[period]
  if (!bets || !bets.length) return

  let totalWin = 0
  bets.forEach(bet => {
    if (betWins(nums, bet.type)) {
      const odds = WIN_ODDS[bet.type] ?? 2.0
      totalWin += bet.amount * odds
    }
  })

  pendingBets.value[period] = []
  if (totalWin > 0) {
    balance.value += totalWin
    lastWinAmount.value = totalWin
    nextTick(() => playWinAnimation(totalWin))
  }
}

function playWinAnimation(amt) {
  const canvas = animCanvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const wrap = document.getElementById('zjlcpt')
  if (!wrap) return

  const dpr = window.devicePixelRatio || 1
  const w = wrap.offsetWidth
  const h = wrap.offsetHeight
  canvas.width = w * dpr
  canvas.height = h * dpr
  canvas.style.width = w + 'px'
  canvas.style.height = h + 'px'
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0)

  // coin sprite canvas
  const cCvs = document.createElement('canvas')
  const cC = cCvs.getContext('2d')
  cCvs.width = 40; cCvs.height = 40
  cC.beginPath(); cC.arc(20, 20, 18, 0, Math.PI * 2)
  const g1 = cC.createLinearGradient(0, 0, 40, 40)
  g1.addColorStop(0, '#fdfd96'); g1.addColorStop(0.5, '#f1c40f'); g1.addColorStop(1, '#d35400')
  cC.fillStyle = g1; cC.fill()
  cC.beginPath(); cC.arc(20, 20, 14, 0, Math.PI * 2)
  const g2 = cC.createLinearGradient(0, 0, 40, 40)
  g2.addColorStop(0, '#d35400'); g2.addColorStop(0.5, '#f39c12'); g2.addColorStop(1, '#f1c40f')
  cC.fillStyle = g2; cC.fill()
  cC.fillStyle = '#c0392b'; cC.fillRect(15, 15, 10, 10)
  cC.beginPath(); cC.arc(20, 20, 14, Math.PI, Math.PI * 1.5)
  cC.strokeStyle = 'rgba(255,255,255,0.6)'; cC.lineWidth = 2; cC.stroke()

  const rays = []
  for (let i = 0; i < 12; i++) {
    const a = (Math.PI * 2 / 12) * i
    rays.push({ a, s: 0.005, w: i % 2 === 0 ? 0.2 : 0.1 })
  }

  const particles = []
  for (let i = 0; i < 80; i++) {
    const a = Math.random() * Math.PI + Math.PI
    const sp = Math.random() * 15 + 10
    particles.push({
      x: w / 2, y: h / 2 + 50,
      vx: Math.cos(a) * sp, vy: Math.sin(a) * sp,
      g: 0.4, f: 0.98, rot: Math.random() * Math.PI * 2,
      rs: (Math.random() - 0.5) * 0.4, sx: 1,
      fs: (Math.random() - 0.5) * 0.2,
      sz: Math.random() * 0.5 + 0.6, al: 1,
      life: Math.random() * 60 + 60,
    })
  }

  showWinOverlay.value = true

  let rafId = null
  function render() {
    ctx.clearRect(0, 0, w, h)
    ctx.globalCompositeOperation = 'screen'
    rays.forEach(r => {
      ctx.save(); ctx.translate(w / 2, h / 2 - 50); ctx.rotate(r.a)
      const g = ctx.createLinearGradient(0, 0, Math.max(w, h), 0)
      g.addColorStop(0, 'rgba(255,234,167,0.8)')
      g.addColorStop(0.5, 'rgba(253,203,110,0.3)')
      g.addColorStop(1, 'rgba(200,100,50,0)')
      ctx.fillStyle = g
      ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(Math.max(w, h), Math.max(w, h) * r.w); ctx.lineTo(Math.max(w, h), -Math.max(w, h) * r.w)
      ctx.closePath(); ctx.fill(); ctx.restore()
      r.a += r.s
    })
    ctx.globalCompositeOperation = 'source-over'
    if (Math.random() > 0.5 && particles.length < 120) {
      particles.push({
        x: w / 2 + (Math.random() - 0.5) * 80, y: h / 2,
        vx: (Math.random() - 0.5) * 10, vy: -Math.random() * 10 - 5,
        g: 0.4, f: 0.98, rot: Math.random() * Math.PI * 2,
        rs: (Math.random() - 0.5) * 0.4, sx: 1,
        fs: (Math.random() - 0.5) * 0.2,
        sz: Math.random() * 0.5 + 0.6, al: 1,
        life: Math.random() * 60 + 60,
      })
    }
    for (let i = particles.length - 1; i >= 0; i--) {
      const p = particles[i]
      p.vy += p.g; p.vx *= p.f; p.vy *= p.f
      p.x += p.vx; p.y += p.vy; p.rot += p.rs
      p.sx = Math.cos(Date.now() * p.fs)
      if (Math.abs(p.sx) < 0.2) p.sx = 0.2 * Math.sign(p.sx || 1)
      p.life--
      if (p.life < 30) p.al = p.life / 30
      ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.scale(p.sx * p.sz, p.sz); ctx.globalAlpha = p.al
      ctx.drawImage(cCvs, -20, -20, 40, 40); ctx.restore()
      if (p.life <= 0 || p.y > h + 50) particles.splice(i, 1)
    }
    rafId = requestAnimationFrame(render)
  }
  render()

  // store cleanup on close
  const _close = closeWinAnimation
  window.__winAnimCtx = { ctx, particles, rafId }
}

function closeWinAnimation() {
  showWinOverlay.value = false
  const cleanup = window.__winAnimCtx
  if (cleanup) {
    if (cleanup.rafId) cancelAnimationFrame(cleanup.rafId)
    cleanup.ctx.clearRect(0, 0, cleanup.ctx.canvas.width, cleanup.ctx.canvas.height)
    cleanup.particles.length = 0
    window.__winAnimCtx = null
  }
}

/** 获取某期的真实玩家投注账单 */
async function fetchBetBill(period) {
  if (!period) return
  // 已有真实数据则跳过
  if (betBillData.value[period] && betBillData.value[period]._real) return

  try {
    const res = await fetch(`${BASE}/api/betbill.json?period=${period}${ts()}`)
    const json = await res.json()
    if (json.data) {
      // API 数据 + 用户投注，"我"的所有金额合计成一条
      const userTotal = userBets.value[period]?.amount || 0
      const merged = [...json.data]
      const existing = merged.find(e => e.name === '我')
      if (existing) {
        existing.amount += userTotal
      } else if (userTotal > 0) {
        merged.push({ name: '我', amount: userTotal })
      }
      betBillData.value[period] = merged
      betBillData.value[period]._real = true
      return
    }
  } catch (e) {
    console.error('fetchBetBill error', e)
  }
  // 接口失败时用用户自己的投注记录
  const userTotal = userBets.value[period]?.amount || 0
  if (userTotal > 0) {
    betBillData.value[period] = [{ name: '我', amount: userTotal }]
  }
}

function pushRobotMessagesForDraw(latest) {
  const nbr = latest?.nbr
  if (!nbr || nbr === lastRobotAnnouncePeriod.value) return
  lastRobotAnnouncePeriod.value = nbr

  // 中奖结算
  const nums = parseResultNums(latest.number)
  settleBets(nums, nbr)

  // 机器人推送账单/开奖结果时激活倒计时
  messages.value.push({
    id: msgId++,
    type: 'betbill',
    period: nbr,
  })

  fetchBetBill(nbr)

  const sum = parseInt(latest.num, 10) || nums.reduce((a, b) => a + b, 0)
  messages.value.push({
    id: msgId++,
    type: 'kj',
    period: nbr,
    nums,
    sum,
    tag: latest.combination || '',
    footerTime: chatFooterTime(latest.time),
  })
}

let msgId = 1
const lastRobotAnnouncePeriod = ref('')

function tickCountdown() {
  if (localSeconds > 0) {
    localSeconds--
    const m = Math.floor(localSeconds / 60)
    const s = localSeconds % 60
    countdownDisplay.value = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  } else if (countdownDisplay.value !== '开奖中') {
    countdownDisplay.value = '开奖中'
    // 倒计时到0时立即推进期号，防止在API回调前用户投进已截止的期
    if (nextPeriod.value) {
      nextPeriod.value = String(parseInt(nextPeriod.value, 10) + 1)
    }
  }
}

async function fetchKjAndSync() {
  try {
    const res = await fetch(`${BASE}/api/kj.json?nbr=20${ts()}`)
    const json = await res.json()
    const raw = json.countdown || '00:00'

    // sync countdown（等待 pushRobotMessagesForDraw 后再显示，否则倒计时比机器人消息先出来）
    const parts = raw.split(':')
    localSeconds = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10)

    const list = json.data || []
    kjHistory.value = list

    if (list.length >= 1) {
      const latest = list[0]
      currentPeriod.value = latest.nbr
      nextPeriod.value = String(parseInt(latest.nbr, 10) + 1)
      resultTag.value = latest.combination || ''

      // number = "1+6+4", num = "11"
      resultNums.value = parseResultNums(latest.number)
      resultSum.value = parseInt(latest.num, 10) || resultNums.value.reduce((a, b) => a + b, 0)

      pushRobotMessagesForDraw(latest)

      // 推送完机器人消息后再显示倒计时（倒计时归零时显示开奖中）
      countdownDisplay.value = raw === '00:00' ? '开奖中' : raw
    }
  } catch (e) {
    console.error('fetchKjAndSync error', e)
  }
}

onMounted(async () => {
  await fetchKjAndSync()
  countdownTimer = setInterval(tickCountdown, 1000)
  kjTimer = setInterval(fetchKjAndSync, 5000)
})

onUnmounted(() => {
  if (kjTimer) clearInterval(kjTimer)
  if (countdownTimer) clearInterval(countdownTimer)
})

/** 投注类型关键字映射（中文/拼音/英文缩写 -> API标识） */
const BET_TYPE_MAP = {
  '大': '大', '大 ': '大', 'd': '大', 'da': '大',
  '小': '小', '小 ': '小', 'x': '小', 'xiao': '小',
  '单': '单', '单 ': '单', 'dan': '单', 's': '单',
  '双': '双', '双 ': '双', 'shuang': '双', 'sh': '双',
  '大单': '大单', 'dx': '大单', 'danx': '大单',
  '小单': '小单', 'xd': '小单', 'xiaodan': '小单',
  '大双': '大双', 'ds': '大双', 'dashuang': '大双',
  '小双': '小双', 'xs': '小双', 'xiaoshuang': '小双',
  '极大': '极大', 'jd': '极大', 'jid': '极大',
  '极小': '极小', 'jx': '极小', 'jix': '极小',
  '对子': '对子', 'dz': '对子', 'duizi': '对子',
  '顺子': '顺子', 'sz': '顺子', 'shunzi': '顺子',
  '豹子': '豹子', 'bz': '豹子', 'baozi': '豹子',
  '龙': '龙', 'long': '龙', 'l': '龙',
  '虎': '虎', 'hu': '虎', 'h': '虎',
  '豹': '豹',
}
// 数字玩法 00–27（与指令中两位数字一致）
for (let n = 0; n <= 27; n++) {
  const k = String(n).padStart(2, '0')
  BET_TYPE_MAP[k] = k
}

/**
 * 解析投注命令字符串，返回 [{type, amount}] 数组
 * 支持格式：
 *   单注：大5 / d5 / 单5 / da5
 *   多注：大5s5 / 大单5小双3 / d5x5
 *   金额接在类型后面：5大 / 5d
 */
function parseBetCommand(raw) {
  const results = []
  const s = String(raw).trim().replace(/\s+/g, '')
  if (!s) return results

  // 遍历字符串，逐一匹配 "类型+金额" 或 "金额+类型" 的组合
  // 支持任意顺序混合，如 ds5d5 / 5大单5小 / d5x5
  let i = 0
  const TYPE_MAX_LEN = 3 // 最长类型名 = 2（极大/极小）

  while (i < s.length) {
    let matched = false

    // 优先尝试：类型名在前 + 数字在后（e.g. "大双5"）
    for (let len = TYPE_MAX_LEN; len >= 1; len--) {
      const typeCandidate = s.slice(i, i + len)
      const type = BET_TYPE_MAP[typeCandidate]
      const numPart = s.slice(i + len).match(/^(\d+)/)
      if (type && numPart) {
        const amount = parseInt(numPart[1], 10)
        if (amount > 0) results.push({ type, amount })
        i += len + numPart[1].length
        matched = true
        break
      }
    }

    if (matched) continue

    // 其次：数字在前 + 类型名在后（e.g. "5大双"）
    const numMatch = s.slice(i).match(/^(\d+)([^\d]+)/)
    if (numMatch) {
      const amount = parseInt(numMatch[1], 10)
      const type = BET_TYPE_MAP[numMatch[2]]
      if (type && amount > 0) results.push({ type, amount })
      i += numMatch[1].length + numMatch[2].length
      continue
    }

    // 无法解析，跳过一个字符继续
    i++
  }

  return results
}

/**
 * 根据期号获取下注期号（下一期）
 */
function getBetPeriod() {
  const cur = currentPeriod.value
  return cur ? String(parseInt(cur, 10) + 1) : ''
}

/**
 * 提交真实投注请求
 */
async function submitBet(betList) {
  if (!betList.length) return null

  const period = getBetPeriod()

  // 记录待结算的投注
  if (period) {
    if (!pendingBets.value[period]) pendingBets.value[period] = []
    betList.forEach(bet => {
      const odds = WIN_ODDS[bet.type] ?? 2.0
      pendingBets.value[period].push({ type: bet.type, amount: bet.amount, odds })
    })
  }

  // TODO: 正式环境替换为真实接口
  // const res = await fetch(`${BASE}/api/bet.json?${params.toString()}${ts()}`)
  // return await res.json()
  await new Promise(r => setTimeout(r, 600 + Math.random() * 400))
  const ok = Math.random() > 0.1
  return ok
    ? { success: true, message: '投注成功' }
    : { success: false, message: '余额不足，请充值' }
}

function padTime(n) {
  return String(n).padStart(2, '0')
}

function nowTimeString() {
  const d = new Date()
  return `${d.getFullYear()}-${padTime(d.getMonth() + 1)}-${padTime(d.getDate())} ${padTime(d.getHours())}:${padTime(d.getMinutes())}:${padTime(d.getSeconds())}`
}

async function send() {
  const t = draft.value.trim()
  if (!t) return

  // 倒计时归零时禁止投注（防止投进已截止的期）
  if (countdownDisplay.value === '开奖中') {
    const msgIdTmp = msgId++
    messages.value.push({ id: msgIdTmp, type: 'system', name: '系统', time: nowTimeString(), text: '本期已截止投注，请等待下一期' })
    nextTick(() => { if (chatRef.value) chatRef.value.scrollTop = chatRef.value.scrollHeight })
    return
  }

  draft.value = ''

  // 收集用户消息
  const userMsgId = msgId++
  messages.value.push({
    id: userMsgId,
    type: 'user-text',
    name: '我',
    time: nowTimeString(),
    text: t,
    avatarBg: 'linear-gradient(145deg,#3b82f6,#1d4ed8)',
  })

  nextTick(() => { if (chatRef.value) chatRef.value.scrollTop = chatRef.value.scrollHeight })

  // 尝试解析为投注命令
  const betList = parseBetCommand(t)
  if (!betList.length) return

  const period = getBetPeriod()
  if (!period) return

  // 插入投注确认气泡，紧跟在用户消息后面（防止开奖消息中途插入打乱顺序）
  const confirmMsgId = msgId++
  const insertIdx = messages.value.findIndex(m => m.id === userMsgId) + 1
  messages.value.splice(insertIdx, 0, {
    id: confirmMsgId,
    name: '系统',
    time: nowTimeString(),
    text: `正在下单...`,
    type: 'system',
    betList,
  })

  const result = await submitBet(betList)

  // 更新确认消息
  const confirmMsg = messages.value.find(m => m.id === confirmMsgId)
  if (!confirmMsg) return

  if (result && result.success) {
    const total = betList.reduce((s, b) => s + b.amount, 0)
    const desc = betList.map(b => `${b.type}×${b.amount}`).join(' | ')
    confirmMsg.text = `投注成功！\n${desc}\n共 ${total} 元，第${period}期`
    confirmMsg.type = 'bet-success'
    confirmMsg.betList = null
    balance.value = Math.max(0, balance.value - total)
    // 记录用户当期投注总额（同一期多笔合并）
    const userTotal = betList.reduce((s, b) => s + b.amount, 0)
    if (!userBets.value[period]) userBets.value[period] = { amount: 0 }
    userBets.value[period].amount += userTotal
    // 如果账单卡片已存在，合并进去（无论是否已有真实数据）
    if (betBillData.value[period]?._real) {
      const existing = betBillData.value[period].find(e => e.name === '我')
      if (existing) {
        existing.amount += userTotal
      } else {
        betBillData.value[period].push({ name: '我', amount: userTotal })
      }
    } else {
      // 账单卡片还未被 fetchBetBill 填充，先在 betBillData 里累计"我"的总额
      if (!betBillData.value[period]) betBillData.value[period] = []
      const existing = betBillData.value[period].find(e => e.name === '我')
      if (existing) {
        existing.amount += userTotal
      } else {
        betBillData.value[period].push({ name: '我', amount: userTotal })
      }
    }
  } else {
    confirmMsg.text = `投注失败：${result?.message || '请稍后重试'}`
    confirmMsg.type = 'bet-fail'
    confirmMsg.betList = null
  }

  nextTick(() => { if (chatRef.value) chatRef.value.scrollTop = chatRef.value.scrollHeight })
}

watch(
  () => messages.value.length,
  () => {
    nextTick(() => {
      const el = chatRef.value
      if (el) el.scrollTop = el.scrollHeight
    })
  },
)
</script>

<style scoped>
.bet-room {
  min-height: 100dvh;
  height: 100%;
  max-width: 480px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  background: #e4e0ee;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.06);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  overflow: hidden;
}

.bet-header {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  padding-top: calc(6px + env(safe-area-inset-top, 0px));
  padding-left: calc(12px + env(safe-area-inset-left, 0px));
  padding-right: calc(12px + env(safe-area-inset-right, 0px));
  background: #1e6fe6;
  color: #fff;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-logo {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.1s ease, transform 0.1s ease;
  -webkit-tap-highlight-color: rgba(255, 255, 255, 0.2);
}

.header-logo:active {
  background: rgba(0, 0, 0, 0.18);
  transform: scale(0.96);
}

.logo-crown {
  font-size: 14px;
  line-height: 1;
}

.header-title-btn {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: none;
  background: transparent;
  color: inherit;
  cursor: pointer;
  padding: 4px 2px;
  border-radius: 8px;
  transition: background 0.1s ease;
  -webkit-tap-highlight-color: rgba(255, 255, 255, 0.2);
  user-select: none;
}

.header-title-btn:active {
  background: rgba(0, 0, 0, 0.1);
}

.header-title-text {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.header-caret {
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 5px solid rgba(255, 255, 255, 0.95);
  margin-top: 1px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.icon-btn {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.1s ease;
  -webkit-tap-highlight-color: rgba(255, 255, 255, 0.2);
}

.icon-btn:active {
  background: rgba(0, 0, 0, 0.18);
  transform: scale(0.96);
}

.ico-eye {
  width: 18px;
  height: 18px;
}

.ico-burger {
  display: block;
  width: 16px;
  height: 2px;
  background: #fff;
  box-shadow: 0 -5px 0 #fff, 0 5px 0 #fff;
  border-radius: 1px;
}

.panel {
  flex-shrink: 0;
  background: #fff;
  border-bottom: 1px solid #e5e1eb;
}

.panel-top {
  display: flex;
  align-items: stretch;
  padding: 5px 10px;
  padding-left: calc(10px + env(safe-area-inset-left, 0px));
  padding-right: calc(10px + env(safe-area-inset-right, 0px));
}

.panel-half {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.panel-balance {
  align-items: flex-end;
  text-align: right;
}

.panel-divider {
  width: 1px;
  background: #ddd8e8;
  margin: 4px 8px;
  align-self: stretch;
}

.panel-label {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}

.period-num {
  color: #2563eb;
  font-weight: 700;
}

.row-label {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.ico-refresh {
  width: 10px;
  height: 10px;
  color: #64748b;
  opacity: 0.85;
  flex-shrink: 0;
}

.countdown-pill {
  align-self: flex-start;
  background: #1e3a5f;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 5px;
  letter-spacing: 0.04em;
}

.balance-value {
  font-size: 13px;
  font-weight: 800;
  color: #ea580c;
  font-variant-numeric: tabular-nums;
}

.balance-value.masked {
  letter-spacing: 2px;
}

.panel-result {
  position: relative;
  z-index: 10;
  padding: 0;
  padding-left: calc(0px + env(safe-area-inset-left, 0px));
  padding-right: calc(0px + env(safe-area-inset-right, 0px));
  background: #fff;
}

.result-card {
  padding: 5px 10px 5px;
  padding-left: calc(10px + env(safe-area-inset-left, 0px));
  padding-right: calc(10px + env(safe-area-inset-right, 0px));
  cursor: pointer;
  user-select: none;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0.05);
}

.result-card:active {
  background: rgba(0, 0, 0, 0.02);
}

.result-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 4px;
  margin-bottom: 3px;
}

.result-title {
  font-size: 12px;
  font-weight: 700;
  color: #111827;
}

.result-line {
  display: flex;
  align-items: center;
}

.result-balls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 3px 2px;
}

.result-balls--main {
  gap: 4px 3px;
}

.result-balls--compact {
  gap: 2px 2px;
  justify-content: flex-end;
}

.ball-blue {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #5eb3f6;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.result-balls--compact .ball-blue {
  width: 18px;
  height: 18px;
  font-size: 10px;
}

.ball-red {
  min-width: 20px;
  height: 20px;
  padding: 0 4px;
  border-radius: 10px;
  background: #ef4444;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.result-balls--compact .ball-red {
  min-width: 18px;
  height: 18px;
  font-size: 10px;
  padding: 0 3px;
  border-radius: 9px;
}

.op {
  color: #9ca3af;
  font-weight: 600;
  font-size: 11px;
  padding: 0 1px;
}

.result-balls--compact .op {
  font-size: 10px;
}

.result-tag {
  color: #111827;
  font-weight: 600;
  font-size: 12px;
  margin-left: 4px;
}

.result-balls--compact .result-tag {
  font-size: 11px;
  margin-left: 3px;
}

.caret-svg {
  width: 14px;
  height: 14px;
  color: #9ca3af;
  display: block;
  transition: transform 0.2s ease;
  flex-shrink: 0;
  cursor: pointer;
}

.caret-svg.rotated {
  transform: rotate(180deg);
}

.chat-area {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 10px 8px 6px;
  overscroll-behavior: contain;
  scroll-behavior: smooth;
}

.chat-item {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  align-items: flex-start;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
}

.chat-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.chat-item--left .chat-main {
  align-items: flex-start;
}

.chat-meta {
  font-size: 11px;
  color: #94a3b8;
  margin-bottom: 4px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.chat-name {
  font-weight: 600;
  color: #64748b;
}

.bubble {
  position: relative;
  display: inline-block;
  max-width: 100%;
  background: #fff;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.bubble::before {
  content: '';
  position: absolute;
  left: -5px;
  top: 10px;
  border: 5px solid transparent;
  border-right-color: #fff;
}

.bubble {
  white-space: pre-line;
}

.bubble--system {
  background: #f1f5f9;
  color: #64748b;
}

.bubble--system::before {
  border-right-color: #f1f5f9;
}

.bubble--success {
  background: #dcfce7;
  color: #166534;
}

.bubble--success::before {
  border-right-color: #dcfce7;
}

.bubble--fail {
  background: #fee2e2;
  color: #991b1b;
}

.bubble--fail::before {
  border-right-color: #fee2e2;
}

.chat-item--self .bubble--system {
  background: #f1f5f9;
  color: #64748b;
}

.chat-item--self .bubble--system::before {
  left: auto;
  right: -5px;
  border-right-color: transparent;
  border-left-color: #f1f5f9;
}

.chat-item--self .bubble--success {
  background: #dcfce7;
  color: #166534;
}

.chat-item--self .bubble--success::before {
  left: auto;
  right: -5px;
  border-right-color: transparent;
  border-left-color: #dcfce7;
}

.chat-item--self .bubble--fail {
  background: #fee2e2;
  color: #991b1b;
}

.chat-item--self .bubble--fail::before {
  left: auto;
  right: -5px;
  border-right-color: transparent;
  border-left-color: #fee2e2;
}

.chat-item--self {
  flex-direction: row-reverse;
}

.chat-item--self .bubble {
  background: #1e6fe6;
  color: #fff;
}

.chat-item--self .bubble::before {
  left: auto;
  right: -5px;
  border-right-color: transparent;
  border-left-color: #1e6fe6;
}

.chat-item--self .chat-meta {
  flex-direction: row-reverse;
}

.chat-item--self .chat-name {
  color: #93c5fd;
}

.chat-item--self .chat-time {
  color: #93c5fd;
}

.chat-item--left {
  margin-bottom: 14px;
}

.avatar--bot {
  background: linear-gradient(180deg, #fff 0%, #f1f5f9 100%);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-bot-text {
  font-size: 11px;
  font-weight: 900;
  letter-spacing: -0.04em;
  background: linear-gradient(145deg, #d4a012, #8b6914);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  line-height: 1;
}

.avatar-sys-text {
  font-size: 9px;
  font-weight: 800;
  color: #94a3b8;
  line-height: 1;
}

.chat-meta--bot {
  margin-bottom: 6px;
  align-items: center;
}

.chat-name--bot {
  color: #ea580c;
  font-weight: 700;
}

.bot-badge {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
  margin-left: 2px;
}

.bot-badge--star {
  background: linear-gradient(145deg, #fb923c, #ea580c);
  border-radius: 3px;
  clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
}

.bot-badge--check {
  border-radius: 3px;
  background: linear-gradient(145deg, #fb923c, #ea580c);
  position: relative;
}

.bot-badge--check::after {
  content: '';
  position: absolute;
  left: 4px;
  top: 2px;
  width: 5px;
  height: 8px;
  border: solid #fff;
  border-width: 0 1.5px 1.5px 0;
  transform: rotate(45deg);
}

.bot-card {
  max-width: 100%;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.06);
}

.bot-card--bill {
  background: #fff;
  padding: 0;
  max-width: 66.67%;
}

.bot-bill-body {
  padding: 4px 4px 0;
  max-height: 220px;
  overflow-y: auto;
}

.bot-bill-loading {
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.bot-bill-skeleton {
  height: 14px;
  border-radius: 4px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.4s ease-in-out infinite;
}

@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.bot-bill-title {
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  padding: 10px 10px 8px;
  border-bottom: 1px solid #f1f5f9;
}

.bot-bill-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 8px 8px 10px;
  font-size: 13px;
  color: #334155;
  border-bottom: 1px solid #f8fafc;
}

.bot-bill-right {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.bot-bill-amount {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
}

.bot-bill-row:last-child {
  border-bottom: none;
}

.bot-bill-name {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bot-bill-caret {
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 5px 0 5px 6px;
  border-color: transparent transparent transparent #94a3b8;
  flex-shrink: 0;
  opacity: 0.85;
}

.bot-card--kj {
  background: #fff;
  max-width: 66.67%;
}

.bot-kj-head {
  background: #1e6fe6;
  color: #fff;
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  padding: 8px 10px;
}

.bot-kj-body {
  padding: 12px 10px 10px;
  background: #fff;
}

.result-balls--chat {
  gap: 4px 3px;
}

.result-balls--chat .ball-blue {
  width: 22px;
  height: 22px;
  font-size: 12px;
}

.result-balls--chat .ball-red {
  min-width: 22px;
  height: 22px;
  font-size: 12px;
  border-radius: 11px;
}

.result-balls--chat .op {
  font-size: 12px;
}

.result-balls--chat .result-tag {
  font-size: 13px;
}

.bot-kj-footer {
  text-align: center;
  font-size: 11px;
  font-style: italic;
  color: #94a3b8;
  padding: 0 10px 10px;
  background: #fff;
}

.compose-wrap {
  flex-shrink: 0;
  background: #fff;
  padding: 7px 10px;
  padding-bottom: calc(7px + env(safe-area-inset-bottom, 0px));
  border-top: 1px solid #e5e1eb;
  padding-left: calc(10px + env(safe-area-inset-left, 0px));
  padding-right: calc(10px + env(safe-area-inset-right, 0px));
}

.compose {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compose-input {
  flex: 1;
  min-width: 0;
  height: 40px;
  border: 1px solid #d4d0dc;
  border-radius: 999px;
  padding: 0 14px;
  font-size: 15px;
  outline: none;
  background: #fff;
  -webkit-appearance: none;
  appearance: none;
}

.quick-btn {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  width: 40px;
  height: 40px;
  border: 1.5px solid #c084fc;
  border-radius: 8px;
  background: #faf5ff;
  color: #7c3aed;
  cursor: pointer;
  font-size: 9px;
  font-weight: 700;
  line-height: 1;
  transition: background 0.1s ease, border-color 0.1s ease, transform 0.1s ease;
  -webkit-tap-highlight-color: rgba(124, 58, 237, 0.1);
  padding: 0;
}

.quick-btn:active {
  background: #ede9fe;
  border-color: #7c3aed;
  transform: scale(0.94);
}

.compose-input::placeholder {
  color: #94a3b8;
}

.compose-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.send-btn {
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  border: none;
  background: #1e6fe6;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  line-height: 1.1;
  padding: 4px;
  transition: transform 0.1s ease, box-shadow 0.1s ease;
  -webkit-tap-highlight-color: rgba(30, 111, 230, 0.3);
}

.send-btn:active {
  transform: scale(0.94);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
}

.mono {
  font-family: 'Roboto Mono', monospace;
}

.history-wrap {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  border-top: 1px solid #e5e7eb;
  background: #fff;
}

.history-thead {
  display: grid;
  grid-template-columns: 1fr 0.75fr 1.5fr;
  gap: 6px;
  align-items: center;
  padding: 7px 10px;
  padding-left: calc(10px + env(safe-area-inset-left, 0px));
  padding-right: calc(10px + env(safe-area-inset-right, 0px));
  background: #f3f4f6;
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
}

.history-thead span:last-child {
  text-align: right;
}

.history-tbody {
  max-height: 320px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.history-tr {
  display: grid;
  grid-template-columns: 1fr 0.75fr 1.5fr;
  gap: 6px;
  align-items: center;
  padding: 8px 10px;
  padding-left: calc(10px + env(safe-area-inset-left, 0px));
  padding-right: calc(10px + env(safe-area-inset-right, 0px));
  border-bottom: 1px solid #f0f0f0;
  font-size: 12px;
}

.history-tr:last-child {
  border-bottom: none;
}

.history-nbr {
  font-weight: 800;
  color: #2563eb;
  font-variant-numeric: tabular-nums;
}

.history-time {
  color: #6b7280;
  font-size: 12px;
}

.history-result {
  min-width: 0;
  display: flex;
  justify-content: flex-end;
}

/* Win Overlay — Red Envelope Style */
#zjlcpt {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  opacity: 0;
  transition: opacity 0.3s ease;
}
#zjlcpt.show {
  display: flex;
  opacity: 1;
}
#tcbgbg {
  position: relative;
  width: 100%; height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
#tcbgbg canvas {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  pointer-events: none;
}
.award-box {
  position: relative;
  z-index: 10;
  text-align: center;
  transform: scale(0);
  transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
#zjlcpt.show .award-box {
  transform: scale(1);
}
.red-envelope {
  width: 280px;
  height: 260px;
  position: relative;
  background: url('/static/image/win_img.png') no-repeat center / contain;
  border-radius: 20px;
}
.award-content {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 1;
}
.award-title {
  display: block;
  color: #ff2e2e;
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 10px;
}
.award-amount {
  color: #ff2e2e;
  font-size: 40px;
  font-weight: 800;
  text-shadow: 0 2px 4px rgba(0,0,0,0.25);
}
.award-amount span {
  font-size: 18px;
  margin-left: 2px;
}
.win-close-btn {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  width: 36px; height: 36px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  border: 2px solid #fff;
  color: #fff;
  font-size: 22px;
  line-height: 32px;
  cursor: pointer;
  z-index: 20;
  text-align: center;
}
</style>
