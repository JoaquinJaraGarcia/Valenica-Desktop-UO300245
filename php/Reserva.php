<?php
require_once __DIR__ . '/Bd.php';

/**
 * Clase Reserva - Gestion de reservas de recursos turisticos.
 *
 * Cubre las operaciones requeridas por el enunciado:
 *   - Confirmar una reserva a partir de un presupuesto existente.
 *   - Listar las reservas del usuario.
 *   - Anular una reserva del usuario.
 *
 * Antes de confirmar se verifican las plazas disponibles para
 * evitar sobreventa de un recurso.
 */
class Reserva {

    private int    $id;
    private int    $usuarioId;
    private int    $recursoId;
    private string $recursoNombre;
    private int    $presupuestoId;
    private int    $numPersonas;
    private float  $precioTotal;
    private string $fechaReserva;
    private string $estado;
    private string $fechaInicioRecurso;
    private string $fechaFinRecurso;

    public function __construct(
        int    $id,
        int    $usuarioId,
        int    $recursoId,
        string $recursoNombre,
        int    $presupuestoId,
        int    $numPersonas,
        float  $precioTotal,
        string $fechaReserva,
        string $estado,
        string $fechaInicioRecurso,
        string $fechaFinRecurso
    ) {
        $this->id                 = $id;
        $this->usuarioId          = $usuarioId;
        $this->recursoId          = $recursoId;
        $this->recursoNombre      = $recursoNombre;
        $this->presupuestoId      = $presupuestoId;
        $this->numPersonas        = $numPersonas;
        $this->precioTotal        = $precioTotal;
        $this->fechaReserva       = $fechaReserva;
        $this->estado             = $estado;
        $this->fechaInicioRecurso = $fechaInicioRecurso;
        $this->fechaFinRecurso    = $fechaFinRecurso;
    }

    // ── Getters ────────────────────────────────────────────────
    public function getId():                 int    { return $this->id; }
    public function getRecursoNombre():      string { return $this->recursoNombre; }
    public function getPresupuestoId():      int    { return $this->presupuestoId; }
    public function getNumPersonas():        int    { return $this->numPersonas; }
    public function getPrecioTotal():        float  { return $this->precioTotal; }
    public function getFechaReserva():       string { return $this->fechaReserva; }
    public function getEstado():             string { return $this->estado; }
    public function getFechaInicioRecurso(): string { return $this->fechaInicioRecurso; }
    public function getFechaFinRecurso():    string { return $this->fechaFinRecurso; }

    public function estaConfirmada(): bool { return $this->estado === 'Confirmada'; }

    // ── Operaciones estaticas ──────────────────────────────────

    /**
     * Confirma una reserva a partir de un presupuesto.
     *
     * Verifica antes de insertar que hay suficientes plazas disponibles
     * para el numero de personas del presupuesto. Si no hay plazas
     * suficientes devuelve un mensaje de error descriptivo.
     *
     * @return true si la reserva se crea correctamente.
     *         string con el mensaje de error si no es posible.
     */
    public static function confirmar(int $presupuestoId, int $usuarioId): bool|string {
        $con = Bd::getInstancia()->getConexion();

        // Obtener datos del presupuesto (validando que pertenece al usuario)
        $stmt = $con->prepare(
            'SELECT recurso_id, num_personas FROM presupuestos WHERE id = ? AND usuario_id = ?'
        );
        $stmt->bind_param('ii', $presupuestoId, $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return 'Presupuesto no encontrado o no pertenece a este usuario.';
        }

        $pres      = $result->fetch_assoc();
        $recursoId = (int)$pres['recurso_id'];
        $numPers   = (int)$pres['num_personas'];
        $stmt->close();

        // Calcular plazas disponibles en este momento
        $stmt = $con->prepare(
            'SELECT plazas_totales - COALESCE(
                (SELECT SUM(num_personas) FROM reservas
                 WHERE recurso_id = ? AND estado = \'Confirmada\'), 0
             ) AS disponibles
             FROM recursos WHERE id = ?'
        );
        $stmt->bind_param('ii', $recursoId, $recursoId);
        $stmt->execute();
        $result      = $stmt->get_result();
        $fila        = $result->fetch_assoc();
        $disponibles = (int)$fila['disponibles'];
        $stmt->close();

        if ($disponibles < $numPers) {
            return "No hay suficientes plazas disponibles. Plazas libres: {$disponibles}, solicitadas: {$numPers}.";
        }

        // Obtener precio_total del presupuesto
        $stmt = $con->prepare('SELECT precio_total FROM presupuestos WHERE id = ?');
        $stmt->bind_param('i', $presupuestoId);
        $stmt->execute();
        $result      = $stmt->get_result();
        $precioTotal = (float)$result->fetch_assoc()['precio_total'];
        $stmt->close();

        // Insertar la reserva
        $stmt = $con->prepare(
            'INSERT INTO reservas (usuario_id, recurso_id, presupuesto_id, num_personas)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iiii', $usuarioId, $recursoId, $presupuestoId, $numPers);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return 'Error al confirmar la reserva: ' . $error;
        }

        $stmt->close();
        return true;
    }

    /**
     * Devuelve todas las reservas de un usuario ordenadas por fecha desc.
     *
     * @return Reserva[]
     */
    public static function obtenerPorUsuario(int $usuarioId): array {
        $con  = Bd::getInstancia()->getConexion();
        $stmt = $con->prepare(
            "SELECT
                res.id,
                res.usuario_id,
                res.recurso_id,
                rec.nombre AS recurso_nombre,
                res.presupuesto_id,
                res.num_personas,
                p.precio_total,
                DATE_FORMAT(res.fecha_reserva,  '%d/%m/%Y %H:%i') AS fecha_reserva,
                res.estado,
                DATE_FORMAT(rec.fecha_inicio,   '%d/%m/%Y %H:%i') AS fecha_inicio_recurso,
                DATE_FORMAT(rec.fecha_fin,      '%d/%m/%Y %H:%i') AS fecha_fin_recurso
             FROM reservas res
             JOIN recursos    rec ON res.recurso_id    = rec.id
             JOIN presupuestos p  ON res.presupuesto_id = p.id
             WHERE res.usuario_id = ?
             ORDER BY res.fecha_reserva DESC"
        );
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();

        $result   = $stmt->get_result();
        $reservas = [];

        while ($fila = $result->fetch_assoc()) {
            $reservas[] = new Reserva(
                (int)$fila['id'],
                (int)$fila['usuario_id'],
                (int)$fila['recurso_id'],
                $fila['recurso_nombre'],
                (int)$fila['presupuesto_id'],
                (int)$fila['num_personas'],
                (float)$fila['precio_total'],
                $fila['fecha_reserva'],
                $fila['estado'],
                $fila['fecha_inicio_recurso'],
                $fila['fecha_fin_recurso']
            );
        }

        $stmt->close();
        return $reservas;
    }

    /**
     * Anula una reserva confirmada del usuario.
     * Comprueba que la reserva pertenece al usuario para seguridad.
     *
     * @return true si se anula correctamente, string con error si no.
     */
    public static function anular(int $reservaId, int $usuarioId): bool|string {
        $con  = Bd::getInstancia()->getConexion();

        // Verificar que la reserva existe, pertenece al usuario y esta confirmada
        $stmt = $con->prepare(
            "SELECT estado FROM reservas WHERE id = ? AND usuario_id = ?"
        );
        $stmt->bind_param('ii', $reservaId, $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return 'Reserva no encontrada o no pertenece a este usuario.';
        }

        $fila   = $result->fetch_assoc();
        $estado = $fila['estado'];
        $stmt->close();

        if ($estado === 'Anulada') {
            return 'Esta reserva ya estaba anulada.';
        }

        // Actualizar estado a Anulada
        $stmt = $con->prepare("UPDATE reservas SET estado = 'Anulada' WHERE id = ?");
        $stmt->bind_param('i', $reservaId);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return 'Error al anular la reserva: ' . $error;
        }

        $stmt->close();
        return true;
    }
}
