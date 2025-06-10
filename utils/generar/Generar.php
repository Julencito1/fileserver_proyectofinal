<?php


namespace Utils\Generar;
use Utils\Caracteres\Caracteres;
use PDO;


class Generar 
{


    public function GenerarIdentificador($con)
    {

        $estado = true;

        while ($estado) {
            $identificador = "";

            for ($x = 0; $x < 36; $x++) {

                $identificador .= Caracteres::$letras_numeros[rand(0, count(Caracteres::$letras_numeros) - 1)];
            }

            $consultaExiste = $con->prepare("SELECT COUNT(*) AS existe FROM videos WHERE identificador = :identificador");

            $consultaExiste->execute(["identificador" => $identificador]);

            $respuesta = $consultaExiste->fetch(PDO::FETCH_ASSOC);

            if ($respuesta["existe"] === 0) {
                return $identificador;
            }


        }

        return "";

    }


    
}



?>