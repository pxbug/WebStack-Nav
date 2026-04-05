# 导航网 - 游戏大厅

## 1. Concept & Vision

一款简洁高效的加拿大28游戏大厅，采用深色科技风格，突出实时开奖数据和倒计时，给用户专业可靠的游戏体验。

## 2. Design Language

### 美学方向
- 深色科技风格，类似加密货币/金融交易界面
- 霓虹渐变点缀，质感高级

### 色彩
- 背景：#0f0f1a（深紫黑）
- 卡片背景：#1a1a2e
- 主色调：#6c5ce7（紫）
- 强调色：#00cec9（青绿）
- 文字主色：#ffffff
- 文字次色：#a0a0b0
- 成功色：#00b894
- 警告色：#fdcb6e
- 危险色：#ff7675

### 字体
- 主字体：'Inter', -apple-system, sans-serif
- 数字字体：'Roboto Mono', monospace

### 动效
- 倒计时数字闪烁动画
- 开奖结果数字滚动效果
- 卡片悬停轻微上浮 + 发光边框

## 3. Layout & Structure

### 大厅页面
- 顶部导航栏（标题 + 用户信息）
- 加拿大28游戏卡片（点击进入场次选择）
- 实时开奖数据展示区
- 下期开奖倒计时

### 场次选择弹窗
- 4个场次按钮：1.88场 / 2.0场 / 2.88场 / 3.2场
- 点击后进入对应场次游戏页面

## 4. Features & Interactions

### 首页/大厅
- 展示加拿大28游戏入口卡片
- 实时获取并展示最近20期开奖结果
- 实时倒计时（每秒刷新）
- 滚动展示最新开奖记录

### 场次选择
- 点击"加拿大28"进入场次弹窗
- 显示4个场次倍率按钮
- 点击场次进入对应游戏房间

## 5. Component Inventory

### GameCard
- 展示游戏名称、图标、简介
- 悬停状态：发光边框 + 上浮

### LottoResultItem
- 展示单期开奖结果
- 期号、日期、时间、号码、组合类型

### CountdownTimer
- 实时倒计时显示 HH:MM:SS
- 最后30秒高亮警告

### RoomModal
- 4个场次选择按钮
- 每个按钮显示场次名称和倍率

## 6. Technical Approach

- 前端框架：Vue 3 + Vite
- 样式：原生 CSS + CSS Variables
- HTTP：axios
- API：https://pc28.ai/api/kj.json?nbr=20 和 /api/keno.json?nbr=1
- 状态管理：Vue Composition API
