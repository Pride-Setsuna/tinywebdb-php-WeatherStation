<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die('Invalid request method.');
}

$logfile = filter_input(INPUT_GET, 'logfile', FILTER_SANITIZE_STRING);
$tag = filter_input(INPUT_GET, 'tag', FILTER_SANITIZE_STRING);

if (!$logfile || !$tag) {
    die('Invalid parameters.');
}

if (!file_exists($logfile)) {
    die('Log file not found.');
}

include ("./jpgraph4/src/jpgraph.php");
include ("./jpgraph4/src/jpgraph_line.php");

$tempers = file($logfile);
$p_time = 0;
$datax = array();
$datay = array();
$data2y = array();
$datap = array();
$datab = array();

foreach ($tempers as $temper) {
    list($dummy, $tagValue) = explode('--', $temper);
    $tagName = substr($dummy, -15, 14);
    if (strcmp($tag, $tagName) !== 0) {
        continue;
    }

    $obj = json_decode($tagValue, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        continue;
    }

    $temp = round(floatval($obj['temperature']), 2);
    $localt = intval($obj['localTime']) + 8 * 3600;
    $ldate = strftime("%D", $localt);
    $ltime = strftime("%T", $localt);
    $pressure = floatval($obj['pressure_hpa']);
    $battery = 10 * floatval($obj['battery_Vcc']);
    $p_time2 = $ldate . " " . substr($ltime, 0, 2);

    if ($p_time2 != $p_time) {
        if ($p_time) {
            array_push($datax, $p_time);
            array_push($datay, $p_low);
            array_push($data2y, $p_high);
            array_push($datap, $pressure);
            array_push($datab, $battery);
        }
        $p_time = $p_time2;
        $p_high = $temp;
        $p_low = $temp;
    } else {
        $p_low = ($p_low > $temp || $p_low == 0) ? $temp : $p_low;
        $p_high = ($p_high < $temp || $p_high == 0) ? $temp : $p_high;
    }
}

array_push($datax, $p_time);
array_push($datay, $p_low);
array_push($data2y, $p_high);
array_push($datap, $pressure);
array_push($datab, $battery);

$graph = new Graph(950,450,"auto");
$graph->img->SetMargin(40,100,40,80);	
$graph->SetScale("textlin");
$graph->SetY2Scale("lin");
$graph->SetShadow();
$graph->title->Set("ESP8266 Wireless Sersor 24 Hourly Report");

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->legend->SetPos(0.03,0.5,"right","center");
$graph->legend->SetColumns(1);

$graph->xaxis->SetTickLabels($datax);
$graph->xaxis->SetFont(FF_FONT1);
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->scale->ticks->Set(6*3600,3600); 

$p1 = new LinePlot($datay);
$p1->mark->SetType(MARK_UTRIANGLE);
$p1->mark->SetFillColor("blue");
$p1->mark->SetWidth(4);
$p1->SetColor("blue");
$p1->SetCenter();
$p1->SetLegend("Low");
$graph->Add($p1);

$p2 = new LinePlot($data2y);
$p2->mark->SetType(MARK_DTRIANGLE);
$p2->mark->SetFillColor("red");
$p2->mark->SetWidth(4);
$p2->SetColor("red");
$p2->SetCenter();
$p2->SetLegend("High");
$graph->Add($p2);

$p3 = new LinePlot($datap);
$p3->mark->SetType(MARK_FILLEDCIRCLE);
$p3->mark->SetFillColor("green");
$p3->mark->SetWidth(4);
$p3->SetColor("green");
$p3->SetCenter();
$p3->SetLegend("pressure");
$graph->AddY2($p3);

$p4 = new LinePlot($datab);
$p4->mark->SetType(MARK_STAR);
$p4->mark->SetFillColor("pink");
$p4->mark->SetWidth(4);
$p4->SetColor("pink");
$p4->SetCenter();
$p4->SetLegend("battery_10");
$graph->Add($p4);

$graph->Stroke();
?>
