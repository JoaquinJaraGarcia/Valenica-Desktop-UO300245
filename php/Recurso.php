<?php
require_once __DIR__ . '/Bd.php';

/**
 * Clase Recurso - Gestion de recursos turisticos reservables.
 *
 * Encapsula el acceso a la tabla 'recursos' y calcula dinamicamente
 * las plazas disponibles en funcion de las reservas confirmadas,
 * sin necesitar un campo 'plazas_ocupadas' extra que podria
 * desincronizarse con el estado real de la tabla reservas.
 */
class Recurso {

    private int    $id;
    private int    $tipoId;
    private string $tipoNombre;
    private string $nombre;
    private string $descripcion;
    private int    $plazasTotales;
    private float  $precio;
    private string $fechaInicio;
    private string $fechaFin;
    private int    $plazasDisponibles;

    public function __construct(
        int    $id,
        int    $tipoId,
        string $tipoNombre,
        string $nombre,
        string $descripcion,
        int    $plazasTotales,
        float  $precio,
        string $fechaInicio,
        string $fechaFin,
        int    $plazasDisponibles
    ) {
        $this->id                = $id;
        $this->tipoId            = $tipoId;
        $this->tipoNombre        = $tipoNombre;
        $this->nombre            = $nombre;
        $this->descripcion       = $descripcion;
        $this->plazasTotales     = $plazasTotales;
        $this->precio            = $precio;
        $this->fechaInicio       = $fechaInicio;
        $this->fechaFin          = $fechaFin;
        $this->plazasDisponibles = $plazasDisponibles;
    }

    // ── Getters ────────────────────────────────────────────────
    public function getId():                int    { return $this->id; }
    public function getTipoNombre():        string { return $this->tipoNombre; }
    public function getNombre():            string { return $this->nombre; }
    public function getDescripcion():       string { return $this->descripcion; }
    public function getPlazasTotales():     int    { return $this->plazasTotales; }
    public function getPrecio():            float  { return $this->precio; }
    public function getFechaInicio():       string { return $this->fechaInicio; }
    public function getFechaFin():          string { return $this->fechaFin; }
    public function getPlazasDisponibles(): int    { return $this->plazasDisponibles; }

    public function tieneDisponibilidad(): bool {
        return $this->plazasDisponibles > 0;
    }

    // ── Consulta SQL comun con calculo de plazas disponibles ───

    /**
     * Query base que une recursos con tipos y calcula plazas disponibles.
     * Las plazas ocupadas se calculan sumando num_personas de las reservas
     * con estado 'Confirmada', lo que siempre refleja el estado real.
     */
    private static function queryBase(): string {
        return "
            SELECT
                r.id,
                r.tipo_id,
                tr.nombre     AS tipo_nombre,
                r.nombre,
                r.descripcion,
                r.plazas_totales,
                r.precio,
                DATE_FORMAT(r.fecha_inicio, '%d/%m/%Y %H:%i') AS fecha_inicio,
                DATE_FORMAT(r.fecha_fin,    '%d/%m/%Y %H:%i') AS fecha_fin,
                r.plazas_totales - COALESCE(
                    (SELECT SUM(res.num_personas)
                     FROM reservas res
                     WHERE res.recurso_id = r.id AND res.estado = 'Confirmada'),
                0) AS plazas_disponibles
            FROM recursos r
            JOIN tipos_recurso tr ON r.tipo_id = tr.id
        ";
    }

    private static function filaAObjeto(array $fila): Recurso {
        return new Recurso(
            (int)$fila['id'],
            (int)$fila['tipo_id'],
            $fila['tipo_nombre'],
            $fila['nombre'],
            $fila['descripcion'],
            (int)$fila['plazas_totales'],
            (float)$fila['precio'],
            $fila['fecha_inicio'],
            $fila['fecha_fin'],
            (int)$fila['plazas_disponibles']
        );
    }

    // ── Operaciones estaticas ──────────────────────────────────

    /**
     * Devuelve todos los recursos ordenados por nombre.
     *
     * @return Recurso[]
     */
    public static function obtenerTodos(): array {
        $con    = Bd::getInstancia()->getConexion();
        $sql    = self::queryBase() . ' ORDER BY r.nombre';
        $result = $con->query($sql);

        $recursos = [];
        while ($fila = $result->fetch_assoc()) {
            $recursos[] = self::filaAObjeto($fila);
        }
        return $recursos;
    }

    /**
     * Devuelve un recurso por su id, o null si no existe.
     */
    public static function obtenerPorId(int $id): ?Recurso {
        $con  = Bd::getInstancia()->getConexion();
        $sql  = self::queryBase() . ' WHERE r.id = ?';
        $stmt = $con->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }

        $fila = $result->fetch_assoc();
        $stmt->close();
        return self::filaAObjeto($fila);
    }
}
