<?php
include("../config/db.php");

/* Check login */
if(!isset($_SESSION['uid']) || $_SESSION['role'] != 'faculty'){
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];

/* Get user info */
$user = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'")
);

/* =====================
   ANALYTICS FUNCTIONS
===================== */

function getFacultyAnalytics($conn, $uid) {
    /* Get all resources uploaded by this faculty */
    $resources = mysqli_query($conn, "SELECT * FROM resources WHERE faculty_id='$uid'");
    
    $analytics = [
        'total_resources' => 0,
        'total_feedback_received' => 0,
        'resources_by_subject' => [],
        'feedback_by_status' => [
            'replied' => 0,
            'pending' => 0
        ]
    ];
    
    $analytics['total_resources'] = mysqli_num_rows($resources);
    
    /* Get feedback on resources */
    $feedback = mysqli_query($conn, "
        SELECT sr.subject, f.faculty_reply, f.id
        FROM feedback f
        JOIN student_requests sr ON f.request_id = sr.id
        WHERE sr.subject IN (SELECT DISTINCT subject FROM resources WHERE faculty_id='$uid')
    ");
    
    while($row = mysqli_fetch_assoc($feedback)) {
        $analytics['total_feedback_received']++;
        
        if($row['faculty_reply']) {
            $analytics['feedback_by_status']['replied']++;
        } else {
            $analytics['feedback_by_status']['pending']++;
        }
    }
    
    /* Resources by subject */
    $resources = mysqli_query($conn, "
        SELECT subject, COUNT(*) as count FROM resources 
        WHERE faculty_id='$uid' 
        GROUP BY subject
    ");
    
    while($row = mysqli_fetch_assoc($resources)) {
        $analytics['resources_by_subject'][$row['subject']] = $row['count'];
    }
    
    return $analytics;
}

function getStudentImpactAnalytics($conn, $uid) {
    /* Get all students who received resources from this faculty */
    $students = mysqli_query($conn, "
        SELECT DISTINCT sr.user_id, u.name, 
               COUNT(sr.id) as requests,
               AVG(p.final_score) as avg_score
        FROM student_requests sr
        JOIN users u ON sr.user_id = u.id
        LEFT JOIN performance p ON u.id = p.user_id
        WHERE sr.subject IN (SELECT DISTINCT subject FROM resources WHERE faculty_id='$uid')
        GROUP BY sr.user_id
        ORDER BY requests DESC
    ");
    
    $impact = [
        'total_students' => 0,
        'students_data' => []
    ];
    
    while($row = mysqli_fetch_assoc($students)) {
        $impact['total_students']++;
        $impact['students_data'][] = $row;
    }
    
    return $impact;
}

function getResourcePerformance($conn, $uid) {
    $result = mysqli_query($conn, "
        SELECT 
            r.subject,
            r.sub_topic,
            COUNT(sr.id) as views,
            COUNT(f.id) as feedback_count
        FROM resources r
        LEFT JOIN student_requests sr ON r.subject = sr.subject AND r.sub_topic = sr.weak_topic
        LEFT JOIN feedback f ON sr.id = f.request_id
        WHERE r.faculty_id = '$uid'
        GROUP BY r.subject, r.sub_topic
        ORDER BY views DESC
    ");
    
    $performance = [];
    while($row = mysqli_fetch_assoc($result)) {
        $performance[] = $row;
    }
    
    return $performance;
}

/* Get all analytics data */
$faculty_analytics = getFacultyAnalytics($conn, $uid);
$student_impact = getStudentImpactAnalytics($conn, $uid);
$resource_performance = getResourcePerformance($conn, $uid);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Analytics - Faculty</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
    * {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    body {
        background: #f8fafc;
    }
</style>

</head>

<body class="min-h-screen flex">

<!-- Sidebar -->
<aside class="w-64 bg-white border-r border-slate-200 sticky top-0 h-screen flex flex-col p-6">

    <div class="flex items-center px-2 py-3 gap-3 font-bold text-xl mb-8">
        <div class="bg-indigo-600 text-white p-2 rounded-lg text-sm">E</div>
        EduSmart
    </div>

    <nav class="flex-1 space-y-2">

        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition">
            Dashboard
        </a>

        <a href="resource_feedback.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition">
            Student Feedback
        </a>

        <a href="analytics.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition">
            Analytics
        </a>

    </nav>

    <div class="p-4 bg-slate-900 rounded-2xl text-white">
        <p class="text-xs text-slate-400 mb-1">Current Role</p>
        <p class="font-bold text-sm">Faculty</p>
    </div>

</aside>

<!-- Main Content -->
<main class="flex-1 p-8">

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-4xl font-bold text-slate-800">Analytics Dashboard</h1>
        <a href="dashboard.php" class="text-indigo-600 hover:underline font-medium">← Back</a>
    </div>
    <p class="text-slate-500 mb-8">Your resource management and student impact overview</p>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-600 text-sm font-medium mb-2">Resources Uploaded</p>
            <p class="text-4xl font-bold text-indigo-600"><?php echo $faculty_analytics['total_resources']; ?></p>
            <p class="text-xs text-slate-400 mt-2">learning materials</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-600 text-sm font-medium mb-2">Feedback Received</p>
            <p class="text-4xl font-bold text-green-600"><?php echo $faculty_analytics['total_feedback_received']; ?></p>
            <p class="text-xs text-slate-400 mt-2">student responses</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-600 text-sm font-medium mb-2">Students Helped</p>
            <p class="text-4xl font-bold text-blue-600"><?php echo $student_impact['total_students']; ?></p>
            <p class="text-xs text-slate-400 mt-2">learners supported</p>
        </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
                <p class="text-slate-600 text-sm font-medium mb-2">Replies Sent</p>
                <p class="text-4xl font-bold text-purple-600"><?php echo $faculty_analytics['feedback_by_status']['replied']; ?></p>
                <p class="text-xs text-slate-400 mt-2">feedback provided</p>
            </div>

        </div>

        <!-- Feedback Status -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border border-slate-200">
            <h2 class="text-2xl font-bold mb-6 text-slate-800">Feedback Management</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <h3 class="font-semibold text-slate-700 mb-4">Feedback Status</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                            <span class="text-slate-600 font-medium">Replied</span>
                            <span class="font-bold text-lg text-green-600"><?php echo $faculty_analytics['feedback_by_status']['replied']; ?></span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $faculty_analytics['total_feedback_received'] > 0 ? ($faculty_analytics['feedback_by_status']['replied'] / $faculty_analytics['total_feedback_received'] * 100) : 0; ?>%"></div>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                            <span class="text-slate-600 font-medium">Pending</span>
                            <span class="font-bold text-lg text-orange-600"><?php echo $faculty_analytics['feedback_by_status']['pending']; ?></span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-orange-600 h-2 rounded-full" style="width: <?php echo $faculty_analytics['total_feedback_received'] > 0 ? ($faculty_analytics['feedback_by_status']['pending'] / $faculty_analytics['total_feedback_received'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-slate-700 mb-4">Resources by Subject</h3>
                    <div class="space-y-2">
                        <?php if(count($faculty_analytics['resources_by_subject']) > 0): ?>
                            <?php foreach($faculty_analytics['resources_by_subject'] as $subject => $count): ?>
                                <div class="flex justify-between items-center p-2 bg-slate-50 rounded">
                                    <span class="text-slate-700 font-medium text-sm"><?php echo htmlspecialchars($subject); ?></span>
                                    <span class="font-bold text-indigo-600"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-slate-400 text-center py-6">No resources uploaded yet</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Resource Performance -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border border-slate-200">
            <h2 class="text-2xl font-bold mb-6 text-slate-800">Resource Performance</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 sticky top-0 border-b border-slate-200">
                        <tr>
                            <th class="p-4 text-left font-semibold text-slate-700">Subject</th>
                            <th class="p-4 text-left font-semibold text-slate-700">Topic</th>
                            <th class="p-4 text-center font-semibold text-slate-700">Views</th>
                            <th class="p-4 text-center font-semibold text-slate-700">Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($resource_performance) > 0): ?>
                            <?php foreach($resource_performance as $perf): ?>
                                <tr class="border-b hover:bg-slate-50 transition-colors">
                                    <td class="p-4 text-slate-700"><?php echo htmlspecialchars($perf['subject']); ?></td>
                                    <td class="p-4 text-slate-700"><?php echo htmlspecialchars($perf['sub_topic']); ?></td>
                                    <td class="p-4 text-center font-bold text-indigo-600"><?php echo $perf['views']; ?></td>
                                    <td class="p-4 text-center font-bold text-green-600"><?php echo $perf['feedback_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400">No resources data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Students Impacted -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200">
            <h2 class="text-2xl font-bold mb-6 text-slate-800">Top Students Helped</h2>
            <div class="space-y-3">
                <?php if(count($student_impact['students_data']) > 0): ?>
                    <?php foreach(array_slice($student_impact['students_data'], 0, 10) as $student): ?>
                        <div class="flex justify-between items-center p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                            <div>
                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($student['name']); ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?php echo $student['requests']; ?> requests</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-blue-600 text-sm"><?php echo $student['avg_score'] ? round($student['avg_score']) . '%' : 'N/A'; ?></p>
                                <p class="text-xs text-slate-500">Avg Score</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-slate-400 text-center py-8">No student data available yet</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

</body>

</html>
