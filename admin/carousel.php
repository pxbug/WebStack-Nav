<?php
$page_title = '轮播图管理';
require_once __DIR__ . '/config.php';
$pdo = pdo_connect();
init_database();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $image_url = trim($_POST['image_url'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        if (empty($image_url)) {
            $err = '请填写图片地址或上传图片';
        } else {
            $image_url = normalize_public_path($image_url);
            $stmt = $pdo->prepare("INSERT INTO carousel (image_url, link, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$image_url, $link, $sort_order]);
            $msg = '添加成功';
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if (empty($image_url)) {
            $err = '图片地址不能为空';
        } else {
            $image_url = normalize_public_path($image_url);
            $stmt = $pdo->prepare("UPDATE carousel SET image_url=?, link=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$image_url, $link, $sort_order, $is_active, $id]);
            $msg = '更新成功';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM carousel WHERE id=?");
        $stmt->execute([$id]);
        $msg = '删除成功';
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE carousel SET is_active = 1 - is_active WHERE id=?");
        $stmt->execute([$id]);
    }
}

$slides = $pdo->query("SELECT * FROM carousel ORDER BY sort_order ASC, id ASC")->fetchAll();
require_once __DIR__ . '/header.php';
?>
<style>
.page-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; }
.card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr 80px; gap: 10px; margin-bottom: 14px; }
.form-grid input[type="text"] { width: 100%; padding: 9px 12px; border: 1px solid #e8e8e8; border-radius: 8px; font-size: 13px; outline: none; transition: border-color 0.2s; }
.form-grid input[type="text"]:focus { border-color: #c9a227; }
.btn { padding: 9px 18px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 500; transition: background 0.2s; }
.btn-primary { background: #c9a227; color: #fff; }
.btn-primary:hover { background: #b8922a; }
.btn-danger { background: #f5f0f0; color: #e03e3e; }
.btn-danger:hover { background: #ffe8e8; }
.btn-sm { padding: 5px 10px; font-size: 12px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { text-align: left; padding: 10px 12px; color: #6b6b6b; font-weight: 500; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
td { padding: 10px 12px; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
.carousel-thumb { width: 80px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #f0f0f0; }
.carousel-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; display: block; }
.toggle-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
.toggle-badge.on { background: #e6f7e6; color: #2e8b57; }
.toggle-badge.off { background: #fff2f2; color: #e03e3e; }
.actions { display: flex; gap: 6px; align-items: center; }
.url-preview { font-size: 12px; color: #999; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1000; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.modal { background: #fff; border-radius: 14px; width: 460px; max-width: 90vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-header { padding: 20px 24px 16px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { font-size: 16px; font-weight: 600; }
.modal-body { padding: 20px 24px; }
.modal-footer { padding: 14px 24px 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #f0f0f0; }
.close-btn { width: 28px; height: 28px; border-radius: 50%; border: none; background: #f5f5f5; cursor: pointer; font-size: 16px; line-height: 1; color: #888; }
.close-btn:hover { background: #eee; }
.form-row { margin-bottom: 14px; }
.form-row label { display: block; font-size: 13px; color: #666; margin-bottom: 6px; font-weight: 500; }
.form-row input[type="text"] { width: 100%; padding: 9px 12px; border: 1px solid #e8e8e8; border-radius: 8px; font-size: 13px; outline: none; transition: border-color 0.2s; }
.form-row input[type="text"]:focus { border-color: #c9a227; }
.form-row input[type="number"] { width: 100%; padding: 9px 12px; border: 1px solid #e8e8e8; border-radius: 8px; font-size: 13px; outline: none; transition: border-color 0.2s; }
.form-row input[type="number"]:focus { border-color: #c9a227; }
.form-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: #c9a227; }
.form-row .hint { font-size: 12px; color: #999; margin-top: 4px; }
.checkbox-row { display: flex; align-items: center; gap: 8px; }
.checkbox-row label { margin: 0; }
.alt-msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 13px; }
.alt-msg.ok { background: #e6f7e6; color: #2e8b57; }
.alt-msg.err { background: #fff2f2; color: #e03e3e; }
.img-mode-tabs { background: #f7f7f7; }
.img-mode-tabs .tab-btn { padding: 7px 16px; border: none; background: none; cursor: pointer; font-size: 13px; font-weight: 500; color: #888; border-radius: 0; transition: background 0.15s, color 0.15s; }
.img-mode-tabs .tab-btn:hover { color: #c9a227; }
.img-mode-tabs .tab-btn.active { background: #c9a227; color: #fff; font-weight: 600; }
</style>

<?php if ($msg): ?>
<div class="alt-msg ok"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alt-msg err"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="card">
    <div style="font-size:14px;font-weight:600;color:#333;margin-bottom:14px;">添加轮播图</div>
    <form method="POST" enctype="multipart/form-data" id="addForm">
        <input type="hidden" name="action" value="add">
        <div class="img-mode-tabs" style="display:flex;gap:0;margin-bottom:12px;border:1px solid #e0c880;border-radius:8px;overflow:hidden;width:fit-content;">
            <button type="button" class="tab-btn active" onclick="switchAddMode(this, 'url')">URL 模式</button>
            <button type="button" class="tab-btn" onclick="switchAddMode(this, 'upload')">上传模式</button>
        </div>
        <div id="add-url-wrap">
            <div class="form-grid">
                <input type="text" name="image_url" id="add_image_url" placeholder="图片地址（如 /assets/images/Banner.jpg）">
                <input type="text" name="link" placeholder="跳转链接（可选）">
                <input type="number" name="sort_order" value="0" placeholder="排序">
            </div>
        </div>
        <div id="add-upload-wrap" style="display:none;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <label class="btn btn-primary" style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    选择图片
                    <input type="file" name="local_image" id="add_local_image" accept="image/*" style="display:none" onchange="previewAddUpload(this)">
                </label>
                <span id="add-file-name" style="font-size:13px;color:#999;"></span>
            </div>
            <img id="add-upload-preview" style="display:none;max-width:200px;max-height:112px;object-fit:cover;border-radius:8px;border:1px solid #f0f0f0;margin-bottom:12px;">
            <input type="hidden" name="image_url" id="add_hidden_url">
            <div class="form-grid">
                <input type="text" id="add_link_url" placeholder="跳转链接（可选）">
                <input type="number" name="sort_order_add" value="0" placeholder="排序">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" id="addSubmit">添加</button>
    </form>
</div>

<div class="card">
    <div style="font-size:14px;font-weight:600;color:#333;margin-bottom:14px;">轮播图列表</div>
    <?php if (empty($slides)): ?>
    <p style="text-align:center;color:#999;font-size:13px;padding:24px;">暂无轮播图，请添加</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>图片</th>
                    <th>图片地址</th>
                    <th>链接</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $slide): ?>
                <tr>
                    <td>
                        <div class="carousel-thumb">
                            <img src="<?= htmlspecialchars(normalize_public_path($slide['image_url'])) ?>" alt="" onerror="this.style.display='none'">
                        </div>
                    </td>
                    <td style="max-width:150px;">
                        <span class="url-preview" title="<?= htmlspecialchars($slide['image_url']) ?>"><?= htmlspecialchars($slide['image_url']) ?></span>
                    </td>
                    <td style="max-width:120px;">
                        <span class="url-preview" title="<?= htmlspecialchars($slide['link']) ?>"><?= htmlspecialchars($slide['link']) ?: '—' ?></span>
                    </td>
                    <td><?= $slide['sort_order'] ?></td>
                    <td>
                        <span class="toggle-badge <?= $slide['is_active'] ? 'on' : 'off' ?>">
                            <?= $slide['is_active'] ? '显示' : '隐藏' ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-sm btn-primary" onclick='openEditModal(<?= json_encode($slide, JSON_UNESCAPED_UNICODE) ?>)'>编辑</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('确定删除该轮播图？')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $slide['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>编辑轮播图</h3>
            <button type="button" class="close-btn" onclick="closeEditModal()">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="img-mode-tabs" style="display:flex;gap:0;margin-bottom:12px;border:1px solid #e0c880;border-radius:8px;overflow:hidden;width:fit-content;">
                    <button type="button" class="tab-btn active" onclick="switchEditMode(this, 'url')">URL 模式</button>
                    <button type="button" class="tab-btn" onclick="switchEditMode(this, 'upload')">上传模式</button>
                </div>
                <div id="edit-url-wrap">
                    <div class="form-row">
                        <label>图片地址 *</label>
                        <input type="text" name="image_url" id="edit_image_url">
                    </div>
                </div>
                <div id="edit-upload-wrap" style="display:none;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <label class="btn btn-primary" style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            重新上传
                            <input type="file" name="local_image" id="edit_local_image" accept="image/*" style="display:none" onchange="previewEditUpload(this)">
                        </label>
                        <span id="edit-file-name" style="font-size:13px;color:#999;"></span>
                    </div>
                    <img id="edit-upload-preview" style="display:none;max-width:200px;max-height:112px;object-fit:cover;border-radius:8px;border:1px solid #f0f0f0;margin-bottom:12px;">
                    <input type="hidden" name="image_url" id="edit_hidden_url">
                </div>
                <div class="form-row">
                    <label>跳转链接</label>
                    <input type="text" name="link" id="edit_link">
                </div>
                <div class="form-row">
                    <label>排序（数字越小越靠前）</label>
                    <input type="number" name="sort_order" id="edit_sort_order" value="0">
                </div>
                <div class="form-row">
                    <div class="checkbox-row">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label for="edit_is_active">显示该轮播图</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchAddMode(btn, mode) {
    document.getElementById('add-url-wrap').style.display = mode === 'url' ? '' : 'none';
    document.getElementById('add-upload-wrap').style.display = mode === 'upload' ? '' : 'none';
    btn.closest('.img-mode-tabs').querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
}

function switchEditMode(btn, mode) {
    var modal = document.getElementById('editModal');
    modal.querySelector('#edit-url-wrap').style.display = mode === 'url' ? '' : 'none';
    modal.querySelector('#edit-upload-wrap').style.display = mode === 'upload' ? '' : 'none';
    modal.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
}

function previewAddUpload(input) {
    var file = input.files[0];
    if (!file) return;
    document.getElementById('add-file-name').textContent = file.name;
    var reader = new FileReader();
    reader.onload = function(e) {
        var preview = document.getElementById('add-upload-preview');
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
    uploadAndFillAdd();
}

function uploadAndFillAdd() {
    var fileInput = document.getElementById('add_local_image');
    var file = fileInput.files[0];
    if (!file) { alert('请先选择图片'); return; }
    var formData = new FormData();
    formData.append('image', file);
    fetch('upload.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(ret) {
            if (ret.code === 0) {
                document.getElementById('add_hidden_url').value = ret.path;
                document.getElementById('add-upload-preview').title = '已上传: ' + ret.path;
            } else {
                alert(ret.msg);
            }
        })
        .catch(function(err) { alert('上传失败: ' + err.message); });
}

function previewEditUpload(input) {
    var file = input.files[0];
    if (!file) return;
    document.getElementById('edit-file-name').textContent = file.name;
    var reader = new FileReader();
    reader.onload = function(e) {
        var preview = document.getElementById('edit-upload-preview');
        preview.src = e.target.result;
        preview.style.display = 'block';
        uploadAndFillEdit(document.getElementById('edit_local_image').files[0]);
    };
    reader.readAsDataURL(file);
}

function uploadAndFillEdit(file) {
    if (!file) { alert('请先选择图片'); return; }
    var formData = new FormData();
    formData.append('image', file);
    fetch('upload.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(ret) {
            if (ret.code === 0) {
                document.getElementById('edit_hidden_url').value = ret.path;
            } else {
                alert(ret.msg);
            }
        })
        .catch(function(err) { alert('上传失败: ' + err.message); });
}

document.getElementById('addForm').addEventListener('submit', function(e) {
    var uploadWrap = document.getElementById('add-upload-wrap');
    if (uploadWrap.style.display !== 'none') {
        var linkVal = document.getElementById('add_link_url').value;
        document.querySelector('input[name="link"]').value = linkVal;
        var sortVal = document.querySelector('input[name="sort_order_add"]').value;
        document.querySelector('input[name="sort_order"]').value = sortVal;
        e.preventDefault();
        var formData = new FormData(this);
        fetch(window.location.pathname, { method: 'POST', body: formData })
            .then(function() { window.location.reload(); });
    }
});

function openEditModal(slide) {
    var modal = document.getElementById('editModal');
    document.getElementById('edit_id').value = slide.id;
    document.getElementById('edit_link').value = slide.link || '';
    document.getElementById('edit_sort_order').value = slide.sort_order;
    document.getElementById('edit_is_active').checked = slide.is_active == 1;
    switchEditMode(modal.querySelector('.tab-btn'), 'url');
    document.getElementById('edit_image_url').value = slide.image_url || '';
    document.getElementById('edit_hidden_url').value = '';
    document.getElementById('edit-upload-preview').style.display = 'none';

    modal.classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

</div><!-- end page-content -->
</div><!-- end main-content -->
</div><!-- end admin-layout -->
</body>
</html>