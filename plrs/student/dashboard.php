<?php
include("../config/db.php");

/* Check login */
if(!isset($_SESSION['uid'])){
header("Location: ../auth/login.php");
exit();
}

$uid = $_SESSION['uid'];

/* =====================
   USER INFO
===================== */

$user = mysqli_fetch_assoc(
mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'")
);

/* =====================
   PERFORMANCE RECORDS
===================== */

$perfs = mysqli_query(
$conn,"SELECT * FROM performance WHERE user_id='$uid' ORDER BY id DESC"
);

/* Latest record */

$latest = mysqli_fetch_assoc(
mysqli_query($conn,"SELECT * FROM performance WHERE user_id='$uid' ORDER BY id DESC LIMIT 1")
);

/* =====================
   STATS
===================== */

$total = 0;
$total_hours = 0;
$avg_score = 0;

/* count tests */

$total = mysqli_num_rows($perfs);

/* get all records */

$all = mysqli_query(
$conn,"SELECT * FROM performance WHERE user_id='$uid'"
);

$count = mysqli_num_rows($all);

/* calculate hours + avg */

while($r = mysqli_fetch_assoc($all)){

$total_hours += $r['study_hours'];
$avg_score += $r['final_score'];

}

if($count > 0){
$avg_score = round($avg_score/$count);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>EduSmart Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body { 
font-family: 'Plus Jakarta Sans', sans-serif; 
background: #f8fafc;
}

.sidebar-glass {
background: rgba(255, 255, 255, 0.8);
backdrop-filter: blur(10px);
border-right: 1px solid rgba(226,232,240,0.8);
}

.course-card {
transition: all 0.3s ease;
}

.course-card:hover {
transform: translateY(-5px);
box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
</style>
</head>

<body class="min-h-screen flex">

<!-- SIDEBAR -->
<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full">

<div class="flex items-center gap-3 mb-10 px-2">

<div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
📘
</div>

<span class="text-xl font-bold text-slate-800">
EduSmart
</span>

</div>

<nav class="space-y-2 flex-1">

<a href="dashboard.php"
class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition">
Dashboard
</a>

<a href="enter_marks.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
Enter Marks
</a>

<a href="resources.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
Learning Resources
</a>

<a href="analytics.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
Analytics
</a>

</nav>

<a href="resources.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">

Learning Resources

</a>

<a href="#"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">
Analytics
</a>

</nav>

<div class="mt-auto p-4 bg-slate-900 rounded-2xl text-white">

<p class="text-xs text-slate-400 mb-1">
Current Role
</p>

<p class="font-bold text-sm">
Student
</p>

</div>

</aside>


<!-- MAIN -->
<main class="flex-1 lg:ml-64 p-8">

<!-- HEADER -->
<header class="flex justify-between items-center mb-10">

<div>
<h2 class="text-3xl font-bold text-slate-800">
Welcome, <?php echo isset($user['name']) ? htmlspecialchars($user['name']) : 'Student'; ?> 👋
</h2>

<p class="text-slate-500 mt-1">
Your personalized learning journey
</p>
</div>

<a href="../auth/logout.php"
class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600 transition">
Logout
</a>

</header>


<!-- STATS -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

<div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition">

<p class="text-slate-500 text-sm font-medium mb-2">
Tests Taken
</p>

<h3 class="text-4xl font-bold text-slate-800">
<?php echo $total; ?>
</h3>

<p class="text-xs text-slate-400 mt-2">Assessments completed</p>

</div>


<div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition">

<p class="text-slate-500 text-sm font-medium mb-2">
Learning Hours
</p>

<h3 class="text-4xl font-bold text-slate-800">
<?php echo $total_hours; ?>
</h3>

<p class="text-xs text-slate-400 mt-2">Time invested</p>

</div>


<div class="bg-indigo-50 p-6 rounded-3xl shadow-sm border border-indigo-100 hover:shadow-md transition">

<p class="text-indigo-600 text-sm font-medium mb-2">
Overall Progress
</p>

<h3 class="text-4xl font-bold text-indigo-700">
<?php echo $avg_score; ?>%
</h3>

<p class="text-xs text-indigo-500 mt-2">Achievement rate</p>

</div>

</section>


<!-- RECOMMENDATIONS -->
<section>

<div class="flex justify-between items-end mb-6">

<h3 class="text-2xl font-bold text-slate-800">
Recommended for You
</h3>

</div>


<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">


<?php if($latest){ ?>

<?php if($latest['math']<50){ ?>
<div class="course-card bg-white rounded-3xl shadow-sm border p-6">

<h4 class="font-bold text-lg mb-2">
📘 Improve Math
</h4>

<p class="text-slate-500 mb-4">
Revise algebra and practice daily.
</p>

<a href="#"
class="text-indigo-600 font-bold text-sm">
Start Learning →
</a>

</div>
<?php } ?>


<?php if($latest['science']<50){ ?>
<div class="course-card bg-white rounded-3xl shadow-sm border p-6">

<h4 class="font-bold text-lg mb-2">
🔬 Strengthen Science
</h4>

<p class="text-slate-500 mb-4">
Practice numericals and revise theory.
</p>

<a href="#"
class="text-indigo-600 font-bold text-sm">
Start Learning →
</a>

</div>
<?php } ?>


<?php if($latest['english']<50){ ?>
<div class="course-card bg-white rounded-3xl shadow-sm border p-6">

<h4 class="font-bold text-lg mb-2">
📖 Improve English
</h4>

<p class="text-slate-500 mb-4">
Read daily and practice grammar.
</p>

<a href="#"
class="text-indigo-600 font-bold text-sm">
Start Learning →
</a>

</div>
<?php } ?>


<?php if($latest['final_score']>=70){ ?>
<div class="course-card bg-white rounded-3xl shadow-sm border p-6">

<h4 class="font-bold text-lg mb-2">
🌟 Advanced Learning
</h4>

<p class="text-slate-500 mb-4">
Try advanced topics and challenges.
</p>

<a href="#"
class="text-indigo-600 font-bold text-sm">
Explore →
</a>

</div>
<?php } ?>

<?php }else{ ?>

<p class="text-slate-500">
No data yet. Enter marks first.
</p>

<?php } ?>


</div>

</section>

</main>

</body>
</html>
