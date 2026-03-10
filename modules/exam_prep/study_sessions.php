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

$pageTitle = 'Study Sessions - ' . $exam['exam_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $topic_id = !empty($_POST['topic_id']) ? (int)$_POST['topic_id'] : NULL;
            $session_date = sanitizeInput($_POST['session_date']);
            $duration_minutes = (int)$_POST['duration_minutes'];
            $topics_covered = sanitizeInput($_POST['topics_covered']);
            $notes = sanitizeInput($_POST['notes']);
            $productivity_rating = sanitizeInput($_POST['productivity_rating']);
            
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO study_sessions (user_id, exam_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisisss", $_SESSION['user_id'], $exam_id, $topic_id, $session_date, $duration_minutes, $topics_covered, $notes, $productivity_rating);
                $message = "Study session logged!";
            } else {
                $session_id = (int)$_POST['session_id'];
                $stmt = $conn->prepare("UPDATE study_sessions SET topic_id = ?, session_date = ?, duration_minutes = ?, topics_covered = ?, notes = ?, productivity_rating = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("isisssii", $topic_id, $session_date, $duration_minutes, $topics_covered, $notes, $productivity_rating, $session_id, $_SESSION['user_id']);
                $message = "Session updated!";
            }
            
            if ($stmt->execute()) {
                setFlashMessage($message, "success");
            } else {
                setFlashMessage("Failed to save session.", "error");
            }
            $stmt->close();
            header("Location: study_sessions.php?exam_id=" . $exam_id);
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $session_id = (int)$_POST['session_id'];
            $stmt = $conn->prepare("DELETE FROM study_sessions WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $session_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Session deleted!", "success");
            header("Location: study_sessions.php?exam_id=" . $exam_id);
            exit();
        }
    }
}

// Fetch topics for dropdown
$stmt = $conn->prepare("SELECT id, topic_name FROM exam_topics WHERE exam_id = ? ORDER BY topic_name");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch study sessions
$stmt = $conn->prepare("
    SELECT ss.*, et.topic_name 
    FROM study_sessions ss
    LEFT JOIN exam_topics et ON ss.topic_id = et.id
    WHERE ss.exam_id = ?
    ORDER BY ss.session_date DESC
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$total_sessions = count($sessions);
$total_hours = array_sum(array_column($sessions, 'duration_minutes')) / 60;
$this_week_sessions = array_filter($sessions, function($s) {
    return strtotime($s['session_date']) >= strtotime('monday this week');
});
$this_week_hours = array_sum(array_column($this_week_sessions, 'duration_minutes')) / 60;

closeDBConnection($conn);

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="my_exams.php"><i class="fas fa-trophy"></i> My Exams</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($exam['exam_name']); ?> - Study Sessions</span>
    </div>

    <div class="page-header">
        <div>
            <h1><i class="fas fa-pen"></i> Study Sessions</h1>
            <p>Track your study time and progress</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Log Session
        </button>
    </div>

    <!-- Statistics -->
    <div class="stats-overview">
        <div class="stat-box">
            <i class="fas fa-book-reader"></i>
            <div>
                <span class="stat-number"><?php echo $total_sessions; ?></span>
                <span class="stat-label">Total Sessions</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-clock"></i>
            <div>
                <span class="stat-number"><?php echo round($total_hours, 1); ?>h</span>
                <span class="stat-label">Total Study Time</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-calendar-week"></i>
            <div>
                <span class="stat-number"><?php echo count($this_week_sessions); ?></span>
                <span class="stat-label">This Week</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-hourglass-half"></i>
            <div>
                <span class="stat-number"><?php echo round($this_week_hours, 1); ?>h</span>
                <span class="stat-label">This Week Hours</span>
            </div>
        </div>
    </div>

    <?php if (empty($sessions)): ?>
    <div class="empty-state">
        <i class="fas fa-book-reader"></i>
        <h3>No Study Sessions Logged</h3>
        <p>Start tracking your study time</p>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Log First Session
        </button>
    </div>
    <?php else: ?>
    <div class="sessions-list">
        <?php foreach ($sessions as $session): ?>
        <div class="session-card productivity-<?php echo strtolower($session['productivity_rating']); ?>">
            <div class="session-header">
                <div>
                    <div class="session-date">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d M Y', strtotime($session['session_date'])); ?>
                    </div>
                    <?php if ($session['topic_name']): ?>
                    <div class="session-topic"><?php echo htmlspecialchars($session['topic_name']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="session-actions">
                    <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($session); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteSession(<?php echo $session['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="session-details">
                <div class="detail-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo $session['duration_minutes']; ?> minutes (<?php echo round($session['duration_minutes'] / 60, 1); ?>h)</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-chart-line"></i>
                    <span class="productivity-<?php echo strtolower($session['productivity_rating']); ?>">
                        <?php echo $session['productivity_rating']; ?> Productivity
                    </span>
                </div>
            </div>
            
            <?php if ($session['topics_covered']): ?>
            <div class="session-topics">
                <strong>Topics Covered:</strong>
                <p><?php echo nl2br(htmlspecialchars($session['topics_covered'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($session['notes']): ?>
            <div class="session-notes">
                <strong>Notes:</strong>
                <p><?php echo nl2br(htmlspecialchars($session['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Session Modal -->
<div id="sessionModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">Log Study Session</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="sessionForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="session_id" id="sessionId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="session_date">Session Date *</label>
                    <input type="date" id="session_date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="duration_minutes">Duration (minutes) *</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="topic_id">Related Topic</label>
                    <select id="topic_id" name="topic_id">
                        <option value="">Select topic (optional)</option>
                        <?php foreach ($topics as $topic): ?>
                        <option value="<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['topic_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="productivity_rating">Productivity *</label>
                    <select id="productivity_rating" name="productivity_rating" required>
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="topics_covered">Topics Covered</label>
                <textarea id="topics_covered" name="topics_covered" rows="2" placeholder="What topics did you study?"></textarea>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Any important notes, doubts, or observations"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Session
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="session_id" id="deleteSessionId">
</form>

<style>
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-box i {
    font-size: 32px;
    color: var(--primary-color);
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
}

.stat-label {
    display: block;
    font-size: 13px;
    color: var(--text-secondary);
}

.sessions-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.session-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary-color);
}

.session-card.productivity-high {
    border-left-color: var(--success-color);
}

.session-card.productivity-low {
    border-left-color: var(--danger-color);
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.session-date {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 5px;
}

.session-topic {
    color: var(--primary-color);
    font-size: 14px;
}

.session-details {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--text-secondary);
}

.productivity-high {
    color: var(--success-color) !important;
    font-weight: 600;
}

.productivity-medium {
    color: var(--warning-color) !important;
    font-weight: 600;
}

.productivity-low {
    color: var(--danger-color) !important;
    font-weight: 600;
}

.session-topics, .session-notes {
    background: var(--light-color);
    padding: 12px;
    border-radius: 6px;
    margin-top: 10px;
    font-size: 14px;
}

.session-topics strong, .session-notes strong {
    display: block;
    margin-bottom: 5px;
    color: var(--text-primary);
}

.session-topics p, .session-notes p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.5;
}
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Log Study Session';
    document.getElementById('formAction').value = 'add';
    document.getElementById('sessionForm').reset();
    document.getElementById('sessionId').value = '';
    document.getElementById('session_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('sessionModal').classList.add('active');
}

function openEditModal(session) {
    document.getElementById('modalTitle').textContent = 'Edit Study Session';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('sessionId').value = session.id;
    document.getElementById('session_date').value = session.session_date;
    document.getElementById('duration_minutes').value = session.duration_minutes;
    document.getElementById('topic_id').value = session.topic_id || '';
    document.getElementById('productivity_rating').value = session.productivity_rating;
    document.getElementById('topics_covered').value = session.topics_covered || '';
    document.getElementById('notes').value = session.notes || '';
    document.getElementById('sessionModal').classList.add('active');
}

function closeModal() {
    document.getElementById('sessionModal').classList.remove('active');
}

function deleteSession(id) {
    if (confirm('Are you sure you want to delete this study session?')) {
        document.getElementById('deleteSessionId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('sessionModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
