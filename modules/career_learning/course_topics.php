<?php
require_once '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if (!$course_id) {
    header('Location: my_courses.php');
    exit();
}

$conn = getDBConnection();

// Verify course belongs to user
$stmt = $conn->prepare("SELECT * FROM courses WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    setFlashMessage('Course not found.', 'error');
    header('Location: my_courses.php');
    exit();
}

$pageTitle = $course['course_name'] . ' - Topics';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'error');
        header("Location: course_topics.php?course_id=$course_id");
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $topic_name = sanitizeInput($_POST['topic_name'] ?? '');
        $section_number = intval($_POST['section_number'] ?? 1);
        $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
        $status = $_POST['status'] ?? 'Not Started';
        $progress = floatval($_POST['progress_percentage'] ?? 0);
        $notes = sanitizeInput($_POST['notes'] ?? '');

        // Auto-set progress based on status
        if ($status === 'Completed') $progress = 100;
        if ($status === 'Not Started') $progress = 0;

        if (empty($topic_name)) {
            setFlashMessage('Topic name is required.', 'error');
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO course_topics (course_id, topic_name, section_number, duration_minutes, status, progress_percentage, notes) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("isiisds", $course_id, $topic_name, $section_number, $duration_minutes, $status, $progress, $notes);
                $stmt->execute();
                $stmt->close();
                setFlashMessage('Topic added!', 'success');
            } else {
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE course_topics SET topic_name=?, section_number=?, duration_minutes=?, status=?, progress_percentage=?, notes=? WHERE id=? AND course_id=?");
                $stmt->bind_param("siiisdii", $topic_name, $section_number, $duration_minutes, $status, $progress, $notes, $id, $course_id);
                $stmt->execute();
                $stmt->close();
                setFlashMessage('Topic updated!', 'success');
            }

            // Recalculate course progress from topics
            $stmt = $conn->prepare("SELECT AVG(progress_percentage) as avg_progress FROM course_topics WHERE course_id=?");
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $avg = $stmt->get_result()->fetch_assoc()['avg_progress'] ?? 0;
            $stmt->close();
            $stmt = $conn->prepare("UPDATE courses SET progress_percentage=? WHERE id=?");
            $stmt->bind_param("di", $avg, $course_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM course_topics WHERE id=? AND course_id=?");
        $stmt->bind_param("ii", $id, $course_id);
        $stmt->execute();
        $stmt->close();
        setFlashMessage('Topic deleted.', 'success');
    }

    header("Location: course_topics.php?course_id=$course_id");
    exit();
}

// Fetch stats
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status='In Progress' THEN 1 ELSE 0 END) as in_progress FROM course_topics WHERE course_id=?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch topics
$stmt = $conn->prepare("SELECT * FROM course_topics WHERE course_id=? ORDER BY section_number ASC, id ASC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);
include '../../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="my_courses.php"><i class="fas fa-laptop-code"></i> My Courses</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($course['course_name']); ?></span>
    </div>

    <div class="page-header">
        <div>
            <h1 style="border-left:4px solid <?php echo $course['color']; ?>;padding-left:12px">
                <i class="fas fa-list-check"></i> Course Topics
            </h1>
            <p class="subtitle"><?php echo htmlspecialchars($course['course_name']); ?> &mdash; <?php echo $course['platform']; ?></p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addTopicModal')">
            <i class="fas fa-plus"></i> Add Topic
        </button>
    </div>

    <!-- Progress Overview -->
    <div class="overview-card" style="border-left:4px solid <?php echo $course['color']; ?>">
        <div class="overview-progress">
            <div class="ov-stat"><span class="ov-num"><?php echo $stats['total'] ?? 0; ?></span><span class="ov-label">Total Topics</span></div>
            <div class="ov-stat"><span class="ov-num"><?php echo $stats['completed'] ?? 0; ?></span><span class="ov-label">Completed</span></div>
            <div class="ov-stat"><span class="ov-num"><?php echo $stats['in_progress'] ?? 0; ?></span><span class="ov-label">In Progress</span></div>
            <div class="ov-stat"><span class="ov-num"><?php echo number_format($course['progress_percentage'],0); ?>%</span><span class="ov-label">Overall</span></div>
        </div>
        <div class="overall-bar-wrap">
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?php echo $course['progress_percentage']; ?>%;background:<?php echo $course['color']; ?>"></div></div>
        </div>
    </div>

    <!-- Topics List -->
    <?php if (empty($topics)): ?>
    <div class="empty-state">
        <i class="fas fa-list"></i>
        <h3>No topics yet</h3>
        <p>Break down the course syllabus into topics to track your progress</p>
        <button class="btn btn-primary" onclick="openModal('addTopicModal')"><i class="fas fa-plus"></i> Add Topic</button>
    </div>
    <?php else: ?>
    <div class="topics-list">
        <?php foreach ($topics as $t):
            $sc = ['Not Started'=>'#94a3b8','In Progress'=>'#f59e0b','Completed'=>'#10b981'][$t['status']] ?? '#94a3b8';
        ?>
        <div class="topic-card <?php echo $t['status']==='Completed'?'topic-done':''; ?>">
            <div class="topic-num"><?php echo $t['section_number']; ?></div>
            <div class="topic-body">
                <div class="topic-top">
                    <span class="topic-name"><?php echo htmlspecialchars($t['topic_name']); ?></span>
                    <span class="status-badge sm" style="background:<?php echo $sc;?>20;color:<?php echo $sc;?>"><?php echo $t['status']; ?></span>
                </div>
                <?php if ($t['duration_minutes'] > 0): ?>
                <p class="topic-dur"><i class="fas fa-clock"></i> <?php echo $t['duration_minutes']; ?> min</p>
                <?php endif; ?>
                <div class="progress-bar-bg" style="margin-top:8px">
                    <div class="progress-bar-fill" style="width:<?php echo $t['progress_percentage']; ?>%;background:<?php echo $course['color']; ?>"></div>
                </div>
                <span class="topic-pct"><?php echo number_format($t['progress_percentage'],0); ?>%</span>
            </div>
            <div class="topic-actions">
                <button onclick='editTopic(<?php echo htmlspecialchars(json_encode($t)); ?>)' class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this topic?')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Topic Modal -->
<div id="addTopicModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('addTopicModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-plus-circle"></i> Add Topic</h2><button onclick="closeModal('addTopicModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label>Topic Name *</label><input type="text" name="topic_name" required placeholder="e.g., Data Preprocessing"></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Section #</label><input type="number" name="section_number" min="1" value="1"></div>
                    <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" min="0" value="0"></div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Status</label>
                        <select name="status"><option>Not Started</option><option>In Progress</option><option>Completed</option></select>
                    </div>
                    <div class="form-group"><label>Progress (%)</label><input type="number" name="progress_percentage" min="0" max="100" value="0"></div>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addTopicModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Topic</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Topic Modal -->
<div id="editTopicModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('editTopicModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-edit"></i> Edit Topic</h2><button onclick="closeModal('editTopicModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="et_id">
            <div class="modal-body">
                <div class="form-group"><label>Topic Name *</label><input type="text" name="topic_name" id="et_name" required></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Section #</label><input type="number" name="section_number" id="et_sec" min="1"></div>
                    <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" id="et_dur" min="0"></div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Status</label>
                        <select name="status" id="et_status"><option>Not Started</option><option>In Progress</option><option>Completed</option></select>
                    </div>
                    <div class="form-group"><label>Progress (%)</label><input type="number" name="progress_percentage" id="et_pct" min="0" max="100"></div>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" id="et_notes" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editTopicModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Topic</button>
            </div>
        </form>
    </div>
</div>

<style>
.breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:20px;color:var(--text-secondary);font-size:14px}
.breadcrumb a{color:var(--primary-color);text-decoration:none;display:flex;align-items:center;gap:5px}
.breadcrumb a:hover{text-decoration:underline}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.page-header h1{margin:0;font-size:24px;display:flex;align-items:center;gap:10px}
.page-header .subtitle{margin:5px 0 0;color:var(--text-secondary)}
.overview-card{background:white;border-radius:12px;padding:20px;box-shadow:var(--shadow);margin-bottom:25px}
.overview-progress{display:flex;gap:30px;margin-bottom:15px}
.ov-stat{display:flex;flex-direction:column;align-items:center}
.ov-num{font-size:28px;font-weight:700;color:var(--text-primary)}
.ov-label{font-size:12px;color:var(--text-secondary)}
.overall-bar-wrap .progress-bar-bg{height:10px;border-radius:6px}
.topics-list{display:flex;flex-direction:column;gap:12px}
.topic-card{background:white;border-radius:10px;padding:16px;box-shadow:var(--shadow);display:flex;align-items:center;gap:16px;transition:all .2s}
.topic-card:hover{box-shadow:var(--shadow-lg)}
.topic-card.topic-done{opacity:.75}
.topic-num{width:36px;height:36px;min-width:36px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;color:var(--text-secondary)}
.topic-body{flex:1}
.topic-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px}
.topic-name{font-weight:600;font-size:15px}
.status-badge.sm{font-size:11px;padding:2px 8px}
.topic-dur{font-size:12px;color:var(--text-secondary);margin:0 0 4px;display:flex;align-items:center;gap:5px}
.topic-pct{font-size:12px;color:var(--text-secondary);float:right}
.topic-actions{display:flex;gap:6px}
.progress-bar-bg{background:#f1f5f9;border-radius:6px;height:8px;overflow:hidden}
.progress-bar-fill{height:100%;border-radius:6px;transition:width .4s}
.empty-state{text-align:center;padding:80px 20px;color:var(--text-secondary)}
.empty-state i{font-size:60px;opacity:.2;margin-bottom:20px;display:block}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
</style>

<script>
function openModal(id){document.getElementById(id).style.display='flex'}
function closeModal(id){document.getElementById(id).style.display='none'}
function editTopic(t){
    document.getElementById('et_id').value=t.id;
    document.getElementById('et_name').value=t.topic_name;
    document.getElementById('et_sec').value=t.section_number;
    document.getElementById('et_dur').value=t.duration_minutes;
    document.getElementById('et_pct').value=t.progress_percentage;
    document.getElementById('et_notes').value=t.notes||'';
    setSelect('et_status',t.status);
    openModal('editTopicModal');
}
function setSelect(id,val){const s=document.getElementById(id);for(let o of s.options)if(o.value===val)o.selected=true;}
</script>

<?php include '../../includes/footer.php'; ?>
