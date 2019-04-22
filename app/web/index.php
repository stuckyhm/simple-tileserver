<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App([
	'settings' => [
		'tilesDir' => "../tiles",
		'defaultPattern' => "{z}/{x}/{y}.png",
		'defaultType' => "plain"
    ],
]);

// Get container
$container = $app->getContainer();

$app->get('/', function (Request $request, Response $response, $args) {
	$tilesDir = $this->get('settings')['tilesDir'];
	$pattern = $this->get('settings')['defaultPattern'];

	if(file_exists($tilesDir)){
		foreach(scandir($tilesDir) as $map){
			$jsonFile = $tilesDir.'/'.$map.'/map.json';
			if(file_exists($jsonFile)){
				$json = json_decode(file_get_contents($jsonFile), true);
				$pattern = $json['pattern'];
				$type = $json['type'];
			}

			$mapUrl = $_SERVER['REQUEST_SCHEME']	.'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'].$map.'/'.$pattern;
			
			if(is_dir($tilesDir.'/'.$map) && substr($map, 0, 1) != '.'){
				$result .= '<a href="'.$map.'">'.$map.'</a> - '.$mapUrl.'<br>';
			}
		}

		return $result;
	}else{
		return $response->withStatus(404);
	}
});

$app->get('/{map:[a-zA-Z0-9_]+}/{z:[0-9]+}/{x:[0-9]+}/{y:[0-9]+}[.png]', function (Request $request, Response $response, $args) {
	$tilesDir = $this->get('settings')['tilesDir'];
	$pattern = $this->get('settings')['defaultPattern'];
	$type = $this->get('settings')['defaultType'];

	$map = $args['map'];
	$z = $args['z'];
	$x = $args['x'];
	$y = $args['y'];

	$jsonFile = $tilesDir.'/'.$map.'/map.json';
	if(file_exists($jsonFile)){
		$json = json_decode(file_get_contents($jsonFile), true);
		$pattern = $json['pattern'];
		$type = $json['type'];
	}

	if($type == 'submap'){
		$map_z = 20;
		do{
			$map_x = sprintf('%d', floor($x/pow(2, $z - $map_z)));
			$map_y = sprintf('%d', floor($y/pow(2, $z - $map_z)));
			$submap = $map_z.'_'.$map_x.'_'.$map_y;
			$map_z--;
	
			$file = str_replace('{x}', $x, str_replace('{y}', $y, str_replace('{z}', $z, $pattern)));
			$image = $tilesDir.'/'.$map.'/'.$submap.'/'.$file;
		}while(!file_exists($image) && $map_z >= 0);
	}else{
		$file = str_replace('{x}', $x, str_replace('{y}', $y, str_replace('{z}', $z, $pattern)));
		$image = $tilesDir.'/'.$map.'/'.$file;
	}

	if(file_exists($image)){
		$stream = new \GuzzleHttp\Psr7\LazyOpenStream($image, 'r');
		return $response->withHeader('Content-type', 'image/png')->withBody($stream);
	}else{
		return $response->withStatus(404)->write($image);
	}
});

$app->run();