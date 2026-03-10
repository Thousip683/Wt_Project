<?php
require_once '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$pageTitle = 'Skills Tracker';
$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'error');
        header('Location: skills.php');
        exit();
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $skill_name = sanitizeInput($_POST['skill_name'] ?? '');
        $category   = $_POST['category'] ?? 'Other';
        $proficiency = $_POST['proficiency'] ?? 'Beginner';
        $course_id  = intval($_POST['course_id'] ?? 0) ?: null;
        $notes      = sanitizeInput($_POST['notes'] ?? '');

        if (empty($skill_name)) {
            setFlashMessage('Skill name is required.', 'error');
        } elseif ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO skills (user_id, skill_name, category, proficiency, course_id, notes) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("isssis", $user_id, $skill_name, $category, $proficiency, $course_id, $notes);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Skill added!', 'success');
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE skills SET skill_name=?, category=?, proficiency=?, course_id=?, notes=? WHERE id=? AND user_id=?");
            $stmt->bind_param("sssisii", $skill_name, $category, $proficiency, $course_id, $notes, $id, $user_id);
            $stmt->execute(); $stmt->close();
            setFlashMessage('Skill updated!', 'success');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM skills WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute(); $stmt->close();
        setFlashMessage('Skill removed.', 'success');
    }
    header('Location: skills.php');
    exit();
}

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN proficiency='Expert' THEN 1 ELSE 0 END) as expert, SUM(CASE WHEN proficiency='Advanced' THEN 1 ELSE 0 END) as advanced FROM skills WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Skills by category
$stmt = $conn->prepare("SELECT s.*, c.course_name FROM skills s LEFT JOIN courses c ON s.course_id=c.id WHERE s.user_id=? ORDER BY FIELD(s.proficiency,'Expert','Advanced','Intermediate','Beginner'), s.category, s.skill_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// All courses for dropdown
$stmt = $conn->prepare("SELECT id, course_name FROM courses WHERE user_id=? ORDER BY course_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);
include '../../includes/header.php';

$prof_colors = ['Beginner'=>'#94a3b8','Intermediate'=>'#3b82f6','Advanced'=>'#f59e0b','Expert'=>'#10b981'];
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-star"></i> Skills Tracker</h1>
            <p class="subtitle">Track the skills you're developing</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addSkillModal')"><i class="fas fa-plus"></i> Add Skill</button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:30px">
        <div class="stat-card">
            <div class="stat-icon" style="background:#6366f1"><i class="fas fa-star"></i></div>
            <div class="stat-content"><h3>Total Skills</h3><p class="stat-value"><?php echo $stats['total']??0; ?></p><p class="stat-label">Skills tracked</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f59e0b"><i class="fas fa-bolt"></i></div>
            <div class="stat-content"><h3>Advanced</h3><p class="stat-value"><?php echo $stats['advanced']??0; ?></p><p class="stat-label">Advanced skills</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#10b981"><i class="fas fa-trophy"></i></div>
            <div class="stat-content"><h3>Expert</h3><p class="stat-value"><?php echo $stats['expert']??0; ?></p><p class="stat-label">Expert-level skills</p></div>
        </div>
    </div>

    <!-- Skills Grid -->
    <?php if (empty($skills)): ?>
    <div class="empty-state">
        <i class="fas fa-star"></i><h3>No skills yet</h3>
        <p>Start tracking the skills you're building through your courses</p>
        <button class="btn btn-primary" onclick="openModal('addSkillModal')"><i class="fas fa-plus"></i> Add First Skill</button>
    </div>
    <?php else: ?>
    <div class="skills-grid">
        <?php foreach ($skills as $sk):
            $pc = $prof_colors[$sk['proficiency']] ?? '#94a3b8';
            $cat_icons = ['Programming Language'=>'fa-code','Framework'=>'fa-layer-group','Tool'=>'fa-tools','Concept'=>'fa-lightbulb','Soft Skill'=>'fa-handshake','Other'=>'fa-star'];
            $icon = $cat_icons[$sk['category']] ?? 'fa-star';
        ?>
        <div class="skill-card">
            <div class="skill-icon" style="background:<?php echo $pc; ?>20;color:<?php echo $pc; ?>">
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
            <div class="skill-body">
                <span class="skill-name"><?php echo htmlspecialchars($sk['skill_name']); ?></span>
                <span class="prof-badge" style="background:<?php echo $pc; ?>20;color:<?php echo $pc; ?>"><?php echo $sk['proficiency']; ?></span>
                <p class="skill-cat"><i class="fas fa-tag"></i> <?php echo $sk['category']; ?></p>
                <?php if ($sk['course_name']): ?>
                <p class="skill-course"><i class="fas fa-laptop-code"></i> <?php echo htmlspecialchars($sk['course_name']); ?></p>
                <?php endif; ?>
                <?php if ($sk['notes']): ?>
                <p class="skill-notes"><?php echo htmlspecialchars($sk['notes']); ?></p>
                <?php endif; ?>
            </div>
            <div class="skill-actions">
                <button onclick='editSkill(<?php echo htmlspecialchars(json_encode($sk)); ?>)' class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Remove this skill?')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $sk['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Skill Modal -->
<div id="addSkillModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('addSkillModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-star"></i> Add Skill</h2><button onclick="closeModal('addSkillModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label>Skill Name *</label><input type="text" name="skill_name" required placeholder="e.g., Python, React, Machine Learning"></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Category</label>
                        <select name="category">
                            <option>Programming Language</option><option>Framework</option><option>Tool</option>
                            <option>Concept</option><option>Soft Skill</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Proficiency</label>
                        <select name="proficiency">
                            <option>Beginner</option><option>Intermediate</option><option>Advanced</option><option>Expert</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Linked Course (optional)</label>
                    <select name="course_id"><option value="">-- None --</option>
                        <?php foreach ($all_courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" rows="2" placeholder="Any notes..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addSkillModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Skill</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Skill Modal -->
<div id="editSkillModal" class="modal" style="display:none">
    <div class="modal-overlay" onclick="closeModal('editSkillModal')"></div>
    <div class="modal-content">
        <div class="modal-header"><h2><i class="fas fa-edit"></i> Edit Skill</h2><button onclick="closeModal('editSkillModal')" class="modal-close">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="esk_id">
            <div class="modal-body">
                <div class="form-group"><label>Skill Name *</label><input type="text" name="skill_name" id="esk_name" required></div>
                <div class="form-grid-2">
                    <div class="form-group"><label>Category</label>
                        <select name="category" id="esk_cat">
                            <option>Programming Language</option><option>Framework</option><option>Tool</option>
                            <option>Concept</option><option>Soft Skill</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Proficiency</label>
                        <select name="proficiency" id="esk_prof">
                            <option>Beginner</option><option>Intermediate</option><option>Advanced</option><option>Expert</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Linked Course</label>
                    <select name="course_id" id="esk_course"><option value="">-- None --</option>
                        <?php foreach ($all_courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" id="esk_notes" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editSkillModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Skill</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px}
.page-header h1{margin:0;font-size:26px;display:flex;align-items:center;gap:10px}
.page-header .subtitle{margin:5px 0 0;color:var(--text-secondary)}
.skills-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.skill-card{background:white;border-radius:10px;padding:18px;box-shadow:var(--shadow);display:flex;align-items:flex-start;gap:14px;transition:all .2s}
.skill-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}
.skill-icon{width:44px;height:44px;min-width:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px}
.skill-body{flex:1}
.skill-name{font-size:16px;font-weight:700;color:var(--text-primary);display:block;margin-bottom:6px}
.prof-badge{font-size:11px;padding:2px 10px;border-radius:12px;font-weight:700;display:inline-block;margin-bottom:8px}
.skill-cat,.skill-course,.skill-notes{font-size:12px;color:var(--text-secondary);margin:2px 0;display:flex;align-items:center;gap:5px}
.skill-notes{font-style:italic}
.skill-actions{display:flex;flex-direction:column;gap:5px}
.empty-state{text-align:center;padding:80px 20px;color:var(--text-secondary)}
.empty-state i{font-size:60px;opacity:.2;margin-bottom:20px;display:block}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
</style>

<script>
function openModal(id){document.getElementById(id).style.display='flex'}
function closeModal(id){document.getElementById(id).style.display='none'}
function editSkill(s){
    document.getElementById('esk_id').value=s.id;
    document.getElementById('esk_name').value=s.skill_name;
    document.getElementById('esk_notes').value=s.notes||'';
    setSelect('esk_cat',s.category);
    setSelect('esk_prof',s.proficiency);
    setSelect('esk_course',s.course_id||'');
    openModal('editSkillModal');
}
function setSelect(id,val){const s=document.getElementById(id);for(let o of s.options)if(o.value==val)o.selected=true;}
</script>

<?php include '../../includes/footer.php'; ?>
