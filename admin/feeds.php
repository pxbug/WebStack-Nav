<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$err = '';
$feeds = [];
$edit_row = null;
$db_err = '';

try {
    $pdo = pdo_connect();
    init_database();

    if (isset($_GET['del'])) {
        $id = intval($_GET['del']);
        $pdo->prepare("DELETE FROM feeds WHERE id = ?")->execute([$id]);
        header("Location: feeds.php?msg=" . urlencode('删除成功'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = trim($_POST['content'] ?? '');
        $images  = trim($_POST['images'] ?? '');

        if (empty($content) && empty($images)) {
            $err = '内容和图片至少填写一项';
        } else {
            $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
            if ($edit_id > 0) {
                $stmt = $pdo->prepare("UPDATE feeds SET content = ?, images = ? WHERE id = ?");
                $stmt->execute([$content, $images, $edit_id]);
                header("Location: feeds.php?msg=" . urlencode('更新成功'));
                exit;
            } else {
                $stmt = $pdo->prepare("INSERT INTO feeds (content, images) VALUES (?, ?)");
                $stmt->execute([$content, $images]);
                header("Location: feeds.php?msg=" . urlencode('发布成功'));
                exit;
            }
        }
    }

    if (isset($_GET['msg'])) {
        $msg = htmlspecialchars($_GET['msg']);
    }

    if (isset($_GET['edit'])) {
        $eid = intval($_GET['edit']);
        $edit_row = $pdo->prepare("SELECT * FROM feeds WHERE id = ?");
        $edit_row->execute([$eid]);
        $edit_row = $edit_row->fetch();
    }

    $feeds = $pdo->query("SELECT * FROM feeds ORDER BY created_at DESC")->fetchAll();

} catch (Exception $e) {
    $db_err = $e->getMessage();
}

$page_title = '发现管理';
require_once __DIR__ . '/header.php';
?>
<style>
.btn { padding: 8px 20px; border-radius: 8px; font-size: 14px; cursor: pointer; border: none; font-family: inherit; font-weight: 500; }
.btn-primary { background: #c9a227; color: #fff; }
.btn-primary:hover { background: #b8922a; }
.btn-outline { background: #fff; color: #666; border: 1px solid #d9d9d9; margin-left: 8px; }
.btn-outline:hover { border-color: #c9a227; color: #c9a227; }
.btn-danger { background: #ff4d4f; color: #fff; padding: 5px 14px; font-size: 12px; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; }
.btn-edit { background: #1890ff; color: #fff; padding: 5px 14px; font-size: 12px; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; }
.alert { padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
.alert-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
.alert-error { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
h2 { font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #333; }
</style>

<?php if ($db_err): ?>
<div style="background:#fff2f2;color:#e03e3e;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    数据库连接失败：<?= htmlspecialchars($db_err) ?><br>
    请确保 MySQL 已运行，并确认 <code>config.php</code> 中的数据库配置正确。
    <a href="?init" style="color:#c9a227;margin-left:8px;">初始化数据库</a>
</div>
<?php return; endif; ?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-error"><?= $err ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:24px;">
<h2><?= $edit_row ? '编辑动态' : '发布新动态' ?></h2>
<form method="post" id="feedForm">
  <?php if ($edit_row): ?>
  <input type="hidden" name="edit_id" value="<?= $edit_row['id'] ?>">
  <?php endif; ?>

  <div style="margin-bottom:14px;">
    <label style="display:block;font-size:13px;font-weight:500;color:#555;margin-bottom:6px;">动态内容</label>
    <textarea name="content" id="contentInput" placeholder="说点什么..." style="width:100%;padding:10px 12px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;resize:vertical;min-height:90px;font-family:inherit;"><?= $edit_row ? htmlspecialchars($edit_row['content']) : '' ?></textarea>
  </div>

  <div style="margin-bottom:14px;">
    <label style="display:block;font-size:13px;font-weight:500;color:#555;margin-bottom:6px;">图片</label>

    <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
      <div id="existingUrls" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;"></div>

      <div id="uploadArea" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <label id="uploadBtn" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#f5f5f5;border:1px dashed #ccc;border-radius:8px;cursor:pointer;font-size:13px;color:#666;">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          上传图片
          <input type="file" accept="image/*" id="imgInput" style="display:none;">
        </label>
        <span style="font-size:12px;color:#999;">支持 JPG/PNG/GIF/WebP，单张最大 8MB</span>
      </div>
    </div>

    <div id="previewArea" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;"></div>

    <input type="hidden" name="images" id="imagesField">
  </div>

  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <?php if ($edit_row): ?>
    <a href="feeds.php" class="btn btn-outline">取消编辑</a>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary" id="submitBtn"><?= $edit_row ? '保存修改' : '发布动态' ?></button>
  </div>
</form>
</div>

<div class="card">
<h2>已发布动态</h2>
<?php if (empty($feeds)): ?>
<p style="color:#999;font-size:14px;padding:16px 0;">暂无动态</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:14px;">
  <colgroup>
    <col style="width:38%">
    <col style="width:28%">
    <col style="width:20%">
    <col style="width:14%">
  </colgroup>
  <thead>
    <tr>
      <th style="text-align:left;padding:10px 12px;background:#fafafa;color:#666;font-weight:500;border-bottom:1px solid #e8e8e8;">内容</th>
      <th style="text-align:left;padding:10px 12px;background:#fafafa;color:#666;font-weight:500;border-bottom:1px solid #e8e8e8;">图片</th>
      <th style="text-align:left;padding:10px 12px;background:#fafafa;color:#666;font-weight:500;border-bottom:1px solid #e8e8e8;">发布时间</th>
      <th style="text-align:left;padding:10px 12px;background:#fafafa;color:#666;font-weight:500;border-bottom:1px solid #e8e8e8;">操作</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($feeds as $f): ?>
    <tr>
      <td style="padding:12px;border-bottom:1px solid #f0f0f0;max-width:260px;"><?= nl2br(htmlspecialchars($f['content'])) ?: '<span style="color:#ccc">（无文字）</span>' ?></td>
      <td style="padding:12px;border-bottom:1px solid #f0f0f0;">
        <?php
        if (!empty($f['images'])) {
            $imgs = array_filter(array_map('trim', explode(',', $f['images'])));
            foreach ($imgs as $img): ?>
              <img src="<?= htmlspecialchars($img) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:6px;margin-right:4px;border:1px solid #f0f0f0;vertical-align:middle;">
        <?php endforeach;
        } else {
            echo '<span style="color:#ccc;font-size:12px;">无图</span>';
        } ?>
      </td>
      <td style="padding:12px;border-bottom:1px solid #f0f0f0;"><?= $f['created_at'] ?></td>
      <td style="padding:12px;border-bottom:1px solid #f0f0f0;white-space:nowrap;">
        <a href="?edit=<?= $f['id'] ?>" class="btn-edit">编辑</a>
        <a href="?del=<?= $f['id'] ?>" class="btn-danger" onclick="return confirm('确定删除？')">删除</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</div>

<script>
var allImages = [];

function escapeHtml(str) {
  var d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}
  
var initialImages = <?php
    if ($edit_row && !empty($edit_row['images'])) {
        $imgs = array_filter(array_map('trim', explode(',', $edit_row['images'])));
        echo json_encode($imgs);
    } else {
        echo '[]';
    }
?>;

initialImages.forEach(function(url) {
  allImages.push(url);
  addPreview(url, true);
});

function syncField() {
  document.getElementById('imagesField').value = allImages.join(',');
}

function addPreview(src, isExisting) {
  var area = document.getElementById('previewArea');
  var wrap = document.createElement('div');
  wrap.style.cssText = 'position:relative;width:80px;height:80px;';
  var img = document.createElement('img');
  img.src = src;
  img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e8e8e8;display:block;';
  var rm = document.createElement('button');
  rm.type = 'button';
  rm.innerHTML = '×';
  rm.style.cssText = 'position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:#ff4d4f;color:#fff;border:none;cursor:pointer;font-size:14px;line-height:1;text-align:center;';
  rm.onclick = function() {
    allImages = allImages.filter(function(u) { return u !== src; });
    area.removeChild(wrap);
    syncField();
  };
  wrap.appendChild(img);
  wrap.appendChild(rm);
  area.appendChild(wrap);
}

document.getElementById('imgInput').addEventListener('change', function(e) {
  var file = e.target.files[0];
  if (!file) return;
  if (file.size > 8 * 1024 * 1024) {
    alert('图片大小不能超过 8MB');
    e.target.value = '';
    return;
  }

  var fd = new FormData();
  fd.append('image', file);

  var btn = document.getElementById('uploadBtn');
  var orig = btn.innerHTML;
  btn.innerHTML = '上传中…';
  btn.style.pointerEvents = 'none';

  fetch('../api/upload.php', { method: 'POST', body: fd })
    .then(function(r) {
      if (!r.ok) {
        return r.json().catch(function() { return { error: '上传失败，服务器错误 ' + r.status }; });
      }
      return r.json();
    })
    .then(function(d) {
      btn.innerHTML = orig;
      btn.style.pointerEvents = '';
      e.target.value = '';
      if (d.error) {
        alert(d.error);
      } else if (d.url) {
        allImages.push(d.url);
        addPreview(d.url, false);
        syncField();
      }
    })
    .catch(function() {
      btn.innerHTML = orig;
      btn.style.pointerEvents = '';
      e.target.value = '';
      alert('上传失败，请重试');
    });
});

syncField();
</script>
</body>
</html>
