<?php
$page_title = '导航管理';
require_once __DIR__ . '/config.php';

$pdo = pdo_connect();
init_database();

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

$nav_items = $pdo->query("
    SELECT n.*, c.name AS cat_name
    FROM nav_items n
    JOIN categories c ON c.id = n.category_id
    ORDER BY n.category_id, n.sort_order
")->fetchAll();

$navs_by_cat = [];
foreach ($categories as $cat) {
    $navs_by_cat[$cat['id']] = [
        'name' => $cat['name'],
        'slug' => $cat['slug'],
        'items' => [],
    ];
}
foreach ($nav_items as $item) {
    if (isset($navs_by_cat[$item['category_id']])) 
        $navs_by_cat[$item['category_id']]['items'][] = $item;
    }
}

$msg = '';
$err = '';

if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    try {
        $pdo->prepare("DELETE FROM nav_items WHERE id = ?")->execute([$id]);
        header("Location: nav.php?msg=" . urlencode('删除成功'));
        exit;
    } catch (Exception $e) {
        $err = '删除失败：' . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $category_id = intval($_POST['category_id']);
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if ($title === '' || $link === '') {
            $err = '标题和跳转链接不能为空';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO nav_items (category_id, title, subtitle, icon, link, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $title, $subtitle, $icon, $link, $sort_order]);
                header("Location: nav.php?msg=" . urlencode('添加成功'));
                exit;
            } catch (Exception $e) {
                $err = '添加失败：' . $e->getMessage();
            }
        }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if ($title === '' || $link === '') {
            $err = '更新失败：标题和跳转链接不能为空';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE nav_items SET title=?, subtitle=?, icon=?, link=?, sort_order=? WHERE id=?");
                $stmt->execute([$title, $subtitle, $icon, $link, $sort_order, $id]);
                header("Location: nav.php?msg=" . urlencode('更新成功'));
                exit;
            } catch (Exception $e) {
                $err = '更新失败：' . $e->getMessage();
            }
        }
    }
}

// ===== 输出开始 =====
require_once __DIR__ . '/header.php';
?>
<style>
.page-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.page-title h2 { font-size: 18px; font-weight: 600; }
.btn-primary {
    padding: 8px 18px;
    background: #c9a227;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-primary:hover { background: #b8922a; }
.btn-sm {
    padding: 4px 10px;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    background: #fff;
    color: #6b6b6b;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}
.btn-sm:hover { border-color: #c9a227; color: #c9a227; }
.btn-sm.del:hover { border-color: #e03e3e; color: #e03e3e; }
.cat-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin-bottom: 16px;
    overflow: hidden;
}
.cat-header {
    padding: 12px 20px;
    background: #fafaf8;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cat-header span {
    display: inline-block;
    padding: 2px 8px;
    background: #f5f0e6;
    color: #c9a227;
    border-radius: 4px;
    font-size: 12px;
}
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th {
    text-align: left;
    padding: 10px 14px;
    color: #999;
    font-weight: 500;
    border-bottom: 1px solid #f5f5f5;
    white-space: nowrap;
}
td {
    padding: 10px 14px;
    border-bottom: 1px solid #f8f8f8;
    color: #333;
    vertical-align: middle;
}
tr:last-child td { border-bottom: none; }
.icon-thumb {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
    background: #f5f5f5;
    border: 1px solid #f0f0f0;
}
.icon-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #f5f5f5;
    border: 1px dashed #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ccc;
    font-size: 18px;
}
.link-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #999;
    font-size: 12px;
}
.actions { display: flex; gap: 6px; align-items: center; }
.empty-cell { color: #ccc; font-size: 12px; }

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal {
    background: #fff;
    border-radius: 14px;
    width: 460px;
    max-width: 90vw;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.modal-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.modal-header h3 { font-size: 16px; font-weight: 600; }
.modal-close {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    font-size: 16px;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.modal-close:hover { background: #eee; color: #333; }
.modal-body { padding: 20px 24px 24px; }
.form-row {
    margin-bottom: 16px;
}
.form-row label {
    display: block;
    font-size: 13px;
    color: #6b6b6b;
    margin-bottom: 6px;
}
.form-row input,
.form-row select,
.form-row textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}
.form-row input:focus,
.form-row select:focus,
.form-row textarea:focus { border-color: #c9a227; }
.form-row textarea { resize: vertical; min-height: 60px; }
.form-hint {
    font-size: 11px;
    color: #aaa;
    margin-top: 4px;
}
.icon-input-row {
    display: flex;
    gap: 8px;
    align-items: center;
}
.icon-input-row input { flex: 1; }
.icon-preview {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
    background: #f5f5f5;
    border: 1px solid #f0f0f0;
    flex-shrink: 0;
}
.icon-preview.empty {
    background: #f5f5f5;
    border: 1px dashed #ddd;
}
.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.btn-cancel {
    padding: 9px 18px;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
    cursor: pointer;
    color: #6b6b6b;
    transition: all 0.2s;
}
.btn-cancel:hover { border-color: #ccc; color: #333; }
.btn-save {
    padding: 9px 20px;
    border: none;
    border-radius: 8px;
    background: #c9a227;
    color: #fff;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-save:hover { background: #b8922a; }
</style>

<div class="page-title">
    <h2>导航管理</h2>
    <button class="btn-primary" onclick="openAddModal()">+ 添加导航</button>
</div>

<?php if ($msg): ?>
<div style="background:#e6f7e6;color:#2e8b57;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;"><?= $msg ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div style="background:#fff2f2;color:#e03e3e;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<?php foreach ($navs_by_cat as $cat_id => $cat): ?>
<div class="cat-section">
    <div class="cat-header">
        <span class="cat-badge"><?= htmlspecialchars($cat['name']) ?></span>
        <span style="font-weight:400;font-size:12px;color:#999;">共 <?= count($cat['items']) ?> 项</span>
    </div>
    <?php if (empty($cat['items'])): ?>
        <div style="padding:20px;text-align:center;color:#ccc;font-size:13px;">暂无导航，请点击上方添加</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th style="width:60px;">图标</th>
                <th>标题</th>
                <th>副标题</th>
                <th>跳转链接</th>
                <th>排序</th>
                <th style="width:100px;">操作</th>
            </tr></thead>
            <tbody>
            <?php foreach ($cat['items'] as $item): ?>
                <tr>
                    <td>
                        <?php if ($item['icon']): ?>
                            <img src="<?= htmlspecialchars($item['icon']) ?>" class="icon-thumb" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="icon-placeholder" style="display:none">?</div>
                        <?php else: ?>
                            <div class="icon-placeholder">?</div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['subtitle']) ?: '<span class="empty-cell">—</span>' ?></td>
                    <td><span class="link-text" title="<?= htmlspecialchars($item['link']) ?>"><?= htmlspecialchars($item['link']) ?></span></td>
                    <td><?= $item['sort_order'] ?></td>
                    <td class="actions">
                        <button class="btn-sm" onclick='openEditModal(<?= json_encode($item, JSON_UNESCAPED_UNICODE) ?>)'>编辑</button>
                        <a href="nav.php?del=<?= $item['id'] ?>" class="btn-sm del" onclick="return confirm('确定要删除该导航吗？')">删除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>添加导航</h3>
            <button class="modal-close" onclick="closeAddModal()">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <label>所属分类 <span style="color:#e03e3e">*</span></label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label>标题 <span style="color:#e03e3e">*</span></label>
                    <input type="text" name="title" placeholder="例如：经典大盘" required maxlength="100">
                </div>
                <div class="form-row">
                    <label>副标题</label>
                    <input type="text" name="subtitle" placeholder="例如：大额无忧 · 稳定流畅" maxlength="200">
                </div>
                <div class="form-row">
                    <label>图标</label>
                    <div class="icon-input-row">
                        <input type="text" name="icon" id="addIconUrl" placeholder="输入图片 URL" oninput="previewAddIcon()">
                        <label for="addIconFile" style="flex-shrink:0;cursor:pointer;padding:8px 12px;background:#f5f5f5;border:1px solid #e8e8e8;border-radius:8px;font-size:12px;color:#666;">上传</label>
                        <input type="file" id="addIconFile" accept="image/*" style="display:none" onchange="handleFileUpload(this, 'addIconUrl')">
                        <img id="addIconPreview" class="icon-preview empty" src="" style="display:none">
                    </div>
                    <div class="form-hint">支持输入图片 URL，或点击"上传"从本地上传图片</div>
                </div>
                <div class="form-row">
                    <label>跳转链接 <span style="color:#e03e3e">*</span></label>
                    <input type="text" name="link" placeholder="https://..." required maxlength="500">
                    <div class="form-hint">在新窗口打开，请填写完整 URL</div>
                </div>
                <div class="form-row">
                    <label>排序</label>
                    <input type="number" name="sort_order" value="0" min="0">
                    <div class="form-hint">数字越小排越前</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">取消</button>
                <button type="submit" class="btn-save">保存</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>编辑导航</h3>
            <button class="modal-close" onclick="closeEditModal()">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="modal-body">
                <div class="form-row">
                    <label>所属分类</label>
                    <select name="category_id" id="editCatId" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label>标题 <span style="color:#e03e3e">*</span></label>
                    <input type="text" name="title" id="editTitle" required maxlength="100">
                </div>
                <div class="form-row">
                    <label>副标题</label>
                    <input type="text" name="subtitle" id="editSubtitle" maxlength="200">
                </div>
                <div class="form-row">
                    <label>图标</label>
                    <div class="icon-input-row">
                        <input type="text" name="icon" id="editIconUrl" placeholder="输入图片 URL" oninput="previewEditIcon()">
                        <img id="editIconPreview" class="icon-preview empty" src="">
                        <div id="editIconPlaceholder" class="icon-placeholder" style="display:none">?</div>
                    </div>
                    <div class="form-hint">支持输入图片 URL，或直接修改后保存</div>
                </div>
                <div class="form-row">
                    <label>跳转链接 <span style="color:#e03e3e">*</span></label>
                    <input type="text" name="link" id="editLink" required maxlength="500">
                </div>
                <div class="form-row">
                    <label>排序</label>
                    <input type="number" name="sort_order" id="editSort" value="0" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">取消</button>
                <button type="submit" class="btn-save">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeAddModal() { document.getElementById('addModal').classList.remove('active'); }
function closeEditModal() { document.getElementById('editModal').classList.remove('active'); }

function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}

function openEditModal(item) {
    document.getElementById('editId').value = item.id;
    document.getElementById('editCatId').value = item.category_id;
    document.getElementById('editTitle').value = item.title;
    document.getElementById('editSubtitle').value = item.subtitle || '';
    document.getElementById('editIconUrl').value = item.icon || '';
    document.getElementById('editLink').value = item.link;
    document.getElementById('editSort').value = item.sort_order || 0;

    if (item.icon) {
        document.getElementById('editIconPreview').src = item.icon;
        document.getElementById('editIconPreview').style.display = 'block';
        document.getElementById('editIconPlaceholder').style.display = 'none';
    } else {
        document.getElementById('editIconPreview').style.display = 'none';
        document.getElementById('editIconPlaceholder').style.display = 'flex';
    }

    document.getElementById('editModal').classList.add('active');
}

function previewAddIcon() {
    var url = document.getElementById('addIconUrl').value;
    var preview = document.getElementById('addIconPreview');
    if (url) {
        preview.src = url;
        preview.style.display = 'block';
        preview.classList.remove('empty');
    } else {
        preview.style.display = 'none';
        preview.classList.add('empty');
    }
}

function previewEditIcon() {
    var url = document.getElementById('editIconUrl').value;
    var preview = document.getElementById('editIconPreview');
    var placeholder = document.getElementById('editIconPlaceholder');
    if (url) {
        preview.src = url;
        preview.style.display = 'block';
        preview.classList.remove('empty');
        placeholder.style.display = 'none';
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'flex';
    }
}

function handleFileUpload(input, targetId) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById(targetId).value = e.target.result;
        previewAddIcon();
    };
    reader.readAsDataURL(file);
}

document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

</div><!-- end page-content -->
</div><!-- end main-content -->
</div><!-- end admin-layout -->
</body>
</html>
