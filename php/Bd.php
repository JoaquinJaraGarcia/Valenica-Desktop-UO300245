<?php
/**
 * Clase Bd - Conexion a la base de datos (patron Singleton).
 *
 * Se utiliza Singleton para garantizar que en toda la ejecucion
 * de reservas.php solo exista una conexion activa a MySQL,
 * evitando conexiones redundantes y mejorando el rendimiento.
 */
class Bd {

    private static ?Bd $instancia = null;
    private mysqli $conexion;

    /** Constructor privado: impide instanciacion directa. */
    private function __construct() {
        $this->conexion = new mysqli('localhost', 'DBUSER2026', 'DBPWD2026', 'UO300245_DB');

        if ($this->conexion->connect_error) {
            die('Error de conexion a la base de datos: ' . $this->conexion->connect_error);
        }

        $this->conexion->set_charset('utf8mb4');
    }

    /** Devuelve la unica instancia de Bd (crea si no existe). */
    public static function getInstancia(): Bd {
        if (self::$instancia === null) {
            self::$instancia = new Bd();
        }
        return self::$instancia;
    }

    /** Devuelve el objeto mysqli para ejecutar consultas. */
    public function getConexion(): mysqli {
        return $this->conexion;
    }

    /** Evita la clonacion del Singleton. */
    private function __clone() {}
}
