<?php
require_once __DIR__ . '/Bd.php';

/**
 * Clase Presupuesto - Generacion y consulta de presupuestos.
 *
 * Un presupuesto es el paso intermedio obligatorio entre
 * seleccionar un recurso y confirmar una reserva.
 * Almacena el precio unitario en el momento de su generacion,
 * de modo que futuras subidas de precio no afecten a presupuestos
 * ya generados.
 */
class Presupuesto {

    private int    $id;
    private int    $usuarioId;
    private int    $recursoId;
    private int    $numPersonas;
    private float  $precioUnitario;
    private float  $precioTotal;
    private string $fechaGeneracion;

    public function __construct(
        int    $id,
        int    $usuarioId,
        int    $recursoId,
        int    $numPersonas,
        float  $precioUnitario,
        float  $precioTotal,
        string $fechaGeneracion
    ) {
        $this->id              = $id;
        $this->usuarioId       = $usuarioId;
        $this->recursoId       = $recursoId;
        $this->numPersonas     = $numPersonas;
        $this->precioUnitario  = $precioUnitario;
        $this->precioTotal     = $precioTotal;
        $this->fechaGeneracion = $fechaGeneracion;
    }

    // ── Getters ────────────────────────────────────────────────
    public function getId():              int    { return $this->id; }
    public function getUsuarioId():       int    { return $this->usuarioId; }
    public function getRecursoId():       int    { return $this->recursoId; }
    public function getNumPersonas():     int    { return $this->numPersonas; }
    public function getPrecioUnitario():  float  { return $this->precioUnitario; }
    public function getPrecioTotal():     float  { return $this->precioTotal; }
    public function getFechaGeneracion(): string { return $this->fechaGeneracion; }

    // ── Operaciones estaticas ──────────────────────────────────

    /**
     * Genera y persiste un nuevo presupuesto.
     * El precio total es: num_personas * precio del recurso en este momento.
     *
     * @return Presupuesto si se crea correctamente, null si hay error.
     */
    public static function generar(int $usuarioId, int $recursoId, int $numPersonas, float $precioUnitario): ?Presupuesto {
        $precioTotal = round($numPersonas * $precioUnitario, 2);
        $con         = Bd::getInstancia()->getConexion();

        $stmt = $con->prepare(
            'INSERT INTO presupuestos (usuario_id, recurso_id, num_personas, precio_unitario, precio_total)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iiidd', $usuarioId, $recursoId, $numPersonas, $precioUnitario, $precioTotal);

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $nuevoId = (int)$stmt->insert_id;
        $stmt->close();

        return self::obtenerPorId($nuevoId);
    }

    /**
     * Devuelve un presupuesto por su id validando que pertenece al usuario indicado.
     * La comprobacion de usuario_id evita que un usuario acceda a presupuestos ajenos.
     */
    public static function obtenerPorIdYUsuario(int $id, int $usuarioId): ?Presupuesto {
        $con  = Bd::getInstancia()->getConexion();
        $stmt = $con->prepare(
            "SELECT id, usuario_id, recurso_id, num_personas, precio_unitario, precio_total,
                    DATE_FORMAT(fecha_generacion, '%d/%m/%Y %H:%i') AS fecha_generacion
             FROM presupuestos WHERE id = ? AND usuario_id = ?"
        );
        $stmt->bind_param('ii', $id, $usuarioId);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }

        $fila = $result->fetch_assoc();
        $stmt->close();

        return new Presupuesto(
            (int)$fila['id'],
            (int)$fila['usuario_id'],
            (int)$fila['recurso_id'],
            (int)$fila['num_personas'],
            (float)$fila['precio_unitario'],
            (float)$fila['precio_total'],
            $fila['fecha_generacion']
        );
    }

    /** Obtiene un presupuesto por id sin restriccion de usuario (uso interno). */
    public static function obtenerPorId(int $id): ?Presupuesto {
        $con  = Bd::getInstancia()->getConexion();
        $stmt = $con->prepare(
            "SELECT id, usuario_id, recurso_id, num_personas, precio_unitario, precio_total,
                    DATE_FORMAT(fecha_generacion, '%d/%m/%Y %H:%i') AS fecha_generacion
             FROM presupuestos WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }

        $fila = $result->fetch_assoc();
        $stmt->close();

        return new Presupuesto(
            (int)$fila['id'],
            (int)$fila['usuario_id'],
            (int)$fila['recurso_id'],
            (int)$fila['num_personas'],
            (float)$fila['precio_unitario'],
            (float)$fila['precio_total'],
            $fila['fecha_generacion']
        );
    }
}
