<?php

use Conexion\Database;
use Utils\Auth\Auth;
use Utils\Generar\Generar;

require __DIR__ . '/vendor/autoload.php';
include "./response/respuestas.php";

$dir = './static/';

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Authorization, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header("HTTP/1.1 204 No Content");
    exit();
}

if (!is_dir($dir)) {
    http_response_code(404);
    exit('El directorio no existe.');
}

$env = parse_ini_file(__DIR__ . '/.env');

$db = new Database();
$con = $db->Conexion($env["DRIVER"], $env["HOST"], $env["PORT"], $env["DATABASE"], $env["USER"], $env["PASSWORD"]);

$router = new AltoRouter();

$router->map('POST', '/crear/video', function() use ($con) {
 
    $headers = getallheaders();

    $identificador = Auth::ObtenerSemilla($headers);

    if ($identificador === "") {

        echo RespuestaFail("No se han podido obtener los datos.");
        return;
    }

    
    $miniatura = $_FILES["miniatura"];
    $video = $_FILES["video"];
    $generarIdentificador = new Generar();
    $cadena = $generarIdentificador->GenerarIdentificador($con);

    $archivoDestino = "./static/miniaturas/" .  basename($cadena . "." . explode(".", $miniatura["name"])[1]);
    $archivoDestinoVideo = "./static/videos/" . basename($cadena . "." . explode(".", $video["name"])[1]);
    move_uploaded_file($miniatura["tmp_name"], $archivoDestino);
    move_uploaded_file($video["tmp_name"], $archivoDestinoVideo);
    $titulo = $_POST["titulo"];
    $descripcion = $_POST["descripcion"];
    $duracion = $_POST["duracion"];
    $categoriaNombre = $_POST["categoria"];
    $estadoVideo = $_POST["estado"];

    $usuarioID = "
        SELECT id FROM usuarios WHERE identificador = ?
    ";

    $obtenerUsuarioID = $con->prepare($usuarioID);
    $obtenerUsuarioID->bindParam(1, $identificador);
    $estado = $obtenerUsuarioID->execute();
    $respuesta = $obtenerUsuarioID->fetch(PDO::FETCH_ASSOC);

    if (!$estado)
    {
        echo EstadoFAIL();
        return;
    }

    $usuarioActualID = $respuesta["id"];
    
    $canalID = "
        SELECT nombre_canal, id FROM canales WHERE usuario_id = ?
    ";

    $obtenerCanalID = $con->prepare($canalID);
    $obtenerCanalID->bindParam(1, $usuarioActualID);
    $estadoCID = $obtenerCanalID->execute();
    $respuestaCID = $obtenerCanalID->fetch(PDO::FETCH_ASSOC);

    if (!$estadoCID)
    {
        echo EstadoFAIL();
        return;
    }

    $usuarioNombreCanalActual = $respuestaCID["nombre_canal"];
    $usuarioActualCanalID = $respuestaCID["id"];

    $categoriaID = "
    SELECT id FROM categorias WHERE nombre = ?
    ";

    $obtenerCategoriaID = $con->prepare($categoriaID);
    $obtenerCategoriaID->bindParam(1, $categoriaNombre);
    $estadoCATID = $obtenerCategoriaID->execute();
    $respuestaCATID = $obtenerCategoriaID->fetch(PDO::FETCH_ASSOC);

    if (!$estadoCATID)
    {
        echo EstadoFAIL();
        return;
    }

    $categoriaActualID = $respuestaCATID["id"];
    $rutaVideo = "http://localhost:8081/file?file=./videos/" . $cadena . "." . explode(".", $video["name"])[1];
    $rutaMiniatura = "http://localhost:8081/file?file=./miniaturas/" . $cadena . "." . explode(".", $miniatura["name"])[1];

    $q = "INSERT INTO videos(canal_id, titulo, descripcion, categoria_id, video, identificador, miniatura, duracion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";


    $crearVideo = $con->prepare($q);
    $crearVideo->bindParam(1, $usuarioActualCanalID);
    $crearVideo->bindParam(2, $titulo);
    $crearVideo->bindParam(3, $descripcion);
    $crearVideo->bindParam(4, $categoriaActualID);
    $crearVideo->bindParam(5, $rutaVideo);
    $crearVideo->bindParam(6, $cadena);
    $crearVideo->bindParam(7, $rutaMiniatura);
    $crearVideo->bindParam(8, $duracion);
    $crearVideo->bindParam(9, $estadoVideo);

    
    $estadoCV = $crearVideo->execute();

    if (!$estadoCV)
    {
        echo EstadoFAIL();
        return;
    }

    if ($estadoVideo === "publico")
    {
        $videoRecienSubidoID = $con->lastInsertId();

        $obtenerUsuariosSuscritos = "SELECT usuario_id FROM suscripciones WHERE canal_id = ?";

        $suscriptoresID = $con->prepare($obtenerUsuariosSuscritos);
        $suscriptoresID->bindParam(1, $usuarioActualCanalID);
        $estadoSID = $suscriptoresID->execute();
        $respuestaSID = $suscriptoresID->fetchAll(PDO::FETCH_ASSOC);

        if (!$estadoSID)
        {
            echo EstadoFAIL();
            return;
        }

        for($i = 0; $i < count($respuestaSID); $i++)
        {
            $enviarNotificaciones = "INSERT INTO notificaciones (usuario_id, video_id, canal_id, enlace) VALUES (?, ?, ?, ?)";

            $sxEN = $con->prepare($enviarNotificaciones);
            $sxEN->bindParam(1, $respuestaSID[$i]["usuario_id"]);
            $sxEN->bindParam(2, $videoRecienSubidoID);
            $sxEN->bindParam(3, $usuarioActualCanalID);
            $sxEN->bindParam(4, $cadena);

            $estado = $sxEN->execute();

            if (!$estado)
            {
                echo EstadoFAIL();
                return;
            }
            
        }
    }

    echo RespuestaOK([
        "canal" => $usuarioNombreCanalActual,
        "estado" => $estadoVideo,
    ]);

});


$router->map('GET', '/file', function() use ($dir) {
    $file = isset($_GET['file']) ? $_GET['file'] : '';

    $filePath = realpath($dir . $file);

    if ($filePath === false || strpos($filePath, realpath($dir)) !== 0) {
        http_response_code(403);
        exit('Acceso no permitido.');
    }

    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }

    $size = filesize($filePath);
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'pdf' => 'application/pdf',
    ];
    $mimeType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    $fp = fopen($filePath, 'rb');

    header("Content-Type: $mimeType");
    header("Accept-Ranges: bytes");
    

    $start = 0;
    $end = $size - 1;

    if (isset($_SERVER['HTTP_RANGE'])) {
        if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = ($matches[1] !== '') ? intval($matches[1]) : 0;
            $end = ($matches[2] !== '') ? intval($matches[2]) : $end;

            if ($start > $end || $start >= $size || $end >= $size) {
                header("HTTP/1.1 416 Requested Range Not Satisfiable");
                header("Content-Range: bytes */$size");
                exit;
            }

            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes $start-$end/$size");
        }
    }

    $length = $end - $start + 1;
    header("Content-Length: $length");

    fseek($fp, $start);

    $bufferSize = 8192;
    while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
        if ($pos + $bufferSize > $end) {
            $bufferSize = $end - $pos + 1;
        }
        echo fread($fp, $bufferSize);
        flush();
    }

    fclose($fp);
});


$match = $router->match();

if ($match && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    echo '404 No encontrado';
}
