<?php

namespace Conexion;
use PDO;
use Exception;

class Database 
{
    
    public function Conexion($Driver, $Host, $Port, $Database, $User, $Password) 
    {
        try
        {
            $con = new PDO($Driver . ":host=" . $Host . ";port=" . $Port . ";dbname=" . $Database, $User, $Password);
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $con;
        }
        catch (Exception $e) 
        {
            echo $e->getMessage();
        }
    }

}







?>