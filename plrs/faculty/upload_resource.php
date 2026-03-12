<?php
include("../config/db.php");

if(!isset($_SESSION['uid']) || $_SESSION['role']!='faculty'){
header("Location: ../auth/login.php");
exit();
}

$request_id=$_GET['request_id'];

$request=mysqli_fetch_assoc(mysqli_query($conn,"
SELECT sr.*,u.name
FROM student_requests sr
JOIN users u ON sr.user_id=u.id
WHERE sr.id='$request_id'
"));

if(isset($_POST['upload'])){

$title=$_POST['title'];
$youtube=$_POST['youtube_link'];

$faculty=mysqli_fetch_assoc(mysqli_query($conn,
"SELECT name FROM users WHERE id='{$_SESSION['uid']}'"));

$faculty_name=$faculty['name'];

$file_path="";

if(!empty($_FILES['pdf_file']['name'])){

$target="../uploads/";
$file_path=$target.time()."_".$_FILES['pdf_file']['name'];

move_uploaded_file(
$_FILES['pdf_file']['tmp_name'],
$file_path
);

$type="pdf";
$link=$file_path;

}else{

$type="youtube";
$link=$youtube;

}

/* Insert Resource */

mysqli_query($conn,"
INSERT INTO resources
(request_id,faculty_name,subject,sub_topic,title,resource_type,resource_link,file_path)

VALUES
('$request_id','$faculty_name',
'{$request['subject']}',
'{$request['weak_topic']}',
'$title','$type','$link','$file_path')
");

header("Location: dashboard.php");
}
?>

<!DOCTYPE html>
<html>
<head>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-slate-100 p-10">

<div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow">

<h2 class="text-xl font-bold mb-4">
Upload Learning Resource
</h2>

<p class="mb-6 text-gray-600">

Student: <?php echo $request['name']; ?><br>
Subject: <?php echo $request['subject']; ?><br>
Topic: <?php echo $request['weak_topic']; ?>

</p>

<form method="post" enctype="multipart/form-data" class="space-y-4">

<input type="text" name="title"
placeholder="Resource Title"
class="w-full border p-2 rounded"
required>

<!-- YOUTUBE LINK -->

<div>

<label class="font-semibold text-sm">
YouTube Link (Optional)
</label>

<input type="text" name="youtube_link"
placeholder="https://youtube.com/..."
class="w-full border p-2 rounded">

</div>

<!-- PDF FILE -->

<div>

<label class="font-semibold text-sm">
Upload PDF / Notes (Optional)
</label>

<input type="file" name="pdf_file"
class="w-full border p-2 rounded">

</div>

<button name="upload"
class="bg-indigo-600 text-white px-4 py-2 rounded">

Upload Resource

</button>

</form>

</div>

</body>
</html>