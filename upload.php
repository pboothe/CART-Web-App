<?php
session_start();
/** 
*This script takes the uploaded file and saves a spreadsheet in a local directory. <br> It also checks to make sure the user did not enter absurd cutoff ranges. <br> From there, it proceeds to read from the uploaded excel spreadsheet. <br> The answer key is saved in an array. The student answers are then parsed into a 2D-array. <br>
*
* @package pagelevel-pagedesc
*/
?>
<html>
<head>
<title>Results</title>
<link rel="styleSheet" type="text/css" href="includes/style.css" />
</head>
<body>


<div id="wrapper" style="width:502px; margin:25px auto">

<?php if( isset($_SESSION['loggedInAs']) ) { ?>

<div id="top">
  <div id="links">
  <a href="protected.php">input new information</a> | 
  <a href="signout.php">signout</a></div>
  </div>

<?php } ?>

<div id="content" style="width:500px;">
<div id="holder">

<?php

if( isset($_SESSION['loggedInAs']) ) { 

$target = "uploaded_spreadsheets/". basename( $_FILES['uploaded']['name']);

/**
* Included Library Information
*
* @link includes/minegrades.php
* @link includes/phpExcel/Classes/PHPExcel/IOFactory.php
*/
require_once('includes/phpExcel/Classes/PHPExcel/IOFactory.php');

$COG = $_REQUEST['cutoff_grade'];
$COP = $_REQUEST['cutoff_prob'];
$ALG = $_REQUEST['algo'];
$uploaded_type = $_FILES['uploaded']['type'];

//Error check if the document uploaded is indeed
//a 2003 Excel Spreadsheet
/*if (!($uploaded_type=="application/vnd.ms-excel"))
{
        echo "You may only upload XLS files.<br>";
        echo "Sorry your file was not uploaded.<br>";
        echo "<a href='protected.php'>Please try again.</a>";
        exit(1);
}

else*/{
        if(move_uploaded_file($_FILES['uploaded']['tmp_name'], $target))
        {       
                echo "<h3>Results:</h3>";
                echo "<small>";
                echo "<i>".$_FILES['uploaded']['name']."</i><br>";
                echo "Cutoff-grade = ".$_REQUEST['cutoff_grade']."<br>"; 
                echo "Cutoff-probability = ".$_REQUEST['cutoff_prob']."<br>";
		echo "Algorithm used = ".$_REQUEST['algo']."<br>";
                echo "</small>";
        }
        else
        {
                echo "Sorry, there was a problem uploading your file";
        }
}


//Set-up a reader to parse the recently uploaded Excel Spreadsheet
$objReader = PHPExcel_IOFactory::createReader('Excel5');
$objPHPExcel = $objReader->load($target);
//$val = ($objPHPExcel->getActiveSheet()->getCell('A1'));
//$temp1 = $val->getvalue();

//Ascertain the last filled Row and Column. This dynamically figures out how many students took
//the test and how many questions the test was. This way the user doesn't need to be asked these
//particulars
$highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
$highestCol = $objPHPExcel->getActiveSheet()->getHighestColumn();
//Convert String Column to Int
$highestCol = PHPExcel_Cell::columnIndexFromString($highestCol);
//Hard-coded position of what column student answers start. This is the same for every sheet.
$startColumn = 'H';
$startColumn = PHPExcel_Cell::columnIndexFromString($startColumn);

//numQuestions = (&highestCol - 7);//number of questions
//Error check if the input cutoff values are allowable
if ($COG < 0 || $COG > ($highestCol - 7) || strlen($COG) < 1 || !(is_numeric($COG)))
{
        echo "<br>Please enter a valid cutoff grade (NOT percentage, but the number), between 0 and ".($highestCol - 7).".<br><br>";
        echo "<a href='protected.php'>Please try again.</a>";
        exit(1);
}

if ($COP < 0 || $COP > 1 || strlen($COP) < 1 || !(is_numeric($COP)))
{
        echo "Please enter a valid cutoff probability, between 0 and 1.<br>";
        echo "<a href='protected.php'>Please try again.</a>";
        exit(1);
}

//Set up and populate the Answer Key Array.
$answerKey = array();

for($col = $startColumn - 1; $col < $highestCol; $col++)
{
        $answerKey[] = ($objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col,4)->getValue());
}


//Set up and populate the Student Answer Array. Start row is hard-coded. The answers start here for
//every spreadsheet.
$startRow = 5;
$studentAns = array(array());

for($row = $startRow; $row < $highestRow; $row++)
{
        $ithStudent = array();
        for($col = $startColumn - 1; $col < $highestCol; $col++)
        {
                 $temp = ($objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col,$row)->getValue());
                if($temp == NULL)
                {
                        $temp = 'X';
                }
                $ithStudent[] = $temp;
        }
        $studentAns[] = $ithStudent;
}

//Boothe-ian Magic Occurs, returns a binary tree of CART data
if ($ALG == "old"){
	include 'includes/minegrades.php';
} elseif ($ALG == "new"){
	include 'includes/minegrades2.php';
}else{
	echo "<small>Invalid algorithm chosen</small>";
}

$stats = mineGrades($COG, $COP, $answerKey, $studentAns);


//printAllNodes returns an array with a simple listing of all nodes
//this is required for DOT
$test = $stats->printAllNodes();

//printDOT returns an array of dependence statments, i.e. node -> node to show
//dependence. this is also required for DOT.
$final = $stats->printDOT(0,1);
//echo "$final";
//Andres A.K.A BowlofRice edits begin below
//Create a randomly namedDOT file in the saved_pngs directory //and check to make sure it can be opened
$Timestamp=time();
$tempdotfile=$Timestamp."process.dot";
$DotFile = "saved_pngs/".$tempdotfile;
$fh = fopen($DotFile, 'w') or die("can't open file");


//DOT format requires this to be the first line
$topStatement = "digraph{\n";
fwrite($fh, $topStatement);


//Loop through listing of all Nodes and write them to the DOT file
for($g = 0; $g < sizeof($test); $g++)
{
	
	
	$blah = str_replace("They","$g They",$test[$g]);
	#if ($g == 0)
		#$blah = "0".$blah;
	//$blah = str_replace(" /", "'\'", $blah);
	//echo $blah;
	//echo $test[$g];
	//echo $g;
	fwrite($fh, $blah);
}


//Loop through listing of all node dependencies and write them to the DOT file
for($c = 0; $c < sizeof($final); $c++)
{
	//$blah2 = str_replace("They","$c They",$final[$c]);
	//echo "$final[$c]";
//	echo"$c";
	fwrite($fh, $final[$c]);
}


//DOT format requires this to the last line of the file
$bottomStatement = "}";
fwrite($fh, $bottomStatement);

//Be a good boy and close the file when done
fclose($fh);



//create the image file
$Timestamp=time();
$temppngfile=$Timestamp."output.png";
$createPng = "dot saved_pngs/".$tempdotfile." -T png -o saved_pngs/".$temppngfile;
system($createPng);

//remove xls and dot files
if(file_exists("saved_pngs/".$tempdotfile)) unlink("saved_pngs/".$tempdotfile);
if(file_exists($target)) unlink($target);

//index 0 is width, 1 is height, in pixels
$imageSize = getimagesize("saved_pngs/$temppngfile");
$scaledWidth = floor($imageSize[0] / 5);
$scaledHeight = floor($imageSize[1] / 5);

?>
<br />
<small>click image for full-size</small>
<br />
<a href="saved_pngs/<?php echo $temppngfile; ?>" target="_blank">
<img src="saved_pngs/<?php echo $temppngfile; ?>" style="width:<?php echo $scaledWidth; ?>px; height:<?php echo $scaledHeight; ?>px; border:2px solid #cccccc" onMouseOver="this.style.border='2px solid #339999'" onMouseOut="this.style.border='2px solid #cccccc'" />
</a><br>
<!--Andres A.K.A BowlofRice edits end here-->

</div><!--close holder-->
</div><!--close content-->
<?php 
}
else echo "You must <a href='index.php'>sign in</a> to view this content."; 
?>
</div><!--close wrapper-->

</body>
</html>
