<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'My Subjects';
$currentUser = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getDBConnection();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' && verifyCSRFToken($_POST['csrf_token'])) {
            $subject_name = sanitizeInput($_POST['subject_name']);
            $subject_code = sanitizeInput($_POST['subject_code']);
            $credits = (int)$_POST['credits'];
            $instructor = sanitizeInput($_POST['instructor']);
            $color = sanitizeInput($_POST['color']);
            
            $stmt = $conn->prepare("INSERT INTO subjects (user_id, subject_name, subject_code, credits, instructor, color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississ", $_SESSION['user_id'], $subject_name, $subject_code, $credits, $instructor, $color);
            
            if ($stmt->execute()) {
                setFlashMessage("Subject added successfully!", "success");
            } else {
                setFlashMessage("Failed to add subject.", "error");
            }
            $stmt->close();
            header("Location: subjects.php");
            exit();
        }
        
        if ($_POST['action'] == 'delete' && verifyCSRFToken($_POST['csrf_token'])) {
            $subject_id = (int)$_POST['subject_id'];
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                setFlashMessage("Subject deleted successfully!", "success");
            } else {
                setFlashMessage("Failed to delete subject.", "error");
            }
            $stmt->close();
            header("Location: subjects.php");
            exit();
        }
        
        if ($_POST['action'] == 'edit' && verifyCSRFToken($_POST['csrf_token'])) {
            $subject_id = (int)$_POST['subject_id'];
            $subject_name = sanitizeInput($_POST['subject_name']);
            $subject_code = sanitizeInput($_POST['subject_code']);
            $credits = (int)$_POST['credits'];
            $instructor = sanitizeInput($_POST['instructor']);
            $color = sanitizeInput($_POST['color']);
            
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, credits = ?, instructor = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssissii", $subject_name, $subject_code, $credits, $instructor, $color, $subject_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                setFlashMessage("Subject updated successfully!", "success");
            } else {
                setFlashMessage("Failed to update subject.", "error");
            }
            $stmt->close();
            header("Location: subjects.php");
            exit();
        }
    }
    
    closeDBConnection($conn);
}

// Fetch user's subjects
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM subjects WHERE user_id = ? ORDER BY subject_name");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$subjects = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
closeDBConnection($conn);

$csrf_token = generateCSRFToken();

include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-book"></i> My Subjects</h1>
            <p>Manage your semester subjects</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Subject
        </button>
    </div>

    <?php if (empty($subjects)): ?>
    <div class="empty-state">
        <i class="fas fa-book-open"></i>
        <h3>No Subjects Yet</h3>
        <p>Start by adding your semester subjects</p>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Your First Subject
        </button>
    </div>
    <?php else: ?>
    <div class="subjects-grid">
        <?php foreach ($subjects as $subject): ?>
        <div class="subject-card" style="border-left: 4px solid <?php echo htmlspecialchars($subject['color']); ?>">
            <div class="subject-header">
                <div>
                    <h3><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                    <p class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></p>
                </div>
                <div class="subject-actions">
                    <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($subject); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteSubject(<?php echo $subject['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="subject-info">
                <div class="info-item">
                    <i class="fas fa-award"></i>
                    <span><?php echo $subject['credits']; ?> Credits</span>
                </div>
                <?php if ($subject['instructor']): ?>
                <div class="info-item">
                    <i class="fas fa-user-tie"></i>
                    <span><?php echo htmlspecialchars($subject['instructor']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Subject Modal -->
<div id="subjectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Subject</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="subjectForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="subject_id" id="subjectId">
            
            <div class="form-group">
                <label for="subject_name">Subject Name *</label>
                <input type="text" id="subject_name" name="subject_name" required>
            </div>
            
            <div class="form-group">
                <label for="subject_code">Subject Code *</label>
                <input type="text" id="subject_code" name="subject_code" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="credits">Credits *</label>
                    <input type="number" id="credits" name="credits" min="1" max="10" value="3" required>
                </div>
                
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="color" id="color" name="color" value="#2563eb">
                </div>
            </div>
            
            <div class="form-group">
                <label for="instructor">Instructor Name</label>
                <input type="text" id="instructor" name="instructor">
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Subject
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="subject_id" id="deleteSubjectId">
</form>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.subjects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.subject-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: var(--shadow);
    transition: all 0.3s;
}

.subject-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.subject-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.subject-header h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.subject-code {
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 600;
}

.subject-actions {
    display: flex;
    gap: 5px;
}

.btn-icon {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.3s;
    color: var(--text-secondary);
}

.btn-icon:hover {
    background: var(--light-color);
    color: var(--primary-color);
}

.btn-icon.btn-danger:hover {
    background: #fee2e2;
    color: var(--danger-color);
}

.subject-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    font-size: 14px;
}

.info-item i {
    width: 16px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
}

.empty-state i {
    font-size: 64px;
    color: var(--text-secondary);
    margin-bottom: 20px;
}

.empty-state h3 {
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--text-secondary);
    margin-bottom: 20px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-content form {
    padding: 25px;
}

.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Subject';
    document.getElementById('formAction').value = 'add';
    document.getElementById('subjectForm').reset();
    document.getElementById('subjectId').value = '';
    document.getElementById('subjectModal').classList.add('active');
}

function openEditModal(subject) {
    document.getElementById('modalTitle').textContent = 'Edit Subject';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('subjectId').value = subject.id;
    document.getElementById('subject_name').value = subject.subject_name;
    document.getElementById('subject_code').value = subject.subject_code;
    document.getElementById('credits').value = subject.credits;
    document.getElementById('instructor').value = subject.instructor || '';
    document.getElementById('color').value = subject.color;
    document.getElementById('subjectModal').classList.add('active');
}

function closeModal() {
    document.getElementById('subjectModal').classList.remove('active');
}

function deleteSubject(id) {
    if (confirm('Are you sure you want to delete this subject? This will also delete all related timetable, assignments, and exams.')) {
        document.getElementById('deleteSubjectId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal on outside click
document.getElementById('subjectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
