<?php
namespace Grav\Plugin;
use Grav\Common\Twig\Extension\GravExtension;
use SQLite3;
use DateTime;

class VertretungTwigExtension extends GravExtension
{
    public function getName()
    {
        return 'VertretungTwigExtension';
    }
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('vertretungsplan', [$this, 'getVertretungsplan']),
            new \Twig_SimpleFunction('dateKeys', [$this, 'getDateKeys'])
        ];
    }

    private function getDateKey($year, $month, $day) 
    {
	return ($year * 10000) + ($month * 100) + $day;
    }

    private function getDateKeyNow() 
    {
	$now = getdate();
	return $this->getDateKey($now['year'], $now['mon'], $now['mday']);
    }
    
    private function getDateKeyStart() 
    {
	$now = getdate();
	
	$hour = $now['hours'];
	if($hour > 13) {
		$now = getdate($now[0] + 86400);	
	} 
	
	$wday = $now['wday'];

	if($wday == 0)
		$now = getdate($now[0] + 86400);
	else if($wday == 6)
		$now = getdate($now[0] + (2 * 86400));
	
	return $this->getDateKey($now['year'], $now['mon'], $now['mday']);
    }

    function parseDateKey($key) 
    {

	$day = intval($key % 100);
	$month = intval(($key / 100) % 100);
	$year = intval($key / 10000);
	
	return new DateTime("$year-$month-$day");
    }
    
    

    public function getDateKeys($dateKey) 
    {
        if(!isset($dateKey) || $dateKey == NULL || !is_numeric($dateKey))
	    $dateKey = $this->getDateKeyStart();
	    
	    
        $dateKey = min(20301231, max(20200101, $dateKey));
	
        try 
        {
	    $dateDatetime = $this->parseDateKey($dateKey);
        } 
        catch(\Exception $e) 
        {
	    $dateKey = $this->getDateKeyNow();
	    $dateDatetime = $this->parseDateKey($dateKey);
        }

        $currentTs = $dateDatetime->getTimestamp();

        $ts = $dateDatetime->format('U') - 86400;
        $d = getdate($ts);
        $w = $d['wday'];
        if($w == 0)
	    $d = getdate($d[0] - (2 * 86400));
        else if($w == 6)
    	    $d = getdate($d[0] - (1 * 86400));

        $gestern = $this->getDateKey($d['year'], $d['mon'], $d['mday']);
        $gesternTs = $d[0];

        $ts = $dateDatetime->format('U') + 86400;
        $d = getdate($ts);
        $w = $d['wday'];
        if($w == 0)
	    $d = getdate($d[0] + (1 * 86400));
	    else if($w == 6)
    	$d = getdate($d[0] + (2 * 86400));


    	$morgen = $this->getDateKey($d['year'], $d['mon'], $d['mday']);        
    	$morgenTs = $d[0];
        
        return [
            'prev' => [ 'key' => $gestern, 'date' => $gesternTs ],
            'now' => [ 'key' => $this->getDateKeyNow() ],
            'current' => [ 'key' => $dateKey, 'date' => $currentTs ], 
            'next' => [ 'key' => $morgen, 'date' => $morgenTs ]
        ];
    }
    
    public function getVertretungsplan($value) 
    {
        $dateKeys = $this->getDateKeys($value);

        $locator = $this->grav['locator'];
        $path = $locator->findResource('user://data', true);
        $dir = $path . DS . 'vertretung';
        $fullFileName = $dir. DS . '_vertretung.sqlite';

        $db = new SQLite3($fullFileName);

        $result = array();

//        $dateKey = 20210909;
	
	$stmt = $db->prepare('SELECT * FROM vertretung WHERE datum = :datum');
	$stmt->bindValue(':datum', $dateKeys['current']['key'], SQLITE3_INTEGER);
	$rows = $stmt->execute();
	
	while($row = $rows->fetchArray(SQLITE3_ASSOC)) {
		$result[] = $row;
	}
	
	return $result;
    }
}