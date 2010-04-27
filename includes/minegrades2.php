<?PHP
// NOT FOR SHOWING DIRECTLY - THIS IS A LIBRARY - THINGS WILL NOT LOOK
// RIGHT IF SOMEONE ACCESSES THIS FILE DIRECTLY


// This is the tree class for the root.  Internal nodes inherit from this
// type to disply different messages.

$flag = 0;
$next = 1;
$old = 0;
$AllNodesPrint = array();
$DOTPrint = array();
$numeroUno = 0;
$work = 0;

class GradeDecisionTree
{
    public $students = array();
    public $cutoff = -1;
    public $right = null;
    public $wrong = null;

    function __construct($students, $cutoff)
    {
        $this->students = $students;
        $this->cutoff = $cutoff;
    }

    // Generate and return the message that this tree node should display
    public function message()
    {
        $total = count($this->students);
        $good = 0;
        $bad = 0;
        foreach ($this->students as $student) {
            if (array_sum($student) >= $this->cutoff) {
                $good++;
            } else {
                $bad++;
            }
        }

        $goodp = (int) (100*$good / $total);
        $badp = (int) (100*$bad / $total);
        return <<<EOT
$total students total
$good students passed ($goodp%)
$bad students failed ($badp%)
EOT;
    }

    public function printout($indent)
    {
        $lead = "";
        for ($i = 0; $i < $indent; $i++) $lead = $lead . " ";

        print(preg_replace("/\n/", "\n" . $lead, $lead . $this->message()));
        print("\n");

        if ($this->right != null) $this->right->printout($indent+2);
        if ($this->wrong != null) $this->wrong->printout($indent+2);
    }

    //prints out the tree in a table format
    public function printHTML()
    {
        echo "<table border=1>\n";
        echo "<tr><td colspan=2 align=center valign=top>\n"; 
        print(preg_replace("/\n/", "<br>", $this->message()));
        echo "</td></tr>\n";
        echo "<tr><td align=center valign=top>\n";
        if ($this->right != null) $this->right->printHTML();
        echo "</td><td align=center valign=top>\n";
        if ($this->wrong != null) $this->wrong->printHTML();
        echo "</td></tr>\n";
        echo "</table>\n";
    }

    public function printDOT($parent,$child)
    {
	global $DOTPrint;
	global $numeroUno;
	global $next;

	if($next > 1)
	{
		$statement = '"';
		$temp = $next - 1;
		$statement .= "$temp ";
		$parent = $temp;
	        $statement .= preg_replace("/\n/", '\n', $this->message());
		$statement .= '";';
		$DOTPrint [] = $statement;
	}
	//$numeroUno++;
        if ($this->right != null)
	{
		$statement = '"';
		$statement .= "$parent ";
        	$statement .= preg_replace("/\n/", '\n', $this->message());
		$statement .= '"->';
		$DOTPrint [] = $statement;
		$next = $next + 1;
		$this->right->printDOT($child,$next);
	}
        if ($this->wrong != null)
	{
		$statement = '"';
		$statement .= "$parent ";
        	$statement .= preg_replace("/\n/", '\n', $this->message());
		$statement .= '"->';
		$DOTPrint [] = $statement;
		$next = $next + 1;
		$this->wrong->printDOT($child,$next);
	}
	return $DOTPrint;	
    }

    public function printAllNodes()
    {
	global $AllNodesPrint;
	global $flag;
	$statement = '"';
	if ($flag == 0)
		{
		$statement .= "0 ";
		$flag = 1;
		}
        $statement .= preg_replace("/\n/", '\n', $this->message());
	$statement .= '";';
	$AllNodesPrint [] = $statement;
        if ($this->right != null) $this->right->printAllNodes();
        if ($this->wrong != null) $this->wrong->printAllNodes();
	return $AllNodesPrint;
    }

}

class GradeDecisionTreeInternal extends GradeDecisionTree
{
    public $question;
    public $gotitright;

    function __construct($students, $cutoff, $question, $gotitright)
    {
       parent::__construct($students, $cutoff);
       $this->question = $question;
       $this->gotitright = $gotitright;
    }

    // Generate and return the message that this tree node should display
    public function message()
    {
        $total = count($this->students);
        $good = 0;
        $bad = 0;
        foreach ($this->students as $student) {
            if (array_sum($student) >= $this->cutoff) {
                $good++;
            } else {
                $bad++;
            }
        }

        $goodp = (int) (100*$good / $total);
        $badp = (int) (100*$bad / $total);
        $rw = $this->gotitright ? "right" : "wrong";
        $q = $this->question + 1;
        return <<<EOT
They got $q $rw

$total students total
$good students passed ($goodp%)
$bad students failed ($badp%)
EOT;
    }
}

function entropy($results, $bar)
{
    $good = 0.0;
    $bad = 0.0;
    foreach ($results as $student) {
        if (array_sum($student) >= $bar) {
            $good+=1;
        } else {
            $bad+=1;
        }
    }

    if ($good > 0) $good = $good / ((float) count($results));
    if ($bad > 0) $bad = $bad / ((float) count($results));

    if ($good > 0 && $bad > 0)
        return -1 * ($good * log($good, 2.0) + $bad * log($bad, 2.0));
    else 
        return 0.0;
}

// Recursively break up the tree until we no longer can
function breakTree($tree, $qcutoff, $ncutoff)
{
    $bestq = -1;
    $bestinfogain = $ncutoff;
    $currententropy = entropy($tree->students, $qcutoff);

    $numss = (float) count($tree->students);
    $numqs = count($tree->students[0]);

    for ($q = 0; $q < $numqs; $q++) {
        $right = array();
        $wrong = array();

        foreach ($tree->students as $student) {
            if ($student[$q] == 1) {
                $right[] = $student;
            } else {
                $wrong[] = $student;
            }
        }

        $rentropy = entropy($right, $qcutoff);
        $wentropy = entropy($wrong, $qcutoff);
        $newe = $rentropy * count($right) / $numss 
                + $wentropy * count($wrong) / $numss;

        $gain = $currententropy - $newe;

        if ($gain >= $bestinfogain) {
            $bestinfogain = $gain;
            $bestq = $q;
        }
    }

    if ($bestq == -1) return; // Don't split if we don't need to

    $right = array();
    $wrong = array();

    foreach ($tree->students as $student) {
        if ($student[$bestq] == 1) {
            $right[] = $student;
        } else {
            $wrong[] = $student;
        }
    }

    if (count($right) > 0 && count($wrong) > 0) {
        $tree->right = new GradeDecisionTreeInternal($right, $qcutoff, $bestq, true);
        $tree->wrong = new GradeDecisionTreeInternal($wrong, $qcutoff, $bestq, false);
        breakTree($tree->right, $qcutoff, $ncutoff);
        breakTree($tree->wrong, $qcutoff, $ncutoff);
    } 
}

// THIS IS THE FUCTION YOU SHOULD USE.  YOU CAN IGNORE THE REST
function mineGrades($numberCorrect, $noisethreshold, $key, $answers)
{
    $graded = array();
    $numq = count($key);
    foreach ($answers as $test) {
        $response = array();
        for ($i = 0; $i < $numq; $i++) {
            $response[] = ( (strcmp($test[$i], $key[$i]) == 0) ? 1 : 0 );
        }
        $graded[] = $response;
    }

    //graded now contains a whole bunch of binary arrays
    $tree = new GradeDecisionTree($graded, $numberCorrect);
    breakTree($tree, $numberCorrect, $noisethreshold);
    return $tree;
}

// All of this commented-out code is test code.

/*
$t = mineGrades(2, .5, 
        array('a', 'b', 'c'), 
        array(  array('a', 'b', 'c'),
                array('a', 'd', 'd'),
                array('a', 'd', 'd'),
                array('a', 'd', 'd'),
                array('a', 'b', 'd'),
                array('d', 'b', 'c'),
                array('d', 'b', 'c'),
                array('d', 'b', 'c')));
print($t->message() . "\n");

$file = fopen('/home/peter/research/dmcalc/103', "r");
$key = str_split(trim(fgets($file)));
$sdata = array();
while (!feof($file)) {
    $sdata[] = str_split(trim(fgets($file)));
}

$t = mineGrades(10, .2, $key, $sdata);
print($t->message() . "\n");
$t->printout(0);
*/


?>
