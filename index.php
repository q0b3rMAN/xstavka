<?php

    // Модуль получения данных с xstavka

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
    
    $result = []; //забудьте про array(), это очень старая фигня
    $rc->setCallback(function(\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) { 
    $t = json_decode($request->getResponseText());
        $new = []; //не забудьте объявить $new
        foreach($t as $key=>$value) {
           $new[$key]['ID'] = $value->I;  
        }
        $result[] = $new; //в массив $result мы добавляем массив $new
        //print_r($t);
       return $result;
    });
    $rs->execute();
    //print_r($result);

