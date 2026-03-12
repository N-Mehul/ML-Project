<?php
include("../config/db.php");

if(!isset($_SESSION['uid'])){
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];

/* USER INFO */
$user = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'")
);

/* SAVE FEEDBACK */
if(isset($_POST['submit_feedback'])){

$request_id = $_POST['request_id'];
$feedback = $_POST['feedback'];
$faculty_name = $_POST['faculty_name'];

mysqli_query($conn,"
INSERT INTO feedback(user_id,request_id,faculty_name,feedback)
VALUES('$uid','$request_id','$faculty_name','$feedback')
");

}

/* FETCH RESOURCES */

$resources = mysqli_query($conn,"
SELECT DISTINCT sr.id as request_id,
sr.subject,
sr.weak_topic,
r.title,
r.faculty_name,
r.resource_link,
f.feedback,
f.faculty_reply

FROM student_requests sr

LEFT JOIN resources r
ON sr.id=r.request_id

LEFT JOIN feedback f
ON sr.id=f.request_id
AND f.user_id='$uid'

WHERE sr.user_id='$uid'

ORDER BY sr.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Learning Resources</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

body{
font-family:'Plus Jakarta Sans',sans-serif;
background:#f8fafc;
}

.sidebar-glass{
background:rgba(255,255,255,0.85);
backdrop-filter:blur(10px);
border-right:1px solid #e5e7eb;
}

</style>

</head>


<body class="min-h-screen flex">

<!-- SIDEBAR -->

<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full">

<div class="flex items-center gap-3 mb-10">

<div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold">
ES
</div>

<span class="text-xl font-bold text-slate-800">
EduSmart
</span>

</div>


<nav class="space-y-2 flex-1">

<a href="dashboard.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">

Dashboard

</a>


<a href="enter_marks.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">

Enter Marks

</a>


<a href="resources.php"
class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold">

Learning Resources

</a>


<a href="analytics.php"
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

<header class="flex justify-between items-center mb-10">

<div>

<h2 class="text-3xl font-bold text-slate-800">

Welcome, <?php echo $user['name']; ?>

</h2>

<p class="text-slate-500">

Your personalized learning journey

</p>

</div>


<a href="../auth/logout.php"
class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600">

Logout

</a>

</header>



<!-- PAGE TITLE -->

<h3 class="text-2xl font-bold text-slate-800 mb-6">

Available Learning Resources

</h3>



<!-- TABLE -->

<div class="bg-white rounded-3xl shadow-sm border overflow-x-auto">

<table class="w-full text-sm">

<thead class="bg-slate-100">

<tr>

<th class="p-3 text-left">Subject</th>
<th class="p-3">Topic</th>
<th class="p-3">Title</th>
<th class="p-3">Faculty</th>
<th class="p-3">Access</th>
<th class="p-3">Feedback</th>

</tr>

</thead>


<tbody>

<?php while($r=mysqli_fetch_assoc($resources)){ ?>

<tr class="border-t hover:bg-slate-50">

<td class="p-3">
<?php echo $r['subject']; ?>
</td>

<td class="p-3 text-center">
<?php echo $r['weak_topic']; ?>
</td>


<td class="p-3 text-center">

<?php
if($r['title'])
echo $r['title'];
else
echo "<span class='text-orange-500 font-semibold'>Pending</span>";
?>

</td>


<td class="p-3 text-center">

<?php
if($r['faculty_name'])
echo "<span class='text-indigo-600 font-semibold'>".$r['faculty_name']."</span>";
else
echo "-";
?>

</td>


<td class="p-3 text-center">

<?php if($r['resource_link']){ ?>

<a href="<?php echo $r['resource_link']; ?>"
target="_blank"
class="text-blue-600 underline">

Open Resource

</a>

<?php }else{

echo "<span class='text-red-500 font-semibold'>Pending</span>";

} ?>

</td>



<td class="p-3 text-center">

<?php if($r['resource_link']){ ?>


<?php if($r['feedback']){ ?>

<div class="text-green-600 font-semibold">

Your Feedback

</div>

<div class="text-gray-700 mb-1">

<?php echo $r['feedback']; ?>

</div>


<?php if($r['faculty_reply']){ ?>

<div class="bg-green-50 border border-green-200 p-2 rounded text-sm">

<strong>Faculty Reply:</strong><br>

<?php echo $r['faculty_reply']; ?>

</div>

<?php }else{ ?>

<span class="text-orange-500 text-sm">

Waiting for faculty reply

</span>

<?php } ?>


<?php }else{ ?>


<form method="post">

<input type="hidden"
name="request_id"
value="<?php echo $r['request_id']; ?>">

<input type="hidden"
name="faculty_name"
value="<?php echo $r['faculty_name']; ?>">

<input type="text"
name="feedback"
placeholder="Write feedback"
class="border p-1 rounded w-full mb-2"
required>

<button name="submit_feedback"
class="bg-indigo-600 text-white px-3 py-1 rounded">

Submit

</button>

</form>

<?php } ?>


<?php }else{

echo "-";

} ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</main>

</body>
</html>