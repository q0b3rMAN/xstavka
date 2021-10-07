<?php
    $start = microtime(true);

    // Модуль получения данных с xstavka
    // https://scanfork.org

    require __DIR__ . '/lib/RollingCurl.php';
    require __DIR__ . '/lib/Request.php';

    // Получаем список всех матчей лайва
    $ch = curl_init("https://1xstavka.ru/LiveFeed/Get1x2_VZip?count=1000&mode=4&top=true&partner=5");
    $fp = fopen('data/xstavka_list.json', "w");
 
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
 
    curl_exec($ch);
    if(curl_error($ch)) {
        fwrite($fp, curl_error($ch));
    }
 
    curl_close($ch);
    fclose($fp);
    
    // Получаем данные по каждому матчу
    $json = file_get_contents('data/xstavka_list.json');
    $ids = json_decode($json, true);
 
    $rc = new \RollingCurl\RollingCurl();
    $rc->window_size = 20; // Количество одновременных соединений

    foreach ($ids['Value'] as $value) {     
        $rc->get('https://1xstavka.ru/LiveFeed/GetGameZip?id='.$value['I']); // Формируем очередь запросов  
    }
    
    $result = [];
    $rc->setCallback(function(\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) use (&$result) { 
    $t = json_decode($request->getResponseText());
        //print_r($t);
        $new = [];

        // Формируем свой массив
        foreach($t as $key=>$value) {

            $new['ID'] = $value->I; // ID матча
            $new['Sport'] = $value->SN; // Вид спорта
            $new['League'] = $value->L; // Лига
            $new['Home'] = $value->O1; // Хозяева
            $new['Away'] = $value->O2; // Гости       

            // Создаем ссылку на матч
            $SE = str_replace('.', '', $value->SE);
            $SE = str_replace(array('(',')','+','/'), array('','','',''), $SE);          
            $SE = preg_replace('![\s]+!', "-" , $SE);

            $LE = str_replace('.', '', $value->LE);
            $LE = str_replace(array('(',')','+','/'), array('','','',''), $LE);                       
            $LE = preg_replace('![\s]+!', "-" , $LE);

            $HOME = str_replace('.', '', $value->O1E);
            $HOME = str_replace(array('(',')','+','/'), array('','','',''), $HOME); 
            $HOME = preg_replace('![\s]+!', "-" , $HOME);

            $AWAY = str_replace('.', '', $value->O2E);
            $AWAY = str_replace(array('(',')','+','/'), array('','','',''), $AWAY);           
            $AWAY = preg_replace('![\s]+!', "-" , $AWAY); 
            
            $new['Href'] = 'https://1xstavka.ru/live/'.$SE.'/'.$value->LI.'-'.$LE.'/'.$value->I.'-'.$HOME.'-'.$AWAY.'/';

            // Если есть коээфиценты
            if (isset($value->E)) {
                foreach ($value->E as $key_c => $value_c) {

                    // У меня свое обозначение маркетов, так удобнее
                    if ($value_c->T == 1) {
                        $new['Odds'][1]['MarketType'] = $value_c->T; // Тип
                        $new['Odds'][1]['MarketName'] = 'П1'; // Название
                        $new['Odds'][1]['Coefficient'] = $value_c->C; // КФ
                    }

                    if ($value_c->T == 2) {
                        $new['Odds'][2]['MarketType'] = $value_c->T;
                        $new['Odds'][2]['MarketName'] = 'Ничья';
                        $new['Odds'][2]['Coefficient'] = $value_c->C;
                    } 
                    
                    if ($value_c->T == 3) {
                        $new['Odds'][3]['MarketType'] = $value_c->T;
                        $new['Odds'][3]['MarketName'] = 'П2';
                        $new['Odds'][3]['Coefficient'] = $value_c->C;
                    }
                    
                    if ($value_c->T == 4) {
                        $new['Odds'][4]['MarketType'] = $value_c->T;
                        $new['Odds'][4]['MarketName'] = '1X';
                        $new['Odds'][4]['Coefficient'] = $value_c->C;
                    }   
                    
                    if ($value_c->T == 5) {
                        $new['Odds'][5]['MarketType'] = $value_c->T;
                        $new['Odds'][5]['MarketName'] = '12';
                        $new['Odds'][5]['Coefficient'] = $value_c->C;
                    }  
                    
                    if ($value_c->T == 6) {
                        $new['Odds'][6]['MarketType'] = $value_c->T;
                        $new['Odds'][6]['MarketName'] = '2X';
                        $new['Odds'][6]['Coefficient'] = $value_c->C;
                    }   
                }               
            }
        }

        $result[] = $new;
        return $result;
    });
    
    $rc->execute(); // Запускаем
    
    // echo '<pre>';
    //print_r($result);

    // Запишем результаты в файл
    if(file_put_contents('data/xstavka_result.json', json_encode($result))) {
        echo 'Status OK! Results processed in '; echo $time = microtime(true) - $start;
    }