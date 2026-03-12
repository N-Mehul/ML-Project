<?php
include("../config/db.php");

if(!isset($_SESSION['uid']) || $_SESSION['role']!='faculty'){
header("Location: ../auth/login.php");
exit();
}

/* Function to save faculty reply */
function saveFacultyReply($conn, $feedback_id, $reply) {
    $reply = mysqli_real_escape_string($conn, $reply);
    $query = "UPDATE feedback SET faculty_reply='$reply' WHERE id='$feedback_id'";
    return mysqli_query($conn, $query);
}

/* Function to fetch feedback data */
function getFeedbackData($conn) {
    $query = "
    SELECT
        u.name AS student,
        sr.subject,
        sr.weak_topic,
        r.title,
        r.resource_link,
        f.feedback,
        f.faculty_reply,
        f.id
    FROM student_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN resources r ON sr.subject = r.subject AND sr.weak_topic = r.sub_topic
    LEFT JOIN feedback f ON sr.id = f.request_id
    ORDER BY sr.id DESC
    ";
    $result = mysqli_query($conn, $query);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

/* Function to get analytics data */
function getAnalyticsData($data) {
    $analytics = [
        'total_feedbacks' => 0,
        'subjects' => [],
        'topics' => [],
        'replied_feedbacks' => 0,
        'pending_replies' => 0
    ];
    
    foreach($data as $row) {
        $analytics['total_feedbacks']++;
        
        $subject = $row['subject'];
        if (!isset($analytics['subjects'][$subject])) {
            $analytics['subjects'][$subject] = 0;
        }
        $analytics['subjects'][$subject]++;
        
        $topic = $row['weak_topic'];
        if (!isset($analytics['topics'][$topic])) {
            $analytics['topics'][$topic] = 0;
        }
        $analytics['topics'][$topic]++;
        
        if ($row['faculty_reply']) {
            $analytics['replied_feedbacks']++;
        } else {
            $analytics['pending_replies']++;
        }
    }
    
    return $analytics;
}

/* SAVE FACULTY REPLY */
if(isset($_POST['reply'])){
    $id = $_POST['feedback_id'];
    $reply = $_POST['faculty_reply'];
    saveFacultyReply($conn, $id, $reply);
}

/* Fetch data and analytics */
$data = getFeedbackData($conn);
$analytics = getAnalyticsData($data);

/* Function to render a table row */
function renderTableRow($row) {
    echo '<tr class="border-t">';
    echo '<td class="p-3">' . htmlspecialchars($row['student']) . '</td>';
    echo '<td class="p-3 text-center">' . htmlspecialchars($row['subject']) . '</td>';
    echo '<td class="p-3 text-center">' . htmlspecialchars($row['weak_topic']) . '</td>';
    echo '<td class="p-3 text-center">';
    if($row['title']) {
        echo htmlspecialchars($row['title']);
    } else {
        echo "<span class='text-orange-500'>Pending</span>";
    }
    echo '</td>';
    echo '<td class="p-3 text-center">';
    if($row['resource_link']) {
        echo '<a href="' . htmlspecialchars($row['resource_link']) . '" target="_blank" class="text-blue-600 underline">Open Resource</a>';
    } else {
        echo "<span class='text-red-500'>Pending</span>";
    }
    echo '</td>';
    echo '<td class="p-3 text-center">' . htmlspecialchars($row['feedback']) . '</td>';
    echo '<td class="p-3 text-center">';
    if($row['faculty_reply']) {
        echo '<div class="text-green-600 font-semibold">' . htmlspecialchars($row['faculty_reply']) . '</div>';
    } else {
        echo '<form method="post">';
        echo '<input type="hidden" name="feedback_id" value="' . htmlspecialchars($row['id']) . '">';
        echo '<input type="text" name="faculty_reply" placeholder="Reply..." class="border p-1 rounded w-40" required>';
        echo '<button name="reply" class="bg-indigo-600 text-white px-3 py-1 rounded ml-1">Send</button>';
        echo '</form>';
    }
    echo '</td>';
    echo '</tr>';
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Resources Given & Student Feedback</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="bg-slate-100 min-h-screen p-10">


<h2 class="text-2xl font-bold mb-6">

Resources Given & Student Feedback

</h2>

<!-- Analytics Section -->
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <h3 class="text-xl font-semibold mb-4">Feedback Analytics</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-medium text-blue-800">Total Feedbacks</h4>
            <p class="text-2xl font-bold text-blue-600"><?php echo $analytics['total_feedbacks']; ?></p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <h4 class="font-medium text-green-800">Replied Feedbacks</h4>
            <p class="text-2xl font-bold text-green-600"><?php echo $analytics['replied_feedbacks']; ?></p>
        </div>
        <div class="bg-orange-50 p-4 rounded-lg">
            <h4 class="font-medium text-orange-800">Pending Replies</h4>
            <p class="text-2xl font-bold text-orange-600"><?php echo $analytics['pending_replies']; ?></p>
        </div>
    </div>
    <div class="mt-6">
        <h4 class="font-medium mb-2">Feedbacks by Subject</h4>
        <ul class="list-disc list-inside">
            <?php foreach($analytics['subjects'] as $subject => $count) { ?>
                <li><?php echo htmlspecialchars($subject) . ': ' . $count; ?></li>
            <?php } ?>
        </ul>
    </div>
    <div class="mt-6">
        <h4 class="font-medium mb-2">Feedbacks by Topic</h4>
        <ul class="list-disc list-inside">
            <?php foreach($analytics['topics'] as $topic => $count) { ?>
                <li><?php echo htmlspecialchars($topic) . ': ' . $count; ?></li>
            <?php } ?>
        </ul>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-x-auto">


<table class="w-full text-sm">

<thead class="bg-gray-100">

<tr>

<th class="p-3 text-left">Student</th>
<th class="p-3">Subject</th>
<th class="p-3">Topic</th>
<th class="p-3">Resource</th>
<th class="p-3">Access</th>
<th class="p-3">Feedback</th>
<th class="p-3">Faculty Reply</th>

</tr>

</thead>


<tbody>

<?php foreach($data as $row) { 
    renderTableRow($row);
} ?>

</tbody>

</table>

</div>


</body>
</html>