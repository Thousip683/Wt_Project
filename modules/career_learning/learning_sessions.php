<?php
require_once '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);
$conn = getDBConnection();

// If course_id given, verify it
$course = null;
if ($course_id) {
    $stmt = $conn->prepare("SELECT id, course_name, color FROM courses WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $course_id, $user_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$course) { $course_id = 0; }
}

$pageTitle = 'Learning Sessions';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'error');
        header("Location: learning_sessions.php" . ($course_id ? "?course_id=$course_id" : ''));
        exit();
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $cid = intval($_POST['course_id_form'] ?? 0);
        $topic_id = intval($_POST['topic_id'] ?? 0) ?: null;
        $session_date = $_POST['session_date'] ?: date('Y-m-d');
        $duration = intval($_POST['duration_minutes'] ?? 0);
        $topics_covered = sanitizeInput($_POST['topics_covered'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');
        $productivity = $_POST['productivity_rating'] ?? 'Medium';

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO learning_sessions (user_id, course_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iiisisss", $user_id, $cid, $topic_id, $session_date, $duration, $topics_covered, $notes, $productivity);
            $stmt->execute(); $stmt->close();
            // Update hours_completed on course
            $stmt = $conn->prepare("UPDATE courses SET hours_completed = (SELECT COALESCE(SUM(duration_minutes),0)/60 FROM learning_sessions WHERE course_id=?) WHERE id=? AND user_id=?");
            $stmt->bind_param("iii", $cid, $cid, $user_id);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Session logged!', 'success');
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE learning_sessions SET course_id=?, topic_id=?, session_date=?, duration_minutes=?, topics_covered=?, notes=?, productivity_rating=? WHERE id=? AND user_id=?");
            $stmt->bind_param("iisisssii", $cid, $topic_id, $session_date, $duration, $topics_covered, $notes, $productivity, $id, $user_id);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Session updated!', 'success');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM learning_sessions WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute(); $stmt->close();
        setFlashMessage('Session deleted.', 'success');
    }
    header("Location: learning_sessions.php" . ($course_id ? "?course_id=$course_id" : ''));
    exit();
}

// Stats
$where = $course_id ? "AND ls.course_id=$course_id" : '';
$stmt = $conn->prepare("SELECT COUNT(*) as total_sessions, COALESCE(SUM(ls.duration_minutes),0) as total_mins, COALESCE(SUM(CASE WHEN ls.session_date >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) THEN ls.duration_minutes ELSE 0 END),0) as week_mins FROM learning_sessions ls WHERE ls.user_id=? $where");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Sessions list
$stmt = $conn->prepare("SELECT ls.*, c.course_name, c.color, ct.topic_name FROM learning_sessions ls JOIN courses c ON ls.course_id=c.id LEFT JOIN course_topics ct ON ls.topic_id=ct.id WHERE ls.user_id=? $where ORDER BY ls.session_date DESC, ls.id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// All courses for form
$stmt = $conn->prepare("SELECT id, course_name FROM courses WHERE user_id=? ORDER BY course_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);
include '../../includes/header.php';
?>

<div class="container">
    <?php if ($course): ?>
    <div class="breadcrumb">
        <a href="my_courses.php"><i class="fas fa-laptop-code"></i> My Courses</a>
        <i class="fas fa-chevron-right"></i>
        <a href="my_courses.php"><?php echo htmlspecialchars($course['course_name']); ?></a>
        <i class="fas fa-chevron-right"></i>
        <span>Learning Sessions</span>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1><i class="fas fa-clock"></i> Learning Sessions</h1>
            <p class="subtitle">Track your daily learning time</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addSessionModal')"><i class="fas fa-plus"></i> Log Session</button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:30px">
        <div class="stat-card">
            <div class="stat-icon" style="background:#6366f1"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-content"><h3>Total Sessions</h3><p class="stat-value"><?php echo $stats['total_sessions']; ?></p><p class="stat-label">Logged sessions</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#10b981"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-content"><h3>Total Time</h3><p class="stat-value"><?php echo number_format($stats['total_mins']/60,1); ?>h</p><p class="stat-label">Total hours learned</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f59e0b"><i class="fas fa-fire"></i></div>
            <div class="stat-content"><h3>This Week</h3><p class="stat-value"><?php echo number_format($stats['week_mins']/60,1); ?>h</p><p class="stat-label">Hours this week</p></div>
        </div>
    </div>

    <!-- Sessions -->
    <?php if (empty($sessions)): ?>
    <div class="empty-state">
        <i class="fas fa-clock"></i><h3>No sessions logged yet</h3>
        <p>Start tracking your learning time by logging a session!</p>
        <button class="btn btn-primary" onclick="openModal('addSessionModal')"><i class="fas fa-plus"></i> Log First Session</button>
    </div>
    <?php else: ?>
    <div class="sessions-list">
        <?php foreach ($sessions as $s):
            $pcols = ['High'=>'#10b981','Medium'=>'#f59e0b','Low'=>'#ef4444'];
            $pc = $pcols[$s['productivity_rating']] ?? '#94a3b8';
        ?>
        <div class="session-card" style="border-left:4px solid <?php echo $pc; ?>">
            <div class="session-left">
                <div class="session-date"><?php echo date('d M', strtotime($s['session_date'])); ?></div>
                <div class="session-day"><?php echo date('D', strtotime($s['session_date'])); ?></div>
            </div>
            <div class="session-body">
                <div class="session-top">
                    <span class="session-course" style="border-color:<?php echo $s['color']; ?>"><?php echo htmlspecialchars($s['course_name']); ?></span>
                    <span class="productivity-badge" style="background:<?php echo $pc;?>20;color:<?php echo $pc;?>"><?php echo $s['productivity_rating']; ?></span>
                </div>
                <?php if ($s['topic_name']): ?><p class="session-topic"><i class="fas fa-bookmark"></i> <?php echo htmlspecialchars($s['topic_name']); ?></p><?php endif; ?>
                <?php if ($s['topics_covered']): ?><p class="session-covered"><?php echo htmlspecialchars($s['topics_covered']); ?></p><?php endif; ?>
                <?php if ($s['notes']): ?><p class="session-notes"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($s['notes']); ?></p><?php endif; ?>
            </div>
            <div class="session-right">
                <div class="session-duration"><?php echo number_format($s['duration_minutes']/60,1); ?>h</div>
                <div class="session-min"><?php echo $s['duration_minutes']; ?> min</div>
                <div class="session-acts">
                    <button onclick='editSession(<?php echo htmlspecialchars(json_encode($s)); ?>)' class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Session Modal -->
<div id="addSessionModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('addSessionModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-plus-circle"></i> Log Learning Session</h2><button onclick="closeModal('addSessionModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group"><label>Date</label><input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="form-group"><label>Duration (minutes) *</label><input type="number" name="duration_minutes" min="1" value="60" required></div>
                </div>
                <div class="form-group"><label>Course *</label>
                    <select name="course_id_form" required>
                        <option value="">Select a course</option>
                        <?php foreach ($all_courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['course_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Topics Covered</label><input type="text" name="topics_covered" placeholder="e.g., Logistic Regression, Gradient Descent"></div>
                <div class="form-group"><label>Productivity Rating</label>
                    <select name="productivity_rating">
                        <option value="High">High 🔥</option><option value="Medium" selected>Medium ⚡</option><option value="Low">Low 😴</option>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" rows="2" placeholder="Any thoughts or takeaways..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addSessionModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Log Session</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Session Modal -->
<div id="editSessionModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('editSessionModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-edit"></i> Edit Session</h2><button onclick="closeModal('editSessionModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="es_id">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group"><label>Date</label><input type="date" name="session_date" id="es_date" required></div>
                    <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" id="es_dur" min="1" required></div>
                </div>
                <div class="form-group"><label>Course</label>
                    <select name="course_id_form" id="es_course">
                        <?php foreach ($all_courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Topics Covered</label><input type="text" name="topics_covered" id="es_topics"></div>
                <div class="form-group"><label>Productivity</label>
                    <select name="productivity_rating" id="es_prod">
                        <option value="High">High 🔥</option><option value="Medium">Medium ⚡</option><option value="Low">Low 😴</option>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" id="es_notes" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editSessionModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px}
.page-header h1{margin:0;font-size:26px;display:flex;align-items:center;gap:10px}
.page-header .subtitle{margin:5px 0 0;color:var(--text-secondary)}
.breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:20px;color:var(--text-secondary);font-size:14px}
.breadcrumb a{color:var(--primary-color);text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.sessions-list{display:flex;flex-direction:column;gap:14px}
.session-card{background:white;border-radius:10px;padding:16px;box-shadow:var(--shadow);display:flex;align-items:flex-start;gap:16px;transition:all .2s}
.session-card:hover{box-shadow:var(--shadow-lg)}
.session-left{text-align:center;min-width:48px;padding-top:2px}
.session-date{font-size:18px;font-weight:700;color:var(--text-primary);line-height:1}
.session-day{font-size:11px;color:var(--text-secondary);margin-top:2px}
.session-body{flex:1}
.session-top{display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap}
.session-course{font-size:13px;font-weight:600;padding:3px 10px;border-radius:12px;border-left:3px solid #6366f1;background:#f8fafc;color:var(--text-secondary)}
.productivity-badge{font-size:12px;padding:3px 10px;border-radius:12px;font-weight:600}
.session-topic{font-size:13px;color:var(--text-secondary);margin:4px 0;display:flex;align-items:center;gap:5px}
.session-covered{font-size:14px;color:var(--text-primary);margin:4px 0}
.session-notes{font-size:13px;color:var(--text-secondary);margin:4px 0;font-style:italic;display:flex;align-items:center;gap:5px}
.session-right{text-align:right;min-width:80px}
.session-duration{font-size:22px;font-weight:700;color:var(--primary-color)}
.session-min{font-size:11px;color:var(--text-secondary)}
.session-acts{display:flex;gap:5px;margin-top:8px;justify-content:flex-end}
.empty-state{text-align:center;padding:80px 20px;color:var(--text-secondary)}
.empty-state i{font-size:60px;opacity:.2;margin-bottom:20px;display:block}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
</style>

<script>
function openModal(id){document.getElementById(id).style.display='flex'}
function closeModal(id){document.getElementById(id).style.display='none'}
function editSession(s){
    document.getElementById('es_id').value=s.id;
    document.getElementById('es_date').value=s.session_date;
    document.getElementById('es_dur').value=s.duration_minutes;
    document.getElementById('es_topics').value=s.topics_covered||'';
    document.getElementById('es_notes').value=s.notes||'';
    setSelect('es_course',s.course_id);
    setSelect('es_prod',s.productivity_rating);
    openModal('editSessionModal');
}
function setSelect(id,val){const s=document.getElementById(id);for(let o of s.options)if(o.value==val)o.selected=true;}
</script>

<?php include '../../includes/footer.php'; ?>
