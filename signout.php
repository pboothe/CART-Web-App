<?php
session_start();
/**
* Signs out user at the end of their session
*
* @package pagelevel-desc
*/
error_reporting(E_ALL);

//clear session variable
unset($_SESSION['loggedInAs']);
$str="saved_pngs";
function recursiveDelete($str){
  if(is_file($str)){
    return @unlink($str);
  }elseif(is_dir($str)){
    $scan=glob(rtrim($str,'/').'/*');
    foreach($scan as $index=>$path){
      recursiveDelete($path);
    }
    return @rmdir($str);
  }
}
recursiveDelete($str);
session_destroy();

/**
* Redirects user to login page
*
* @link index.php
*/
echo "<script type='text/javaScript'>window.location='index.php';</script>";
?>
