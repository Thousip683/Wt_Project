# Stage 4 - Career / Course Learning Module

## ✅ COMPLETED

### Database Schema
- **File:** `database/stage4_schema.sql`
- **Tables Created:**
  - `courses` — Track online courses with platform, category, hours, progress, color, URL
  - `course_topics` — Sections/topics within each course with individual progress
  - `learning_sessions` — Daily study time logs with productivity rating
  - `skills` — Skills tracker with proficiency levels and course linking
  - `projects` — Hands-on projects with tech stack, GitHub/live URLs
  - `career_goals` — Daily/weekly/monthly targets with progress tracking

### Pages Created

#### 1. My Courses (`modules/career_learning/my_courses.php`)
- Add/Edit/Delete courses
- Platform: Udemy, Coursera, edX, YouTube, LinkedIn Learning, Other
- Category: AI, ML, Data Science, Web Dev, DSA, DevOps, Cybersecurity, Other
- Progress bar with hours completed / total hours
- Target date with overdue/soon indicators
- Quick links to Topics, Sessions, Projects per course
- 4-stat summary (Total, In Progress, Completed, Hours Learned)

#### 2. Course Topics (`modules/career_learning/course_topics.php`)
- Scoped per course (via `?course_id=`)
- Add/Edit/Delete topics with section number and duration
- Auto-recalculates course overall progress from topic averages
- Visual progress per topic
- Breadcrumb navigation

#### 3. Learning Sessions (`modules/career_learning/learning_sessions.php`)
- Log daily learning time with productivity rating (High/Medium/Low)
- Color-coded session cards by productivity
- Stats: total sessions, total hours, this week's hours
- Automatically updates `hours_completed` on the linked course
- Can be filtered by course via URL param

#### 4. Skills Tracker (`modules/career_learning/skills.php`)
- Track skills: Programming Language, Framework, Tool, Concept, Soft Skill
- Proficiency: Beginner → Intermediate → Advanced → Expert
- Category-specific icons
- Optional course linking
- Stats: total, advanced, expert count

#### 5. Projects (`modules/career_learning/projects.php`)
- Add projects with tech stack (comma-separated → auto-tagged)
- GitHub URL and Live URL buttons
- Status: Planning / In Progress / Completed / On Hold
- Optional course linking
- Stats: total, in progress, completed

#### 6. Goals (`modules/career_learning/goals.php`)
- Tabbed interface: Daily / Weekly / Monthly
- Units: Hours, Topics, Courses, Projects
- Quick progress update (+N button)
- Auto-completes goals when target reached
- Visual progress bars + days remaining

### Navigation Integration
- Added **"Career Learning"** dropdown in header with links to:
  My Courses, Sessions, Skills, Projects, Goals

### Dashboard Integration
- New **"Courses Active"** stat card (shows in-progress count + weekly hours)
- 4 new quick-action links: My Courses, Skills Tracker, My Projects, Career Goals
- **"In-Progress Courses"** widget showing up to 3 active courses with progress bars

### Test Data
- **File:** `database/test_data_stage4.sql`
- 3 sample courses (2 in progress, 1 completed)
- 12 course topics across 2 courses
- 8 learning sessions with varied productivity
- 6 skills (Python, scikit-learn, JS, React, Node.js, SQL)
- 3 projects (1 in progress, 2 completed)
- 6 career goals (daily/weekly/monthly mix)

---

## 📋 Setup Instructions

### 1. Import Schema
```bash
mysql -u root -p beyond_classroom < database/stage4_schema.sql
```

### 2. Load Test Data (optional)
```bash
mysql -u root -p beyond_classroom < database/test_data_stage4.sql
```

### 3. Start Server
```bash
cd /home/thousip/beyond-classroom
php -S localhost:8000
```

### 4. Test
- Visit `http://localhost:8000`
- Log in → look for **Career Learning** in the nav
- Click **My Courses** to start

---

## 📁 File Structure

```
modules/career_learning/
├── my_courses.php
├── course_topics.php
├── learning_sessions.php
├── skills.php
├── projects.php
└── goals.php

database/
├── stage4_schema.sql
└── test_data_stage4.sql

Modified files:
├── includes/functions.php   — Added getCareerStats()
├── includes/header.php      — Added Career Learning dropdown
└── dashboard.php            — Career stats, courses widget, quick links
```

---

## ✨ Next Steps

**Stage 5:** Intelligent Recommendation System  
**Stage 6:** Final Polish & Unified Dashboard

**Status:** Stage 4 Complete ✅
