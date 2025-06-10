<?php


define("CODIGO_OK", 200);
define("EXITO", "exito");
define("CODIGO_FAIL", 500);
define("FALLIDO", "fallido");

function RespuestaOK($mensaje)
{
    return json_encode(["codigo" => CODIGO_OK, "mensaje" => $mensaje, "estado" => EXITO], JSON_PRETTY_PRINT);
}

function EstadoOK()
{
    return json_encode(["codigo" => CODIGO_OK, "estado" => EXITO], JSON_PRETTY_PRINT);
}

function RespuestaFail($mensaje, $codigo = CODIGO_FAIL)
{
    return json_encode(["codigo" => $codigo, "mensaje" => $mensaje, "estado" => FALLIDO], JSON_PRETTY_PRINT);
}

function EstadoFAIL()
{
    return json_encode(["codigo" => CODIGO_FAIL, "estado" => FALLIDO], JSON_PRETTY_PRINT);
}

function InternalServerError()
{
    return json_encode(["codigo" => CODIGO_FAIL, "mensaje" => "Algo ha salido mal", "estado" => FALLIDO], JSON_PRETTY_PRINT);
}






?>