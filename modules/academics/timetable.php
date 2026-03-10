<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'My Timetable';
$currentUser = getCurrentUser();

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $subject_id = (int)$_POST['subject_id'];
            $day_of_week = sanitizeInput($_POST['day_of_week']);
            $start_time = sanitizeInput($_POST['start_time']);
            $end_time = sanitizeInput($_POST['end_time']);
            $room_number = sanitizeInput($_POST['room_number']);
            $class_type = sanitizeInput($_POST['class_type']);
            
            $stmt = $conn->prepare("INSERT INTO timetable (user_id, subject_id, day_of_week, start_time, end_time, room_number, class_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $_SESSION['user_id'], $subject_id, $day_of_week, $start_time, $end_time, $room_number, $class_type);
            
            if ($stmt->execute()) {
                setFlashMessage("Class added to timetable!", "success");
            } else {
                setFlashMessage("Failed to add class.", "error");
            }
            $stmt->close();
            header("Location: timetable.php");
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $entry_id = (int)$_POST['entry_id'];
            $stmt = $conn->prepare("DELETE FROM timetable WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $entry_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            setFlashMessage("Class removed from timetable!", "success");
            header("Location: timetable.php");
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

// Fetch timetable
$stmt = $conn->prepare("
    SELECT t.*, s.subject_name, s.subject_code, s.color 
    FROM timetable t 
    JOIN subjects s ON t.subject_id = s.id 
    WHERE t.user_id = ? 
    ORDER BY 
        FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
        t.start_time
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$timetable_entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Organize timetable by day
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$timetable = [];
foreach ($days as $day) {
    $timetable[$day] = array_filter($timetable_entries, function($entry) use ($day) {
        return $entry['day_of_week'] == $day;
    });
}

closeDBConnection($conn);

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-calendar-alt"></i> My Timetable</h1>
            <p>Weekly class schedule</p>
        </div>
        <?php if (!empty($subjects)): ?>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Class
        </button>
        <?php endif; ?>
    </div>

    <?php if (empty($subjects)): ?>
    <div class="empty-state">
        <i class="fas fa-book"></i>
        <h3>No Subjects Found</h3>
        <p>Please add subjects first before creating your timetable</p>
        <a href="subjects.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Subjects
        </a>
    </div>
    <?php elseif (empty($timetable_entries)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3>No Classes Scheduled</h3>
        <p>Start building your weekly timetable</p>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add First Class
        </button>
    </div>
    <?php else: ?>
    <div class="timetable-view">
        <?php foreach ($days as $day): ?>
        <div class="day-column">
            <div class="day-header">
                <h3><?php echo $day; ?></h3>
                <span class="class-count"><?php echo count($timetable[$day]); ?> classes</span>
            </div>
            <div class="day-classes">
                <?php if (empty($timetable[$day])): ?>
                    <div class="no-class">No classes</div>
                <?php else: ?>
                    <?php foreach ($timetable[$day] as $entry): ?>
                    <div class="class-card" style="border-left: 4px solid <?php echo htmlspecialchars($entry['color']); ?>">
                        <div class="class-time">
                            <i class="fas fa-clock"></i>
                            <?php echo date('g:i A', strtotime($entry['start_time'])); ?> - 
                            <?php echo date('g:i A', strtotime($entry['end_time'])); ?>
                        </div>
                        <div class="class-subject"><?php echo htmlspecialchars($entry['subject_name']); ?></div>
                        <div class="class-details">
                            <span class="badge"><?php echo htmlspecialchars($entry['class_type']); ?></span>
                            <?php if ($entry['room_number']): ?>
                            <span><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($entry['room_number']); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="class-delete" onclick="deleteEntry(<?php echo $entry['id']; ?>)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Class Modal -->
<div id="classModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Class to Timetable</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="subject_id">Subject *</label>
                <select id="subject_id" name="subject_id" required>
                    <option value="">Select subject</option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?php echo $subject['id']; ?>">
                        <?php echo htmlspecialchars($subject['subject_name']); ?> (<?php echo htmlspecialchars($subject['subject_code']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="day_of_week">Day *</label>
                <select id="day_of_week" name="day_of_week" required>
                    <?php foreach ($days as $day): ?>
                    <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time">End Time *</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="class_type">Class Type *</label>
                    <select id="class_type" name="class_type" required>
                        <option value="Lecture">Lecture</option>
                        <option value="Lab">Lab</option>
                        <option value="Tutorial">Tutorial</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="room_number">Room Number</label>
                    <input type="text" id="room_number" name="room_number">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Class
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="entry_id" id="deleteEntryId">
</form>

<style>
.timetable-view {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.day-column {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.day-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 15px;
    text-align: center;
}

.day-header h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.class-count {
    font-size: 12px;
    opacity: 0.9;
}

.day-classes {
    padding: 10px;
    min-height: 100px;
}

.no-class {
    text-align: center;
    padding: 20px;
    color: var(--text-secondary);
    font-size: 14px;
}

.class-card {
    background: var(--light-color);
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
    position: relative;
    transition: all 0.3s;
}

.class-card:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow);
}

.class-time {
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 5px;
}

.class-subject {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.class-details {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--text-secondary);
}

.badge {
    background: var(--primary-color);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
}

.class-delete {
    position: absolute;
    top: 8px;
    right: 8px;
    background: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.3s;
    color: var(--danger-color);
}

.class-card:hover .class-delete {
    opacity: 1;
}

.class-delete:hover {
    background: var(--danger-color);
    color: white;
}

@media (max-width: 768px) {
    .timetable-view {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openAddModal() {
    document.getElementById('classModal').classList.add('active');
}

function closeModal() {
    document.getElementById('classModal').classList.remove('active');
}

function deleteEntry(id) {
    if (confirm('Remove this class from timetable?')) {
        document.getElementById('deleteEntryId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('classModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
