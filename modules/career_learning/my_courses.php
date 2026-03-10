<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'My Courses';
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'error');
        header('Location: my_courses.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $course_name = sanitizeInput($_POST['course_name'] ?? '');
        $platform    = $_POST['platform'] ?? 'Other';
        $category    = $_POST['category'] ?? 'Other';
        $instructor  = sanitizeInput($_POST['instructor'] ?? '');
        $total_hours = floatval($_POST['total_hours'] ?? 0);
        $start_date  = $_POST['start_date'] ?: null;
        $target_date = $_POST['target_date'] ?: null;
        $status      = $_POST['status'] ?? 'Not Started';
        $progress    = floatval($_POST['progress_percentage'] ?? 0);
        $course_url  = sanitizeInput($_POST['course_url'] ?? '');
        $color       = $_POST['color'] ?? '#6366f1';
        $notes       = sanitizeInput($_POST['notes'] ?? '');

        if (empty($course_name)) {
            setFlashMessage('Course name is required.', 'error');
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO courses (user_id, course_name, platform, category, instructor, total_hours, start_date, target_date, status, progress_percentage, course_url, color, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("issssdsssdsss", $user_id, $course_name, $platform, $category, $instructor, $total_hours, $start_date, $target_date, $status, $progress, $course_url, $color, $notes);
                $stmt->execute();
                $stmt->close();
                setFlashMessage('Course added successfully!', 'success');
            } else {
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE courses SET course_name=?, platform=?, category=?, instructor=?, total_hours=?, start_date=?, target_date=?, status=?, progress_percentage=?, course_url=?, color=?, notes=?, hours_completed=? WHERE id=? AND user_id=?");
                $hours_comp = floatval($_POST['hours_completed'] ?? 0);
                $stmt->bind_param("ssssdsssdsssdii", $course_name, $platform, $category, $instructor, $total_hours, $start_date, $target_date, $status, $progress, $course_url, $color, $notes, $hours_comp, $id, $user_id);
                $stmt->execute();
                $stmt->close();
                setFlashMessage('Course updated successfully!', 'success');
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM courses WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
        setFlashMessage('Course deleted.', 'success');
    }

    header('Location: my_courses.php');
    exit();
}

// Fetch stats
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='In Progress' THEN 1 ELSE 0 END) as in_progress, SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as completed, SUM(hours_completed) as total_hours FROM courses WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch all courses
$stmt = $conn->prepare("SELECT * FROM courses WHERE user_id=? ORDER BY status='In Progress' DESC, updated_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-laptop-code"></i> My Courses</h1>
            <p class="subtitle">Track your online learning journey</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addCourseModal')">
            <i class="fas fa-plus"></i> Add Course
        </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:#6366f1"><i class="fas fa-laptop-code"></i></div>
            <div class="stat-content"><h3>Total</h3><p class="stat-value"><?php echo $stats['total'] ?? 0; ?></p><p class="stat-label">Courses tracked</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f59e0b"><i class="fas fa-spinner"></i></div>
            <div class="stat-content"><h3>In Progress</h3><p class="stat-value"><?php echo $stats['in_progress'] ?? 0; ?></p><p class="stat-label">Currently active</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#10b981"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content"><h3>Completed</h3><p class="stat-value"><?php echo $stats['completed'] ?? 0; ?></p><p class="stat-label">Courses finished</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#3b82f6"><i class="fas fa-clock"></i></div>
            <div class="stat-content"><h3>Hours Learned</h3><p class="stat-value"><?php echo number_format($stats['total_hours'] ?? 0, 1); ?></p><p class="stat-label">Total study time</p></div>
        </div>
    </div>

    <!-- Courses Grid -->
    <?php if (empty($courses)): ?>
    <div class="empty-state">
        <i class="fas fa-laptop-code"></i>
        <h3>No courses yet</h3>
        <p>Start tracking your first online course!</p>
        <button class="btn btn-primary" onclick="openModal('addCourseModal')"><i class="fas fa-plus"></i> Add Course</button>
    </div>
    <?php else: ?>
    <div class="courses-grid">
        <?php foreach ($courses as $course): 
            $status_colors = ['Not Started'=>'#94a3b8','In Progress'=>'#f59e0b','Completed'=>'#10b981','On Hold'=>'#ef4444'];
            $sc = $status_colors[$course['status']] ?? '#94a3b8';
        ?>
        <div class="course-card" style="border-top: 4px solid <?php echo $course['color']; ?>">
            <div class="course-card-header">
                <div>
                    <h3 class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <p class="course-meta">
                        <span class="platform-badge"><?php echo $course['platform']; ?></span>
                        <span class="category-badge"><?php echo $course['category']; ?></span>
                    </p>
                </div>
                <span class="status-badge" style="background:<?php echo $sc; ?>20;color:<?php echo $sc; ?>"><?php echo $course['status']; ?></span>
            </div>

            <?php if ($course['instructor']): ?>
            <p class="course-instructor"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($course['instructor']); ?></p>
            <?php endif; ?>

            <!-- Progress Bar -->
            <div class="progress-section">
                <div class="progress-label">
                    <span>Progress</span>
                    <span><?php echo number_format($course['progress_percentage'], 0); ?>%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width:<?php echo $course['progress_percentage']; ?>%;background:<?php echo $course['color']; ?>"></div>
                </div>
                <p class="hours-text"><?php echo number_format($course['hours_completed'],1); ?> / <?php echo number_format($course['total_hours'],1); ?> hrs</p>
            </div>

            <?php if ($course['target_date']): ?>
            <p class="course-deadline">
                <i class="fas fa-calendar-alt"></i> Target: <?php echo date('d M Y', strtotime($course['target_date'])); ?>
                <?php 
                    $days_left = floor((strtotime($course['target_date']) - time()) / 86400);
                    if ($course['status'] !== 'Completed' && $days_left < 0): ?>
                    <span class="overdue-badge">Overdue</span>
                <?php elseif ($course['status'] !== 'Completed' && $days_left <= 14): ?>
                    <span class="soon-badge"><?php echo $days_left; ?>d left</span>
                <?php endif; ?>
            </p>
            <?php endif; ?>

            <!-- Quick Links -->
            <div class="course-links">
                <a href="course_topics.php?course_id=<?php echo $course['id']; ?>" class="course-link-btn"><i class="fas fa-list"></i> Topics</a>
                <a href="learning_sessions.php?course_id=<?php echo $course['id']; ?>" class="course-link-btn"><i class="fas fa-clock"></i> Sessions</a>
                <a href="projects.php?course_id=<?php echo $course['id']; ?>" class="course-link-btn"><i class="fas fa-code"></i> Projects</a>
                <?php if ($course['course_url']): ?>
                <a href="<?php echo htmlspecialchars($course['course_url']); ?>" target="_blank" class="course-link-btn ext"><i class="fas fa-external-link-alt"></i> Open</a>
                <?php endif; ?>
            </div>

            <div class="card-actions">
                <button onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this course and all its data?')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $course['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Course Modal -->
<div id="addCourseModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('addCourseModal')"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Add New Course</h2>
            <button onclick="closeModal('addCourseModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Course Name *</label>
                        <input type="text" name="course_name" required placeholder="e.g., Machine Learning A-Z">
                    </div>
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform">
                            <option>Udemy</option><option>Coursera</option><option>edX</option>
                            <option>YouTube</option><option>LinkedIn Learning</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option>AI</option><option>Machine Learning</option><option>Data Science</option>
                            <option>Web Development</option><option>DSA</option><option>DevOps</option>
                            <option>Cybersecurity</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Instructor</label>
                        <input type="text" name="instructor" placeholder="Instructor name">
                    </div>
                    <div class="form-group">
                        <label>Total Hours</label>
                        <input type="number" name="total_hours" min="0" step="0.5" value="0">
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date">
                    </div>
                    <div class="form-group">
                        <label>Target Completion</label>
                        <input type="date" name="target_date">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option>Not Started</option><option>In Progress</option>
                            <option>Completed</option><option>On Hold</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Progress (%)</label>
                        <input type="number" name="progress_percentage" min="0" max="100" step="1" value="0">
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="color" name="color" value="#6366f1" style="height:42px;padding:4px">
                    </div>
                    <div class="form-group full-width">
                        <label>Course URL</label>
                        <input type="url" name="course_url" placeholder="https://...">
                    </div>
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea name="notes" rows="2" placeholder="Any notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addCourseModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Course</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="editCourseModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('editCourseModal')"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Course</h2>
            <button onclick="closeModal('editCourseModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Course Name *</label>
                        <input type="text" name="course_name" id="edit_course_name" required>
                    </div>
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform" id="edit_platform">
                            <option>Udemy</option><option>Coursera</option><option>edX</option>
                            <option>YouTube</option><option>LinkedIn Learning</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="edit_category">
                            <option>AI</option><option>Machine Learning</option><option>Data Science</option>
                            <option>Web Development</option><option>DSA</option><option>DevOps</option>
                            <option>Cybersecurity</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Instructor</label>
                        <input type="text" name="instructor" id="edit_instructor">
                    </div>
                    <div class="form-group">
                        <label>Total Hours</label>
                        <input type="number" name="total_hours" id="edit_total_hours" min="0" step="0.5">
                    </div>
                    <div class="form-group">
                        <label>Hours Completed</label>
                        <input type="number" name="hours_completed" id="edit_hours_completed" min="0" step="0.5">
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="edit_start_date">
                    </div>
                    <div class="form-group">
                        <label>Target Completion</label>
                        <input type="date" name="target_date" id="edit_target_date">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status">
                            <option>Not Started</option><option>In Progress</option>
                            <option>Completed</option><option>On Hold</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Progress (%)</label>
                        <input type="number" name="progress_percentage" id="edit_progress" min="0" max="100" step="1">
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="color" name="color" id="edit_color" style="height:42px;padding:4px">
                    </div>
                    <div class="form-group full-width">
                        <label>Course URL</label>
                        <input type="url" name="course_url" id="edit_course_url">
                    </div>
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea name="notes" id="edit_notes" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editCourseModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Course</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px}
.page-header h1{margin:0;font-size:28px;display:flex;align-items:center;gap:10px}
.page-header .subtitle{margin:5px 0 0;color:var(--text-secondary)}
.courses-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px}
.course-card{background:white;border-radius:12px;padding:22px;box-shadow:var(--shadow);transition:transform .2s,box-shadow .2s}
.course-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg)}
.course-card-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;gap:10px}
.course-name{margin:0 0 6px;font-size:17px;font-weight:700;color:var(--text-primary)}
.course-meta{display:flex;gap:6px;margin:0;flex-wrap:wrap}
.platform-badge,.category-badge{font-size:11px;padding:2px 8px;border-radius:10px;background:#f1f5f9;color:#64748b;font-weight:600}
.status-badge{font-size:12px;padding:4px 10px;border-radius:20px;font-weight:600;white-space:nowrap}
.course-instructor{font-size:13px;color:var(--text-secondary);margin:8px 0;display:flex;align-items:center;gap:6px}
.progress-section{margin:14px 0}
.progress-label{display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:6px}
.progress-bar-bg{background:#f1f5f9;border-radius:6px;height:8px;overflow:hidden}
.progress-bar-fill{height:100%;border-radius:6px;transition:width .4s ease}
.hours-text{font-size:12px;color:var(--text-secondary);margin:5px 0 0;text-align:right}
.course-deadline{font-size:13px;color:var(--text-secondary);display:flex;align-items:center;gap:6px;margin:10px 0}
.overdue-badge{background:#ef444420;color:#ef4444;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700}
.soon-badge{background:#f59e0b20;color:#f59e0b;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700}
.course-links{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}
.course-link-btn{font-size:12px;padding:5px 12px;border-radius:20px;background:#f8fafc;color:var(--text-secondary);text-decoration:none;display:flex;align-items:center;gap:5px;font-weight:600;border:1px solid #e2e8f0;transition:all .2s}
.course-link-btn:hover{background:var(--primary-color);color:white;border-color:var(--primary-color)}
.course-link-btn.ext:hover{background:#10b981;border-color:#10b981}
.card-actions{display:flex;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9}
.btn-sm{padding:6px 12px;font-size:13px}
.empty-state{text-align:center;padding:80px 20px;color:var(--text-secondary)}
.empty-state i{font-size:60px;opacity:.2;margin-bottom:20px;display:block}
.empty-state h3{font-size:22px;margin-bottom:10px;color:var(--text-primary)}
.modal-lg{max-width:700px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid .full-width{grid-column:1/-1}
</style>

<script>
function openModal(id){document.getElementById(id).style.display='flex'}
function closeModal(id){document.getElementById(id).style.display='none'}
function editCourse(c){
    document.getElementById('edit_id').value=c.id;
    document.getElementById('edit_course_name').value=c.course_name;
    document.getElementById('edit_instructor').value=c.instructor||'';
    document.getElementById('edit_total_hours').value=c.total_hours;
    document.getElementById('edit_hours_completed').value=c.hours_completed;
    document.getElementById('edit_start_date').value=c.start_date||'';
    document.getElementById('edit_target_date').value=c.target_date||'';
    document.getElementById('edit_progress').value=c.progress_percentage;
    document.getElementById('edit_color').value=c.color;
    document.getElementById('edit_course_url').value=c.course_url||'';
    document.getElementById('edit_notes').value=c.notes||'';
    setSelect('edit_platform',c.platform);
    setSelect('edit_category',c.category);
    setSelect('edit_status',c.status);
    openModal('editCourseModal');
}
function setSelect(id,val){const s=document.getElementById(id);for(let o of s.options)if(o.value===val)o.selected=true;}
window.onclick=e=>{document.querySelectorAll('.modal').forEach(m=>{if(e.target===m)m.style.display='none'});}
</script>

<?php include '../../includes/footer.php'; ?>
