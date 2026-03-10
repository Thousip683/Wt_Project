<?php
require_once '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);
$pageTitle = 'Projects';
$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'error');
        header('Location: projects.php' . ($course_id ? "?course_id=$course_id" : ''));
        exit();
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name        = sanitizeInput($_POST['project_name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $tech_stack  = sanitizeInput($_POST['tech_stack'] ?? '');
        $github_url  = sanitizeInput($_POST['github_url'] ?? '');
        $live_url    = sanitizeInput($_POST['live_url'] ?? '');
        $cid         = intval($_POST['course_id_form'] ?? 0) ?: null;
        $status      = $_POST['status'] ?? 'Planning';
        $start_date  = $_POST['start_date'] ?: null;
        $comp_date   = $_POST['completion_date'] ?: null;

        if (empty($name)) {
            setFlashMessage('Project name is required.', 'error');
        } elseif ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO projects (user_id, project_name, description, tech_stack, github_url, live_url, course_id, status, start_date, completion_date) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("isssssisss", $user_id, $name, $description, $tech_stack, $github_url, $live_url, $cid, $status, $start_date, $comp_date);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Project added!', 'success');
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE projects SET project_name=?, description=?, tech_stack=?, github_url=?, live_url=?, course_id=?, status=?, start_date=?, completion_date=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssssssissii", $name, $description, $tech_stack, $github_url, $live_url, $cid, $status, $start_date, $comp_date, $id, $user_id);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Project updated!', 'success');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM projects WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute(); $stmt->close();
        setFlashMessage('Project deleted.', 'success');
    }
    header('Location: projects.php' . ($course_id ? "?course_id=$course_id" : ''));
    exit();
}

// Fetch stats
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status='In Progress' THEN 1 ELSE 0 END) as in_progress FROM projects WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Projects
$where = $course_id ? "AND p.course_id=$course_id" : '';
$stmt = $conn->prepare("SELECT p.*, c.course_name FROM projects p LEFT JOIN courses c ON p.course_id=c.id WHERE p.user_id=? $where ORDER BY FIELD(p.status,'In Progress','Planning','On Hold','Completed'), p.updated_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// All courses for form
$stmt = $conn->prepare("SELECT id, course_name FROM courses WHERE user_id=? ORDER BY course_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);
include '../../includes/header.php';

$status_colors = ['Planning'=>'#6366f1','In Progress'=>'#f59e0b','Completed'=>'#10b981','On Hold'=>'#94a3b8'];
?>

<div class="container">
    <?php if ($course_id): ?>
    <div class="breadcrumb">
        <a href="my_courses.php"><i class="fas fa-laptop-code"></i> My Courses</a>
        <i class="fas fa-chevron-right"></i><span>Projects</span>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1><i class="fas fa-code-branch"></i> Projects</h1>
            <p class="subtitle">Hands-on projects you've built during learning</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addProjectModal')"><i class="fas fa-plus"></i> Add Project</button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:30px">
        <div class="stat-card">
            <div class="stat-icon" style="background:#6366f1"><i class="fas fa-code-branch"></i></div>
            <div class="stat-content"><h3>Total</h3><p class="stat-value"><?php echo $stats['total']??0; ?></p><p class="stat-label">Projects</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f59e0b"><i class="fas fa-spinner"></i></div>
            <div class="stat-content"><h3>In Progress</h3><p class="stat-value"><?php echo $stats['in_progress']??0; ?></p><p class="stat-label">Active projects</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#10b981"><i class="fas fa-check-double"></i></div>
            <div class="stat-content"><h3>Completed</h3><p class="stat-value"><?php echo $stats['completed']??0; ?></p><p class="stat-label">Shipped projects</p></div>
        </div>
    </div>

    <!-- Projects Grid -->
    <?php if (empty($projects)): ?>
    <div class="empty-state">
        <i class="fas fa-code-branch"></i><h3>No projects yet</h3>
        <p>Start adding the projects you build while learning!</p>
        <button class="btn btn-primary" onclick="openModal('addProjectModal')"><i class="fas fa-plus"></i> Add Project</button>
    </div>
    <?php else: ?>
    <div class="projects-grid">
        <?php foreach ($projects as $p):
            $sc = $status_colors[$p['status']] ?? '#94a3b8';
            $techs = array_filter(array_map('trim', explode(',', $p['tech_stack'] ?? '')));
        ?>
        <div class="project-card">
            <div class="project-header">
                <h3 class="project-name"><?php echo htmlspecialchars($p['project_name']); ?></h3>
                <span class="status-badge" style="background:<?php echo $sc;?>20;color:<?php echo $sc;?>"><?php echo $p['status']; ?></span>
            </div>
            <?php if ($p['description']): ?>
            <p class="project-desc"><?php echo htmlspecialchars($p['description']); ?></p>
            <?php endif; ?>

            <!-- Tech Stack Tags -->
            <?php if (!empty($techs)): ?>
            <div class="tech-tags">
                <?php foreach ($techs as $t): ?><span class="tech-tag"><?php echo htmlspecialchars($t); ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($p['course_name']): ?>
            <p class="project-course"><i class="fas fa-laptop-code"></i> <?php echo htmlspecialchars($p['course_name']); ?></p>
            <?php endif; ?>

            <?php if ($p['start_date']): ?>
            <p class="project-dates"><i class="fas fa-calendar"></i>
                <?php echo date('d M Y', strtotime($p['start_date'])); ?>
                <?php if ($p['completion_date']): ?> → <?php echo date('d M Y', strtotime($p['completion_date'])); ?><?php endif; ?>
            </p>
            <?php endif; ?>

            <!-- Links -->
            <div class="project-links">
                <?php if ($p['github_url']): ?>
                <a href="<?php echo htmlspecialchars($p['github_url']); ?>" target="_blank" class="proj-link github"><i class="fab fa-github"></i> GitHub</a>
                <?php endif; ?>
                <?php if ($p['live_url']): ?>
                <a href="<?php echo htmlspecialchars($p['live_url']); ?>" target="_blank" class="proj-link live"><i class="fas fa-external-link-alt"></i> Live</a>
                <?php endif; ?>
            </div>

            <div class="card-actions">
                <button onclick='editProject(<?php echo htmlspecialchars(json_encode($p)); ?>)' class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this project?')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Project Modal -->
<div id="addProjectModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('addProjectModal')"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header"><h2><i class="fas fa-plus-circle"></i> Add Project</h2><button onclick="closeModal('addProjectModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label>Project Name *</label><input type="text" name="project_name" required placeholder="e.g., Student Grade Predictor"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2" placeholder="What does this project do?"></textarea></div>
                <div class="form-group"><label>Tech Stack <small>(comma-separated)</small></label><input type="text" name="tech_stack" placeholder="Python, scikit-learn, Flask"></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>GitHub URL</label><input type="url" name="github_url" placeholder="https://github.com/..."></div>
                    <div class="form-group"><label>Live URL</label><input type="url" name="live_url" placeholder="https://..."></div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Status</label>
                        <select name="status"><option>Planning</option><option>In Progress</option><option>Completed</option><option>On Hold</option></select>
                    </div>
                    <div class="form-group"><label>Linked Course</label>
                        <select name="course_id_form"><option value="">-- None --</option>
                            <?php foreach ($all_courses as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $course_id==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['course_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
                    <div class="form-group"><label>Completion Date</label><input type="date" name="completion_date"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addProjectModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Project Modal -->
<div id="editProjectModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('editProjectModal')"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header"><h2><i class="fas fa-edit"></i> Edit Project</h2><button onclick="closeModal('editProjectModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="ep_id">
            <div class="modal-body">
                <div class="form-group"><label>Project Name *</label><input type="text" name="project_name" id="ep_name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" id="ep_desc" rows="2"></textarea></div>
                <div class="form-group"><label>Tech Stack</label><input type="text" name="tech_stack" id="ep_tech"></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>GitHub URL</label><input type="url" name="github_url" id="ep_github"></div>
                    <div class="form-group"><label>Live URL</label><input type="url" name="live_url" id="ep_live"></div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Status</label>
                        <select name="status" id="ep_status"><option>Planning</option><option>In Progress</option><option>Completed</option><option>On Hold</option></select>
                    </div>
                    <div class="form-group"><label>Linked Course</label>
                        <select name="course_id_form" id="ep_course"><option value="">-- None --</option>
                            <?php foreach ($all_courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Start Date</label><input type="date" name="start_date" id="ep_start"></div>
                    <div class="form-group"><label>Completion Date</label><input type="date" name="completion_date" id="ep_comp"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editProjectModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Project</button>
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
.projects-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px}
.project-card{background:white;border-radius:12px;padding:20px;box-shadow:var(--shadow);transition:all .2s}
.project-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg)}
.project-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px}
.project-name{margin:0;font-size:17px;font-weight:700}
.project-desc{font-size:14px;color:var(--text-secondary);margin:0 0 12px;line-height:1.5}
.tech-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.tech-tag{font-size:11px;padding:3px 10px;border-radius:12px;background:#f1f5f9;color:#475569;font-weight:600}
.project-course,.project-dates{font-size:13px;color:var(--text-secondary);margin:4px 0;display:flex;align-items:center;gap:6px}
.project-links{display:flex;gap:8px;margin:14px 0}
.proj-link{display:flex;align-items:center;gap:6px;font-size:13px;padding:6px 14px;border-radius:20px;text-decoration:none;font-weight:600;border:1px solid #e2e8f0;color:var(--text-secondary);transition:all .2s}
.proj-link.github:hover{background:#1f2937;color:white;border-color:#1f2937}
.proj-link.live:hover{background:#6366f1;color:white;border-color:#6366f1}
.card-actions{display:flex;gap:8px;margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9}
.empty-state{text-align:center;padding:80px 20px;color:var(--text-secondary)}
.empty-state i{font-size:60px;opacity:.2;margin-bottom:20px;display:block}
.modal-lg{max-width:700px}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
</style>

<script>
function openModal(id){document.getElementById(id).style.display='flex'}
function closeModal(id){document.getElementById(id).style.display='none'}
function editProject(p){
    document.getElementById('ep_id').value=p.id;
    document.getElementById('ep_name').value=p.project_name;
    document.getElementById('ep_desc').value=p.description||'';
    document.getElementById('ep_tech').value=p.tech_stack||'';
    document.getElementById('ep_github').value=p.github_url||'';
    document.getElementById('ep_live').value=p.live_url||'';
    document.getElementById('ep_start').value=p.start_date||'';
    document.getElementById('ep_comp').value=p.completion_date||'';
    setSelect('ep_status',p.status);
    setSelect('ep_course',p.course_id||'');
    openModal('editProjectModal');
}
function setSelect(id,val){const s=document.getElementById(id);for(let o of s.options)if(o.value==val)o.selected=true;}
</script>

<?php include '../../includes/footer.php'; ?>
