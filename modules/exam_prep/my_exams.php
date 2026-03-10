<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'My Exam Preparation';
$currentUser = getCurrentUser();

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $exam_name = sanitizeInput($_POST['exam_name']);
            $exam_full_name = sanitizeInput($_POST['exam_full_name']);
            $target_date = !empty($_POST['target_date']) ? sanitizeInput($_POST['target_date']) : NULL;
            $status = sanitizeInput($_POST['status']);
            $notes = sanitizeInput($_POST['notes']);
            
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO competitive_exams (user_id, exam_name, exam_full_name, target_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $_SESSION['user_id'], $exam_name, $exam_full_name, $target_date, $status, $notes);
                $message = "Exam added successfully!";
            } else {
                $exam_id = (int)$_POST['exam_id'];
                $stmt = $conn->prepare("UPDATE competitive_exams SET exam_name = ?, exam_full_name = ?, target_date = ?, status = ?, notes = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sssssii", $exam_name, $exam_full_name, $target_date, $status, $notes, $exam_id, $_SESSION['user_id']);
                $message = "Exam updated successfully!";
            }
            
            if ($stmt->execute()) {
                setFlashMessage($message, "success");
            } else {
                setFlashMessage("Failed to save exam.", "error");
            }
            $stmt->close();
            header("Location: my_exams.php");
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $exam_id = (int)$_POST['exam_id'];
            $stmt = $conn->prepare("DELETE FROM competitive_exams WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Exam removed!", "success");
            header("Location: my_exams.php");
            exit();
        }
    }
}

// Fetch user's competitive exams with statistics
$stmt = $conn->prepare("
    SELECT 
        ce.*,
        COUNT(DISTINCT et.id) as total_topics,
        SUM(CASE WHEN et.status = 'Completed' THEN 1 ELSE 0 END) as completed_topics,
        COALESCE(AVG(et.progress_percentage), 0) as avg_progress,
        COALESCE(SUM(ss.duration_minutes), 0) as total_study_time
    FROM competitive_exams ce
    LEFT JOIN exam_topics et ON ce.id = et.exam_id
    LEFT JOIN study_sessions ss ON ce.id = ss.exam_id
    WHERE ce.user_id = ?
    GROUP BY ce.id
    ORDER BY ce.status ASC, ce.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$exams = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);

$csrf_token = generateCSRFToken();

// Common exam options
$exam_options = [
    'JEE' => 'JEE (Joint Entrance Examination)',
    'GATE' => 'GATE (Graduate Aptitude Test in Engineering)',
    'SSC' => 'SSC (Staff Selection Commission)',
    'UPSC' => 'UPSC (Union Public Service Commission)',
    'CAT' => 'CAT (Common Admission Test)',
    'NEET' => 'NEET (National Eligibility cum Entrance Test)',
    'BANK' => 'Banking Exams (IBPS/SBI)',
    'RAILWAY' => 'Railway Recruitment Exams',
    'Other' => 'Other Competitive Exam'
];

include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-trophy"></i> My Exam Preparation</h1>
            <p>Manage your competitive exam preparation</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Exam
        </button>
    </div>

    <?php if (empty($exams)): ?>
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>No Exams Added Yet</h3>
        <p>Start tracking your competitive exam preparation</p>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Your First Exam
        </button>
    </div>
    <?php else: ?>
    <div class="exams-prep-grid">
        <?php foreach ($exams as $exam): 
            $days_until = $exam['target_date'] ? floor((strtotime($exam['target_date']) - time()) / 86400) : null;
            $progress = round($exam['avg_progress'], 1);
        ?>
        <div class="exam-prep-card status-<?php echo strtolower($exam['status']); ?>">
            <div class="exam-prep-header">
                <div>
                    <h3><?php echo htmlspecialchars($exam['exam_name']); ?></h3>
                    <p class="exam-full-name"><?php echo htmlspecialchars($exam['exam_full_name']); ?></p>
                </div>
                <div class="exam-prep-actions">
                    <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($exam); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteExam(<?php echo $exam['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="exam-prep-stats">
                <div class="stat-item">
                    <i class="fas fa-book"></i>
                    <div>
                        <span class="stat-value"><?php echo $exam['completed_topics']; ?> / <?php echo $exam['total_topics']; ?></span>
                        <span class="stat-label">Topics</span>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="stat-value"><?php echo round($exam['total_study_time'] / 60, 1); ?>h</span>
                        <span class="stat-label">Study Time</span>
                    </div>
                </div>
                <?php if ($exam['target_date']): ?>
                <div class="stat-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <span class="stat-value"><?php echo $days_until > 0 ? $days_until . ' days' : 'Past'; ?></span>
                        <span class="stat-label">Time Left</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <span class="progress-text"><?php echo $progress; ?>% Complete</span>
            </div>

            <div class="exam-prep-footer">
                <span class="status-badge status-<?php echo strtolower($exam['status']); ?>">
                    <?php echo $exam['status']; ?>
                </span>
                <div class="action-links">
                    <a href="topics.php?exam_id=<?php echo $exam['id']; ?>" class="action-link">
                        <i class="fas fa-list"></i> Topics
                    </a>
                    <a href="study_sessions.php?exam_id=<?php echo $exam['id']; ?>" class="action-link">
                        <i class="fas fa-pen"></i> Sessions
                    </a>
                    <a href="goals.php?exam_id=<?php echo $exam['id']; ?>" class="action-link">
                        <i class="fas fa-bullseye"></i> Goals
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Exam Modal -->
<div id="examModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Competitive Exam</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="examForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="exam_id" id="examId">
            
            <div class="form-group">
                <label for="exam_name">Exam Type *</label>
                <select id="exam_name" name="exam_name" required onchange="updateFullName()">
                    <option value="">Select exam type</option>
                    <?php foreach ($exam_options as $key => $value): ?>
                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="exam_full_name">Full Exam Name *</label>
                <input type="text" id="exam_full_name" name="exam_full_name" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="target_date">Target Exam Date</label>
                    <input type="date" id="target_date" name="target_date">
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="Active" selected>Active</option>
                        <option value="Paused">Paused</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Preparation strategy, resources, etc."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Exam
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="exam_id" id="deleteExamId">
</form>

<style>
.exams-prep-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.exam-prep-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: var(--shadow);
    transition: all 0.3s;
    border-left: 4px solid var(--primary-color);
}

.exam-prep-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.exam-prep-card.status-paused {
    border-left-color: var(--warning-color);
    opacity: 0.8;
}

.exam-prep-card.status-completed {
    border-left-color: var(--success-color);
}

.exam-prep-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
}

.exam-prep-header h3 {
    margin: 0 0 5px 0;
    font-size: 20px;
    color: var(--primary-color);
}

.exam-full-name {
    color: var(--text-secondary);
    font-size: 13px;
    margin: 0;
}

.exam-prep-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-item i {
    font-size: 24px;
    color: var(--primary-color);
}

.stat-value {
    display: block;
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
}

.stat-label {
    display: block;
    font-size: 12px;
    color: var(--text-secondary);
}

.progress-bar-container {
    margin-bottom: 20px;
}

.progress-bar {
    height: 8px;
    background: var(--light-color);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--success-color));
    border-radius: 10px;
    transition: width 0.3s;
}

.progress-text {
    font-size: 12px;
    color: var(--text-secondary);
}

.exam-prep-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
}

.action-links {
    display: flex;
    gap: 15px;
}

.action-link {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--primary-color);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s;
}

.action-link:hover {
    color: var(--secondary-color);
}

.status-badge.status-active {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.status-paused {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.status-completed {
    background: #d1fae5;
    color: #065f46;
}

@media (max-width: 768px) {
    .exams-prep-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const examOptions = <?php echo json_encode($exam_options); ?>;

function updateFullName() {
    const select = document.getElementById('exam_name');
    const input = document.getElementById('exam_full_name');
    if (select.value && examOptions[select.value]) {
        input.value = examOptions[select.value];
    }
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Competitive Exam';
    document.getElementById('formAction').value = 'add';
    document.getElementById('examForm').reset();
    document.getElementById('examId').value = '';
    document.getElementById('examModal').classList.add('active');
}

function openEditModal(exam) {
    document.getElementById('modalTitle').textContent = 'Edit Competitive Exam';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('examId').value = exam.id;
    document.getElementById('exam_name').value = exam.exam_name;
    document.getElementById('exam_full_name').value = exam.exam_full_name;
    document.getElementById('target_date').value = exam.target_date || '';
    document.getElementById('status').value = exam.status;
    document.getElementById('notes').value = exam.notes || '';
    document.getElementById('examModal').classList.add('active');
}

function closeModal() {
    document.getElementById('examModal').classList.remove('active');
}

function deleteExam(id) {
    if (confirm('Are you sure you want to delete this exam? This will remove all related topics, sessions, and goals.')) {
        document.getElementById('deleteExamId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('examModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
