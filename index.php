<?php

const SUZURI_TOKEN = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';//①アクセストークン https://suzuri.jp/developer/apps
const SUZURI_CACHE_LIMIT = 86400;                                  //キャッシュ保管期限1日
const SUZURI_DISPLAY_COUNT = 10;                                   //表示個数
const SUZURI_URL = "https://suzuri.jp/api/v1/products?userName=";  //suzuriAPI
const STORE_NAME = "XXXXXXXXX";                                    //②SUZURIでのID

//suzuri商品を出力
function suzuri_func() {
    
    $apiurl = SUZURI_URL.STORE_NAME;
    
    //キャッシュor新規取得
    $result = getCacheContents($apiurl);
    
    //jsonTOhtml成形
    $html = editJsonToHtml($result);
        
    return $html;
}
// add_shortcode('SUZURI_ITEM', 'suzuri_func');//Wordpressテーマのfunction.phpでショートコードとして登録
echo suzuri_func(); //phpで関数起動

// file_get_contentsの結果をキャッシュしつつ返す
function getCacheContents($apiurl) {
    $cachePath = dirname(__FILE__)."/cachefile";
    if(file_exists($cachePath) && (filemtime($cachePath) + SUZURI_CACHE_LIMIT) > time()) {
        // キャッシュ有効期間内なのでキャッシュの内容を返す
// 		do_action( 'qm/debug', "aaaキャッシュ" );
        // echo "aaaa";
        return file_get_contents($cachePath);
    } else {
        // キャッシュがないか、期限切れなので取得しなおす
// 		do_action( 'qm/debug', "bbb新規取得");
        // echo "bbbb";
        $data = getSuzuriApi($apiurl);
        file_put_contents($cachePath, $data, LOCK_EX); // キャッシュに保存
        return $data;
    }
}

//suzuriAPIにアクセスしてjsonファイルを取得
function getSuzuriApi($apiurl) {
    
    $curl = curl_init($apiurl);
    $option = [
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_RETURNTRANSFER => true,//curlの結果を自動で表示させない
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer ".SUZURI_TOKEN,  //アクセストークン
            "Content-Type: application/json",
            ],
    ];
    curl_setopt_array($curl, $option);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

//jsonファイルからhtml要素に変換
function editJsonToHtml($result){

    $arrayData = json_decode($result,true);
    if($arrayData){
        if (array_key_exists('products', $arrayData)) {
            
            $productData = array();
            $html = "";
            $html .= "<div class='base_items suzuri_items'>";
            $html .= "<ul class='base_items_list suzuri_items_list'>";
            $suzuriCnt = 0;
            foreach($arrayData["products"] as $key => $val){
                // $productData[] = array(
                //     "title" => $val["title"],
                //     "sampleImageUrl" => $val["sampleImageUrl"],
                //     "sampleUrl" => $val["sampleUrl"],
                // );
    		    $html .= "<li class='base_item suzuri_item'>";
    			$html .= "    <dt><span class='base_item_title suzuri_item_title'>{$val['title']}</span></dt>";
    			$html .= "	<dd>";
    			$html .= "		<a href='{$val["sampleUrl"]}' target='_blank'>";
    			$html .= "			<img src='{$val["sampleImageUrl"]}' alt='{$val['title']}'>";
    			$html .= "		</a>";
    			$html .= "    </dd>";
    			$html .= "</li>";
    			$suzuriCnt++;
    			if(SUZURI_DISPLAY_COUNT <= $suzuriCnt){
    			    break;
    			}
            }
            $html .= "</ul>";
            $html .= "</div>";
        }else{
            $html = "<pre>".var_dump($arrayData)."</pre>";
        }
    }

    return $html;
}