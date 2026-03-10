<?php
require_once '../../config/config.php';
requireLogin();

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if (!$exam_id) {
    header("Location: my_exams.php");
    exit();
}

$conn = getDBConnection();

// Verify exam belongs to user
$stmt = $conn->prepare("SELECT * FROM competitive_exams WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    header("Location: my_exams.php");
    exit();
}

$pageTitle = 'Topics - ' . $exam['exam_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $topic_name = sanitizeInput($_POST['topic_name']);
            $subject_category = sanitizeInput($_POST['subject_category']);
            $total_chapters = (int)$_POST['total_chapters'];
            $priority = sanitizeInput($_POST['priority']);
            $status = sanitizeInput($_POST['status']);
            $progress = (float)$_POST['progress_percentage'];
            
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO exam_topics (exam_id, topic_name, subject_category, total_chapters, priority, status, progress_percentage) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssd", $exam_id, $topic_name, $subject_category, $total_chapters, $priority, $status, $progress);
                $message = "Topic added successfully!";
            } else {
                $topic_id = (int)$_POST['topic_id'];
                $stmt = $conn->prepare("UPDATE exam_topics SET topic_name = ?, subject_category = ?, total_chapters = ?, priority = ?, status = ?, progress_percentage = ? WHERE id = ? AND exam_id = ?");
                $stmt->bind_param("ssissdii", $topic_name, $subject_category, $total_chapters, $priority, $status, $progress, $topic_id, $exam_id);
                $message = "Topic updated successfully!";
            }
            
            if ($stmt->execute()) {
                setFlashMessage($message, "success");
            } else {
                setFlashMessage("Failed to save topic.", "error");
            }
            $stmt->close();
            header("Location: topics.php?exam_id=" . $exam_id);
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $topic_id = (int)$_POST['topic_id'];
            $stmt = $conn->prepare("DELETE FROM exam_topics WHERE id = ? AND exam_id = ?");
            $stmt->bind_param("ii", $topic_id, $exam_id);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Topic deleted!", "success");
            header("Location: topics.php?exam_id=" . $exam_id);
            exit();
        }
    }
}

// Fetch topics
$stmt = $conn->prepare("SELECT * FROM exam_topics WHERE exam_id = ? ORDER BY priority DESC, topic_name ASC");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate overall progress
$total_topics = count($topics);
$completed_topics = count(array_filter($topics, function($t) { return $t['status'] == 'Completed'; }));
$avg_progress = $total_topics > 0 ? array_sum(array_column($topics, 'progress_percentage')) / $total_topics : 0;

closeDBConnection($conn);

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="my_exams.php"><i class="fas fa-trophy"></i> My Exams</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($exam['exam_name']); ?> - Topics</span>
    </div>

    <div class="page-header">
        <div>
            <h1><i class="fas fa-list"></i> Topics for <?php echo htmlspecialchars($exam['exam_name']); ?></h1>
            <p>Track your topic-wise preparation progress</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Topic
        </button>
    </div>

    <!-- Progress Overview -->
    <div class="progress-overview">
        <div class="overview-card">
            <i class="fas fa-book"></i>
            <div>
                <span class="overview-value"><?php echo $total_topics; ?></span>
                <span class="overview-label">Total Topics</span>
            </div>
        </div>
        <div class="overview-card">
            <i class="fas fa-check-circle"></i>
            <div>
                <span class="overview-value"><?php echo $completed_topics; ?></span>
                <span class="overview-label">Completed</span>
            </div>
        </div>
        <div class="overview-card">
            <i class="fas fa-chart-line"></i>
            <div>
                <span class="overview-value"><?php echo round($avg_progress, 1); ?>%</span>
                <span class="overview-label">Avg Progress</span>
            </div>
        </div>
    </div>

    <?php if (empty($topics)): ?>
    <div class="empty-state">
        <i class="fas fa-list-ul"></i>
        <h3>No Topics Added</h3>
        <p>Start adding topics to track your preparation</p>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add First Topic
        </button>
    </div>
    <?php else: ?>
    <div class="topics-list">
        <?php foreach ($topics as $topic): ?>
        <div class="topic-card priority-<?php echo strtolower($topic['priority']); ?> status-<?php echo strtolower(str_replace(' ', '-', $topic['status'])); ?>">
            <div class="topic-header">
                <div class="topic-info">
                    <h3><?php echo htmlspecialchars($topic['topic_name']); ?></h3>
                    <?php if ($topic['subject_category']): ?>
                    <span class="subject-badge"><?php echo htmlspecialchars($topic['subject_category']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="topic-actions">
                    <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($topic); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteTopic(<?php echo $topic['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="topic-meta">
                <span class="priority-badge priority-<?php echo strtolower($topic['priority']); ?>">
                    <i class="fas fa-flag"></i> <?php echo $topic['priority']; ?>
                </span>
                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $topic['status'])); ?>">
                    <?php echo $topic['status']; ?>
                </span>
                <?php if ($topic['total_chapters']): ?>
                <span class="chapters-info">
                    <i class="fas fa-book-open"></i> <?php echo $topic['total_chapters']; ?> chapters
                </span>
                <?php endif; ?>
            </div>
            
            <div class="topic-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $topic['progress_percentage']; ?>%"></div>
                </div>
                <span class="progress-text"><?php echo $topic['progress_percentage']; ?>% Complete</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Topic Modal -->
<div id="topicModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Topic</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="topicForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="topic_id" id="topicId">
            
            <div class="form-group">
                <label for="topic_name">Topic Name *</label>
                <input type="text" id="topic_name" name="topic_name" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="subject_category">Subject Category</label>
                    <input type="text" id="subject_category" name="subject_category" placeholder="e.g., Mathematics, Physics">
                </div>
                
                <div class="form-group">
                    <label for="total_chapters">Total Chapters</label>
                    <input type="number" id="total_chapters" name="total_chapters" min="0" value="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select id="priority" name="priority" required>
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="Not Started" selected>Not Started</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="progress_percentage">Progress Percentage</label>
                <input type="range" id="progress_percentage" name="progress_percentage" min="0" max="100" value="0" oninput="updateProgress(this.value)">
                <span id="progressValue">0%</span>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Topic
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="topic_id" id="deleteTopicId">
</form>

<style>
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb i.fa-chevron-right {
    font-size: 12px;
    color: var(--text-secondary);
}

.progress-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.overview-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 15px;
}

.overview-card i {
    font-size: 32px;
    color: var(--primary-color);
}

.overview-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
}

.overview-label {
    display: block;
    font-size: 13px;
    color: var(--text-secondary);
}

.topics-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.topic-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary-color);
    transition: all 0.3s;
}

.topic-card:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow-lg);
}

.topic-card.priority-high {
    border-left-color: var(--danger-color);
}

.topic-card.priority-medium {
    border-left-color: var(--warning-color);
}

.topic-card.priority-low {
    border-left-color: var(--success-color);
}

.topic-card.status-completed {
    background: #f0fdf4;
}

.topic-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.topic-info h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
}

.subject-badge {
    background: var(--light-color);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    color: var(--text-secondary);
}

.topic-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.priority-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.priority-badge.priority-high {
    background: #fee2e2;
    color: #991b1b;
}

.priority-badge.priority-medium {
    background: #fef3c7;
    color: #92400e;
}

.priority-badge.priority-low {
    background: #d1fae5;
    color: #065f46;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.status-not-started {
    background: #f3f4f6;
    color: #4b5563;
}

.status-badge.status-in-progress {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.chapters-info {
    color: var(--text-secondary);
    font-size: 13px;
}

.topic-progress {
    display: flex;
    align-items: center;
    gap: 10px;
}

.topic-progress .progress-bar {
    flex: 1;
}

#progressValue {
    font-weight: 600;
    color: var(--primary-color);
    min-width: 50px;
    text-align: right;
}
</style>

<script>
function updateProgress(value) {
    document.getElementById('progressValue').textContent = value + '%';
    
    // Auto-update status based on progress
    const status = document.getElementById('status');
    if (value == 0) {
        status.value = 'Not Started';
    } else if (value == 100) {
        status.value = 'Completed';
    } else {
        status.value = 'In Progress';
    }
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Topic';
    document.getElementById('formAction').value = 'add';
    document.getElementById('topicForm').reset();
    document.getElementById('topicId').value = '';
    updateProgress(0);
    document.getElementById('topicModal').classList.add('active');
}

function openEditModal(topic) {
    document.getElementById('modalTitle').textContent = 'Edit Topic';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('topicId').value = topic.id;
    document.getElementById('topic_name').value = topic.topic_name;
    document.getElementById('subject_category').value = topic.subject_category || '';
    document.getElementById('total_chapters').value = topic.total_chapters;
    document.getElementById('priority').value = topic.priority;
    document.getElementById('status').value = topic.status;
    document.getElementById('progress_percentage').value = topic.progress_percentage;
    updateProgress(topic.progress_percentage);
    document.getElementById('topicModal').classList.add('active');
}

function closeModal() {
    document.getElementById('topicModal').classList.remove('active');
}

function deleteTopic(id) {
    if (confirm('Are you sure you want to delete this topic?')) {
        document.getElementById('deleteTopicId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('topicModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
