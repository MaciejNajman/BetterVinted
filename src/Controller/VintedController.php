<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VintedController extends Controller {

  public function get_cookie($url){
      $options = array(
          CURLOPT_RETURNTRANSFER => true,     // return web page
          CURLOPT_HEADER         => true,     //return headers in addition to content
          CURLOPT_FOLLOWLOCATION => true,     // follow redirects
          CURLOPT_ENCODING       => "",       // handle all encodings
          CURLOPT_AUTOREFERER    => true,     // set referer on redirect
          CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
          CURLOPT_TIMEOUT        => 120,      // timeout on response
          CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
          CURLINFO_HEADER_OUT    => true,
          CURLOPT_SSL_VERIFYPEER => true,     // Validate SSL Certificates
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1
      );

      $ch      = curl_init( $url );
      curl_setopt_array( $ch, $options );
      $rough_content = curl_exec( $ch );
      $err     = curl_errno( $ch );
      $errmsg  = curl_error( $ch );
      $header  = curl_getinfo( $ch );
      curl_close( $ch );

      $header_content = substr($rough_content, 0, $header['header_size']);
      $body_content = trim(str_replace($header_content, '', $rough_content));
      $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
      preg_match_all($pattern, $header_content, $matches); 
      $cookiesOut = implode("; ", $matches['cookie']);

      $header['errno']   = $err;
      $header['errmsg']  = $errmsg;
      $header['headers']  = $header_content;
      $header['content'] = $body_content;
      $header['cookies'] = $cookiesOut;

      $link = $header['cookies'];
      $pos_start = strpos($link, '_vinted_fr_session');
      $sub_string = substr($link, $pos_start);
      $pos_end = strpos($sub_string, ';');
      $sub_string = substr($sub_string, 19, $pos_end-19);
      return $sub_string;
  }

  public function get_web_page( $url, $cookiesIn = '' ){
      $options = array(
          CURLOPT_RETURNTRANSFER => true,     // return web page
          CURLOPT_HEADER         => true,     //return headers in addition to content
          CURLOPT_FOLLOWLOCATION => true,     // follow redirects
          CURLOPT_ENCODING       => "",       // handle all encodings
          CURLOPT_AUTOREFERER    => true,     // set referer on redirect
          CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
          CURLOPT_TIMEOUT        => 120,      // timeout on response
          CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
          CURLINFO_HEADER_OUT    => true,
          CURLOPT_SSL_VERIFYPEER => true,     // Validate SSL Certificates
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_COOKIE         => $cookiesIn
      );

      $ch      = curl_init( $url );
      curl_setopt_array( $ch, $options );
      $rough_content = curl_exec( $ch );
      $err     = curl_errno( $ch );
      $errmsg  = curl_error( $ch );
      $header  = curl_getinfo( $ch );
      curl_close( $ch );

      $header_content = substr($rough_content, 0, $header['header_size']);
      $body_content = trim(str_replace($header_content, '', $rough_content));
      $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
      preg_match_all($pattern, $header_content, $matches); 
      $cookiesOut = implode("; ", $matches['cookie']);

      $header['errno']   = $err;
      $header['errmsg']  = $errmsg;
      $header['headers']  = $header_content;
      $header['content'] = $body_content;
      $header['cookies'] = $cookiesOut;
      return $header['content'];
  }

  public function search(Request $request, Response $response, array $args){
    $queryValue = rawurlencode($request->getQueryParam('query'));
    $cntValue = $request->getQueryParam('cnt');
    $sortValue = $request->getQueryParam('sort');

    //if($queryValue == ''){
     // return $response->withStatus(400)->withJson(['error' => 'Invalid query']);
    //}
    
    if(!empty($queryValue) && !empty($cntValue)) {
      $query = $this->test_input($queryValue);
      $cnt = $this->test_input($cntValue);
      if ($query && $cnt){
        $cookie = $this->get_cookie("https://www.vinted.pl/");
        for ($x = 0; $x <= 10; $x++) {
          //$data = json_decode($this->get_web_page('https://www.vinted.pl/api/v2/catalog/items?search_text='.$query.'&page='.$cnt, '_vinted_fr_session='.$cookie), true);
          $data = json_decode(file_get_contents(__DIR__ . '/../../data/albums.json'), true);
          //if (is_countable($data) && count($data['items']) != 0){
          //  break;
          //}
        }
      }
      //Sortowanie
      switch ($sortValue) {
        case 0:
          usort($data['items'], fn($b, $a) => $a['favourite_count'] <=> $b['favourite_count']);
          $sortmes="Po serduszkach rosnąco";
            break;
        case 1:
          usort($data['items'], fn($b, $a) => $b['favourite_count'] <=> $a['favourite_count']);
          $sortmes="Po serduszkach malejąco";
            break;
        case 2:
          usort($data['items'], fn($b, $a) => $a['price'] <=> $b['price']);
          $sortmes="Po cenie rosnąco";
            break;
        case 3:
          usort($data['items'], fn($a, $b) => $a['price'] <=> $b['price']);
          $sortmes="Po cenie malejąco";
            break;
        default:
    }

      return $this->render($response, 'vintedSearch.html', [
        'data' => $data,
        'adidas' =>$queryValue,
        'cnt' =>$cntValue,
        'sortmes'=>$sortmes
      ]);
    }
    else if(!empty($queryValue)) {
      $query = $this->test_input($queryValue);
      if ($query){
        $cookie = $this->get_cookie("https://www.vinted.pl/");
        for ($x = 0; $x <= 10; $x++) {
          $data = json_decode($this->get_web_page('https://www.vinted.pl/api/v2/catalog/items?search_text='.$query, '_vinted_fr_session='.$cookie), true);
          if (is_countable($data) && count($data['items']) != 0){
            break;
          }
        }
      }

      //Sortowanie
      switch ($sortValue) {
        case 0:
          usort($data['items'], fn($b, $a) => $a['favourite_count'] <=> $b['favourite_count']);
          $sortmes="Po serduszkach rosnąco";
            break;
        case 1:
          usort($data['items'], fn($b, $a) => $b['favourite_count'] <=> $a['favourite_count']);
          $sortmes="Po serduszkach malejąco";
            break;
        case 2:
          usort($data['items'], fn($b, $a) => $a['price'] <=> $b['price']);
          $sortmes="Po cenie rosnąco";
            break;
        case 3:
          usort($data['items'], fn($a, $b) => $a['price'] <=> $b['price']);
          $sortmes="Po cenie malejąco";
            break;
        default:
    } 

    return $this->render($response, 'vintedSearch.html', [
      'data' => $data,
      'adidas' =>$queryValue,
      'cnt' =>$cntValue,
      'sortmes'=>$sortmes
    ]);
    }else{
      //return $response->withStatus(400)->withJson(['error' => 'Invalid query']);
    }
  }

  public function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
}

?>
