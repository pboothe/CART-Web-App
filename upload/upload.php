<?php
$target = "uploaded_spreadsheets/";
$target = $target . basename( $_FILES['uploaded']['name']);
$ok=1;

$uploaded_type = $_FILES['uploaded']['type'];

if (!($uploaded_type=="application/vnd.ms-excel"))
{
	echo "You may only upload XLS files.<br>";
	$ok=0;
}

if ($ok==0)
{
	echo "Sorry your file was not uploaded";
}


else{
	if(move_uploaded_file($_FILES['uploaded']['tmp_name'], $target))
	{
		echo "The file ". basename( $_FILES['uploaded']['name']). " has been uploaded <a href='".$target."'>view</a>";
	}
	else
	{
		echo "Sorry, there was a problem uploading your file";
	}
}
?>