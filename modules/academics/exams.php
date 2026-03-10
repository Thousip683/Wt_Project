<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'Exams';
$currentUser = getCurrentUser();

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $subject_id = (int)$_POST['subject_id'];
            $exam_name = sanitizeInput($_POST['exam_name']);
            $exam_type = sanitizeInput($_POST['exam_type']);
            $exam_date = sanitizeInput($_POST['exam_date']);
            $exam_time = !empty($_POST['exam_time']) ? sanitizeInput($_POST['exam_time']) : NULL;
            $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : NULL;
            $total_marks = !empty($_POST['total_marks']) ? (int)$_POST['total_marks'] : NULL;
            $obtained_marks = !empty($_POST['obtained_marks']) ? (int)$_POST['obtained_marks'] : NULL;
            $syllabus = sanitizeInput($_POST['syllabus']);
            $room_number = sanitizeInput($_POST['room_number']);
            $status = sanitizeInput($_POST['status']);
            
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO exams (user_id, subject_id, exam_name, exam_type, exam_date, exam_time, duration, total_marks, obtained_marks, syllabus, room_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissssiissss", $_SESSION['user_id'], $subject_id, $exam_name, $exam_type, $exam_date, $exam_time, $duration, $total_marks, $obtained_marks, $syllabus, $room_number, $status);
                $message = "Exam added successfully!";
            } else {
                $exam_id = (int)$_POST['exam_id'];
                $stmt = $conn->prepare("UPDATE exams SET subject_id = ?, exam_name = ?, exam_type = ?, exam_date = ?, exam_time = ?, duration = ?, total_marks = ?, obtained_marks = ?, syllabus = ?, room_number = ?, status = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("isssssiisssii", $subject_id, $exam_name, $exam_type, $exam_date, $exam_time, $duration, $total_marks, $obtained_marks, $syllabus, $room_number, $status, $exam_id, $_SESSION['user_id']);
                $message = "Exam updated successfully!";
            }
            
            if ($stmt->execute()) {
                setFlashMessage($message, "success");
            } else {
                setFlashMessage("Failed to save exam.", "error");
            }
            $stmt->close();
            header("Location: exams.php");
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $exam_id = (int)$_POST['exam_id'];
            $stmt = $conn->prepare("DELETE FROM exams WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Exam deleted!", "success");
            header("Location: exams.php");
            exit();
        }
    }
}

// Fetch subjects
$stmt = $conn->prepare("SELECT id, subject_name, subject_code, color FROM subjects WHERE user_id = ? ORDER BY subject_name");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$subjects_result = $stmt->get_result();
$subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch exams
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'upcoming';
$sql = "SELECT e.*, s.subject_name, s.subject_code, s.color 
        FROM exams e 
        JOIN subjects s ON e.subject_id = s.id 
        WHERE e.user_id = ?";

if ($filter == 'upcoming') {
    $sql .= " AND e.status = 'Upcoming'";
} elseif ($filter == 'completed') {
    $sql .= " AND e.status = 'Completed'";
}

$sql .= " ORDER BY e.exam_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$exams = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-file-alt"></i> Exams</h1>
            <p>Track your exam schedule and results</p>
        </div>
        <?php if (!empty($subjects)): ?>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Exam
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="filters">
        <a href="?filter=upcoming" class="filter-btn <?php echo $filter == 'upcoming' ? 'active' : ''; ?>">
            Upcoming
        </a>
        <a href="?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">
            Completed
        </a>
        <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">
            All
        </a>
    </div>

    <?php if (empty($subjects)): ?>
    <div class="empty-state">
        <i class="fas fa-book"></i>
        <h3>No Subjects Found</h3>
        <p>Please add subjects first</p>
        <a href="subjects.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Subjects
        </a>
    </div>
    <?php elseif (empty($exams)): ?>
    <div class="empty-state">
        <i class="fas fa-file-alt"></i>
        <h3>No Exams</h3>
        <p><?php echo $filter == 'all' ? 'Start tracking your exams' : 'No ' . $filter . ' exams'; ?></p>
        <?php if ($filter == 'all' || $filter == 'upcoming'): ?>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add First Exam
        </button>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="exams-grid">
        <?php foreach ($exams as $exam): 
            $days_until = floor((strtotime($exam['exam_date']) - time()) / 86400);
            $is_soon = ($days_until >= 0 && $days_until <= 7);
        ?>
        <div class="exam-card <?php echo $is_soon ? 'exam-soon' : ''; ?>" style="border-left: 4px solid <?php echo htmlspecialchars($exam['color']); ?>">
            <div class="exam-header">
                <div class="exam-type-badge <?php echo strtolower($exam['exam_type']); ?>">
                    <?php echo htmlspecialchars($exam['exam_type']); ?>
                </div>
                <div class="exam-actions">
                    <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($exam); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteExam(<?php echo $exam['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <h3 class="exam-title"><?php echo htmlspecialchars($exam['exam_name']); ?></h3>
            <div class="exam-subject"><?php echo htmlspecialchars($exam['subject_name']); ?></div>
            
            <div class="exam-details">
                <div class="detail-row">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('d F Y', strtotime($exam['exam_date'])); ?></span>
                    <?php if ($is_soon && $exam['status'] == 'Upcoming'): ?>
                        <span class="days-badge"><?php echo $days_until; ?> days</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($exam['exam_time']): ?>
                <div class="detail-row">
                    <i class="fas fa-clock"></i>
                    <span><?php echo date('g:i A', strtotime($exam['exam_time'])); ?></span>
                    <?php if ($exam['duration']): ?>
                        <span class="text-muted">(<?php echo $exam['duration']; ?> mins)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($exam['room_number']): ?>
                <div class="detail-row">
                    <i class="fas fa-door-open"></i>
                    <span>Room <?php echo htmlspecialchars($exam['room_number']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($exam['total_marks']): ?>
                <div class="detail-row">
                    <i class="fas fa-award"></i>
                    <span>
                        <?php if ($exam['obtained_marks'] !== null): ?>
                            <?php echo $exam['obtained_marks']; ?> / <?php echo $exam['total_marks']; ?> marks
                            (<?php echo round(($exam['obtained_marks'] / $exam['total_marks']) * 100, 1); ?>%)
                        <?php else: ?>
                            Total: <?php echo $exam['total_marks']; ?> marks
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($exam['syllabus']): ?>
            <div class="exam-syllabus">
                <strong>Syllabus:</strong>
                <p><?php echo nl2br(htmlspecialchars($exam['syllabus'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="exam-status">
                <span class="status-badge status-<?php echo strtolower($exam['status']); ?>">
                    <?php echo $exam['status']; ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Exam Modal -->
<div id="examModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">Add Exam</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="examForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="exam_id" id="examId">
            
            <div class="form-group">
                <label for="subject_id">Subject *</label>
                <select id="subject_id" name="subject_id" required>
                    <option value="">Select subject</option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?php echo $subject['id']; ?>">
                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="exam_name">Exam Name *</label>
                    <input type="text" id="exam_name" name="exam_name" required>
                </div>
                
                <div class="form-group">
                    <label for="exam_type">Exam Type *</label>
                    <select id="exam_type" name="exam_type" required>
                        <option value="Quiz">Quiz</option>
                        <option value="Mid-term">Mid-term</option>
                        <option value="End-term">End-term</option>
                        <option value="Practical">Practical</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="exam_date">Exam Date *</label>
                    <input type="date" id="exam_date" name="exam_date" required>
                </div>
                
                <div class="form-group">
                    <label for="exam_time">Exam Time</label>
                    <input type="time" id="exam_time" name="exam_time">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration" min="0">
                </div>
                
                <div class="form-group">
                    <label for="room_number">Room Number</label>
                    <input type="text" id="room_number" name="room_number">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="total_marks">Total Marks</label>
                    <input type="number" id="total_marks" name="total_marks" min="0">
                </div>
                
                <div class="form-group">
                    <label for="obtained_marks">Obtained Marks</label>
                    <input type="number" id="obtained_marks" name="obtained_marks" min="0">
                </div>
            </div>
            
            <div class="form-group">
                <label for="syllabus">Syllabus/Topics</label>
                <textarea id="syllabus" name="syllabus" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="Upcoming" selected>Upcoming</option>
                    <option value="Completed">Completed</option>
                </select>
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
.exams-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.exam-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: var(--shadow);
    transition: all 0.3s;
}

.exam-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.exam-card.exam-soon {
    background: #fffbeb;
    border-left-color: var(--warning-color) !important;
}

.exam-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.exam-type-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.exam-type-badge.quiz {
    background: #dbeafe;
    color: #1e40af;
}

.exam-type-badge.mid-term {
    background: #fef3c7;
    color: #92400e;
}

.exam-type-badge.end-term {
    background: #fee2e2;
    color: #991b1b;
}

.exam-type-badge.practical {
    background: #d1fae5;
    color: #065f46;
}

.exam-type-badge.other {
    background: #f3e8ff;
    color: #6b21a8;
}

.exam-title {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.exam-subject {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 15px;
}

.exam-details {
    margin-bottom: 15px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 14px;
}

.detail-row i {
    width: 16px;
    color: var(--text-secondary);
}

.days-badge {
    background: var(--warning-color);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: auto;
}

.exam-syllabus {
    background: var(--light-color);
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 14px;
}

.exam-syllabus strong {
    display: block;
    margin-bottom: 5px;
}

.exam-syllabus p {
    margin: 0;
    color: var(--text-secondary);
}

.exam-status {
    display: flex;
    justify-content: flex-end;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.status-upcoming {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.status-completed {
    background: #d1fae5;
    color: #065f46;
}

@media (max-width: 768px) {
    .exams-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Exam';
    document.getElementById('formAction').value = 'add';
    document.getElementById('examForm').reset();
    document.getElementById('examId').value = '';
    document.getElementById('examModal').classList.add('active');
}

function openEditModal(exam) {
    document.getElementById('modalTitle').textContent = 'Edit Exam';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('examId').value = exam.id;
    document.getElementById('subject_id').value = exam.subject_id;
    document.getElementById('exam_name').value = exam.exam_name;
    document.getElementById('exam_type').value = exam.exam_type;
    document.getElementById('exam_date').value = exam.exam_date;
    document.getElementById('exam_time').value = exam.exam_time || '';
    document.getElementById('duration').value = exam.duration || '';
    document.getElementById('total_marks').value = exam.total_marks || '';
    document.getElementById('obtained_marks').value = exam.obtained_marks || '';
    document.getElementById('syllabus').value = exam.syllabus || '';
    document.getElementById('room_number').value = exam.room_number || '';
    document.getElementById('status').value = exam.status;
    document.getElementById('examModal').classList.add('active');
}

function closeModal() {
    document.getElementById('examModal').classList.remove('active');
}

function deleteExam(id) {
    if (confirm('Are you sure you want to delete this exam?')) {
        document.getElementById('deleteExamId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('examModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
