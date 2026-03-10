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

$pageTitle = 'Practice Tests - ' . $exam['exam_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $test_name = sanitizeInput($_POST['test_name']);
            $test_date = sanitizeInput($_POST['test_date']);
            $total_marks = (int)$_POST['total_marks'];
            $obtained_marks = (int)$_POST['obtained_marks'];
            $time_taken_minutes = (int)$_POST['time_taken_minutes'];
            $accuracy_percentage = (float)$_POST['accuracy_percentage'];
            $analysis_notes = sanitizeInput($_POST['analysis_notes']);
            
            $percentage = $total_marks > 0 ? round(($obtained_marks / $total_marks) * 100, 2) : 0;
            
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO practice_tests (user_id, exam_id, test_name, test_date, total_marks, obtained_marks, percentage, time_taken_minutes, accuracy_percentage, analysis_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissiiidds", $_SESSION['user_id'], $exam_id, $test_name, $test_date, $total_marks, $obtained_marks, $percentage, $time_taken_minutes, $accuracy_percentage, $analysis_notes);
                $message = "Practice test recorded!";
            } else {
                $test_id = (int)$_POST['test_id'];
                $stmt = $conn->prepare("UPDATE practice_tests SET test_name = ?, test_date = ?, total_marks = ?, obtained_marks = ?, percentage = ?, time_taken_minutes = ?, accuracy_percentage = ?, analysis_notes = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ssiididsii", $test_name, $test_date, $total_marks, $obtained_marks, $percentage, $time_taken_minutes, $accuracy_percentage, $analysis_notes, $test_id, $_SESSION['user_id']);
                $message = "Test updated!";
            }
            
            if ($stmt->execute()) {
                setFlashMessage($message, "success");
            } else {
                setFlashMessage("Failed to save test.", "error");
            }
            $stmt->close();
            header("Location: practice_tests.php?exam_id=" . $exam_id);
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $test_id = (int)$_POST['test_id'];
            $stmt = $conn->prepare("DELETE FROM practice_tests WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $test_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Test deleted!", "success");
            header("Location: practice_tests.php?exam_id=" . $exam_id);
            exit();
        }
    }
}

// Fetch practice tests
$stmt = $conn->prepare("SELECT * FROM practice_tests WHERE exam_id = ? ORDER BY test_date DESC");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$total_tests = count($tests);
$avg_percentage = $total_tests > 0 ? round(array_sum(array_column($tests, 'percentage')) / $total_tests, 1) : 0;
$avg_accuracy = $total_tests > 0 ? round(array_sum(array_column($tests, 'accuracy_percentage')) / $total_tests, 1) : 0;

// Find highest score
$highest_score = $total_tests > 0 ? max(array_column($tests, 'percentage')) : 0;

// Check if improving (last 3 tests trend)
$recent_tests = array_slice($tests, 0, 3);
$is_improving = false;
if (count($recent_tests) >= 2) {
    $recent_percentages = array_column($recent_tests, 'percentage');
    $is_improving = $recent_percentages[0] > $recent_percentages[count($recent_percentages) - 1];
}

closeDBConnection($conn);

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="my_exams.php"><i class="fas fa-trophy"></i> My Exams</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($exam['exam_name']); ?> - Practice Tests</span>
    </div>

    <div class="page-header">
        <div>
            <h1><i class="fas fa-file-alt"></i> Practice Tests</h1>
            <p>Track your mock test performance</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Test
        </button>
    </div>

    <!-- Statistics -->
    <div class="stats-overview">
        <div class="stat-box">
            <i class="fas fa-file-alt"></i>
            <div>
                <span class="stat-number"><?php echo $total_tests; ?></span>
                <span class="stat-label">Total Tests</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-percentage"></i>
            <div>
                <span class="stat-number"><?php echo $avg_percentage; ?>%</span>
                <span class="stat-label">Average Score</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-bullseye"></i>
            <div>
                <span class="stat-number"><?php echo $avg_accuracy; ?>%</span>
                <span class="stat-label">Average Accuracy</span>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-trophy"></i>
            <div>
                <span class="stat-number"><?php echo $highest_score; ?>%</span>
                <span class="stat-label">Highest Score</span>
            </div>
        </div>
    </div>

    <?php if ($is_improving): ?>
    <div class="improvement-badge">
        <i class="fas fa-chart-line"></i>
        <span>You're improving! Keep it up!</span>
    </div>
    <?php endif; ?>

    <?php if (empty($tests)): ?>
    <div class="empty-state">
        <i class="fas fa-file-alt"></i>
        <h3>No Practice Tests Recorded</h3>
        <p>Start tracking your mock test scores and analyze your performance</p>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add First Test
        </button>
    </div>
    <?php else: ?>
    <div class="tests-list">
        <?php foreach ($tests as $test): 
            $score_class = $test['percentage'] >= 75 ? 'excellent' : ($test['percentage'] >= 50 ? 'good' : 'needs-improvement');
        ?>
        <div class="test-card score-<?php echo $score_class; ?>">
            <div class="test-header">
                <div>
                    <h3><?php echo htmlspecialchars($test['test_name']); ?></h3>
                    <div class="test-date">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d M Y', strtotime($test['test_date'])); ?>
                    </div>
                </div>
                <div class="test-actions">
                    <button class="btn-icon" onclick='openEditModal(<?php echo json_encode($test); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteTest(<?php echo $test['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="test-score-display">
                <div class="score-circle score-<?php echo $score_class; ?>">
                    <div class="score-value"><?php echo $test['percentage']; ?>%</div>
                    <div class="score-label"><?php echo $test['obtained_marks']; ?>/<?php echo $test['total_marks']; ?></div>
                </div>
                
                <div class="test-metrics">
                    <div class="metric">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $test['time_taken_minutes']; ?> min</span>
                    </div>
                    <div class="metric">
                        <i class="fas fa-bullseye"></i>
                        <span><?php echo $test['accuracy_percentage']; ?>% accuracy</span>
                    </div>
                </div>
            </div>
            
            <div class="performance-indicator">
                <?php if ($test['percentage'] >= 75): ?>
                    <span class="badge-excellent"><i class="fas fa-star"></i> Excellent</span>
                <?php elseif ($test['percentage'] >= 50): ?>
                    <span class="badge-good"><i class="fas fa-thumbs-up"></i> Good</span>
                <?php else: ?>
                    <span class="badge-improve"><i class="fas fa-chart-line"></i> Keep Practicing</span>
                <?php endif; ?>
            </div>
            
            <?php if ($test['analysis_notes']): ?>
            <div class="test-analysis">
                <strong>Analysis & Notes:</strong>
                <p><?php echo nl2br(htmlspecialchars($test['analysis_notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Test Modal -->
<div id="testModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">Add Practice Test</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" id="testForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="test_id" id="testId">
            
            <div class="form-group">
                <label for="test_name">Test Name *</label>
                <input type="text" id="test_name" name="test_name" placeholder="E.g., JEE Mock Test 1, Full Length Test" required>
            </div>
            
            <div class="form-group">
                <label for="test_date">Test Date *</label>
                <input type="date" id="test_date" name="test_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="total_marks">Total Marks *</label>
                    <input type="number" id="total_marks" name="total_marks" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="obtained_marks">Obtained Marks *</label>
                    <input type="number" id="obtained_marks" name="obtained_marks" min="0" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="time_taken_minutes">Time Taken (minutes) *</label>
                    <input type="number" id="time_taken_minutes" name="time_taken_minutes" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="accuracy_percentage">Accuracy % *</label>
                    <input type="number" id="accuracy_percentage" name="accuracy_percentage" min="0" max="100" step="0.1" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="analysis_notes">Analysis & Notes</label>
                <textarea id="analysis_notes" name="analysis_notes" rows="4" placeholder="What went well? What topics need more practice? Any strategies to improve?"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Test
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="test_id" id="deleteTestId">
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

.improvement-badge {
    background: linear-gradient(135deg, var(--success-color), #059669);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    box-shadow: var(--shadow);
}

.improvement-badge i {
    font-size: 24px;
}

.tests-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.test-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary-color);
}

.test-card.score-excellent {
    border-left-color: var(--success-color);
}

.test-card.score-good {
    border-left-color: var(--warning-color);
}

.test-card.score-needs-improvement {
    border-left-color: var(--danger-color);
}

.test-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
}

.test-header h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.test-date {
    font-size: 14px;
    color: var(--text-secondary);
}

.test-score-display {
    display: flex;
    align-items: center;
    gap: 30px;
    margin-bottom: 20px;
}

.score-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 6px solid;
}

.score-circle.score-excellent {
    border-color: var(--success-color);
    background: rgba(16, 185, 129, 0.1);
}

.score-circle.score-good {
    border-color: var(--warning-color);
    background: rgba(245, 158, 11, 0.1);
}

.score-circle.score-needs-improvement {
    border-color: var(--danger-color);
    background: rgba(239, 68, 68, 0.1);
}

.score-value {
    font-size: 32px;
    font-weight: 700;
}

.score-circle.score-excellent .score-value {
    color: var(--success-color);
}

.score-circle.score-good .score-value {
    color: var(--warning-color);
}

.score-circle.score-needs-improvement .score-value {
    color: var(--danger-color);
}

.score-label {
    font-size: 14px;
    color: var(--text-secondary);
    margin-top: 4px;
}

.test-metrics {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.metric {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    color: var(--text-secondary);
}

.metric i {
    color: var(--primary-color);
    font-size: 18px;
    width: 24px;
}

.performance-indicator {
    padding: 12px 0;
    border-top: 1px solid var(--light-color);
    border-bottom: 1px solid var(--light-color);
    margin-bottom: 15px;
}

.badge-excellent, .badge-good, .badge-improve {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.badge-excellent {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.badge-good {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
}

.badge-improve {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
}

.test-analysis {
    background: var(--light-color);
    padding: 15px;
    border-radius: 6px;
    font-size: 14px;
}

.test-analysis strong {
    display: block;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.test-analysis p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.6;
}
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Practice Test';
    document.getElementById('formAction').value = 'add';
    document.getElementById('testForm').reset();
    document.getElementById('testId').value = '';
    document.getElementById('test_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('testModal').classList.add('active');
}

function openEditModal(test) {
    document.getElementById('modalTitle').textContent = 'Edit Practice Test';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('testId').value = test.id;
    document.getElementById('test_name').value = test.test_name;
    document.getElementById('test_date').value = test.test_date;
    document.getElementById('total_marks').value = test.total_marks;
    document.getElementById('obtained_marks').value = test.obtained_marks;
    document.getElementById('time_taken_minutes').value = test.time_taken_minutes;
    document.getElementById('accuracy_percentage').value = test.accuracy_percentage;
    document.getElementById('analysis_notes').value = test.analysis_notes || '';
    document.getElementById('testModal').classList.add('active');
}

function closeModal() {
    document.getElementById('testModal').classList.remove('active');
}

function deleteTest(id) {
    if (confirm('Are you sure you want to delete this practice test?')) {
        document.getElementById('deleteTestId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('testModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Auto-calculate percentage display (visual feedback)
document.getElementById('obtained_marks').addEventListener('input', updatePercentage);
document.getElementById('total_marks').addEventListener('input', updatePercentage);

function updatePercentage() {
    const obtained = parseFloat(document.getElementById('obtained_marks').value) || 0;
    const total = parseFloat(document.getElementById('total_marks').value) || 1;
    const percentage = Math.round((obtained / total) * 100);
    
    // Could add a live percentage display here if needed
}
</script>

<?php include '../../includes/footer.php'; ?>
