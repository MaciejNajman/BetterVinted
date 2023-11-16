<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomepageController extends Controller {
  public function homepage(Request $request, Response $response){
    return $this->render($response, 'homepage.html');
  }
}
?>
