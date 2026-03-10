<?php
require_once '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$pageTitle = 'Career Goals';
$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'error');
        header('Location: goals.php');
        exit();
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $goal_type   = $_POST['goal_type'] ?? 'Weekly';
        $description = sanitizeInput($_POST['goal_description'] ?? '');
        $target      = intval($_POST['target_value'] ?? 1);
        $current     = intval($_POST['current_value'] ?? 0);
        $unit        = $_POST['unit'] ?? 'Hours';
        $start_date  = $_POST['start_date'] ?: date('Y-m-d');
        $end_date    = $_POST['end_date'] ?: date('Y-m-d', strtotime('+7 days'));
        $status      = $current >= $target ? 'Completed' : ($_POST['status'] ?? 'Active');

        if (empty($description)) {
            setFlashMessage('Goal description is required.', 'error');
        } elseif ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO career_goals (user_id, goal_type, goal_description, target_value, current_value, unit, start_date, end_date, status) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issiissss", $user_id, $goal_type, $description, $target, $current, $unit, $start_date, $end_date, $status);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Goal created!', 'success');
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE career_goals SET goal_type=?, goal_description=?, target_value=?, current_value=?, unit=?, start_date=?, end_date=?, status=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssiiisssii", $goal_type, $description, $target, $current, $unit, $start_date, $end_date, $status, $id, $user_id);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Goal updated!', 'success');
        }
    } elseif ($action === 'quick_update') {
        $id = intval($_POST['id']);
        $add = intval($_POST['add_value'] ?? 0);
        // Get current and target
        $stmt = $conn->prepare("SELECT current_value, target_value FROM career_goals WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $g = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($g) {
            $new_val = min($g['current_value'] + $add, $g['target_value']);
            $new_status = ($new_val >= $g['target_value']) ? 'Completed' : 'Active';
            $stmt = $conn->prepare("UPDATE career_goals SET current_value=?, status=? WHERE id=? AND user_id=?");
            $stmt->bind_param("isii", $new_val, $new_status, $id, $user_id);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Progress updated!', 'success');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM career_goals WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute(); $stmt->close();
        setFlashMessage('Goal deleted.', 'success');
    }
    header('Location: goals.php');
    exit();
}

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as completed FROM career_goals WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Goals by type
$stmt = $conn->prepare("SELECT * FROM career_goals WHERE user_id=? ORDER BY FIELD(status,'Active','Completed','Failed'), goal_type, end_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);

// Group by type
$goals_by_type = ['Daily'=>[], 'Weekly'=>[], 'Monthly'=>[]];
foreach ($all_goals as $g) {
    $goals_by_type[$g['goal_type']][] = $g;
}

include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-bullseye"></i> Career Goals</h1>
            <p class="subtitle">Set and track daily, weekly, and monthly learning targets</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addGoalModal')"><i class="fas fa-plus"></i> Add Goal</button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:30px">
        <div class="stat-card">
            <div class="stat-icon" style="background:#6366f1"><i class="fas fa-bullseye"></i></div>
            <div class="stat-content"><h3>Total Goals</h3><p class="stat-value"><?php echo $stats['total']??0; ?></p><p class="stat-label">Goals set</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f59e0b"><i class="fas fa-fire"></i></div>
            <div class="stat-content"><h3>Active</h3><p class="stat-value"><?php echo $stats['active']??0; ?></p><p class="stat-label">In progress</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#10b981"><i class="fas fa-medal"></i></div>
            <div class="stat-content">
                <h3>Completed</h3>
                <p class="stat-value"><?php echo $stats['completed']??0; ?></p>
                <p class="stat-label"><?php echo $stats['total']>0 ? round($stats['completed']/$stats['total']*100).'% rate' : '—'; ?></p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="goal-tabs">
        <button class="tab-btn active" onclick="showTab('daily')"><i class="fas fa-sun"></i> Daily</button>
        <button class="tab-btn" onclick="showTab('weekly')"><i class="fas fa-calendar-week"></i> Weekly</button>
        <button class="tab-btn" onclick="showTab('monthly')"><i class="fas fa-calendar-alt"></i> Monthly</button>
    </div>

    <?php foreach (['Daily','Weekly','Monthly'] as $type): ?>
    <div id="tab-<?php echo strtolower($type); ?>" class="tab-content <?php echo $type==='Daily'?'active':''; ?>">
        <?php if (empty($goals_by_type[$type])): ?>
        <div class="empty-state" style="padding:50px 20px">
            <i class="fas fa-bullseye"></i>
            <h3>No <?php echo strtolower($type); ?> goals yet</h3>
            <button class="btn btn-primary" onclick="openAddGoalWith('<?php echo $type; ?>')"><i class="fas fa-plus"></i> Add <?php echo $type; ?> Goal</button>
        </div>
        <?php else: ?>
        <div class="goals-list">
            <?php foreach ($goals_by_type[$type] as $g):
                $pct = $g['target_value'] > 0 ? min(100, round($g['current_value']/$g['target_value']*100)) : 0;
                $sc_map = ['Active'=>'#6366f1','Completed'=>'#10b981','Failed'=>'#ef4444'];
                $sc = $sc_map[$g['status']] ?? '#94a3b8';
                $days_left = floor((strtotime($g['end_date']) - time()) / 86400);
            ?>
            <div class="goal-card <?php echo $g['status']==='Completed'?'goal-done':''; ?>">
                <div class="goal-top">
                    <div class="goal-info">
                        <span class="goal-desc"><?php echo htmlspecialchars($g['goal_description']); ?></span>
                        <div class="goal-meta">
                            <span class="goal-unit-badge"><?php echo $g['unit']; ?></span>
                            <span class="status-badge sm" style="background:<?php echo $sc;?>20;color:<?php echo $sc;?>"><?php echo $g['status']; ?></span>
                            <?php if ($g['status']==='Active' && $days_left >= 0): ?>
                            <span class="days-left"><?php echo $days_left; ?>d left</span>
                            <?php elseif ($g['status']==='Active' && $days_left < 0): ?>
                            <span class="days-left overdue">Overdue</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="goal-value">
                        <span class="current-val"><?php echo $g['current_value']; ?></span>
                        <span class="target-val">/ <?php echo $g['target_value']; ?></span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress-bar-bg" style="margin:10px 0 6px">
                    <div class="progress-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $sc; ?>"></div>
                </div>
                <span style="font-size:12px;color:var(--text-secondary)"><?php echo $pct; ?>% complete</span>

                <div class="goal-actions">
                    <?php if ($g['status'] === 'Active'): ?>
                    <form method="POST" style="display:flex;align-items:center;gap:6px">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="quick_update">
                        <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                        <input type="number" name="add_value" min="1" value="1" class="quick-input">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Add Progress</button>
                    </form>
                    <?php endif; ?>
                    <button onclick='editGoal(<?php echo htmlspecialchars(json_encode($g)); ?>)' class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this goal?')">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Goal Modal -->
<div id="addGoalModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('addGoalModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-bullseye"></i> Add Goal</h2><button onclick="closeModal('addGoalModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label>Goal Description *</label><input type="text" name="goal_description" required placeholder="e.g., Study 8 hours this week"></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Type</label>
                        <select name="goal_type" id="add_goal_type" onchange="updateDates(this.value)">
                            <option>Daily</option><option selected>Weekly</option><option>Monthly</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Unit</label>
                        <select name="unit"><option>Hours</option><option>Topics</option><option>Courses</option><option>Projects</option></select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Target Value *</label><input type="number" name="target_value" min="1" value="8" required></div>
                    <div class="form-group"><label>Current Value</label><input type="number" name="current_value" min="0" value="0"></div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Start Date</label><input type="date" name="start_date" id="add_start_date" value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="form-group"><label>End Date</label><input type="date" name="end_date" id="add_end_date" value="<?php echo date('Y-m-d', strtotime('+6 days')); ?>"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addGoalModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Goal</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Goal Modal -->
<div id="editGoalModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('editGoalModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-edit"></i> Edit Goal</h2><button onclick="closeModal('editGoalModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="eg_id">
            <div class="modal-body">
                <div class="form-group"><label>Goal Description *</label><input type="text" name="goal_description" id="eg_desc" required></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Type</label>
                        <select name="goal_type" id="eg_type"><option>Daily</option><option>Weekly</option><option>Monthly</option></select>
                    </div>
                    <div class="form-group"><label>Unit</label>
                        <select name="unit" id="eg_unit"><option>Hours</option><option>Topics</option><option>Courses</option><option>Projects</option></select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Target</label><input type="number" name="target_value" id="eg_target" min="1"></div>
                    <div class="form-group"><label>Current</label><input type="number" name="current_value" id="eg_current" min="0"></div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Status</label>
                        <select name="status" id="eg_status"><option>Active</option><option>Completed</option><option>Failed</option></select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Start Date</label><input type="date" name="start_date" id="eg_start"></div>
                    <div class="form-group"><label>End Date</label><input type="date" name="end_date" id="eg_end"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editGoalModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Goal</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px}
.page-header h1{margin:0;font-size:26px;display:flex;align-items:center;gap:10px}
.page-header .subtitle{margin:5px 0 0;color:var(--text-secondary)}
.goal-tabs{display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:10px;margin-bottom:20px;width:fit-content}
.tab-btn{padding:8px 22px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;background:transparent;color:var(--text-secondary);transition:all .2s;display:flex;align-items:center;gap:6px}
.tab-btn.active{background:white;color:var(--primary-color);box-shadow:0 1px 4px rgba(0,0,0,0.1)}
.tab-content{display:none}
.tab-content.active{display:block}
.goals-list{display:flex;flex-direction:column;gap:14px}
.goal-card{background:white;border-radius:12px;padding:18px;box-shadow:var(--shadow);transition:all .2s}
.goal-card:hover{box-shadow:var(--shadow-lg)}
.goal-card.goal-done{opacity:.75}
.goal-top{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.goal-info{flex:1}
.goal-desc{font-size:16px;font-weight:600;display:block;margin-bottom:6px}
.goal-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.goal-unit-badge{font-size:11px;padding:2px 8px;border-radius:10px;background:#f1f5f9;color:#64748b;font-weight:600}
.days-left{font-size:12px;color:#64748b;font-weight:600}
.days-left.overdue{color:#ef4444}
.goal-value{text-align:right;white-space:nowrap}
.current-val{font-size:28px;font-weight:700;color:var(--primary-color)}
.target-val{font-size:14px;color:var(--text-secondary)}
.goal-actions{display:flex;gap:8px;align-items:center;margin-top:12px;flex-wrap:wrap}
.quick-input{width:65px;padding:5px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.btn-success{background:#10b981;color:white;border:none}
.btn-success:hover{background:#059669}
.progress-bar-bg{background:#f1f5f9;border-radius:6px;height:8px;overflow:hidden}
.progress-bar-fill{height:100%;border-radius:6px;transition:width .4s}
.status-badge.sm{font-size:11px;padding:2px 8px;border-radius:10px;font-weight:600}
.empty-state{text-align:center;color:var(--text-secondary)}
.empty-state i{font-size:50px;opacity:.2;margin-bottom:15px;display:block}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
</style>

<script>
function openModal(id){document.getElementById(id).style.display='flex'}
function closeModal(id){document.getElementById(id).style.display='none'}
function showTab(t){
    document.querySelectorAll('.tab-content').forEach(el=>el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el=>el.classList.remove('active'));
    document.getElementById('tab-'+t).classList.add('active');
    event.currentTarget.classList.add('active');
}
function openAddGoalWith(type){
    setSelect('add_goal_type',type);
    updateDates(type);
    openModal('addGoalModal');
}
function updateDates(type){
    const today=new Date();
    const start=document.getElementById('add_start_date');
    const end=document.getElementById('add_end_date');
    start.value=today.toISOString().split('T')[0];
    let endD=new Date(today);
    if(type==='Daily') endD.setDate(endD.getDate());
    else if(type==='Weekly') endD.setDate(endD.getDate()+6);
    else endD.setMonth(endD.getMonth()+1);
    end.value=endD.toISOString().split('T')[0];
}
function editGoal(g){
    document.getElementById('eg_id').value=g.id;
    document.getElementById('eg_desc').value=g.goal_description;
    document.getElementById('eg_target').value=g.target_value;
    document.getElementById('eg_current').value=g.current_value;
    document.getElementById('eg_start').value=g.start_date;
    document.getElementById('eg_end').value=g.end_date;
    setSelect('eg_type',g.goal_type);
    setSelect('eg_unit',g.unit);
    setSelect('eg_status',g.status);
    openModal('editGoalModal');
}
function setSelect(id,val){const s=document.getElementById(id);for(let o of s.options)if(o.value==val)o.selected=true;}
</script>

<?php include '../../includes/footer.php'; ?>
