<div style="text-align:center">
<?php
$arr = scandir('.');
for($i = 3; $i < count($arr); $i++) {
	echo "<img style=\"margin-top: 30px\" src=\"$arr[$i]\" /><br />";
}
?>
</div>