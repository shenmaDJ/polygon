<?php
// 开启会话
session_start();

// 引入数据库配置
require_once '../db_config.php';

// 检查登录状态（简单示例，实际应用中请使用更安全的认证方式）
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// 登录处理
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 这里使用简单的硬编码认证，实际应用中请使用数据库存储和加密密码
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $isLoggedIn = true;
    } else {
        $loginError = '用户名或密码错误';
    }
}

// 登出处理
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// 处理状态更新
if ($isLoggedIn && isset($_POST['update_status'])) {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 0;
    
    if ($id > 0) {
        $conn = getDbConnection();
        if ($conn) {
            $stmt = $conn->prepare("UPDATE permits SET status = ? WHERE id = ?");
            $stmt->bind_param("ii", $status, $id);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
    }
}

// 获取数据列表（仅当已登录时）
$permitList = [];
if ($isLoggedIn) {
    $conn = getDbConnection();
    if ($conn) {
        // 分页参数
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // 获取总记录数
        $result = $conn->query("SELECT COUNT(*) as total FROM permits");
        $totalRecords = $result->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $perPage);
        
        // 获取当前页数据
        $result = $conn->query("SELECT * FROM permits ORDER BY created_at DESC LIMIT $offset, $perPage");
        
        while ($row = $result->fetch_assoc()) {
            $permitList[] = $row;
        }
        
        $conn->close();
    }
}

// 状态文本映射
$statusText = [
    '0' => '待处理',
    '1' => '已处理',
    '-1' => '失败'
];

// 状态样式映射
$statusClass = [
    '0' => 'text-warning',
    '1' => 'text-success',
    '-1' => 'text-danger'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>签名数据管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .login-box { max-width: 400px; margin: 100px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .table-responsive { margin-top: 20px; }
        .address-cell { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pagination { margin-top: 20px; }
        .action-buttons { display: flex; gap: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$isLoggedIn): ?>
            <!-- 登录表单 -->
            <div class="login-box">
                <h3 class="text-center mb-4">管理员登录</h3>
                
                <?php if (isset($loginError)): ?>
                    <div class="alert alert-danger"><?php echo $loginError; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">登录</button>
                </form>
            </div>
        <?php else: ?>
            <!-- 管理界面 -->
            <div class="d-flex justify-content-between align-items-center">
                <h2>签名数据管理</h2>
                <a href="?logout=1" class="btn btn-outline-danger">退出登录</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Owner地址</th>
                            <th>Spender地址</th>
                            <th>金额</th>
                            <th>通证类型</th>
                            <th>备注</th>
                            <th>创建时间</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($permitList)): ?>
                            <tr>
                                <td colspan="9" class="text-center">暂无数据</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($permitList as $permit): ?>
                                <tr>
                                    <td><?php echo $permit['id']; ?></td>
                                    <td class="address-cell" title="<?php echo $permit['owner']; ?>"><?php echo $permit['owner']; ?></td>
                                    <td class="address-cell" title="<?php echo $permit['spender']; ?>"><?php echo $permit['spender']; ?></td>
                                    <td><?php echo $permit['amount']; ?></td>
                                    <td><?php echo $permit['token_type']; ?></td>
                                    <td><?php echo $permit['remark'] ? substr($permit['remark'], 0, 20) . (strlen($permit['remark']) > 20 ? '...' : '') : '无'; ?></td>
                                    <td><?php echo $permit['created_at']; ?></td>
                                    <td class="<?php echo $statusClass[$permit['status']]; ?>">
                                        <?php echo $statusText[$permit['status']]; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="detail.php?id=<?php echo $permit['id']; ?>" class="btn btn-sm btn-info">详情</a>
                                            <form method="post" action="" class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $permit['id']; ?>">
                                                <input type="hidden" name="status" value="1">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-success">标记处理</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 分页 -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 