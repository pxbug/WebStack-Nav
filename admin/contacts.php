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

try {
    $pdo = pdo_connect();
    init_database();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $type    = trim($_POST['type'] ?? '');
            $label   = trim($_POST['label'] ?? '');
            $value   = trim($_POST['value'] ?? '');
            $icon_bg = trim($_POST['icon_bg'] ?? '#999999');
            $sort_order = intval($_POST['sort_order'] ?? 0);

            if ($type && $label && $value) {
                $stmt = $pdo->prepare("INSERT INTO contacts (type, label, value, icon_bg, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$type, $label, $value, $icon_bg, $sort_order]);
                header("Location: contacts.php?msg=" . urlencode('添加成功'));
                exit;
            } else {
                $err = '请填写所有必填字段';
            }
        }

        if ($action === 'update') {
            $id      = intval($_POST['id'] ?? 0);
            $type    = trim($_POST['type'] ?? '');
            $label   = trim($_POST['label'] ?? '');
            $value   = trim($_POST['value'] ?? '');
            $icon_bg = trim($_POST['icon_bg'] ?? '#999999');
            $sort_order = intval($_POST['sort_order'] ?? 0);

            if ($id && $type && $label && $value) {
                $stmt = $pdo->prepare("UPDATE contacts SET type=?, label=?, value=?, icon_bg=?, sort_order=? WHERE id=?");
                $stmt->execute([$type, $label, $value, $icon_bg, $sort_order, $id]);
                header("Location: contacts.php?msg=" . urlencode('更新成功'));
                exit;
            } else {
                $err = '请填写所有必填字段';
            }
        }

        if ($action === 'toggle') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("UPDATE contacts SET is_active = 1 - is_active WHERE id = ?");
                $stmt->execute([$id]);
            }
            header("Location: contacts.php");
            exit;
        }

        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
                $stmt->execute([$id]);
            }
            header("Location: contacts.php?msg=" . urlencode('已删除'));
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM contacts ORDER BY sort_order ASC, id ASC");
    $stmt->execute();
    $contacts = $stmt->fetchAll();

    if (isset($_GET['msg'])) {
        $msg = htmlspecialchars($_GET['msg']);
    }

} catch (Exception $e) {
    $db_err = $e->getMessage();
}

$page_title = '联系方式管理';
require_once __DIR__ . '/header.php';
?>
<style>
.btn { padding: 7px 18px; border-radius: 8px; font-size: 13px; cursor: pointer; border: none; font-family: inherit; font-weight: 500; text-decoration: none; display: inline-block; }
.btn-primary { background: #c9a227; color: #fff; }
.btn-primary:hover { background: #b8922a; }
.btn-danger { background: #ff4d4f; color: #fff; }
.btn-danger:hover { background: #e63d3f; }
.btn-secondary { background: #f0f0f0; color: #666; }
.btn-secondary:hover { background: #e0e0e0; }
.btn-sm { padding: 4px 12px; font-size: 12px; }
.alert { padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
.alert-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
.alert-error { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
.section-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 20px; }
.section-title { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end; }
.form-grid.single { grid-template-columns: 1fr; }
.form-field { display: flex; flex-direction: column; gap: 4px; }
.form-label { font-size: 12px; color: #666; font-weight: 500; }
.form-label .required { color: #ff4d4f; margin-left: 2px; }
.form-input { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 8px; font-size: 13px; font-family: inherit; width: 100%; }
.form-input:focus { outline: none; border-color: #c9a227; }
.form-select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 8px; font-size: 13px; font-family: inherit; width: 100%; background: #fff; }
.form-select:focus { outline: none; border-color: #c9a227; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { text-align: left; padding: 10px 14px; background: #fafafa; color: #666; font-weight: 500; border-bottom: 1px solid #e8e8e8; white-space: nowrap; }
.data-table td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; color: #333; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #fafafa; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.badge-ww    { background: #fff0e6; color: #ff5000; }
.badge-tg    { background: #e6f4ff; color: #0088cc; }
.badge-email { background: #fffbe6; color: #c9a227; }
.badge-wechat { background: #f6ffed; color: #52c41a; }
.badge-phone  { background: #fff0f5; color: #ff4d9d; }
.badge-other  { background: #f5f5f5; color: #999; }
.color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); vertical-align: middle; margin-right: 6px; }
.status-on  { color: #52c41a; font-size: 12px; }
.status-off { color: #ff4d4f; font-size: 12px; }
.actions { display: flex; gap: 6px; align-items: center; }
.type-icon-wrap { display: flex; align-items: center; gap: 8px; }
</style>

<?php if (isset($db_err)): ?>
<div style="background:#fff2f2;color:#e03e3e;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    数据库连接失败：<?= htmlspecialchars($db_err) ?>
</div>
<?php return; endif; ?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<?php if ($err): ?>
<div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="section-card">
    <div class="section-title">添加联系方式</div>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-grid" style="margin-bottom:12px;">
            <div class="form-field">
                <label class="form-label">类型 <span class="required">*</span></label>
                <select name="type" class="form-select" required>
                    <option value="">请选择</option>
                    <option value="ww">旺旺</option>
                    <option value="tg">TG</option>
                    <option value="email">邮箱</option>
                    <option value="wechat">微信</option>
                    <option value="phone">电话</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">显示名称 <span class="required">*</span></label>
                <input type="text" name="label" class="form-input" placeholder="如：旺旺 / TG / 邮箱" required>
            </div>
            <div class="form-field">
                <label class="form-label">账号/内容 <span class="required">*</span></label>
                <input type="text" name="value" class="form-input" placeholder="具体账号或内容" required>
            </div>
            <div class="form-field">
                <label class="form-label">图标背景色</label>
                <input type="color" name="icon_bg" value="#999999" style="width:100%;height:36px;border:1px solid #d9d9d9;border-radius:8px;cursor:pointer;">
            </div>
        </div>
        <div class="form-grid" style="align-items:center;">
            <div class="form-field">
                <label class="form-label">排序（数字越小越靠前）</label>
                <input type="number" name="sort_order" class="form-input" value="0" min="0">
            </div>
            <div></div>
            <div></div>
            <button type="submit" class="btn btn-primary">添加</button>
        </div>
    </form>
</div>

<div class="section-card">
    <div class="section-title">已有联系方式（<?= count($contacts) ?> 条）</div>
    <?php if (empty($contacts)): ?>
    <p style="color:#999;font-size:13px;text-align:center;padding:20px 0;">暂无联系方式，请添加</p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>图标</th>
                    <th>类型</th>
                    <th>名称</th>
                    <th>账号/内容</th>
                    <th>背景色</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $c): ?>
                <?php
                    $typeClass = 'badge-other';
                    $typeIcons = ['ww'=>'淘','tg'=>'TG','email'=>'邮','wechat'=>'微','phone'=>'电','other'=>'其'];
                    $iconLabel = $typeIcons[$c['type']] ?? '其';
                ?>
                <tr>
                    <td>
                        <span class="badge badge-<?= htmlspecialchars($c['type']) ?>"
                              style="background:<?= htmlspecialchars($c['icon_bg']) ?>;color:#fff;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                            <?= htmlspecialchars($c['label']) ?>
                        </span>
                    </td>
                    <td><code style="font-size:12px;color:#666;"><?= htmlspecialchars($c['type']) ?></code></td>
                    <td style="font-weight:500;"><?= htmlspecialchars($c['label']) ?></td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($c['value']) ?></td>
                    <td>
                        <span class="color-dot" style="background:<?= htmlspecialchars($c['icon_bg']) ?>"></span>
                        <code style="font-size:11px;color:#999;"><?= htmlspecialchars($c['icon_bg']) ?></code>
                    </td>
                    <td><?= intval($c['sort_order']) ?></td>
                    <td>
                        <?php if ($c['is_active']): ?>
                            <span class="status-on">● 显示中</span>
                        <?php else: ?>
                            <span class="status-off">● 已隐藏</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="openEditModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['type']) ?>', '<?= htmlspecialchars($c['label']) ?>', '<?= htmlspecialchars($c['value']) ?>', '<?= htmlspecialchars($c['icon_bg']) ?>', <?= intval($c['sort_order']) ?>)">编辑</button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('确认删除？')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">删除</button>
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

<!-- 编辑弹窗 -->
<div id="editModal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);">
    <div style="background:#fff;border-radius:16px;padding:28px;width:440px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h3 style="font-size:16px;font-weight:600;margin-bottom:20px;color:#333;">编辑联系方式</h3>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editId">
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div class="form-field">
                    <label class="form-label">类型</label>
                    <select name="type" id="editType" class="form-select" required>
                        <option value="ww">旺旺</option>
                        <option value="tg">TG</option>
                        <option value="email">邮箱</option>
                        <option value="wechat">微信</option>
                        <option value="phone">电话</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label">显示名称</label>
                    <input type="text" name="label" id="editLabel" class="form-input" required>
                </div>
                <div class="form-field">
                    <label class="form-label">账号/内容</label>
                    <input type="text" name="value" id="editValue" class="form-input" required>
                </div>
                <div class="form-field">
                    <label class="form-label">图标背景色</label>
                    <input type="color" name="icon_bg" id="editIconBg" value="#999999" style="width:100%;height:36px;border:1px solid #d9d9d9;border-radius:8px;cursor:pointer;">
                </div>
                <div class="form-field">
                    <label class="form-label">排序</label>
                    <input type="number" name="sort_order" id="editSortOrder" class="form-input" value="0" min="0">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, type, label, value, iconBg, sortOrder) {
    document.getElementById('editId').value = id;
    document.getElementById('editType').value = type;
    document.getElementById('editLabel').value = label;
    document.getElementById('editValue').value = value;
    document.getElementById('editIconBg').value = iconBg;
    document.getElementById('editSortOrder').value = sortOrder;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
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
