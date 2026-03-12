<?php
include("../config/db.php");

/* Check login */
if(!isset($_SESSION['uid']) || $_SESSION['role'] != 'student'){
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

function getStudyAnalytics($conn, $uid) {
    $result = mysqli_query($conn, "SELECT * FROM performance WHERE user_id='$uid'");
    
    $analytics = [
        'total_tests' => 0,
        'total_hours' => 0,
        'avg_score' => 0,
        'best_score' => 0,
        'worst_score' => 100,
        'performance_data' => []
    ];
    
    $scores = [];
    while($row = mysqli_fetch_assoc($result)) {
        $analytics['total_tests']++;
        $analytics['total_hours'] += $row['study_hours'];
        $scores[] = $row['final_score'];
        $analytics['best_score'] = max($analytics['best_score'], $row['final_score']);
        $analytics['worst_score'] = min($analytics['worst_score'], $row['final_score']);
        $analytics['performance_data'][] = $row;
    }
    
    if(count($scores) > 0) {
        $analytics['avg_score'] = round(array_sum($scores) / count($scores));
    }
    
    return $analytics;
}

function getResourceAnalytics($conn, $uid) {
    /* Get feedback/requests data */
    $result = mysqli_query($conn, "
        SELECT sr.id, sr.subject, sr.weak_topic, sr.created_at, f.id as feedback_id
        FROM student_requests sr
        LEFT JOIN feedback f ON sr.id = f.request_id
        WHERE sr.user_id = '$uid'
        ORDER BY sr.created_at DESC
    ");
    
    $analytics = [
        'total_requests' => 0,
        'subjects_requested' => [],
        'topics_requested' => [],
        'total_feedback_given' => 0,
        'pending_resources' => 0
    ];
    
    while($row = mysqli_fetch_assoc($result)) {
        $analytics['total_requests']++;
        
        $subject = $row['subject'];
        if(!isset($analytics['subjects_requested'][$subject])) {
            $analytics['subjects_requested'][$subject] = 0;
        }
        $analytics['subjects_requested'][$subject]++;
        
        $topic = $row['weak_topic'];
        if(!isset($analytics['topics_requested'][$topic])) {
            $analytics['topics_requested'][$topic] = 0;
        }
        $analytics['topics_requested'][$topic]++;
        
        if($row['feedback_id']) {
            $analytics['total_feedback_given']++;
        } else {
            $analytics['pending_resources']++;
        }
    }
    
    return $analytics;
}

function getProgressMetrics($conn, $uid) {
    $perf_result = mysqli_query($conn, "
        SELECT final_score FROM performance WHERE user_id='$uid' 
        ORDER BY id DESC LIMIT 10
    ");
    
    $recent_scores = [];
    while($row = mysqli_fetch_assoc($perf_result)) {
        $recent_scores[] = intval($row['final_score']);
    }
    
    $trend = 'stable';
    if(count($recent_scores) > 1) {
        $avg_first_half = array_sum(array_slice($recent_scores, 0, ceil(count($recent_scores)/2))) / ceil(count($recent_scores)/2);
        $avg_second_half = array_sum(array_slice($recent_scores, ceil(count($recent_scores)/2))) / floor(count($recent_scores)/2);
        
        if($avg_second_half > $avg_first_half + 5) {
            $trend = 'improving';
        } elseif($avg_second_half < $avg_first_half - 5) {
            $trend = 'declining';
        }
    }
    
    return [
        'recent_scores' => array_reverse($recent_scores),
        'trend' => $trend
    ];
}

/* Get all analytics data */
$study_analytics = getStudyAnalytics($conn, $uid);
$resource_analytics = getResourceAnalytics($conn, $uid);
$progress = getProgressMetrics($conn, $uid);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Analytics - EduSmart</title>

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

        <a href="enter_marks.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition">
            Enter Marks
        </a>

        <a href="resources.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition">
            Learning Resources
        </a>

        <a href="analytics.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition">
            Analytics
        </a>

    </nav>

    <div class="p-4 bg-slate-900 rounded-2xl text-white">
        <p class="text-xs text-slate-400 mb-1">Current Role</p>
        <p class="font-bold text-sm">Student</p>
    </div>

</aside>

<!-- Main Content -->
<main class="flex-1 lg:ml-0 p-8">

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-4xl font-bold text-slate-800">Analytics Dashboard</h1>
        <a href="dashboard.php" class="text-indigo-600 hover:underline font-medium">← Back</a>
    </div>
    <p class="text-slate-500 mb-8">Your comprehensive learning analytics</p>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-600 text-sm font-medium mb-2">Tests Completed</p>
            <p class="text-4xl font-bold text-indigo-600"><?php echo $study_analytics['total_tests']; ?></p>
            <p class="text-xs text-slate-400 mt-2">assessments taken</p>
        </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
                <p class="text-slate-600 text-sm font-medium mb-2">Study Hours</p>
                <p class="text-4xl font-bold text-green-600"><?php echo round($study_analytics['total_hours'], 1); ?></p>
                <p class="text-xs text-slate-400 mt-2">hours invested</p>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
                <p class="text-slate-600 text-sm font-medium mb-2">Average Score</p>
                <p class="text-4xl font-bold text-blue-600"><?php echo $study_analytics['avg_score']; ?>%</p>
                <p class="text-xs text-slate-400 mt-2">overall performance</p>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
                <p class="text-slate-600 text-sm font-medium mb-2">Best Score</p>
                <p class="text-4xl font-bold text-purple-600"><?php echo $study_analytics['best_score']; ?>%</p>
                <p class="text-xs text-slate-400 mt-2">highest achievement</p>
            </div>

        </div>

        <!-- Performance Trend -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border border-slate-200">
            <h2 class="text-2xl font-bold mb-6 text-slate-800">Performance Trend</h2>
            <div class="flex items-center justify-between mb-6">
                <p class="text-slate-600">Recent test scores progression</p>
                <span class="px-4 py-2 rounded-full text-sm font-medium <?php 
                    echo $progress['trend'] == 'improving' ? 'bg-green-100 text-green-700' : 
                         ($progress['trend'] == 'declining' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700');
                ?>">
                    <?php echo ucfirst($progress['trend']); ?>
                </span>
            </div>
            <canvas id="performanceChart" height="80"></canvas>
        </div>



    </main>

</body>

</html>

<script>
    // Performance Chart
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const recentScores = <?php echo json_encode($progress['recent_scores']); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: recentScores.map((_, i) => 'Test ' + (i + 1)),
            datasets: [{
                label: 'Test Scores (%)',
                data: recentScores,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
</script>

</body>

</html>
