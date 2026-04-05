<?php
$page_title = '仪表盘';
require_once __DIR__ . '/header.php';

if (isset($_GET['init'])) {
    try {
        init_database();
        $init_msg = '数据库初始化成功！';
    } catch (Exception $e) {
        $init_err = '初始化失败：' . $e->getMessage();
    }
}

try {
    $pdo = pdo_connect();
    init_database();

    $stat_cats = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $stat_navs = $pdo->query("SELECT COUNT(*) FROM nav_items")->fetchColumn();
    $stat_admins = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

    $recent = $pdo->query("
        SELECT n.id, n.title, n.subtitle, c.name AS cat_name, n.created_at
        FROM nav_items n
        JOIN categories c ON c.id = n.category_id
        ORDER BY n.created_at DESC LIMIT 5
    ")->fetchAll();

    $cat_counts = $pdo->query("
        SELECT c.name, COUNT(n.id) AS cnt
        FROM categories c
        LEFT JOIN nav_items n ON n.category_id = c.id
        GROUP BY c.id ORDER BY c.sort_order
    ")->fetchAll();

} catch (Exception $e) {
    $db_err = $e->getMessage();
}
?>
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    text-align: center;
}
.stat-num {
    font-size: 28px;
    font-weight: 700;
    color: #c9a227;
}
.stat-label {
    font-size: 13px;
    color: #6b6b6b;
    margin-top: 4px;
}
.init-btn {
    display: inline-block;
    margin-top: 16px;
    padding: 8px 16px;
    background: #c9a227;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}
.init-btn:hover { background: #b8922a; }
.section-title {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e8e8e8;
}
.table-wrap { overflow-x: auto; }
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
th {
    text-align: left;
    padding: 10px 12px;
    color: #6b6b6b;
    font-weight: 500;
    border-bottom: 1px solid #f0f0f0;
    white-space: nowrap;
}
td {
    padding: 10px 12px;
    border-bottom: 1px solid #f5f5f5;
    color: #333;
}
tr:last-child td { border-bottom: none; }
.cat-badge {
    display: inline-block;
    padding: 2px 8px;
    background: #f5f0e6;
    color: #c9a227;
    border-radius: 4px;
    font-size: 12px;
}
.tips {
    background: #f0f2f5;
    border-radius: 8px;
    padding: 16px;
    font-size: 13px;
    color: #6b6b6b;
    line-height: 1.8;
    margin-top: 24px;
}
.tips strong { color: #333; }
</style>

<?php if (isset($init_msg)): ?>
<div style="background:#e6f7e6;color:#2e8b57;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;"><?= htmlspecialchars($init_msg) ?></div>
<?php endif; ?>
<?php if (isset($init_err)): ?>
<div style="background:#fff2f2;color:#e03e3e;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;"><?= htmlspecialchars($init_err) ?></div>
<?php endif; ?>
<?php if (isset($db_err)): ?>
<div style="background:#fff2f2;color:#e03e3e;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    数据库连接失败：<?= htmlspecialchars($db_err) ?><br>
    请确保 MySQL 已运行，并确认 <code>config.php</code> 中的数据库配置正确。
    <a href="?init" style="color:#c9a227;margin-left:8px;">初始化数据库</a>
</div>
<?php return; endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-num"><?= $stat_navs ?></div>
        <div class="stat-label">导航总数</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $stat_cats ?></div>
        <div class="stat-label">分类数量</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $stat_admins ?></div>
        <div class="stat-label">管理员</div>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="section-title">各分类导航数量</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>分类</th><th>导航数量</th></tr></thead>
            <tbody>
            <?php foreach ($cat_counts as $row): ?>
                <tr>
                    <td><span class="cat-badge"><?= htmlspecialchars($row['name']) ?></span></td>
                    <td><?= $row['cnt'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="section-title">最近添加的导航</div>
    <?php if (empty($recent)): ?>
        <p style="color:#6b6b6b;font-size:13px;text-align:center;padding:20px;">暂无数据</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>标题</th><th>副标题</th><th>分类</th><th>添加时间</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['subtitle']) ?></td>
                    <td><span class="cat-badge"><?= htmlspecialchars($item['cat_name']) ?></span></td>
                    <td><?= $item['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="tips">
    <strong>使用提示：</strong><br>
    - 默认管理员账号：<strong>admin</strong>，密码：<strong>admin123</strong>（首次使用请及时修改密码）<br>
    - 前往 <a href="nav.php" style="color:#c9a227;">导航管理</a> 添加和管理导航项<br>
    - 导航项管理支持：标题、副标题、图标（URL 或本地上传）、跳转链接
</div>

</div><!-- end page-content -->
</div><!-- end main-content -->
</div><!-- end admin-layout -->
</body>
</html>
