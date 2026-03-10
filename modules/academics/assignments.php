<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'Assignments';
$currentUser = getCurrentUser();

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $subject_id = (int)$_POST['subject_id'];
            $title = sanitizeInput($_POST['title']);
            $description = sanitizeInput($_POST['description']);
            $due_date = sanitizeInput($_POST['due_date']);
            $priority = sanitizeInput($_POST['priority']);
            $status = sanitizeInput($_POST['status']);
            $marks = !empty($_POST['marks']) ? (int)$_POST['marks'] : NULL;
            
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO assignments (user_id, subject_id, title, description, due_date, priority, status, marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssssi", $_SESSION['user_id'], $subject_id, $title, $description, $due_date, $priority, $status, $marks);
                $message = "Assignment added successfully!";
            } else {
                $assignment_id = (int)$_POST['assignment_id'];
                $stmt = $conn->prepare("UPDATE assignments SET subject_id = ?, title = ?, description = ?, due_date = ?, priority = ?, status = ?, marks = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("issssssii", $subject_id, $title, $description, $due_date, $priority, $status, $marks, $assignment_id, $_SESSION['user_id']);
                $message = "Assignment updated successfully!";
            }
            
            if ($stmt->execute()) {
                setFlashMessage($message, "success");
            } else {
                setFlashMessage("Failed to save assignment.", "error");
            }
            $stmt->close();
            header("Location: assignments.php");
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $assignment_id = (int)$_POST['assignment_id'];
            $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $assignment_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Assignment deleted!", "success");
            header("Location: assignments.php");
            exit();
        }
        
        if ($_POST['action'] == 'update_status') {
            $assignment_id = (int)$_POST['assignment_id'];
            $status = sanitizeInput($_POST['status']);
            $stmt = $conn->prepare("UPDATE assignments SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $status, $assignment_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Status updated!", "success");
            header("Location: assignments.php");
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

// Fetch assignments
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql = "SELECT a.*, s.subject_name, s.subject_code, s.color 
        FROM assignments a 
        JOIN subjects s ON a.subject_id = s.id 
        WHERE a.user_id = ?";

if ($filter == 'pending') {
    $sql .= " AND a.status != 'Completed'";
} elseif ($filter == 'completed') {
    $sql .= " AND a.status = 'Completed'";
} elseif ($filter == 'overdue') {
    $sql .= " AND a.due_date < CURDATE() AND a.status != 'Completed'";
}

$sql .= " ORDER BY a.due_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-tasks"></i> Assignments</h1>
            <p>Track your assignments and deadlines</p>
        </div>
        <?php if (!empty($subjects)): ?>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Assignment
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="filters">
        <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">
            All
        </a>
        <a href="?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">
            Pending
        </a>
        <a href="?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">
            Completed
        </a>
        <a href="?filter=overdue" class="filter-btn <?php echo $filter == 'overdue' ? 'active' : ''; ?>">
            Overdue
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
    <?php elseif (empty($assignments)): ?>
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>No Assignments</h3>
        <p><?php echo $filter == 'all' ? 'Start tracking your assignments' : 'No ' . $filter . ' assignments'; ?></p>
        <?php if ($filter == 'all'): ?>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add First Assignment
        </button>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="assignments-list">
        <?php foreach ($assignments as $assignment): 
            $is_overdue = (strtotime($assignment['due_date']) < time() && $assignment['status'] != 'Completed');
            $days_left = floor((strtotime($assignment['due_date']) - time()) / 86400);
        ?>
        <div class="assignment-card <?php echo $is_overdue ? 'overdue' : ''; ?>" style="border-left: 4px solid <?php echo htmlspecialchars($assignment['color']); ?>">
            <div class="assignment-header">
                <div class="assignment-subject">
                    <span class="subject-badge" style="background: <?php echo htmlspecialchars($assignment['color']); ?>">
                        <?php echo htmlspecialchars($assignment['subject_code']); ?>
                    </span>
                    <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                </div>
                <div class="assignment-actions">
                    <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($assignment); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <?php if ($assignment['description']): ?>
            <p class="assignment-description"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
            <?php endif; ?>
            
            <div class="assignment-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span>Due: <?php echo date('d M Y', strtotime($assignment['due_date'])); ?></span>
                    <?php if ($is_overdue): ?>
                        <span class="overdue-badge">Overdue</span>
                    <?php elseif ($days_left >= 0 && $days_left <= 3): ?>
                        <span class="urgent-badge"><?php echo $days_left; ?> days left</span>
                    <?php endif; ?>
                </div>
                <div class="meta-item">
                    <i class="fas fa-flag"></i>
                    <span class="priority-<?php echo strtolower($assignment['priority']); ?>">
                        <?php echo $assignment['priority']; ?> Priority
                    </span>
                </div>
                <?php if ($assignment['marks']): ?>
                <div class="meta-item">
                    <i class="fas fa-award"></i>
                    <span><?php echo $assignment['marks']; ?> marks</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="assignment-footer">
                <select class="status-select status-<?php echo strtolower(str_replace(' ', '-', $assignment['status'])); ?>" 
                        onchange="updateStatus(<?php echo $assignment['id']; ?>, this.value)">
                    <option value="Pending" <?php echo $assignment['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="In Progress" <?php echo $assignment['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $assignment['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Assignment Modal -->
<div id="assignmentModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">Add Assignment</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="assignmentForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="assignment_id" id="assignmentId">
            
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
            
            <div class="form-group">
                <label for="title">Assignment Title *</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="due_date">Due Date *</label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select id="priority" name="priority" required>
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="Pending" selected>Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="marks">Total Marks</label>
                    <input type="number" id="marks" name="marks" min="0">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Assignment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="assignment_id" id="deleteAssignmentId">
</form>

<!-- Status Update Form -->
<form method="POST" action="" id="statusForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="assignment_id" id="statusAssignmentId">
    <input type="hidden" name="status" id="statusValue">
</form>

<style>
.filters {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 20px;
    border: 2px solid var(--border-color);
    border-radius: 20px;
    text-decoration: none;
    color: var(--text-primary);
    font-weight: 500;
    transition: all 0.3s;
}

.filter-btn:hover, .filter-btn.active {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
}

.assignments-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.assignment-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: var(--shadow);
    transition: all 0.3s;
}

.assignment-card:hover {
    box-shadow: var(--shadow-lg);
}

.assignment-card.overdue {
    background: #fef2f2;
}

.assignment-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.assignment-subject {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.subject-badge {
    color: white;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.assignment-subject h3 {
    margin: 0;
    font-size: 18px;
}

.assignment-description {
    color: var(--text-secondary);
    margin-bottom: 15px;
    line-height: 1.5;
}

.assignment-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--text-secondary);
}

.overdue-badge, .urgent-badge {
    background: var(--danger-color);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

.urgent-badge {
    background: var(--warning-color);
}

.priority-high {
    color: var(--danger-color);
    font-weight: 600;
}

.priority-medium {
    color: var(--warning-color);
    font-weight: 600;
}

.priority-low {
    color: var(--success-color);
    font-weight: 600;
}

.assignment-footer {
    display: flex;
    justify-content: flex-end;
}

.status-select {
    padding: 6px 12px;
    border: 2px solid var(--border-color);
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.status-select.status-pending {
    border-color: #fbbf24;
    color: #92400e;
    background: #fef3c7;
}

.status-select.status-in-progress {
    border-color: #60a5fa;
    color: #1e40af;
    background: #dbeafe;
}

.status-select.status-completed {
    border-color: #34d399;
    color: #065f46;
    background: #d1fae5;
}

.modal-large {
    max-width: 600px;
}

@media (max-width: 768px) {
    .assignment-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .assignment-meta {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Assignment';
    document.getElementById('formAction').value = 'add';
    document.getElementById('assignmentForm').reset();
    document.getElementById('assignmentId').value = '';
    document.getElementById('assignmentModal').classList.add('active');
}

function openEditModal(assignment) {
    document.getElementById('modalTitle').textContent = 'Edit Assignment';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('assignmentId').value = assignment.id;
    document.getElementById('subject_id').value = assignment.subject_id;
    document.getElementById('title').value = assignment.title;
    document.getElementById('description').value = assignment.description || '';
    document.getElementById('due_date').value = assignment.due_date;
    document.getElementById('priority').value = assignment.priority;
    document.getElementById('status').value = assignment.status;
    document.getElementById('marks').value = assignment.marks || '';
    document.getElementById('assignmentModal').classList.add('active');
}

function closeModal() {
    document.getElementById('assignmentModal').classList.remove('active');
}

function deleteAssignment(id) {
    if (confirm('Are you sure you want to delete this assignment?')) {
        document.getElementById('deleteAssignmentId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function updateStatus(id, status) {
    document.getElementById('statusAssignmentId').value = id;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}

document.getElementById('assignmentModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
