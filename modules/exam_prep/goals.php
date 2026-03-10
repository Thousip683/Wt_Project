<?php
require_once '../../config/config.php';
requireLogin();

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if (!$exam_id) {
    header("Location: my_exams.php");
    exit();
}

$conn = getDBConnection();

// Verify exam belongs to user
$stmt = $conn->prepare("SELECT * FROM competitive_exams WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    header("Location: my_exams.php");
    exit();
}

$pageTitle = 'Goals - ' . $exam['exam_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $goal_type = sanitizeInput($_POST['goal_type']);
            $target_description = sanitizeInput($_POST['target_description']);
            $target_value = (int)$_POST['target_value'];
            $current_value = (int)$_POST['current_value'];
            $start_date = sanitizeInput($_POST['start_date']);
            $end_date = sanitizeInput($_POST['end_date']);
            $status = sanitizeInput($_POST['status']);
            
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO exam_goals (user_id, exam_id, goal_type, target_description, target_value, current_value, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissiisss", $_SESSION['user_id'], $exam_id, $goal_type, $target_description, $target_value, $current_value, $start_date, $end_date, $status);
                $message = "Goal created!";
            } else {
                $goal_id = (int)$_POST['goal_id'];
                $stmt = $conn->prepare("UPDATE exam_goals SET goal_type = ?, target_description = ?, target_value = ?, current_value = ?, start_date = ?, end_date = ?, status = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ssiisssii", $goal_type, $target_description, $target_value, $current_value, $start_date, $end_date, $status, $goal_id, $_SESSION['user_id']);
                $message = "Goal updated!";
            }
            
            if ($stmt->execute()) {
                setFlashMessage($message, "success");
            } else {
                setFlashMessage("Failed to save goal.", "error");
            }
            $stmt->close();
            header("Location: goals.php?exam_id=" . $exam_id);
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $goal_id = (int)$_POST['goal_id'];
            $stmt = $conn->prepare("DELETE FROM exam_goals WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $goal_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Goal deleted!", "success");
            header("Location: goals.php?exam_id=" . $exam_id);
            exit();
        }
        
        if ($_POST['action'] == 'update_progress') {
            $goal_id = (int)$_POST['goal_id'];
            $current_value = (int)$_POST['current_value'];
            
            // Fetch target value to auto-update status
            $stmt = $conn->prepare("SELECT target_value FROM exam_goals WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $goal_id, $_SESSION['user_id']);
            $stmt->execute();
            $goal = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($goal) {
                $status = ($current_value >= $goal['target_value']) ? 'Completed' : 'In Progress';
                
                $stmt = $conn->prepare("UPDATE exam_goals SET current_value = ?, status = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("isii", $current_value, $status, $goal_id, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            header("Location: goals.php?exam_id=" . $exam_id);
            exit();
        }
    }
}

// Fetch goals grouped by type
$stmt = $conn->prepare("SELECT * FROM exam_goals WHERE exam_id = ? ORDER BY end_date ASC");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$all_goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group goals by type
$goals_by_type = [
    'Daily' => [],
    'Weekly' => [],
    'Monthly' => []
];

foreach ($all_goals as $goal) {
    $goals_by_type[$goal['goal_type']][] = $goal;
}

// Calculate statistics
$active_goals = count(array_filter($all_goals, fn($g) => $g['status'] === 'In Progress'));
$completed_goals = count(array_filter($all_goals, fn($g) => $g['status'] === 'Completed'));
$total_goals = count($all_goals);

closeDBConnection($conn);

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="my_exams.php"><i class="fas fa-trophy"></i> My Exams</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($exam['exam_name']); ?> - Goals</span>
    </div>

    <div class="page-header">
        <div>
            <h1><i class="fas fa-bullseye"></i> Study Goals</h1>
            <p>Set and track your preparation targets</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Goal
        </button>
    </div>

    <!-- Statistics -->
    <div class="stats-overview">
        <div class="stat-box">
            <i class="fas fa-list-check"></i>
            <div>
                <span class="stat-number"><?php echo $total_goals; ?></span>
                <span class="stat-label">Total Goals</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-spinner"></i>
            <div>
                <span class="stat-number"><?php echo $active_goals; ?></span>
                <span class="stat-label">Active</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-check-circle"></i>
            <div>
                <span class="stat-number"><?php echo $completed_goals; ?></span>
                <span class="stat-label">Completed</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-percentage"></i>
            <div>
                <span class="stat-number"><?php echo $total_goals > 0 ? round(($completed_goals / $total_goals) * 100) : 0; ?>%</span>
                <span class="stat-label">Completion Rate</span>
            </div>
        </div>
    </div>

    <?php if (empty($all_goals)): ?>
    <div class="empty-state">
        <i class="fas fa-bullseye"></i>
        <h3>No Goals Set</h3>
        <p>Create daily, weekly, or monthly targets to stay on track</p>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Create First Goal
        </button>
    </div>
    <?php else: ?>
    <!-- Goals Tabs -->
    <div class="goals-tabs">
        <button class="tab-btn active" onclick="switchTab('daily')">
            <i class="fas fa-sun"></i> Daily
            <?php if (count($goals_by_type['Daily']) > 0): ?>
            <span class="tab-badge"><?php echo count($goals_by_type['Daily']); ?></span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('weekly')">
            <i class="fas fa-calendar-week"></i> Weekly
            <?php if (count($goals_by_type['Weekly']) > 0): ?>
            <span class="tab-badge"><?php echo count($goals_by_type['Weekly']); ?></span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('monthly')">
            <i class="fas fa-calendar-alt"></i> Monthly
            <?php if (count($goals_by_type['Monthly']) > 0): ?>
            <span class="tab-badge"><?php echo count($goals_by_type['Monthly']); ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Daily Goals -->
    <div id="daily-tab" class="tab-content active">
        <?php if (empty($goals_by_type['Daily'])): ?>
        <div class="empty-tab">
            <p>No daily goals set</p>
        </div>
        <?php else: ?>
        <div class="goals-grid">
            <?php foreach ($goals_by_type['Daily'] as $goal): 
                $progress = $goal['target_value'] > 0 ? min(($goal['current_value'] / $goal['target_value']) * 100, 100) : 0;
            ?>
            <div class="goal-card status-<?php echo strtolower(str_replace(' ', '-', $goal['status'])); ?>">
                <div class="goal-header">
                    <h3><?php echo htmlspecialchars($goal['target_description']); ?></h3>
                    <div class="goal-actions">
                        <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($goal); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-danger" onclick="deleteGoal(<?php echo $goal['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="goal-progress-section">
                    <div class="goal-values">
                        <span class="current-value"><?php echo $goal['current_value']; ?></span>
                        <span class="separator">/</span>
                        <span class="target-value"><?php echo $goal['target_value']; ?></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <span class="progress-percentage"><?php echo round($progress); ?>%</span>
                </div>
                
                <div class="goal-dates">
                    <span><i class="fas fa-calendar-day"></i> <?php echo date('d M', strtotime($goal['start_date'])); ?> - <?php echo date('d M Y', strtotime($goal['end_date'])); ?></span>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $goal['status'])); ?>">
                        <?php echo $goal['status']; ?>
                    </span>
                </div>
                
                <?php if ($goal['status'] !== 'Completed'): ?>
                <div class="quick-update">
                    <form method="POST" action="" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="update_progress">
                        <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                        <input type="number" name="current_value" value="<?php echo $goal['current_value']; ?>" min="0" max="<?php echo $goal['target_value']; ?>">
                        <button type="submit" class="btn-sm btn-primary">Update</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Weekly Goals -->
    <div id="weekly-tab" class="tab-content">
        <?php if (empty($goals_by_type['Weekly'])): ?>
        <div class="empty-tab">
            <p>No weekly goals set</p>
        </div>
        <?php else: ?>
        <div class="goals-grid">
            <?php foreach ($goals_by_type['Weekly'] as $goal): 
                $progress = $goal['target_value'] > 0 ? min(($goal['current_value'] / $goal['target_value']) * 100, 100) : 0;
            ?>
            <div class="goal-card status-<?php echo strtolower(str_replace(' ', '-', $goal['status'])); ?>">
                <div class="goal-header">
                    <h3><?php echo htmlspecialchars($goal['target_description']); ?></h3>
                    <div class="goal-actions">
                        <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($goal); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-danger" onclick="deleteGoal(<?php echo $goal['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="goal-progress-section">
                    <div class="goal-values">
                        <span class="current-value"><?php echo $goal['current_value']; ?></span>
                        <span class="separator">/</span>
                        <span class="target-value"><?php echo $goal['target_value']; ?></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <span class="progress-percentage"><?php echo round($progress); ?>%</span>
                </div>
                
                <div class="goal-dates">
                    <span><i class="fas fa-calendar-week"></i> <?php echo date('d M', strtotime($goal['start_date'])); ?> - <?php echo date('d M Y', strtotime($goal['end_date'])); ?></span>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $goal['status'])); ?>">
                        <?php echo $goal['status']; ?>
                    </span>
                </div>
                
                <?php if ($goal['status'] !== 'Completed'): ?>
                <div class="quick-update">
                    <form method="POST" action="" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="update_progress">
                        <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                        <input type="number" name="current_value" value="<?php echo $goal['current_value']; ?>" min="0" max="<?php echo $goal['target_value']; ?>">
                        <button type="submit" class="btn-sm btn-primary">Update</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Goals -->
    <div id="monthly-tab" class="tab-content">
        <?php if (empty($goals_by_type['Monthly'])): ?>
        <div class="empty-tab">
            <p>No monthly goals set</p>
        </div>
        <?php else: ?>
        <div class="goals-grid">
            <?php foreach ($goals_by_type['Monthly'] as $goal): 
                $progress = $goal['target_value'] > 0 ? min(($goal['current_value'] / $goal['target_value']) * 100, 100) : 0;
            ?>
            <div class="goal-card status-<?php echo strtolower(str_replace(' ', '-', $goal['status'])); ?>">
                <div class="goal-header">
                    <h3><?php echo htmlspecialchars($goal['target_description']); ?></h3>
                    <div class="goal-actions">
                        <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($goal); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-danger" onclick="deleteGoal(<?php echo $goal['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="goal-progress-section">
                    <div class="goal-values">
                        <span class="current-value"><?php echo $goal['current_value']; ?></span>
                        <span class="separator">/</span>
                        <span class="target-value"><?php echo $goal['target_value']; ?></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <span class="progress-percentage"><?php echo round($progress); ?>%</span>
                </div>
                
                <div class="goal-dates">
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M', strtotime($goal['start_date'])); ?> - <?php echo date('d M Y', strtotime($goal['end_date'])); ?></span>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $goal['status'])); ?>">
                        <?php echo $goal['status']; ?>
                    </span>
                </div>
                
                <?php if ($goal['status'] !== 'Completed'): ?>
                <div class="quick-update">
                    <form method="POST" action="" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="update_progress">
                        <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                        <input type="number" name="current_value" value="<?php echo $goal['current_value']; ?>" min="0" max="<?php echo $goal['target_value']; ?>">
                        <button type="submit" class="btn-sm btn-primary">Update</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Goal Modal -->
<div id="goalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create Goal</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="goalForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="goal_id" id="goalId">
            
            <div class="form-group">
                <label for="goal_type">Goal Type *</label>
                <select id="goal_type" name="goal_type" required>
                    <option value="Daily">Daily</option>
                    <option value="Weekly" selected>Weekly</option>
                    <option value="Monthly">Monthly</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="target_description">Goal Description *</label>
                <input type="text" id="target_description" name="target_description" placeholder="E.g., Complete 5 topics, Study 20 hours" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="target_value">Target Value *</label>
                    <input type="number" id="target_value" name="target_value" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="current_value">Current Progress</label>
                    <input type="number" id="current_value" name="current_value" value="0" min="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date *</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="Not Started">Not Started</option>
                    <option value="In Progress" selected>In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Goal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="goal_id" id="deleteGoalId">
</form>

<style>
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-box i {
    font-size: 32px;
    color: var(--primary-color);
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
}

.stat-label {
    display: block;
    font-size: 13px;
    color: var(--text-secondary);
}

.goals-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid var(--light-color);
}

.tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    color: var(--text-secondary);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: -2px;
}

.tab-btn:hover {
    color: var(--primary-color);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-badge {
    background: var(--primary-color);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.empty-tab {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
}

.goals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.goal-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary-color);
}

.goal-card.status-completed {
    border-left-color: var(--success-color);
}

.goal-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
}

.goal-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
}

.goal-progress-section {
    margin-bottom: 15px;
}

.goal-values {
    display: flex;
    align-items: baseline;
    justify-content: center;
    margin-bottom: 10px;
    gap: 5px;
}

.current-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary-color);
}

.separator {
    font-size: 20px;
    color: var(--text-secondary);
}

.target-value {
    font-size: 24px;
    font-weight: 600;
    color: var(--text-secondary);
}

.progress-bar-container {
    height: 8px;
    background: var(--light-color);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-bar {
    height: 100%;
    background: var(--primary-color);
    transition: width 0.3s;
}

.goal-card.status-completed .progress-bar {
    background: var(--success-color);
}

.progress-percentage {
    display: block;
    text-align: right;
    font-size: 13px;
    color: var(--text-secondary);
    font-weight: 600;
}

.goal-dates {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-top: 1px solid var(--light-color);
    font-size: 13px;
    color: var(--text-secondary);
}

.quick-update {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--light-color);
}

.inline-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.inline-form input[type="number"] {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}
</style>

<script>
function switchTab(type) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.tab-btn').classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(type + '-tab').classList.add('active');
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Create Goal';
    document.getElementById('formAction').value = 'add';
    document.getElementById('goalForm').reset();
    document.getElementById('goalId').value = '';
    document.getElementById('start_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('goalModal').classList.add('active');
}

function openEditModal(goal) {
    document.getElementById('modalTitle').textContent = 'Edit Goal';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('goalId').value = goal.id;
    document.getElementById('goal_type').value = goal.goal_type;
    document.getElementById('target_description').value = goal.target_description;
    document.getElementById('target_value').value = goal.target_value;
    document.getElementById('current_value').value = goal.current_value;
    document.getElementById('start_date').value = goal.start_date;
    document.getElementById('end_date').value = goal.end_date;
    document.getElementById('status').value = goal.status;
    document.getElementById('goalModal').classList.add('active');
}

function closeModal() {
    document.getElementById('goalModal').classList.remove('active');
}

function deleteGoal(id) {
    if (confirm('Are you sure you want to delete this goal?')) {
        document.getElementById('deleteGoalId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('goalModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Auto-calculate end date based on goal type
document.getElementById('goal_type').addEventListener('change', function() {
    const startDate = new Date(document.getElementById('start_date').value);
    let endDate = new Date(startDate);
    
    switch(this.value) {
        case 'Daily':
            endDate.setDate(endDate.getDate() + 1);
            break;
        case 'Weekly':
            endDate.setDate(endDate.getDate() + 7);
            break;
        case 'Monthly':
            endDate.setMonth(endDate.getMonth() + 1);
            break;
    }
    
    document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
});

document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('goal_type').dispatchEvent(new Event('change'));
});
</script>

<?php include '../../includes/footer.php'; ?>
