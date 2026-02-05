<?php
// 开启会话
session_start();

// 引入数据库配置
require_once '../db_config.php';

// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// 获取ID参数
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// 获取数据详情
$permitData = null;
$conn = getDbConnection();
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM permits WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $permitData = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
}

// 状态文本映射
$statusText = [
    '0' => '待处理',
    '1' => '已处理',
    '-1' => '失败'
];

// 状态样式映射
$statusClass = [
    '0' => 'warning',
    '1' => 'success',
    '-1' => 'danger'
];

// 处理状态更新
if (isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? 0;
    
    $conn = getDbConnection();
    if ($conn) {
        $stmt = $conn->prepare("UPDATE permits SET status = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        
        // 更新当前数据
        if ($permitData) {
            $permitData['status'] = $status;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>签名数据详情 - 管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .card { margin-top: 20px; }
        .copy-btn { cursor: pointer; }
        .token-badge { font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>签名数据详情</h2>
            <a href="index.php" class="btn btn-outline-primary">返回列表</a>
        </div>
        
        <?php if (!$permitData): ?>
            <div class="alert alert-danger">数据不存在或已被删除</div>
        <?php else: ?>
            <div class="row">
                <!-- 基本信息 -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">基本信息</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>ID:</strong> <?php echo $permitData['id']; ?></p>
                            <p>
                                <strong>状态:</strong> 
                                <span class="badge bg-<?php echo $statusClass[$permitData['status']]; ?>">
                                    <?php echo $statusText[$permitData['status']]; ?>
                                </span>
                            </p>
                            <p>
                                <strong>通证类型:</strong> 
                                <span class="badge bg-info token-badge">
                                    <?php echo $permitData['token_type']; ?>
                                </span>
                            </p>
                            <p><strong>金额:</strong> <?php echo $permitData['amount']; ?></p>
                            <p><strong>创建时间:</strong> <?php echo $permitData['created_at']; ?></p>
                            <p><strong>用户IP:</strong> <?php echo $permitData['user_ip']; ?></p>
                            
                            <!-- 备注信息 -->
                            <div class="mt-3">
                                <strong>备注信息:</strong>
                                <p class="text-muted"><?php echo $permitData['remark'] ?: '无'; ?></p>
                            </div>
                            
                            <!-- 状态更新 -->
                            <div class="mt-4">
                                <form method="post" action="">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">更新状态</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="0" <?php echo $permitData['status'] == 0 ? 'selected' : ''; ?>>待处理</option>
                                            <option value="1" <?php echo $permitData['status'] == 1 ? 'selected' : ''; ?>>已处理</option>
                                            <option value="-1" <?php echo $permitData['status'] == -1 ? 'selected' : ''; ?>>失败</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary">更新状态</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 签名详情 -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">签名详情</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><strong>Owner 地址:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $permitData['owner']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $permitData['owner']; ?>">复制</button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Spender 地址:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $permitData['spender']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $permitData['spender']; ?>">复制</button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Value:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $permitData['value']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $permitData['value']; ?>">复制</button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Deadline:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $permitData['deadline']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $permitData['deadline']; ?>">复制</button>
                                </div>
                                <small class="text-muted">时间戳: <?php echo date('Y-m-d H:i:s', $permitData['deadline']); ?></small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>v:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $permitData['v']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $permitData['v']; ?>">复制</button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>r:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $permitData['r']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $permitData['r']; ?>">复制</button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>s:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $permitData['s']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $permitData['s']; ?>">复制</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 复制功能
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', () => {
                const textToCopy = button.getAttribute('data-copy');
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = button.textContent;
                    button.textContent = '已复制!';
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-success');
                    
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-secondary');
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html> 