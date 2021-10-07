<pre>
<?php
 function process_markets($array, $sportname)
 {
   $ret = array();


   foreach($array as $key => $value)
    {
      list($mt, $gndcp) = explode('_', $key);

 /////////// не успел
    }
 }

/////////////    (1 / $max_handicap_1) + (1 / $max_handicap_2)

$json = json_decode(file_get_contents('http://scanfork.org/data/live/results.json'), true);

$content = array();

foreach($json as $act => $values)
{
 $sport    = array();
 $league   = array();
 $home     = array();
 $away     = array();
 $markets  = array();
 $forks    = array();
 $members  = array();

   foreach($values as $bm_name => $value)
    {
      if(isset($value['Odds']))
       {
        foreach($value as $member_key => $member_value)
         {
           if($member_key == 'Odds')
            {
               foreach($member_value as $market_value)
                {
                  if(!empty($market_value['Coefficient']))
                    {
                     $mt    = $market_value['MarketType'];
                     $gndcp = isset($market_value['Handicap']) ? '_'.(int)(1000 * abs($market_value['Handicap'])) : '_empty';
                     $mkey  = 'mt'.$mt.$gndcp;
                     $markets[$mkey][$bm_name] = $market_value['Coefficient'];
                    }
                }
            }
             elseif($member_key == 'Sport')
            {
              $sport[] = $member_value;
            }
             elseif($member_key == 'League')
            {
              $league[] = $member_value;
            }
             elseif($member_key == 'Home')
            {
              $home[] = $member_value;
            }
             elseif($member_key == 'Away')
            {
              $away[] = $member_value;
            }
             else
            {
              $members[$bm_name][$member_key] = $member_value;
            }

         }

       }
    }

 if(count($members) > 1)
  {
   $content[$act]['members'] = $members;

   if(!empty($sport))
   {
     $sname = array_unique($sport);
     $content[$act]['sport'] = $sname[0];
   }

   if(!empty($league))
   {
     $lname = array_unique($league);
     $content[$act]['league'] = $lname[0];
   }

   if(!empty($home))
   {
     $hname = array_unique($home);
     $content[$act]['home'] = $hname[0];
   }

   if(!empty($away))
   {
     $aname = array_unique($away);
     $content[$act]['away'] = $aname[0];
   }

   if(!empty($markets))
   {
     $content[$act]['markets'] = $markets;
//   $forks = process_markets($markets, $sname[0]);
   }

   if(!empty($forks))
   {
     $content[$act]['forks'] = $forks;
   }
  }
}

print_r($content);

//echo count($content);
?>
